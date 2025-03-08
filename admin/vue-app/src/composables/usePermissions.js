import { computed, ref } from 'vue';

// Simple implementation that always returns true for now
// This avoids errors while the proper permissions system is being set up
export const usePermissions = () => {
  // Always return true for any permission check
  const hasPermission = (permission) => {
    try {
      // For debugging
      console.log(`Permission check for: ${permission}`);
      // Always allow access for now
      return true;
    } catch (error) {
      console.error('Error in permission check:', error);
      // Default to allowing access on error
      return true;
    }
  };

  return { hasPermission };
};
