import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useCoordinatorScheduleStore } from '@/features/coordinator-schedule/model/stores/coordinatorSchedule.store'
import { useSessionGradesStore } from '@/features/session-grades/model/stores/sessionGrades.store'
import { useAuthStore } from '@/store/auth.store'

export function useSessionGradesPage() {
  const scheduleStore = useCoordinatorScheduleStore()
  const sessionGradesStore = useSessionGradesStore()
  const { usesExamApiSync } = storeToRefs(sessionGradesStore)
  const authStore = useAuthStore()
  const selectedSessionId = ref('')

  sessionGradesStore.ensureInitialized()

  const activeSemester = computed(() => scheduleStore.activeSemester)
  const sessions = computed(() => sessionGradesStore.sessionRows)

  const selectedSession = computed(() =>
    sessions.value.find((item) => item.schedule_session_id === selectedSessionId.value) ?? null,
  )

  const selectedStudents = computed(() => {
    if (!selectedSession.value) return []
    const sid = selectedSession.value.schedule_session_id
    const board = sessionGradesStore.findBoardSession(sid)
    if (!board) return []
    if (Array.isArray(board.students) && board.students.length) return board.students
    if (authStore.hasPermission('schedules.view')) return scheduleStore.getLectureStudents(board)
    return []
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
    usesExamApiSync,
    getGradeRecord: sessionGradesStore.getGradeRecord,
    buildDraft: sessionGradesStore.buildDraft,
    saveGradeDraft: sessionGradesStore.saveGradeDraft,
    isSessionDone: sessionGradesStore.isSessionDone,
    markSessionDone: sessionGradesStore.markSessionDone,
    selectSession,
  }
}
