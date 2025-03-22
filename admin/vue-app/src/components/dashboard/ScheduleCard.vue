<template>
  <div class="schedule-card">
    <div 
      class="schedule-header" 
      :style="{ backgroundColor: schedule.resource ? schedule.resource.color : '#f5f5f5' }"
    >
      <h3>{{ schedule.title || 'Schema' }}</h3>
      <span class="schedule-date">{{ formattedDate }}</span>
    </div>
    <div class="schedule-content">
      <div class="schedule-info">
        <div class="info-item">
          <span class="label">Resurs:</span>
          <span class="value">{{ resourceName }}</span>
        </div>
        <div class="info-item">
          <span class="label">Tid:</span>
          <span class="value">{{ timeRange }}</span>
        </div>
        <div class="info-item" v-if="schedule.organization">
          <span class="label">Organisation:</span>
          <span class="value">{{ schedule.organization.name }}</span>
        </div>
        <div class="info-item" v-if="schedule.note">
          <span class="label">Anteckningar:</span>
          <span class="value">{{ schedule.note }}</span>
        </div>
      </div>
    </div>
    <div class="schedule-footer">
      <div class="schedule-actions">
        <BaseButton 
          size="small" 
          @click="$emit('view')"
        >
          Visa detaljer
        </BaseButton>
        
        <!-- Only show edit for Schemaläggare and Admin roles -->
        <BaseButton 
          v-if="canEdit"
          size="small" 
          variant="secondary"
          @click="$emit('edit')"
        >
          Redigera
        </BaseButton>
        
        <!-- Only show delete for Admin role -->
        <BaseButton 
          v-if="isAdmin"
          size="small" 
          variant="danger"
          @click="$emit('delete')"
        >
          Ta bort
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import { formatDate, formatTime } from '@/utils/date-helpers';
import BaseButton from '@/components/BaseButton.vue';
import { useStore } from 'vuex';
import { computed } from 'vue';

export default defineComponent({
  name: 'ScheduleCard',
  
  components: {
    BaseButton
  },
  
  props: {
    schedule: {
      type: Object,
      required: true
    }
  },
  
  emits: ['view', 'edit', 'delete'],
  
  setup(props) {
    const store = useStore();
    
    // Check user permissions based on the user role
    const userRole = computed(() => store.getters.getUserRole(props.schedule.organization_id));
    const canEdit = computed(() => 
      ['schemaläggare', 'admin'].includes(userRole.value) || 
      props.schedule.user_id === store.state.user.id
    );
    const isAdmin = computed(() => userRole.value === 'admin');
    
    const resourceName = computed(() => 
      props.schedule.resource ? props.schedule.resource.name : 'Ingen resurs'
    );
    
    const formattedDate = computed(() => {
      if (!props.schedule.start_time) return 'Okänt datum';
      return formatDate(props.schedule.start_time);
    });
    
    const timeRange = computed(() => {
      if (!props.schedule.start_time || !props.schedule.end_time) return 'Okänd tid';
      return `${formatTime(props.schedule.start_time)} - ${formatTime(props.schedule.end_time)}`;
    });
    
    return {
      resourceName,
      formattedDate,
      timeRange,
      canEdit,
      isAdmin
    };
  }
});
</script>

<style scoped>
.schedule-card {
  background-color: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
  overflow: hidden;
}

.schedule-header {
  padding: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: white;
  position: relative;
}

.schedule-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
}

.schedule-date {
  font-size: 0.9rem;
  opacity: 0.9;
  font-weight: 500;
}

.schedule-content {
  padding: 15px;
}

.schedule-info {
  display: grid;
  gap: 10px;
}

.info-item {
  display: flex;
  flex-direction: column;
}

.label {
  font-weight: 600;
  font-size: 0.85rem;
  color: #555;
  margin-bottom: 2px;
}

.value {
  color: #333;
}

.schedule-footer {
  background-color: #f9f9f9;
  border-top: 1px solid #e0e0e0;
  padding: 10px 15px;
}

.schedule-actions {
  display: flex;
  gap: 8px;
}

@media (min-width: 768px) {
  .schedule-info {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
