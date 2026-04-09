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

export function mapDraftToAvailabilityApiPayload(draft) {
  return {
    instructor_id: draft.instructor_id,
    cells: Array.isArray(draft.cells) ? [...draft.cells] : [],
  }
}

export const instructorPreferencesService = {
  async getInstructorPreferences() {
    return preferencesDb.map(clonePreference)
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
}
