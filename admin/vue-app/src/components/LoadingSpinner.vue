<template>
  <div class="loading-spinner-container">
    <div class="loading-spinner"></div>
    <div v-if="message" class="loading-message">{{ message }}</div>
  </div>
</template>

<script>
export default {
  name: 'LoadingSpinner',
  props: {
    message: {
      type: String,
      default: ''
    }
  }
};
</script>

<style scoped>
.loading-spinner-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.loading-spinner {
  border: 4px solid rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  border-top: 4px solid #0073aa;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}

.loading-message {
  margin-top: 10px;
  color: #666;
  font-size: 14px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>
