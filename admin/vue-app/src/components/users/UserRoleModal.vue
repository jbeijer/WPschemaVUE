<template>
  <div class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Hantera användarroller</h3>
        <button class="close-button" @click="$emit('close')">&times;</button>
      </div>
      <div class="modal-body">
        <LoadingIndicator v-if="loading" message="Laddar användare..." />
        <ErrorMessage v-if="error" :message="error" />
        
        <div v-if="!loading && !error">
          <div class="organization-info">
            <h4>Organisation: {{ organization.name }}</h4>
            <p v-if="organization.description">{{ organization.description }}</p>
          </div>
          
          <div class="user-search">
            <div class="search-field">
              <label for="user-search">Sök efter användare</label>
              <input 
                type="text"
                id="user-search"
                v-model="searchQuery"
                placeholder="Namn eller e-post"
              >
            </div>
            <BaseButton @click="searchUsers" :disabled="searchQuery.length < 3">
              Sök
            </BaseButton>
          </div>
          
          <div v-if="searchResults.length > 0" class="search-results">
            <h5>Sökresultat</h5>
            <div class="users-list">
              <div v-for="user in searchResults" :key="user.id" class="user-item">
                <div class="user-info">
                  <div class="user-name">{{ user.display_name }}</div>
                  <div class="user-email">{{ user.user_email }}</div>
                </div>
                <div class="user-actions">
                  <div class="role-select" v-if="!isUserInOrganization(user.id)">
                    <select v-model="selectedRoles[user.id]">
                      <option 
                        v-for="role in assignableRoles" 
                        :key="role.id" 
                        :value="role.id"
                      >
                        {{ role.name }}
                      </option>
                    </select>
                    <BaseButton 
                      size="small" 
                      @click="addUserToOrganization(user)"
                      :disabled="!selectedRoles[user.id]"
                    >
                      Lägg till
                    </BaseButton>
                  </div>
                  <div v-else class="already-added">
                    Redan tillagd som {{ getRoleName(getUserRole(user.id)) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="current-members">
            <h5>Nuvarande medlemmar</h5>
            <div v-if="members.length === 0" class="empty-state">
              <p>Inga medlemmar hittades</p>
            </div>
            <div v-else class="members-list">
              <div v-for="member in members" :key="member.id" class="member-item">
                <div class="member-info">
                  <div class="member-name">{{ member.display_name }}</div>
                  <div class="member-email">{{ member.user_email }}</div>
                </div>
                <div class="member-actions">
                  <!-- Only show role selector for other users (not current user) -->
                  <div v-if="member.id !== currentUserId" class="role-select">
                    <select v-model="memberRoles[member.id]" @change="updateUserRole(member)">
                      <option 
                        v-for="role in assignableRoles" 
                        :key="role.id" 
                        :value="role.id"
                      >
                        {{ role.name }}
                      </option>
                    </select>
                    <BaseButton 
                      size="small"
                      variant="danger" 
                      @click="confirmRemoveUser(member)"
                    >
                      Ta bort
                    </BaseButton>
                  </div>
                  <div v-else class="current-user-badge">
                    <RoleBadge :role="memberRoles[member.id] || 'bas'" />
                    <span>(Du)</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <BaseButton variant="secondary" @click="$emit('close')">
          Stäng
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import { useStore } from 'vuex';
import { computed, ref, reactive, onMounted } from 'vue';
import { getRoleName, getAssignableRoles } from '@/utils/roleUtils';
import BaseButton from '@/components/BaseButton.vue';
import LoadingIndicator from '@/components/LoadingIndicator.vue';
import ErrorMessage from '@/components/ErrorMessage.vue';
import RoleBadge from '@/components/RoleBadge.vue';

export default defineComponent({
  name: 'UserRoleModal',
  
  components: {
    BaseButton,
    LoadingIndicator,
    ErrorMessage,
    RoleBadge
  },
  
  props: {
    organization: {
      type: Object,
      required: true
    }
  },
  
  emits: ['close', 'update'],
  
  setup(props, { emit }) {
    const store = useStore();
    const loading = ref(false);
    const error = ref('');
    const members = ref([]);
    const memberRoles = reactive({});
    const searchQuery = ref('');
    const searchResults = ref([]);
    const selectedRoles = reactive({});
    
    const currentUserId = computed(() => store.state.user.id);
    
    // Get the current user's role for this organization
    const userRole = computed(() => 
      store.getters['permissions/getUserRole'](props.organization.id)
    );
    
    // Only admins can assign roles
    const isAdmin = computed(() => userRole.value === 'admin');
    
    // Get roles that current user can assign
    const assignableRoles = computed(() => {
      // If not admin, return empty array
      if (!isAdmin.value) return [];
      
      return getAssignableRoles(userRole.value);
    });
    
    // Load organization members
    const loadMembers = async () => {
      if (!props.organization.id) return;
      
      loading.value = true;
      error.value = '';
      
      try {
        const response = await fetch(`/wp-json/wpschema/v1/organizations/${props.organization.id}/members`);
        
        if (!response.ok) {
          throw new Error('Kunde inte ladda medlemmar');
        }
        
        const data = await response.json();
        members.value = data;
        
        // Initialize member roles
        data.forEach(member => {
          memberRoles[member.id] = member.role || 'bas';
        });
      } catch (err) {
        console.error('Error loading members:', err);
        error.value = 'Ett fel uppstod vid hämtning av medlemmar';
      } finally {
        loading.value = false;
      }
    };
    
    // Search for users
    const searchUsers = async () => {
      if (searchQuery.value.length < 3) return;
      
      loading.value = true;
      error.value = '';
      
      try {
        const response = await fetch(`/wp-json/wpschema/v1/users/search?query=${encodeURIComponent(searchQuery.value)}`);
        
        if (!response.ok) {
          throw new Error('Kunde inte söka efter användare');
        }
        
        const data = await response.json();
        searchResults.value = data;
        
        // Initialize selected roles for new users
        data.forEach(user => {
          if (!selectedRoles[user.id]) {
            selectedRoles[user.id] = 'bas';
          }
        });
      } catch (err) {
        console.error('Error searching users:', err);
        error.value = 'Ett fel uppstod vid sökning av användare';
      } finally {
        loading.value = false;
      }
    };
    
    // Check if user is already in the organization
    const isUserInOrganization = (userId) => {
      return members.value.some(member => member.id === userId);
    };
    
    // Get role for a user in the organization
    const getUserRole = (userId) => {
      const member = members.value.find(member => member.id === userId);
      return member ? member.role : null;
    };
    
    // Add user to organization
    const addUserToOrganization = async (user) => {
      if (!props.organization.id || !user.id || !selectedRoles[user.id]) return;
      
      loading.value = true;
      error.value = '';
      
      try {
        const response = await fetch(`/wp-json/wpschema/v1/organizations/${props.organization.id}/members`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            user_id: user.id,
            role: selectedRoles[user.id]
          })
        });
        
        if (!response.ok) {
          throw new Error('Kunde inte lägga till användare i organisationen');
        }
        
        // Reload members to show the newly added user
        await loadMembers();
        
        // Clear search results
        searchResults.value = [];
        searchQuery.value = '';
        
        // Notify parent component
        emit('update');
      } catch (err) {
        console.error('Error adding user to organization:', err);
        error.value = 'Ett fel uppstod vid tillägg av användare';
      } finally {
        loading.value = false;
      }
    };
    
    // Update user role
    const updateUserRole = async (user) => {
      if (!props.organization.id || !user.id || !memberRoles[user.id]) return;
      
      try {
        const response = await fetch(`/wp-json/wpschema/v1/organizations/${props.organization.id}/members/${user.id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            role: memberRoles[user.id]
          })
        });
        
        if (!response.ok) {
          throw new Error('Kunde inte uppdatera användarroll');
        }
        
        // Notify parent component
        emit('update');
      } catch (err) {
        console.error('Error updating user role:', err);
        error.value = 'Ett fel uppstod vid uppdatering av användarroll';
        
        // Reset to previous role
        memberRoles[user.id] = user.role || 'bas';
      }
    };
    
    // Confirm removal of user
    const confirmRemoveUser = (user) => {
      if (confirm(`Är du säker på att du vill ta bort ${user.display_name} från organisationen?`)) {
        removeUserFromOrganization(user);
      }
    };
    
    // Remove user from organization
    const removeUserFromOrganization = async (user) => {
      if (!props.organization.id || !user.id) return;
      
      loading.value = true;
      error.value = '';
      
      try {
        const response = await fetch(`/wp-json/wpschema/v1/organizations/${props.organization.id}/members/${user.id}`, {
          method: 'DELETE'
        });
        
        if (!response.ok) {
          throw new Error('Kunde inte ta bort användare från organisationen');
        }
        
        // Remove user from members list
        members.value = members.value.filter(member => member.id !== user.id);
        
        // Remove user's role
        delete memberRoles[user.id];
        
        // Notify parent component
        emit('update');
      } catch (err) {
        console.error('Error removing user from organization:', err);
        error.value = 'Ett fel uppstod vid borttagning av användare';
      } finally {
        loading.value = false;
      }
    };
    
    onMounted(() => {
      loadMembers();
    });
    
    return {
      loading,
      error,
      members,
      memberRoles,
      searchQuery,
      searchResults,
      selectedRoles,
      currentUserId,
      userRole,
      isAdmin,
      assignableRoles,
      getRoleName,
      isUserInOrganization,
      getUserRole,
      searchUsers,
      addUserToOrganization,
      updateUserRole,
      confirmRemoveUser
    };
  }
});
</script>

<style scoped>
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
  background-color: white;
  border-radius: 4px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.3rem;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  color: #666;
}

.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex-grow: 1;
}

.modal-footer {
  padding: 15px 20px;
  border-top: 1px solid #e0e0e0;
  display: flex;
  justify-content: flex-end;
}

.organization-info {
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
}

.organization-info h4 {
  margin-top: 0;
  margin-bottom: 5px;
}

.organization-info p {
  margin: 0;
  color: #666;
}

.user-search {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  margin-bottom: 20px;
}

.search-field {
  flex-grow: 1;
}

.search-field label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.search-field input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.search-results,
.current-members {
  margin-top: 20px;
}

.search-results h5,
.current-members h5 {
  margin-top: 0;
  margin-bottom: 10px;
  padding-bottom: 5px;
  border-bottom: 1px solid #e0e0e0;
}

.users-list,
.members-list {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.user-item,
.member-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  border-bottom: 1px solid #e0e0e0;
}

.user-item:last-child,
.member-item:last-child {
  border-bottom: none;
}

.user-info,
.member-info {
  flex-grow: 1;
}

.user-name,
.member-name {
  font-weight: 500;
}

.user-email,
.member-email {
  font-size: 0.9rem;
  color: #666;
}

.user-actions,
.member-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 250px;
  justify-content: flex-end;
}

.role-select {
  display: flex;
  align-items: center;
  gap: 5px;
}

.role-select select {
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 4px;
  min-width: 120px;
}

.already-added {
  font-style: italic;
  color: #666;
}

.current-user-badge {
  display: flex;
  align-items: center;
  gap: 5px;
}

.current-user-badge span {
  font-style: italic;
  font-size: 0.9rem;
  color: #666;
}

.empty-state {
  text-align: center;
  padding: 30px;
  color: #666;
  background-color: #f9f9f9;
  border-radius: 4px;
}
</style>
