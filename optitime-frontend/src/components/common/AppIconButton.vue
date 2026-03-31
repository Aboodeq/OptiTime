<template>
  <button
    class="app-icon-button"
    :class="[sizeClass, variantClass]"
    :style="inlineStyle"
    :type="type"
    :disabled="disabled"
    :title="title"
    :aria-label="ariaLabel || title"
    @click="$emit('click', $event)"
  >
    <i :class="icon" aria-hidden="true"></i>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  icon: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
  ariaLabel: {
    type: String,
    default: '',
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'danger', 'neutral'].includes(value),
  },
  size: {
    type: String,
    default: 'sm',
    validator: (value) => ['sm', 'md'].includes(value),
  },
  type: {
    type: String,
    default: 'button',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  toneColor: {
    type: String,
    default: '',
  },
})

defineEmits(['click'])

const variantClass = computed(() => `app-icon-button--${props.variant}`)
const sizeClass = computed(() => `app-icon-button--${props.size}`)
const inlineStyle = computed(() => {
  if (props.variant !== 'primary' || !props.toneColor) return undefined
  return {
    '--app-icon-color': props.toneColor,
    '--app-icon-border': hexToRgba(props.toneColor, 0.26),
    '--app-icon-bg-hover': hexToRgba(props.toneColor, 0.08),
    '--app-icon-border-hover': hexToRgba(props.toneColor, 0.42),
  }
})

function hexToRgba(hex, alpha) {
  const normalized = normalizeHex(hex)
  if (!normalized) return `rgba(79, 70, 229, ${alpha})`
  const intVal = Number.parseInt(normalized, 16)
  const r = (intVal >> 16) & 255
  const g = (intVal >> 8) & 255
  const b = intVal & 255
  return `rgba(${r}, ${g}, ${b}, ${alpha})`
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
.app-icon-button {
  border-radius: 9px;
  border: 1px solid transparent;
  background: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.app-icon-button--sm {
  width: 30px;
  height: 30px;
  font-size: 12px;
}

.app-icon-button--md {
  width: 34px;
  height: 34px;
  font-size: 13px;
}

.app-icon-button--primary {
  color: var(--app-icon-color, #4f46e5);
  border-color: var(--app-icon-border, #dbe2f3);
}

.app-icon-button--primary:hover:not(:disabled) {
  background: var(--app-icon-bg-hover, #eef2ff);
  border-color: var(--app-icon-border-hover, #a5b4fc);
}

.app-icon-button--danger {
  color: #be123c;
  border-color: #fecdd3;
}

.app-icon-button--danger:hover:not(:disabled) {
  background: #fff1f2;
  border-color: #fda4af;
}

.app-icon-button--neutral {
  color: #475569;
  border-color: #cbd5e1;
}

.app-icon-button--neutral:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #94a3b8;
}

.app-icon-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
