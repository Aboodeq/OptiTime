import { apiFetch, apiJson } from '@/api/client'
import { authService } from '@/features/auth/api/auth.service'
import {
  computeLetterGrade,
  computeTotal,
  normalizeScore,
} from '@/features/session-grades/model/constants/gradeScale'

const DEMO_SEMESTER_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa'

function demoExamSections() {
  return [
    {
      id: 'demo-section-a',
      course_id: 'demo-course',
      course: { code: 'CS101', name_en: 'Introduction to CS', name: 'Introduction to CS' },
      exam_sessions: [
        {
          id: 'demo-exam-sess-1',
          schedule_session_id: 'demo-exam-sess-1',
          section_id: 'demo-section-a',
          semester_id: DEMO_SEMESTER_ID,
          is_done: true,
        },
        {
          id: 'demo-exam-sess-2',
          schedule_session_id: 'demo-exam-sess-2',
          section_id: 'demo-section-a',
          semester_id: DEMO_SEMESTER_ID,
          is_done: false,
        },
      ],
    },
    {
      id: 'demo-section-b',
      course_id: 'demo-course-2',
      course: { code: 'MATH201', name_en: 'Calculus II', name: 'Calculus II' },
      exam_sessions: [
        {
          id: 'demo-exam-sess-3',
          schedule_session_id: 'demo-exam-sess-3',
          section_id: 'demo-section-b',
          semester_id: DEMO_SEMESTER_ID,
          is_done: false,
        },
      ],
    },
  ]
}

/**
 * Laravel course-section payload → flat rows for UI (one row per schedule session).
 * @param {unknown} sections
 */
export function flattenExamSessionsPayload(sections) {
  const rows = []
  if (!Array.isArray(sections)) return rows

  for (const section of sections) {
    if (!section || typeof section !== 'object') continue
    const course =
      section.course && typeof section.course === 'object' ? section.course : {}
    const code = `${course.code || ''}`.trim()
    const name = `${course.name_en || course.name || course.name_ar || ''}`.trim()
    const sectionId = `${section.id || ''}`.trim()
    const rawSessions =
      (Array.isArray(section.exam_sessions) && section.exam_sessions) ||
      (Array.isArray(section.examSessions) && section.examSessions) ||
      []

    for (const es of rawSessions) {
      if (!es || typeof es !== 'object') continue
      const scheduleSessionId = `${es.schedule_session_id || es.id || ''}`.trim()
      if (!scheduleSessionId) continue
      rows.push({
        section_id: sectionId,
        schedule_session_id: scheduleSessionId,
        semester_id: `${es.semester_id || ''}`.trim(),
        course_id: `${section.course_id || course.id || ''}`.trim(),
        course_code: code,
        course_name: name,
        instructor_name: '',
        room_name: '',
        day: '',
        start: '',
        end: '',
        is_done: Boolean(es.is_done),
      })
    }
  }
  return rows
}

function mapApiRowToGradeRecord(row) {
  if (!row || typeof row !== 'object') return null
  const scheduleSessionId = `${row.schedule_session_id || ''}`.trim()
  const studentId = `${row.student_id || ''}`.trim()
  if (!scheduleSessionId || !studentId) return null

  const draft = {
    schedule_session_id: scheduleSessionId,
    student_id: studentId,
    oral: normalizeScore(row.oral),
    lab: normalizeScore(row.lab),
    midterm: normalizeScore(row.midterm),
    final: normalizeScore(row.final),
  }
  const total = computeTotal(draft)
  return {
    id: `${row.id || `grade-${scheduleSessionId}-${studentId}`}`,
    ...draft,
    total,
    letter_grade: row.letter_grade != null && `${row.letter_grade}`.trim()
      ? `${row.letter_grade}`.trim()
      : computeLetterGrade(total),
  }
}

export const examsService = {
  /**
   * @param {string} semesterId
   * @returns {Promise<unknown[]>} raw sections array from API
   */
  async getExamSessions(semesterId) {
    if (authService.isDemoMode()) {
      return demoExamSections()
    }
    const { data } = await apiJson(
      `/exams/sessions?semester_id=${encodeURIComponent(semesterId)}`,
      { method: 'GET' },
    )
    return Array.isArray(data) ? data : []
  },

  /**
   * @param {{ section_id: string, semester_id: string }} payload
   */
  async markSectionExamsDone(payload) {
    if (authService.isDemoMode()) {
      return { section_id: payload.section_id, semester_id: payload.semester_id, sessions_updated: 1 }
    }
    const { data } = await apiJson('/exams/sessions/mark-done', {
      method: 'POST',
      json: payload,
    })
    return data
  },

  /**
   * @param {Record<string, unknown>} payload — schedule_session_id, student_id, score fields
   */
  async postExamGrade(payload) {
    if (authService.isDemoMode()) {
      return mapApiRowToGradeRecord(payload)
    }
    const { data } = await apiJson('/exams/grades', {
      method: 'POST',
      json: payload,
    })
    return mapApiRowToGradeRecord(data)
  },

  /**
   * @param {string} scheduleSessionId
   * @returns {Promise<Blob>}
   */
  async downloadSessionGradesPdf(scheduleSessionId) {
    if (authService.isDemoMode()) {
      return new Blob([new Uint8Array([0x25, 0x50, 0x44, 0x46])], { type: 'application/pdf' })
    }
    const response = await apiFetch(
      `/exams/grades/session-pdf?schedule_session_id=${encodeURIComponent(scheduleSessionId)}`,
      { method: 'GET' },
    )
    if (!response.ok) {
      const err = new Error(response.statusText || 'PDF download failed')
      err.status = response.status
      throw err
    }
    return response.blob()
  },
}
