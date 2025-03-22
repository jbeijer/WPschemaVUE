import { defineStore } from 'pinia';
// Vi behöver fortfarande färgfunktionerna för andra delar av koden
import { normalizeColor, isValidColor } from '@/utils/colorUtils';

export const useResourcesStore = defineStore('resources', {
  state: () => ({
    resources: [],
    loading: false,
    error: null,
    currentResource: null,
    currentOrganizationId: null,
    availabilities: {}
  }),
  
  getters: {
    // Get all resources
    getResources: (state) => state.resources,
    
    // Get resource by ID
    getResourceById: (state) => (id) => {
      return state.resources.find(resource => resource.id === id);
    },
    
    // Get resources for a specific organization
    getResourcesByOrganization: (state) => (organizationId) => {
      return state.resources.filter(resource => resource.organization_id === organizationId);
    },
    
    // Check if resources are loading
    isLoading: (state) => state.loading,

    // Get availability for a resource
    getAvailability: (state) => (resourceId) => {
      return state.availabilities[resourceId];
    }
  },
  
  actions: {
    // Fetch resources for an organization
    async fetchResourcesByOrganization(organizationId) {
      this.loading = true;
      this.error = null;
      this.currentOrganizationId = organizationId;
      
      try {
        const wpData = window.wpScheduleData || {};
        console.log('Fetching resources with wpData:', wpData);
        
        const url = `${wpData.rest_url}schedule/v1/organizations/${organizationId}/resources`;
        console.log('Request URL:', url);
        
        const response = await fetch(url, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          }
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        
        // Hantera både array-svar och success/data-svar
        if (Array.isArray(data)) {
          this.resources = data;
          return data;
        } else if (data && data.success) {
          this.resources = data.data;
          return data.data;
        } else {
          const errorMessage = data?.error?.message || 'Ett okänt fel uppstod vid hämtning av resurser';
          console.error('API Error:', data);
          throw new Error(errorMessage);
        }
      } catch (error) {
        console.error(`Error fetching resources for organization ${organizationId}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Fetch a single resource
    async fetchResource(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/resources/${id}`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          }
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update the resource in the list
        const index = this.resources.findIndex(resource => resource.id === id);
        if (index !== -1) {
          this.resources[index] = data;
        } else {
          this.resources.push(data);
        }
        
        this.currentResource = data;
        return data;
      } catch (error) {
        console.error(`Error fetching resource ${id}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Create a new resource
    async createResource(organizationId, resourceData) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        
        // Create formatted data with all necessary fields
        const formattedData = {
          name: resourceData.name,
          description: resourceData.description || '',
          is_24_7: resourceData.is_24_7
        };
        
        // Add time constraints if not 24/7
        if (!resourceData.is_24_7) {
          // Ensure time values are in the correct format (HH:MM)
          // The backend expects times in format HH:MM or HH:MM:SS
          // Make sure we're sending exactly in that format
          if (resourceData.start_time) {
            // Extract just the HH:MM part to ensure proper format
            let startTime = resourceData.start_time;
            // If the time contains more than just HH:MM, trim it
            if (startTime.length > 5) {
              startTime = startTime.substring(0, 5);
            }
            
            // Ensure hours and minutes are properly formatted
            const startTimeParts = startTime.split(':');
            if (startTimeParts.length >= 2) {
              const hours = startTimeParts[0].padStart(2, '0');
              const minutes = startTimeParts[1].padStart(2, '0');
              formattedData.start_time = `${hours}:${minutes}`;
              console.log('Formatted start_time:', formattedData.start_time);
            } else {
              console.error('Invalid start_time format:', startTime);
            }
          }
          
          if (resourceData.end_time) {
            // Extract just the HH:MM part to ensure proper format
            let endTime = resourceData.end_time;
            // If the time contains more than just HH:MM, trim it
            if (endTime.length > 5) {
              endTime = endTime.substring(0, 5);
            }
            
            // Ensure hours and minutes are properly formatted
            const endTimeParts = endTime.split(':');
            if (endTimeParts.length >= 2) {
              const hours = endTimeParts[0].padStart(2, '0');
              const minutes = endTimeParts[1].padStart(2, '0');
              formattedData.end_time = `${hours}:${minutes}`;
              console.log('Formatted end_time:', formattedData.end_time);
            } else {
              console.error('Invalid end_time format:', endTime);
            }
          }
        }
        
        console.log('Creating resource with data:', {
          organizationId,
          formattedData,
          wpData
        });
        
        const url = `${wpData.rest_url}schedule/v1/organizations/${organizationId}/resources`;
        console.log('Request URL:', url);
        
        // Log request body
        const requestBody = JSON.stringify(formattedData);
        console.log('Request body:', requestBody);
        
        const response = await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: requestBody
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
          const errorData = await response.json().catch(() => null);
          console.error('Error response:', errorData);
          
          // Hantera specifika felmeddelanden från API:et
          if (errorData?.code === 'rest_invalid_param') {
            const paramErrors = errorData.data?.params || {};
            console.log('Parameter errors:', paramErrors);
            
            // Skapa ett mer specifikt felmeddelande
            const errorMessages = Object.entries(paramErrors).map(([param, message]) => `${param}: ${message}`);
            throw new Error(`Valideringsfel: ${errorMessages.join(', ')}`);
          }
          
          throw new Error(errorData?.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Hantera både array-svar och success/data-svar
        if (data.success) {
          // Lägg till den nya resursen i listan
          this.resources.push(data.data);
          return data.data;
        } else {
          throw new Error(data.error?.message || 'Ett okänt fel uppstod vid skapande av resurs');
        }
      } catch (error) {
        console.error('Error creating resource:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Update a resource
    async updateResource(resourceId, resourceData) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        
        // Uppdatera data utan färg
        const formattedData = {
          name: resourceData.name,
          description: resourceData.description || ''
          // Skickar inte med color, så backend använder standardfärg eller behåller befintlig
        };
        
        console.log('Updating resource with data:', {
          resourceId,
          formattedData,
          wpData
        });
        
        const url = `${wpData.rest_url}schedule/v1/resources/${resourceId}`;
        console.log('Request URL:', url);
        
        // Logga request body
        const requestBody = JSON.stringify(formattedData);
        console.log('Request body:', requestBody);
        
        const response = await fetch(url, {
          method: 'PUT',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: requestBody
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
          const errorData = await response.json().catch(() => null);
          console.error('Error response:', errorData);
          
          // Hantera specifika felmeddelanden från API:et
          if (errorData?.code === 'rest_invalid_param') {
            const paramErrors = errorData.data?.params || {};
            console.log('Parameter errors:', paramErrors);
            
            // Skapa ett mer specifikt felmeddelande
            const errorMessages = Object.entries(paramErrors).map(([param, message]) => `${param}: ${message}`);
            throw new Error(`Valideringsfel: ${errorMessages.join(', ')}`);
          }
          
          throw new Error(errorData?.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success) {
          const index = this.resources.findIndex(r => r.id === resourceId);
          if (index !== -1) {
            this.resources[index] = data.data;
          }
          return data.data;
        } else {
          throw new Error(data.error?.message || 'Ett okänt fel uppstod vid uppdatering av resurs');
        }
      } catch (error) {
        console.error('Error updating resource:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Delete a resource
    async deleteResource(resourceId) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/resources/${resourceId}`, {
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
        
        const data = await response.json();
        if (data.success) {
          this.resources = this.resources.filter(r => r.id !== resourceId);
          return true;
        } else {
          throw new Error(data.error?.message || 'Ett okänt fel uppstod vid borttagning av resurs');
        }
      } catch (error) {
        console.error('Error deleting resource:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Set current resource
    setCurrentResource(resource) {
      this.currentResource = resource;
    },
    
    // Clear current resource
    clearCurrentResource() {
      this.currentResource = null;
    },
    
    // Set current organization ID
    setCurrentOrganizationId(organizationId) {
      this.currentOrganizationId = organizationId;
    },

    // Fetch availability for a resource
    async fetchAvailability(resourceId) {
      try {
        const wpData = window.wpScheduleData || {};
        
        // Get the organization ID for this resource
        const resource = this.resources.find(r => r.id === resourceId);
        if (!resource) {
          throw new Error(`Resource with ID ${resourceId} not found`);
        }
        
        const organizationId = resource.organization_id;
        const url = `${wpData.rest_url}schedule/v1/organizations/${organizationId}/resources/${resourceId}/availability`;
        
        console.log('Fetching availability with URL:', url);
        
        try {
          const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': wpData.nonce
            }
          });
          
          if (!response.ok) {
            // If API endpoint doesn't exist, use fallback data
            if (response.status === 404) {
              console.log('API endpoint not found. Using local availability data.');
              return this.getLocalAvailability(resourceId);
            }
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          const data = await response.json();
          if (data.success) {
            this.availabilities[resourceId] = data.data;
            // Also save to local storage as a backup
            this.saveLocalAvailability(resourceId, data.data);
            return data.data;
          } else {
            throw new Error(data.error?.message || 'Ett okänt fel uppstod vid hämtning av tillgänglighet');
          }
        } catch (error) {
          if (error.message.includes('404')) {
            console.log('API endpoint not found. Using local availability data.');
            return this.getLocalAvailability(resourceId);
          }
          throw error;
        }
      } catch (error) {
        console.error(`Error fetching availability for resource ${resourceId}:`, error);
        throw error;
      }
    },
    
    // Get availability from local storage
    getLocalAvailability(resourceId) {
      // Check if we already have it in state
      if (this.availabilities[resourceId]) {
        return this.availabilities[resourceId];
      }
      
      // Try to get from localStorage
      try {
        const key = `resource_availability_${resourceId}`;
        const stored = localStorage.getItem(key);
        
        if (stored) {
          const availability = JSON.parse(stored);
          this.availabilities[resourceId] = availability;
          return availability;
        }
      } catch (e) {
        console.error('Error reading from localStorage:', e);
      }
      
      // Return default availability if nothing is found
      const defaultAvailability = {
        is24_7: false,
        weeklySchedule: Array(7).fill().map(() => ({
          enabled: false,
          startTime: '09:00',
          endTime: '17:00'
        })),
        specialDates: []
      };
      
      this.availabilities[resourceId] = defaultAvailability;
      return defaultAvailability;
    },
    
    // Save availability to local storage
    saveLocalAvailability(resourceId, availability) {
      try {
        const key = `resource_availability_${resourceId}`;
        localStorage.setItem(key, JSON.stringify(availability));
      } catch (e) {
        console.error('Error saving to localStorage:', e);
      }
    },

    // Save availability for a resource
    async saveAvailability(availabilityData) {
      try {
        const resourceId = availabilityData.resourceId;
        const wpData = window.wpScheduleData || {};
        
        // Get the organization ID for this resource
        const resource = this.resources.find(r => r.id === resourceId);
        if (!resource) {
          throw new Error(`Resource with ID ${resourceId} not found`);
        }
        
        const organizationId = resource.organization_id;
        const url = `${wpData.rest_url}schedule/v1/organizations/${organizationId}/resources/${resourceId}/availability`;
        
        console.log('Saving availability with URL:', url);
        
        try {
          const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': wpData.nonce
            },
            body: JSON.stringify(availabilityData)
          });
          
          if (!response.ok) {
            // If API endpoint doesn't exist, save to local storage instead
            if (response.status === 404) {
              console.log('API endpoint not found. Saving availability to local storage.');
              // Save availability data to localStorage
              this.availabilities[resourceId] = {
                is24_7: availabilityData.is24_7,
                weeklySchedule: availabilityData.weeklySchedule,
                specialDates: availabilityData.specialDates
              };
              this.saveLocalAvailability(resourceId, this.availabilities[resourceId]);
              return { success: true };
            }
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          const data = await response.json();
          if (data.success) {
            // Update availability in store
            this.availabilities[resourceId] = availabilityData;
            return data.data;
          } else {
            throw new Error(data.error?.message || 'Ett okänt fel uppstod vid sparande av tillgänglighet');
          }
        } catch (error) {
          if (error.message.includes('404')) {
            console.log('API endpoint not found. Saving availability to local storage.');
            // Save availability data to localStorage
            this.availabilities[resourceId] = {
              is24_7: availabilityData.is24_7,
              weeklySchedule: availabilityData.weeklySchedule,
              specialDates: availabilityData.specialDates
            };
            this.saveLocalAvailability(resourceId, this.availabilities[resourceId]);
            return { success: true };
          }
          throw error;
        }
      } catch (error) {
        console.error(`Error saving availability for resource ${availabilityData.resourceId}:`, error);
        throw error;
      }
    }
  }
});
