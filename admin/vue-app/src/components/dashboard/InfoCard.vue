<template>
  <div class="info-card">
    <div class="info-card-header">
      <span v-if="icon" class="info-icon" :class="icon + '-icon'"></span>
      <h3>{{ title }}</h3>
    </div>
    <div class="info-card-content">
      <LoadingIndicator v-if="loading" />
      <slot v-else name="content"></slot>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import LoadingIndicator from '@/components/LoadingIndicator.vue';

export default defineComponent({
  name: 'InfoCard',
  
  components: {
    LoadingIndicator
  },
  
  props: {
    title: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      default: null
    },
    loading: {
      type: Boolean,
      default: false
    }
  }
});
</script>

<style scoped>
.info-card {
  background-color: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
  overflow: hidden;
}

.info-card-header {
  display: flex;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
  background-color: #f9f9f9;
}

.info-card-header h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #23282d;
}

.info-icon {
  width: 20px;
  height: 20px;
  margin-right: 10px;
  opacity: 0.7;
}

.user-icon {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23333'%3E%3Cpath d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E");
  background-size: contain;
}

.building-icon {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23333'%3E%3Cpath d='M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z'/%3E%3C/svg%3E");
  background-size: contain;
}

.info-card-content {
  padding: 20px;
}
</style>
