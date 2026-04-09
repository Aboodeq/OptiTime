<template>
  <AppShell :page-title="t('routes.studentWeeklySchedule')">
    <section class="dashboard-card w-100">
      <div class="mb-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
          <h1 class="h4 fw-bold mb-0">{{ t('pages.studentWeeklySchedule.title') }}</h1>
          <p class="dashboard-card__meta mb-0">{{ t('pages.studentWeeklySchedule.subtitle') }}</p>
          <p class="dashboard-card__meta mb-0">
            {{ t('pages.studentWeeklySchedule.activeSemester', { name: activeSemester?.name ?? '-' }) }}
          </p>
        </div>
        <AppButton
          v-if="canExportPdf"
          type="button"
          :disabled="pdfLoading || !activeSemester?.id"
          :tone-color="authStore.roleColor"
          @click="handleExportPdf"
        >
          {{ t('pages.studentWeeklySchedule.actions.exportPdf') }}
        </AppButton>
      </div>

      <AppSectionPanel :title="t('pages.studentWeeklySchedule.sections.board')">
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
      :show-students="false"
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
import { studentWeeklyScheduleService } from '@/features/coordinator-schedule/api/studentWeeklySchedule.service'
import { useSelfWeeklySchedulePage } from '@/features/coordinator-schedule/model/composables/useSelfWeeklySchedulePage'
import LectureDetailsDialog from '@/features/coordinator-schedule/ui/components/LectureDetailsDialog.vue'
import WeeklyScheduleBoard from '@/features/coordinator-schedule/ui/components/WeeklyScheduleBoard.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const canExportPdf = computed(() => authStore.hasPermission('student.schedule.export'))
const pdfLoading = ref(false)
const detailsDialogOpen = ref(false)
const selectedLecture = ref(null)

const { activeSemester, dayOptions, timeSlots, blockedSlotStarts, sessions, getLectureStudents } =
  useSelfWeeklySchedulePage('student')

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

function localizedDayLabel(day) {
  const item = localizedDayOptions.value.find((entry) => entry.value === day)
  return item?.label ?? day ?? '-'
}

async function handleExportPdf() {
  if (!canExportPdf.value || !activeSemester.value?.id) return
  pdfLoading.value = true
  try {
    const blob = await studentWeeklyScheduleService.downloadWeeklyPdf(activeSemester.value.id)
    const url = URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = `weekly-schedule-${activeSemester.value.id}.pdf`
    anchor.click()
    URL.revokeObjectURL(url)
  } catch {
    toast.error(t('pages.studentWeeklySchedule.errors.pdfFailed'))
  } finally {
    pdfLoading.value = false
  }
}
</script>
