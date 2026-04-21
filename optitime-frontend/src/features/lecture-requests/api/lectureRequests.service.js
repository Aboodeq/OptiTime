import { apiJson } from '@/api/client'
import { authService } from '@/features/auth/api/auth.service'

/** @param {unknown} val */
function toYmd(val) {
  if (val == null || val === '') return ''
  if (val instanceof Date && !Number.isNaN(val.getTime())) {
    return val.toISOString().slice(0, 10)
  }
  const s = typeof val === 'string' ? val : String(val)
  return s.length >= 10 ? s.slice(0, 10) : s
}

function cloneRow(row) {
  return { ...row }
}

/**
 * Laravel lecture_request JSON → flat row for tables.
 * @param {Record<string, unknown>} raw
 */
export function normalizeLectureRequestRow(raw) {
  if (!raw || typeof raw !== 'object') return null
  const sess = raw.schedule_session ?? raw.scheduleSession ?? {}
  const co = sess.course_offering ?? sess.courseOffering ?? {}
  const course = co.course && typeof co.course === 'object' ? co.course : {}
  const si = sess.section_instructor ?? sess.sectionInstructor ?? {}
  const section = si.section && typeof si.section === 'object' ? si.section : {}
  const inst = raw.instructor && typeof raw.instructor === 'object' ? raw.instructor : {}
  const instUser = inst.user && typeof inst.user === 'object' ? inst.user : {}

  const code = `${course.code || ''}`.trim()
  const cname = `${course.name_en || course.name || course.name_ar || ''}`.trim()
  const courseLabel =
    [code, cname].filter(Boolean).join(' — ') || `${raw.schedule_session_id || ''}`.trim()

  const id = `${raw.id || ''}`.trim()
  if (!id) return null

  return {
    id,
    schedule_session_id: `${raw.schedule_session_id || ''}`.trim(),
    instructor_id: `${raw.instructor_id || ''}`.trim(),
    instructor_name: `${instUser.full_name || instUser.name || inst.name || ''}`.trim(),
    request_type: raw.request_type === 'makeup' ? 'makeup' : 'apology',
    requested_date: toYmd(raw.requested_date),
    note: raw.note != null ? String(raw.note) : '',
    status: `${raw.status || 'pending'}`.trim(),
    review_note: raw.review_note != null ? String(raw.review_note) : '',
    reviewed_at: raw.reviewed_at ?? null,
    course_label: courseLabel,
    section_label: `${section.section_name || section.id || ''}`.trim(),
  }
}

let demoRequests = []

function seedDemoIfEmpty() {
  if (demoRequests.length > 0) return
  demoRequests = [
    {
      id: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaa1',
      schedule_session_id: 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbb1',
      instructor_id: 'cccccccc-cccc-cccc-cccc-ccccccccccc1',
      instructor_name: 'Dr. Demo',
      request_type: 'apology',
      requested_date: '2026-05-15',
      note: 'Illness — demo request',
      status: 'pending',
      review_note: '',
      reviewed_at: null,
      course_label: 'CS101 — Introduction',
      section_label: 'A',
    },
  ]
}

async function deleteDemoPendingById(id) {
  if (!authService.isDemoMode()) return false
  seedDemoIfEmpty()
  const rid = `${id || ''}`.trim()
  const row = demoRequests.find((r) => r.id === rid)
  if (!row || row.status !== 'pending') return false
  demoRequests = demoRequests.filter((r) => r.id !== rid)
  return true
}

async function demoInstructorUpdatePending(id, partial) {
  if (!authService.isDemoMode()) return null
  seedDemoIfEmpty()
  const rid = `${id || ''}`.trim()
  const idx = demoRequests.findIndex((r) => r.id === rid)
  if (idx < 0 || demoRequests[idx].status !== 'pending') return null
  const next = {
    ...demoRequests[idx],
    requested_date: toYmd(partial?.requested_date ?? demoRequests[idx].requested_date),
    note: partial?.note != null ? String(partial.note) : demoRequests[idx].note,
  }
  demoRequests = demoRequests.map((r, i) => (i === idx ? next : r))
  return cloneRow(next)
}

function normalizeDemoCreatePayload(payload) {
  const requestType = payload?.request_type === 'makeup' ? 'makeup' : 'apology'
  return {
    schedule_session_id: `${payload?.schedule_session_id || ''}`.trim(),
    instructor_id: `${payload?.instructor_id || ''}`.trim(),
    instructor_name: '',
    request_type: requestType,
    requested_date: toYmd(payload?.requested_date),
    note: `${payload?.note || ''}`.trim(),
    status: 'pending',
    review_note: '',
    reviewed_at: null,
    course_label: payload?.schedule_session_id
      ? `Session ${String(payload.schedule_session_id).slice(0, 8)}…`
      : '—',
    section_label: '',
  }
}

export const lectureRequestsService = {
  async instructorList() {
    if (authService.isDemoMode()) {
      seedDemoIfEmpty()
      return demoRequests.map(cloneRow)
    }
    const { data } = await apiJson('/instructor/lecture-requests', { method: 'GET' })
    const list = Array.isArray(data) ? data : []
    return list.map(normalizeLectureRequestRow).filter(Boolean)
  },

  async coordinatorList() {
    if (authService.isDemoMode()) {
      seedDemoIfEmpty()
      return demoRequests.map(cloneRow)
    }
    const { data } = await apiJson('/coordinator/lecture-requests', { method: 'GET' })
    const list = Array.isArray(data) ? data : []
    return list.map(normalizeLectureRequestRow).filter(Boolean)
  },

  /**
   * @param {{ schedule_session_id: string, request_type: string, requested_date: string, note?: string, instructor_id?: string }} payload
   */
  async instructorCreate(payload) {
    const scheduleSessionId = `${payload?.schedule_session_id || ''}`.trim()
    const requestedDate = toYmd(payload?.requested_date)
    const requestType = payload?.request_type === 'makeup' ? 'makeup' : 'apology'
    if (!scheduleSessionId || !requestedDate) return null

    if (authService.isDemoMode()) {
      seedDemoIfEmpty()
      const base = normalizeDemoCreatePayload(payload)
      const row = {
        ...base,
        id: crypto.randomUUID(),
        instructor_name: base.instructor_id ? 'Demo instructor' : '',
      }
      demoRequests = [row, ...demoRequests]
      return cloneRow(row)
    }

    const { data } = await apiJson('/instructor/lecture-requests', {
      method: 'POST',
      json: {
        schedule_session_id: scheduleSessionId,
        request_type: requestType,
        requested_date: requestedDate,
        note: payload?.note ? String(payload.note).trim() : null,
      },
    })
    return normalizeLectureRequestRow(data)
  },

  /**
   * @param {string} id
   * @param {{ requested_date: string, note?: string }} partial
   */
  async instructorUpdate(id, partial) {
    const rid = `${id || ''}`.trim()
    if (!rid) return null
    const requestedDate = toYmd(partial?.requested_date)
    if (!requestedDate) return null

    if (authService.isDemoMode()) {
      return demoInstructorUpdatePending(id, partial)
    }

    const { data } = await apiJson(`/instructor/lecture-requests/${encodeURIComponent(rid)}`, {
      method: 'PUT',
      json: {
        requested_date: requestedDate,
        note: partial?.note != null ? String(partial.note).trim() : null,
      },
    })
    return normalizeLectureRequestRow(data)
  },

  /**
   * @param {string} id
   * @returns {Promise<boolean>}
   */
  async instructorDelete(id) {
    const rid = `${id || ''}`.trim()
    if (!rid) return false
    if (authService.isDemoMode()) {
      return deleteDemoPendingById(id)
    }
    await apiJson(`/instructor/lecture-requests/${encodeURIComponent(rid)}`, { method: 'DELETE' })
    return true
  },

  /**
   * @param {string} id
   * @param {{ status: string, review_note?: string }} body
   */
  async coordinatorUpdate(id, body) {
    const rid = `${id || ''}`.trim()
    if (!rid) return null
    const status = `${body?.status || ''}`.trim()
    if (!status) return null

    if (authService.isDemoMode()) {
      seedDemoIfEmpty()
      const idx = demoRequests.findIndex((r) => r.id === rid)
      if (idx < 0) return null
      const next = {
        ...demoRequests[idx],
        status,
        review_note: body?.review_note != null ? String(body.review_note) : '',
        reviewed_at: new Date().toISOString(),
      }
      demoRequests = demoRequests.map((r, i) => (i === idx ? next : r))
      return cloneRow(next)
    }

    const { data } = await apiJson(`/coordinator/lecture-requests/${encodeURIComponent(rid)}`, {
      method: 'PUT',
      json: {
        status,
        review_note: body?.review_note != null ? String(body.review_note).trim() : null,
      },
    })
    return normalizeLectureRequestRow(data)
  },

  /** Demo / future API: coordinator edits pending fields */
  async demoCoordinatorEditPending(id, partial) {
    if (!authService.isDemoMode()) return null
    seedDemoIfEmpty()
    const rid = `${id || ''}`.trim()
    const idx = demoRequests.findIndex((r) => r.id === rid)
    if (idx < 0 || demoRequests[idx].status !== 'pending') return null
    const next = {
      ...demoRequests[idx],
      requested_date: toYmd(partial?.requested_date ?? demoRequests[idx].requested_date),
      note: partial?.note != null ? String(partial.note) : demoRequests[idx].note,
    }
    demoRequests = demoRequests.map((r, i) => (i === idx ? next : r))
    return cloneRow(next)
  },

  async demoDeletePending(id) {
    return deleteDemoPendingById(id)
  },
}
