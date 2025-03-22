<template>
  <div class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>{{ organization.name }}</h3>
        <button class="close-button" @click="$emit('close')">&times;</button>
      </div>
      <div class="modal-body">
        <!-- Loading and error states -->
        <LoadingIndicator v-if="loading" message="Laddar organisationsdetaljer..." />
        <ErrorMessage v-if="error" :message="error" />
        
        <div v-if="!loading && !error" class="organization-details">
          <div class="detail-section">
            <h4>Information</h4>
            <div class="detail-row">
              <div class="detail-label">Namn:</div>
              <div class="detail-value">{{ organization.name }}</div>
            </div>
            <div class="detail-row">
              <div class="detail-label">Beskrivning:</div>
              <div class="detail-value">{{ organization.description || 'Ingen beskrivning' }}</div>
            </div>
            <div class="detail-row">
              <div class="detail-label">Skapad:</div>
              <div class="detail-value">{{ formatOrganizationDate(organization.created_at) }}</div>
            </div>
          </div>
          
          <div class="detail-section">
            <h4>Din roll</h4>
            <div class="detail-row">
              <RoleBadge :role="userRole" />
              <div class="role-permissions">
                <h5>Din roll ger dig behörighet att:</h5>
                <ul>
                  <li v-for="(permission, index) in rolePermissions" :key="index">
                    {{ permission }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
          
          <!-- Only show members section for admins and schedulers -->
          <div class="detail-section" v-if="canViewMembers">
            <h4>Medlemmar</h4>
            <LoadingIndicator v-if="loadingMembers" message="Laddar medlemmar..." />
            <ErrorMessage v-if="membersError" :message="membersError" />
            
            <div v-if="!loadingMembers && !membersError">
              <div v-if="members.length === 0" class="empty-members">
                <p>Inga medlemmar hittades i organisationen.</p>
              </div>
              
              <div v-else class="members-list">
                <div v-for="member in members" :key="member.id" class="member-item">
                  <div class="member-info">
                    <div class="member-name">{{ member.display_name }}</div>
                    <div class="member-email">{{ member.user_email }}</div>
                  </div>
                  <RoleBadge :role="member.role" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <BaseButton 
          variant="secondary" 
          @click="$emit('close')"
        >
          Stäng
        </BaseButton>
        
        <!-- Only show edit button for admins -->
        <BaseButton 
          v-if="isAdmin"
          variant="primary" 
          @click="$emit('edit', organization)"
        >
          Redigera
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import { formatOrganizationDate } from '@/utils/dateUtils';
import { getRolePermissions } from '@/utils/roleUtils';
import { useStore } from 'vuex';
import { computed, ref, watch } from 'vue';
import LoadingIndicator from '@/components/LoadingIndicator.vue';
import ErrorMessage from '@/components/ErrorMessage.vue';
import BaseButton from '@/components/BaseButton.vue';
import RoleBadge from '@/components/RoleBadge.vue';

export default defineComponent({
  name: 'OrganizationViewModal',
  
  components: {
    LoadingIndicator,
    ErrorMessage,
    BaseButton,
    RoleBadge
  },
  
  props: {
    organization: {
      type: Object,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: ''
    }
  },
  
  emits: ['close', 'edit'],
  
  setup(props) {
    const store = useStore();
    const members = ref([]);
    const loadingMembers = ref(false);
    const membersError = ref('');
    
    // Get user role for this organization
    const userRole = computed(() => {
      if (store && store.getters && store.getters['permissions/getUserRole']) {
        return store.getters['permissions/getUserRole'](props.organization.id);
      }
      return 'bas'; // Default to basic role if store is not available
    });
    
    // Get permissions for user role
    const rolePermissions = computed(() => 
      getRolePermissions(userRole.value)
    );
    
    // Check if user is admin
    const isAdmin = computed(() => 
      userRole.value === 'admin'
    );
    
    // Check if user can view members (Admin and Scheduler roles)
    const canViewMembers = computed(() => 
      ['admin', 'schemaläggare'].includes(userRole.value)
    );
    
    // Load members if possible (only for admin and scheduler roles)
    const loadMembers = async () => {
      if (!canViewMembers.value) return;
      
      loadingMembers.value = true;
      membersError.value = '';
      
      try {
        // Assuming there's an API endpoint to get organization members
        const response = await fetch(`/wp-json/wpschema/v1/organizations/${props.organization.id}/members`);
        
        if (!response.ok) {
          throw new Error('Kunde inte ladda medlemmar');
        }
        
        const data = await response.json();
        members.value = data;
      } catch (error) {
        console.error('Error loading members:', error);
        membersError.value = 'Ett fel uppstod vid hämtning av medlemmar';
      } finally {
        loadingMembers.value = false;
      }
    };
    
    // Watch for organization changes to reload members
    watch(() => props.organization.id, () => {
      if (props.organization.id) {
        loadMembers();
      }
    }, { immediate: true });
    
    return {
      formatOrganizationDate,
      userRole,
      rolePermissions,
      isAdmin,
      canViewMembers,
      members,
      loadingMembers,
      membersError
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
  max-width: 600px;
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
  gap: 10px;
}

.organization-details {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-section {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  padding: 15px;
}

.detail-section h4 {
  margin-top: 0;
  margin-bottom: 10px;
  font-size: 1.1rem;
  color: #333;
  border-bottom: 1px solid #eee;
  padding-bottom: 8px;
}

.detail-row {
  display: flex;
  margin-bottom: 10px;
}

.detail-label {
  font-weight: bold;
  min-width: 120px;
  color: #555;
}

.role-permissions {
  margin-left: 15px;
}

.role-permissions h5 {
  font-size: 0.9rem;
  margin: 10px 0 5px 0;
  color: #555;
}

.role-permissions ul {
  margin: 0;
  padding-left: 20px;
}

.role-permissions li {
  margin-bottom: 3px;
  font-size: 0.9rem;
}

.members-list {
  margin-top: 10px;
}

.member-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.member-item:last-child {
  border-bottom: none;
}

.member-name {
  font-weight: 500;
}

.member-email {
  font-size: 0.9rem;
  color: #666;
}

.empty-members {
  text-align: center;
  color: #666;
  padding: 20px 0;
}
</style>
