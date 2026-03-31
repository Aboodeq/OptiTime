<template>
  <AppShell :page-title="t('routes.semestersManagement')">
    <section class="dashboard-card w-100">
      <div class="semesters-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.semestersManagement.title') }}</h1>
        <AppCan permission="semesters.create">
          <AppButton
            class="new-semester-btn"
            type="button"
            :tone-color="authStore.roleColor"
            @click="startCreateSemester"
          >
            + {{ t('pages.semestersManagement.actions.newSemester') }}
          </AppButton>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.semestersManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <div class="semesters-table-card">
        <AppDataTable
          :columns="tableColumns"
          :rows="filteredSemesters"
          row-key="id"
          :empty-text="t('pages.semestersManagement.table.empty')"
          :show-search="canViewSemesters"
          :search-value="search"
          :search-placeholder="t('nav.topbar.quickSearch')"
          @update:search-value="search = $event"
        >
          <template #cell-semester="{ row }">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ row.name }}</span>
              <small class="text-secondary"><code>{{ row.code }}</code></small>
            </div>
          </template>

          <template #cell-academic-year="{ row }">
            {{ row.academic_year }}
          </template>

          <template #cell-date-range="{ row }">
            <span>{{ row.start_date }} - {{ row.end_date }}</span>
          </template>

          <template #cell-status="{ row }">
            <span :class="row.is_active ? 'text-success' : 'text-secondary'">
              {{
                row.is_active
                  ? t('pages.semestersManagement.status.active')
                  : t('pages.semestersManagement.status.inactive')
              }}
            </span>
          </template>

          <template #cell-actions="{ row }">
            <div class="text-start">
              <AppCan permission="semesters.update">
                <AppIconButton
                  class="me-2"
                  icon="bi bi-pencil-square"
                  variant="primary"
                  :title="t('pages.semestersManagement.actions.edit')"
                  :aria-label="t('pages.semestersManagement.actions.edit')"
                  @click="startEditSemester(row)"
                />
              </AppCan>
              <AppCan permission="semesters.delete">
                <AppIconButton
                  icon="bi bi-trash3"
                  variant="danger"
                  :title="t('pages.semestersManagement.actions.delete')"
                  :aria-label="t('pages.semestersManagement.actions.delete')"
                  @click="requestDeleteSemester(row)"
                />
              </AppCan>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>

    <SemesterFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :can-edit="canCreateSemesters || canUpdateSemesters"
      @cancel="closeDialog"
      @save="handleSaveSemester"
      @update:name="draft.name = $event"
      @update:code="draft.code = $event"
      @update:academic-year="draft.academic_year = $event"
      @update:start-date="draft.start_date = $event"
      @update:end-date="draft.end_date = $event"
      @update:is-active="draft.is_active = $event"
    />

    <AppConfirmDialog
      :open="Boolean(semesterPendingDelete)"
      :title="t('pages.semestersManagement.confirmDelete.title')"
      :message="
        t('pages.semestersManagement.confirmDelete.message', {
          semester: semesterPendingDelete?.name ?? '',
        })
      "
      :confirm-text="t('pages.semestersManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.semestersManagement.confirmDelete.cancel')"
      @cancel="semesterPendingDelete = null"
      @confirm="confirmDeleteSemester"
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
import { useSemestersManagementPage } from '@/features/semesters/model/composables/useSemestersManagementPage'
import SemesterFormDialog from '@/features/semesters/ui/components/SemesterFormDialog.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const {
  semesters,
  semestersCount,
  activeSemestersCount,
  inactiveSemestersCount,
  draft,
  isDialogOpen,
  isEditing,
  startCreateSemester,
  startEditSemester,
  closeDialog,
  saveSemester,
  removeSemester,
} = useSemestersManagementPage()

const search = ref('')
const semesterPendingDelete = ref(null)

const canViewSemesters = computed(() => authStore.hasPermission('semesters.view'))
const canCreateSemesters = computed(() => authStore.hasPermission('semesters.create'))
const canUpdateSemesters = computed(() => authStore.hasPermission('semesters.update'))
const canDeleteSemesters = computed(() => authStore.hasPermission('semesters.delete'))

const filteredSemesters = computed(() => {
  if (!canViewSemesters.value) return []
  const q = search.value.trim().toLowerCase()
  if (!q) return semesters.value
  return semesters.value.filter(
    (semester) =>
      semester.name.toLowerCase().includes(q) ||
      semester.code.toLowerCase().includes(q) ||
      semester.academic_year.toLowerCase().includes(q),
  )
})

const statsCards = computed(() => [
  {
    id: 'semesters',
    value: semestersCount.value,
    label: t('pages.semestersManagement.stats.totalSemesters'),
    icon: 'bi bi-journals',
    iconColor: '#8b5cf6',
  },
  {
    id: 'active',
    value: activeSemestersCount.value,
    label: t('pages.semestersManagement.stats.activeSemesters'),
    icon: 'bi bi-check2-circle',
    iconColor: '#22c55e',
  },
  {
    id: 'inactive',
    value: inactiveSemestersCount.value,
    label: t('pages.semestersManagement.stats.inactiveSemesters'),
    icon: 'bi bi-pause-circle',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'semester', label: t('pages.semestersManagement.table.semester') },
  { key: 'academic-year', label: t('pages.semestersManagement.table.academicYear') },
  { key: 'date-range', label: t('pages.semestersManagement.table.dateRange') },
  { key: 'status', label: t('pages.semestersManagement.table.status') },
  ...(canUpdateSemesters.value || canDeleteSemesters.value
    ? [{ key: 'actions', label: t('pages.semestersManagement.table.actions') }]
    : []),
])

function requestDeleteSemester(semester) {
  if (!canDeleteSemesters.value) return
  semesterPendingDelete.value = semester
}

async function confirmDeleteSemester() {
  if (!canDeleteSemesters.value) return
  if (!semesterPendingDelete.value) return
  await removeSemester(semesterPendingDelete.value.id)
  semesterPendingDelete.value = null
}

async function handleSaveSemester() {
  if (isEditing.value && !canUpdateSemesters.value) return
  if (!isEditing.value && !canCreateSemesters.value) return
  const saved = await saveSemester()
  if (!saved) {
    toast.error(t('pages.semestersManagement.errors.invalidForm'))
  }
}
</script>

<style scoped>
.semesters-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-semester-btn {
  padding: 0.6rem 1rem;
}

.semesters-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}
</style>
