<template>
  <AppShell :page-title="t('routes.auditLogs')">
    <section class="dashboard-card w-100">
      <div class="audit-toolbar">
        <div>
          <h1 class="h4 fw-bold mb-1">{{ t('pages.auditLogs.title') }}</h1>
          <p class="dashboard-card__meta mb-0">{{ t('pages.auditLogs.subtitle') }}</p>
        </div>
      </div>

      <AppStatsGrid :cards="statsCards" class="mb-3 mt-3" />

      <div class="audit-table-card">
        <div class="audit-filters p-3 border-bottom">
          <AppAutocompleteField
            input-id="audit-logs-event-type-filter"
            :label="t('pages.auditLogs.filters.eventType')"
            :model-value="eventType"
            :options="eventTypeOptions"
            :placeholder="t('pages.auditLogs.filters.eventTypePlaceholder')"
            :empty-text="t('pages.auditLogs.filters.noEventTypeFound')"
            @update:model-value="eventType = $event"
          />
          <AppAutocompleteField
            input-id="audit-logs-module-filter"
            :label="t('pages.auditLogs.filters.module')"
            :model-value="moduleKey"
            :options="moduleFilterOptions"
            :placeholder="t('pages.auditLogs.filters.modulePlaceholder')"
            :empty-text="t('pages.auditLogs.filters.noModuleFound')"
            @update:model-value="moduleKey = $event"
          />
        </div>

        <AppDataTable
          :columns="tableColumns"
          :rows="filteredLogs"
          row-key="id"
          :empty-text="t('pages.auditLogs.table.empty')"
          :show-search="canViewAuditLogs"
          :search-value="search"
          :search-placeholder="t('pages.auditLogs.actions.searchPlaceholder')"
          @update:search-value="search = $event"
        >
          <template #cell-created-at="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
          <template #cell-actor="{ row }">
            {{ row.actor_name || 'System' }}
          </template>
          <template #cell-event-type="{ row }">
            <span :class="eventTypeBadgeClass(row.event_type)">
              {{ t(`pages.auditLogs.eventTypes.${row.event_type}`) }}
            </span>
          </template>
          <template #cell-module="{ row }">
            <code>{{ row.module }}</code>
          </template>
          <template #cell-action="{ row }">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ row.action }}</span>
              <small class="text-secondary">{{ row.description }}</small>
            </div>
          </template>
          <template #cell-target="{ row }">
            <span v-if="row.target_table && row.target_id">
              {{ row.target_table }} / {{ row.target_id }}
            </span>
            <span v-else class="text-secondary">-</span>
          </template>
          <template #cell-request="{ row }">
            <div class="d-flex flex-column">
              <small><code>{{ row.request_id || '-' }}</code></small>
              <small class="text-secondary">{{ row.ip_address || '-' }}</small>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>
  </AppShell>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAutocompleteField from '@/components/common/AppAutocompleteField.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useAuditLogsPage } from '@/features/audit-logs/model/composables/useAuditLogsPage'
import { useAuthStore } from '@/store/auth.store'

const { t, locale } = useI18n()
const authStore = useAuthStore()

const { logsCount, successCount, warningCount, errorCount, search, eventType, moduleKey, moduleOptions, filteredLogs } =
  useAuditLogsPage()

const canViewAuditLogs = computed(() => authStore.hasPermission('audit_logs.view'))
const eventTypeOptions = computed(() => [
  { value: 'all', label: t('pages.auditLogs.filters.allEventTypes') },
  { value: 'success', label: t('pages.auditLogs.eventTypes.success') },
  { value: 'info', label: t('pages.auditLogs.eventTypes.info') },
  { value: 'warning', label: t('pages.auditLogs.eventTypes.warning') },
  { value: 'error', label: t('pages.auditLogs.eventTypes.error') },
])
const moduleFilterOptions = computed(() => [
  { value: 'all', label: t('pages.auditLogs.filters.allModules') },
  ...moduleOptions.value.map((option) => ({ value: option, label: option })),
])

const statsCards = computed(() => [
  {
    id: 'total',
    value: logsCount.value,
    label: t('pages.auditLogs.stats.total'),
    icon: 'bi bi-journal-text',
    iconColor: '#334155',
  },
  {
    id: 'success',
    value: successCount.value,
    label: t('pages.auditLogs.stats.success'),
    icon: 'bi bi-check2-circle',
    iconColor: '#22c55e',
  },
  {
    id: 'warning',
    value: warningCount.value,
    label: t('pages.auditLogs.stats.warning'),
    icon: 'bi bi-exclamation-triangle',
    iconColor: '#f59e0b',
  },
  {
    id: 'error',
    value: errorCount.value,
    label: t('pages.auditLogs.stats.error'),
    icon: 'bi bi-x-octagon',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'created-at', label: t('pages.auditLogs.table.createdAt') },
  { key: 'actor', label: t('pages.auditLogs.table.actor') },
  { key: 'event-type', label: t('pages.auditLogs.table.eventType') },
  { key: 'module', label: t('pages.auditLogs.table.module') },
  { key: 'action', label: t('pages.auditLogs.table.action') },
  { key: 'target', label: t('pages.auditLogs.table.target') },
  { key: 'request', label: t('pages.auditLogs.table.request') },
])

function formatDate(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString(locale.value === 'ar' ? 'ar-SY' : 'en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function eventTypeBadgeClass(type) {
  if (type === 'success') return 'badge text-bg-success'
  if (type === 'warning') return 'badge text-bg-warning'
  if (type === 'error') return 'badge text-bg-danger'
  return 'badge text-bg-secondary'
}
</script>

<style scoped>
.audit-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}

.audit-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: visible;
  background: #fff;
}

.audit-filters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  align-items: center;
  gap: 8px;
  position: relative;
  z-index: 30;
}

.audit-filters :deep(.app-autocomplete-field__menu) {
  position: absolute;
  left: 0;
  right: 0;
  z-index: 40;
}
</style>
