<template>
  <div class="action-card" :class="{ 'is-disabled': disabled }">
    <div class="action-card-header" :style="{ backgroundColor: headerColor }">
      <span v-if="icon" class="action-icon" :class="icon + '-icon'"></span>
      <h3>{{ title }}</h3>
    </div>
    <div class="action-card-content">
      <p v-if="description">{{ description }}</p>
      <slot name="content"></slot>
    </div>
    <div class="action-card-footer">
      <BaseButton
        @click="$emit('action')"
        :disabled="disabled"
        :variant="buttonVariant"
      >
        {{ buttonText }}
      </BaseButton>
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import BaseButton from '@/components/BaseButton.vue';

export default defineComponent({
  name: 'ActionCard',
  
  components: {
    BaseButton
  },
  
  props: {
    title: {
      type: String,
      required: true
    },
    description: {
      type: String,
      default: ''
    },
    icon: {
      type: String,
      default: null
    },
    buttonText: {
      type: String,
      default: 'Visa'
    },
    buttonVariant: {
      type: String,
      default: 'primary'
    },
    headerColor: {
      type: String,
      default: '#f5f5f5'
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  
  emits: ['action']
});
</script>

<style scoped>
.action-card {
  background-color: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
  transition: transform 0.2s, box-shadow 0.2s;
  overflow: hidden;
}

.action-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.action-card.is-disabled {
  opacity: 0.7;
  pointer-events: none;
}

.action-card-header {
  display: flex;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0;
  color: #333;
}

.action-card-header h3 {
  margin: 0;
  font-size: 1.1rem;
}

.action-icon {
  width: 24px;
  height: 24px;
  margin-right: 10px;
}

.calendar-icon {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23333'%3E%3Cpath d='M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z'/%3E%3C/svg%3E");
  background-size: contain;
}

.resource-icon {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23333'%3E%3Cpath d='M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z'/%3E%3C/svg%3E");
  background-size: contain;
}

.action-card-content {
  padding: 15px 20px;
  color: #555;
}

.action-card-content p {
  margin-top: 0;
}

.action-card-footer {
  padding: 15px 20px;
  background-color: #fafafa;
  border-top: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
