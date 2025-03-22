/**
 * Re-export date utilities from date-helpers.js
 * This provides backwards compatibility with existing code that imports from dateUtils
 */

import {
  formatDate,
  formatTime,
  formatDateRange,
  getDayName,
  getShortDayName,
  isToday,
  isPastDate
} from './date-helpers';

// Re-export all functions from date-helpers
export {
  formatDate,
  formatTime,
  formatDateRange,
  getDayName,
  getShortDayName,
  isToday,
  isPastDate
};

// Add any additional date utilities needed by Organizations.vue below
// For example:

/**
 * Format a date for display in the organizations list
 * @param {string} dateString - ISO date string
 * @returns {string} Formatted date
 */
export function formatOrganizationDate(dateString) {
  if (!dateString) return 'N/A';
  return formatDate(dateString);
}

/**
 * Get the current month and year as a string
 * @returns {string} Current month and year (e.g., "Mars 2025")
 */
export function getCurrentMonthYear() {
  const now = new Date();
  return new Intl.DateTimeFormat('sv-SE', {
    month: 'long',
    year: 'numeric'
  }).format(now);
}

/**
 * Check if a date is within the current week
 * @param {string|Date} date - Date to check
 * @returns {boolean} True if date is in current week
 */
export function isCurrentWeek(date) {
  const now = new Date();
  const checkDate = new Date(date);
  
  // Get the first day of the week (Monday in Sweden)
  const firstDayOfWeek = new Date(now);
  const day = now.getDay();
  const diff = now.getDate() - day + (day === 0 ? -6 : 1); // adjust when day is Sunday
  firstDayOfWeek.setDate(diff);
  firstDayOfWeek.setHours(0, 0, 0, 0);
  
  // Get the last day of the week (Sunday)
  const lastDayOfWeek = new Date(firstDayOfWeek);
  lastDayOfWeek.setDate(lastDayOfWeek.getDate() + 6);
  lastDayOfWeek.setHours(23, 59, 59, 999);
  
  return checkDate >= firstDayOfWeek && checkDate <= lastDayOfWeek;
}
