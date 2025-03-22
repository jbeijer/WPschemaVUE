<template>
  <div class="resources-grid">
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
        <BaseButton size="small" @click="$emit('view-schedules', resource)">
          Visa schema
        </BaseButton>
        <BaseButton size="small" @click="$emit('manage-availability', resource)">
          Hantera tillgänglighet
        </BaseButton>
        <BaseButton 
          size="small" 
          @click="$emit('edit', resource)" 
          v-if="canManage"
        >
          Redigera
        </BaseButton>
        <BaseButton 
          size="small" 
          variant="danger" 
          @click="$emit('delete', resource)" 
          v-if="canManage"
        >
          Ta bort
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';
import BaseButton from '@/components/BaseButton.vue';

export default defineComponent({
  name: 'ResourceGrid',
  
  components: {
    BaseButton
  },
  
  props: {
    resources: {
      type: Array,
      required: true
    },
    canManage: {
      type: Boolean,
      default: false
    }
  },
  
  emits: ['view-schedules', 'manage-availability', 'edit', 'delete']
});
</script>

<style scoped>
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
  flex-wrap: wrap;
  gap: 8px;
}
</style>
