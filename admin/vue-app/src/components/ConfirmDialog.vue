<template>
  <div class="confirm-dialog-backdrop" v-if="show" @click="handleBackdropClick">
    <div class="confirm-dialog" @click.stop>
      <div class="confirm-dialog-header">
        <h3>{{ title }}</h3>
        <button class="close-button" @click="cancel">&times;</button>
      </div>
      <div class="confirm-dialog-body">
        <p>{{ message }}</p>
        <div v-if="isWarning" class="warning-message">
          {{ warningMessage || 'Denna åtgärd kan inte ångras!' }}
        </div>
      </div>
      <div class="confirm-dialog-footer">
        <button class="btn btn-secondary" @click="cancel">
          {{ cancelText }}
        </button>
        <button class="btn" :class="confirmButtonClass" @click="confirm" :disabled="loading">
          {{ loading ? loadingText : confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmDialog',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: 'Bekräfta'
    },
    message: {
      type: String,
      default: 'Är du säker?'
    },
    confirmText: {
      type: String,
      default: 'Bekräfta'
    },
    cancelText: {
      type: String,
      default: 'Avbryt'
    },
    confirmButtonClass: {
      type: String,
      default: 'btn-primary'
    },
    loading: {
      type: Boolean,
      default: false
    },
    loadingText: {
      type: String,
      default: 'Bearbetar...'
    },
    isWarning: {
      type: Boolean,
      default: false
    },
    warningMessage: {
      type: String,
      default: ''
    },
    closeOnBackdropClick: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    confirm() {
      this.$emit('confirm');
    },
    cancel() {
      this.$emit('cancel');
    },
    handleBackdropClick() {
      if (this.closeOnBackdropClick) {
        this.cancel();
      }
    }
  }
};
</script>

<style scoped>
.confirm-dialog-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.confirm-dialog {
  background-color: #fff;
  border-radius: 4px;
  width: 400px;
  max-width: 90%;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.confirm-dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e5e5e5;
}

.confirm-dialog-header h3 {
  margin: 0;
  font-size: 1.2em;
}

.close-button {
  background: none;
  border: none;
  font-size: 1.5em;
  cursor: pointer;
  color: #6c757d;
}

.confirm-dialog-body {
  padding: 20px;
}

.warning-message {
  margin-top: 10px;
  color: #dc3545;
  font-weight: bold;
}

.confirm-dialog-footer {
  padding: 15px 20px;
  border-top: 1px solid #e5e5e5;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn {
  display: inline-block;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #0073aa;
  color: #fff;
}

.btn-primary:hover {
  background-color: #005177;
}

.btn-secondary {
  background-color: #6c757d;
  color: #fff;
}

.btn-secondary:hover {
  background-color: #5a6268;
}

.btn-danger {
  background-color: #dc3545;
  color: #fff;
}

.btn-danger:hover {
  background-color: #bd2130;
}

.btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}
</style>
