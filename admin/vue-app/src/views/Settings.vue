<template>
  <div class="settings">
    <div class="settings-header">
      <h2>Inställningar</h2>
    </div>
    
    <div class="loading-indicator" v-if="loading">
      <p>Laddar inställningar...</p>
    </div>
    
    <div class="error-message" v-if="error">
      <p>{{ error }}</p>
    </div>
    
    <div class="settings-content" v-if="!loading && !error">
      <div class="settings-section">
        <h3>Användarinformation</h3>
        <div class="user-info" v-if="currentUser">
          <p><strong>Namn:</strong> {{ currentUser.display_name }}</p>
          <p><strong>E-post:</strong> {{ currentUser.email }}</p>
          <p><strong>Användarnamn:</strong> {{ currentUser.username }}</p>
          <p><strong>Roller:</strong> {{ currentUser.roles.join(', ') }}</p>
        </div>
      </div>
      
      <div class="settings-section">
        <h3>Organisationer</h3>
        <div v-if="currentUser && currentUser.organizations">
          <p>Du är medlem i följande organisationer:</p>
          <ul class="organizations-list">
            <li v-for="org in currentUser.organizations" :key="org.id">
              <span class="org-name">{{ org.name }}</span>
              <span class="org-role">{{ translateRole(org.role) }}</span>
            </li>
          </ul>
        </div>
        <div v-else>
          <p>Du är inte medlem i någon organisation.</p>
        </div>
      </div>
      
      <div class="settings-section">
        <h3>Appinställningar</h3>
        <form @submit.prevent="saveSettings">
          <div class="form-group">
            <label for="default-view">Standardvy</label>
            <select id="default-view" v-model="settings.defaultView">
              <option value="dashboard">Dashboard</option>
              <option value="organizations">Organisationer</option>
              <option value="resources">Resurser</option>
              <option value="schedules">Scheman</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="items-per-page">Antal objekt per sida</label>
            <select id="items-per-page" v-model="settings.itemsPerPage">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>
              <input type="checkbox" v-model="settings.showConfirmDialogs">
              Visa bekräftelsedialoger vid borttagning
            </label>
          </div>
          
          <div class="form-group">
            <label>
              <input type="checkbox" v-model="settings.enableNotifications">
              Aktivera notifikationer
            </label>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saveLoading">
              {{ saveLoading ? 'Sparar...' : 'Spara inställningar' }}
            </button>
          </div>
        </form>
      </div>
      
      <div class="settings-section">
        <h3>Om Schema Manager</h3>
        <div class="about-info">
          <p><strong>Version:</strong> {{ pluginVersion }}</p>
          <p><strong>Beskrivning:</strong> Ett komplett schemahanteringssystem med hierarkiska organisationer och Vue 3</p>
          <p>
            Schema Manager är ett kraftfullt verktyg för att hantera scheman inom organisationer med hierarkisk struktur.
            Systemet är särskilt utformat för att hantera komplexa organisationsstrukturer med olika behörighetsnivåer.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useUsersStore } from '@/stores/users';

export default {
  name: 'Settings',
  data() {
    return {
      loading: false,
      error: null,
      saveLoading: false,
      settings: {
        defaultView: 'dashboard',
        itemsPerPage: '25',
        showConfirmDialogs: true,
        enableNotifications: true
      },
      pluginVersion: ''
    };
  },
  computed: {
    currentUser() {
      return this.usersStore.currentUserInfo;
    }
  },
  created() {
    this.usersStore = useUsersStore();
    
    this.loadData();
    this.loadSettings();
  },
  methods: {
    async loadData() {
      this.loading = true;
      this.error = null;
      
      try {
        // Load user info
        await this.usersStore.fetchCurrentUserInfo();
        
        // Get plugin version from global data
        const wpData = window.wpScheduleData || {};
        this.pluginVersion = wpData.version || '1.0.0';
      } catch (error) {
        console.error('Error loading settings data:', error);
        this.error = 'Det gick inte att ladda inställningar: ' + error.message;
      } finally {
        this.loading = false;
      }
    },
    
    loadSettings() {
      // Load settings from localStorage
      const savedSettings = localStorage.getItem('wpschema_settings');
      if (savedSettings) {
        try {
          const parsedSettings = JSON.parse(savedSettings);
          this.settings = { ...this.settings, ...parsedSettings };
        } catch (error) {
          console.error('Error parsing saved settings:', error);
        }
      }
    },
    
    async saveSettings() {
      this.saveLoading = true;
      
      try {
        // Save settings to localStorage
        localStorage.setItem('wpschema_settings', JSON.stringify(this.settings));
        
        // Show success message
        alert('Inställningarna har sparats.');
      } catch (error) {
        console.error('Error saving settings:', error);
        alert('Det gick inte att spara inställningarna: ' + error.message);
      } finally {
        this.saveLoading = false;
      }
    },
    
    translateRole(role) {
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
  }
};
</script>

<style scoped>
.settings {
  padding: 20px 0;
}

.settings-header {
  margin-bottom: 20px;
}

.settings-section {
  background-color: #fff;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.settings-section h3 {
  margin-top: 0;
  border-bottom: 1px solid #e5e5e5;
  padding-bottom: 10px;
  margin-bottom: 15px;
}

.user-info p,
.about-info p {
  margin: 5px 0;
}

.organizations-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.organizations-list li {
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.organizations-list li:last-child {
  border-bottom: none;
}

.org-name {
  font-weight: bold;
}

.org-role {
  background-color: #e5e5e5;
  color: #333;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.8em;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.form-group input[type="checkbox"] + label {
  display: inline;
  font-weight: normal;
  margin-left: 5px;
}

.form-group select,
.form-group input[type="text"],
.form-group input[type="number"] {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.form-actions {
  margin-top: 20px;
}

.btn {
  display: inline-block;
  background-color: #0073aa;
  color: #fff;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: #005177;
}

.btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #0073aa;
}

.loading-indicator,
.error-message {
  padding: 15px;
  margin-bottom: 20px;
  border-radius: 4px;
}

.loading-indicator {
  background-color: #f8f9fa;
  border: 1px solid #e5e5e5;
}

.error-message {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}
</style>
