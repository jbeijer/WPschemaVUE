/**
 * Utility functions for the Vue app
 */

/**
 * Format a date as YYYY-MM-DD
 * @param {Date} date - Date object
 * @returns {string} Formatted date string
 */
export function formatDateForInput(date) {
  if (!date) return '';
  
  if (typeof date === 'string') {
    date = new Date(date);
  }
  
  return date.toISOString().split('T')[0];
}

/**
 * Format a date and time as YYYY-MM-DDTHH:MM
 * @param {Date} date - Date object
 * @returns {string} Formatted date and time string
 */
export function formatDateTimeForInput(date) {
  if (!date) return '';
  
  if (typeof date === 'string') {
    date = new Date(date);
  }
  
  return date.toISOString().slice(0, 16);
}

/**
 * Format a date as a localized date string
 * @param {Date|string} date - Date object or date string
 * @param {string} locale - Locale string (default: 'sv-SE')
 * @returns {string} Formatted date string
 */
export function formatDate(date, locale = 'sv-SE') {
  if (!date) return '-';
  
  if (typeof date === 'string') {
    date = new Date(date);
  }
  
  return date.toLocaleDateString(locale);
}

/**
 * Format a time as a localized time string
 * @param {Date|string} date - Date object or date string
 * @param {string} locale - Locale string (default: 'sv-SE')
 * @returns {string} Formatted time string
 */
export function formatTime(date, locale = 'sv-SE') {
  if (!date) return '-';
  
  if (typeof date === 'string') {
    date = new Date(date);
  }
  
  return date.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' });
}

/**
 * Format a date and time as a localized date and time string
 * @param {Date|string} date - Date object or date string
 * @param {string} locale - Locale string (default: 'sv-SE')
 * @returns {string} Formatted date and time string
 */
export function formatDateTime(date, locale = 'sv-SE') {
  if (!date) return '-';
  
  if (typeof date === 'string') {
    date = new Date(date);
  }
  
  return date.toLocaleString(locale);
}

/**
 * Translate a schedule status to a human-readable string
 * @param {string} status - Schedule status
 * @returns {string} Translated status
 */
export function translateStatus(status) {
  switch (status) {
    case 'scheduled':
      return 'Planerad';
    case 'confirmed':
      return 'Bekräftad';
    case 'completed':
      return 'Genomförd';
    default:
      return status;
  }
}

/**
 * Translate a user role to a human-readable string
 * @param {string} role - User role
 * @returns {string} Translated role
 */
export function translateRole(role) {
  switch (role) {
    case 'admin':
      return 'Admin';
    case 'scheduler':
      return 'Schemaläggare';
    case 'base':
      return 'Bas';
    default:
      return role;
  }
}

/**
 * Check if a date is today
 * @param {Date|string} date - Date object or date string
 * @returns {boolean} True if the date is today
 */
export function isToday(date) {
  if (!date) return false;
  
  if (typeof date === 'string') {
    date = new Date(date);
  }
  
  const today = new Date();
  return (
    date.getDate() === today.getDate() &&
    date.getMonth() === today.getMonth() &&
    date.getFullYear() === today.getFullYear()
  );
}

/**
 * Get the start of the current week (Monday)
 * @returns {Date} Start of the current week
 */
export function getStartOfWeek() {
  const date = new Date();
  const day = date.getDay();
  const diff = day === 0 ? -6 : 1 - day; // If Sunday, go back 6 days, otherwise go to Monday
  
  date.setDate(date.getDate() + diff);
  date.setHours(0, 0, 0, 0);
  
  return date;
}

/**
 * Get the end of the current week (Sunday)
 * @returns {Date} End of the current week
 */
export function getEndOfWeek() {
  const date = getStartOfWeek();
  date.setDate(date.getDate() + 6);
  date.setHours(23, 59, 59, 999);
  
  return date;
}

/**
 * Validate a hex color code
 * @param {string} color - Color code
 * @returns {boolean} True if the color code is valid
 */
export function isValidHexColor(color) {
  return /^#[0-9A-Fa-f]{6}$/.test(color);
}

/**
 * Generate a random hex color code
 * @returns {string} Random hex color code
 */
export function getRandomColor() {
  const letters = '0123456789ABCDEF';
  let color = '#';
  
  for (let i = 0; i < 6; i++) {
    color += letters[Math.floor(Math.random() * 16)];
  }
  
  return color;
}

/**
 * Debounce a function
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(func, wait = 300) {
  let timeout;
  
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}
