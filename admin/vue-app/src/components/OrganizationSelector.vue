<template>
  <div class="organization-selector">
    <label for="organization-select">Organisation:</label>
    <select 
      id="organization-select" 
      v-model="selectedId" 
      @change="handleChange"
    >
      <option value="">Välj organisation</option>
      <option 
        v-for="org in organizations" 
        :key="org.id" 
        :value="org.id"
      >
        {{ org.name }}
      </option>
    </select>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'OrganizationSelector',
  
  props: {
    organizations: {
      type: Array,
      default: () => []
    },
    modelValue: {
      type: [Number, String, null],
      default: null
    }
  },
  
  emits: ['update:modelValue', 'change'],
  
  computed: {
    selectedId: {
      get() {
        return this.modelValue;
      },
      set(value) {
        this.$emit('update:modelValue', value ? parseInt(value, 10) : null);
      }
    }
  },
  
  methods: {
    handleChange() {
      this.$emit('change', this.selectedId);
    }
  }
});
</script>

<style scoped>
.organization-selector {
  display: flex;
  align-items: center;
  gap: 10px;
}

.organization-selector label {
  font-weight: bold;
  white-space: nowrap;
}

.organization-selector select {
  padding: 8px;
  border-radius: 4px;
  border: 1px solid #ddd;
  min-width: 200px;
}
</style>
