<template>
  <div class="resource-availability">
    <h3>Tillgänglighet för {{ resource.name }}</h3>
    
    <!-- Loading and Error States -->
    <LoadingIndicator v-if="loading" message="Laddar tillgänglighet..." />
    <ErrorMessage v-if="error" :message="error" />
    
    <div v-if="!loading && !error">
      <!-- 24/7 Toggle -->
      <div class="availability-toggle">
        <label class="toggle-label">
          <input type="checkbox" v-model="is24_7" @change="handle24_7Change">
          <span>Tillgänglig dygnet runt, alla dagar</span>
        </label>
      </div>

      <!-- Veckoschema -->
      <div class="weekly-schedule" v-if="!is24_7">
        <h4>Veckoschema</h4>
        <div v-for="(day, index) in weekDays" :key="day" class="day-schedule">
          <div class="day-header">
            <label class="toggle-label">
              <input 
                type="checkbox" 
                v-model="schedule[index].enabled"
                @change="updateDaySchedule(index)"
              >
              <span>{{ day }}</span>
            </label>
          </div>
          
          <div class="time-slots" v-if="schedule[index].enabled">
            <div class="time-input">
              <label>Från:</label>
              <input 
                type="time" 
                v-model="schedule[index].startTime"
                @change="updateDaySchedule(index)"
              >
            </div>
            <div class="time-input">
              <label>Till:</label>
              <input 
                type="time" 
                v-model="schedule[index].endTime"
                @change="updateDaySchedule(index)"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Specialdagar -->
      <div class="special-dates">
        <div class="special-dates-header">
          <h4>Specialdagar</h4>
          <InfoTooltip message="Specialdagar har högre prioritet än veckoschemat. Lägg till dagar som har annan tillgänglighet, t.ex. helgdagar." />
        </div>
        
        <div class="special-dates-list">
          <div v-for="(date, index) in specialDates" :key="index" class="special-date">
            <div class="special-date-header">
              <input 
                type="date" 
                v-model="date.date"
                @change="saveAvailability"
                class="date-input"
              >
              <label class="toggle-label closed-toggle">
                <input 
                  type="checkbox" 
                  v-model="date.isClosed"
                  @change="saveAvailability"
                >
                <span>Stängd hela dagen</span>
              </label>
            </div>
            
            <div class="time-slots" v-if="!date.isClosed">
              <div class="time-input">
                <label>Från:</label>
                <input 
                  type="time" 
                  v-model="date.startTime"
                  @change="saveAvailability"
                >
              </div>
              <div class="time-input">
                <label>Till:</label>
                <input 
                  type="time" 
                  v-model="date.endTime"
                  @change="saveAvailability"
                >
              </div>
            </div>
            
            <BaseButton 
              variant="danger" 
              size="small" 
              @click="removeSpecialDate(index)"
              class="remove-date-btn"
            >
              Ta bort
            </BaseButton>
          </div>
          
          <div v-if="specialDates.length === 0" class="no-special-dates">
            Inga specialdagar tillagda.
          </div>
        </div>
        
        <BaseButton 
          size="small" 
          @click="addSpecialDate"
          class="add-special-date-btn"
        >
          <span class="plus-icon">+</span> Lägg till specialdag
        </BaseButton>
      </div>

      <!-- Save button (only shown if changes are pending) -->
      <div class="save-section" v-if="hasUnsavedChanges">
        <BaseButton 
          variant="primary" 
          :loading="saving" 
          @click="saveAvailability"
        >
          {{ saving ? 'Sparar...' : 'Spara ändringar' }}
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import { useResourcesStore } from '@/stores/resources';
import BaseButton from '@/components/BaseButton.vue';
import LoadingIndicator from '@/components/LoadingIndicator.vue';
import ErrorMessage from '@/components/ErrorMessage.vue';
import InfoTooltip from '@/components/InfoTooltip.vue';

export default defineComponent({
  name: 'ResourceAvailability',
  
  components: {
    BaseButton,
    LoadingIndicator,
    ErrorMessage,
    InfoTooltip
  },
  
  props: {
    resource: {
      type: Object,
      required: true
    }
  },
  
  data() {
    return {
      loading: false,
      saving: false,
      error: null,
      is24_7: false,
      originalData: null,
      resourcesStore: null,
      
      weekDays: ['Måndag', 'Tisdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lördag', 'Söndag'],
      schedule: Array(7).fill().map(() => ({
        enabled: false,
        startTime: '09:00',
        endTime: '17:00'
      })),
      
      specialDates: []
    };
  },
  
  computed: {
    /**
     * Check if there are unsaved changes by comparing current state with original data
     */
    hasUnsavedChanges() {
      if (!this.originalData) return false;
      
      // Check if 24/7 status changed
      if (this.is24_7 !== this.originalData.is24_7) return true;
      
      // Check if schedule changed
      if (!this.is24_7) {
        for (let i = 0; i < 7; i++) {
          const original = this.originalData.weeklySchedule[i];
          const current = this.schedule[i];
          
          if (original.enabled !== current.enabled ||
              original.startTime !== current.startTime ||
              original.endTime !== current.endTime) {
            return true;
          }
        }
      }
      
      // Check if special dates changed (using simple length comparison for now)
      if (this.specialDates.length !== this.originalData.specialDates.length) {
        return true;
      }
      
      return false;
    }
  },
  
  methods: {
    /**
     * Handle changes to 24/7 availability toggle
     */
    async handle24_7Change() {
      try {
        await this.saveAvailability();
      } catch (error) {
        // Restore previous state on error
        this.is24_7 = !this.is24_7;
      }
    },

    /**
     * Update day schedule with default times when enabled
     */
    async updateDaySchedule(dayIndex) {
      if (!this.schedule[dayIndex].enabled) {
        // Reset to default times when disabling
        this.schedule[dayIndex].startTime = '09:00';
        this.schedule[dayIndex].endTime = '17:00';
      }
      await this.saveAvailability();
    },

    /**
     * Add a new special date with default values
     */
    addSpecialDate() {
      // Create a new date and format it as YYYY-MM-DD
      const today = new Date();
      const formattedDate = today.toISOString().split('T')[0];
      
      this.specialDates.push({
        date: formattedDate,
        startTime: '09:00',
        endTime: '17:00',
        isClosed: false
      });
    },

    /**
     * Remove a special date by index
     */
    removeSpecialDate(index) {
      this.specialDates.splice(index, 1);
      this.saveAvailability();
    },

    /**
     * Save all availability data to the store
     */
    async saveAvailability() {
      this.saving = true;
      this.error = null;
      
      try {
        const availability = {
          resourceId: this.resource.id,
          is24_7: this.is24_7,
          weeklySchedule: this.schedule,
          specialDates: this.specialDates
        };

        await this.resourcesStore.saveAvailability(availability);
        
        // Update original data after successful save
        this.originalData = JSON.parse(JSON.stringify(availability));
      } catch (error) {
        console.error('Error saving availability:', error);
        this.error = 'Kunde inte spara tillgänglighet: ' + (error.message || 'Okänt fel');
        throw error;
      } finally {
        this.saving = false;
      }
    },

    /**
     * Load availability data from the store
     */
    async loadAvailability() {
      this.loading = true;
      this.error = null;
      
      try {
        const availability = await this.resourcesStore.fetchAvailability(this.resource.id);
        
        this.is24_7 = availability.is24_7;
        this.schedule = availability.weeklySchedule;
        this.specialDates = availability.specialDates;
        
        // Store original data for change detection
        this.originalData = JSON.parse(JSON.stringify({
          is24_7: this.is24_7,
          weeklySchedule: this.schedule,
          specialDates: this.specialDates
        }));
      } catch (error) {
        console.error('Error loading availability:', error);
        this.error = 'Kunde inte ladda tillgänglighet: ' + (error.message || 'Okänt fel');
      } finally {
        this.loading = false;
      }
    }
  },
  
  created() {
    // Initialize the stores according to the established pattern
    this.resourcesStore = useResourcesStore();
    this.loadAvailability();
  }
});
</script>

<style scoped>
.resource-availability {
  padding: 20px;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.toggle-label input[type="checkbox"] {
  margin: 0;
}

.weekly-schedule {
  margin-top: 20px;
  border: 1px solid #eee;
  border-radius: 4px;
  padding: 15px;
  background-color: #f9f9f9;
}

.weekly-schedule h4 {
  margin-top: 0;
  margin-bottom: 15px;
  color: #333;
}

.day-schedule {
  margin-bottom: 15px;
  padding: 10px;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  background-color: white;
}

.time-slots {
  display: flex;
  gap: 15px;
  margin-top: 10px;
}

.time-input {
  display: flex;
  align-items: center;
  gap: 8px;
}

.special-dates {
  margin-top: 30px;
  border: 1px solid #eee;
  border-radius: 4px;
  padding: 15px;
  background-color: #f9f9f9;
}

.special-dates-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
}

.special-dates-header h4 {
  margin: 0;
  color: #333;
}

.special-dates-list {
  margin-bottom: 15px;
}

.special-date {
  margin-bottom: 15px;
  padding: 12px;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  background-color: white;
  position: relative;
}

.special-date-header {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 10px;
  align-items: center;
}

.date-input {
  padding: 5px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.closed-toggle {
  font-weight: bold;
}

.remove-date-btn {
  position: absolute;
  top: 12px;
  right: 12px;
}

.add-special-date-btn {
  display: flex;
  align-items: center;
  gap: 5px;
}

.plus-icon {
  font-size: 1.2em;
}

.no-special-dates {
  padding: 15px;
  text-align: center;
  color: #666;
  font-style: italic;
}

.save-section {
  margin-top: 30px;
  display: flex;
  justify-content: flex-end;
}

@media (max-width: 576px) {
  .time-slots {
    flex-direction: column;
    gap: 10px;
  }
  
  .special-date-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .remove-date-btn {
    position: static;
    margin-top: 10px;
  }
}
</style>