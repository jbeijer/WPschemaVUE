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
    <div class="modal" v-if="showCreateForm" @click.self="showCreateForm = false">
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
    <div class="modal" v-if="showEditForm" @click.self="showEditForm = false">
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
    <div class="modal" v-if="showViewModal" @click.self="showViewModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ selectedOrganization ? selectedOrganization.name : 'Organisation' }}</h3>
          <button class="close-button" @click="showViewModal = false">&times;</button>
        </div>
        <div class="modal-body" v-if="selectedOrganization">
          <div class="organization-details">
            <p><strong>ID:</strong> {{ selectedOrganization.id }}</p>
            <p><strong>Namn:</strong> {{ selectedOrganization.name }}</p>
            <p><strong>Föräldraorganisation:</strong> {{ getParentOrganizationName(selectedOrganization.parent_id) }}</p>
            <p><strong>Skapad:</strong> {{ formatDate(selectedOrganization.created_at) }}</p>
            <p><strong>Senast uppdaterad:</strong> {{ formatDate(selectedOrganization.updated_at) }}</p>
            <p><strong>Antal underorganisationer:</strong> {{ selectedOrganization.children_count || 0 }}</p>
          </div>

          <div class="tabs">
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
              <div v-if="activeTab === 'users'" class="tab-pane">
                <div class="tab-actions">
                  <button class="btn btn-primary" @click="showAddUserForm = true">
                    Lägg till användare
                  </button>
                </div>

                <div class="user-list" v-if="!usersLoading">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Namn</th>
                        <th>E-post</th>
                        <th>Roll</th>
                        <th>Andra organisationer</th>
                        <th>Åtgärder</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="user in organizationUsers" :key="user.id">
                        <td>{{ user.user_data.display_name }}</td>
                        <td>{{ user.user_data.user_email }}</td>
                        <td>{{ translateRole(user.role) }}</td>
                        <td>
                          <div class="org-chips-container">
                            <span v-if="!user.organizations || user.organizations.length <= 1" class="no-orgs">
                              <i class="dashicons dashicons-businessman"></i> Endast denna organisation
                            </span>
                            <div v-else class="org-chips">
                              <div v-for="orgId in user.organizations" :key="orgId" 
                                   v-if="orgId !== selectedOrganization.id"
                                   class="org-chip" 
                                   :class="getRoleClass(user.organization_roles?.[orgId])">
                                <span class="org-chip-icon">
                                  <i class="dashicons dashicons-building"></i>
                                </span>
                                <span class="org-chip-name">{{ getOrganizationName(orgId) }}</span>
                                <span class="org-chip-role">{{ translateRole(user.organization_roles?.[orgId] || 'unknown') }}</span>
                              </div>
                            </div>
                          </div>
                        </td>
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
                <div v-else class="loading">
                  Laddar användare...
                </div>
              </div>

              <div v-if="activeTab === 'resources'" class="tab-pane">
                <div class="tab-actions">
                  <button class="btn btn-primary" @click="showResourceForm = true">
                    Hantera resurser
                  </button>
                </div>

                <div class="resource-list" v-if="!resourcesLoading">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>Namn</th>
                        <th>Beskrivning</th>
                        <th>Tillgänglighet</th>
                        <th>Åtgärder</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="resource in organizationResources" :key="resource.id">
                        <td>{{ resource.name }}</td>
                        <td>{{ resource.description || 'Ingen beskrivning' }}</td>
                        <td>
                          <span v-if="resource.is_24_7">Tillgänglig 24/7</span>
                          <span v-else>{{ resource.start_time }} - {{ resource.end_time }}</span>
                        </td>
                        <td class="actions">
                          <button class="btn btn-small" @click="editResource(resource)">
                            Redigera
                          </button>
                          <button class="btn btn-small btn-danger" @click="confirmDeleteResource(resource)">
                            Ta bort
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div v-else class="loading">
                  Laddar resurser...
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" v-if="showDeleteConfirmation" @click.self="showDeleteConfirmation = false">
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
    <div class="modal" v-if="showAddUserForm" @click.self="showAddUserForm = false">
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

    <!-- Edit User Modal -->
    <div class="modal" v-if="showEditUserModal" @click.self="showEditUserModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Hantera användare: {{ selectedUser?.user_data?.display_name }}</h3>
          <button class="close-button" @click="showEditUserModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveUserRole">
            <div class="form-group">
              <label>Nuvarande organisation: {{ getOrganizationName(selectedOrganization.id) }}</label>
              <div class="current-org-controls">
                <select id="new-role" v-model="selectedUser.newRole" required>
                  <option value="">Välj en roll</option>
                  <option value="base">Bas</option>
                  <option value="schemalaggare">Schemaläggare</option>
                  <option value="schemaanmain">Admin</option>
                </select>
              </div>
            </div>

            <div class="form-group" v-if="selectedUser.organizations && selectedUser.organizations.length > 0">
              <label>Andra organisationer</label>
              <div class="organizations-list">
                <div v-for="orgId in selectedUser.organizations" :key="orgId" class="organization-item" v-if="orgId !== selectedOrganization.id">
                  <div class="org-info">
                    <span class="org-name">{{ getOrganizationName(orgId) }}</span>
                    <span class="org-role-badge" :class="getRoleClass(selectedUser.organization_roles?.[orgId])">
                      {{ translateRole(selectedUser.organization_roles?.[orgId] || 'unknown') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-danger" @click="confirmRemoveFromCurrentOrg">
                Ta bort från organisationen
              </button>
              <div class="form-actions-right">
                <button type="button" class="btn btn-secondary" @click="showEditUserModal = false">
                  Avbryt
                </button>
                <button type="submit" class="btn btn-primary" :disabled="saveUserLoading">
                  {{ saveUserLoading ? 'Sparar...' : 'Spara' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Resource Form Modal -->
    <div class="modal" v-if="showResourceForm" @click.self="showResourceForm = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingResource ? 'Redigera resurs' : 'Skapa ny resurs' }}</h3>
          <button class="close-button" @click="showResourceForm = false">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveResource">
            <div class="form-group">
              <label for="resource-name">Namn</label>
              <input type="text" id="resource-name" v-model="resourceForm.name" required>
            </div>
            <div class="form-group">
              <label for="resource-description">Beskrivning</label>
              <textarea id="resource-description" v-model="resourceForm.description" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>Tillgänglighet</label>
              <div class="availability-options">
                <div class="radio-option">
                  <input type="radio" id="available-24-7" v-model="resourceForm.is_24_7" :value="true">
                  <label for="available-24-7">Tillgänglig 24/7</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="available-specific" v-model="resourceForm.is_24_7" :value="false">
                  <label for="available-specific">Tillgänglig under specifika tider</label>
                </div>
              </div>
            </div>
            <div class="form-group" v-if="!resourceForm.is_24_7">
              <div class="time-inputs">
                <div class="time-input">
                  <label for="start-time">Starttid</label>
                  <input type="time" id="start-time" v-model="resourceForm.start_time" required>
                </div>
                <div class="time-input">
                  <label for="end-time">Sluttid</label>
                  <input type="time" id="end-time" v-model="resourceForm.end_time" required>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="showResourceForm = false">
                Avbryt
              </button>
              <button type="submit" class="btn btn-primary" :disabled="resourceLoading">
                {{ resourceLoading ? 'Sparar...' : 'Spara' }}
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
      showEditUserModal: false,
      showResourceForm: false,
      createLoading: false,
      updateLoading: false,
      deleteLoading: false,
      usersLoading: false,
      resourcesLoading: false,
      addUserLoading: false,
      saveUserLoading: false,
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
      organizationResources: [],
      selectedUser: null,
      editingResource: null,
      resourceForm: {
        name: '',
        description: '',
        is_24_7: true,
        start_time: '',
        end_time: ''
      },
      resourceLoading: false
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
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/organizations/${organizationId}/users`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          }
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const users = await response.json();
        
        // Process organization data for each user
        if (users && Array.isArray(users)) {
          // Fetch organizations and roles for each user
          const userPromises = users.map(async (user) => {
            try {
              const userOrgsResponse = await fetch(`${wpData.rest_url}schedule/v1/users/${user.user_id}/organizations`, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                  'Content-Type': 'application/json',
                  'X-WP-Nonce': wpData.nonce
                }
              });
              
              if (userOrgsResponse.ok) {
                const userOrgs = await userOrgsResponse.json();
                return {
                  ...user,
                  organizations: userOrgs.map(org => org.id),
                  organization_roles: userOrgs.reduce((acc, org) => {
                    acc[org.id] = org.role;
                    return acc;
                  }, {})
                };
              }
              return user;
            } catch (error) {
              console.error(`Error fetching organizations for user ${user.user_id}:`, error);
              return user;
            }
          });
          
          const processedUsers = await Promise.all(userPromises);
          this.organizationUsers = processedUsers;
          
          // Ensure we have all organizations data
          const allOrgIds = new Set();
          processedUsers.forEach(user => {
            if (user.organizations && Array.isArray(user.organizations)) {
              user.organizations.forEach(orgId => {
                if (orgId) {
                  allOrgIds.add(orgId);
                }
              });
            }
          });
          
          // Fetch any missing organizations
          const missingOrgIds = [...allOrgIds].filter(orgId => 
            !this.organizations.some(org => org.id === orgId)
          );
          
          if (missingOrgIds.length > 0) {
            await this.fetchMissingOrganizations(missingOrgIds);
          }
        }
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
    
    getOrganizationName(orgId) {
      if (!orgId) {
        return 'Okänd';
      }
      
      const org = this.organizationsStore.organizations.find(org => org.id === orgId);
      return org ? org.name : 'Okänd';
    },
    
    getOrganizationObject(orgId) {
      if (!orgId) {
        return null;
      }
      
      const org = this.organizations.find(org => org.id === orgId);
      return org ? org : null;
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
        case 'schemaanmain':
          return 'Admin';
        case 'scheduler':
        case 'schemalaggare':
          return 'Schemaläggare';
        case 'base':
          return 'Bas';
        case 'wpschema_anvandare':
          return 'WP Schema Användare';
        default:
          return role;
      }
    },
    async editUserRole(user) {
      this.selectedUser = {
        ...user,
        newRole: user.role, // Sätt nuvarande roll som default
        organization_roles: user.organization_roles || {} // Lägg till organization_roles
      };
      this.showEditUserModal = true;
    },
    async saveUserRole() {
      if (!this.selectedUser || !this.selectedUser.newRole) {
        alert('Välj en giltig roll');
        return;
      }

      this.saveUserLoading = true;
      try {
        await this.usersStore.updateUserRole(
          this.selectedOrganization.id,
          this.selectedUser.user_id,
          this.selectedUser.newRole
        );
        await this.loadOrganizationUsers(this.selectedOrganization.id);
        this.showEditUserModal = false;
        this.selectedUser = null;
      } catch (error) {
        alert("Fel vid uppdatering av roll: " + error.message);
      } finally {
        this.saveUserLoading = false;
      }
    },
    confirmRemoveUser(user) {
      if (confirm(`Är du säker på att du vill ta bort ${user.user_data.display_name} från organisationen?`)) {
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
    },
    confirmRemoveFromOrganization(organizationId) {
      if (confirm(`Är du säker på att du vill ta bort ${this.selectedUser.user_data.display_name} från ${this.getOrganizationName(organizationId)}?`)) {
        this.removeFromOrganization(organizationId);
      }
    },
    async removeFromOrganization(organizationId) {
      try {
        await this.usersStore.removeUserFromOrganization(organizationId, this.selectedUser.user_id);
        // Uppdatera selectedUser för att reflektera borttagningen
        this.selectedUser.organizations = this.selectedUser.organizations.filter(id => id !== organizationId);
        delete this.selectedUser.role;
      } catch (error) {
        alert("Fel vid borttagning från organisation: " + error.message);
      }
    },
    confirmRemoveFromCurrentOrg() {
      if (confirm(`Är du säker på att du vill ta bort ${this.selectedUser.user_data.display_name} från ${this.getOrganizationName(this.selectedOrganization.id)}?`)) {
        this.removeFromCurrentOrg();
      }
    },
    async removeFromCurrentOrg() {
      try {
        await this.usersStore.removeUserFromOrganization(this.selectedOrganization.id, this.selectedUser.user_id);
        this.showEditUserModal = false;
        await this.loadOrganizationUsers(this.selectedOrganization.id);
      } catch (error) {
        alert("Fel vid borttagning från organisation: " + error.message);
      }
    },
    async fetchMissingOrganizations(orgIds) {
      if (!orgIds || orgIds.length === 0) return;
      
      try {
        const wpData = window.wpScheduleData || {};
        const promises = orgIds.map(orgId => 
          fetch(`${wpData.rest_url}schedule/v1/organizations/${orgId}`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': wpData.nonce
            }
          })
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
          })
        );
        
        const organizations = await Promise.all(promises);
        
        // Uppdatera organizations store med de nya organisationerna
        organizations.forEach(org => {
          if (!this.organizationsStore.organizations.some(existingOrg => existingOrg.id === org.id)) {
            this.organizationsStore.$patch((state) => {
              state.organizations.push(org);
            });
          }
        });
      } catch (error) {
        console.error('Error fetching missing organizations:', error);
      }
    },
    hasVisibleOrganizations(user) {
      return user.organizations && 
             Array.isArray(user.organizations) && 
             user.organizations.some(orgId => orgId !== this.selectedOrganization.id);
    },
    getRoleClass(role) {
      switch (role) {
        case 'admin':
        case 'schemaanmain':
          return 'role-admin';
        case 'scheduler':
        case 'schemalaggare':
          return 'role-scheduler';
        case 'base':
          return 'role-base';
        default:
          return '';
      }
    },
    editResource(resource) {
      this.editingResource = resource;
      this.resourceForm = {
        name: resource.name,
        description: resource.description || '',
        is_24_7: resource.is_24_7,
        start_time: resource.start_time || '',
        end_time: resource.end_time || ''
      };
      this.showResourceForm = true;
    },
    async saveResource() {
      this.resourceLoading = true;
      try {
        if (this.editingResource) {
          await this.resourcesStore.updateResource(this.editingResource.id, this.resourceForm);
        } else {
          await this.resourcesStore.createResource(this.selectedOrganization.id, this.resourceForm);
        }
        await this.loadOrganizationResources(this.selectedOrganization.id);
        this.showResourceForm = false;
        this.resetResourceForm();
      } catch (error) {
        alert(this.editingResource ? 
          "Fel vid uppdatering av resurs: " + error.message :
          "Fel vid skapande av resurs: " + error.message
        );
      } finally {
        this.resourceLoading = false;
      }
    },
    resetResourceForm() {
      this.resourceForm = {
        name: '',
        description: '',
        is_24_7: true,
        start_time: '',
        end_time: ''
      };
      this.editingResource = null;
    },
    async confirmDeleteResource(resource) {
      if (confirm(`Är du säker på att du vill ta bort resursen "${resource.name}"?`)) {
        await this.removeResource(resource);
      }
    },
    async removeResource(resource) {
      try {
        await this.resourcesStore.deleteResource(resource.id);
        await this.loadOrganizationResources(this.selectedOrganization.id);
      } catch (error) {
        alert("Fel vid borttagning av resurs: " + error.message);
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
  display: flex;
  gap: 8px;
  justify-content: flex-start;
  min-width: 200px;
}

.actions .btn {
  white-space: nowrap;
  min-width: fit-content;
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
  padding: 4px 12px;
  font-size: 0.9em;
  min-width: 80px;
  text-align: center;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: flex-start;
  z-index: 1000;
  padding: 2rem;
  overflow-y: auto;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 1200px;
  margin: 2rem auto;
  display: flex;
  flex-direction: column;
  max-height: calc(100vh - 4rem);
}

.modal-header {
  position: sticky;
  top: 0;
  background: white;
  z-index: 2;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e5e5;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 8px 8px 0 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.5rem;
  color: #333;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
  padding: 0.5rem;
}

.close-button:hover {
  color: #333;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
  min-height: 200px;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-group input[type="text"],
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-group input[type="text"]:focus,
.form-group textarea:focus {
  border-color: #4a90e2;
  outline: none;
  box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
}

.availability-options {
  display: flex;
  gap: 1.5rem;
  margin-top: 0.5rem;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.time-inputs {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

.time-input {
  flex: 1;
}

.time-input input[type="time"] {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background-color: #4a90e2;
  color: white;
  border: none;
}

.btn-primary:hover {
  background-color: #357abd;
}

.btn-primary:disabled {
  background-color: #a0c3e8;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
}

.btn-secondary:hover {
  background-color: #e5e5e5;
}

.organization-details {
  margin-bottom: 30px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.organization-details p {
  margin: 10px 0;
}

.tabs {
  margin-top: 20px;
}

.tab-header {
  display: flex;
  border-bottom: 2px solid #e5e5e5;
  margin-bottom: 20px;
}

.tab-button {
  padding: 10px 20px;
  border: none;
  background: none;
  cursor: pointer;
  font-size: 16px;
  color: #666;
  position: relative;
}

.tab-button.active {
  color: #0073aa;
  font-weight: bold;
}

.tab-button.active::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  height: 2px;
  background: #0073aa;
}

.tab-content {
  padding: 1rem 0;
  overflow: visible;
}

.tab-pane {
  overflow: visible;
}

.user-list, .resource-list {
  overflow: visible;
  margin: -1.5rem;
  padding: 1.5rem;
}

.data-table {
  min-width: 800px;
  width: 100%;
  margin-bottom: 2rem;
  border-collapse: separate;
  border-spacing: 0;
}

.data-table th {
  position: sticky;
  top: 0;
  background: white;
  z-index: 1;
}

.data-table th,
.data-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e5e5e5;
  white-space: nowrap;
}

.data-table td:last-child {
  padding-right: 24px;
  min-width: 180px;
}

.other-orgs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.org-badge {
  background: #e9ecef;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.9em;
  color: #495057;
}

.loading {
  text-align: center;
  padding: 20px;
  color: #666;
}

.current-org-controls {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-top: 8px;
}

.current-org-controls select {
  flex: 1;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.organizations-list {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 12px;
}

.organization-item {
  padding: 12px;
  border-bottom: 1px solid #e9ecef;
}

.organization-item:last-child {
  border-bottom: none;
}

.org-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.org-name {
  font-weight: 500;
  color: #333;
}

.org-role-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.85em;
  font-weight: 500;
  background: #e9ecef;
  color: #495057;
}

.org-role-badge.role-admin {
  background: #dc3545;
  color: white;
}

.org-role-badge.role-scheduler {
  background: #0073aa;
  color: white;
}

.org-role-badge.role-base {
  background: #6c757d;
  color: white;
}

.form-actions {
  margin-top: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-actions-right {
  display: flex;
  gap: 12px;
}

.org-chips-container {
  min-width: 250px;
  max-width: none;
  width: 100%;
}

.no-orgs {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #666;
  font-style: italic;
  padding: 4px 8px;
  background: #f8f9fa;
  border-radius: 4px;
  font-size: 0.9em;
}

.org-chips {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.org-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 20px;
  background: linear-gradient(to right, #f8f9fa, #e9ecef);
  border: 1px solid #dee2e6;
  font-size: 0.9em;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.org-chip:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.org-chip.role-admin {
  background: linear-gradient(to right, #fee2e2, #fecaca);
  border-color: #fca5a5;
}

.org-chip.role-scheduler {
  background: linear-gradient(to right, #dbeafe, #bfdbfe);
  border-color: #93c5fd;
}

.org-chip.role-base {
  background: linear-gradient(to right, #f3f4f6, #e5e7eb);
  border-color: #d1d5db;
}

.org-chip-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  color: #4b5563;
}

.org-chip-name {
  font-weight: 500;
  color: #374151;
}

.org-chip-role {
  font-size: 0.85em;
  padding: 2px 6px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.5);
  color: #4b5563;
}

.org-chip.role-admin .org-chip-role {
  background: #dc3545;
  color: white;
}

.org-chip.role-scheduler .org-chip-role {
  background: #0073aa;
  color: white;
}

.org-chip.role-base .org-chip-role {
  background: #6c757d;
  color: white;
}

/* Lägg till WordPress dashicons */
@font-face {
  font-family: "dashicons";
  src: url("/wp-includes/fonts/dashicons.eot");
  src: url("/wp-includes/fonts/dashicons.eot?#iefix") format("embedded-opentype"),
       url("/wp-includes/fonts/dashicons.woff") format("woff"),
       url("/wp-includes/fonts/dashicons.ttf") format("truetype"),
       url("/wp-includes/fonts/dashicons.svg#dashicons") format("svg");
  font-weight: normal;
  font-style: normal;
}

.dashicons {
  font-family: "dashicons";
  font-size: 16px;
  line-height: 1;
  font-weight: 400;
  font-style: normal;
  text-decoration: inherit;
  text-transform: none;
  -webkit-font-smoothing: antialiased;
}

.dashicons-building:before {
  content: "\f512";
}

.dashicons-businessman:before {
  content: "\f338";
}

/* Förbättrat responsivitet för mindre skärmar */
@media (max-width: 768px) {
  .modal {
    padding: 0;
  }

  .modal-content {
    width: 100%;
    max-width: 100%;
    margin: 0;
    border-radius: 0;
    height: 100vh;
  }

  .modal-body {
    padding: 1rem;
  }

  .user-list, .resource-list {
    margin: -1rem;
    padding: 1rem;
  }

  .data-table {
    min-width: 650px;
  }

  .btn-small {
    min-width: 70px;
    padding: 4px 8px;
  }
}
</style>
