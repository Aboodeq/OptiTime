<template>
  <AppShell :page-title="t('routes.coordinatorWeeklySchedule')">
    <section class="dashboard-card w-100 coordinator-schedule-page">
      <div class="coordinator-schedule-page__toolbar">
        <div>
          <h1 class="h4 fw-bold mb-0">{{ t('pages.coordinatorWeeklySchedule.title') }}</h1>
          <p class="dashboard-card__meta mb-0">{{ t('pages.coordinatorWeeklySchedule.subtitle') }}</p>
          <p class="dashboard-card__meta mb-0">
            {{ t('pages.coordinatorWeeklySchedule.activeSemester', { name: activeSemester?.name ?? '-' }) }}
          </p>
        </div>
        <AppButton
          type="button"
          :disabled="saving || loading || !canUpdateSchedule"
          :tone-color="authStore.roleColor"
          @click="handleSave"
        >
          {{ t('pages.coordinatorWeeklySchedule.actions.save') }}
        </AppButton>
      </div>

      <AppSectionPanel
        :title="t('pages.coordinatorWeeklySchedule.sections.kpis')"
        class="mb-3"
      >
        <ScheduleKpiCards :kpis="kpis" />
      </AppSectionPanel>

      <AppSectionPanel
        :title="t('pages.coordinatorWeeklySchedule.sections.generation')"
        class="mb-3"
      >
        <div class="coordinator-schedule-page__generation">
          <AlgorithmSelector
            v-model="selectedAlgorithm"
            :label="t('pages.coordinatorWeeklySchedule.sections.generation')"
          />
          <AppButton
            type="button"
            :disabled="generating || loading || !canGenerateSchedule"
            @click="handleGenerate"
          >
            {{ t('pages.coordinatorWeeklySchedule.actions.generate') }}
          </AppButton>
        </div>
      </AppSectionPanel>

      <AppSectionPanel
        :title="t('pages.coordinatorWeeklySchedule.sections.board')"
        :subtitle="t('pages.coordinatorWeeklySchedule.hints.board')"
      >
        <WeeklyScheduleBoard
          :sessions="editableDraft.sessions"
          :day-options="localizedDayOptions"
          :time-slots="timeSlots"
          :blocked-slot-starts="blockedSlotStarts"
          :conflict-session-ids="conflictSessionIds"
          :time-header="t('pages.instructorSelfAvailability.grid.time')"
          :draggable="canUpdateSchedule"
          @move-session="handleMoveSession"
          @select-session="handleSelectSession"
        />
      </AppSectionPanel>

    </section>

    <GeneratedSchedulePreviewDialog
      :open="previewOpen"
      :sessions="generatedDraft?.sessions ?? []"
      :evaluation-meta="generatedDraft?.meta ?? {}"
      :day-options="localizedDayOptions"
      :time-slots="timeSlots"
      :blocked-slot-starts="blockedSlotStarts"
      :conflict-session-ids="conflictSessionIds"
      :can-confirm="canPublishSchedule"
      @confirm="handleConfirmGenerated"
      @cancel="cancelPreview"
    />

    <LectureDetailsDialog
      :open="detailsDialogOpen"
      :lecture="selectedLecture"
      :day-label="localizedDayLabel(selectedLecture?.day)"
      :students="selectedLectureStudents"
      @close="detailsDialogOpen = false"
    />

  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import AppButton from '@/components/common/AppButton.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useCoordinatorWeeklySchedulePage } from '@/features/coordinator-schedule/model/composables/useCoordinatorWeeklySchedulePage'
import AlgorithmSelector from '@/features/coordinator-schedule/ui/components/AlgorithmSelector.vue'
import GeneratedSchedulePreviewDialog from '@/features/coordinator-schedule/ui/components/GeneratedSchedulePreviewDialog.vue'
import LectureDetailsDialog from '@/features/coordinator-schedule/ui/components/LectureDetailsDialog.vue'
import ScheduleKpiCards from '@/features/coordinator-schedule/ui/components/ScheduleKpiCards.vue'
import WeeklyScheduleBoard from '@/features/coordinator-schedule/ui/components/WeeklyScheduleBoard.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const detailsDialogOpen = ref(false)
const selectedLecture = ref(null)

const {
  loading,
  saving,
  generating,
  selectedAlgorithm,
  previewOpen,
  editableDraft,
  generatedDraft,
  activeSemester,
  dayOptions,
  timeSlots,
  blockedSlotStarts,
  validationErrors,
  conflictSessionIds,
  kpis,
  saveChanges,
  generate,
  confirmGenerated,
  cancelPreview,
  moveSession,
  getLectureStudents,
} = useCoordinatorWeeklySchedulePage()

const canPublishSchedule = computed(() => authStore.hasPermission('schedules.publish'))

const localizedDayOptions = computed(() => {
  const labels = {
    sun: t('pages.constraintsManagement.days.sun'),
    mon: t('pages.constraintsManagement.days.mon'),
    tue: t('pages.constraintsManagement.days.tue'),
    wed: t('pages.constraintsManagement.days.wed'),
    thu: t('pages.constraintsManagement.days.thu'),
    fri: t('pages.constraintsManagement.days.fri'),
    sat: t('pages.constraintsManagement.days.sat'),
  }
  return dayOptions.value.map((item) => ({
    ...item,
    label: labels[item.value] ?? item.value,
  }))
})

const canUpdateSchedule = computed(() => authStore.hasPermission('schedules.update'))
const canGenerateSchedule = computed(() => authStore.hasPermission('schedule.generate'))
const selectedLectureStudents = computed(() =>
  selectedLecture.value ? getLectureStudents(selectedLecture.value) : [],
)

function handleMoveSession({ sessionId, day, start }) {
  moveSession(sessionId, day, start)
}

function handleSelectSession(session) {
  selectedLecture.value = session
  detailsDialogOpen.value = true
}

function localizedDayLabel(day) {
  const item = localizedDayOptions.value.find((entry) => entry.value === day)
  return item?.label ?? day ?? '-'
}

function buildValidationHint() {
  const count = validationErrors.value.length
  if (count === 0) return ''
  const unique = [...new Set(validationErrors.value)].slice(0, 3).join(', ')
  return unique ? ` (${unique})` : ''
}

async function handleSave() {
  if (!canUpdateSchedule.value) return
  const saved = await saveChanges()
  if (!saved) {
    toast.error(`${t('pages.coordinatorWeeklySchedule.errors.saveFailed')}${buildValidationHint()}`)
    return
  }
  toast.success(t('pages.coordinatorWeeklySchedule.toasts.saved'))
}

async function handleGenerate() {
  if (!canGenerateSchedule.value) return
  const ok = await generate()
  if (!ok) {
    toast.error(`${t('pages.coordinatorWeeklySchedule.errors.generateFailed')}${buildValidationHint()}`)
    return
  }
  toast.success(t('pages.coordinatorWeeklySchedule.toasts.generated'))
}

async function handleConfirmGenerated() {
  if (!canPublishSchedule.value) return
  if (validationErrors.value.length > 0) {
    toast.error(t('pages.coordinatorWeeklySchedule.errors.invalidSchedule'))
    return
  }
  const saved = await confirmGenerated()
  if (!saved) {
    toast.error(t('pages.coordinatorWeeklySchedule.errors.saveFailed'))
    return
  }
  toast.success(t('pages.coordinatorWeeklySchedule.toasts.generatedSaved'))
}

</script>

<style scoped>
.coordinator-schedule-page {
  width: 100%;
  max-width: 100%;
}

.coordinator-schedule-page__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.95rem;
  margin-bottom: 1.15rem;
}

.coordinator-schedule-page__generation {
  display: flex;
  gap: 0.8rem;
  align-items: center;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .coordinator-schedule-page__toolbar {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
