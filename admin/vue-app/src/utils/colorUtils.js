/**
 * Utility functions for handling colors
 */

/**
 * Normalizes a color value to the #RRGGBB format
 * Accepts various formats including #RGB, RGB, RRGGBB, rgb(r,g,b)
 * 
 * @param {string} color - The color value to normalize
 * @param {string} defaultColor - Default color to use if input is invalid
 * @returns {string} - Normalized color in #RRGGBB format
 */
export function normalizeColor(color, defaultColor = '#3788d8') {
  // Handle empty values
  if (!color) {
    return defaultColor;
  }
  
  // Remove whitespace
  color = color.trim();
  
  // If it's already a valid hex format, return it (lowercase)
  if (/^#[0-9a-f]{6}$/i.test(color)) {
    return color.toLowerCase();
  }
  
  // If it's short hex format (#RGB), convert to #RRGGBB
  if (/^#[0-9a-f]{3}$/i.test(color)) {
    const r = color.charAt(1);
    const g = color.charAt(2);
    const b = color.charAt(3);
    return `#${r}${r}${g}${g}${b}${b}`.toLowerCase();
  }
  
  // If it lacks #, add it (RRGGBB format)
  if (/^[0-9a-f]{6}$/i.test(color)) {
    return `#${color}`.toLowerCase();
  }
  
  // If it's short format without # (RGB), convert to #RRGGBB
  if (/^[0-9a-f]{3}$/i.test(color)) {
    const r = color.charAt(0);
    const g = color.charAt(1);
    const b = color.charAt(2);
    return `#${r}${r}${g}${g}${b}${b}`.toLowerCase();
  }
  
  // Handle rgb() format
  const rgbMatch = color.match(/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i);
  if (rgbMatch) {
    const r = Math.min(255, Math.max(0, parseInt(rgbMatch[1]))).toString(16).padStart(2, '0');
    const g = Math.min(255, Math.max(0, parseInt(rgbMatch[2]))).toString(16).padStart(2, '0');
    const b = Math.min(255, Math.max(0, parseInt(rgbMatch[3]))).toString(16).padStart(2, '0');
    return `#${r}${g}${b}`.toLowerCase();
  }
  
  // If no format matched, use default color
  return defaultColor;
}

/**
 * Validates if a color is in the correct #RRGGBB format
 * 
 * @param {string} color - The color to validate
 * @returns {boolean} - True if valid, false otherwise
 */
export function isValidColor(color) {
  return /^#[0-9a-f]{6}$/i.test(color);
}
