<template>
  <div class="resource-availability">
    <h3>Tillgänglighet för {{ resource.name }}</h3>
    
    <!-- 24/7 Toggle -->
    <div class="availability-toggle">
      <label>
        <input type="checkbox" v-model="is24_7" @change="handle24_7Change">
        Tillgänglig dygnet runt, alla dagar
      </label>
    </div>

    <!-- Veckoschema -->
    <div class="weekly-schedule" v-if="!is24_7">
      <div v-for="(day, index) in weekDays" :key="day" class="day-schedule">
        <div class="day-header">
          <label>
            <input 
              type="checkbox" 
              v-model="schedule[index].enabled"
              @change="updateDaySchedule(index)"
            >
            {{ day }}
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
      <h4>Specialdagar</h4>
      <button class="btn btn-small" @click="addSpecialDate">
        Lägg till specialdag
      </button>
      
      <div v-for="(date, index) in specialDates" :key="index" class="special-date">
        <input type="date" v-model="date.date">
        <div class="time-slots" v-if="!date.isClosed">
          <input type="time" v-model="date.startTime">
          <input type="time" v-model="date.endTime">
        </div>
        <label>
          <input type="checkbox" v-model="date.isClosed">
          Stängd hela dagen
        </label>
        <button class="btn btn-danger btn-small" @click="removeSpecialDate(index)">
          Ta bort
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ResourceAvailability',
  props: {
    resource: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      is24_7: false,
      weekDays: ['Måndag', 'Tisdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lördag', 'Söndag'],
      schedule: Array(7).fill().map(() => ({
        enabled: false,
        startTime: '09:00',
        endTime: '17:00'
      })),
      specialDates: []
    }
  },
  methods: {
    async handle24_7Change() {
      try {
        await this.saveAvailability();
      } catch (error) {
        console.error('Error saving 24/7 availability:', error);
        this.is24_7 = !this.is24_7; // Återställ om det misslyckas
      }
    },

    async updateDaySchedule(dayIndex) {
      if (!this.schedule[dayIndex].enabled) {
        this.schedule[dayIndex].startTime = '09:00';
        this.schedule[dayIndex].endTime = '17:00';
      }
      await this.saveAvailability();
    },

    addSpecialDate() {
      this.specialDates.push({
        date: '',
        startTime: '09:00',
        endTime: '17:00',
        isClosed: false
      });
    },

    removeSpecialDate(index) {
      this.specialDates.splice(index, 1);
      this.saveAvailability();
    },

    async saveAvailability() {
      try {
        const availability = {
          resourceId: this.resource.id,
          is24_7: this.is24_7,
          weeklySchedule: this.schedule,
          specialDates: this.specialDates
        };

        await this.$store.dispatch('resources/saveAvailability', availability);
      } catch (error) {
        console.error('Error saving availability:', error);
        throw error;
      }
    },

    async loadAvailability() {
      try {
        const availability = await this.$store.dispatch(
          'resources/fetchAvailability', 
          this.resource.id
        );
        
        this.is24_7 = availability.is24_7;
        this.schedule = availability.weeklySchedule;
        this.specialDates = availability.specialDates;
      } catch (error) {
        console.error('Error loading availability:', error);
      }
    }
  },
  created() {
    this.loadAvailability();
  }
}
</script>

<style scoped>
.resource-availability {
  padding: 20px;
}

.weekly-schedule {
  margin-top: 20px;
}

.day-schedule {
  margin-bottom: 15px;
  padding: 10px;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
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
}

.special-date {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-top: 10px;
  padding: 10px;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
}
</style> 