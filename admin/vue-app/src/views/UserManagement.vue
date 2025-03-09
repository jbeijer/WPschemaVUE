<template>
  <div class="user-management">
    <h2>Användarhantering</h2>
    
    <div ref="successMessage" class="success-message" style="display: none;">
      Användarrollen har uppdaterats
    </div>
    
    <div class="organization-selector">
      <label for="organization-select">Filtrera efter organisation:</label>
      <select 
        id="organization-select" 
        v-model="selectedOrgId"
        @change="handleOrganizationChange"
        class="organization-select"
      >
        <option value="">Alla användare</option>
        <option 
          v-for="org in organizations" 
          :key="org.id" 
          :value="org.id"
        >
          {{ org.name }}
        </option>
      </select>
    </div>
    
    <div v-if="loading" class="loading-message">
      Laddar användare...
    </div>
    
    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>
    
    <table v-else-if="users.length > 0" class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>Namn</th>
          <th>E-post</th>
          <th>Roll</th>
          <th>Organisation</th>
          <th>Åtgärder</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id">
          <td>{{ user.user_data?.display_name }}</td>
          <td>{{ user.user_data?.user_email }}</td>
          <td>{{ getRoleLabel(user.role || getUserRoleInOrg(user.user_id || user.id)) }}</td>
          <td>{{ getOrganizationName(user.organization_id) }}</td>
          <td>
            <button 
              class="button button-primary"
              @click="openEditModal(user)"
              v-if="hasPermission('manage_options')"
            >
              Redigera
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    
    <div v-else class="no-users-message">
      Inga användare hittades.
    </div>

    <!-- Modal backdrop -->
    <div v-if="showEditModal" class="modal-backdrop" @click="showEditModal = false"></div>
    
    <!-- Edit User Modal -->
    <div v-if="showEditModal" class="permission-modal">
      <h3>Redigera användare: {{ selectedUser.user_data?.display_name }}</h3>
      
      <div v-if="modalErrorMessage" class="error-message" style="margin-bottom: 1rem;">
        {{ modalErrorMessage }}
      </div>
      
      <div v-if="editedUser.organization" class="current-org">
        <label>Nuvarande organisation:</label>
        <div class="org-controls">
          <div class="org-info">
            <span>{{ getOrganizationName(editedUser.organization) }}</span>
            <select v-model="editedUser.role" class="role-select" :disabled="modalSaving">
              <option v-for="role in roles" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
          </div>
          <button 
            type="button" 
            class="button button-link-delete" 
            @click="confirmRemoveFromOrg(editedUser.organization)"
            :disabled="modalSaving"
          >
            Ta bort från organisation
          </button>
        </div>
      </div>
      
      <div class="other-orgs" v-if="userOrganizations && userOrganizations.length > 0">
        <label>Andra organisationer:</label>
        <div class="orgs-list">
          <div v-for="org in userOrganizations" :key="org.id" class="org-item" v-if="org.id !== editedUser.organization">
            <div class="org-info">
              <span>{{ org.name }}</span>
              <span class="org-role">({{ getRoleLabel(org.role) }})</span>
            </div>
            <button 
              type="button" 
              class="button button-link-delete" 
              @click="confirmRemoveFromOrg(org.id)"
              :disabled="modalSaving"
            >
              Ta bort
            </button>
          </div>
        </div>
      </div>
      
      <div class="add-to-org" style="margin-top: 1rem;">
        <label>Lägg till i organisation:</label>
        <select v-model="editedUser.organization" class="org-select" :disabled="modalSaving">
          <option v-for="org in availableOrganizations" :key="org.id" :value="org.id">
            {{ org.name }}
          </option>
        </select>
      </div>
      
      <div style="margin-top: 1rem;">
        <button class="button" @click="closeEditModal" :disabled="modalSaving">Avbryt</button>
        <button class="button button-primary" @click="saveUserChanges" :disabled="modalSaving">
          <span v-if="modalSaving">Sparar...</span>
          <span v-else>Spara</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { usePermissions } from '@/composables/usePermissions';
import { useOrganizationsStore } from '@/stores/organizations';
import { useUsersStore } from '@/stores/users';
import { storeToRefs } from 'pinia';

export default {
  setup() {
    const { hasPermission } = usePermissions();
    const organizationsStore = useOrganizationsStore();
    const usersStore = useUsersStore();
    
    const { organizations } = storeToRefs(organizationsStore);
    const { users, loading, error } = storeToRefs(usersStore);
    
    function getOrganizationName(orgId) {
      if (!orgId) return '-';
      const org = organizations.value.find(o => o.id === orgId);
      return org ? org.name : '-';
    }
    
    return { 
      hasPermission, 
      organizationsStore, 
      usersStore,
      organizations,
      users,
      loading,
      error,
      getOrganizationName
    };
  },
  data() {
    return {
      selectedOrgId: '',
      showEditModal: false,
      selectedUser: null,
      editedUser: null,
      modalSaving: false,
      modalErrorMessage: '',
      userOrganizations: [],
      roles: [
        { value: 'base', label: 'Bas (Anställd)' },
        { value: 'scheduler', label: 'Schemaläggare' },
        { value: 'admin', label: 'Admin' },
        { value: 'wpschema_anvandare', label: 'WP Schema Användare' },
        { value: 'schemaanmain', label: 'Schema Admin' }
      ]
    };
  },
  computed: {
    availableOrganizations() {
      if (!this.organizations) return [];
      const currentOrgs = this.userOrganizations.map(org => org.id);
      return this.organizations.filter(org => !currentOrgs.includes(org.id));
    }
  },
  async mounted() {
    try {
      // Fetch current user info first
      await this.usersStore.fetchCurrentUserInfo();
      
      // Fetch organizations when component mounts
      await this.organizationsStore.fetchOrganizations();
      
      // Fetch all WordPress users by default
      await this.usersStore.fetchAllWordPressUsers();
    } catch (error) {
      console.error('Kunde inte hämta data:', error);
    }
  },
  methods: {
    async handleOrganizationChange() {
      if (this.selectedOrgId) {
        try {
          await this.usersStore.fetchUsersByOrganization(this.selectedOrgId);
        } catch (error) {
          console.error('Kunde inte hämta användare:', error);
        }
      } else {
        // Show all users when no organization is selected
        await this.usersStore.fetchAllWordPressUsers();
      }
    },
    async openEditModal(user) {
      this.selectedUser = user;
      // Create a deep copy of the user to avoid modifying the original
      this.editedUser = {
        ...JSON.parse(JSON.stringify(user)),
        user_id: user.user_id || user.id,
        organization: user.organization_id || this.selectedOrgId || '',
        role: user.role || 'base'
      };
      
      // Använd befintlig data från users store istället för att göra ett nytt API-anrop
      this.userOrganizations = [];
      if (user.organizations) {
        this.userOrganizations = user.organizations.map(orgId => {
          const org = this.organizations.find(o => o.id === orgId);
          return {
            id: orgId,
            name: org ? org.name : '',
            role: user.organization_roles?.[orgId] || 'base'
          };
        });
      }
      
      this.modalErrorMessage = '';
      this.modalSaving = false;
      this.showEditModal = true;
    },
    closeEditModal() {
      this.showEditModal = false;
      this.selectedUser = null;
      this.editedUser = null;
    },
    async saveUserChanges() {
      this.modalSaving = true;
      this.modalErrorMessage = '';
      
      try {
        if (this.editedUser.organization) {
          // If an organization is selected, update the user's role and organization
          await this.usersStore.updateUserRole(
            this.editedUser.organization,
            this.editedUser.user_id,
            this.editedUser.role
          );
          
          // Uppdatera användarlistan baserat på vald organisation
          if (this.selectedOrgId) {
            await this.usersStore.fetchUsersByOrganization(this.selectedOrgId);
          } else {
            await this.usersStore.fetchAllWordPressUsers();
          }
          
          // Show success message
          this.$refs.successMessage.textContent = 'Användarrollen har uppdaterats';
          this.$refs.successMessage.style.display = 'block';
          
          // Hide success message after 3 seconds
          setTimeout(() => {
            if (this.$refs.successMessage) {
              this.$refs.successMessage.style.display = 'none';
            }
          }, 3000);
          
          this.closeEditModal();
        } else {
          // If no organization is selected, show error message in modal
          this.modalErrorMessage = 'Du måste välja en organisation för att uppdatera användarrollen';
          this.modalSaving = false;
        }
      } catch (error) {
        console.error('Uppdatering misslyckades:', error);
        this.modalErrorMessage = `Uppdatering misslyckades: ${error.message || 'Okänt fel'}`;
        this.modalSaving = false;
      }
    },
    getUserRoleInOrg(userId) {
      if (!userId || !this.selectedOrgId) return null;
      const userInOrg = this.usersStore.getUserInOrganization(userId, this.selectedOrgId);
      return userInOrg ? userInOrg.role : null;
    },
    // Map role value to human-readable label
    getRoleLabel(roleValue) {
      // Handle case when role is undefined or null
      if (!roleValue) return '-';
      
      // Normalisera rollvärdet till små bokstäver för att matcha våra definierade roller
      const normalizedRole = typeof roleValue === 'string' ? roleValue.toLowerCase() : roleValue;
      
      // Find the role in our predefined roles array
      const role = this.roles.find(r => r.value === normalizedRole);
      if (role) {
        return role.label;
      }
      
      // Om rollen inte finns i våra fördefinierade roller, kan det vara en WordPress-roll
      // eller en kommaseparerad lista med roller från fetchAllWordPressUsers
      // Returnera den som den är, med första bokstaven stor för varje roll
      return String(normalizedRole).split(', ')
        .map(r => r.charAt(0).toUpperCase() + r.slice(1))
        .join(', ');
    },
    confirmRemoveFromOrg(orgId) {
      if (confirm(`Är du säker på att du vill ta bort användaren från ${this.getOrganizationName(orgId)}?`)) {
        this.removeFromOrg(orgId);
      }
    },
    async removeFromOrg(orgId) {
      this.modalSaving = true;
      try {
        await this.usersStore.removeUserFromOrganization(orgId, this.editedUser.user_id);
        
        // Uppdatera userOrganizations listan
        this.userOrganizations = this.userOrganizations.filter(org => org.id !== orgId);
        
        // Om det var den nuvarande organisationen, rensa den
        if (orgId === this.editedUser.organization) {
          this.editedUser.organization = '';
          this.editedUser.role = '';
        }
        
        // Uppdatera användarlistan
        if (this.selectedOrgId) {
          await this.usersStore.fetchUsersByOrganization(this.selectedOrgId);
        } else {
          await this.usersStore.fetchAllWordPressUsers();
        }
      } catch (error) {
        this.modalErrorMessage = `Kunde inte ta bort användaren från organisationen: ${error.message}`;
      } finally {
        this.modalSaving = false;
      }
    }
  }
};
</script>

<style scoped>
.permission-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 1000;
}

.role-select {
    padding: 0.5rem;
    margin-left: 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.organization-selector {
    margin-bottom: 1.5rem;
}

.organization-select {
    padding: 0.5rem;
    margin-left: 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    min-width: 200px;
}

.loading-message,
.error-message,
.no-users-message,
.select-org-message {
    padding: 1rem;
    margin: 1rem 0;
    border-radius: 4px;
}

.loading-message {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

.error-message {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.success-message {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.no-users-message,
.select-org-message {
    background-color: #e2e3e5;
    border: 1px solid #d6d8db;
    color: #383d41;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.current-org,
.other-orgs {
  margin-bottom: 1rem;
}

.org-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.5rem;
  padding: 0.5rem;
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}

.orgs-list {
  margin-top: 0.5rem;
}

.org-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  border-bottom: 1px solid #dee2e6;
}

.org-item:last-child {
  border-bottom: none;
}

.org-role {
  color: #6c757d;
  font-size: 0.9em;
  margin-left: 0.5rem;
}

.button-link-delete {
  color: #dc3545;
  background: none;
  border: none;
  padding: 4px 8px;
  cursor: pointer;
  font-size: 0.9em;
}

.button-link-delete:hover {
  text-decoration: underline;
  background-color: #f8d7da;
  border-radius: 4px;
}

.button-link-delete:disabled {
  color: #6c757d;
  cursor: not-allowed;
}
</style>
