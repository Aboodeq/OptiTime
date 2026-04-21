<template>
  <AppShell :page-title="t('routes.coordinatorLectureRequests')">
    <section class="dashboard-card w-100">
      <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.coordinatorLectureRequests.title') }}</h1>
        <p class="dashboard-card__meta mb-0">{{ t('pages.coordinatorLectureRequests.subtitle') }}</p>
        <p v-if="apiModeHint" class="dashboard-card__meta mb-0 small text-secondary">
          {{ t('pages.coordinatorLectureRequests.apiHint') }}
        </p>
      </div>

      <p v-if="requestsStore.loadingCoordinator" class="text-secondary small py-2 mb-0">
        {{ t('common.loading') }}…
      </p>

      <AppDataTable
        v-else
        :columns="columns"
        :rows="requestsStore.coordinatorRequests"
        row-key="id"
        :empty-text="t('pages.coordinatorLectureRequests.empty')"
      >
        <template #cell-type="{ row }">
          {{ t(`pages.coordinatorWeeklySchedule.requests.types.${row.request_type}`) }}
        </template>
        <template #cell-course="{ row }">
          <span class="fw-semibold">{{ row.course_label || '—' }}</span>
        </template>
        <template #cell-instructor_name="{ row }">
          {{ row.instructor_name || row.instructor_id || '—' }}
        </template>
        <template #cell-status="{ row }">
          {{ t(`pages.coordinatorWeeklySchedule.requests.statuses.${row.status}`) }}
        </template>
        <template #cell-actions="{ row }">
          <div class="actions-cell">
            <AppIconButton
              v-if="canReview"
              icon="bi bi-check2-square"
              :title="t('pages.coordinatorWeeklySchedule.requests.actions.check')"
              variant="primary"
              :disabled="!isPendingRequest(row)"
              @click="startReview(row)"
            />
            <AppIconButton
              v-if="canModify"
              icon="bi bi-pencil-square"
              :title="t('pages.coordinatorWeeklySchedule.requests.actions.modify')"
              variant="neutral"
              :disabled="!isPendingRequest(row)"
              @click="startEdit(row)"
            />
            <AppIconButton
              v-if="canDelete"
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

    <AppDialog :open="reviewOpen" :title="t('pages.coordinatorWeeklySchedule.requests.reviewDialog.title')" @close="reviewOpen = false">
      <div class="d-grid gap-3">
        <label class="request-field__label">{{ t('pages.coordinatorWeeklySchedule.requests.reviewDialog.status') }}</label>
        <select v-model="reviewForm.status" class="form-select">
          <option value="pending">{{ t('pages.coordinatorWeeklySchedule.requests.statuses.pending') }}</option>
          <option value="approved">{{ t('pages.coordinatorWeeklySchedule.requests.statuses.approved') }}</option>
          <option value="rejected">{{ t('pages.coordinatorWeeklySchedule.requests.statuses.rejected') }}</option>
        </select>
        <AppTextareaField
          input-id="coordinator-review-note"
          :label="t('pages.coordinatorWeeklySchedule.requests.reviewDialog.note')"
          :model-value="reviewForm.review_note"
          :placeholder="t('pages.coordinatorWeeklySchedule.requests.reviewDialog.notePlaceholder')"
          @update:model-value="reviewForm.review_note = $event"
        />
        <div class="d-flex justify-content-end gap-2">
          <AppButton variant="outline" @click="reviewOpen = false">{{ t('common.actions.cancel') }}</AppButton>
          <AppButton @click="confirmReview">{{ t('pages.coordinatorWeeklySchedule.requests.actions.saveReview') }}</AppButton>
        </div>
      </div>
    </AppDialog>

    <AppDialog :open="editOpen" :title="t('pages.coordinatorWeeklySchedule.requests.editDialog.title')" @close="editOpen = false">
      <div class="d-grid gap-3">
        <AppInputField
          input-id="coordinator-edit-date"
          :label="t('pages.coordinatorWeeklySchedule.requests.editDialog.requestedDate')"
          icon="bi bi-calendar-event"
          input-type="date"
          :model-value="editForm.requested_date"
          @update:model-value="editForm.requested_date = $event"
        />
        <AppTextareaField
          input-id="coordinator-edit-note"
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
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import { authService } from '@/features/auth/api/auth.service'
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
const reviewOpen = ref(false)
const editOpen = ref(false)
const deleteOpen = ref(false)
const reviewForm = ref({ status: 'pending', review_note: '' })
const editForm = ref({ requested_date: '', note: '' })

const apiModeHint = computed(() => !authService.isDemoMode())

const canReview = computed(() => authStore.hasPermission('lecture_requests.update'))
const canModify = computed(
  () => authStore.hasPermission('lecture_requests.update') && authService.isDemoMode(),
)
const canDelete = computed(
  () => authStore.hasPermission('lecture_requests.update') && authService.isDemoMode(),
)

const columns = computed(() => [
  { key: 'type', label: t('pages.coordinatorWeeklySchedule.requests.columns.type') },
  { key: 'instructor_name', label: t('pages.coordinatorWeeklySchedule.requests.columns.instructor') },
  { key: 'course', label: t('pages.coordinatorWeeklySchedule.requests.columns.course') },
  { key: 'requested_date', label: t('pages.coordinatorWeeklySchedule.requests.columns.requestedDate') },
  { key: 'status', label: t('pages.coordinatorWeeklySchedule.requests.columns.status') },
  { key: 'actions', label: t('pages.coordinatorWeeklySchedule.requests.columns.actions') },
])

onMounted(async () => {
  try {
    await requestsStore.loadCoordinatorRequests()
  } catch {
    toast.error(t('pages.coordinatorLectureRequests.errors.loadFailed'))
  }
})

function startReview(row) {
  if (!canReview.value || !isPendingRequest(row)) return
  activeId.value = row.id
  reviewForm.value = { status: row.status, review_note: row.review_note || '' }
  reviewOpen.value = true
}

async function confirmReview() {
  const current = requestsStore.coordinatorRequests.find((item) => item.id === activeId.value)
  if (!current || !isPendingRequest(current)) return
  const ok = await requestsStore.reviewCoordinatorRequest(activeId.value, {
    status: reviewForm.value.status,
    review_note: reviewForm.value.review_note.trim(),
  })
  if (!ok) {
    toast.error(t('pages.coordinatorLectureRequests.errors.reviewFailed'))
    return
  }
  reviewOpen.value = false
  toast.success(t('pages.coordinatorWeeklySchedule.requests.toasts.reviewSaved'))
}

function startEdit(row) {
  if (!canModify.value || !isPendingRequest(row)) return
  activeId.value = row.id
  editForm.value = { requested_date: row.requested_date, note: row.note }
  editOpen.value = true
}

async function confirmEdit() {
  const current = requestsStore.coordinatorRequests.find((item) => item.id === activeId.value)
  if (!current || !isPendingRequest(current)) return
  const updated = await requestsStore.coordinatorEditPending(activeId.value, {
    requested_date: editForm.value.requested_date,
    note: editForm.value.note.trim(),
  })
  if (!updated) return
  editOpen.value = false
  toast.success(t('pages.coordinatorWeeklySchedule.requests.toasts.requestUpdated'))
}

function startDelete(row) {
  if (!canDelete.value || !isPendingRequest(row)) return
  activeId.value = row.id
  deleteOpen.value = true
}

async function confirmDelete() {
  const current = requestsStore.coordinatorRequests.find((item) => item.id === activeId.value)
  if (!current || !isPendingRequest(current)) return
  const deleted = await requestsStore.deletePending(activeId.value)
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

.request-field__label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}
</style>
