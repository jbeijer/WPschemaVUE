import { defineStore } from 'pinia';

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
        
        // Normalisera och validera färgkoden
        const color = resourceData.color ? resourceData.color.toLowerCase() : '#3788d8';
        if (!color.match(/^#[0-9a-f]{6}$/i)) {
          throw new Error('Färgvärdet måste vara i formatet #RRGGBB (t.ex. #3788d8)');
        }
        
        const formattedData = {
          name: resourceData.name,
          description: resourceData.description || '',
          color: color
        };
        
        console.log('Creating resource with data:', {
          organizationId,
          formattedData,
          wpData
        });
        
        const url = `${wpData.rest_url}schedule/v1/organizations/${organizationId}/resources`;
        console.log('Request URL:', url);
        
        // Logga request body
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
            console.error('Parameter errors:', paramErrors);
            const errorMessages = Object.entries(paramErrors)
              .map(([param, details]) => `${param}: ${details}`)
              .join(', ');
            throw new Error(`Valideringsfel: ${errorMessages}`);
          }
          
          throw new Error(errorData?.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API Response:', data);
        
        // Hantera både array-svar och success/data-svar
        if (Array.isArray(data)) {
          const newResource = data[0]; // Ta första resursen från arrayen
          this.resources.push(newResource);
          return newResource;
        } else if (data && data.success) {
          this.resources.push(data.data);
          return data.data;
        } else if (data && data.data) {
          this.resources.push(data.data);
          return data.data;
        } else {
          throw new Error(data?.error?.message || 'Ett okänt fel uppstod vid skapande av resurs');
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
        const response = await fetch(`${wpData.rest_url}schedule/v1/resources/${resourceId}`, {
          method: 'PUT',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify(resourceData)
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
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
        const response = await fetch(`${wpData.rest_url}schedule/v1/resources/${resourceId}/availability`, {
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
        if (data.success) {
          this.availabilities[resourceId] = data.data;
          return data.data;
        } else {
          throw new Error(data.error?.message || 'Ett okänt fel uppstod vid hämtning av tillgänglighet');
        }
      } catch (error) {
        console.error(`Error fetching availability for resource ${resourceId}:`, error);
        throw error;
      }
    },

    // Save availability for a resource
    async saveAvailability(resourceId, availabilityData) {
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/resources/${resourceId}/availability`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify(availabilityData)
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success) {
          this.availabilities[resourceId] = data.data;
          return data.data;
        } else {
          throw new Error(data.error?.message || 'Ett okänt fel uppstod vid sparande av tillgänglighet');
        }
      } catch (error) {
        console.error(`Error saving availability for resource ${resourceId}:`, error);
        throw error;
      }
    }
  }
});
