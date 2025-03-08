import { defineStore } from 'pinia';

export const useOrganizationsStore = defineStore('organizations', {
  state: () => ({
    organizations: [],
    loading: false,
    error: null,
    currentOrganization: null
  }),
  
  getters: {
    // Get all organizations
    getOrganizations: (state) => state.organizations,
    
    // Get organization by ID
    getOrganizationById: (state) => (id) => {
      return state.organizations.find(org => org.id === id);
    },
    
    // Get organizations as a tree structure
    getOrganizationTree: (state) => {
      // Helper function to build tree
      const buildTree = (items, parentId = null) => {
        return items
          .filter(item => item.parent_id === parentId)
          .map(item => ({
            ...item,
            children: buildTree(items, item.id)
          }));
      };
      
      return buildTree([...state.organizations]);
    },
    
    // Get organizations as flat list with indentation level
    getOrganizationFlatList: (state) => {
      // Helper function to flatten tree with level
      const flattenWithLevel = (items, parentId = null, level = 0) => {
        let result = [];
        
        items
          .filter(item => item.parent_id === parentId)
          .forEach(item => {
            result.push({
              ...item,
              level
            });
            
            result = result.concat(
              flattenWithLevel(items, item.id, level + 1)
            );
          });
        
        return result;
      };
      
      return flattenWithLevel([...state.organizations]);
    },
    
    // Check if organizations are loading
    isLoading: (state) => state.loading
  },
  
  actions: {
    // Fetch all organizations
    async fetchOrganizations() {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/organizations`, {
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
        this.organizations = data;
      } catch (error) {
        console.error('Error fetching organizations:', error);
        this.error = error.message;
      } finally {
        this.loading = false;
      }
    },
    
    // Fetch a single organization
    async fetchOrganization(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/organizations/${id}`, {
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
        
        // Update the organization in the list
        const index = this.organizations.findIndex(org => org.id === id);
        if (index !== -1) {
          this.organizations[index] = data;
        } else {
          this.organizations.push(data);
        }
        
        this.currentOrganization = data;
        return data;
      } catch (error) {
        console.error(`Error fetching organization ${id}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Create a new organization
    async createOrganization(organizationData) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/organizations`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify(organizationData)
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        this.organizations.push(data);
        return data;
      } catch (error) {
        console.error('Error creating organization:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Update an organization
    async updateOrganization(id, organizationData) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/organizations/${id}`, {
          method: 'PUT',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify(organizationData)
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update the organization in the list
        const index = this.organizations.findIndex(org => org.id === id);
        if (index !== -1) {
          this.organizations[index] = data;
        }
        
        if (this.currentOrganization && this.currentOrganization.id === id) {
          this.currentOrganization = data;
        }
        
        return data;
      } catch (error) {
        console.error(`Error updating organization ${id}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Delete an organization
    async deleteOrganization(id) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}/organizations/${id}`, {
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
        
        // Remove the organization from the list
        this.organizations = this.organizations.filter(org => org.id !== id);
        
        if (this.currentOrganization && this.currentOrganization.id === id) {
          this.currentOrganization = null;
        }
        
        return true;
      } catch (error) {
        console.error(`Error deleting organization ${id}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Set current organization
    setCurrentOrganization(organization) {
      this.currentOrganization = organization;
    },
    
    // Clear current organization
    clearCurrentOrganization() {
      this.currentOrganization = null;
    }
  }
});
