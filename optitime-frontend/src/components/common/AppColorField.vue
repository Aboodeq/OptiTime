<template>
  <div class="app-color-field">
    <label class="form-label fw-semibold">{{ label }}</label>
    <div class="d-flex align-items-center gap-2">
      <input
        :value="modelValue"
        type="color"
        class="form-control form-control-color"
        :disabled="disabled"
        @input="$emit('update:modelValue', $event.target.value)"
      />
      <span class="small text-secondary">{{ modelValue }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  modelValue: { type: String, default: '#4361ee' },
  disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>
