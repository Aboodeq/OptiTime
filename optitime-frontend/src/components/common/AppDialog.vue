<template>
  <Teleport to="body">
    <Transition name="dialog-fade">
      <div v-if="open" class="app-dialog" @keydown.esc="$emit('close')">
        <AppButton
          class="app-dialog__overlay"
          variant="plain"
          type="button"
          :aria-label="title"
          @click="$emit('close')"
        />
        <section
          class="app-dialog__panel"
          role="dialog"
          aria-modal="true"
          :aria-label="title"
          :style="dialogToneStyle"
        >
          <header class="app-dialog__header">
            <h2 class="app-dialog__title">{{ title }}</h2>
            <AppIconButton
              class="app-dialog__close"
              icon="bi bi-x-lg"
              variant="primary"
              size="md"
              :title="title"
              @click="$emit('close')"
            />
          </header>
          <div class="app-dialog__body">
            <slot />
          </div>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import AppButton from '@/components/common/AppButton.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import { useAuthStore } from '@/store/auth.store'

defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
})

defineEmits(['close'])

const authStore = useAuthStore()
const dialogToneStyle = computed(() => {
  const tone = authStore.roleColor
  if (!tone) return undefined
  return {
    '--app-button-brand-start': tone,
    '--app-button-brand-end': shadeHexColor(tone, -16),
    '--app-button-brand-shadow': hexToRgba(tone, 0.35),
    '--app-button-brand-shadow-hover': hexToRgba(tone, 0.45),
    '--app-button-outline-color': tone,
    '--app-button-outline-border': hexToRgba(tone, 0.35),
    '--app-button-outline-hover-bg': hexToRgba(tone, 0.08),
    '--app-icon-color': tone,
    '--app-icon-border': hexToRgba(tone, 0.26),
    '--app-icon-bg-hover': hexToRgba(tone, 0.08),
    '--app-icon-border-hover': hexToRgba(tone, 0.42),
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
.app-dialog {
  position: fixed;
  inset: 0;
  z-index: 120;
  display: grid;
  place-items: center;
  padding: 1rem;
}
.app-dialog__overlay {
  position: absolute;
  inset: 0;
  border: none;
  background: rgba(15, 23, 42, 0.48);
  border-radius: 0;
}
.app-dialog__panel {
  position: relative;
  width: min(100%, 620px);
  max-height: 88vh;
  overflow: hidden;
  background: #fff;
  border-radius: 16px;
  border: 1px solid #eef0f7;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
}
.app-dialog__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.9rem 1rem;
  border-bottom: 1px solid #eef2f7;
}
.app-dialog__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 800;
}
.app-dialog__close {
  border-color: #e2e8f0;
}
.app-dialog__body {
  padding: 1rem;
  max-height: calc(88vh - 66px);
  overflow: auto;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

.app-dialog__body::-webkit-scrollbar {
  width: 9px;
}

.app-dialog__body::-webkit-scrollbar-track {
  background: transparent;
}

.app-dialog__body::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #cbd5e1, #94a3b8);
  border-radius: 999px;
  border: 2px solid #fff;
}

.app-dialog__body::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #94a3b8, #64748b);
}

.dialog-fade-enter-active,
.dialog-fade-leave-active {
  transition: opacity 220ms ease;
}

.dialog-fade-enter-from,
.dialog-fade-leave-to {
  opacity: 0;
}

.dialog-fade-enter-from .app-dialog__panel,
.dialog-fade-leave-to .app-dialog__panel {
  transform: translateY(10px) scale(0.985);
  opacity: 0;
}

.dialog-fade-enter-active .app-dialog__panel,
.dialog-fade-leave-active .app-dialog__panel {
  transition:
    transform 240ms cubic-bezier(0.22, 1, 0.36, 1),
    opacity 240ms ease;
}
</style>
