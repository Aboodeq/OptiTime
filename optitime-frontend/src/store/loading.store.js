import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

let requestSeq = 0

export const useLoadingStore = defineStore('loading', () => {
  const activeRequests = ref(0)
  const generationState = ref({
    active: false,
    algorithm: '',
    jobId: '',
    status: 'queued',
    progress: 0,
    message: '',
  })

  const isLoading = computed(() => activeRequests.value > 0 || generationState.value.active)
  const shouldBlockLeaving = computed(() => generationState.value.active)

  function beginRequest() {
    activeRequests.value += 1
    requestSeq += 1
    return requestSeq
  }

  function endRequest() {
    activeRequests.value = Math.max(0, activeRequests.value - 1)
  }

  function startGenerationTracking(algorithm) {
    generationState.value = {
      active: true,
      algorithm: `${algorithm || 'genetic'}`.toLowerCase(),
      jobId: '',
      status: 'queued',
      progress: 0,
      message: 'Waiting for cron worker to start processing.',
    }
  }

  function updateGenerationTracking(payload = {}) {
    if (!generationState.value.active) return
    const nextStatus = `${payload.status || generationState.value.status || 'queued'}`.toLowerCase()
    generationState.value = {
      ...generationState.value,
      jobId: payload.jobId ?? generationState.value.jobId,
      status: nextStatus,
      progress:
        Number.isFinite(Number(payload.progress))
          ? Math.max(0, Math.min(100, Number(payload.progress)))
          : generationState.value.progress,
      message: payload.message ?? statusToMessage(nextStatus),
    }
  }

  function finishGenerationTracking() {
    generationState.value = {
      active: false,
      algorithm: '',
      jobId: '',
      status: 'queued',
      progress: 0,
      message: '',
    }
  }

  return {
    isLoading,
    shouldBlockLeaving,
    activeRequests,
    generationState,
    beginRequest,
    endRequest,
    startGenerationTracking,
    updateGenerationTracking,
    finishGenerationTracking,
  }
})

function statusToMessage(status) {
  if (status === 'queued') return 'Queued. Waiting for cron runner.'
  if (status === 'running') return 'Running schedule generation via cron worker.'
  if (status === 'completed') return 'Generation completed.'
  if (status === 'failed') return 'Generation failed.'
  return 'Processing generation job.'
}
