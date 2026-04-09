import { apiJson } from '@/api/client'
import { createDefaultConstraintsSettings } from '@/features/constraints/model/constants/defaultConstraints'
import { authService } from '@/features/auth/api/auth.service'

const STORAGE_KEY = 'optitime.instructor-availability.v1'

const INSTRUCTOR_PREFERENCES_SEED = [
  {
    id: 'instructor-pref-1',
    instructor_id: 'instructor-1',
    cells: [],
  },
  {
    id: 'instructor-pref-2',
    instructor_id: 'instructor-2',
    cells: [],
  },
]

let preferencesDb = loadInitialData()

function loadInitialData() {
  if (typeof window === 'undefined') return INSTRUCTOR_PREFERENCES_SEED.map(clonePreference)
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    if (!raw) return INSTRUCTOR_PREFERENCES_SEED.map(clonePreference)
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) return INSTRUCTOR_PREFERENCES_SEED.map(clonePreference)
    return parsed.map(clonePreference)
  } catch {
    return INSTRUCTOR_PREFERENCES_SEED.map(clonePreference)
  }
}

function persistData() {
  if (typeof window === 'undefined') return
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(preferencesDb))
  } catch {
    // ignore storage errors
  }
}

function clonePreference(item) {
  return {
    ...item,
    cells: Array.isArray(item.cells) ? [...item.cells] : [],
  }
}

export function normalizeTimeToHHMM(value) {
  if (typeof value !== 'string') return ''
  const trimmed = value.trim()
  if (trimmed.length >= 5 && /^\d{2}:\d{2}/.test(trimmed)) {
    return trimmed.slice(0, 5)
  }
  return trimmed
}

export function mapApiAvailabilityRowToCell(row) {
  if (!row || typeof row !== 'object') return null
  const id = row.id
  const day = row.day_value
  const start = normalizeTimeToHHMM(String(row.start_time ?? ''))
  const end = normalizeTimeToHHMM(String(row.end_time ?? ''))
  const rawStatus = String(row.status ?? 'preferred').trim()
  const status = rawStatus === 'unavailable' ? 'unavailable' : 'preferred'
  if (!id || !day || !start || !end) return null
  return { id: String(id), day, start, end, status }
}

function cellSlotKey(cell) {
  return `${cell.day}|${cell.start}|${cell.end}`
}

function toApiWriteBody(cell) {
  return {
    day_of_week: cell.day,
    start_time: cell.start,
    end_time: cell.end,
    status: cell.status,
  }
}

async function syncAvailabilityCellsApi(previousCells, normalizedCells) {
  const prevList = Array.isArray(previousCells) ? previousCells : []
  const targetList = Array.isArray(normalizedCells) ? normalizedCells : []

  const prevByKey = new Map()
  for (const c of prevList) {
    if (c?.id && c.day && c.start && c.end) {
      prevByKey.set(cellSlotKey(c), { id: c.id, status: c.status })
    }
  }

  const targetByKey = new Map()
  for (const c of targetList) {
    if (c?.day && c?.start && c?.end) {
      targetByKey.set(cellSlotKey(c), c)
    }
  }

  for (const [key, prev] of prevByKey) {
    if (!targetByKey.has(key)) {
      await apiJson(`/instructor/availabilities/${encodeURIComponent(prev.id)}`, { method: 'DELETE' })
    }
  }

  for (const [key, target] of targetByKey) {
    const prev = prevByKey.get(key)
    if (!prev) {
      await apiJson('/instructor/availabilities', {
        method: 'POST',
        json: toApiWriteBody(target),
      })
      continue
    }
    if (prev.status !== target.status) {
      await apiJson(`/instructor/availabilities/${encodeURIComponent(prev.id)}`, {
        method: 'PUT',
        json: { status: target.status },
      })
    }
  }
}

async function listAvailabilitiesApi() {
  const { data } = await apiJson('/instructor/availabilities', { method: 'GET' })
  const rows = Array.isArray(data) ? data : []
  const cells = []
  for (const row of rows) {
    const cell = mapApiAvailabilityRowToCell(row)
    if (cell) cells.push(cell)
  }
  return cells
}

function getDemoPreferences() {
  return preferencesDb.map(clonePreference)
}

export function defaultAvailabilityGridContext() {
  const d = createDefaultConstraintsSettings()
  return {
    day_start: d.day_start,
    day_end: d.day_end,
    slot_minutes: d.slot_minutes,
    gap_minutes: d.gap_minutes,
    max_daily_lectures: d.max_daily_lectures,
    study_days: d.study_days.map((x) => ({ ...x })),
    break_times: d.break_times.map((x) => ({ ...x })),
  }
}

export function mapApiAvailabilityGridContext(raw) {
  if (!raw || typeof raw !== 'object') return defaultAvailabilityGridContext()
  const study_days = Array.isArray(raw.study_days)
    ? raw.study_days.map((r) => ({
        value: String(r?.value ?? '').trim(),
        enabled: Boolean(r?.enabled),
      }))
    : []
  const break_times = Array.isArray(raw.break_times)
    ? raw.break_times.map((r) => ({
        key: String(r?.key ?? '').trim() || 'break',
        start: normalizeTimeToHHMM(String(r?.start ?? '')) || String(r?.start ?? ''),
        end: normalizeTimeToHHMM(String(r?.end ?? '')) || String(r?.end ?? ''),
        enabled: Boolean(r?.enabled),
      }))
    : []
  return {
    day_start: normalizeTimeToHHMM(String(raw.day_start ?? '')) || '08:00',
    day_end: normalizeTimeToHHMM(String(raw.day_end ?? '')) || '16:00',
    slot_minutes: Number(raw.slot_minutes) || 60,
    gap_minutes: Math.max(0, Number(raw.gap_minutes) || 0),
    max_daily_lectures: Number(raw.max_daily_lectures) || 4,
    study_days,
    break_times,
  }
}

export const instructorPreferencesService = {
  isDemoBacked() {
    return authService.isDemoMode()
  },

  async getAvailabilityGridContext() {
    if (authService.isDemoMode()) {
      return defaultAvailabilityGridContext()
    }
    const { data } = await apiJson('/instructor/availability-grid-context', { method: 'GET' })
    return mapApiAvailabilityGridContext(data)
  },

  async getInstructorPreferences(userId) {
    if (authService.isDemoMode()) {
      return getDemoPreferences()
    }
    const uid = `${userId || ''}`.trim()
    if (!uid) return []
    const cells = await listAvailabilitiesApi()
    return [{ instructor_id: uid, cells }]
  },

  async createInstructorPreference(payload) {
    const created = {
      ...payload,
      id: `instructor-pref-${Date.now()}`,
    }
    preferencesDb = [...preferencesDb, created]
    persistData()
    return clonePreference(created)
  },

  async updateInstructorPreference(preferenceId, payload) {
    let updated = null
    preferencesDb = preferencesDb.map((item) => {
      if (item.id !== preferenceId) return item
      updated = { ...item, ...payload }
      return updated
    })
    persistData()
    return updated ? clonePreference(updated) : null
  },

  async upsertInstructorPreferenceByInstructorId(instructorId, payload) {
    const current = preferencesDb.find((item) => item.instructor_id === instructorId)
    if (!current) return this.createInstructorPreference(payload)
    return this.updateInstructorPreference(current.id, payload)
  },

  /**
   * @param {string} userId
   * @param {Array<{ day: string, start: string, end: string, status: string }>} previousCells
   * @param {Array<{ day: string, start: string, end: string, status: string }>} normalizedCells
   */
  async syncAvailabilityFromNormalizedDraft(userId, previousCells, normalizedCells) {
    if (authService.isDemoMode()) {
      const saved = await this.upsertInstructorPreferenceByInstructorId(userId, {
        instructor_id: userId,
        cells: normalizedCells.map((c) => ({ ...c })),
      })
      return { instructor_id: userId, cells: saved?.cells ? [...saved.cells] : [] }
    }

    await syncAvailabilityCellsApi(previousCells, normalizedCells)
    const cells = await listAvailabilitiesApi()
    return { instructor_id: `${userId || ''}`.trim(), cells }
  },
}
