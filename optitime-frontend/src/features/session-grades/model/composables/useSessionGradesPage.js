import { computed, ref } from 'vue'
import { useCoordinatorScheduleStore } from '@/features/coordinator-schedule/model/stores/coordinatorSchedule.store'
import { useSessionGradesStore } from '@/features/session-grades/model/stores/sessionGrades.store'

export function useSessionGradesPage() {
  const scheduleStore = useCoordinatorScheduleStore()
  const sessionGradesStore = useSessionGradesStore()
  const selectedSessionId = ref('')

  sessionGradesStore.ensureInitialized()

  const activeSemester = computed(() => scheduleStore.activeSemester)
  const sessions = computed(() => sessionGradesStore.sessionRows)

  const selectedSession = computed(() =>
    sessions.value.find((item) => item.schedule_session_id === selectedSessionId.value) ?? null,
  )

  const selectedStudents = computed(() => {
    if (!selectedSession.value) return []
    const session = (scheduleStore.editableDraft?.sessions ?? []).find(
      (item) => item.id === selectedSession.value.schedule_session_id,
    )
    if (!session) return []
    return scheduleStore.getLectureStudents(session)
  })

  function selectSession(sessionId) {
    selectedSessionId.value = sessionId
  }

  return {
    loading: sessionGradesStore.loading,
    saving: sessionGradesStore.saving,
    activeSemester,
    sessions,
    selectedSessionId,
    selectedSession,
    selectedStudents,
    getGradeRecord: sessionGradesStore.getGradeRecord,
    buildDraft: sessionGradesStore.buildDraft,
    saveGradeDraft: sessionGradesStore.saveGradeDraft,
    isSessionDone: sessionGradesStore.isSessionDone,
    markSessionDone: sessionGradesStore.markSessionDone,
    selectSession,
  }
}
