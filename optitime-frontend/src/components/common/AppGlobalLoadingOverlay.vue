<template>
  <Transition name="global-loading-fade">
    <div v-if="isLoading" class="global-loading-overlay" role="status" aria-live="polite">
      <div class="global-loading-overlay__panel">
        <div class="global-loading-overlay__spinner" />
        <h2 class="global-loading-overlay__title">
          {{ title }}
        </h2>
        <p class="global-loading-overlay__text">
          {{ subtitle }}
        </p>
        <div v-if="generationState.active" class="global-loading-overlay__meta">
          <div><strong>Algorithm:</strong> {{ generationState.algorithm || '-' }}</div>
          <div><strong>Job ID:</strong> {{ generationState.jobId || '-' }}</div>
          <div><strong>Status:</strong> {{ generationState.status }}</div>
          <div><strong>Progress:</strong> {{ generationState.progress }}%</div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useLoadingStore } from '@/store/loading.store'

const loadingStore = useLoadingStore()
const { isLoading, generationState, activeRequests } = storeToRefs(loadingStore)

const title = computed(() =>
  generationState.value.active ? 'Generating schedule' : 'Loading',
)

const subtitle = computed(() => {
  if (generationState.value.active) {
    return generationState.value.message || 'Please wait while generation is in progress.'
  }
  const count = activeRequests.value
  if (count > 1) return `${count} requests in progress...`
  return 'Please wait...'
})
</script>

<style scoped>
.global-loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 3000;
  background: rgba(15, 23, 42, 0.45);
  display: grid;
  place-items: center;
  padding: 1rem;
}

.global-loading-overlay__panel {
  width: min(95vw, 520px);
  border-radius: 16px;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 24px 56px rgba(2, 6, 23, 0.28);
  padding: 1rem 1.1rem;
  text-align: center;
}

.global-loading-overlay__spinner {
  width: 42px;
  height: 42px;
  margin: 0 auto 0.75rem;
  border-radius: 50%;
  border: 4px solid #dbeafe;
  border-top-color: #2563eb;
  animation: spin 0.9s linear infinite;
}

.global-loading-overlay__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
}

.global-loading-overlay__text {
  margin: 0.45rem 0 0;
  color: #475569;
}

.global-loading-overlay__meta {
  margin-top: 0.85rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e2e8f0;
  color: #1e293b;
  text-align: start;
  display: grid;
  gap: 0.3rem;
  font-size: 0.93rem;
}

.global-loading-fade-enter-active,
.global-loading-fade-leave-active {
  transition: opacity 160ms ease;
}

.global-loading-fade-enter-from,
.global-loading-fade-leave-to {
  opacity: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
