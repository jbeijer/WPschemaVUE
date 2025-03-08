import apiFetch from '@wordpress/api-fetch';

export const fetchWithAuth = async (endpoint, options = {}) => {
  // Get WordPress data from the global variable
  const wpData = window.wpScheduleData || {
    nonce: '',
    rest_url: '',
    admin_url: '',
    plugin_url: '',
    current_user: null,
    pages: {}
  };

  apiFetch.use(apiFetch.createNonceMiddleware(wpData.nonce));
  
  return await apiFetch({
    path: endpoint,
    ...options
  });
};
