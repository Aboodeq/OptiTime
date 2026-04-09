<template>
  <AppShell :page-title="t('routes.instructorLectureRequests')">
    <section class="dashboard-card w-100">
      <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.instructorLectureRequests.title') }}</h1>
        <p class="dashboard-card__meta mb-0">{{ t('pages.instructorLectureRequests.subtitle') }}</p>
      </div>

      <AppDataTable
        :columns="columns"
        :rows="instructorRequests"
        row-key="id"
        :empty-text="t('pages.instructorLectureRequests.empty')"
      >
        <template #cell-type="{ row }">
          {{ t(`pages.coordinatorWeeklySchedule.requests.types.${row.request_type}`) }}
        </template>
        <template #cell-status="{ row }">
          {{ t(`pages.coordinatorWeeklySchedule.requests.statuses.${row.status}`) }}
        </template>
        <template #cell-actions="{ row }">
          <div class="actions-cell">
            <AppIconButton
              v-if="canModifyOwn"
              icon="bi bi-pencil-square"
              :title="t('pages.coordinatorWeeklySchedule.requests.actions.modify')"
              variant="neutral"
              :disabled="!isPendingRequest(row)"
              @click="startEdit(row)"
            />
            <AppIconButton
              v-if="canDeleteOwn"
              icon="bi bi-trash3"
              :title="t('pages.coordinatorWeeklySchedule.requests.actions.delete')"
              variant="danger"
              :disabled="!isPendingRequest(row)"
              @click="startDelete(row)"
            />
          </div>
        </template>
      </AppDataTable>
    </section>

    <AppDialog
      :open="editOpen"
      :title="t('pages.coordinatorWeeklySchedule.requests.editDialog.title')"
      @close="editOpen = false"
    >
      <div class="d-grid gap-3">
        <AppInputField
          input-id="instructor-edit-date"
          :label="t('pages.coordinatorWeeklySchedule.requests.editDialog.requestedDate')"
          icon="bi bi-calendar-event"
          input-type="date"
          :model-value="editForm.requested_date"
          @update:model-value="editForm.requested_date = $event"
        />
        <AppTextareaField
          input-id="instructor-edit-note"
          :label="t('pages.coordinatorWeeklySchedule.requests.editDialog.note')"
          :model-value="editForm.note"
          :placeholder="t('pages.coordinatorWeeklySchedule.requests.editDialog.notePlaceholder')"
          @update:model-value="editForm.note = $event"
        />
        <div class="d-flex justify-content-end gap-2">
          <AppButton variant="outline" @click="editOpen = false">{{ t('common.actions.cancel') }}</AppButton>
          <AppButton @click="confirmEdit">{{ t('pages.coordinatorWeeklySchedule.requests.actions.saveChanges') }}</AppButton>
        </div>
      </div>
    </AppDialog>

    <AppConfirmDialog
      :open="deleteOpen"
      :title="t('pages.coordinatorWeeklySchedule.requests.deleteDialog.title')"
      :message="t('pages.coordinatorWeeklySchedule.requests.deleteDialog.message')"
      :confirm-text="t('pages.coordinatorWeeklySchedule.requests.actions.delete')"
      :cancel-text="t('common.actions.cancel')"
      @cancel="deleteOpen = false"
      @confirm="confirmDelete"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import AppButton from '@/components/common/AppButton.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import AppInputField from '@/components/common/AppInputField.vue'
import AppTextareaField from '@/components/common/AppTextareaField.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useLectureRequestsStore } from '@/features/lecture-requests/model/stores/lectureRequests.store'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const requestsStore = useLectureRequestsStore()
const activeId = ref('')
const editOpen = ref(false)
const deleteOpen = ref(false)
const editForm = ref({ requested_date: '', note: '' })

const canModifyOwn = computed(() => authStore.hasPermission('instructorSchedule.requests.self.update'))
const canDeleteOwn = computed(() => authStore.hasPermission('instructorSchedule.requests.self.delete'))
const instructorRequests = computed(() => requestsStore.requests)

const columns = computed(() => [
  { key: 'type', label: t('pages.coordinatorWeeklySchedule.requests.columns.type') },
  { key: 'schedule_session_id', label: t('pages.coordinatorWeeklySchedule.requests.columns.course') },
  { key: 'requested_date', label: t('pages.coordinatorWeeklySchedule.requests.columns.requestedDate') },
  { key: 'status', label: t('pages.coordinatorWeeklySchedule.requests.columns.status') },
  { key: 'instructor_id', label: t('pages.coordinatorWeeklySchedule.requests.columns.instructor') },
  { key: 'note', label: t('pages.instructorLectureRequests.columns.note') },
  { key: 'actions', label: t('pages.coordinatorWeeklySchedule.requests.columns.actions') },
])

function startEdit(row) {
  if (!canModifyOwn.value || !isPendingRequest(row)) return
  activeId.value = row.id
  editForm.value = { requested_date: row.requested_date, note: row.note }
  editOpen.value = true
}

function confirmEdit() {
  const current = requestsStore.requests.find((item) => item.id === activeId.value)
  if (!current || !isPendingRequest(current)) return
  const updated = requestsStore.updateRequest(activeId.value, {
    requested_date: editForm.value.requested_date,
    note: editForm.value.note.trim(),
  })
  if (!updated) return
  editOpen.value = false
  toast.success(t('pages.coordinatorWeeklySchedule.requests.toasts.requestUpdated'))
}

function startDelete(row) {
  if (!canDeleteOwn.value || !isPendingRequest(row)) return
  activeId.value = row.id
  deleteOpen.value = true
}

function confirmDelete() {
  const current = requestsStore.requests.find((item) => item.id === activeId.value)
  if (!current || !isPendingRequest(current)) return
  const deleted = requestsStore.deleteRequest(activeId.value)
  if (!deleted) return
  deleteOpen.value = false
  toast.success(t('pages.coordinatorWeeklySchedule.requests.toasts.requestDeleted'))
}

function isPendingRequest(row) {
  return row?.status === 'pending'
}
</script>

<style scoped>
.actions-cell {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}
</style>
