/**
 * Format a date string to a localized display format
 * @param {string} dateString - ISO date string
 * @returns {string} Formatted date
 */
export function formatDate(dateString) {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('sv-SE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(date);
}

/**
 * Format a time string to a localized display format
 * @param {string} timeString - ISO date string or time string (HH:MM)
 * @returns {string} Formatted time
 */
export function formatTime(timeString) {
  if (!timeString) return '';
  
  // Check if timeString is a full ISO date
  if (timeString.includes('T')) {
    const date = new Date(timeString);
    return date.toLocaleTimeString('sv-SE', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    });
  }
  
  // Otherwise, assume it's just a time string (HH:MM)
  return timeString;
}

/**
 * Format a date range for display
 * @param {string} startDate - ISO date string
 * @param {string} endDate - ISO date string
 * @returns {string} Formatted date range
 */
export function formatDateRange(startDate, endDate) {
  if (!startDate || !endDate) return '';
  
  const start = new Date(startDate);
  const end = new Date(endDate);
  
  // Same day
  if (start.toDateString() === end.toDateString()) {
    const date = formatDate(startDate);
    const startTime = formatTime(startDate);
    const endTime = formatTime(endDate);
    
    return `${date}, ${startTime} - ${endTime}`;
  }
  
  // Different days
  return `${formatDate(startDate)}, ${formatTime(startDate)} - ${formatDate(endDate)}, ${formatTime(endDate)}`;
}

/**
 * Get readable day name from day number (0-6)
 * @param {number} dayNumber - Day number (0 = Sunday, 6 = Saturday)
 * @returns {string} Day name in Swedish
 */
export function getDayName(dayNumber) {
  const days = [
    'Söndag',
    'Måndag',
    'Tisdag',
    'Onsdag',
    'Torsdag',
    'Fredag',
    'Lördag'
  ];
  
  return days[dayNumber] || '';
}

/**
 * Get short day name from day number (0-6)
 * @param {number} dayNumber - Day number (0 = Sunday, 6 = Saturday)
 * @returns {string} Short day name in Swedish
 */
export function getShortDayName(dayNumber) {
  const days = ['Sön', 'Mån', 'Tis', 'Ons', 'Tors', 'Fre', 'Lör'];
  return days[dayNumber] || '';
}

/**
 * Check if a date is today
 * @param {string|Date} date - Date to check
 * @returns {boolean} True if date is today
 */
export function isToday(date) {
  const today = new Date();
  const checkDate = new Date(date);
  
  return (
    checkDate.getDate() === today.getDate() &&
    checkDate.getMonth() === today.getMonth() &&
    checkDate.getFullYear() === today.getFullYear()
  );
}

/**
 * Check if a date is in the past
 * @param {string|Date} date - Date to check
 * @returns {boolean} True if date is in the past
 */
export function isPastDate(date) {
  const now = new Date();
  const checkDate = new Date(date);
  
  return checkDate < now;
}
