/**
 * Permissions utility functions for WP Schedule Manager
 * 
 * This file contains utility functions to check user permissions based on their roles
 * in the organization. The system has three distinct roles:
 * 
 * 1. Bas (Anställd): Can view schedules and manage their own shifts
 * 2. Schemaläggare: Inherits Bas permissions and can schedule shifts for others
 * 3. Admin: Inherits Schemaläggare permissions and can manage resources, lock shifts, etc.
 */

// Get the current user's role in the specified organization
export function getUserRole(organizationId) {
  const wpData = window.wpScheduleData || {};
  const userOrganizations = wpData.user_organizations || [];
  
  console.log('getUserRole - wpData:', wpData);
  console.log('getUserRole - userOrganizations:', userOrganizations);
  console.log('getUserRole - organizationId:', organizationId);
  
  const userOrg = userOrganizations.find(org => org.organization_id === organizationId);
  console.log('getUserRole - userOrg:', userOrg);
  
  return userOrg ? userOrg.role : null;
}

// Check if the user has at least the specified role in the organization
export function hasRole(organizationId, requiredRole) {
  const userRole = getUserRole(organizationId);
  
  console.log('hasRole - userRole:', userRole);
  console.log('hasRole - requiredRole:', requiredRole);
  
  if (!userRole) {
    return false;
  }
  
  // Role hierarchy
  const roles = {
    'bas': 1,
    'schemaläggare': 2,
    'admin': 3
  };
  
  // Convert roles to lowercase for case-insensitive comparison
  const userRoleLevel = roles[userRole.toLowerCase()] || 0;
  const requiredRoleLevel = roles[requiredRole.toLowerCase()] || 0;
  
  console.log('hasRole - userRoleLevel:', userRoleLevel);
  console.log('hasRole - requiredRoleLevel:', requiredRoleLevel);
  
  return userRoleLevel >= requiredRoleLevel;
}

// Permission check functions
export function canViewSchedules(organizationId) {
  // All users with any role can view schedules
  return !!getUserRole(organizationId);
}

export function canManageOwnShifts(organizationId) {
  // All users with any role can manage their own shifts
  return !!getUserRole(organizationId);
}

export function canScheduleShifts(organizationId) {
  // Schemaläggare and Admin can schedule shifts
  return hasRole(organizationId, 'schemaläggare');
}

export function canManageResources(organizationId) {
  // For testing purposes, always allow resource management
  console.log('canManageResources called with organizationId:', organizationId);
  return true;
  
  // Only Admin can manage resources
  // return hasRole(organizationId, 'admin');
}

export function canLockShifts(organizationId) {
  // Only Admin can lock shifts
  return hasRole(organizationId, 'admin');
}

export function canManageOrganization(organizationId) {
  // Only Admin can manage organization settings
  return hasRole(organizationId, 'admin');
}
