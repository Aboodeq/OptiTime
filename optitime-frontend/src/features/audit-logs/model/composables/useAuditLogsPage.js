import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useAuditLogsStore } from '@/features/audit-logs/model/stores/auditLogs.store'

function byNewestDate(a, b) {
  return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
}

export function useAuditLogsPage() {
  const auditLogsStore = useAuditLogsStore()
  auditLogsStore.ensureInitialized()

  const { logs, logsCount, successCount, warningCount, errorCount } = storeToRefs(auditLogsStore)

  const search = ref('')
  const eventType = ref('all')
  const moduleKey = ref('all')

  const moduleOptions = computed(() => {
    const keys = [...new Set(logs.value.map((item) => item.module).filter(Boolean))]
    return keys.sort((a, b) => a.localeCompare(b))
  })

  const filteredLogs = computed(() => {
    const q = search.value.trim().toLowerCase()
    return [...logs.value]
      .filter((item) => eventType.value === 'all' || item.event_type === eventType.value)
      .filter((item) => moduleKey.value === 'all' || item.module === moduleKey.value)
      .filter((item) => {
        if (!q) return true
        const haystack = [
          item.actor_name,
          item.module,
          item.action,
          item.description,
          item.target_table,
          item.target_id,
          item.request_id,
          item.ip_address,
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase()
        return haystack.includes(q)
      })
      .sort(byNewestDate)
  })

  return {
    logsCount,
    successCount,
    warningCount,
    errorCount,
    search,
    eventType,
    moduleKey,
    moduleOptions,
    filteredLogs,
  }
}
