import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';

// Funktion för att montera appen
function mountApp() {
    console.log('Försöker montera Vue-appen...');
    
    // Create the Vue app instance
    const app = createApp(App);

    // Use Pinia for state management
    app.use(createPinia());

    // Use Vue Router
    app.use(router);

    // Get the WordPress data passed from the admin class
    const wpData = window.wpScheduleData || {
        nonce: '',
        rest_url: '',
        admin_url: '',
        plugin_url: '',
        current_user: null,
        pages: {}
    };

    // Logga WordPress-data för felsökning
    console.log('WordPress-data:', wpData);

    // Make WordPress data available globally in the app
    app.config.globalProperties.$wp = wpData;

    // Mount the app to the element with id 'wpschema-vue-admin-app'
    const appContainer = document.getElementById('wpschema-vue-admin-app');
    
    if (appContainer) {
        console.log('Hittade appContainer, monterar Vue-appen');
        app.mount(appContainer);
    } else {
        console.error('Kunde inte hitta element med ID "wpschema-vue-admin-app"');
        
        // Logga alla div-element på sidan för felsökning
        console.log('Tillgängliga div-element:', document.querySelectorAll('div'));
    }
}

// Försök montera appen när DOM är redo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountApp);
} else {
    // Om DOMContentLoaded redan har triggats, försök montera direkt
    mountApp();
}
