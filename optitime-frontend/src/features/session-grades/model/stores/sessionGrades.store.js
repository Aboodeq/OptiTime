import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useAuthStore } from '@/store/auth.store'
import { useCoordinatorScheduleStore } from '@/features/coordinator-schedule/model/stores/coordinatorSchedule.store'
import { sessionGradesService } from '@/features/session-grades/api/sessionGrades.service'
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

  const recordsMap = computed(() => {
    const map = new Map()
    for (const item of records.value) {
      map.set(`${item.schedule_session_id}|${item.student_id}`, item)
    }
    return map
  })

  async function ensureInitialized() {
    if (initialized.value) return
    loading.value = true
    await scheduleStore.ensureInitialized()
    const payload = await sessionGradesService.getAllGrades()
    records.value = Array.isArray(payload?.records) ? payload.records : []
    doneSessionIds.value = Array.isArray(payload?.done_session_ids) ? payload.done_session_ids : []
    initialized.value = true
    loading.value = false
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
    const saved = await sessionGradesService.upsertGrade(normalized)
    if (!saved) {
      saving.value = false
      return false
    }
    const key = `${saved.schedule_session_id}|${saved.student_id}`
    const next = records.value.filter((item) => `${item.schedule_session_id}|${item.student_id}` !== key)
    records.value = [...next, saved]
    saving.value = false
    return true
  }

  async function markSessionDone(sessionId, done = true) {
    const normalized = `${sessionId || ''}`.trim()
    if (!normalized) return false
    const saved = await sessionGradesService.markSessionDone(normalized, done)
    if (!saved) return false
    if (done) {
      doneSessionIds.value = [...new Set([...doneSessionIds.value, normalized])]
    } else {
      doneSessionIds.value = doneSessionIds.value.filter((item) => item !== normalized)
    }
    return true
  }

  const sessionRows = computed(() =>
    (scheduleStore.editableDraft?.sessions ?? []).map((session) => ({
      id: session.id,
      schedule_session_id: session.id,
      course_code: session.course_code,
      course_name: session.course_name,
      instructor_name: session.instructor_name,
      room_name: session.room_name,
      day: session.day,
      start: session.start,
      end: session.end,
      student_count: scheduleStore.getLectureStudents(session).length,
      is_done: isSessionDone(session.id),
    })),
  )

  const studentGradesForCurrentUser = computed(() => {
    const studentId = `${authStore.user?.id || ''}`.trim()
    if (!studentId) return []

    const sessionsById = new Map((scheduleStore.editableDraft?.sessions ?? []).map((item) => [item.id, item]))
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
    getGradeRecord,
    buildDraft,
    saveGradeDraft,
    isSessionDone,
    markSessionDone,
  }
})
