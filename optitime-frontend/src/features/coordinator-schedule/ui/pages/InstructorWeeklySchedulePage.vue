<template>
  <AppShell :page-title="t('routes.instructorWeeklySchedule')">
    <section class="dashboard-card w-100">
      <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
          <h1 class="h4 fw-bold mb-0">{{ t('pages.instructorWeeklySchedule.title') }}</h1>
          <p class="dashboard-card__meta mb-0">{{ t('pages.instructorWeeklySchedule.subtitle') }}</p>
          <p class="dashboard-card__meta mb-0">
            {{ t('pages.instructorWeeklySchedule.activeSemester', { name: activeSemester?.name ?? '-' }) }}
          </p>
        </div>
        <AppButton
          v-if="canExportPdf"
          type="button"
          :disabled="pdfLoading || !activeSemester?.id"
          :tone-color="authStore.roleColor"
          @click="handleExportPdf"
        >
          {{ t('pages.instructorWeeklySchedule.actions.exportPdf') }}
        </AppButton>
      </div>

      <AppSectionPanel :title="t('pages.instructorWeeklySchedule.sections.board')">
        <WeeklyScheduleBoard
          :sessions="sessions"
          :day-options="localizedDayOptions"
          :time-slots="timeSlots"
          :blocked-slot-starts="blockedSlotStarts"
          :time-header="t('pages.instructorSelfAvailability.grid.time')"
          :draggable="false"
          @select-session="handleSelectSession"
        />
      </AppSectionPanel>
    </section>

    <LectureDetailsDialog
      :open="detailsDialogOpen"
      :lecture="selectedLecture"
      :day-label="localizedDayLabel(selectedLecture?.day)"
      :students="selectedLectureStudents"
      :can-request-apology="canRequestApologyLecture"
      :can-request-makeup="canRequestMakeupLecture"
      @request-apology="openApologyDialog"
      @request-makeup="openMakeupDialog"
      @close="detailsDialogOpen = false"
    />

    <LectureApologyRequestDialog
      :open="apologyDialogOpen"
      :lecture-day="selectedLecture?.day"
      :lecture-day-label="localizedDayLabel(selectedLecture?.day)"
      @close="apologyDialogOpen = false"
      @submit="handleApologySubmit"
    />

    <MakeupLectureRequestDialog
      :open="makeupDialogOpen"
      @close="makeupDialogOpen = false"
      @submit="handleMakeupSubmit"
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
import { useSelfWeeklySchedulePage } from '@/features/coordinator-schedule/model/composables/useSelfWeeklySchedulePage'
import LectureApologyRequestDialog from '@/features/coordinator-schedule/ui/components/LectureApologyRequestDialog.vue'
import LectureDetailsDialog from '@/features/coordinator-schedule/ui/components/LectureDetailsDialog.vue'
import MakeupLectureRequestDialog from '@/features/coordinator-schedule/ui/components/MakeupLectureRequestDialog.vue'
import { instructorWeeklyScheduleService } from '@/features/coordinator-schedule/api/instructorWeeklySchedule.service'
import { useLectureRequestsStore } from '@/features/lecture-requests/model/stores/lectureRequests.store'
import WeeklyScheduleBoard from '@/features/coordinator-schedule/ui/components/WeeklyScheduleBoard.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const lectureRequestsStore = useLectureRequestsStore()
const detailsDialogOpen = ref(false)
const apologyDialogOpen = ref(false)
const makeupDialogOpen = ref(false)
const selectedLecture = ref(null)
const canRequestApologyLecture = computed(() =>
  authStore.hasPermission('instructor.requests.create'),
)
const canRequestMakeupLecture = computed(() =>
  authStore.hasPermission('instructor.requests.create'),
)
const canExportPdf = computed(() => authStore.hasPermission('instructor.schedule.export'))
const pdfLoading = ref(false)

const { activeSemester, dayOptions, timeSlots, blockedSlotStarts, sessions, getLectureStudents } =
  useSelfWeeklySchedulePage('instructor')

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

const selectedLectureStudents = computed(() =>
  selectedLecture.value ? getLectureStudents(selectedLecture.value) : [],
)

function handleSelectSession(session) {
  selectedLecture.value = session
  detailsDialogOpen.value = true
}

function openApologyDialog(lecture) {
  if (!canRequestApologyLecture.value) return
  selectedLecture.value = lecture ?? selectedLecture.value
  detailsDialogOpen.value = false
  apologyDialogOpen.value = true
}

function openMakeupDialog(lecture) {
  if (!canRequestMakeupLecture.value) return
  selectedLecture.value = lecture ?? selectedLecture.value
  detailsDialogOpen.value = false
  makeupDialogOpen.value = true
}

async function handleApologySubmit(form) {
  if (!canRequestApologyLecture.value) return
  const payload = buildRequestPayload('apology', form)
  const ok = await lectureRequestsStore.createInstructorRequest(payload)
  if (!ok) {
    toast.error(t('pages.instructorWeeklySchedule.requests.toasts.createFailed'))
    return
  }
  toast.success(t('pages.instructorWeeklySchedule.requests.toasts.apologySubmitted'))
  apologyDialogOpen.value = false
}

async function handleMakeupSubmit(form) {
  if (!canRequestMakeupLecture.value) return
  const payload = buildRequestPayload('makeup', form)
  const ok = await lectureRequestsStore.createInstructorRequest(payload)
  if (!ok) {
    toast.error(t('pages.instructorWeeklySchedule.requests.toasts.createFailed'))
    return
  }
  toast.success(t('pages.instructorWeeklySchedule.requests.toasts.makeupSubmitted'))
  makeupDialogOpen.value = false
}

function buildRequestPayload(type, form) {
  const lecture = selectedLecture.value ?? {}
  return {
    request_type: type,
    schedule_session_id: lecture.id ?? null,
    instructor_id: lecture.instructor_id ?? null,
    requested_date: form.requested_date,
    note: form.note,
  }
}

function localizedDayLabel(day) {
  const item = localizedDayOptions.value.find((entry) => entry.value === day)
  return item?.label ?? day ?? '-'
}

async function handleExportPdf() {
  if (!canExportPdf.value || !activeSemester.value?.id) return
  pdfLoading.value = true
  try {
    const blob = await instructorWeeklyScheduleService.downloadWeeklyPdf(activeSemester.value.id)
    const url = URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = `weekly-schedule-${activeSemester.value.id}.pdf`
    anchor.click()
    URL.revokeObjectURL(url)
  } catch {
    toast.error(t('pages.instructorWeeklySchedule.errors.pdfFailed'))
  } finally {
    pdfLoading.value = false
  }
}
</script>
