<template>
  <div class="schedules">
    <div class="schedules-header">
      <h2>Scheman</h2>
      <div class="header-actions">
        <div class="resource-selector">
          <label for="resource-select">Resurs:</label>
          <select id="resource-select" v-model="selectedResourceId" @change="loadSchedules">
            <option v-for="resource in resources" :key="resource.id" :value="resource.id">
              {{ resource.name }}
            </option>
          </select>
        </div>
        <div class="date-range-selector">
          <label for="start-date">Från:</label>
          <input type="date" id="start-date" v-model="startDate" @change="loadSchedules">
          <label for="end-date">Till:</label>
          <input type="date" id="end-date" v-model="endDate" @change="loadSchedules">
        </div>
        <button class="btn btn-primary" @click="showCreateForm = true" :disabled="!selectedResourceId">
          Skapa nytt schema
        </button>
      </div>
    </div>
    
    <div class="loading-indicator" v-if="loading">
      <p>Laddar scheman...</p>
    </div>
    
    <div class="error-message" v-if="error">
      <p>{{ error }}</p>
    </div>
    
    <div class="schedules-content" v-if="!loading && !error">
      <div v-if="!selectedResourceId" class="select-resource-message">
        <p>Välj en resurs för att visa scheman.</p>
      </div>
      
      <div v-else-if="schedules.length === 0" class="no-schedules-message">
        <p>Inga scheman hittades för den valda resursen och tidsperioden.</p>
        <p>Klicka på "Skapa nytt schema" för att lägga till ett schema.</p>
      </div>
      
      <div v-else class="schedules-calendar">
        <div class="calendar-header">
          <div class="calendar-title">
            {{ selectedResource ? selectedResource.name : 'Schema' }}
          </div>
          <div class="calendar-navigation">
            <button class="btn btn-small" @click="previousWeek">
              &laquo; Föregående vecka
            </button>
            <span class="current-week">
              {{ formatDateRange(weekStart, weekEnd) }}
            </span>
            <button class="btn btn-small" @click="nextWeek">
              Nästa vecka &raquo;
            </button>
          </div>
        </div>
        
        <div class="calendar-grid">
          <div class="calendar-days">
            <div class="time-column"></div>
            <div 
              v-for="day in weekDays" 
              :key="day.date" 
              class="day-column"
              :class="{ 'today': isToday(day.date) }"
            >
              <div class="day-header">
                <div class="day-name">{{ day.name }}</div>
                <div class="day-date">{{ formatDate(day.date) }}</div>
              </div>
            </div>
          </div>
          
          <div class="calendar-body">
            <div class="time-column">
              <div 
                v-for="hour in hours" 
                :key="hour" 
                class="time-slot"
              >
                {{ formatHour(hour) }}
              </div>
            </div>
            
            <div 
              v-for="day in weekDays" 
              :key="day.date" 
              class="day-column"
            >
              <div 
                v-for="hour in hours" 
                :key="hour" 
                class="time-slot"
                @click="createScheduleAt(day.date, hour)"
              ></div>
              
              <div 
                v-for="schedule in getSchedulesForDay(day.date)" 
                :key="schedule.id" 
                class="schedule-item"
                :class="'status-' + schedule.status"
                :style="getScheduleStyle(schedule)"
                @click="viewSchedule(schedule)"
              >
                <div class="schedule-time">
                  {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                </div>
                <div class="schedule-user">
                  {{ schedule.user_data ? schedule.user_data.display_name : 'Okänd användare' }}
                </div>
                <div class="schedule-status">
                  {{ translateStatus(schedule.status) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create Schedule Modal -->
    <div class="modal" v-if="showCreateForm">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Skapa nytt schema</h3>
          <button class="close-button" @click="showCreateForm = false">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createSchedule">
            <div class="form-group">
              <label for="user_id">Användare</label>
              <select id="user_id" v-model="newSchedule.user_id" required>
                <option value="">Välj användare</option>
                <option v-for="user in users" :key="user.user_id" :value="user.user_id">
                  {{ user.user_data.display_name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label for="start_time">Starttid</label>
              <input type="datetime-local" id="start_time" v-model="newSchedule.start_time" required>
            </div>
            <div class="form-group">
              <label for="end_time">Sluttid</label>
              <input type="datetime-local" id="end_time" v-model="newSchedule.end_time" required>
            </div>
            <div class="form-group">
              <label for="notes">Anteckningar</label>
              <textarea id="notes" v-model="newSchedule.notes" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label for="status">Status</label>
              <select id="status" v-model="newSchedule.status">
                <option value="scheduled">Planerad</option>
                <option value="confirmed">Bekräftad</option>
                <option value="completed">Genomförd</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="showCreateForm = false">
                Avbryt
              </button>
              <button type="submit" class="btn btn-primary" :disabled="createLoading">
                {{ createLoading ? 'Skapar...' : 'Skapa' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- View/Edit Schedule Modal -->
    <div class="modal" v-if="showViewModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ isEditMode ? 'Redigera schema' : 'Visa schema' }}</h3>
          <button class="close-button" @click="closeViewModal">&times;</button>
        </div>
        <div class="modal-body" v-if="selectedSchedule">
          <form v-if="isEditMode" @submit.prevent="updateSchedule">
            <div class="form-group">
              <label for="edit_user_id">Användare</label>
              <select id="edit_user_id" v-model="editedSchedule.user_id" required>
                <option value="">Välj användare</option>
                <option v-for="user in users" :key="user.user_id" :value="user.user_id">
                  {{ user.user_data.display_name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label for="edit_start_time">Starttid</label>
              <input type="datetime-local" id="edit_start_time" v-model="editedSchedule.start_time" required>
            </div>
            <div class="form-group">
              <label for="edit_end_time">Sluttid</label>
              <input type="datetime-local" id="edit_end_time" v-model="editedSchedule.end_time" required>
            </div>
            <div class="form-group">
              <label for="edit_notes">Anteckningar</label>
              <textarea id="edit_notes" v-model="editedSchedule.notes" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label for="edit_status">Status</label>
              <select id="edit_status" v-model="editedSchedule.status">
                <option value="scheduled">Planerad</option>
                <option value="confirmed">Bekräftad</option>
                <option value="completed">Genomförd</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="closeViewModal">
                Avbryt
              </button>
              <button type="submit" class="btn btn-primary" :disabled="updateLoading">
                {{ updateLoading ? 'Sparar...' : 'Spara' }}
              </button>
            </div>
          </form>
          
          <div v-else class="schedule-details">
            <p><strong>Användare:</strong> {{ getUserName(selectedSchedule.user_id) }}</p>
            <p><strong>Resurs:</strong> {{ getResourceName(selectedSchedule.resource_id) }}</p>
            <p><strong>Starttid:</strong> {{ formatDateTime(selectedSchedule.start_time) }}</p>
            <p><strong>Sluttid:</strong> {{ formatDateTime(selectedSchedule.end_time) }}</p>
            <p><strong>Status:</strong> {{ translateStatus(selectedSchedule.status) }}</p>
            <p v-if="selectedSchedule.notes"><strong>Anteckningar:</strong> {{ selectedSchedule.notes }}</p>
            <p><strong>Skapad:</strong> {{ formatDateTime(selectedSchedule.created_at) }}</p>
            <p><strong>Senast uppdaterad:</strong> {{ formatDateTime(selectedSchedule.updated_at) }}</p>
            
            <div class="schedule-actions">
              <button class="btn" @click="isEditMode = true">
                Redigera
              </button>
              <button class="btn btn-danger" @click="confirmDelete(selectedSchedule)">
                Ta bort
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" v-if="showDeleteConfirmation">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Bekräfta borttagning</h3>
          <button class="close-button" @click="showDeleteConfirmation = false">&times;</button>
        </div>
        <div class="modal-body">
          <p>Är du säker på att du vill ta bort detta schema?</p>
          <p class="warning">Denna åtgärd kan inte ångras!</p>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="showDeleteConfirmation = false">
              Avbryt
            </button>
            <button type="button" class="btn btn-danger" @click="deleteSchedule" :disabled="deleteLoading">
              {{ deleteLoading ? 'Tar bort...' : 'Ta bort' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useResourcesStore } from '@/stores/resources';
import { useSchedulesStore } from '@/stores/schedules';
import { useUsersStore } from '@/stores/users';
import { useOrganizationsStore } from '@/stores/organizations';

export default {
  name: 'Schedules',
  data() {
    return {
      loading: false,
      error: null,
      selectedResourceId: null,
      startDate: this.getDefaultStartDate(),
      endDate: this.getDefaultEndDate(),
      weekStart: null,
      weekEnd: null,
      showCreateForm: false,
      showViewModal: false,
      showDeleteConfirmation: false,
      isEditMode: false,
      createLoading: false,
      updateLoading: false,
      deleteLoading: false,
      newSchedule: {
        user_id: '',
        resource_id: null,
        start_time: '',
        end_time: '',
        notes: '',
        status: 'scheduled'
      },
      editedSchedule: {
        id: null,
        user_id: '',
        resource_id: null,
        start_time: '',
        end_time: '',
        notes: '',
        status: 'scheduled'
      },
      selectedSchedule: null,
      scheduleToDelete: null,
      hours: Array.from({ length: 24 }, (_, i) => i) // 0-23 hours
    };
  },
  computed: {
    resources() {
      return this.resourcesStore.resources;
    },
    schedules() {
      return this.schedulesStore.schedules;
    },
    users() {
      return this.usersStore.users;
    },
    selectedResource() {
      if (!this.selectedResourceId) return null;
      return this.resources.find(resource => resource.id === this.selectedResourceId);
    },
    weekDays() {
      if (!this.weekStart) return [];
      
      const days = [];
      const dayNames = ['Söndag', 'Måndag', 'Tisdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lördag'];
      
      for (let i = 0; i < 7; i++) {
        const date = new Date(this.weekStart);
        date.setDate(date.getDate() + i);
        days.push({
          date: date,
          name: dayNames[date.getDay()]
        });
      }
      
      return days;
    }
  },
  created() {
    this.resourcesStore = useResourcesStore();
    this.schedulesStore = useSchedulesStore();
    this.usersStore = useUsersStore();
    this.organizationsStore = useOrganizationsStore();
    
    // Set the current week
    this.setCurrentWeek();
    
    // Load initial data
    this.loadInitialData();
    
    // Check if resource is specified in query params
    const resourceId = this.$route.query.resource;
    if (resourceId) {
      this.selectedResourceId = parseInt(resourceId, 10);
    }
  },
  methods: {
    getDefaultStartDate() {
      const date = new Date();
      date.setDate(date.getDate() - date.getDay() + 1); // Start of current week (Monday)
      return this.formatDateForInput(date);
    },
    
    getDefaultEndDate() {
      const date = new Date();
      date.setDate(date.getDate() - date.getDay() + 7); // End of current week (Sunday)
      return this.formatDateForInput(date);
    },
    
    setCurrentWeek() {
      const today = new Date();
      
      // Set to Monday of current week
      const weekStart = new Date(today);
      const dayOfWeek = today.getDay();
      const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek; // If Sunday, go back 6 days, otherwise go to Monday
      weekStart.setDate(today.getDate() + diff);
      weekStart.setHours(0, 0, 0, 0);
      
      // Set to Sunday of current week
      const weekEnd = new Date(weekStart);
      weekEnd.setDate(weekStart.getDate() + 6);
      weekEnd.setHours(23, 59, 59, 999);
      
      this.weekStart = weekStart;
      this.weekEnd = weekEnd;
      
      // Update start and end date inputs
      this.startDate = this.formatDateForInput(weekStart);
      this.endDate = this.formatDateForInput(weekEnd);
    },
    
    previousWeek() {
      const newStart = new Date(this.weekStart);
      newStart.setDate(newStart.getDate() - 7);
      
      const newEnd = new Date(this.weekEnd);
      newEnd.setDate(newEnd.getDate() - 7);
      
      this.weekStart = newStart;
      this.weekEnd = newEnd;
      
      // Update start and end date inputs
      this.startDate = this.formatDateForInput(newStart);
      this.endDate = this.formatDateForInput(newEnd);
      
      // Load schedules for the new week
      this.loadSchedules();
    },
    
    nextWeek() {
      const newStart = new Date(this.weekStart);
      newStart.setDate(newStart.getDate() + 7);
      
      const newEnd = new Date(this.weekEnd);
      newEnd.setDate(newEnd.getDate() + 7);
      
      this.weekStart = newStart;
      this.weekEnd = newEnd;
      
      // Update start and end date inputs
      this.startDate = this.formatDateForInput(newStart);
      this.endDate = this.formatDateForInput(newEnd);
      
      // Load schedules for the new week
      this.loadSchedules();
    },
    
    async loadInitialData() {
      this.loading = true;
      this.error = null;
      
      try {
        // Load organizations first
        await this.organizationsStore.fetchOrganizations();
        
        // Then load resources for the first organization
        if (this.organizationsStore.organizations.length > 0) {
          const firstOrg = this.organizationsStore.organizations[0];
          await this.resourcesStore.fetchResourcesByOrganization(firstOrg.id);
          
          // If no resource is selected and we have resources, select the first one
          if (!this.selectedResourceId && this.resources.length > 0) {
            this.selectedResourceId = this.resources[0].id;
          }
          
          // Load users for the organization
          await this.usersStore.fetchUsersByOrganization(firstOrg.id);
          
          // If we have a selected resource, load its schedules
          if (this.selectedResourceId) {
            await this.loadSchedules();
          }
        }
      } catch (error) {
        console.error('Error loading initial data:', error);
        this.error = 'Det gick inte att ladda data: ' + error.message;
      } finally {
        this.loading = false;
      }
    },
    
    async loadSchedules() {
      if (!this.selectedResourceId) {
        return;
      }
      
      this.loading = true;
      this.error = null;
      
      try {
        // Parse dates from input fields
        const startDate = this.startDate;
        const endDate = this.endDate;
        
        // Update week start and end
        this.weekStart = new Date(startDate);
        this.weekEnd = new Date(endDate);
        
        // Load schedules for the selected resource and date range
        await this.schedulesStore.fetchSchedulesByResource(
          this.selectedResourceId,
          startDate,
          endDate
        );
      } catch (error) {
        console.error('Error loading schedules:', error);
        this.error = 'Det gick inte att ladda scheman: ' + error.message;
      } finally {
        this.loading = false;
      }
    },
    
    getSchedulesForDay(date) {
      return this.schedules.filter(schedule => {
        const scheduleDate = new Date(schedule.start_time);
        return (
          scheduleDate.getFullYear() === date.getFullYear() &&
          scheduleDate.getMonth() === date.getMonth() &&
          scheduleDate.getDate() === date.getDate()
        );
      });
    },
    
    getScheduleStyle(schedule) {
      const startTime = new Date(schedule.start_time);
      const endTime = new Date(schedule.end_time);
      
      // Calculate position and height based on time
      const startHour = startTime.getHours() + startTime.getMinutes() / 60;
      const endHour = endTime.getHours() + endTime.getMinutes() / 60;
      const duration = endHour - startHour;
      
      return {
        top: `${startHour * 60}px`,
        height: `${duration * 60}px`,
        backgroundColor: this.getStatusColor(schedule.status)
      };
    },
    
    getStatusColor(status) {
      switch (status) {
        case 'scheduled':
          return 'rgba(0, 115, 170, 0.7)'; // Blue
        case 'confirmed':
          return 'rgba(70, 180, 80, 0.7)'; // Green
        case 'completed':
          return 'rgba(153, 153, 153, 0.7)'; // Gray
        default:
          return 'rgba(0, 115, 170, 0.7)';
      }
    },
    
    createScheduleAt(date, hour) {
      // Create a new schedule at the clicked time
      const startTime = new Date(date);
      startTime.setHours(hour, 0, 0, 0);
      
      const endTime = new Date(startTime);
      endTime.setHours(hour + 1, 0, 0, 0);
      
      this.newSchedule = {
        user_id: '',
        resource_id: this.selectedResourceId,
        start_time: this.formatDateTimeForInput(startTime),
        end_time: this.formatDateTimeForInput(endTime),
        notes: '',
        status: 'scheduled'
      };
      
      this.showCreateForm = true;
    },
    
    async createSchedule() {
      if (!this.selectedResourceId) {
        alert('Välj en resurs först.');
        return;
      }
      
      this.createLoading = true;
      
      try {
        // Ensure resource_id is set
        this.newSchedule.resource_id = this.selectedResourceId;
        
        // Check for conflicts
        const hasConflict = this.schedulesStore.checkConflicts(
          this.newSchedule.user_id,
          this.newSchedule.start_time,
          this.newSchedule.end_time
        );
        
        if (hasConflict) {
          if (!confirm('Det finns redan ett schema för denna användare under denna tid. Vill du fortsätta ändå?')) {
            this.createLoading = false;
            return;
          }
        }
        
        await this.schedulesStore.createSchedule(this.newSchedule);
        this.showCreateForm = false;
        this.newSchedule = {
          user_id: '',
          resource_id: null,
          start_time: '',
          end_time: '',
          notes: '',
          status: 'scheduled'
        };
      } catch (error) {
        console.error('Error creating schedule:', error);
        alert('Det gick inte att skapa schemat: ' + error.message);
      } finally {
        this.createLoading = false;
      }
    },
    
    viewSchedule(schedule) {
      this.selectedSchedule = schedule;
      this.isEditMode = false;
      this.showViewModal = true;
    },
    
    editSchedule() {
      if (!this.selectedSchedule) return;
      
      this.editedSchedule = {
        id: this.selectedSchedule.id,
        user_id: this.selectedSchedule.user_id,
        resource_id: this.selectedSchedule.resource_id,
        start_time: this.formatDateTimeForInput(new Date(this.selectedSchedule.start_time)),
        end_time: this.formatDateTimeForInput(new Date(this.selectedSchedule.end_time)),
        notes: this.selectedSchedule.notes || '',
        status: this.selectedSchedule.status
      };
      
      this.isEditMode = true;
    },
    
    async updateSchedule() {
      this.updateLoading = true;
      
      try {
        // Check for conflicts
        const hasConflict = this.schedulesStore.checkConflicts(
          this.editedSchedule.user_id,
          this.editedSchedule.start_time,
          this.editedSchedule.end_time,
          this.editedSchedule.id
        );
        
        if (hasConflict) {
          if (!confirm('Det finns redan ett schema för denna användare under denna tid. Vill du fortsätta ändå?')) {
            this.updateLoading = false;
            return;
          }
        }
        
        await this.schedulesStore.updateSchedule(
          this.editedSchedule.id,
          {
            user_id: this.editedSchedule.user_id,
            start_time: this.editedSchedule.start_time,
            end_time: this.editedSchedule.end_time,
            notes: this.editedSchedule.notes,
            status: this.editedSchedule.status
          }
        );
        
        this.closeViewModal();
      } catch (error) {
        console.error('Error updating schedule:', error);
        alert('Det gick inte att uppdatera schemat: ' + error.message);
      } finally {
        this.updateLoading = false;
      }
    },
    
    confirmDelete(schedule) {
      this.scheduleToDelete = schedule;
      this.showDeleteConfirmation = true;
      this.showViewModal = false;
    },
    
    async deleteSchedule() {
      if (!this.scheduleToDelete) {
        return;
      }
      
      this.deleteLoading = true;
      
      try {
        await this.schedulesStore.deleteSchedule(this.scheduleToDelete.id);
        this.showDeleteConfirmation = false;
        this.scheduleToDelete = null;
      } catch (error) {
        console.error('Error deleting schedule:', error);
        alert('Det gick inte att ta bort schemat: ' + error.message);
      } finally {
        this.deleteLoading = false;
      }
    },
    
    closeViewModal() {
      this.showViewModal = false;
      this.isEditMode = false;
      this.selectedSchedule = null;
    },
    
    getUserName(userId) {
      const user = this.users.find(u => u.user_id === userId);
      return user ? user.user_data.display_name : 'Okänd användare';
    },
    
    getResourceName(resourceId) {
      const resource = this.resources.find(r => r.id === resourceId);
      return resource ? resource.name : 'Okänd resurs';
    },
    
    formatDateForInput(date) {
      return date.toISOString().split('T')[0];
    },
    
    formatDateTimeForInput(date) {
      return date.toISOString().slice(0, 16);
    },
    
    formatDate(date) {
      return date.toLocaleDateString('sv-SE');
    },
    
    formatTime(dateString) {
      const date = new Date(dateString);
      return date.toLocaleTimeString('sv-SE', { hour: '2-digit', minute: '2-digit' });
    },
    
    formatDateTime(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return date.toLocaleString('sv-SE');
    },
    
    formatDateRange(start, end) {
      return `${this.formatDate(start)} - ${this.formatDate(end)}`;
    },
    
    formatHour(hour) {
      return `${hour.toString().padStart(2, '0')}:00`;
    },
    
    translateStatus(status) {
      switch (status) {
        case 'scheduled':
          return 'Planerad';
        case 'confirmed':
          return 'Bekräftad';
        case 'completed':
          return 'Genomförd';
        default:
          return status;
      }
    },
    
    isToday(date) {
      const today = new Date();
      return (
        date.getDate() === today.getDate() &&
        date.getMonth() === today.getMonth() &&
        date.getFullYear() === today.getFullYear()
      );
    }
  },
  watch: {
    // If the route query changes, update the selected resource
    '$route.query.resource'(newResourceId) {
      if (newResourceId) {
        this.selectedResourceId = parseInt(newResourceId, 10);
        this.loadSchedules();
      }
    }
  }
};
</script>

<style scoped>
.schedules {
  padding: 20px 0;
}

.schedules-header {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
}

.header-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 15px;
}

.resource-selector,
.date-range-selector {
  display: flex;
  align-items: center;
  gap: 8px;
}

.resource-selector label,
.date-range-selector label {
  font-weight: bold;
}

.resource-selector select,
.date-range-selector input {
  padding: 6px 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.resource-selector select {
  min-width: 200px;
}

.select-resource-message,
.no-schedules-message {
  background-color: #f9f9f9;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  padding: 20px;
  text-align: center;
  margin: 20px 0;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.calendar-title {
  font-size: 1.2em;
  font-weight: bold;
}

.calendar-navigation {
  display: flex;
  align-items: center;
  gap: 10px;
}

.current-week {
  font-weight: bold;
}

.calendar-grid {
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  overflow: hidden;
}

.calendar-days {
  display: flex;
  border-bottom: 1px solid #e5e5e5;
}

.time-column {
  width: 60px;
  flex-shrink: 0;
  border-right: 1px solid #e5e5e5;
}

.day-column {
  flex: 1;
  min-width: 120px;
  border-right: 1px solid #e5e5e5;
  position: relative;
}

.day-column:last-child {
  border-right: none;
}

.day-header {
  padding: 10px;
  text-align: center;
  background-color: #f9f9f9;
  border-bottom: 1px solid #e5e5e5;
}

.day-name {
  font-weight: bold;
}

.day-date {
  font-size: 0.9em;
  color: #666;
}

.today .day-header {
  background-color: #e6f7ff;
}

.calendar-body {
  display: flex;
  position: relative;
  height: 1440px; /* 24 hours * 60px per hour */
}

.time-slot {
  height: 60px;
  border-bottom: 1px solid #f0f0f0;
  padding: 5px;
  box-sizing: border-box;
}

.time-column .time-slot {
  text-align: center;
  font-size: 0.8em;
  color: #666;
}

.schedule-item {
  position: absolute;
  left: 5px;
  right: 5px;
  padding: 5px;
  border-radius: 4px;
  color: white;
  font-size: 0.9em;
  overflow: hidden;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  z-index: 10;
}

.schedule-time {
  font-weight: bold;
  margin-bottom: 5px;
}

.schedule-user {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.schedule-status {
  font-size: 0.8em;
  margin-top: 5px;
}

.status-scheduled {
  border-left: 3px solid #0073aa;
}

.status-confirmed {
  border-left: 3px solid #46b450;
}

.status-completed {
  border-left: 3px solid #999;
}

.btn {
  display: inline-block;
  background-color: #0073aa;
  color: #fff;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: #005177;
}

.btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #0073aa;
}

.btn-secondary {
  background-color: #6c757d;
}

.btn-danger {
  background-color: #dc3545;
}

.btn-danger:hover {
  background-color: #bd2130;
}

.btn-small {
  padding: 4px 8px;
  font-size: 0.9em;
}

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
  background-color: #fff;
  border-radius: 4px;
  width: 500px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e5e5e5;
}

.modal-header h3 {
  margin: 0;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5em;
  cursor: pointer;
  color: #6c757d;
}

.modal-body {
  padding: 20px;
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

.form-group textarea {
  resize: vertical;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.warning {
  color: #dc3545;
  font-weight: bold;
}

.schedule-details {
  line-height: 1.6;
}

.schedule-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}
</style>
