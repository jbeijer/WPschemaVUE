<template>
  <div class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>{{ isEditing ? 'Redigera organisation' : 'Skapa ny organisation' }}</h3>
        <button class="close-button" @click="$emit('close')">&times;</button>
      </div>
      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label for="org-name">Namn</label>
            <input 
              type="text" 
              id="org-name" 
              v-model="formData.name" 
              required
              autofocus
            >
          </div>
          
          <div class="form-group">
            <label for="org-description">Beskrivning</label>
            <textarea 
              id="org-description" 
              v-model="formData.description" 
              rows="3"
            ></textarea>
          </div>
          
          <!-- Only admins can modify user roles within organizations -->
          <div class="form-group" v-if="isAdmin && members.length > 0 && isEditing">
            <h4>Hantera medlemmar</h4>
            <div class="members-list">
              <div v-for="member in members" :key="member.id" class="member-item">
                <div class="member-info">
                  <div class="member-name">{{ member.display_name }}</div>
                  <div class="member-email">{{ member.user_email }}</div>
                </div>
                <div class="role-select" v-if="member.id !== currentUserId">
                  <select v-model="memberRoles[member.id]">
                    <option v-for="role in availableRoles" :key="role.id" :value="role.id">
                      {{ role.name }}
                    </option>
                  </select>
                </div>
                <div v-else class="current-user-badge">
                  (Du)
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
  name: 'OrganizationFormModal',
  
  components: {
    BaseButton
  },
  
  props: {
    organization: {
      type: Object,
      default: () => ({
        name: '',
        description: ''
      })
    },
    loading: {
      type: Boolean,
      default: false
    },
    isEditing: {
      type: Boolean,
      default: false
    }
  },
  
  emits: ['close', 'submit'],
  
  setup(props, { emit }) {
    const store = useStore();
    const formData = reactive({
      name: props.organization.name || '',
      description: props.organization.description || ''
    });
    
    const members = ref([]);
    const memberRoles = reactive({});
    const currentUserId = computed(() => store.state.user.id);
    
    // Only admins should be able to edit roles
    const isAdmin = computed(() => {
      if (!props.isEditing) return false;
      return store.getters['permissions/isAdmin'](props.organization.id);
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
    
    // Load members if editing an existing organization
    const loadMembers = async () => {
      if (!props.isEditing || !props.organization.id || !isAdmin.value) return;
      
      try {
        // Fetch members from API
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
      } catch (error) {
        console.error('Error loading members:', error);
      }
    };
    
    const handleSubmit = () => {
      // Validate form
      if (!formData.name.trim()) {
        // Set an error message or handle invalid form
        alert('Organisationsnamn måste anges');
        return;
      }
      
      const payload = {
        ...formData,
        parent_id: props.organization?.parent_id || null
      };
      
      // If editing and admin, include member role updates
      if (props.isEditing && isAdmin.value) {
        payload.member_roles = memberRoles;
      }
      
      // If editing, include organization id
      if (props.isEditing && props.organization.id) {
        payload.id = props.organization.id;
      }
      
      emit('submit', payload);
    };
    
    // Watch for changes to organization prop
    onMounted(() => {
      if (props.organization) {
        formData.name = props.organization.name || '';
        formData.description = props.organization.description || '';
        
        loadMembers();
      }
    });
    
    return {
      formData,
      members,
      memberRoles,
      currentUserId,
      isAdmin,
      availableRoles,
      submitButtonText,
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

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.form-group input,
.form-group textarea,
.role-select select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.form-group textarea {
  resize: vertical;
}

.form-group h4 {
  margin-top: 20px;
  margin-bottom: 10px;
  font-size: 1.1rem;
  color: #333;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.members-list {
  margin-top: 10px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
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

.member-info {
  flex-grow: 1;
}

.member-name {
  font-weight: 500;
}

.member-email {
  font-size: 0.9rem;
  color: #666;
}

.role-select {
  width: 150px;
}

.current-user-badge {
  font-style: italic;
  color: #666;
  font-size: 0.9rem;
}
</style>
