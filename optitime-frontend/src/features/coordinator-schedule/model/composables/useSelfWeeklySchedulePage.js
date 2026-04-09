import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { useCoordinatorScheduleStore } from '@/features/coordinator-schedule/model/stores/coordinatorSchedule.store'
import { useAuthStore } from '@/store/auth.store'

export function useSelfWeeklySchedulePage(actor) {
  const scheduleStore = useCoordinatorScheduleStore()
  const authStore = useAuthStore()
  scheduleStore.ensureInitialized()

  const { loading, activeSemester, enabledStudyDayValues, timeSlots, blockedSlotStarts } =
    storeToRefs(scheduleStore)

  const dayOptions = computed(() =>
    enabledStudyDayValues.value.map((day) => ({ value: day, label: day })),
  )

  const sessions = computed(() => {
    if (actor === 'instructor') {
      return scheduleStore.getInstructorSessionsForUser(authStore.user)
    }
    return scheduleStore.getStudentSessionsForUser(authStore.user)
  })

  function getLectureStudents(session) {
    return scheduleStore.getLectureStudents(session)
  }

  return {
    loading,
    activeSemester,
    dayOptions,
    timeSlots,
    blockedSlotStarts,
    sessions,
    getLectureStudents,
  }
}
