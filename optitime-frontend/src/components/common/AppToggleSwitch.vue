<template>
  <label class="app-toggle" :class="{ 'app-toggle--disabled': disabled }">
    <input
      class="app-toggle__input"
      :checked="modelValue"
      :disabled="disabled"
      type="checkbox"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    <span class="app-toggle__slider"></span>
  </label>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.app-toggle {
  position: relative;
  display: inline-block;
  width: 42px;
  height: 24px;
}

.app-toggle__input {
  position: absolute;
  inset: 0;
  margin: 0;
  opacity: 0;
  cursor: pointer;
}

.app-toggle__slider {
  position: absolute;
  inset: 0;
  cursor: pointer;
  background: #d8ddee;
  border: 1px solid #cfd6e8;
  transition: all 0.2s ease;
  border-radius: 999px;
}

.app-toggle__slider::before {
  position: absolute;
  content: '';
  height: 18px;
  width: 18px;
  inset-inline-start: 2px;
  top: 2px;
  background: #fff;
  transition:
    inset-inline-start 0.2s ease,
    box-shadow 0.2s ease;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.25);
}

.app-toggle__input:checked + .app-toggle__slider {
  background: #5f46f7;
  border-color: #5f46f7;
}

.app-toggle__input:checked + .app-toggle__slider::before {
  inset-inline-start: 22px;
}

.app-toggle__input:focus-visible + .app-toggle__slider {
  box-shadow: 0 0 0 3px rgba(95, 70, 247, 0.2);
}

.app-toggle--disabled .app-toggle__slider {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
