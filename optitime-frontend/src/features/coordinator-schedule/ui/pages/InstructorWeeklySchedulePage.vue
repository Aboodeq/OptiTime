<template>
  <AppShell :page-title="t('routes.instructorWeeklySchedule')">
    <section class="dashboard-card w-100">
      <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.instructorWeeklySchedule.title') }}</h1>
        <p class="dashboard-card__meta mb-0">{{ t('pages.instructorWeeklySchedule.subtitle') }}</p>
        <p class="dashboard-card__meta mb-0">
          {{ t('pages.instructorWeeklySchedule.activeSemester', { name: activeSemester?.name ?? '-' }) }}
        </p>
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
      @close="detailsDialogOpen = false"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useSelfWeeklySchedulePage } from '@/features/coordinator-schedule/model/composables/useSelfWeeklySchedulePage'
import LectureDetailsDialog from '@/features/coordinator-schedule/ui/components/LectureDetailsDialog.vue'
import WeeklyScheduleBoard from '@/features/coordinator-schedule/ui/components/WeeklyScheduleBoard.vue'

const { t } = useI18n()
const detailsDialogOpen = ref(false)
const selectedLecture = ref(null)

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

function localizedDayLabel(day) {
  const item = localizedDayOptions.value.find((entry) => entry.value === day)
  return item?.label ?? day ?? '-'
}
</script>
