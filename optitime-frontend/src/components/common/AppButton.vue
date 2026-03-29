<template>
  <button
    class="app-button"
    :class="[variantClass, { 'app-button--block': block }]"
    :disabled="disabled || loading"
    :type="type"
    :aria-busy="loading ? 'true' : 'false'"
  >
    <span
      v-if="loading"
      class="spinner-border spinner-border-sm app-button__spinner"
      aria-hidden="true"
    ></span>
    <i v-else-if="icon" :class="[icon, 'app-button__icon']" aria-hidden="true"></i>
    <span class="app-button__label">
      {{ loading && loadingLabel ? loadingLabel : null }}
      <slot v-if="!(loading && loadingLabel)" />
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'brand',
    validator: (value) => ['brand', 'outline', 'plain'].includes(value),
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
  loadingLabel: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: '',
  },
  block: {
    type: Boolean,
    default: false,
  },
})

const variantClass = computed(() => `app-button--${props.variant}`)
</script>

<style scoped>
.app-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  border: none;
  cursor: pointer;
  font: inherit;
  font-weight: 700;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background-color 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}

.app-button--block {
  width: 100%;
}

.app-button--brand {
  background: linear-gradient(135deg, #4361ee, #3a0ca3);
  color: #fff;
  border-radius: 12px;
  padding: 14px;
  font-size: 15px;
  box-shadow: 0 4px 15px rgba(67, 97, 238, 0.35);
}

.app-button--brand:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(67, 97, 238, 0.45);
}

.app-button--outline {
  background: transparent;
  color: #4361ee;
  border: 1px solid rgba(67, 97, 238, 0.35);
  border-radius: 12px;
  padding: 12px 18px;
}

.app-button--outline:hover:not(:disabled) {
  background: rgba(67, 97, 238, 0.06);
}

.app-button--plain {
  background: transparent;
  color: inherit;
  border: none;
  padding: 0;
  box-shadow: none;
}

.app-button:disabled {
  opacity: 0.8;
  cursor: not-allowed;
}

.app-button__label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.app-button__spinner,
.app-button__icon {
  flex-shrink: 0;
}
</style>
