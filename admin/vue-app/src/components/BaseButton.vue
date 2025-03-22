<template>
  <button
    :class="[
      'btn',
      variant ? `btn-${variant}` : '',
      size ? `btn-${size}` : '',
      { 'btn-loading': loading }
    ]"
    :disabled="disabled || loading"
    :type="type"
    @click="onClick"
  >
    <span v-if="loading" class="spinner"></span>
    <span :class="{ 'invisible': loading }">
      <slot></slot>
    </span>
  </button>
</template>

<script>
export default {
  name: 'BaseButton',
  props: {
    variant: {
      type: String,
      default: '',
      validator: (value) => ['primary', 'secondary', 'danger', ''].includes(value)
    },
    size: {
      type: String,
      default: '',
      validator: (value) => ['small', ''].includes(value)
    },
    disabled: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    type: {
      type: String,
      default: 'button',
      validator: (value) => ['button', 'submit', 'reset'].includes(value)
    }
  },
  methods: {
    onClick(event) {
      if (!this.disabled && !this.loading) {
        this.$emit('click', event);
      }
    }
  }
};
</script>

<style scoped>
.btn {
  display: inline-block;
  background-color: #0073aa;
  color: #fff;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  transition: background-color 0.2s, opacity 0.2s;
  position: relative;
  min-width: 80px;
}

.btn:hover {
  background-color: #005177;
}

.btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn-primary {
  background-color: #0073aa;
}

.btn-secondary {
  background-color: #6c757d;
}

.btn-danger {
  background-color: #dc3545;
}

.btn-danger:hover {
  background-color: #bd2130;
}

.btn-small {
  padding: 4px 12px;
  font-size: 0.9em;
  min-width: 70px;
}

.btn-loading {
  color: transparent;
}

.spinner {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

.btn-small .spinner {
  width: 12px;
  height: 12px;
}

.invisible {
  visibility: visible;
  opacity: 0;
}

@keyframes spin {
  to {
    transform: translate(-50%, -50%) rotate(360deg);
  }
}
</style>
