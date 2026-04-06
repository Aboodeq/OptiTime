<template>
  <AppShell :page-title="t('routes.resourcesManagement')">
    <section class="dashboard-card w-100">
      <div class="resources-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.resourcesManagement.title') }}</h1>
        <AppCan permission="resources.create">
          <AppButton
            class="new-resource-btn"
            type="button"
            :tone-color="authStore.roleColor"
            @click="startCreateResource"
          >
            + {{ t('pages.resourcesManagement.actions.newResource') }}
          </AppButton>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.resourcesManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <div class="resources-table-card">
        <AppDataTable
          :columns="tableColumns"
          :rows="filteredResources"
          row-key="id"
          :empty-text="t('pages.resourcesManagement.table.empty')"
          :show-search="canViewResources"
          :search-value="search"
          :search-placeholder="t('nav.topbar.quickSearch')"
          @update:search-value="search = $event"
        >
          <template #cell-resource="{ row }">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ row.name_ar }}</span>
              <small class="text-secondary">{{ row.name_en }}</small>
              <small class="text-secondary">{{ row.type }}</small>
            </div>
          </template>

          <template #cell-quantity="{ row }">
            {{ row.quantity }}
          </template>

          <template #cell-location="{ row }">
            <div class="d-flex flex-column">
              <span>{{ row.location_ar || '-' }}</span>
              <small class="text-secondary">{{ row.location_en || '-' }}</small>
            </div>
          </template>

          <template #cell-status="{ row }">
            <span
              :class="{
                'text-success': row.status === 'available',
                'text-warning': row.status === 'maintenance',
                'text-danger': row.status === 'unavailable',
              }"
            >
              {{ t(`pages.resourcesManagement.status.${row.status}`) }}
            </span>
          </template>

          <template #cell-notes="{ row }">
            <div class="d-flex flex-column">
              <span>{{ row.notes_ar || '-' }}</span>
              <small class="text-secondary">{{ row.notes_en || '-' }}</small>
            </div>
          </template>

          <template #cell-actions="{ row }">
            <div class="text-start">
              <AppCan permission="resources.update">
                <AppIconButton
                  class="me-2"
                  icon="bi bi-pencil-square"
                  variant="primary"
                  :title="t('pages.resourcesManagement.actions.edit')"
                  :aria-label="t('pages.resourcesManagement.actions.edit')"
                  @click="startEditResource(row)"
                />
              </AppCan>
              <AppCan permission="resources.delete">
                <AppIconButton
                  icon="bi bi-trash3"
                  variant="danger"
                  :title="t('pages.resourcesManagement.actions.delete')"
                  :aria-label="t('pages.resourcesManagement.actions.delete')"
                  @click="requestDeleteResource(row)"
                />
              </AppCan>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>

    <ResourceFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :can-edit="canCreateResources || canUpdateResources"
      @cancel="closeDialog"
      @save="handleSaveResource"
      @update:name_ar="draft.name_ar = $event"
      @update:name_en="draft.name_en = $event"
      @update:type="draft.type = $event"
      @update:quantity="draft.quantity = $event"
      @update:location_ar="draft.location_ar = $event"
      @update:location_en="draft.location_en = $event"
      @update:status="draft.status = $event"
      @update:notes_ar="draft.notes_ar = $event"
      @update:notes_en="draft.notes_en = $event"
    />

    <AppConfirmDialog
      :open="Boolean(resourcePendingDelete)"
      :title="t('pages.resourcesManagement.confirmDelete.title')"
      :message="
        t('pages.resourcesManagement.confirmDelete.message', {
          resource: resourcePendingDelete?.name_en ?? '',
        })
      "
      :confirm-text="t('pages.resourcesManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.resourcesManagement.confirmDelete.cancel')"
      @cancel="resourcePendingDelete = null"
      @confirm="confirmDeleteResource"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppCan from '@/components/common/AppCan.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useResourcesManagementPage } from '@/features/resources/model/composables/useResourcesManagementPage'
import ResourceFormDialog from '@/features/resources/ui/components/ResourceFormDialog.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const {
  resources,
  resourcesCount,
  availableResourcesCount,
  unavailableResourcesCount,
  draft,
  isDialogOpen,
  isEditing,
  startCreateResource,
  startEditResource,
  closeDialog,
  saveResource,
  removeResource,
} = useResourcesManagementPage()

const search = ref('')
const resourcePendingDelete = ref(null)

const canViewResources = computed(() => authStore.hasPermission('resources.view'))
const canCreateResources = computed(() => authStore.hasPermission('resources.create'))
const canUpdateResources = computed(() => authStore.hasPermission('resources.update'))
const canDeleteResources = computed(() => authStore.hasPermission('resources.delete'))

const filteredResources = computed(() => {
  if (!canViewResources.value) return []
  const q = search.value.trim().toLowerCase()
  if (!q) return resources.value
  return resources.value.filter(
    (resource) =>
      (resource.name_ar || '').toLowerCase().includes(q) ||
      (resource.name_en || '').toLowerCase().includes(q) ||
      (resource.type || '').toLowerCase().includes(q) ||
      (resource.location_ar || '').toLowerCase().includes(q) ||
      (resource.location_en || '').toLowerCase().includes(q) ||
      (resource.status || '').toLowerCase().includes(q) ||
      (resource.notes_ar || '').toLowerCase().includes(q) ||
      (resource.notes_en || '').toLowerCase().includes(q),
  )
})

const statsCards = computed(() => [
  {
    id: 'resources',
    value: resourcesCount.value,
    label: t('pages.resourcesManagement.stats.totalResources'),
    icon: 'bi bi-box-seam',
    iconColor: '#8b5cf6',
  },
  {
    id: 'available',
    value: availableResourcesCount.value,
    label: t('pages.resourcesManagement.stats.availableResources'),
    icon: 'bi bi-check2-circle',
    iconColor: '#22c55e',
  },
  {
    id: 'unavailable',
    value: unavailableResourcesCount.value,
    label: t('pages.resourcesManagement.stats.unavailableResources'),
    icon: 'bi bi-x-circle',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'resource', label: t('pages.resourcesManagement.table.resource') },
  { key: 'quantity', label: t('pages.resourcesManagement.table.quantity') },
  { key: 'location', label: t('pages.resourcesManagement.table.location') },
  { key: 'status', label: t('pages.resourcesManagement.table.status') },
  { key: 'notes', label: t('pages.resourcesManagement.table.notes') },
  ...(canUpdateResources.value || canDeleteResources.value
    ? [{ key: 'actions', label: t('pages.resourcesManagement.table.actions') }]
    : []),
])

function requestDeleteResource(resource) {
  if (!canDeleteResources.value) return
  resourcePendingDelete.value = resource
}

async function confirmDeleteResource() {
  if (!canDeleteResources.value) return
  if (!resourcePendingDelete.value) return
  await removeResource(resourcePendingDelete.value.id)
  resourcePendingDelete.value = null
}

async function handleSaveResource() {
  if (isEditing.value && !canUpdateResources.value) return
  if (!isEditing.value && !canCreateResources.value) return
  const saved = await saveResource()
  if (!saved) {
    toast.error(t('pages.resourcesManagement.errors.invalidForm'))
  }
}
</script>

<style scoped>
.resources-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-resource-btn {
  padding: 0.6rem 1rem;
}

.resources-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}
</style>
