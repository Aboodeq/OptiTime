import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '@/features/auth/api/auth.service'
import { instructorWeeklyScheduleService } from '@/features/coordinator-schedule/api/instructorWeeklySchedule.service'
import { studentWeeklyScheduleService } from '@/features/coordinator-schedule/api/studentWeeklySchedule.service'
import { mapWeeklyApiItemToBoardSession } from '@/features/coordinator-schedule/api/weeklyScheduleMappers'
import { useCoordinatorScheduleStore } from '@/features/coordinator-schedule/model/stores/coordinatorSchedule.store'
import { examsService, flattenExamSessionsPayload } from '@/features/exams/api/exams.service'
import { sessionGradesService } from '@/features/session-grades/api/sessionGrades.service'
import { useSemestersStore } from '@/features/semesters/model/stores/semesters.store'
import { useAuthStore } from '@/store/auth.store'
import {
  GRADE_COMPONENT_KEYS,
  MAX_COMPONENT_SCORE,
  computeLetterGrade,
  computeTotal,
  normalizeScore,
} from '@/features/session-grades/model/constants/gradeScale'

export const useSessionGradesStore = defineStore('sessionGrades', () => {
  const initialized = ref(false)
  const loading = ref(false)
  const saving = ref(false)
  const records = ref([])
  const doneSessionIds = ref([])

  const authStore = useAuthStore()
  const scheduleStore = useCoordinatorScheduleStore()
  const semestersStore = useSemestersStore()
  const instructorBoardSessions = ref([])
  const studentBoardSessions = ref([])
  const examSessionRowsFromApi = ref([])
  const examSemesterId = ref('')

  const recordsMap = computed(() => {
    const map = new Map()
    for (const item of records.value) {
      map.set(`${item.schedule_session_id}|${item.student_id}`, item)
    }
    return map
  })

  async function resolveExamSemesterId() {
    const fromSchedule = scheduleStore.activeSemester?.id
    if (fromSchedule) return `${fromSchedule}`.trim()
    await semestersStore.ensureInitialized()
    const list = semestersStore.semesters ?? []
    const active = list.find((s) => s.is_active)
    return `${active?.id || list[0]?.id || ''}`.trim()
  }

  async function refreshExamSessionsFromApi() {
    const sid = examSemesterId.value
    if (!sid || !authStore.hasPermission('exam_sessions.view') || authService.isDemoMode()) return
    const sections = await examsService.getExamSessions(sid)
    examSessionRowsFromApi.value = flattenExamSessionsPayload(sections)
    doneSessionIds.value = examSessionRowsFromApi.value
      .filter((r) => r.is_done)
      .map((r) => `${r.schedule_session_id}`.trim())
      .filter(Boolean)
  }

  async function ensureInitialized() {
    if (initialized.value) return
    loading.value = true
    try {
      if (authStore.hasPermission('schedules.view')) {
        await scheduleStore.ensureInitialized()
      } else if (authStore.hasPermission('instructor.schedule.view')) {
        const data = await instructorWeeklyScheduleService.getWeeklySchedule()
        const items = Array.isArray(data?.items) ? data.items : []
        instructorBoardSessions.value = items.map((row) => mapWeeklyApiItemToBoardSession(row))
      } else if (authStore.hasPermission('student.schedule.view')) {
        const data = await studentWeeklyScheduleService.getWeeklySchedule()
        const items = Array.isArray(data?.items) ? data.items : []
        studentBoardSessions.value = items.map((row) => mapWeeklyApiItemToBoardSession(row))
      }

      examSemesterId.value = await resolveExamSemesterId()

      if (authService.isDemoMode()) {
        examSessionRowsFromApi.value = []
        const payload = await sessionGradesService.getAllGrades()
        records.value = Array.isArray(payload?.records) ? payload.records : []
        doneSessionIds.value = Array.isArray(payload?.done_session_ids) ? payload.done_session_ids : []
      } else if (authStore.hasPermission('exam_sessions.view') && examSemesterId.value) {
        try {
          await refreshExamSessionsFromApi()
          records.value = []
        } catch {
          examSessionRowsFromApi.value = []
          records.value = []
          doneSessionIds.value = []
        }
      } else {
        examSessionRowsFromApi.value = []
        const payload = await sessionGradesService.getAllGrades()
        records.value = Array.isArray(payload?.records) ? payload.records : []
        doneSessionIds.value = Array.isArray(payload?.done_session_ids) ? payload.done_session_ids : []
      }
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  const usesExamApiSync = computed(
    () =>
      !authService.isDemoMode() &&
      authStore.hasPermission('exam_sessions.view') &&
      Boolean(examSemesterId.value),
  )

  const gradeBoardSessions = computed(() => {
    if (authStore.hasPermission('schedules.view')) {
      return scheduleStore.editableDraft?.sessions ?? []
    }
    if (authStore.hasPermission('instructor.schedule.view')) {
      return instructorBoardSessions.value
    }
    if (authStore.hasPermission('student.schedule.view')) {
      return studentBoardSessions.value
    }
    return []
  })

  function findBoardSession(sessionId) {
    const id = `${sessionId || ''}`.trim()
    if (!id) return null
    return gradeBoardSessions.value.find((s) => `${s.id || ''}`.trim() === id) ?? null
  }

  function isSessionDone(sessionId) {
    const normalized = `${sessionId || ''}`.trim()
    return doneSessionIds.value.includes(normalized)
  }

  function getGradeRecord(scheduleSessionId, studentId) {
    return recordsMap.value.get(`${scheduleSessionId}|${studentId}`) ?? null
  }

  function buildDraft(scheduleSessionId, studentId) {
    const existing = getGradeRecord(scheduleSessionId, studentId)
    const draft = {
      schedule_session_id: scheduleSessionId,
      student_id: studentId,
      oral: existing?.oral ?? 0,
      lab: existing?.lab ?? 0,
      midterm: existing?.midterm ?? 0,
      final: existing?.final ?? 0,
    }
    const total = computeTotal(draft)
    return {
      ...draft,
      total,
      letter_grade: computeLetterGrade(total),
    }
  }

  function normalizeDraft(draft) {
    if (!draft?.schedule_session_id || !draft?.student_id) return null
    const normalized = {
      schedule_session_id: `${draft.schedule_session_id}`.trim(),
      student_id: `${draft.student_id}`.trim(),
    }
    for (const key of GRADE_COMPONENT_KEYS) {
      normalized[key] = normalizeScore(draft[key])
      if (!Number.isFinite(normalized[key])) return null
      if (normalized[key] < 0 || normalized[key] > MAX_COMPONENT_SCORE) return null
    }
    const total = computeTotal(normalized)
    return {
      ...normalized,
      total,
      letter_grade: computeLetterGrade(total),
    }
  }

  async function saveGradeDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    saving.value = true
    try {
      let saved
      if (authService.isDemoMode() || !usesExamApiSync.value) {
        saved = await sessionGradesService.upsertGrade(normalized)
      } else {
        saved = await examsService.postExamGrade({
          schedule_session_id: normalized.schedule_session_id,
          student_id: normalized.student_id,
          oral: normalized.oral,
          lab: normalized.lab,
          midterm: normalized.midterm,
          final: normalized.final,
          total: normalized.total,
          letter_grade: normalized.letter_grade,
        })
      }
      if (!saved) {
        saving.value = false
        return false
      }
      const key = `${saved.schedule_session_id}|${saved.student_id}`
      const next = records.value.filter((item) => `${item.schedule_session_id}|${item.student_id}` !== key)
      records.value = [...next, saved]
      saving.value = false
      return true
    } catch {
      saving.value = false
      return false
    }
  }

  async function markSessionDone(sessionId, done = true) {
    const normalized = `${sessionId || ''}`.trim()
    if (!normalized) return false

    if (authService.isDemoMode()) {
      const saved = await sessionGradesService.markSessionDone(normalized, done)
      if (!saved) return false
      if (done) {
        doneSessionIds.value = [...new Set([...doneSessionIds.value, normalized])]
      } else {
        doneSessionIds.value = doneSessionIds.value.filter((item) => item !== normalized)
      }
      return true
    }

    if (usesExamApiSync.value) {
      if (!done) return false
      const board = findBoardSession(normalized)
      const apiRow = examSessionRowsFromApi.value.find((r) => r.schedule_session_id === normalized)
      const sectionId = `${board?.section_id || apiRow?.section_id || ''}`.trim()
      const sem = examSemesterId.value
      if (!sectionId || !sem) return false
      try {
        await examsService.markSectionExamsDone({ section_id: sectionId, semester_id: sem })
        await refreshExamSessionsFromApi()
        return true
      } catch {
        return false
      }
    }

    const saved = await sessionGradesService.markSessionDone(normalized, done)
    if (!saved) return false
    if (done) {
      doneSessionIds.value = [...new Set([...doneSessionIds.value, normalized])]
    } else {
      doneSessionIds.value = doneSessionIds.value.filter((item) => item !== normalized)
    }
    return true
  }

  const sessionRows = computed(() => {
    const map = new Map()
    for (const session of gradeBoardSessions.value) {
      const sid = `${session.id || ''}`.trim()
      if (!sid) continue
      map.set(sid, {
        id: session.id,
        schedule_session_id: sid,
        section_id: `${session.section_id || ''}`.trim(),
        course_code: session.course_code,
        course_name: session.course_name,
        instructor_name: session.instructor_name,
        room_name: session.room_name,
        day: session.day,
        start: session.start,
        end: session.end,
        student_count: Array.isArray(session.students)
          ? session.students.length
          : scheduleStore.getLectureStudents(session).length,
        is_done: isSessionDone(sid),
      })
    }
    for (const row of examSessionRowsFromApi.value) {
      const sid = `${row.schedule_session_id || ''}`.trim()
      if (!sid || map.has(sid)) continue
      map.set(sid, {
        id: sid,
        schedule_session_id: sid,
        section_id: `${row.section_id || ''}`.trim(),
        course_code: row.course_code,
        course_name: row.course_name,
        instructor_name: row.instructor_name || '—',
        room_name: row.room_name || '',
        day: row.day || '',
        start: row.start || '',
        end: row.end || '',
        student_count: 0,
        is_done: isSessionDone(sid),
      })
    }
    return [...map.values()]
  })

  const studentGradesForCurrentUser = computed(() => {
    const studentId = `${authStore.user?.id || ''}`.trim()
    if (!studentId) return []

    const sessionsById = new Map(gradeBoardSessions.value.map((item) => [item.id, item]))
    return records.value
      .filter((item) => item.student_id === studentId)
      .map((item) => {
        const session = sessionsById.get(item.schedule_session_id) ?? {}
        return {
          ...item,
          course_code: session.course_code ?? '',
          course_name: session.course_name ?? '',
          instructor_name: session.instructor_name ?? '',
          day: session.day ?? '',
          start: session.start ?? '',
          end: session.end ?? '',
        }
      })
  })

  return {
    loading,
    saving,
    records,
    sessionRows,
    studentGradesForCurrentUser,
    ensureInitialized,
    refreshExamSessionsFromApi,
    getGradeRecord,
    buildDraft,
    saveGradeDraft,
    isSessionDone,
    markSessionDone,
    findBoardSession,
    usesExamApiSync,
  }
})
