<template>
  <Teleport to="body">
    <Transition name="dialog-fade">
      <div v-if="open" class="app-dialog" @keydown.esc="$emit('close')">
        <button class="app-dialog__overlay" type="button" @click="$emit('close')"></button>
        <section class="app-dialog__panel" role="dialog" aria-modal="true" :aria-label="title">
          <header class="app-dialog__header">
            <h2 class="app-dialog__title">{{ title }}</h2>
            <button class="app-dialog__close" type="button" @click="$emit('close')">
              <i class="bi bi-x-lg"></i>
            </button>
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
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
})

defineEmits(['close'])
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
}
.app-dialog__panel {
  position: relative;
  width: min(100%, 620px);
  max-height: 88vh;
  overflow: auto;
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
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #fff;
}
.app-dialog__body {
  padding: 1rem;
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
