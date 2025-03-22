<template>
  <div class="organizations">
    <div class="organizations-header">
      <h2>Organisationer</h2>
      <BaseButton variant="primary" @click="openCreateModal">
        Skapa ny organisation
      </BaseButton>
    </div>
    
    <!-- Loading and Error States -->
    <div class="loading-indicator" v-if="loading">
      <p>Laddar organisationer...</p>
    </div>
    
    <div class="error-message" v-if="error">
      <p>{{ error }}</p>
    </div>
    
    <!-- Organizations Table -->
    <div class="organizations-content" v-if="!loading && !error">
      <div class="organizations-tree">
        <table class="organizations-table">
          <thead>
            <tr>
              <th>Namn</th>
              <th>Antal underorganisationer</th>
              <th>Skapad</th>
              <th>Åtgärder</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="org in organizationFlatList" 
              :key="org.id" 
              :class="{ 'child-org': org.level > 0 }"
            >
              <td>
                <span 
                  class="indent" 
                  :style="{ width: org.level * 20 + 'px' }"
                ></span>
                <span class="org-name">{{ org.name }}</span>
              </td>
              <td>{{ org.children_count || 0 }}</td>
              <td>{{ formatDate(org.created_at) }}</td>
              <td class="actions">
                <BaseButton size="small" @click="viewOrganization(org)">
                  Visa
                </BaseButton>
                <BaseButton size="small" @click="editOrganization(org)">
                  Redigera
                </BaseButton>
                <BaseButton 
                  size="small" 
                  variant="danger" 
                  @click="confirmDelete(org)" 
                  :disabled="org.children_count > 0"
                >
                  Ta bort
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Organization Modals -->
    <OrganizationFormModal
      v-if="showCreateForm"
      :organization="newOrganization"
      :loading="createLoading"
      :parent-options="organizations"
      @close="showCreateForm = false"
      @submit="onCreateFormSubmit"
    />
    
    <OrganizationFormModal
      v-if="showEditForm"
      :organization="editedOrganization"
      :loading="updateLoading"
      :parent-options="validParentOrganizations"
      @close="showEditForm = false"
      @submit="updateOrganization"
      title="Redigera organisation"
      submit-text="Spara"
    />

    <OrganizationViewModal
      v-if="showViewModal"
      :organization="selectedOrganization"
      :users="organizationUsers"
      :resources="organizationResources"
      :users-loading="usersLoading"
      :resources-loading="resourcesLoading"
      @close="showViewModal = false"
      @add-user="showAddUserForm = true"
      @edit-user="editUserRole"
      @remove-user="confirmRemoveUser"
      @add-resource="openResourceForm(false)"
      @edit-resource="editResource"
      @delete-resource="confirmDeleteResource"
      @change-tab="handleTabChange"
    />
    
    <ConfirmDialog
      v-if="showDeleteConfirmation"
      title="Ta bort organisation"
      :message="`Är du säker på att du vill ta bort ${organizationToDelete?.name}?`"
      confirmText="Ta bort"
      @confirm="deleteOrganization"
      @cancel="showDeleteConfirmation = false"
    />
    
    <!-- User Management Modals -->
    <UserFormModal
      v-if="showAddUserForm"
      :users="availableUsers"
      :selected-organization="selectedOrganization"
      :loading="addUserLoading"
      @close="showAddUserForm = false"
      @submit="addUser"
    />
    
    <UserRoleModal
      v-if="showEditUserModal"
      :user="selectedUser"
      :organization="selectedOrganization"
      :loading="updateUserLoading"
      @close="showEditUserModal = false"
      @save="saveUserRole"
      @remove="confirmRemoveFromCurrentOrg"
    />
    
    <!-- Resource Management Modal -->
    <ResourceFormModal
      v-if="showResourceForm"
      :resource="currentResource"
      :is-editing="!!editingResource"
      :loading="resourceLoading"
      @close="showResourceForm = false"
      @submit="saveResource"
    />
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import { useOrganizationsStore } from '@/stores/organizations';
import { useUsersStore } from '@/stores/users';
import { useResourcesStore } from '@/stores/resources';
import { formatDate } from '@/utils/dateUtils';
import { translateRole, getRoleClass } from '@/utils/roleUtils';
import BaseButton from '@/components/BaseButton.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import OrganizationFormModal from '@/components/organizations/OrganizationFormModal.vue';
import OrganizationViewModal from '@/components/organizations/OrganizationViewModal.vue';
import UserFormModal from '@/components/users/UserFormModal.vue';
import UserRoleModal from '@/components/users/UserRoleModal.vue';
import ResourceFormModal from '@/components/resources/ResourceFormModal.vue';

export default defineComponent({
  name: 'Organizations',
  
  components: {
    BaseButton,
    ConfirmDialog,
    OrganizationFormModal,
    OrganizationViewModal,
    UserFormModal,
    UserRoleModal,
    ResourceFormModal
  },
  
  data() {
    return {
      // Organization state
      loading: false,
      error: null,
      showCreateForm: false,
      showEditForm: false,
      showViewModal: false,
      createLoading: false,
      updateLoading: false,
      
      // Organization data
      newOrganization: {
        name: '',
        parent_id: null
      },
      editedOrganization: {
        id: null,
        name: '',
        parent_id: null
      },
      selectedOrganization: null,
      organizationToDelete: null,
      showDeleteConfirmation: false,
      
      // User management
      activeTab: 'users',
      usersLoading: false,
      organizationUsers: [],
      showAddUserForm: false,
      addUserLoading: false,
      
      // User editing
      showEditUserModal: false,
      selectedUser: null,
      updateUserLoading: false,
      
      // Resource management
      resourcesLoading: false,
      organizationResources: [],
      showResourceForm: false,
      editingResource: null,
      resourceLoading: false,
      currentResource: {
        name: '',
        description: '',
        type: 'room',
        meta: {}
      }
    };
  },
  
  computed: {
    organizations() {
      return this.organizationsStore.organizations;
    },
    
    organizationFlatList() {
      return this.organizationsStore.getOrganizationFlatList;
    },
    
    allUsers() {
      return this.usersStore.users;
    },
    
    validParentOrganizations() {
      if (!this.editedOrganization?.id) return this.organizations;
      
      // Function to check if org is a descendant of the current org
      const isDescendant = (org, targetId) => {
        if (!org) return false;
        if (org.id === targetId) return true;
        
        const children = this.organizations.filter(o => o.parent_id === org.id);
        return children.some(child => isDescendant(child, targetId));
      };
      
      // Filter out the edited org and its descendants
      return this.organizations.filter(org => 
        org.id !== this.editedOrganization.id && 
        !isDescendant(org, this.editedOrganization.id)
      );
    },
    
    availableUsers() {
      // Filter users who are not already in the organization
      const existingUserIds = this.organizationUsers.map(u => u.user_id);
      return this.allUsers.filter(user => !existingUserIds.includes(user.ID));
    }
  },
  
  created() {
    this.organizationsStore = useOrganizationsStore();
    this.usersStore = useUsersStore();
    this.resourcesStore = useResourcesStore();
    this.loadOrganizations();
  },
  
  methods: {
    // Organization CRUD operations
    async loadOrganizations() {
      this.loading = true;
      this.error = null;
      
      try {
        await this.organizationsStore.fetchOrganizations();
      } catch (err) {
        this.error = 'Kunde inte ladda organisationer: ' + (err.message || 'Okänt fel');
      } finally {
        this.loading = false;
      }
    },
    
    openCreateModal() {
      this.newOrganization = {
        name: '',
        parent_id: null
      };
      this.showCreateForm = true;
    },
    
    onCreateFormSubmit(formData) {
      this.newOrganization = { ...formData };
      this.createOrganization();
    },
    
    async createOrganization() {
      this.createLoading = true;
      
      try {
        // Validate organization data
        if (!this.newOrganization.name || !this.newOrganization.name.trim()) {
          this.error = 'Organisationsnamn måste anges';
          this.createLoading = false;
          return;
        }
        
        console.log('Creating organization with data:', JSON.stringify(this.newOrganization));
        await this.organizationsStore.createOrganization(this.newOrganization);
        this.showCreateForm = false;
        this.newOrganization = { name: '', parent_id: null };
      } catch (err) {
        this.error = 'Kunde inte skapa organisation: ' + (err.message || 'Okänt fel');
      } finally {
        this.createLoading = false;
      }
    },
    
    editOrganization(organization) {
      this.editedOrganization = { ...organization };
      this.showEditForm = true;
    },
    
    async updateOrganization() {
      this.updateLoading = true;
      
      try {
        await this.organizationsStore.updateOrganization(this.editedOrganization);
        this.showEditForm = false;
      } catch (err) {
        this.error = 'Kunde inte uppdatera organisation: ' + (err.message || 'Okänt fel');
      } finally {
        this.updateLoading = false;
      }
    },
    
    confirmDelete(organization) {
      if (organization.children_count > 0) return;
      
      this.organizationToDelete = organization;
      this.showDeleteConfirmation = true;
    },
    
    async deleteOrganization() {
      if (!this.organizationToDelete) return;
      
      try {
        await this.organizationsStore.deleteOrganization(this.organizationToDelete.id);
        this.showDeleteConfirmation = false;
        this.organizationToDelete = null;
      } catch (err) {
        this.error = 'Kunde inte ta bort organisation: ' + (err.message || 'Okänt fel');
      }
    },
    
    // View organization details
    async viewOrganization(organization) {
      this.selectedOrganization = organization;
      this.activeTab = 'users';
      this.showViewModal = true;
      
      // Load users on open
      await this.loadOrganizationUsers(organization.id);
    },
    
    async handleTabChange(tab) {
      this.activeTab = tab;
      
      if (tab === 'users' && this.selectedOrganization) {
        await this.loadOrganizationUsers(this.selectedOrganization.id);
      } else if (tab === 'resources' && this.selectedOrganization) {
        await this.loadOrganizationResources(this.selectedOrganization.id);
      }
    },
    
    // Load organization data
    async loadOrganizationUsers(organizationId) {
      if (!organizationId) return;
      
      this.usersLoading = true;
      this.organizationUsers = [];
      
      try {
        const response = await this.usersStore.fetchUsersByOrganization(organizationId);
        this.organizationUsers = response;
        
        // If users have other organizations, fetch any missing organizations
        const otherOrgIds = new Set();
        for (const user of this.organizationUsers) {
          if (user.organizations?.length) {
            for (const orgId of user.organizations) {
              if (orgId !== organizationId) {
                otherOrgIds.add(orgId);
              }
            }
          }
        }
        
        if (otherOrgIds.size > 0) {
          await this.fetchMissingOrganizations([...otherOrgIds]);
        }
      } catch (err) {
        this.error = 'Kunde inte ladda användare: ' + (err.message || 'Okänt fel');
      } finally {
        this.usersLoading = false;
      }
    },
    
    async loadOrganizationResources(organizationId) {
      if (!organizationId) return;
      
      this.resourcesLoading = true;
      this.organizationResources = [];
      
      try {
        this.organizationResources = await this.resourcesStore.fetchOrganizationResources(organizationId);
      } catch (err) {
        this.error = 'Kunde inte ladda resurser: ' + (err.message || 'Okänt fel');
      } finally {
        this.resourcesLoading = false;
      }
    },
    
    // User management
    editUserRole(user) {
      this.selectedUser = user;
      this.showEditUserModal = true;
    },
    
    async saveUserRole(userData) {
      if (!this.selectedUser || !this.selectedOrganization) return;
      
      this.updateUserLoading = true;
      
      try {
        await this.usersStore.updateUserRole({
          user_id: this.selectedUser.user_id,
          organization_id: this.selectedOrganization.id,
          role: userData.role
        });
        
        // Refresh user list
        await this.loadOrganizationUsers(this.selectedOrganization.id);
        this.showEditUserModal = false;
      } catch (err) {
        this.error = 'Kunde inte uppdatera användarens roll: ' + (err.message || 'Okänt fel');
      } finally {
        this.updateUserLoading = false;
      }
    },
    
    confirmRemoveUser(user) {
      this.selectedUser = user;
      this.confirmRemoveFromCurrentOrg();
    },
    
    confirmRemoveFromCurrentOrg() {
      if (confirm(`Är du säker på att du vill ta bort ${this.selectedUser?.user_data?.display_name} från denna organisation?`)) {
        this.removeFromCurrentOrg();
      }
    },
    
    async removeFromCurrentOrg() {
      if (!this.selectedUser || !this.selectedOrganization) return;
      
      try {
        await this.usersStore.removeUserFromOrganization({
          user_id: this.selectedUser.user_id,
          organization_id: this.selectedOrganization.id
        });
        
        await this.loadOrganizationUsers(this.selectedOrganization.id);
        this.showEditUserModal = false;
      } catch (err) {
        this.error = 'Kunde inte ta bort användaren från organisationen: ' + (err.message || 'Okänt fel');
      }
    },
    
    async addUser(userData) {
      if (!this.selectedOrganization || !userData.user_id) return;
      
      this.addUserLoading = true;
      
      try {
        await this.usersStore.addUserToOrganization({
          user_id: userData.user_id,
          organization_id: this.selectedOrganization.id,
          role: userData.role
        });
        
        await this.loadOrganizationUsers(this.selectedOrganization.id);
        this.showAddUserForm = false;
      } catch (err) {
        this.error = 'Kunde inte lägga till användaren: ' + (err.message || 'Okänt fel');
      } finally {
        this.addUserLoading = false;
      }
    },
    
    // Resource management
    openResourceForm(isEditing) {
      this.editingResource = null;
      this.currentResource = {
        name: '',
        description: '',
        type: 'room',
        meta: {},
        organization_id: this.selectedOrganization?.id
      };
      this.showResourceForm = true;
    },
    
    editResource(resource) {
      this.editingResource = resource;
      this.currentResource = { ...resource };
      this.showResourceForm = true;
    },
    
    async saveResource() {
      if (!this.selectedOrganization) return;
      
      this.resourceLoading = true;
      
      try {
        if (this.editingResource) {
          await this.resourcesStore.updateResource(this.currentResource);
        } else {
          await this.resourcesStore.createResource({
            ...this.currentResource,
            organization_id: this.selectedOrganization.id
          });
        }
        
        await this.loadOrganizationResources(this.selectedOrganization.id);
        this.showResourceForm = false;
        this.resetResourceForm();
      } catch (err) {
        this.error = 'Kunde inte spara resursen: ' + (err.message || 'Okänt fel');
      } finally {
        this.resourceLoading = false;
      }
    },
    
    resetResourceForm() {
      this.editingResource = null;
      this.currentResource = {
        name: '',
        description: '',
        type: 'room',
        meta: {}
      };
    },
    
    confirmDeleteResource(resource) {
      if (confirm(`Är du säker på att du vill ta bort resursen ${resource.name}?`)) {
        this.removeResource(resource);
      }
    },
    
    async removeResource(resource) {
      if (!this.selectedOrganization) return;
      
      try {
        await this.resourcesStore.deleteResource(resource.id);
        await this.loadOrganizationResources(this.selectedOrganization.id);
      } catch (err) {
        this.error = 'Kunde inte ta bort resursen: ' + (err.message || 'Okänt fel');
      }
    },
    
    // Helper methods
    async fetchMissingOrganizations(orgIds) {
      if (!orgIds.length) return;
      
      // Filter out organizations we already have
      const knownOrgIds = this.organizations.map(org => org.id);
      const missingOrgIds = orgIds.filter(id => !knownOrgIds.includes(id));
      
      if (missingOrgIds.length === 0) return;
      
      try {
        await this.organizationsStore.fetchOrganizationsByIds(missingOrgIds);
      } catch (err) {
        console.error('Error fetching missing organizations:', err);
      }
    },
    
    getOrganizationName(orgId) {
      const org = this.organizations.find(o => o.id === orgId);
      return org ? org.name : 'Okänd organisation';
    },
    
    formatDate,
    translateRole,
    getRoleClass
  }
});
</script>

<style scoped>
.organizations {
  padding: 20px;
}

.organizations-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.organizations-header h2 {
  margin: 0;
  font-size: 1.5rem;
}

.loading-indicator, 
.error-message {
  padding: 20px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.error-message {
  background-color: #ffebee;
  color: #c62828;
}

/* Table styling */
.organizations-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #e0e0e0;
}

.organizations-table th,
.organizations-table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.organizations-table th {
  background-color: #f5f5f5;
  font-weight: 600;
}

.organizations-table .actions {
  display: flex;
  gap: 8px;
}

.child-org {
  background-color: #fafafa;
}

.indent {
  display: inline-block;
}

.org-name {
  font-weight: 500;
}

/* Make sure background styles carry from the original */
.organization-details {
  margin-bottom: 20px;
}

.organization-details p {
  margin: 8px 0;
}

.tab-header {
  display: flex;
  border-bottom: 1px solid #e0e0e0;
  margin-bottom: 16px;
}

.tab-button {
  padding: 8px 16px;
  border: none;
  background: none;
  cursor: pointer;
  font-size: 1rem;
  opacity: 0.7;
}

.tab-button.active {
  border-bottom: 2px solid #1976d2;
  opacity: 1;
  font-weight: 500;
}

.tab-actions {
  margin-bottom: 16px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.data-table th {
  position: sticky;
  top: 0;
  background: white;
  z-index: 1;
}

.org-chips-container {
  max-width: 300px;
}

.org-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.org-chip {
  display: inline-flex;
  align-items: center;
  background-color: #f1f1f1;
  border-radius: 16px;
  padding: 4px 8px;
  font-size: 0.85rem;
  max-width: 100%;
}

.org-chip-role {
  background-color: rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  padding: 2px 6px;
  margin-left: 4px;
  font-size: 0.75rem;
}

.org-chip.role-admin {
  background-color: #e3f2fd;
}

.org-chip.role-scheduler {
  background-color: #e8f5e9;
}

.org-chip.role-employee {
  background-color: #f1f8e9;
}
</style>
