<template>
  <button
    class="btn optitime-btn"
    :class="[variantClass, { 'w-100': block }]"
    :type="type"
    :disabled="isDisabled"
  >
    <span v-if="loading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
    validator: (value) => ['button', 'submit', 'reset'].includes(value),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  block: {
    type: Boolean,
    default: false,
  },
})

const isDisabled = computed(() => props.disabled || props.loading)
const variantClass = computed(() => `optitime-btn-${props.variant}`)
</script>
