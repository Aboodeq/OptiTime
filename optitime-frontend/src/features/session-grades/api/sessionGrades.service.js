import {
  GRADE_COMPONENT_KEYS,
  computeLetterGrade,
  computeTotal,
  normalizeScore,
} from '@/features/session-grades/model/constants/gradeScale'

const STORAGE_KEY = 'optitime.session-grades.v1'

const SESSION_GRADES_SEED = [
  {
    id: 'grade-session-1-student-demo',
    schedule_session_id: 'session-1',
    student_id: 'student-demo',
    oral: 21,
    lab: 22,
    midterm: 18,
    final: 24,
  },
]

let gradesDb = []
let doneSessionIds = new Set()
loadInitialData()

function normalizeGradeRecord(record) {
  const normalized = {
    id: record?.id || `grade-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    schedule_session_id: `${record?.schedule_session_id || ''}`.trim(),
    student_id: `${record?.student_id || ''}`.trim(),
  }

  for (const key of GRADE_COMPONENT_KEYS) {
    normalized[key] = normalizeScore(record?.[key])
  }

  const total = computeTotal(normalized)
  normalized.total = total
  normalized.letter_grade = computeLetterGrade(total)
  return normalized
}

function cloneRecord(record) {
  return { ...record }
}

function loadInitialData() {
  if (typeof window === 'undefined') {
    gradesDb = SESSION_GRADES_SEED.map(normalizeGradeRecord)
    doneSessionIds = new Set()
    return
  }
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      gradesDb = SESSION_GRADES_SEED.map(normalizeGradeRecord)
      doneSessionIds = new Set()
      return
    }
    const parsed = JSON.parse(raw)
    const parsedRecords = Array.isArray(parsed) ? parsed : parsed?.records
    const parsedDoneIds = Array.isArray(parsed?.done_session_ids) ? parsed.done_session_ids : []
    if (!Array.isArray(parsedRecords)) {
      gradesDb = SESSION_GRADES_SEED.map(normalizeGradeRecord)
      doneSessionIds = new Set()
      return
    }

    const map = new Map()
    for (const item of parsedRecords) {
      const normalized = normalizeGradeRecord(item)
      const key = `${normalized.schedule_session_id}|${normalized.student_id}`
      map.set(key, normalized)
    }
    for (const seed of SESSION_GRADES_SEED) {
      const normalized = normalizeGradeRecord(seed)
      const key = `${normalized.schedule_session_id}|${normalized.student_id}`
      if (!map.has(key)) map.set(key, normalized)
    }
    gradesDb = [...map.values()]
    doneSessionIds = new Set(parsedDoneIds.map((item) => `${item || ''}`.trim()).filter(Boolean))
  } catch {
    gradesDb = SESSION_GRADES_SEED.map(normalizeGradeRecord)
    doneSessionIds = new Set()
  }
}

function persistData() {
  if (typeof window === 'undefined') return
  try {
    window.localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        records: gradesDb,
        done_session_ids: [...doneSessionIds],
      }),
    )
  } catch {
    // ignore storage errors
  }
}

export const sessionGradesService = {
  async getAllGrades() {
    return {
      records: gradesDb.map(cloneRecord),
      done_session_ids: [...doneSessionIds],
    }
  },

  async upsertGrade(payload) {
    const normalized = normalizeGradeRecord(payload)
    if (!normalized.schedule_session_id || !normalized.student_id) return null

    const existingIndex = gradesDb.findIndex(
      (item) =>
        item.schedule_session_id === normalized.schedule_session_id &&
        item.student_id === normalized.student_id,
    )
    if (existingIndex === -1) {
      gradesDb = [...gradesDb, normalized]
      persistData()
      return cloneRecord(normalized)
    }

    const merged = { ...gradesDb[existingIndex], ...normalized }
    const recalculated = normalizeGradeRecord(merged)
    gradesDb = gradesDb.map((item, index) => (index === existingIndex ? recalculated : item))
    persistData()
    return cloneRecord(recalculated)
  },

  async markSessionDone(scheduleSessionId, done = true) {
    const normalizedSessionId = `${scheduleSessionId || ''}`.trim()
    if (!normalizedSessionId) return false
    if (done) {
      doneSessionIds.add(normalizedSessionId)
    } else {
      doneSessionIds.delete(normalizedSessionId)
    }
    persistData()
    return true
  },
}
