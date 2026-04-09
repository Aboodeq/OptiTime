import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { auditLogsService } from '@/features/audit-logs/api/auditLogs.service'

export const useAuditLogsStore = defineStore('auditLogs', () => {
  const logs = ref([])
  const initialized = ref(false)

  /** Totals reflect the batch returned by the API (paginated), not the full audit history. */
  const logsCount = computed(() => logs.value.length)
  const successCount = computed(
    () => logs.value.filter((item) => item.event_type === 'success').length,
  )
  const warningCount = computed(
    () => logs.value.filter((item) => item.event_type === 'warning').length,
  )
  const errorCount = computed(() => logs.value.filter((item) => item.event_type === 'error').length)

  async function ensureInitialized() {
    if (initialized.value) return
    try {
      logs.value = await auditLogsService.getAuditLogs()
    } catch {
      logs.value = []
    }
    initialized.value = true
  }

  return {
    logs,
    logsCount,
    successCount,
    warningCount,
    errorCount,
    ensureInitialized,
  }
})
