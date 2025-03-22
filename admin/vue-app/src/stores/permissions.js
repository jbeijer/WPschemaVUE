/**
 * Permissions store for handling user role-based permissions
 */
import { defineStore } from 'pinia';

export const usePermissionsStore = defineStore('permissions', {
  state: () => ({
    // Map of organization_id -> role
    userRoles: {},
  }),
  
  actions: {
    /**
     * Set the user's role for a specific organization
     * @param {number|string} organizationId - The organization ID
     * @param {string} role - Role (bas, schemaläggare, admin)
     */
    setUserRole(organizationId, role) {
      if (organizationId) {
        this.userRoles[organizationId] = role;
      }
    },
    
    /**
     * Initialize roles for all organizations
     * @param {Array} organizations - List of organizations with role information
     */
    initRoles(organizations = []) {
      organizations.forEach(org => {
        if (org.id && org.user_role) {
          this.userRoles[org.id] = org.user_role;
        }
      });
    },
    
    /**
     * Clear all role information
     */
    clearRoles() {
      this.userRoles = {};
    }
  },
  
  getters: {
    /**
     * Get the user's role for a specific organization
     * @param {number|string} organizationId - The organization ID
     * @returns {string} The user's role or 'bas' as default
     */
    getUserRole: (state) => (organizationId) => {
      return state.userRoles[organizationId] || 'bas';
    },
    
    /**
     * Check if the user has a specific role or higher for an organization
     * @param {number|string} organizationId - The organization ID
     * @param {string} role - Required role (bas, schemaläggare, admin)
     * @returns {boolean} True if user has the required role or higher
     */
    hasRole: (state) => (organizationId, role) => {
      const userRole = state.userRoles[organizationId] || 'bas';
      
      const roleLevels = {
        'bas': 1,
        'schemaläggare': 2,
        'admin': 3
      };
      
      return roleLevels[userRole] >= roleLevels[role];
    },
    
    /**
     * Check if the user is an admin for a specific organization
     * @param {number|string} organizationId - The organization ID
     * @returns {boolean} True if user is an admin
     */
    isAdmin: (state) => (organizationId) => {
      return state.userRoles[organizationId] === 'admin';
    },
    
    /**
     * Check if the user is a scheduler or admin for a specific organization
     * @param {number|string} organizationId - The organization ID
     * @returns {boolean} True if user is a scheduler or admin
     */
    isScheduler: (state) => (organizationId) => {
      const role = state.userRoles[organizationId];
      return role === 'schemaläggare' || role === 'admin';
    },
    
    /**
     * Check if the user can manage resources in an organization
     * @param {number|string} organizationId - The organization ID
     * @returns {boolean} True if user can manage resources
     */
    canManageResources: (state) => (organizationId) => {
      // Only schedulers and admins can manage resources
      const role = state.userRoles[organizationId];
      return role === 'schemaläggare' || role === 'admin';
    },
    
    /**
     * Check if the user can manage schedules in an organization
     * @param {number|string} organizationId - The organization ID
     * @returns {boolean} True if user can manage schedules
     */
    canManageSchedules: (state) => (organizationId) => {
      // Only schedulers and admins can manage schedules
      const role = state.userRoles[organizationId];
      return role === 'schemaläggare' || role === 'admin';
    },
    
    /**
     * Check if the user can manage organizations
     * @param {number|string} organizationId - The organization ID
     * @returns {boolean} True if user can manage organizations
     */
    canManageOrganizations: (state) => (organizationId) => {
      // Only admins can manage organizations
      return state.userRoles[organizationId] === 'admin';
    },
    
    /**
     * Check if the user can lock schedules in an organization
     * @param {number|string} organizationId - The organization ID
     * @returns {boolean} True if user can lock schedules
     */
    canLockSchedules: (state) => (organizationId) => {
      // Only admins can lock schedules
      return state.userRoles[organizationId] === 'admin';
    }
  }
});

/**
 * Helper functions for checking permissions in components
 */

/**
 * Check if the user can manage resources in an organization
 * @param {Object} store - Vuex or Pinia store
 * @param {number|string} organizationId - The organization ID
 * @returns {boolean} True if user can manage resources
 */
export function canManageResources(store, organizationId) {
  // Only schedulers and admins can manage resources
  return store.getters['permissions/isScheduler'](organizationId);
}

/**
 * Check if the user can manage schedules in an organization
 * @param {Object} store - Vuex or Pinia store
 * @param {number|string} organizationId - The organization ID
 * @returns {boolean} True if user can manage schedules
 */
export function canManageSchedules(store, organizationId) {
  // Only schedulers and admins can manage schedules
  return store.getters['permissions/isScheduler'](organizationId);
}

/**
 * Check if the user can manage organizations
 * @param {Object} store - Vuex or Pinia store
 * @param {number|string} organizationId - The organization ID
 * @returns {boolean} True if user can manage organizations
 */
export function canManageOrganizations(store, organizationId) {
  // Only admins can manage organizations
  return store.getters['permissions/isAdmin'](organizationId);
}

/**
 * Check if the user can lock schedules in an organization
 * @param {Object} store - Vuex or Pinia store
 * @param {number|string} organizationId - The organization ID
 * @returns {boolean} True if user can lock schedules
 */
export function canLockSchedules(store, organizationId) {
  // Only admins can lock schedules
  return store.getters['permissions/isAdmin'](organizationId);
}

/**
 * Check if the user can edit a specific schedule
 * @param {Object} store - Vuex or Pinia store
 * @param {Object} schedule - The schedule object
 * @returns {boolean} True if user can edit the schedule
 */
export function canEditSchedule(store, schedule) {
  // Users can edit their own schedules
  // Schedulers and admins can edit any schedule in their organization
  return (
    schedule.user_id === store.state.user.id ||
    store.getters['permissions/isScheduler'](schedule.organization_id)
  );
}

/**
 * Check if the user can delete a specific schedule
 * @param {Object} store - Vuex or Pinia store
 * @param {Object} schedule - The schedule object
 * @returns {boolean} True if user can delete the schedule
 */
export function canDeleteSchedule(store, schedule) {
  // Only admins can delete schedules
  return store.getters['permissions/isAdmin'](schedule.organization_id);
}
