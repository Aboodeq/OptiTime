<template>
  <input
    class="form-control"
    :class="{ 'is-invalid': invalid }"
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :autocomplete="autocomplete"
    :disabled="disabled"
    v-bind="$attrs"
    @input="$emit('update:modelValue', $event.target.value)"
    @blur="$emit('blur', $event)"
  />
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  autocomplete: {
    type: String,
    default: 'off',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  invalid: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:modelValue', 'blur'])
</script>
