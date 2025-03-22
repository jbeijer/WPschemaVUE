<template>
  <span class="role-badge" :class="roleClass">
    {{ displayRole }}
  </span>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'RoleBadge',
  
  props: {
    role: {
      type: String,
      required: true,
      validator: (value) => ['bas', 'schemaläggare', 'admin'].includes(value)
    }
  },
  
  computed: {
    displayRole() {
      const labels = {
        'bas': 'Anställd',
        'schemaläggare': 'Schemaläggare',
        'admin': 'Admin'
      };
      
      return labels[this.role] || this.role;
    },
    
    roleClass() {
      return `role-${this.role}`;
    }
  }
});
</script>

<style scoped>
.role-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 500;
  text-align: center;
  white-space: nowrap;
}

.role-bas {
  background-color: #e9ecef;
  color: #495057;
}

.role-schemaläggare {
  background-color: #cff4fc;
  color: #055160;
}

.role-admin {
  background-color: #f8d7da;
  color: #842029;
}
</style>
