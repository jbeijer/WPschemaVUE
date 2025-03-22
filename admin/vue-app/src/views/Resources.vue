<template>
  <div class="resources">
    <div class="resources-header">
      <h2>Resurser</h2>
      <div class="header-actions">
        <OrganizationSelector 
          :organizations="organizations" 
          v-model="selectedOrganizationId"
          @change="loadResources"
        />
        <BaseButton 
          v-if="selectedOrganizationId && canManageResources" 
          variant="primary" 
          @click="openCreateForm"
        >
          <span class="plus-icon">+</span> Skapa ny resurs
        </BaseButton>
      </div>
    </div>
    
    <!-- Loading and Error States -->
    <LoadingIndicator v-if="loading" message="Laddar resurser..." />
    <ErrorMessage v-if="error" :message="error" />
    
    <!-- Content States -->
    <div class="resources-content" v-if="!loading && !error">
      <!-- No Organization Selected -->
      <EmptyState 
        v-if="!selectedOrganizationId"
        message="Välj en organisation för att visa resurser."
      />
      
      <!-- No Resources Found -->
      <EmptyState 
        v-else-if="resources.length === 0"
        message="Inga resurser hittades för den valda organisationen."
      >
        <BaseButton 
          v-if="canManageResources" 
          variant="primary" 
          @click="openCreateForm"
        >
          Skapa din första resurs
        </BaseButton>
      </EmptyState>
      
      <!-- Resource Grid -->
      <ResourceGrid 
        v-else 
        :resources="resources"
        :can-manage="canManageResources"
        @view-schedules="viewSchedules"
        @manage-availability="manageAvailability"
        @edit="editResource"
        @delete="confirmDelete"
      />
    </div>
    
    <!-- Modals -->
    <ResourceFormModal
      v-if="showCreateForm"
      title="Skapa ny resurs"
      :resource="newResource"
      :organization-id="selectedOrganizationId"
      :loading="createLoading"
      @close="showCreateForm = false"
      @submit="createResource"
    />
    
    <ResourceFormModal
      v-if="showEditForm"
      title="Redigera resurs"
      :resource="editedResource"
      :organization-id="selectedOrganizationId"
      :loading="updateLoading"
      :is-editing="true"
      @close="showEditForm = false"
      @submit="updateResource"
    />
    
    <ConfirmDialog
      v-if="showDeleteConfirmation"
      title="Bekräfta borttagning"
      :message="`Är du säker på att du vill ta bort resursen '${resourceToDelete?.name}'?`"
      warning-message="Denna åtgärd kan inte ångras!"
      confirm-text="Ta bort"
      :loading="deleteLoading"
      @confirm="deleteResource"
      @cancel="showDeleteConfirmation = false"
    />
    
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
import { defineComponent } from 'vue';
import { useOrganizationsStore } from '@/stores/organizations';
import { useResourcesStore } from '@/stores/resources';
import { usePermissionsStore } from '@/stores/permissions';
import BaseButton from '@/components/BaseButton.vue';
import LoadingIndicator from '@/components/LoadingIndicator.vue';
import ErrorMessage from '@/components/ErrorMessage.vue';
import EmptyState from '@/components/EmptyState.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import OrganizationSelector from '@/components/OrganizationSelector.vue';
import ResourceGrid from '@/components/resources/ResourceGrid.vue';
import ResourceFormModal from '@/components/resources/ResourceFormModal.vue';
import ResourceAvailability from '@/components/ResourceAvailability.vue';

export default defineComponent({
  name: 'Resources',
  
  components: {
    BaseButton,
    LoadingIndicator,
    ErrorMessage,
    EmptyState,
    ConfirmDialog,
    OrganizationSelector,
    ResourceGrid,
    ResourceFormModal,
    ResourceAvailability
  },
  
  data() {
    return {
      // State
      loading: false,
      error: null,
      selectedOrganizationId: null,
      
      // Resource data
      showCreateForm: false,
      showEditForm: false,
      createLoading: false,
      updateLoading: false,
      
      newResource: this.getDefaultResourceObject(),
      editedResource: null,
      
      // Delete confirmation
      showDeleteConfirmation: false,
      resourceToDelete: null,
      deleteLoading: false,
      
      // Availability management
      showAvailabilityModal: false,
      selectedResource: null
    };
  },
  
  computed: {
    organizations() {
      return useOrganizationsStore().organizations;
    },
    
    resources() {
      return useResourcesStore().resources;
    },
    
    canManageResources() {
      return usePermissionsStore().canManageResources(this.selectedOrganizationId);
    }
  },
  
  created() {
    this.loadOrganizations();
    
    // Check for organization in query params
    const { organization } = this.$route.query;
    if (organization) {
      this.selectedOrganizationId = parseInt(organization, 10);
      this.loadResources();
    }
  },
  
  methods: {
    getDefaultResourceObject() {
      return {
        name: '',
        description: '',
        is_24_7: true,
        start_time: '09:00',
        end_time: '17:00',
        color: '#2196F3'
      };
    },
    
    async loadOrganizations() {
      this.loading = true;
      this.error = null;
      
      try {
        await useOrganizationsStore().fetchOrganizations();
        
        // If there's only one organization, select it automatically
        const orgs = this.organizations;
        if (orgs.length === 1) {
          this.selectedOrganizationId = orgs[0].id;
          await this.loadResources();
        }
      } catch (err) {
        this.error = 'Kunde inte ladda organisationer: ' + (err.message || 'Okänt fel');
      } finally {
        this.loading = false;
      }
    },
    
    async loadResources() {
      if (!this.selectedOrganizationId) return;
      
      this.loading = true;
      this.error = null;
      
      try {
        await useResourcesStore().fetchResourcesByOrganization(this.selectedOrganizationId);
        
        // Update the URL with the organization ID as a query parameter
        this.$router.replace({
          query: { ...this.$route.query, organization: this.selectedOrganizationId }
        });
      } catch (err) {
        this.error = 'Kunde inte ladda resurser: ' + (err.message || 'Okänt fel');
      } finally {
        this.loading = false;
      }
    },
    
    openCreateForm() {
      this.newResource = this.getDefaultResourceObject();
      this.showCreateForm = true;
    },
    
    async createResource(resourceData) {
      if (!this.selectedOrganizationId) return;
      
      this.createLoading = true;
      
      try {
        await useResourcesStore().createResource({
          ...resourceData,
          organization_id: this.selectedOrganizationId
        });
        
        this.showCreateForm = false;
        await this.loadResources();
        
        return true;
      } catch (err) {
        this.error = 'Kunde inte skapa resurs: ' + (err.message || 'Okänt fel');
        return false;
      } finally {
        this.createLoading = false;
      }
    },
    
    editResource(resource) {
      this.editedResource = { ...resource };
      this.showEditForm = true;
    },
    
    async updateResource(resourceData) {
      this.updateLoading = true;
      
      try {
        await useResourcesStore().updateResource(resourceData);
        
        this.showEditForm = false;
        await this.loadResources();
        
        return true;
      } catch (err) {
        this.error = 'Kunde inte uppdatera resurs: ' + (err.message || 'Okänt fel');
        return false;
      } finally {
        this.updateLoading = false;
      }
    },
    
    confirmDelete(resource) {
      this.resourceToDelete = resource;
      this.showDeleteConfirmation = true;
    },
    
    async deleteResource() {
      if (!this.resourceToDelete) return;
      
      this.deleteLoading = true;
      
      try {
        await useResourcesStore().deleteResource(this.resourceToDelete.id);
        
        this.showDeleteConfirmation = false;
        this.resourceToDelete = null;
        await this.loadResources();
      } catch (err) {
        this.error = 'Kunde inte ta bort resurs: ' + (err.message || 'Okänt fel');
      } finally {
        this.deleteLoading = false;
      }
    },
    
    viewSchedules(resource) {
      this.$router.push({
        name: 'schedules',
        query: { resource: resource.id }
      });
    },
    
    manageAvailability(resource) {
      this.selectedResource = resource;
      this.showAvailabilityModal = true;
    }
  },
  
  watch: {
    // If the route query changes, update the selected organization
    '$route.query.organization'(newOrgId) {
      if (newOrgId) {
        const parsedId = parseInt(newOrgId, 10);
        if (parsedId !== this.selectedOrganizationId) {
          this.selectedOrganizationId = parsedId;
          this.loadResources();
        }
      }
    }
  }
});
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
  flex-wrap: wrap;
  gap: 15px;
}

.resources-header h2 {
  margin: 0;
  font-size: 1.5rem;
}

.header-actions {
  display: flex;
  gap: 15px;
  align-items: center;
}

.resources-content {
  margin-top: 20px;
}

.plus-icon {
  font-size: 1.2em;
  margin-right: 5px;
}

/* Modal styling */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 4px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.modal-large {
  max-width: 800px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
}

.modal-body {
  padding: 20px;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #888;
}

.close-button:hover {
  color: #333;
}
</style>
