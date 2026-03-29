<template>
  <button class="btn" :class="variantClass" :disabled="disabled || loading" :type="type">
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
    validator: (value) => ['primary', 'outline'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const variantClass = computed(() =>
  props.variant === 'outline' ? 'btn-outline-primary rounded-3 px-4' : 'btn-primary rounded-3 px-4',
)
</script>
