<template>
  <AppButton
    class="role-btn"
    :disabled="disabled"
    :icon="role.icon"
    :loading="loading"
    :style="{ '--role-color': role.color, '--role-bg': role.bg }"
    type="button"
    variant="plain"
    @click="$emit('select', role.value)"
  >
    <span>{{ role.label }}</span>
  </AppButton>
</template>

<script setup>
import AppButton from '@/components/common/AppButton.vue'

defineProps({
  role: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['select'])
</script>

<style scoped>
.role-btn.app-button--plain {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 16px;
  border-radius: 10px;
  border: 1.5px solid transparent;
  background: var(--role-bg);
  color: var(--role-color);
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.role-btn.app-button--plain:hover:not(:disabled) {
  border-color: var(--role-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.role-btn.app-button--plain:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}
</style>
