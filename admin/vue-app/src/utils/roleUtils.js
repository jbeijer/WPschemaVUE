/**
 * Utility functions for handling user roles and permissions
 */

/**
 * Get the display name for a role
 * @param {string} role - Role (bas, schemaläggare, admin)
 * @returns {string} Localized display name
 */
export function getRoleName(role) {
  const roleNames = {
    'bas': 'Anställd',
    'schemaläggare': 'Schemaläggare',
    'admin': 'Admin'
  };
  
  return roleNames[role] || role;
}

/**
 * Translate a role to its display name (alias for getRoleName for backward compatibility)
 * @param {string} role - Role (bas, schemaläggare, admin)
 * @returns {string} Localized display name
 */
export function translateRole(role) {
  return getRoleName(role);
}

/**
 * Get CSS class for a role
 * @param {string} role - Role (bas, schemaläggare, admin)
 * @returns {string} CSS class for styling
 */
export function getRoleClass(role) {
  const roleClasses = {
    'bas': 'role-bas',
    'schemaläggare': 'role-schemaläggare',
    'admin': 'role-admin'
  };
  
  return roleClasses[role] || 'role-default';
}

/**
 * Get all available roles
 * @returns {Array} List of role objects with id and name
 */
export function getAllRoles() {
  return [
    { id: 'bas', name: 'Anställd' },
    { id: 'schemaläggare', name: 'Schemaläggare' },
    { id: 'admin', name: 'Admin' }
  ];
}

/**
 * Get the role level (numeric value)
 * @param {string} role - Role (bas, schemaläggare, admin)
 * @returns {number} Role level (1-3)
 */
export function getRoleLevel(role) {
  const roleLevels = {
    'bas': 1,
    'schemaläggare': 2,
    'admin': 3
  };
  
  return roleLevels[role] || 0;
}

/**
 * Check if role1 is higher or equal to role2
 * @param {string} role1 - First role to compare
 * @param {string} role2 - Second role to compare
 * @returns {boolean} True if role1 >= role2
 */
export function isRoleHigherOrEqual(role1, role2) {
  return getRoleLevel(role1) >= getRoleLevel(role2);
}

/**
 * Get roles that a user with the given role can assign
 * For example, an admin can assign any role, but a schemaläggare can only assign bas roles
 * @param {string} userRole - The role of the current user
 * @returns {Array} List of assignable role objects
 */
export function getAssignableRoles(userRole) {
  const allRoles = getAllRoles();
  const userRoleLevel = getRoleLevel(userRole);
  
  // Users can only assign roles lower than their own
  return allRoles.filter(role => getRoleLevel(role.id) < userRoleLevel);
}

/**
 * Get permissions available for a specific role
 * @param {string} role - Role (bas, schemaläggare, admin)
 * @returns {Array} List of permission descriptions
 */
export function getRolePermissions(role) {
  const permissions = {
    'bas': [
      'Visa schema',
      'Hantera egna tider'
    ],
    'schemaläggare': [
      'Visa schema',
      'Hantera egna tider', 
      'Schemalägga andra användare',
      'Hantera resurser'
    ],
    'admin': [
      'Visa schema',
      'Hantera egna tider', 
      'Schemalägga andra användare',
      'Hantera resurser',
      'Hantera alla scheman',
      'Låsa scheman',
      'Administrera organisationen'
    ]
  };
  
  return permissions[role] || [];
}

/**
 * Get the color associated with a role
 * @param {string} role - Role (bas, schemaläggare, admin)
 * @returns {string} CSS color
 */
export function getRoleColor(role) {
  const colors = {
    'bas': '#495057',
    'schemaläggare': '#055160',
    'admin': '#842029'
  };
  
  return colors[role] || '#6c757d';
}

/**
 * Get the background color associated with a role
 * @param {string} role - Role (bas, schemaläggare, admin)
 * @returns {string} CSS background color
 */
export function getRoleBackgroundColor(role) {
  const colors = {
    'bas': '#e9ecef',
    'schemaläggare': '#cff4fc',
    'admin': '#f8d7da'
  };
  
  return colors[role] || '#e9ecef';
}
