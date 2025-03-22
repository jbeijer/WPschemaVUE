<template>
  <div class="resources">
    <div class="resources-header">
      <h2>Resurser</h2>
      <div class="header-actions">
        <div class="organization-selector">
          <label for="organization-select">Organisation:</label>
          <select id="organization-select" v-model="selectedOrganizationId" @change="loadResources">
            <option v-for="org in organizations" :key="org.id" :value="org.id">
              {{ org.name }}
            </option>
          </select>
        </div>
        <button 
          v-if="selectedOrganizationId" 
          class="header-create-btn" 
          @click="showCreateForm = true"
        >
          <span class="plus-icon">+</span> Skapa ny resurs
        </button>
      </div>
    </div>
    
    <!-- Add prominent create button -->
    <div class="create-resource-button-container" v-if="selectedOrganizationId">
      <button class="btn btn-primary create-resource-btn" @click="showCreateForm = true">
        <span class="plus-icon">+</span> Skapa ny resurs
      </button>
    </div>
    
    <div class="loading-indicator" v-if="loading">
      <p>Laddar resurser...</p>
    </div>
    
    <div class="error-message" v-if="error">
      <p>{{ error }}</p>
    </div>
    
    <div class="resources-content" v-if="!loading && !error">
      <div v-if="!selectedOrganizationId" class="select-organization-message">
        <p>Välj en organisation för att visa resurser.</p>
      </div>
      
      <div v-else-if="resources.length === 0" class="no-resources-message">
        <p>Inga resurser hittades för den valda organisationen.</p>
        <button 
          v-if="canManageResources" 
          class="btn btn-primary" 
          @click="showCreateForm = true"
        >
          Skapa din första resurs
        </button>
      </div>
      
      <div v-else class="resources-grid">
        <div v-for="resource in resources" :key="resource.id" class="resource-card" :style="{ borderColor: resource.color }">
          <div class="resource-header" :style="{ backgroundColor: resource.color }">
            <h3>{{ resource.name }}</h3>
          </div>
          <div class="resource-body">
            <p class="resource-description">{{ resource.description || 'Ingen beskrivning' }}</p>
            <div class="resource-color">
              <span class="color-label">Färg:</span>
              <span class="color-box" :style="{ backgroundColor: resource.color }"></span>
              <span class="color-value">{{ resource.color }}</span>
            </div>
          </div>
          <div class="resource-footer">
            <button class="btn btn-small" @click="viewSchedules(resource)">
              Visa schema
            </button>
            <button class="btn btn-small" @click="manageAvailability(resource)">
              Hantera tillgänglighet
            </button>
            <button class="btn btn-small" @click="editResource(resource)" v-if="canManageResources">
              Redigera
            </button>
            <button class="btn btn-small btn-danger" @click="confirmDelete(resource)" v-if="canManageResources">
              Ta bort
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create Resource Modal -->
    <div class="modal" v-if="showCreateForm">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Skapa ny resurs</h3>
          <button class="close-button" @click="showCreateForm = false">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="createResource">
            <div class="form-group">
              <label for="create-name">Namn</label>
              <input type="text" id="create-name" v-model="newResource.name" required>
            </div>
            <div class="form-group">
              <label for="create-description">Beskrivning</label>
              <textarea id="create-description" v-model="newResource.description" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>Tillgänglighet</label>
              <div class="availability-options">
                <div class="radio-option">
                  <input type="radio" id="available-24-7" v-model="newResource.is_24_7" :value="true">
                  <label for="available-24-7">Tillgänglig 24/7</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="available-specific" v-model="newResource.is_24_7" :value="false">
                  <label for="available-specific">Tillgänglig under specifika tider</label>
                </div>
              </div>
            </div>
            <div class="form-group" v-if="!newResource.is_24_7">
              <div class="time-inputs">
                <div class="time-input">
                  <label for="create-start-time">Starttid</label>
                  <input type="time" id="create-start-time" v-model="newResource.start_time" required>
                </div>
                <div class="time-input">
                  <label for="create-end-time">Sluttid</label>
                  <input type="time" id="create-end-time" v-model="newResource.end_time" required>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="showCreateForm = false">
                Avbryt
              </button>
              <button type="submit" class="btn btn-primary" :disabled="createLoading">
                {{ createLoading ? 'Skapar...' : 'Skapa' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Edit Resource Modal -->
    <div class="modal" v-if="showEditForm">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Redigera resurs</h3>
          <button class="close-button" @click="showEditForm = false">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updateResource">
            <div class="form-group">
              <label for="edit-name">Namn</label>
              <input type="text" id="edit-name" v-model="editedResource.name" required>
            </div>
            <div class="form-group">
              <label for="edit-description">Beskrivning</label>
              <textarea id="edit-description" v-model="editedResource.description" rows="3"></textarea>
            </div>
            <div class="form-actions">
              <button type="button" class="btn btn-secondary" @click="showEditForm = false">
                Avbryt
              </button>
              <button type="submit" class="btn btn-primary" :disabled="updateLoading">
                {{ updateLoading ? 'Sparar...' : 'Spara' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal" v-if="showDeleteConfirmation">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Bekräfta borttagning</h3>
          <button class="close-button" @click="showDeleteConfirmation = false">&times;</button>
        </div>
        <div class="modal-body">
          <p>Är du säker på att du vill ta bort resursen "{{ resourceToDelete ? resourceToDelete.name : '' }}"?</p>
          <p class="warning">Denna åtgärd kan inte ångras!</p>
          
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="showDeleteConfirmation = false">
              Avbryt
            </button>
            <button type="button" class="btn btn-danger" @click="deleteResource" :disabled="deleteLoading">
              {{ deleteLoading ? 'Tar bort...' : 'Ta bort' }}
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Availability Modal -->
    <div class="modal" v-if="showAvailabilityModal">
      <div class="modal-content modal-large">
        <div class="modal-header">
          <h3>Hantera tillgänglighet</h3>
          <button class="close-button" @click="showAvailabilityModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <ResourceAvailability 
            v-if="selectedResource"
            :resource="selectedResource"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useOrganizationsStore } from '@/stores/organizations';
import { useResourcesStore } from '@/stores/resources';
import ResourceAvailability from '@/components/ResourceAvailability.vue';
import { canManageResources } from '@/utils/permissions';

export default {
  name: 'Resources',
  components: {
    ResourceAvailability
  },
  data() {
    return {
      loading: false,
      error: null,
      selectedOrganizationId: null,
      showEditForm: false,
      showDeleteConfirmation: false,
      updateLoading: false,
      deleteLoading: false,
      editedResource: {
        id: null,
        name: '',
        description: '',
        color: '#3788d8'
      },
      resourceToDelete: null,
      showAvailabilityModal: false,
      selectedResource: null,
      showCreateForm: false,
      newResource: {
        name: '',
        description: '',
        is_24_7: true,
        start_time: '',
        end_time: ''
      },
      createLoading: false
    };
  },
  computed: {
    organizations() {
      return this.organizationsStore.organizations;
    },
    resources() {
      return this.resourcesStore.resources;
    },
    canManageResources() {
      return canManageResources(this.selectedOrganizationId);
    }
  },
  created() {
    this.organizationsStore = useOrganizationsStore();
    this.resourcesStore = useResourcesStore();
    
    this.loadOrganizations();
    
    // Check if organization is specified in query params
    const orgId = this.$route.query.organization;
    if (orgId) {
      this.selectedOrganizationId = parseInt(orgId, 10);
    }
  },
  methods: {
    async loadOrganizations() {
      this.loading = true;
      this.error = null;
      
      try {
        await this.organizationsStore.fetchOrganizations();
        
        // If no organization is selected and we have organizations, select the first one
        if (!this.selectedOrganizationId && this.organizations.length > 0) {
          this.selectedOrganizationId = this.organizations[0].id;
        }
        
        // If we have a selected organization, load its resources
        if (this.selectedOrganizationId) {
          await this.loadResources();
        }
      } catch (error) {
        console.error('Error loading organizations:', error);
        this.error = 'Det gick inte att ladda organisationer: ' + error.message;
      } finally {
        this.loading = false;
      }
    },
    
    async loadResources() {
      if (!this.selectedOrganizationId) {
        return;
      }
      
      this.loading = true;
      this.error = null;
      
      try {
        await this.resourcesStore.fetchResourcesByOrganization(this.selectedOrganizationId);
      } catch (error) {
        console.error('Error loading resources:', error);
        this.error = 'Det gick inte att ladda resurser: ' + error.message;
      } finally {
        this.loading = false;
      }
    },
    
    editResource(resource) {
      this.editedResource = {
        id: resource.id,
        name: resource.name,
        description: resource.description || '',
        color: resource.color
      };
      this.showEditForm = true;
    },
    
    async updateResource() {
      this.updateLoading = true;
      
      try {
        await this.resourcesStore.updateResource(
          this.editedResource.id,
          this.editedResource
        );
        this.showEditForm = false;
      } catch (error) {
        console.error('Error updating resource:', error);
        alert('Det gick inte att uppdatera resursen: ' + error.message);
      } finally {
        this.updateLoading = false;
      }
    },
    
    confirmDelete(resource) {
      this.resourceToDelete = resource;
      this.showDeleteConfirmation = true;
    },
    
    async deleteResource() {
      if (!this.resourceToDelete) {
        return;
      }
      
      this.deleteLoading = true;
      
      try {
        await this.resourcesStore.deleteResource(this.resourceToDelete.id);
        this.showDeleteConfirmation = false;
        this.resourceToDelete = null;
      } catch (error) {
        console.error('Error deleting resource:', error);
        alert('Det gick inte att ta bort resursen: ' + error.message);
      } finally {
        this.deleteLoading = false;
      }
    },
    
    viewSchedules(resource) {
      // Navigate to schedules view with this resource selected
      this.$router.push({
        name: 'schedules',
        query: { resource: resource.id }
      });
    },
    
    manageAvailability(resource) {
      this.selectedResource = resource;
      this.showAvailabilityModal = true;
    },
    
    async createResource() {
      this.createLoading = true;
      
      try {
        // Create a copy of the resource data to avoid modifying the form directly
        const formattedResource = { ...this.newResource };
        
        // Format time values to ensure they match the required pattern HH:MM
        if (!formattedResource.is_24_7) {
          // Make sure times are in HH:MM format
          if (formattedResource.start_time) {
            formattedResource.start_time = formattedResource.start_time.substring(0, 5);
          }
          if (formattedResource.end_time) {
            formattedResource.end_time = formattedResource.end_time.substring(0, 5);
          }
        }
        
        await this.resourcesStore.createResource(
          this.selectedOrganizationId,
          formattedResource
        );
        this.showCreateForm = false;
        this.newResource = {
          name: '',
          description: '',
          is_24_7: true,
          start_time: '',
          end_time: ''
        };
      } catch (error) {
        console.error('Error creating resource:', error);
        alert('Det gick inte att skapa resursen: ' + error.message);
      } finally {
        this.createLoading = false;
      }
    }
  },
  watch: {
    // If the route query changes, update the selected organization
    '$route.query.organization'(newOrgId) {
      if (newOrgId) {
        this.selectedOrganizationId = parseInt(newOrgId, 10);
        this.loadResources();
      }
    }
  }
};
</script>

<style scoped>
.resources {
  padding: 20px;
}

.resources-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  border-bottom: 1px solid #ddd;
  padding-bottom: 15px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 15px;
}

.organization-selector {
  display: flex;
  align-items: center;
  gap: 10px;
}

.organization-selector select {
  padding: 8px;
  border-radius: 4px;
  border: 1px solid #ddd;
  min-width: 200px;
}

.header-create-btn {
  padding: 8px 16px;
  font-size: 1em;
  background-color: #2271b1;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  font-weight: bold;
}

.header-create-btn:hover {
  background-color: #135e96;
}

.header-create-btn .plus-icon {
  font-size: 1.2em;
  margin-right: 5px;
}

.select-organization-message,
.no-resources-message {
  background-color: #f9f9f9;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  padding: 20px;
  text-align: center;
  margin: 20px 0;
}

.resources-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.resource-card {
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  border-top-width: 4px;
}

.resource-header {
  padding: 15px;
  color: white;
}

.resource-header h3 {
  margin: 0;
  font-size: 1.2em;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.resource-body {
  padding: 15px;
  flex-grow: 1;
}

.resource-description {
  margin-top: 0;
  margin-bottom: 15px;
  min-height: 40px;
}

.resource-color {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
}

.color-label {
  font-weight: bold;
}

.color-box {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.resource-footer {
  padding: 15px;
  background-color: #f9f9f9;
  border-top: 1px solid #e5e5e5;
  display: flex;
  justify-content: space-between;
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

.btn-secondary {
  background-color: #6c757d;
}

.btn-danger {
  background-color: #dc3545;
}

.btn-danger:hover {
  background-color: #bd2130;
}

.btn-small {
  padding: 4px 8px;
  font-size: 0.9em;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: #fff;
  border-radius: 4px;
  width: 500px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e5e5e5;
}

.modal-header h3 {
  margin: 0;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5em;
  cursor: pointer;
  color: #6c757d;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.form-group textarea {
  resize: vertical;
}

.color-picker {
  display: flex;
  gap: 10px;
}

.color-picker input[type="color"] {
  width: 50px;
  height: 38px;
  padding: 0;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.warning {
  color: #dc3545;
  font-weight: bold;
}

.modal-large {
  max-width: 800px;
  width: 90%;
}

.availability-options {
  display: flex;
  gap: 10px;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 5px;
}

.time-inputs {
  display: flex;
  gap: 10px;
}

.time-input {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.create-resource-button-container {
  margin-bottom: 20px;
  text-align: center;
}

.create-resource-button-container .btn {
  padding: 12px 20px;
  font-size: 1.1em;
}

.create-resource-button-container .plus-icon {
  font-size: 1.5em;
  margin-right: 5px;
}

.header-create-btn {
  padding: 8px 12px;
  font-size: 1em;
}

.header-create-btn .plus-icon {
  font-size: 1.2em;
  margin-right: 5px;
}
</style>
