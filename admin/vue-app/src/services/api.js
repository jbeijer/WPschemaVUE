/**
 * API service for making requests to the WordPress REST API
 */

// Get WordPress data from the global variable
const wpData = window.wpScheduleData || {
  nonce: '',
  rest_url: '',
  admin_url: '',
  plugin_url: '',
  current_user: null,
  pages: {}
};

/**
 * Base API class for making requests to the WordPress REST API
 */
class Api {
  /**
   * Constructor
   */
  constructor() {
    this.baseUrl = wpData.rest_url || '/wp-json/schedule/v1';
    this.nonce = wpData.nonce || '';
  }

  /**
   * Get request headers
   * @returns {Object} Headers object
   */
  getHeaders() {
    return {
      'Content-Type': 'application/json',
      'X-WP-Nonce': this.nonce
    };
  }

  /**
   * Make a GET request
   * @param {string} endpoint - API endpoint
   * @param {Object} params - Query parameters
   * @returns {Promise} Promise that resolves to the response data
   */
  async get(endpoint, params = {}) {
    // Build query string
    const queryString = Object.keys(params).length
      ? '?' + new URLSearchParams(params).toString()
      : '';

    try {
      const response = await fetch(`${this.baseUrl}${endpoint}${queryString}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: this.getHeaders()
      });

      if (!response.ok) {
        throw await this.handleErrorResponse(response);
      }

      return await response.json();
    } catch (error) {
      console.error(`GET request failed for ${endpoint}:`, error);
      throw error;
    }
  }

  /**
   * Make a POST request
   * @param {string} endpoint - API endpoint
   * @param {Object} data - Request body data
   * @returns {Promise} Promise that resolves to the response data
   */
  async post(endpoint, data = {}) {
    try {
      const response = await fetch(`${this.baseUrl}${endpoint}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: this.getHeaders(),
        body: JSON.stringify(data)
      });

      if (!response.ok) {
        throw await this.handleErrorResponse(response);
      }

      return await response.json();
    } catch (error) {
      console.error(`POST request failed for ${endpoint}:`, error);
      throw error;
    }
  }

  /**
   * Make a PUT request
   * @param {string} endpoint - API endpoint
   * @param {Object} data - Request body data
   * @returns {Promise} Promise that resolves to the response data
   */
  async put(endpoint, data = {}) {
    try {
      const response = await fetch(`${this.baseUrl}${endpoint}`, {
        method: 'PUT',
        credentials: 'same-origin',
        headers: this.getHeaders(),
        body: JSON.stringify(data)
      });

      if (!response.ok) {
        throw await this.handleErrorResponse(response);
      }

      return await response.json();
    } catch (error) {
      console.error(`PUT request failed for ${endpoint}:`, error);
      throw error;
    }
  }

  /**
   * Make a DELETE request
   * @param {string} endpoint - API endpoint
   * @returns {Promise} Promise that resolves to the response data
   */
  async delete(endpoint) {
    try {
      const response = await fetch(`${this.baseUrl}${endpoint}`, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: this.getHeaders()
      });

      if (!response.ok) {
        throw await this.handleErrorResponse(response);
      }

      return await response.json();
    } catch (error) {
      console.error(`DELETE request failed for ${endpoint}:`, error);
      throw error;
    }
  }

  /**
   * Handle error response
   * @param {Response} response - Fetch Response object
   * @returns {Promise} Promise that resolves to an error object
   */
  async handleErrorResponse(response) {
    let errorData;
    
    try {
      errorData = await response.json();
    } catch (e) {
      // If the response is not JSON, use the status text
      return new Error(`${response.status}: ${response.statusText}`);
    }
    
    // Check if the error is a WordPress error
    if (errorData.code && errorData.message) {
      return new Error(`${errorData.code}: ${errorData.message}`);
    }
    
    // Check if the error is a custom error
    if (errorData.error && errorData.error.message) {
      return new Error(errorData.error.message);
    }
    
    // Fallback to a generic error
    return new Error(`${response.status}: ${response.statusText}`);
  }
}

// Create and export a singleton instance
const api = new Api();
export default api;
