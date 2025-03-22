<template>
  <div class="empty-state">
    <div v-if="icon" class="empty-icon" :class="icon + '-icon'"></div>
    <p class="empty-message">{{ message }}</p>
    <div class="empty-actions">
      <slot></slot>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'EmptyState',
  
  props: {
    message: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      default: null
    }
  }
});
</script>

<style scoped>
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f9f9f9;
  border: 1px solid #e5e5e5;
  border-radius: 4px;
  padding: 30px;
  text-align: center;
  margin: 20px 0;
}

.empty-icon {
  width: 48px;
  height: 48px;
  margin-bottom: 15px;
  opacity: 0.6;
}

.empty-message {
  color: #666;
  margin: 0 0 15px 0;
}

.empty-actions {
  margin-top: 10px;
}
</style>
