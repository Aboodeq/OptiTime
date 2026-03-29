<template>
  <div class="form-field">
    <label v-if="label" :for="forId" class="form-label fw-semibold text-dark mb-2">
      {{ label }}
    </label>
    <slot />
    <div v-if="error" class="invalid-feedback d-block">
      {{ error }}
    </div>
    <div v-else-if="hint" class="form-text">
      {{ hint }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    default: '',
  },
  forId: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
})
</script>
