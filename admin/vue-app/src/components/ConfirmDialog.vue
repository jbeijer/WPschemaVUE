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
        <BaseButton variant="secondary" @click="cancel">
          {{ cancelText }}
        </BaseButton>
        <BaseButton :variant="confirmButtonVariant" @click="confirm" :loading="loading">
          {{ loading ? loadingText : confirmText }}
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script>
import BaseButton from '@/components/BaseButton.vue';

export default {
  name: 'ConfirmDialog',
  components: {
    BaseButton
  },
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
  computed: {
    confirmButtonVariant() {
      if (this.confirmButtonClass === 'btn-primary') return 'primary';
      if (this.confirmButtonClass === 'btn-danger') return 'danger';
      if (this.confirmButtonClass === 'btn-secondary') return 'secondary';
      return '';
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

.btn,
.btn-primary,
.btn-primary:hover,
.btn-secondary,
.btn-secondary:hover,
.btn-danger,
.btn-danger:hover,
.btn:disabled {
  display: none;
}
</style>
