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
          <td>{{ getRoleLabel(user.role) }}</td>
          <td>{{ getOrganizationName(user.organization) }}</td>
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
      
      <div>
        <label>Roll:</label>
        <select v-model="editedUser.role" class="role-select" :disabled="modalSaving">
          <option v-for="role in roles" :key="role.value" :value="role.value">
            {{ role.label }}
          </option>
        </select>
      </div>
      
      <div style="margin-top: 1rem;">
        <label>Organisation:</label>
        <select v-model="editedUser.organization" class="org-select" :disabled="modalSaving">
          <option v-for="org in organizations" :key="org.id" :value="org.id">
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
      roles: [
        { value: 'base', label: 'Bas' },
        { value: 'scheduler', label: 'Schemaläggare' },
        { value: 'admin', label: 'Admin' }
      ]
    };
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
    openEditModal(user) {
      this.selectedUser = user;
      // Create a deep copy of the user to avoid modifying the original
      this.editedUser = JSON.parse(JSON.stringify(user));
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
    // Map role value to human-readable label
    getRoleLabel(roleValue) {
      // Handle case when role is undefined or null
      if (!roleValue) return '-';
      
      // Find the role in our predefined roles array
      const role = this.roles.find(r => r.value === roleValue);
      if (role) {
        return role.label;
      }
      
      // If role is not found in our predefined roles, it might be a WordPress role
      // or a comma-separated list of roles from fetchAllWordPressUsers
      // Return it as is, with first letter capitalized for each role
      return roleValue.split(', ')
        .map(r => r.charAt(0).toUpperCase() + r.slice(1))
        .join(', ');
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
</style>
