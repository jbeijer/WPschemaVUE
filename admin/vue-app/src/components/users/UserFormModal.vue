<template>
  <div class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>{{ isEditing ? 'Redigera användare' : 'Skapa ny användare' }}</h3>
        <button class="close-button" @click="$emit('close')">&times;</button>
      </div>
      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <!-- Only admin can create users -->
          <div class="admin-required-notice" v-if="!isAdmin">
            <p>Du behöver administratörsbehörighet för att hantera användare.</p>
          </div>
          
          <div v-else>
            <div class="form-group">
              <label for="user-name">Namn</label>
              <input 
                type="text" 
                id="user-name" 
                v-model="formData.display_name" 
                required
                autofocus
              >
            </div>
            
            <div class="form-group">
              <label for="user-email">E-post</label>
              <input 
                type="email" 
                id="user-email" 
                v-model="formData.user_email" 
                required
                :disabled="isEditing"
              >
              <div class="field-hint" v-if="isEditing">
                E-post kan inte ändras efter att användaren skapats
              </div>
            </div>
            
            <!-- Username field - only shown when creating new users -->
            <div class="form-group" v-if="!isEditing">
              <label for="user-login">Användarnamn</label>
              <input 
                type="text" 
                id="user-login" 
                v-model="formData.user_login" 
                required
              >
            </div>
            
            <!-- Password fields - required for new users, optional for editing -->
            <div class="form-group">
              <label for="user-pass">
                {{ isEditing ? 'Lösenord (lämna tomt för att behålla nuvarande)' : 'Lösenord' }}
              </label>
              <input 
                type="password" 
                id="user-pass" 
                v-model="formData.user_pass" 
                :required="!isEditing"
              >
            </div>
            
            <div class="form-group" v-if="formData.user_pass">
              <label for="user-pass-confirm">Bekräfta lösenord</label>
              <input 
                type="password" 
                id="user-pass-confirm" 
                v-model="passwordConfirm" 
                :required="!!formData.user_pass"
              >
              <div class="field-error" v-if="passwordMismatch">
                Lösenorden matchar inte
              </div>
            </div>
            
            <!-- Organization selection for new users -->
            <div class="form-group" v-if="!isEditing && organizations.length > 0">
              <h4>Organisationstillhörighet</h4>
              <div class="organizations-list">
                <div v-for="org in organizations" :key="org.id" class="org-item">
                  <div class="org-checkbox">
                    <input 
                      type="checkbox" 
                      :id="'org-' + org.id" 
                      v-model="selectedOrgs[org.id]"
                    >
                    <label :for="'org-' + org.id">{{ org.name }}</label>
                  </div>
                  <div class="org-role" v-if="selectedOrgs[org.id]">
                    <select v-model="orgRoles[org.id]">
                      <option 
                        v-for="role in availableRoles" 
                        :key="role.id" 
                        :value="role.id"
                      >
                        {{ role.name }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="form-actions">
            <BaseButton 
              type="button" 
              variant="secondary" 
              @click="$emit('close')"
            >
              Avbryt
            </BaseButton>
            <BaseButton 
              type="submit" 
              variant="primary" 
              :loading="loading"
              :disabled="!isAdmin || (formData.user_pass && passwordMismatch)"
            >
              {{ submitButtonText }}
            </BaseButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import { useStore } from 'vuex';
import { computed, ref, reactive, onMounted } from 'vue';
import { getAllRoles } from '@/utils/roleUtils';
import BaseButton from '@/components/BaseButton.vue';

export default defineComponent({
  name: 'UserFormModal',
  
  components: {
    BaseButton
  },
  
  props: {
    user: {
      type: Object,
      default: () => ({
        display_name: '',
        user_email: '',
        user_login: ''
      })
    },
    loading: {
      type: Boolean,
      default: false
    },
    isEditing: {
      type: Boolean,
      default: false
    },
    organizationId: {
      type: [Number, String],
      default: null
    }
  },
  
  emits: ['close', 'submit'],
  
  setup(props, { emit }) {
    const store = useStore();
    const formData = reactive({
      display_name: props.user.display_name || '',
      user_email: props.user.user_email || '',
      user_login: props.user.user_login || '',
      user_pass: ''
    });
    
    const passwordConfirm = ref('');
    const organizations = ref([]);
    const selectedOrgs = reactive({});
    const orgRoles = reactive({});
    
    // Check if current user is admin
    const isAdmin = computed(() => {
      // For editing a user, check if admin in the user's organization
      if (props.isEditing && props.organizationId) {
        return store.getters['permissions/isAdmin'](props.organizationId);
      }
      
      // For creating a user, check if admin in any organization
      return Object.keys(store.state.permissions.userRoles).some(
        orgId => store.getters['permissions/isAdmin'](orgId)
      );
    });
    
    // Get available roles for assignment
    const availableRoles = computed(() => getAllRoles());
    
    // Button text based on state
    const submitButtonText = computed(() => {
      if (props.loading) {
        return props.isEditing ? 'Sparar...' : 'Skapar...';
      }
      return props.isEditing ? 'Spara' : 'Skapa';
    });
    
    // Check if passwords match when both are entered
    const passwordMismatch = computed(() => {
      return formData.user_pass && passwordConfirm.value && formData.user_pass !== passwordConfirm.value;
    });
    
    // Load organizations where current user is admin
    const loadOrganizations = async () => {
      if (props.isEditing) return; // Not needed for editing
      
      try {
        // Fetch organizations where current user is admin
        const response = await fetch('/wp-json/wpschema/v1/organizations?role=admin');
        
        if (!response.ok) {
          throw new Error('Kunde inte ladda organisationer');
        }
        
        const data = await response.json();
        organizations.value = data;
        
        // Initialize organization selections
        data.forEach(org => {
          // Default to not selected
          selectedOrgs[org.id] = false;
          
          // Default role to 'bas'
          orgRoles[org.id] = 'bas';
        });
        
        // If creating in a specific organization context, preselect it
        if (props.organizationId) {
          selectedOrgs[props.organizationId] = true;
        }
      } catch (error) {
        console.error('Error loading organizations:', error);
      }
    };
    
    const handleSubmit = () => {
      if (!isAdmin.value) return;
      
      // Validate passwords match if a password is provided
      if (formData.user_pass && formData.user_pass !== passwordConfirm.value) {
        return;
      }
      
      const payload = { ...formData };
      
      // If editing, include user ID
      if (props.isEditing && props.user.id) {
        payload.id = props.user.id;
      }
      
      // For new users, include organization assignments
      if (!props.isEditing) {
        const organizationAssignments = [];
        
        Object.keys(selectedOrgs).forEach(orgId => {
          if (selectedOrgs[orgId]) {
            organizationAssignments.push({
              organization_id: orgId,
              role: orgRoles[orgId] || 'bas'
            });
          }
        });
        
        payload.organizations = organizationAssignments;
      }
      
      emit('submit', payload);
    };
    
    onMounted(() => {
      if (props.user) {
        formData.display_name = props.user.display_name || '';
        formData.user_email = props.user.user_email || '';
        formData.user_login = props.user.user_login || '';
      }
      
      loadOrganizations();
    });
    
    return {
      formData,
      passwordConfirm,
      organizations,
      selectedOrgs,
      orgRoles,
      isAdmin,
      availableRoles,
      submitButtonText,
      passwordMismatch,
      handleSubmit
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

.admin-required-notice {
  background-color: #fff3cd;
  color: #856404;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.admin-required-notice p {
  margin: 0;
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
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.form-group h4 {
  margin-top: 20px;
  margin-bottom: 10px;
  font-size: 1.1rem;
  color: #333;
}

.field-hint {
  font-size: 0.85rem;
  color: #666;
  margin-top: 5px;
}

.field-error {
  color: #dc3545;
  font-size: 0.85rem;
  margin-top: 5px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.organizations-list {
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  max-height: 200px;
  overflow-y: auto;
}

.org-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  border-bottom: 1px solid #e0e0e0;
}

.org-item:last-child {
  border-bottom: none;
}

.org-checkbox {
  display: flex;
  align-items: center;
  gap: 5px;
}

.org-checkbox label {
  margin-bottom: 0;
}

.org-role select {
  padding: 5px;
  border: 1px solid #ccc;
  border-radius: 4px;
  width: 150px;
}
</style>
