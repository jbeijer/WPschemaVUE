<template>
  <div class="error-message">
    <div class="error-icon">!</div>
    <p>{{ message }}</p>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'ErrorMessage',
  
  props: {
    message: {
      type: String,
      required: true
    }
  }
});
</script>

<style scoped>
.error-message {
  display: flex;
  align-items: center;
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
  border-radius: 4px;
  padding: 15px;
  margin: 15px 0;
}

.error-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #721c24;
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-right: 10px;
  font-weight: bold;
}

.error-message p {
  margin: 0;
}
</style>
