import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useCoordinatorScheduleStore } from '@/features/coordinator-schedule/model/stores/coordinatorSchedule.store'

export function useCoordinatorWeeklySchedulePage() {
  const scheduleStore = useCoordinatorScheduleStore()
  scheduleStore.ensureInitialized()

  const selectedAlgorithm = ref('genetic')
  const previewOpen = ref(false)

  const {
    loading,
    saving,
    generating,
    editableDraft,
    generatedDraft,
    activeSemester,
    enabledStudyDayValues,
    timeSlots,
    blockedSlotStarts,
    validationErrors,
    conflictSessionIds,
    kpis,
  } = storeToRefs(scheduleStore)

  const dayOptions = computed(() =>
    enabledStudyDayValues.value.map((day) => ({ value: day, label: day })),
  )

  async function saveChanges() {
    return scheduleStore.saveEditableDraft()
  }

  async function generate() {
    const generated = await scheduleStore.generateSchedule(selectedAlgorithm.value)
    previewOpen.value = generated
    return generated
  }

  async function confirmGenerated() {
    const saved = await scheduleStore.confirmAndSaveGeneratedSchedule()
    if (!saved) return false
    previewOpen.value = false
    return true
  }

  function cancelPreview() {
    previewOpen.value = false
    scheduleStore.clearGeneratedDraft()
  }

  function moveSession(sessionId, day, start) {
    return scheduleStore.moveSession(sessionId, day, start)
  }

  function getLectureStudents(session) {
    return scheduleStore.getLectureStudents(session)
  }

  return {
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
  }
}
