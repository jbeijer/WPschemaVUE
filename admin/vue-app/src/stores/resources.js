import { defineStore } from 'pinia';

export const useResourcesStore = defineStore('resources', {
  state: () => ({
    resources: [],
    loading: false,
    error: null,
    currentResource: null,
    currentOrganizationId: null
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
    isLoading: (state) => state.loading
  },
  
  actions: {
    // Fetch resources for an organization
    async fetchResourcesByOrganization(organizationId) {
      this.loading = true;
      this.error = null;
      this.currentOrganizationId = organizationId;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/organizations/${organizationId}/resources`, {
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
        this.resources = data;
        return data;
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
        const response = await fetch(`${wpData.rest_url}/organizations/${organizationId}/resources`, {
          method: 'POST',
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
        this.resources.push(data);
        return data;
      } catch (error) {
        console.error('Error creating resource:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Update a resource
    async updateResource(id, resourceData) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/resources/${id}`, {
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
        
        // Update the resource in the list
        const index = this.resources.findIndex(resource => resource.id === id);
        if (index !== -1) {
          this.resources[index] = data;
        }
        
        if (this.currentResource && this.currentResource.id === id) {
          this.currentResource = data;
        }
        
        return data;
      } catch (error) {
        console.error(`Error updating resource ${id}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Delete a resource
    async deleteResource(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/resources/${id}`, {
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
        
        // Remove the resource from the list
        this.resources = this.resources.filter(resource => resource.id !== id);
        
        if (this.currentResource && this.currentResource.id === id) {
          this.currentResource = null;
        }
        
        return true;
      } catch (error) {
        console.error(`Error deleting resource ${id}:`, error);
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
    }
  }
});
