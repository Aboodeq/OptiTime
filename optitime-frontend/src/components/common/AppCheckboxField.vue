<template>
  <div class="app-checkbox-field form-check">
    <input
      :id="inputId"
      class="form-check-input"
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    <label :for="inputId" class="form-check-label">
      {{ label }}
    </label>
  </div>
</template>

<script setup>
defineProps({
  inputId: { type: String, required: true },
  label: { type: String, required: true },
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>
