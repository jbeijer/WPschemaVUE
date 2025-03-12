import { defineStore } from 'pinia';

export const useUsersStore = defineStore('users', {
  state: () => ({
    users: [],
    loading: false,
    error: null,
    currentUser: null,
    currentOrganizationId: null,
    currentUserInfo: null,
    allUsers: [], // New state to store all users from all organizations
    userOrganizations: {} // New state to store user-organization relationships
  }),
  
  getters: {
    // Get all users
    getUsers: (state) => state.users,
    
    // Get user by ID
    getUserById: (state) => (id) => {
      return state.users.find(user => user.user_id === id || user.id === id);
    },
    
    // Get user's role in organization
    getUserInOrganization: (state) => (userId, organizationId) => {
      const key = `${userId}-${organizationId}`;
      return state.userOrganizations[key] || null;
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
        
        // Hämta alla organisationer för varje användare
        const usersWithOrgs = await Promise.all(data.map(async (user) => {
          const orgsResponse = await fetch(`${wpData.rest_url}schedule/v1/users/${user.user_id}/organizations`, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              'X-WP-Nonce': wpData.nonce
            }
          });
          
          let organizations = [];
          if (orgsResponse.ok) {
            const orgsData = await orgsResponse.json();
            organizations = orgsData.map(org => org.id);
          }
          
          return {
            ...user,
            organization_id: organizationId,
            organizations: organizations
          };
        }));
        
        this.users = usersWithOrgs;
        
        // Update userOrganizations state
        usersWithOrgs.forEach(user => {
          const key = `${user.user_id}-${organizationId}`;
          this.userOrganizations[key] = {
            role: user.role,
            organization_id: organizationId
          };
        });
        
        return usersWithOrgs;
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
        
        // Hämta alla WordPress-användare
        const usersResponse = await fetch(`${wpData.rest_url}wp/v2/users?per_page=100`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          }
        });
        
        if (!usersResponse.ok) {
          throw new Error(`HTTP error! status: ${usersResponse.status}`);
        }
        
        const usersData = await usersResponse.json();
        
        // Hämta alla organisationer
        const orgsResponse = await fetch(`${wpData.rest_url}schedule/v1/organizations`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          }
        });
        
        if (!orgsResponse.ok) {
          throw new Error(`HTTP error! status: ${orgsResponse.status}`);
        }
        
        const organizations = await orgsResponse.json();
        
        // Hämta användarroller för varje organisation
        const userPromises = organizations.map(async (org) => {
          try {
            const orgUsersResponse = await fetch(`${wpData.rest_url}schedule/v1/organizations/${org.id}/users`, {
              method: 'GET',
              credentials: 'same-origin',
              headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wpData.nonce
              }
            });
            
            if (orgUsersResponse.ok) {
              const orgUsers = await orgUsersResponse.json();
              return orgUsers.map(user => ({
                ...user,
                organization_id: org.id
              }));
            }
            return [];
          } catch (error) {
            console.error(`Error fetching users for organization ${org.id}:`, error);
            return [];
          }
        });
        
        const orgUsersArrays = await Promise.all(userPromises);
        
        // Kombinera all användarinformation
        const usersWithRoles = usersData.map(user => {
          const userRoles = [];
          const userOrgs = [];
          const userOrgRoles = {};
          
          // Hitta användarens roller och organisationer
          orgUsersArrays.forEach(orgUsers => {
            const orgUser = orgUsers.find(ou => ou.user_id === user.id);
            if (orgUser) {
              userRoles.push(orgUser.role);
              userOrgs.push(orgUser.organization_id);
              userOrgRoles[orgUser.organization_id] = orgUser.role;
            }
          });
          
          return {
            id: user.id,
            user_id: user.id,
            user_data: {
              display_name: user.name,
              user_email: user.email || user.slug + '@example.com'
            },
            role: userRoles.join(', ') || '',
            organization_id: userOrgs[0] || null,
            organizations: userOrgs,
            organization_roles: userOrgRoles
          };
        });
        
        this.users = usersWithRoles;
        
        // Uppdatera userOrganizations state
        this.userOrganizations = {};
        orgUsersArrays.forEach(orgUsers => {
          orgUsers.forEach(user => {
            const key = `${user.user_id}-${user.organization_id}`;
            this.userOrganizations[key] = {
              role: user.role,
              organization_id: user.organization_id
            };
          });
        });
        
        return this.users;
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
        // Validera rollen
        const validRoles = ['base', 'schemalaggare', 'schemaanmain'];
        if (!validRoles.includes(role)) {
          throw new Error(`Ogiltig roll. Giltiga roller är: ${validRoles.join(', ')}`);
        }

        const wpData = window.wpScheduleData || {};
        const response = await fetch(`${wpData.rest_url}schedule/v1/organizations/${organizationId}/users/${userId}`, {
          method: 'PUT',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpData.nonce
          },
          body: JSON.stringify({ role })
        });
        
        if (!response.ok) {
          const errorData = await response.json();
          throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Update user in the list and userOrganizations state
        const key = `${userId}-${organizationId}`;
        this.userOrganizations[key] = {
          role: role,
          organization_id: organizationId
        };
        
        const index = this.users.findIndex(user => (user.user_id === userId || user.id === userId));
        if (index !== -1) {
          this.users[index] = {
            ...this.users[index],
            role: role,
            organization_id: organizationId
          };
        }
        
        // Update currentUser if it matches
        if (this.currentUser && (this.currentUser.user_id === userId || this.currentUser.id === userId)) {
          this.currentUser = {
            ...this.currentUser,
            role: role,
            organization_id: organizationId
          };
        }
        
        return data;
      } catch (error) {
        console.error(`Fel vid uppdatering av användarroll för användare ${userId}:`, error);
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
