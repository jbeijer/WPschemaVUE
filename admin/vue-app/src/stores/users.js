import { defineStore } from 'pinia';

export const useUsersStore = defineStore('users', {
  state: () => ({
    users: [],
    loading: false,
    error: null,
    currentUser: null,
    currentOrganizationId: null,
    currentUserInfo: null,
    allUsers: [] // New state to store all users from all organizations
  }),
  
  getters: {
    // Get all users
    getUsers: (state) => state.users,
    
    // Get user by ID
    getUserById: (state) => (id) => {
      return state.users.find(user => user.user_id === id);
    },
    
    // Get users by role
    getUsersByRole: (state) => (role) => {
      return state.users.filter(user => user.role === role);
    },
    
    // Get current user info
    getCurrentUserInfo: (state) => state.currentUserInfo,
    
    // Check if users are loading
    isLoading: (state) => state.loading
  },
  
  actions: {
    // Fetch users for an organization
    async fetchUsersByOrganization(organizationId) {
      this.loading = true;
      this.error = null;
      this.currentOrganizationId = organizationId;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/organizations/${organizationId}/users`, {
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
        this.users = data;
        return data;
      } catch (error) {
        console.error(`Error fetching users for organization ${organizationId}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Fetch all WordPress users
    async fetchAllWordPressUsers() {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        // Use the WordPress REST API to fetch all users
        const response = await fetch(`${wpData.rest_url}wp/v2/users?per_page=100`, {
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
        // Transform the data to match our expected format
        this.allUsers = data.map(user => ({
          id: user.id,
          user_id: user.id,
          user_data: {
            display_name: user.name,
            user_email: user.email || user.slug + '@example.com' // Email might be hidden for privacy
          },
          role: user.roles ? user.roles.join(', ') : '' // WordPress roles, check if roles exists
        }));
        
        // Also set as current users for display
        this.users = this.allUsers;
        
        return this.allUsers;
      } catch (error) {
        console.error('Error fetching all WordPress users:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Fetch current user info
    async fetchCurrentUserInfo() {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/me`, {
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
        this.currentUserInfo = data;
        return data;
      } catch (error) {
        console.error('Error fetching current user info:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Add a user to an organization
    async addUserToOrganization(organizationId, userData) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/organizations/${organizationId}/users`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify(userData)
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        this.users.push(data);
        return data;
      } catch (error) {
        console.error('Error adding user to organization:', error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Update a user's role in an organization
    async updateUserRole(organizationId, userId, role) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/organizations/${organizationId}/users/${userId}`, {
          method: 'PUT',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify({ role, organization: organizationId })
        });
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update the user in the list
        const index = this.users.findIndex(user => user.user_id === userId);
          if (index !== -1) {
            this.users[index] = { ...this.users[index], ...data, organization: organizationId };
          }
        
          if (this.currentUser && this.currentUser.user_id === userId) {
            this.currentUser = { ...this.currentUser, ...data, organization: organizationId };
          }
        
        return data;
      } catch (error) {
        console.error(`Error updating user role for user ${userId}:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Remove a user from an organization
    async removeUserFromOrganization(organizationId, userId) {
      this.loading = true;
      this.error = null;
      
      try {
        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/organizations/${organizationId}/users/${userId}`, {
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
        
        // Remove the user from the list
        this.users = this.users.filter(user => user.user_id !== userId);
        
        if (this.currentUser && this.currentUser.user_id === userId) {
          this.currentUser = null;
        }
        
        return true;
      } catch (error) {
        console.error(`Error removing user ${userId} from organization:`, error);
        this.error = error.message;
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    // Set current user
    setCurrentUser(user) {
      this.currentUser = user;
    },
    
    // Clear current user
    clearCurrentUser() {
      this.currentUser = null;
    },
    
    // Set current organization ID
    setCurrentOrganizationId(organizationId) {
      this.currentOrganizationId = organizationId;
    }
  }
});
