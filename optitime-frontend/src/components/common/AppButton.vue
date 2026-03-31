<template>
  <button
    class="app-button"
    :class="[variantClass, { 'app-button--block': block }]"
    :style="inlineStyle"
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
  toneColor: {
    type: String,
    default: '',
  },
})

const variantClass = computed(() => `app-button--${props.variant}`)
const inlineStyle = computed(() => {
  if (props.variant !== 'brand' || !props.toneColor) return undefined
  return {
    '--app-button-brand-start': props.toneColor,
    '--app-button-brand-end': shadeHexColor(props.toneColor, -16),
    '--app-button-brand-shadow': hexToRgba(props.toneColor, 0.35),
    '--app-button-brand-shadow-hover': hexToRgba(props.toneColor, 0.45),
  }
})

function hexToRgba(hex, alpha) {
  const normalized = normalizeHex(hex)
  if (!normalized) return `rgba(67, 97, 238, ${alpha})`
  const intVal = Number.parseInt(normalized, 16)
  const r = (intVal >> 16) & 255
  const g = (intVal >> 8) & 255
  const b = intVal & 255
  return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

function shadeHexColor(hex, percent) {
  const normalized = normalizeHex(hex)
  if (!normalized) return '#3a0ca3'
  const num = Number.parseInt(normalized, 16)
  const amt = Math.round(2.55 * percent)
  const r = Math.min(255, Math.max(0, (num >> 16) + amt))
  const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00ff) + amt))
  const b = Math.min(255, Math.max(0, (num & 0x0000ff) + amt))
  return `#${(0x1000000 + (r << 16) + (g << 8) + b).toString(16).slice(1)}`
}

function normalizeHex(hex) {
  if (typeof hex !== 'string') return null
  const cleaned = hex.replace('#', '')
  if (!/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/.test(cleaned)) return null
  if (cleaned.length === 3) {
    return cleaned
      .split('')
      .map((char) => char + char)
      .join('')
  }
  return cleaned
}
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
  background: linear-gradient(
    135deg,
    var(--app-button-brand-start, #4361ee),
    var(--app-button-brand-end, #3a0ca3)
  );
  color: #fff;
  border-radius: 12px;
  padding: 14px;
  font-size: 15px;
  box-shadow: 0 4px 15px var(--app-button-brand-shadow, rgba(67, 97, 238, 0.35));
}

.app-button--brand:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px var(--app-button-brand-shadow-hover, rgba(67, 97, 238, 0.45));
}

.app-button--outline {
  background: transparent;
  color: var(--app-button-outline-color, #4361ee);
  border: 1px solid var(--app-button-outline-border, rgba(67, 97, 238, 0.35));
  border-radius: 12px;
  padding: 12px 18px;
}

.app-button--outline:hover:not(:disabled) {
  background: var(--app-button-outline-hover-bg, rgba(67, 97, 238, 0.06));
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
