<template>
  <div class="dashboard">
    <div class="dashboard-header">
      <h2>Dashboard</h2>
      <p>Välkommen till Schema Manager!</p>
    </div>
    
    <div class="dashboard-content">
      <div class="dashboard-stats">
        <div class="stat-card" v-if="currentUserInfo">
          <h3>Din information</h3>
          <p><strong>Namn:</strong> {{ currentUserInfo.display_name }}</p>
          <p><strong>E-post:</strong> {{ currentUserInfo.email }}</p>
          <p><strong>Användarnamn:</strong> {{ currentUserInfo.username }}</p>
        </div>
        
        <div class="stat-card">
          <h3>Organisationer</h3>
          <p v-if="organizationsLoading">Laddar organisationer...</p>
          <p v-else-if="organizations.length === 0">Inga organisationer hittades.</p>
          <ul v-else>
            <li v-for="org in organizations" :key="org.id">
              {{ org.name }}
              <span class="badge" v-if="currentUserInfo && currentUserInfo.organizations">
                {{ getUserRoleInOrganization(org.id) }}
              </span>
            </li>
          </ul>
        </div>
      </div>
      
      <div class="dashboard-actions">
        <div class="action-card">
          <h3>Snabbåtgärder</h3>
          <div class="action-buttons">
            <router-link :to="{ name: 'organizations' }" class="btn">
              Hantera organisationer
            </router-link>
            <router-link :to="{ name: 'resources' }" class="btn">
              Hantera resurser
            </router-link>
            <router-link :to="{ name: 'schedules' }" class="btn">
              Visa scheman
            </router-link>
          </div>
        </div>
        
        <div class="action-card">
          <h3>Mitt schema</h3>
          <p v-if="schedulesLoading">Laddar schema...</p>
          <p v-else-if="mySchedules.length === 0">Inga schemalagda arbetspass.</p>
          <ul v-else class="schedule-list">
            <li v-for="schedule in mySchedules" :key="schedule.id" class="schedule-item">
              <div class="schedule-date">
                {{ formatDate(schedule.start_time) }}
              </div>
              <div class="schedule-time">
                {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
              </div>
              <div class="schedule-resource">
                {{ schedule.resource_name || 'Okänd resurs' }}
              </div>
              <div class="schedule-status" :class="'status-' + schedule.status">
                {{ translateStatus(schedule.status) }}
              </div>
            </li>
          </ul>
          <div class="view-all">
            <router-link :to="{ name: 'schedules' }" class="btn btn-small">
              Visa alla
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useUsersStore } from '@/stores/users';
import { useOrganizationsStore } from '@/stores/organizations';
import { useSchedulesStore } from '@/stores/schedules';

export default {
  name: 'Dashboard',
  data() {
    return {
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
      try {
        // Load user info
        await this.usersStore.fetchCurrentUserInfo();
        
        // Load organizations
        await this.organizationsStore.fetchOrganizations();
        
        // Load upcoming schedules
        await this.loadMySchedules();
      } catch (error) {
        console.error('Error loading dashboard data:', error);
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
      } finally {
        this.schedulesLoading = false;
      }
    },
    formatDateForAPI(date) {
      return date.toISOString().split('T')[0];
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('sv-SE');
    },
    formatTime(dateString) {
      const date = new Date(dateString);
      return date.toLocaleTimeString('sv-SE', { hour: '2-digit', minute: '2-digit' });
    },
    translateStatus(status) {
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
    },
    getUserRoleInOrganization(orgId) {
      if (!this.currentUserInfo || !this.currentUserInfo.organizations) {
        return '';
      }
      
      const org = this.currentUserInfo.organizations.find(o => o.id === orgId);
      if (!org) {
        return '';
      }
      
      switch (org.role) {
        case 'admin':
          return 'Admin';
        case 'scheduler':
          return 'Schemaläggare';
        case 'base':
          return 'Bas';
        default:
          return org.role;
      }
    }
  }
};
</script>

<style scoped>
.dashboard {
  padding: 20px 0;
}

.dashboard-header {
  margin-bottom: 30px;
}

.dashboard-content {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

.dashboard-stats, .dashboard-actions {
  flex: 1;
  min-width: 300px;
}

.stat-card, .action-card {
  background-color: #fff;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.stat-card h3, .action-card h3 {
  margin-top: 0;
  border-bottom: 1px solid #e5e5e5;
  padding-bottom: 10px;
  margin-bottom: 15px;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.btn {
  display: inline-block;
  background-color: #0073aa;
  color: #fff;
  padding: 8px 12px;
  border-radius: 4px;
  text-decoration: none;
  text-align: center;
  transition: background-color 0.2s;
}

.btn:hover {
  background-color: #005177;
}

.btn-small {
  padding: 4px 8px;
  font-size: 0.9em;
}

.badge {
  display: inline-block;
  background-color: #e5e5e5;
  color: #333;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.8em;
  margin-left: 5px;
}

.schedule-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.schedule-item {
  display: flex;
  padding: 10px 0;
  border-bottom: 1px solid #f0f0f0;
}

.schedule-date {
  width: 100px;
  font-weight: bold;
}

.schedule-time {
  width: 150px;
}

.schedule-resource {
  flex: 1;
}

.schedule-status {
  width: 100px;
  text-align: right;
}

.status-scheduled {
  color: #0073aa;
}

.status-confirmed {
  color: #46b450;
}

.status-completed {
  color: #999;
}

.view-all {
  margin-top: 15px;
  text-align: right;
}
</style>
