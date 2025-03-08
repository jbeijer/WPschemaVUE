import { defineStore } from 'pinia';

export const useSchedulesStore = defineStore('schedules', {
  state: () => ({
    schedules: [],
    loading: false,
    error: null,
    currentSchedule: null,
    currentResourceId: null,
    dateRange: {
      start: null,
      end: null
    }
  }),
  
  getters: {
    // Get all schedules
    getSchedules: (state) => state.schedules,
    
    // Get schedule by ID
    getScheduleById: (state) => (id) => {
      return state.schedules.find(schedule => schedule.id === id);
    },
    
    // Get schedules for a specific resource
    getSchedulesByResource: (state) => (resourceId) => {
      return state.schedules.filter(schedule => schedule.resource_id === resourceId);
    },
    
    // Get schedules for a specific user
    getSchedulesByUser: (state) => (userId) => {
      return state.schedules.filter(schedule => schedule.user_id === userId);
    },
    
    // Get schedules for a specific date range
    getSchedulesByDateRange: (state) => (startDate, endDate) => {
      return state.schedules.filter(schedule => {
        const scheduleStart = new Date(schedule.start_time);
        const scheduleEnd = new Date(schedule.end_time);
        const rangeStart = new Date(startDate);
        const rangeEnd = new Date(endDate);
        
        // Check if the schedule overlaps with the date range
        return (
          (scheduleStart >= rangeStart && scheduleStart <= rangeEnd) ||
          (scheduleEnd >= rangeStart && scheduleEnd <= rangeEnd) ||
          (scheduleStart <= rangeStart && scheduleEnd >= rangeEnd)
        );
      });
    },
    
    // Check if schedules are loading
    isLoading: (state) => state.loading
  },
  
  actions: {
    // Fetch schedules for a resource
    async fetchSchedulesByResource(resourceId, startDate, endDate) {
      this.loading = true;
      this.error = null;
      this.currentResourceId = resourceId;
      this.dateRange = { start: startDate, end: endDate };
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(
          `${wpData.rest_url}/schedules/resource/${resourceId}?start_date=${startDate}&end_date=${endDate}`, 
          {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': wpData.nonce
            }
          }
        );
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        this.schedules = data;
        return data;
      } catch (error) {
        console.error(`Error fetching schedules for resource ${resourceId}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Fetch my schedules
    async fetchMySchedules(startDate, endDate) {
      this.loading = true;
      this.error = null;
      this.dateRange = { start: startDate, end: endDate };
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(
          `${wpData.rest_url}/schedules/my-schedule?start_date=${startDate}&end_date=${endDate}`, 
          {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': wpData.nonce
            }
          }
        );
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        this.schedules = data;
        return data;
      } catch (error) {
        console.error('Error fetching my schedules:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Create a new schedule
    async createSchedule(scheduleData) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/schedules`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify(scheduleData)
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        this.schedules.push(data);
        return data;
      } catch (error) {
        console.error('Error creating schedule:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Update a schedule
    async updateSchedule(id, scheduleData) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/schedules/${id}`, {
          method: 'PUT',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify(scheduleData)
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update the schedule in the list
        const index = this.schedules.findIndex(schedule => schedule.id === id);
        if (index !== -1) {
          this.schedules[index] = data;
        }
        
        if (this.currentSchedule && this.currentSchedule.id === id) {
          this.currentSchedule = data;
        }
        
        return data;
      } catch (error) {
        console.error(`Error updating schedule ${id}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Delete a schedule
    async deleteSchedule(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/schedules/${id}`, {
          method: 'DELETE',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          }
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        // Remove the schedule from the list
        this.schedules = this.schedules.filter(schedule => schedule.id !== id);
        
        if (this.currentSchedule && this.currentSchedule.id === id) {
          this.currentSchedule = null;
        }
        
        return true;
      } catch (error) {
        console.error(`Error deleting schedule ${id}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Set current schedule
    setCurrentSchedule(schedule) {
      this.currentSchedule = schedule;
    },
    
    // Clear current schedule
    clearCurrentSchedule() {
      this.currentSchedule = null;
    },
    
    // Set date range
    setDateRange(startDate, endDate) {
      this.dateRange = { start: startDate, end: endDate };
    },
    
    // Check for schedule conflicts
    checkConflicts(userId, startTime, endTime, excludeScheduleId = null) {
      return this.schedules.some(schedule => {
        // Skip the current schedule if we're updating
        if (excludeScheduleId && schedule.id === excludeScheduleId) {
          return false;
        }
        
        // Only check schedules for the same user
        if (schedule.user_id !== userId) {
          return false;
        }
        
        const scheduleStart = new Date(schedule.start_time);
        const scheduleEnd = new Date(schedule.end_time);
        const newStart = new Date(startTime);
        const newEnd = new Date(endTime);
        
        // Check if the schedules overlap
        return (
          (newStart >= scheduleStart && newStart < scheduleEnd) ||
          (newEnd > scheduleStart && newEnd <= scheduleEnd) ||
          (newStart <= scheduleStart && newEnd >= scheduleEnd)
        );
      });
    }
  }
});
