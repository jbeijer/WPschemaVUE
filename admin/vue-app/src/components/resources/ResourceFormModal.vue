<template>
  <div class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>{{ title }}</h3>
        <button class="close-button" @click="$emit('close')">&times;</button>
      </div>
      <div class="modal-body">
        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label for="resource-name">Namn</label>
            <input 
              type="text" 
              id="resource-name" 
              v-model="formData.name" 
              required
              autofocus
            >
          </div>
          
          <div class="form-group">
            <label for="resource-description">Beskrivning</label>
            <textarea 
              id="resource-description" 
              v-model="formData.description" 
              rows="3"
            ></textarea>
          </div>
          
          <div class="form-group">
            <label for="resource-color">Färg</label>
            <div class="color-picker">
              <input 
                type="color" 
                id="resource-color" 
                v-model="formData.color"
              >
              <span class="color-value">{{ formData.color }}</span>
            </div>
          </div>
          
          <div class="form-group" v-if="!isEditing">
            <label>Tillgänglighet</label>
            <div class="availability-options">
              <div class="radio-option">
                <input 
                  type="radio" 
                  id="available-24-7" 
                  v-model="formData.is_24_7" 
                  :value="true"
                >
                <label for="available-24-7">Tillgänglig 24/7</label>
              </div>
              <div class="radio-option">
                <input 
                  type="radio" 
                  id="available-specific" 
                  v-model="formData.is_24_7" 
                  :value="false"
                >
                <label for="available-specific">Tillgänglig under specifika tider</label>
              </div>
            </div>
          </div>
          
          <div class="form-group" v-if="!formData.is_24_7 && !isEditing">
            <div class="time-inputs">
              <div class="time-input">
                <label for="resource-start-time">Starttid</label>
                <input 
                  type="time" 
                  id="resource-start-time" 
                  v-model="formData.start_time" 
                  required
                >
              </div>
              <div class="time-input">
                <label for="resource-end-time">Sluttid</label>
                <input 
                  type="time" 
                  id="resource-end-time" 
                  v-model="formData.end_time" 
                  required
                >
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
import BaseButton from '@/components/BaseButton.vue';

export default defineComponent({
  name: 'ResourceFormModal',
  
  components: {
    BaseButton
  },
  
  props: {
    title: {
      type: String,
      required: true
    },
    resource: {
      type: Object,
      required: true
    },
    organizationId: {
      type: [Number, String],
      required: true
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
  
  data() {
    return {
      formData: { ...this.resource }
    };
  },
  
  computed: {
    submitButtonText() {
      if (this.loading) {
        return this.isEditing ? 'Sparar...' : 'Skapar...';
      }
      return this.isEditing ? 'Spara' : 'Skapa';
    }
  },
  
  watch: {
    resource: {
      handler(newResource) {
        this.formData = { ...newResource };
      },
      deep: true
    }
  },
  
  methods: {
    async handleSubmit() {
      // Make a copy to avoid mutating the prop directly
      const resourceData = { ...this.formData };
      
      // Format time values to ensure they match the required pattern HH:MM
      if (!resourceData.is_24_7) {
        if (resourceData.start_time) {
          resourceData.start_time = resourceData.start_time.substring(0, 5);
        }
        if (resourceData.end_time) {
          resourceData.end_time = resourceData.end_time.substring(0, 5);
        }
      }
      
      // Add the organization_id if it's not already set
      if (!resourceData.organization_id) {
        resourceData.organization_id = this.organizationId;
      }
      
      const result = await this.$emit('submit', resourceData);
      
      // If the parent returns false, it means the submission failed
      // In that case, don't close the modal
      if (result !== false) {
        this.$emit('close');
      }
    }
  }
});
</script>

<style scoped>
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

.form-group textarea {
  resize: vertical;
}

.color-picker {
  display: flex;
  align-items: center;
  gap: 10px;
}

.color-picker input[type="color"] {
  width: 50px;
  height: 38px;
  padding: 0;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.color-value {
  font-family: monospace;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.availability-options {
  display: flex;
  gap: 20px;
  margin-top: 5px;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 5px;
}

.time-inputs {
  display: flex;
  gap: 15px;
}

.time-input {
  flex: 1;
}
</style>
