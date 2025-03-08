<template>
  <div class="organizations">
    <div class="organizations-header">
      <h2>Organisationer</h2>
      <button class="btn btn-primary" @click="showCreateForm = true">
        Skapa ny organisation
      </button>
    </div>
    
    <div class="loading-indicator" v-if="loading">
      <p>Laddar organisationer...</p>
    </div>
    
    <div class="error-message" v-if="error">
      <p>{{ error }}</p>
    </div>
    
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
            <tr v-for="org in organizationFlatList" :key="org.id" :class="{ 'child-org': org.level > 0 }">
              <td>
                <span class="indent" :style="{ width: org.level * 20 + 'px' }"></span>
                <span class="org-name">{{ org.name }}</span>
              </td>
              <td>{{ org.children_count || 0 }}</td>
              <td>{{ formatDate(org.created_at) }}</td>
              <td class="actions">
                <button class="btn btn-small" @click="viewOrganization(org)">
                  Visa
                </button>
                <button class="btn btn-small" @click="editOrganization(org)">
                  Redigera
                </button>
                <button class="btn btn-small btn-danger" @click="confirmDelete(org)" :disabled="org.children_count > 0">
                  Ta bort
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Create Organization Modal -->
    <div class="modal" v-if="showCreateForm">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Skapa ny organisation</h3>
          <button class="close-button" @click="showCreateForm = false">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createOrganization">
            <div class="form-group">
              <label for="name">Namn</label>
              <input type="text" id="name" v-model="newOrganization.name" required>
            </div>
            <div class="form-group">
              <label for="parent_id">Föräldraorganisation</label>
              <select id="parent_id" v-model="newOrganization.parent_id">
                <option :value="null">Ingen (huvudorganisation)</option>
                <option v-for="org in organizations" :key="org.id" :value="org.id">
                  {{ org.name }}
                </option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="showCreateForm = false">
                Avbryt
              </button>
              <button type="submit" class="btn btn-primary" :disabled="createLoading">
                {{ createLoading ? 'Skapar...' : 'Skapa' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Edit Organization Modal -->
    <div class="modal" v-if="showEditForm">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Redigera organisation</h3>
          <button class="close-button" @click="showEditForm = false">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updateOrganization">
            <div class="form-group">
              <label for="edit-name">Namn</label>
              <input type="text" id="edit-name" v-model="editedOrganization.name" required>
            </div>
            <div class="form-group">
              <label for="edit-parent_id">Föräldraorganisation</label>
              <select id="edit-parent_id" v-model="editedOrganization.parent_id">
                <option :value="null">Ingen (huvudorganisation)</option>
                <option v-for="org in validParentOrganizations" :key="org.id" :value="org.id">
                  {{ org.name }}
                </option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="showEditForm = false">
                Avbryt
              </button>
              <button type="submit" class="btn btn-primary" :disabled="updateLoading">
                {{ updateLoading ? 'Sparar...' : 'Spara' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- View Organization Modal -->
    <div class="modal" v-if="showViewModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ selectedOrganization ? selectedOrganization.name : 'Organisation' }}</h3>
          <button class="close-button" @click="showViewModal = false">&times;</button>
        </div>
        <div class="modal-body" v-if="selectedOrganization">
          <div class="organization-details">
            <p><strong>ID:</strong> {{ selectedOrganization.id }}</p>
            <p><strong>Namn:</strong> {{ selectedOrganization.name }}</p>
            <p><strong>Föräldraorganisation:</strong> 
              {{ getParentOrganizationName(selectedOrganization.parent_id) }}
            </p>
            <p><strong>Skapad:</strong> {{ formatDate(selectedOrganization.created_at) }}</p>
            <p><strong>Senast uppdaterad:</strong> {{ formatDate(selectedOrganization.updated_at) }}</p>
            <p><strong>Antal underorganisationer:</strong> {{ selectedOrganization.children_count || 0 }}</p>
          </div>
          
          <div class="organization-tabs">
            <div class="tab-header">
              <button 
                class="tab-button" 
                :class="{ active: activeTab === 'users' }"
                @click="activeTab = 'users'"
              >
                Användare
              </button>
              <button 
                class="tab-button" 
                :class="{ active: activeTab === 'resources' }"
                @click="activeTab = 'resources'"
              >
                Resurser
              </button>
            </div>
            
            <div class="tab-content">
              <!-- Users Tab -->
              <div v-if="activeTab === 'users'" class="tab-pane">
                <div class="tab-actions">
                  <button class="btn btn-small" @click="showAddUserForm = true">
                    Lägg till användare
                  </button>
                </div>
                
                <div v-if="usersLoading">Laddar användare...</div>
                <div v-else-if="organizationUsers.length === 0">
                  Inga användare i denna organisation.
                </div>
                <table v-else class="data-table">
                  <thead>
                    <tr>
                      <th>Namn</th>
                      <th>E-post</th>
                      <th>Roll</th>
                      <th>Åtgärder</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="user in organizationUsers" :key="user.id">
                      <td>{{ user.user_data.display_name }}</td>
                      <td>{{ user.user_data.user_email }}</td>
                      <td>{{ translateRole(user.role) }}</td>
                      <td class="actions">
                        <button class="btn btn-small" @click="editUserRole(user)">
                          Ändra roll
                        </button>
                        <button class="btn btn-small btn-danger" @click="confirmRemoveUser(user)">
                          Ta bort
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <!-- Resources Tab -->
              <div v-if="activeTab === 'resources'" class="tab-pane">
                <div class="tab-actions">
                  <router-link 
                    :to="{ name: 'resources', query: { organization: selectedOrganization.id } }" 
                    class="btn btn-small"
                  >
                    Hantera resurser
                  </router-link>
                </div>
                
                <div v-if="resourcesLoading">Laddar resurser...</div>
                <div v-else-if="organizationResources.length === 0">
                  Inga resurser i denna organisation.
                </div>
                <table v-else class="data-table">
                  <thead>
                    <tr>
                      <th>Namn</th>
                      <th>Beskrivning</th>
                      <th>Färg</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="resource in organizationResources" :key="resource.id">
                      <td>{{ resource.name }}</td>
                      <td>{{ resource.description || '-' }}</td>
                      <td>
                        <span class="color-box" :style="{ backgroundColor: resource.color }"></span>
                        {{ resource.color }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" v-if="showDeleteConfirmation">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Bekräfta borttagning</h3>
          <button class="close-button" @click="showDeleteConfirmation = false">&times;</button>
        </div>
        <div class="modal-body">
          <p>Är du säker på att du vill ta bort organisationen "{{ organizationToDelete ? organizationToDelete.name : '' }}"?</p>
          <p class="warning">Denna åtgärd kan inte ångras!</p>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="showDeleteConfirmation = false">
              Avbryt
            </button>
            <button type="button" class="btn btn-danger" @click="deleteOrganization" :disabled="deleteLoading">
              {{ deleteLoading ? 'Tar bort...' : 'Ta bort' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal" v-if="showAddUserForm">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Lägg till användare i organisationen</h3>
          <button class="close-button" @click="showAddUserForm = false">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="addUser">
            <div class="form-group">
              <label for="user-select">Välj användare</label>
              <select id="user-select" v-model="newUserAssignment.user_id" required>
                <option disabled value="">Välj en användare</option>
                <option v-for="user in allUsers" :key="user.id" :value="user.id">
                  {{ user.user_data.display_name }} ({{ user.user_data.user_email }})
                </option>
              </select>
            </div>
            <div class="form-group">
              <label for="role-select">Välj roll</label>
              <select id="role-select" v-model="newUserAssignment.role" required>
                <option disabled value="">Välj roll</option>
                <option value="base">Bas</option>
                <option value="scheduler">Schemaläggare</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="showAddUserForm = false">
                Avbryt
              </button>
              <button type="submit" class="btn btn-primary" :disabled="addUserLoading">
                {{ addUserLoading ? 'Lägger till...' : 'Lägg till' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useOrganizationsStore } from '@/stores/organizations';
import { useUsersStore } from '@/stores/users';
import { useResourcesStore } from '@/stores/resources';

export default {
  name: 'Organizations',
  data() {
    return {
      loading: false,
      error: null,
      showCreateForm: false,
      showEditForm: false,
      showViewModal: false,
      showDeleteConfirmation: false,
      showAddUserForm: false,
      createLoading: false,
      updateLoading: false,
      deleteLoading: false,
      usersLoading: false,
      resourcesLoading: false,
      addUserLoading: false,
      newOrganization: {
        name: '',
        parent_id: null
      },
      editedOrganization: {
        id: null,
        name: '',
        parent_id: null
      },
      newUserAssignment: {
        user_id: '',
        role: ''
      },
      selectedOrganization: null,
      organizationToDelete: null,
      activeTab: 'users',
      organizationUsers: [],
      organizationResources: []
    };
  },
  computed: {
    organizations() {
      return this.organizationsStore.organizations;
    },
    organizationFlatList() {
      return this.organizationsStore.getOrganizationFlatList;
    },
    validParentOrganizations() {
      if (!this.editedOrganization || !this.editedOrganization.id) {
        return this.organizations;
      }
      
      // Filter out the current organization and its descendants
      return this.organizations.filter(org => {
        // Can't be its own parent
        if (org.id === this.editedOrganization.id) {
          return false;
        }
        
        // Can't be a descendant
        if (org.path && org.path.includes('/' + this.editedOrganization.id + '/')) {
          return false;
        }
        
        return true;
      });
    },
    allUsers() {
      return this.usersStore.allUsers;
    }
  },
  created() {
    this.organizationsStore = useOrganizationsStore();
    this.usersStore = useUsersStore();
    this.resourcesStore = useResourcesStore();
    
    this.loadOrganizations();
  },
  methods: {
    async loadOrganizations() {
      this.loading = true;
      this.error = null;
      
      try {
        await this.organizationsStore.fetchOrganizations();
      } catch (error) {
        console.error('Error loading organizations:', error);
        this.error = 'Det gick inte att ladda organisationer: ' + error.message;
      } finally {
        this.loading = false;
      }
    },
    
    async createOrganization() {
      this.createLoading = true;
      
      try {
        await this.organizationsStore.createOrganization(this.newOrganization);
        this.showCreateForm = false;
        this.newOrganization = {
          name: '',
          parent_id: null
        };
      } catch (error) {
        console.error('Error creating organization:', error);
        alert('Det gick inte att skapa organisationen: ' + error.message);
      } finally {
        this.createLoading = false;
      }
    },
    
    editOrganization(organization) {
      this.editedOrganization = {
        id: organization.id,
        name: organization.name,
        parent_id: organization.parent_id
      };
      this.showEditForm = true;
    },
    
    async updateOrganization() {
      this.updateLoading = true;
      
      try {
        await this.organizationsStore.updateOrganization(
          this.editedOrganization.id,
          {
            name: this.editedOrganization.name,
            parent_id: this.editedOrganization.parent_id
          }
        );
        this.showEditForm = false;
      } catch (error) {
        console.error('Error updating organization:', error);
        alert('Det gick inte att uppdatera organisationen: ' + error.message);
      } finally {
        this.updateLoading = false;
      }
    },
    
    confirmDelete(organization) {
      if (organization.children_count > 0) {
        alert('Kan inte ta bort en organisation med underorganisationer.');
        return;
      }
      
      this.organizationToDelete = organization;
      this.showDeleteConfirmation = true;
    },
    
    async deleteOrganization() {
      if (!this.organizationToDelete) {
        return;
      }
      
      this.deleteLoading = true;
      
      try {
        await this.organizationsStore.deleteOrganization(this.organizationToDelete.id);
        this.showDeleteConfirmation = false;
        this.organizationToDelete = null;
      } catch (error) {
        console.error('Error deleting organization:', error);
        alert('Det gick inte att ta bort organisationen: ' + error.message);
      } finally {
        this.deleteLoading = false;
      }
    },
    
    async viewOrganization(organization) {
      this.selectedOrganization = organization;
      this.activeTab = 'users';
      this.showViewModal = true;
      
      // Load users for this organization
      await this.loadOrganizationUsers(organization.id);
    },
    
    async loadOrganizationUsers(organizationId) {
      this.usersLoading = true;
      
      try {
        await this.usersStore.fetchUsersByOrganization(organizationId);
        this.organizationUsers = this.usersStore.users;
      } catch (error) {
        console.error('Error loading organization users:', error);
      } finally {
        this.usersLoading = false;
      }
    },
    
    async loadOrganizationResources(organizationId) {
      this.resourcesLoading = true;
      
      try {
        await this.resourcesStore.fetchResourcesByOrganization(organizationId);
        this.organizationResources = this.resourcesStore.resources;
      } catch (error) {
        console.error('Error loading organization resources:', error);
      } finally {
        this.resourcesLoading = false;
      }
    },
    
    getParentOrganizationName(parentId) {
      if (!parentId) {
        return 'Ingen (huvudorganisation)';
      }
      
      const parent = this.organizations.find(org => org.id === parentId);
      return parent ? parent.name : 'Okänd';
    },
    
    formatDate(dateString) {
      if (!dateString) {
        return '-';
      }
      
      const date = new Date(dateString);
      return date.toLocaleString('sv-SE');
    },
    
    translateRole(role) {
      switch (role) {
        case 'admin':
          return 'Admin';
        case 'scheduler':
          return 'Schemaläggare';
        case 'base':
          return 'Bas';
        default:
          return role;
      }
    },
    async editUserRole(user) {
      const newRole = window.prompt("Ange ny roll (base, scheduler, admin)", user.role);
      if (newRole && ['base', 'scheduler', 'admin'].includes(newRole)) {
        try {
          await this.usersStore.updateUserRole(this.selectedOrganization.id, user.user_id, newRole);
          await this.loadOrganizationUsers(this.selectedOrganization.id);
        } catch (error) {
          alert("Fel vid uppdatering av roll: " + error.message);
        }
      }
    },
    confirmRemoveUser(user) {
      if (confirm("Är du säker på att du vill ta bort " + user.user_data.display_name + " från organisationen?")) {
        this.removeUser(user);
      }
    },
    async removeUser(user) {
      try {
        await this.usersStore.removeUserFromOrganization(this.selectedOrganization.id, user.user_id);
        await this.loadOrganizationUsers(this.selectedOrganization.id);
      } catch (error) {
        alert("Fel vid borttagning: " + error.message);
      }
    },
    async addUser() {
      this.addUserLoading = true;
      try {
        await this.usersStore.addUserToOrganization(this.selectedOrganization.id, this.newUserAssignment);
        await this.loadOrganizationUsers(this.selectedOrganization.id);
        this.showAddUserForm = false;
        this.newUserAssignment = { user_id: '', role: '' };
      } catch (error) {
        alert("Fel vid tillägg av användare: " + error.message);
      } finally {
        this.addUserLoading = false;
      }
    }
  },
  watch: {
    activeTab(newTab) {
      if (newTab === 'resources' && this.selectedOrganization) {
        this.loadOrganizationResources(this.selectedOrganization.id);
      }
    }
  }
};
</script>

<style scoped>
.organizations {
  padding: 20px 0;
}

.organizations-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.organizations-table {
  width: 100%;
  border-collapse: collapse;
}

.organizations-table th,
.organizations-table td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #e5e5e5;
}

.organizations-table th {
  background-color: #f9f9f9;
  font-weight: bold;
}

.child-org {
  background-color: #f9f9f9;
}

.indent {
  display: inline-block;
}

.org-name {
  font-weight: bold;
}

.actions {
  white-space: nowrap;
}

.actions .btn {
  margin-right: 5px;
}

.btn {
  display: inline-block;
  background-color: #0073aa;
  color: #fff;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: #005177;
}

.btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #0073aa;
}

.btn-secondary {
  background-color: #6c757d;
}

.btn-danger {
  background-color: #dc3545;
}

.btn-danger:hover {
  background-color: #bd2130;
}

.btn-small {
  padding: 4px 8px;
  font-size: 0.9em;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: #fff;
  border-radius: 4px;
  width: 500px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e5e5e5;
}

.modal-header h3 {
  margin: 0;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5em;
  cursor: pointer;
  color: #6c757d;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.warning {
  color: #dc3545;
  font-weight: bold;
}

.organization-tabs {
  margin-top: 20px;
}

.tab-header {
  display: flex;
  border-bottom: 1px solid #e5e5e5;
}

.tab-button {
  padding: 10px 15px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
}

.tab-button.active {
  border-bottom-color: #0073aa;
  font-weight: bold;
}

.tab-content {
  padding: 15px 0;
}

.tab-actions {
  margin-bottom: 15px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 8px;
  text-align: left;
  border-bottom: 1px solid #e5e5e5;
}

.data-table th {
  background-color: #f9f9f9;
  font-weight: bold;
}

.color-box {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 1px solid #ccc;
  margin-right: 5px;
  vertical-align: middle;
}
</style>
