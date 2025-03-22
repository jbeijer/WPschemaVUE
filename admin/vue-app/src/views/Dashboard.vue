<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h2>Dashboard</h2>
      <p>Välkommen till Schema Manager!</p>
    </div>
    
    <!-- Loading and Error States -->
    <LoadingIndicator v-if="loading" message="Laddar dashboard..." />
    <ErrorMessage v-if="error" :message="error" />
    
    <div class="dashboard-content" v-if="!loading && !error">
      <div class="dashboard-stats">
        <!-- User Information Card -->
        <InfoCard
          v-if="currentUserInfo"
          title="Din information"
          icon="user"
        >
          <template #content>
            <p><strong>Namn:</strong> {{ currentUserInfo.display_name }}</p>
            <p><strong>E-post:</strong> {{ currentUserInfo.email }}</p>
            <p><strong>Användarnamn:</strong> {{ currentUserInfo.username }}</p>
          </template>
        </InfoCard>
        
        <!-- Organizations Card -->
        <InfoCard 
          title="Organisationer"
          icon="building"
          :loading="organizationsLoading"
        >
          <template #content>
            <EmptyState 
              v-if="!organizationsLoading && organizations.length === 0"
              message="Inga organisationer hittades."
              icon="building"
            />
            
            <ul v-else-if="!organizationsLoading" class="organization-list">
              <li v-for="org in organizations" :key="org.id" class="organization-item">
                <span class="organization-name">{{ org.name }}</span>
                <RoleBadge 
                  v-if="currentUserInfo && currentUserInfo.organizations"
                  :role="getUserRoleInOrganization(org.id)"
                />
              </li>
            </ul>
          </template>
        </InfoCard>
      </div>
      
      <div class="dashboard-actions">
        <!-- Quick Actions Card -->
        <ActionCard title="Snabbåtgärder" icon="lightning">
          <template #actions>
            <router-link :to="{ name: 'organizations' }" class="action-button">
              <span class="action-icon building-icon"></span>
              Hantera organisationer
            </router-link>
            <router-link :to="{ name: 'resources' }" class="action-button">
              <span class="action-icon resource-icon"></span>
              Hantera resurser
            </router-link>
            <router-link :to="{ name: 'schedules' }" class="action-button">
              <span class="action-icon calendar-icon"></span>
              Visa scheman
            </router-link>
          </template>
        </ActionCard>
        
        <!-- My Schedule Card -->
        <ScheduleCard
          title="Mitt schema"
          :schedules="mySchedules"
          :loading="schedulesLoading"
          @view-all="navigateToSchedules"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import { useUsersStore } from '@/stores/users';
import { useOrganizationsStore } from '@/stores/organizations';
import { useSchedulesStore } from '@/stores/schedules';
import LoadingIndicator from '@/components/LoadingIndicator.vue';
import ErrorMessage from '@/components/ErrorMessage.vue';
import InfoCard from '@/components/dashboard/InfoCard.vue';
import ActionCard from '@/components/dashboard/ActionCard.vue';
import ScheduleCard from '@/components/dashboard/ScheduleCard.vue';
import EmptyState from '@/components/EmptyState.vue';
import RoleBadge from '@/components/RoleBadge.vue';

export default defineComponent({
  name: 'Dashboard',
  
  components: {
    LoadingIndicator,
    ErrorMessage,
    InfoCard,
    ActionCard,
    ScheduleCard,
    EmptyState,
    RoleBadge
  },
  
  data() {
    return {
      loading: true,
      error: null,
      mySchedules: [],
      schedulesLoading: false
    };
  },
  
  computed: {
    currentUserInfo() {
      return this.usersStore.currentUserInfo;
    },
    
    organizations() {
      return this.organizationsStore.organizations;
    },
    
    organizationsLoading() {
      return this.organizationsStore.loading;
    }
  },
  
  created() {
    this.usersStore = useUsersStore();
    this.organizationsStore = useOrganizationsStore();
    this.schedulesStore = useSchedulesStore();
    
    this.loadData();
  },
  
  methods: {
    async loadData() {
      this.loading = true;
      this.error = null;
      
      try {
        // Load user info
        await this.usersStore.fetchCurrentUserInfo();
        
        // Load organizations
        await this.organizationsStore.fetchOrganizations();
        
        // Load upcoming schedules
        await this.loadMySchedules();
      } catch (error) {
        console.error('Error loading dashboard data:', error);
        this.error = 'Kunde inte ladda dashboard-data: ' + (error.message || 'Okänt fel');
      } finally {
        this.loading = false;
      }
    },
    
    async loadMySchedules() {
      this.schedulesLoading = true;
      
      try {
        // Get today's date
        const today = new Date();
        
        // Get date 30 days from now
        const endDate = new Date();
        endDate.setDate(today.getDate() + 30);
        
        // Format dates as YYYY-MM-DD
        const startDateStr = this.formatDateForAPI(today);
        const endDateStr = this.formatDateForAPI(endDate);
        
        // Fetch schedules
        await this.schedulesStore.fetchMySchedules(startDateStr, endDateStr);
        
        // Get only the next 5 schedules
        this.mySchedules = this.schedulesStore.schedules.slice(0, 5);
      } catch (error) {
        console.error('Error loading schedules:', error);
        this.error = 'Kunde inte ladda schema: ' + (error.message || 'Okänt fel');
      } finally {
        this.schedulesLoading = false;
      }
    },
    
    formatDateForAPI(date) {
      return date.toISOString().split('T')[0];
    },
    
    getUserRoleInOrganization(orgId) {
      if (!this.currentUserInfo || !this.currentUserInfo.organizations) {
        return '';
      }
      
      const org = this.currentUserInfo.organizations.find(o => o.id === orgId);
      if (!org) {
        return '';
      }
      
      return org.role;
    },
    
    navigateToSchedules() {
      this.$router.push({ name: 'schedules' });
    }
  }
});
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.dashboard-header {
  margin-bottom: 24px;
}

.dashboard-header h2 {
  margin: 0 0 8px 0;
  font-size: 1.75rem;
  color: #23282d;
}

.dashboard-header p {
  margin: 0;
  color: #50575e;
}

.dashboard-content {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

.dashboard-stats,
.dashboard-actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

.organization-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.organization-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}

.organization-item:last-child {
  border-bottom: none;
}

.organization-name {
  font-weight: 500;
}

.action-button {
  display: flex;
  align-items: center;
  padding: 12px;
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  color: #495057;
  text-decoration: none;
  font-weight: 500;
  margin-bottom: 10px;
  transition: all 0.2s ease;
}

.action-button:hover {
  background-color: #e9ecef;
  transform: translateY(-2px);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.action-icon {
  width: 20px;
  height: 20px;
  margin-right: 10px;
  opacity: 0.7;
}

/* Responsive layout for larger screens */
@media (min-width: 768px) {
  .dashboard-content {
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }
  
  .dashboard-stats,
  .dashboard-actions {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 1200px) {
  .dashboard-stats,
  .dashboard-actions {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
