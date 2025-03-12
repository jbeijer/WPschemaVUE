<template>
  <div class="user-management">
    <h2>Användarhantering</h2>
    
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
          <th style="width: 30px"></th>
          <th>Namn</th>
          <th>E-post</th>
          <th>Organisationer & Roller</th>
          <th>Åtgärder</th>
        </tr>
      </thead>
      <tbody>
        <template v-for="user in users" :key="user.user_id || user.id || user.user_data?.ID">
          <tr>
            <td>
              <button 
                class="expand-button" 
                @click="toggleUserDetails(user)"
                :class="{ 'expanded': expandedUsers.includes(user.user_id) }"
              >
                <span class="dashicons" :class="expandedUsers.includes(user.user_id) ? 'dashicons-arrow-down' : 'dashicons-arrow-right'"></span>
              </button>
            </td>
            <td>{{ user.user_data?.display_name }}</td>
            <td>{{ user.user_data?.user_email }}</td>
            <td>
              <div class="org-badges">
                <span 
                  v-for="orgId in user.organizations || []" 
                  :key="orgId"
                  class="org-badge"
                  :class="{ 'primary-org': orgId === user.organization_id }"
                >
                  {{ getOrganizationName(orgId) }}
                  <span class="role-label">{{ getRoleLabel(user.organization_roles?.[orgId]) }}</span>
                </span>
              </div>
            </td>
            <td>
              <button 
                class="button button-primary"
                @click="handleEditClick(user)"
                v-if="hasPermission('manage_options')"
              >
                Redigera
              </button>
            </td>
          </tr>
          <tr v-if="expandedUsers.includes(user.user_id)" class="expanded-details">
            <td colspan="5">
              <div class="user-details">
                <h4>Organisationsdetaljer</h4>
                <div class="org-details-grid">
                  <div v-for="orgId in user.organizations || []" :key="orgId" class="org-detail-card">
                    <div class="org-detail-header">
                      <h5>{{ getOrganizationName(orgId) }}</h5>
                      <span :class="['org-status', { 'primary': orgId === user.organization_id }]">
                        {{ orgId === user.organization_id ? 'Primär' : 'Sekundär' }}
                      </span>
                    </div>
                    <div class="org-detail-content">
                      <p><strong>Roll:</strong> {{ getRoleLabel(user.organization_roles?.[orgId]) }}</p>
                      <p><strong>Behörigheter:</strong></p>
                      <ul class="permissions-list">
                        <li v-for="perm in getRolePermissions(user.organization_roles?.[orgId])" :key="perm">
                          {{ perm }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
    
    <div v-else class="no-users-message">
      Inga användare hittades.
    </div>

    <!-- Modal för redigering av användare -->
    <div v-if="showEditModal" class="modal-backdrop" @click="closeEditModal"></div>
    <div v-if="showEditModal" class="edit-modal">
      <div class="modal-header">
        <h3>Redigera användare: {{ selectedUser?.user_data?.display_name }}</h3>
        <button class="close-button" @click="closeEditModal">&times;</button>
      </div>

      <div class="modal-content">
        <div v-if="modalError" class="error-message">
          {{ modalError }}
        </div>

        <!-- Nuvarande organisation -->
        <div v-if="selectedUser?.organization_id" class="current-org">
          <h4>Nuvarande organisation</h4>
          <div class="org-info">
            <span>{{ getOrganizationName(selectedUser.organization_id) }}</span>
            <select v-model="selectedUser.role" class="role-select">
              <option v-for="role in roles" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Lista över alla organisationer -->
        <div class="organizations-list">
          <h4>Organisationer</h4>
          <div v-for="orgId in selectedUser?.organizations || []" :key="orgId" class="org-item">
            <div class="org-info">
              <span>{{ getOrganizationName(orgId) }}</span>
              <span class="org-role">({{ getRoleLabel(selectedUser?.organization_roles?.[orgId]) }})</span>
            </div>
            <button 
              v-if="orgId !== selectedUser?.organization_id"
              class="button button-link-delete"
              @click="removeFromOrg(orgId)"
            >
              Ta bort
            </button>
          </div>
        </div>

        <!-- Lägg till i ny organisation -->
        <div class="add-org">
          <h4>Lägg till i organisation</h4>
          <div class="add-org-controls">
            <select v-model="newOrgId" class="org-select">
              <option value="">Välj organisation</option>
              <option 
                v-for="org in availableOrganizations" 
                :key="org.id" 
                :value="org.id"
              >
                {{ org.name }}
              </option>
            </select>
            <select v-model="newOrgRole" class="role-select">
              <option v-for="role in roles" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
            <button 
              class="button button-primary"
              @click="addToOrg"
              :disabled="!newOrgId || !newOrgRole"
            >
              Lägg till
            </button>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="button" @click="closeEditModal">Avbryt</button>
        <button 
          class="button button-primary" 
          @click="saveChanges"
          :disabled="saving"
        >
          {{ saving ? 'Sparar...' : 'Spara ändringar' }}
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
      modalError: '',
      saving: false,
      newOrgId: '',
      newOrgRole: 'base',
      expandedUsers: [],
      roles: [
        { value: 'base', label: 'Bas (Anställd)' },
        { value: 'schemalaggare', label: 'Schemaläggare' },
        { value: 'schemaanmain', label: 'Schema Admin' }
      ]
    };
  },
  computed: {
    availableOrganizations() {
      if (!this.organizations) return [];
      const currentOrgs = this.selectedUser?.organizations || [];
      return this.organizations.filter(org => !currentOrgs.includes(org.id));
    }
  },
  async mounted() {
    try {
      await this.usersStore.fetchCurrentUserInfo();
      await this.organizationsStore.fetchOrganizations();
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
        await this.usersStore.fetchAllWordPressUsers();
      }
    },
    handleEditClick(user) {
      this.selectedUser = JSON.parse(JSON.stringify({
        ...user,
        role: user.role || user.organization_roles?.[user.organization_id] || 'base'
      }));
      this.showEditModal = true;
      this.modalError = '';
    },
    closeEditModal() {
      this.showEditModal = false;
      this.selectedUser = null;
      this.modalError = '';
      this.newOrgId = '';
      this.newOrgRole = 'base';
    },
    async addToOrg() {
      if (!this.newOrgId || !this.newOrgRole) return;
      
      try {
        await this.usersStore.updateUserRole(
          this.newOrgId,
          this.selectedUser.user_id,
          this.newOrgRole
        );

        // Uppdatera selectedUser med den nya organisationen
        this.selectedUser.organizations = [...(this.selectedUser.organizations || []), this.newOrgId];
        this.selectedUser.organization_roles = {
          ...this.selectedUser.organization_roles,
          [this.newOrgId]: this.newOrgRole
        };

        // Återställ formuläret
        this.newOrgId = '';
        this.newOrgRole = 'base';
      } catch (error) {
        this.modalError = `Kunde inte lägga till användaren i organisationen: ${error.message}`;
      }
    },
    async removeFromOrg(orgId) {
      if (!confirm(`Är du säker på att du vill ta bort användaren från ${this.getOrganizationName(orgId)}?`)) {
        return;
      }

      try {
        await this.usersStore.removeUserFromOrganization(orgId, this.selectedUser.user_id);
        
        // Uppdatera selectedUser
        this.selectedUser.organizations = this.selectedUser.organizations.filter(id => id !== orgId);
        delete this.selectedUser.organization_roles[orgId];
        
        // Om det var den nuvarande organisationen, rensa den
        if (orgId === this.selectedUser.organization_id) {
          this.selectedUser.organization_id = null;
          this.selectedUser.role = null;
        }
      } catch (error) {
        this.modalError = `Kunde inte ta bort användaren från organisationen: ${error.message}`;
      }
    },
    async saveChanges() {
      this.modalError = '';
      
      // Om användaren inte har någon organisation och inga ändringar gjorts
      if (!this.selectedUser.organizations?.length) {
        this.modalError = 'Användaren måste tillhöra minst en organisation';
        return;
      }

      // Om ingen primär organisation är vald, använd den första i listan
      if (!this.selectedUser.organization_id && this.selectedUser.organizations?.length > 0) {
        this.selectedUser.organization_id = this.selectedUser.organizations[0];
        this.selectedUser.role = this.selectedUser.organization_roles?.[this.selectedUser.organizations[0]] || 'base';
      }

      // Validera att rollen är giltig
      const validRoles = ['base', 'schemalaggare', 'schemaanmain'];
      if (!this.selectedUser.role || !validRoles.includes(this.selectedUser.role)) {
        this.selectedUser.role = 'base'; // Sätt default roll till 'base' om ogiltig
      }

      this.saving = true;

      try {
        // Spara användarens roll för primär organisation
        await this.usersStore.updateUserRole(
          this.selectedUser.organization_id,
          this.selectedUser.user_id,
          this.selectedUser.role
        );

        // Spara roller för alla andra organisationer
        const savePromises = this.selectedUser.organizations
          .filter(orgId => orgId !== this.selectedUser.organization_id)
          .map(orgId => {
            const role = this.selectedUser.organization_roles?.[orgId] || 'base';
            return this.usersStore.updateUserRole(
              orgId,
              this.selectedUser.user_id,
              role
            ).catch(error => {
              console.warn(`Kunde inte uppdatera roll för org ${orgId}:`, error);
              return null; // Fortsätt med andra uppdateringar även om en misslyckas
            });
          });

        await Promise.all(savePromises);

        // Visa bekräftelse och stäng modal
        this.showSuccessMessage('Ändringarna har sparats');
        this.closeEditModal();

        // Uppdatera användarlistan i bakgrunden
        this.refreshUserList();
      } catch (error) {
        this.modalError = `Kunde inte spara ändringarna: ${error.message}`;
      } finally {
        this.saving = false;
      }
    },
    async refreshUserList() {
      try {
        if (this.selectedOrgId) {
          await this.usersStore.fetchUsersByOrganization(this.selectedOrgId);
        } else {
          await this.usersStore.fetchAllWordPressUsers();
        }
      } catch (error) {
        console.warn('Kunde inte uppdatera användarlistan:', error);
        // Visa inget felmeddelande till användaren eftersom ändringarna sparades
      }
    },
    showSuccessMessage(message) {
      // Skapa ett success-element
      const successDiv = document.createElement('div');
      successDiv.className = 'notice notice-success is-dismissible';
      successDiv.innerHTML = `<p>${message}</p>`;
      
      // Lägg till elementet i WordPress admin area
      const wpWrap = document.querySelector('.wrap');
      if (wpWrap) {
        wpWrap.insertBefore(successDiv, wpWrap.firstChild);
        
        // Ta bort meddelandet efter 3 sekunder
        setTimeout(() => {
          successDiv.remove();
        }, 3000);
      }
    },
    getUserRoleInOrg(userId) {
      if (!userId || !this.selectedOrgId) return null;
      const userInOrg = this.usersStore.getUserInOrganization(userId, this.selectedOrgId);
      return userInOrg ? userInOrg.role : null;
    },
    getRoleLabel(roleValue) {
      if (!roleValue) return '-';
      const normalizedRole = typeof roleValue === 'string' ? roleValue.toLowerCase() : roleValue;
      const role = this.roles.find(r => r.value === normalizedRole);
      if (role) {
        return role.label;
      }
      return String(normalizedRole).split(', ')
        .map(r => r.charAt(0).toUpperCase() + r.slice(1))
        .join(', ');
    },
    toggleUserDetails(user) {
      const userId = user.user_id || user.id || user.user_data?.ID;
      const index = this.expandedUsers.indexOf(userId);
      if (index === -1) {
        this.expandedUsers.push(userId);
      } else {
        this.expandedUsers.splice(index, 1);
      }
    },
    getRolePermissions(role) {
      const permissions = {
        base: [
          'Visa schema',
          'Hantera egen tillgänglighet',
          'Se organisationsinformation'
        ],
        schemalaggare: [
          'Alla bas-behörigheter',
          'Skapa och redigera scheman',
          'Hantera resurser',
          'Hantera tillgänglighet för andra'
        ],
        schemaanmain: [
          'Alla schemaläggarbehörigheter',
          'Hantera användare',
          'Hantera organisationsinställningar',
          'Full åtkomst till systemet'
        ]
      };
      
      return permissions[role] || ['Inga specifika behörigheter'];
    }
  }
};
</script>

<style scoped>
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
.no-users-message {
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

.no-users-message {
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
  z-index: 1000;
}

.edit-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  z-index: 1001;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  line-height: 1;
}

.modal-content {
  margin-bottom: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  border-top: 1px solid #dee2e6;
  padding-top: 1rem;
}

.current-org,
.organizations-list,
.add-org {
  margin-bottom: 1.5rem;
}

.org-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
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
}

.add-org-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.role-select,
.org-select {
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  min-width: 150px;
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

.expand-button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 2px;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.org-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.org-badge {
  display: inline-flex;
  align-items: center;
  background-color: #f0f0f0;
  border-radius: 16px;
  padding: 4px 12px;
  font-size: 0.9em;
  gap: 8px;
}

.org-badge.primary-org {
  background-color: #e3f2fd;
  border: 1px solid #2196f3;
}

.role-label {
  font-weight: 500;
  color: #666;
}

.expanded-details {
  background-color: #f8f9fa;
}

.user-details {
  padding: 1rem;
}

.org-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.org-detail-card {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 1rem;
}

.org-detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  border-bottom: 1px solid #dee2e6;
  padding-bottom: 0.5rem;
}

.org-detail-header h5 {
  margin: 0;
  font-size: 1.1em;
}

.org-status {
  font-size: 0.8em;
  padding: 2px 8px;
  border-radius: 12px;
  background-color: #e9ecef;
}

.org-status.primary {
  background-color: #e3f2fd;
  color: #1976d2;
}

.permissions-list {
  margin: 0;
  padding-left: 1.5rem;
  list-style-type: disc;
}

.permissions-list li {
  margin-bottom: 0.25rem;
  color: #666;
}

.notice {
  padding: 1rem;
  margin: 1rem 0;
  border-left: 4px solid;
  background: white;
}

.notice-success {
  border-left-color: #46b450;
}

.notice-error {
  border-left-color: #dc3232;
}
</style>
