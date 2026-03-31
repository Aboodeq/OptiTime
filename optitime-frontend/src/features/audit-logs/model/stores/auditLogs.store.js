import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { auditLogsService } from '@/features/audit-logs/api/auditLogs.service'

export const useAuditLogsStore = defineStore('auditLogs', () => {
  const logs = ref([])
  const initialized = ref(false)

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
    logs.value = await auditLogsService.getAuditLogs()
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
