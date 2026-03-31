<template>
  <AppShell :page-title="t('routes.specialitiesManagement')">
    <section class="dashboard-card w-100">
      <div class="specialities-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.specialitiesManagement.title') }}</h1>
        <AppCan permission="specialities.create">
          <AppButton
            class="new-speciality-btn"
            type="button"
            :tone-color="authStore.roleColor"
            @click="startCreateSpeciality"
          >
            + {{ t('pages.specialitiesManagement.actions.newSpeciality') }}
          </AppButton>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.specialitiesManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <div class="specialities-table-card">
        <AppDataTable
          :columns="tableColumns"
          :rows="filteredSpecialities"
          row-key="id"
          :empty-text="t('pages.specialitiesManagement.table.empty')"
          :show-search="canViewSpecialities"
          :search-value="search"
          :search-placeholder="t('nav.topbar.quickSearch')"
          @update:search-value="search = $event"
        >
          <template #cell-speciality="{ row }">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ row.name_en }}</span>
              <small class="text-secondary">{{ row.name_ar }}</small>
            </div>
          </template>

          <template #cell-code="{ row }">
            <code>{{ row.code }}</code>
          </template>

          <template #cell-status="{ row }">
            <span :class="row.is_active ? 'text-success' : 'text-secondary'">
              {{
                row.is_active
                  ? t('pages.specialitiesManagement.status.active')
                  : t('pages.specialitiesManagement.status.inactive')
              }}
            </span>
          </template>

          <template #cell-actions="{ row }">
            <div class="text-start">
              <AppCan permission="specialities.update">
                <AppIconButton
                  class="me-2"
                  icon="bi bi-pencil-square"
                  variant="primary"
                  :title="t('pages.specialitiesManagement.actions.edit')"
                  :aria-label="t('pages.specialitiesManagement.actions.edit')"
                  @click="startEditSpeciality(row)"
                />
              </AppCan>
              <AppCan permission="specialities.delete">
                <AppIconButton
                  icon="bi bi-trash3"
                  variant="danger"
                  :title="t('pages.specialitiesManagement.actions.delete')"
                  :aria-label="t('pages.specialitiesManagement.actions.delete')"
                  @click="requestDeleteSpeciality(row)"
                />
              </AppCan>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>

    <SpecialityFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :can-edit="canCreateSpecialities || canUpdateSpecialities"
      @cancel="closeDialog"
      @save="handleSaveSpeciality"
      @update:code="draft.code = $event"
      @update:name-ar="draft.name_ar = $event"
      @update:name-en="draft.name_en = $event"
      @update:is-active="draft.is_active = $event"
    />

    <AppConfirmDialog
      :open="Boolean(specialityPendingDelete)"
      :title="t('pages.specialitiesManagement.confirmDelete.title')"
      :message="
        t('pages.specialitiesManagement.confirmDelete.message', {
          speciality: specialityPendingDelete?.name_en ?? '',
        })
      "
      :confirm-text="t('pages.specialitiesManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.specialitiesManagement.confirmDelete.cancel')"
      @cancel="specialityPendingDelete = null"
      @confirm="confirmDeleteSpeciality"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import AppCan from '@/components/common/AppCan.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useSpecialitiesManagementPage } from '@/features/specialities/model/composables/useSpecialitiesManagementPage'
import SpecialityFormDialog from '@/features/specialities/ui/components/SpecialityFormDialog.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const {
  specialities,
  specialitiesCount,
  activeSpecialitiesCount,
  inactiveSpecialitiesCount,
  draft,
  isDialogOpen,
  isEditing,
  startCreateSpeciality,
  startEditSpeciality,
  closeDialog,
  saveSpeciality,
  removeSpeciality,
} = useSpecialitiesManagementPage()

const search = ref('')
const specialityPendingDelete = ref(null)
const canViewSpecialities = computed(() => authStore.hasPermission('specialities.view'))
const canCreateSpecialities = computed(() => authStore.hasPermission('specialities.create'))
const canUpdateSpecialities = computed(() => authStore.hasPermission('specialities.update'))
const canDeleteSpecialities = computed(() => authStore.hasPermission('specialities.delete'))

const filteredSpecialities = computed(() => {
  if (!canViewSpecialities.value) return []
  const q = search.value.trim().toLowerCase()
  if (!q) return specialities.value
  return specialities.value.filter(
    (speciality) =>
      speciality.name_en.toLowerCase().includes(q) ||
      speciality.name_ar.toLowerCase().includes(q) ||
      speciality.code.toLowerCase().includes(q),
  )
})

const statsCards = computed(() => [
  {
    id: 'specialities',
    value: specialitiesCount.value,
    label: t('pages.specialitiesManagement.stats.totalSpecialities'),
    icon: 'bi bi-bookmarks',
    iconColor: '#8b5cf6',
  },
  {
    id: 'active',
    value: activeSpecialitiesCount.value,
    label: t('pages.specialitiesManagement.stats.activeSpecialities'),
    icon: 'bi bi-bookmark-check',
    iconColor: '#22c55e',
  },
  {
    id: 'inactive',
    value: inactiveSpecialitiesCount.value,
    label: t('pages.specialitiesManagement.stats.inactiveSpecialities'),
    icon: 'bi bi-bookmark-dash',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'speciality', label: t('pages.specialitiesManagement.table.speciality') },
  { key: 'code', label: t('pages.specialitiesManagement.table.code') },
  { key: 'status', label: t('pages.specialitiesManagement.table.status') },
  ...(canUpdateSpecialities.value || canDeleteSpecialities.value
    ? [{ key: 'actions', label: t('pages.specialitiesManagement.table.actions') }]
    : []),
])

function requestDeleteSpeciality(speciality) {
  if (!canDeleteSpecialities.value) return
  specialityPendingDelete.value = speciality
}

async function confirmDeleteSpeciality() {
  if (!canDeleteSpecialities.value) return
  if (!specialityPendingDelete.value) return
  await removeSpeciality(specialityPendingDelete.value.id)
  specialityPendingDelete.value = null
}

async function handleSaveSpeciality() {
  if (isEditing.value && !canUpdateSpecialities.value) return
  if (!isEditing.value && !canCreateSpecialities.value) return
  const saved = await saveSpeciality()
  if (!saved) {
    toast.error(t('pages.specialitiesManagement.errors.invalidForm'))
  }
}
</script>

<style scoped>
.specialities-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-speciality-btn {
  padding: 0.6rem 1rem;
}

.specialities-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}
</style>
