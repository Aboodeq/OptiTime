<template>
  <div class="toggle-item-row">
    <div class="toggle-item-row__leading">
      <i v-if="icon" :class="[icon, 'toggle-item-row__icon']"></i>
      <span v-else class="toggle-item-row__icon toggle-item-row__icon--placeholder"></span>
    </div>
    <div class="toggle-item-row__content">
      <div class="toggle-item-row__title">{{ title }}</div>
      <div v-if="description" class="toggle-item-row__description">{{ description }}</div>
    </div>
    <div class="toggle-item-row__meta">
      <slot name="meta"></slot>
      <AppToggleSwitch :model-value="modelValue" :disabled="disabled" @update:model-value="emitToggle" />
    </div>
  </div>
</template>

<script setup>
import AppToggleSwitch from '@/components/common/AppToggleSwitch.vue'

defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
const emitToggle = (value) => emit('update:modelValue', value)
</script>

<style scoped>
.toggle-item-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.75rem;
  align-items: center;
  padding: 0.55rem 0.6rem;
  border-radius: 12px;
}

.toggle-item-row:hover {
  background: #f8f9fe;
}

.toggle-item-row__title {
  font-weight: 700;
  font-size: 0.92rem;
}

.toggle-item-row__description {
  margin-top: 0.15rem;
  color: #98a0b3;
  font-size: 0.75rem;
}

.toggle-item-row__meta {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.toggle-item-row__leading {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #eef1ff;
  border: 1px solid #dfe5ff;
}

.toggle-item-row__icon {
  color: #5d4cf4;
  font-size: 1rem;
}

.toggle-item-row__icon--placeholder {
  opacity: 0.4;
}

@media (max-width: 576px) {
  .toggle-item-row {
    grid-template-columns: auto 1fr;
  }

  .toggle-item-row__meta {
    grid-column: 1 / -1;
    justify-content: flex-end;
  }
}
</style>
