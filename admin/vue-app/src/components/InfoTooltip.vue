<template>
  <div class="info-tooltip">
    <div class="info-icon" @mouseenter="showTooltip" @mouseleave="hideTooltip">i</div>
    <div class="tooltip-content" v-if="isVisible">
      {{ message }}
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'InfoTooltip',
  
  props: {
    message: {
      type: String,
      required: true
    }
  },
  
  data() {
    return {
      isVisible: false
    };
  },
  
  methods: {
    showTooltip() {
      this.isVisible = true;
    },
    
    hideTooltip() {
      this.isVisible = false;
    }
  }
});
</script>

<style scoped>
.info-tooltip {
  position: relative;
  display: inline-block;
}

.info-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background-color: #0073aa;
  color: white;
  font-size: 12px;
  font-weight: bold;
  cursor: help;
}

.tooltip-content {
  position: absolute;
  top: -5px;
  left: 30px;
  background-color: #333;
  color: white;
  padding: 8px 12px;
  border-radius: 4px;
  font-size: 14px;
  width: 250px;
  z-index: 1000;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.tooltip-content::before {
  content: '';
  position: absolute;
  top: 10px;
  left: -6px;
  border-width: 6px 6px 6px 0;
  border-style: solid;
  border-color: transparent #333 transparent transparent;
}
</style>
