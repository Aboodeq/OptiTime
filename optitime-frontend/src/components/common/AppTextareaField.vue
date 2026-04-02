<template>
  <div class="app-textarea-field">
    <label :for="inputId" class="form-label fw-semibold">{{ label }}</label>
    <textarea
      :id="inputId"
      class="form-control"
      :rows="rows"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      @input="$emit('update:modelValue', $event.target.value)"
    ></textarea>
  </div>
</template>

<script setup>
defineProps({
  inputId: { type: String, required: true },
  label: { type: String, required: true },
  modelValue: { type: String, default: '' },
  rows: { type: Number, default: 3 },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>
