import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
  defaultAvailabilityGridContext,
  instructorPreferencesService,
} from '@/features/instructor-preferences/api/instructorPreferences.service'
import { useInstructorsStore } from '@/features/instructors/model/stores/instructors.store'
import { useAuthStore } from '@/store/auth.store'

const VALID_DAYS = new Set(['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'])
const VALID_CELL_STATUSES = new Set(['preferred', 'unavailable'])

function createEmptyDraft() {
  return {
    instructor_id: '',
    cells: [],
  }
}

function toMinutes(time) {
  if (typeof time !== 'string' || !/^\d{2}:\d{2}$/.test(time)) return NaN
  const [h, m] = time.split(':').map(Number)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return NaN
  if (h < 0 || h > 23 || m < 0 || m > 59) return NaN
  return h * 60 + m
}

function overlaps(startA, endA, startB, endB) {
  return startA < endB && endA > startB
}

function buildConstrainedDailySlots(dayStartMinutes, dayEndMinutes, slotMinutes, gapMinutes, enabledBreaks) {
  const slots = []
  const cycle = slotMinutes + gapMinutes
  for (let start = dayStartMinutes; start + slotMinutes <= dayEndMinutes; start += cycle) {
    const end = start + slotMinutes
    const intersectsBreak = enabledBreaks.some((breakItem) =>
      overlaps(start, end, toMinutes(breakItem.start), toMinutes(breakItem.end)),
    )
    if (!intersectsBreak) {
      slots.push({ start, end })
    }
  }
  return slots
}

export const useInstructorPreferencesStore = defineStore('instructorPreferences', () => {
  const instructorPreferences = ref([])
  const initialized = ref(false)
  const availabilityGridContext = ref(null)

  const instructorsStore = useInstructorsStore()

  const availabilityGrid = computed(
    () => availabilityGridContext.value ?? defaultAvailabilityGridContext(),
  )

  const enabledStudyDayValues = computed(() => {
    const settings = availabilityGrid.value
    return (settings?.study_days ?? [])
      .filter((item) => item.enabled)
      .map((item) => item.value)
      .filter((value) => VALID_DAYS.has(value))
  })

  const enabledStudyDaySet = computed(() => new Set(enabledStudyDayValues.value))

  function resolveInstructor(identity) {
    const key = `${identity || ''}`.trim()
    if (!key) return null
    const authStore = useAuthStore()
    const user = authStore.user
    if (user?.id === key && user.instructor) {
      const ins = user.instructor
      return {
        id: ins.id,
        user_id: user.id,
        email: user.email,
        min_work_hours_per_week: ins.min_work_hours_per_week,
        max_work_hours_per_week: ins.max_work_hours_per_week,
      }
    }
    return (
      instructorsStore.instructors.find((item) => item.id === key || item.user_id === key || item.email === key) ??
      null
    )
  }

  async function ensureInitialized() {
    if (initialized.value) return
    try {
      availabilityGridContext.value = await instructorPreferencesService.getAvailabilityGridContext()
    } catch {
      availabilityGridContext.value = defaultAvailabilityGridContext()
    }
    const authStore = useAuthStore()
    const userId = authStore.user?.id ? String(authStore.user.id) : ''
    if (!userId) {
      instructorPreferences.value = []
      initialized.value = true
      return
    }
    const payload = await instructorPreferencesService.getInstructorPreferences(userId)
    instructorPreferences.value = payload.map((item) => ({ ...item }))
    initialized.value = true
  }

  function buildDraftFromPreference(preference) {
    return {
      instructor_id: preference.instructor_id ?? '',
      cells: Array.isArray(preference.cells) ? [...preference.cells] : [],
    }
  }

  function normalizeDraft(draft, options = {}) {
    const allowUnknownInstructor = Boolean(options.allowUnknownInstructor)
    const instructorIdentity = draft.instructor_id?.trim() || ''
    if (!instructorIdentity) return null
    const selectedInstructor = resolveInstructor(instructorIdentity)
    if (!allowUnknownInstructor && !selectedInstructor) return null

    const settings = availabilityGrid.value
    const dayStartMinutes = toMinutes(settings?.day_start)
    const dayEndMinutes = toMinutes(settings?.day_end)
    const slotMinutes = Number(settings?.slot_minutes)
    const gapMinutes = Math.max(0, Number(settings?.gap_minutes) || 0)
    const maxDailyLectures = Number(settings?.max_daily_lectures)
    if (!Number.isFinite(dayStartMinutes) || !Number.isFinite(dayEndMinutes)) return null
    if (!Number.isFinite(slotMinutes) || slotMinutes <= 0) return null

    const enabledBreaks = (settings?.break_times ?? []).filter((item) => item?.enabled)
    const constrainedDailySlots = buildConstrainedDailySlots(
      dayStartMinutes,
      dayEndMinutes,
      slotMinutes,
      gapMinutes,
      enabledBreaks,
    )
    const constrainedSlotSet = new Set(constrainedDailySlots.map((slot) => `${slot.start}|${slot.end}`))
    const cells = Array.isArray(draft.cells) ? draft.cells : []
    const normalizedCellMap = new Map()
    for (const item of cells) {
      const day = `${item?.day || ''}`.trim()
      const start = `${item?.start || ''}`.trim()
      const end = `${item?.end || ''}`.trim()
      const status = `${item?.status || 'preferred'}`.trim()

      if (!day || !start || !end) return null
      if (!VALID_DAYS.has(day) || !enabledStudyDaySet.value.has(day)) return null
      if (!VALID_CELL_STATUSES.has(status)) return null

      const startMinutes = toMinutes(start)
      const endMinutes = toMinutes(end)
      if (!Number.isFinite(startMinutes) || !Number.isFinite(endMinutes)) return null
      if (!(startMinutes < endMinutes)) return null
      if (endMinutes - startMinutes !== slotMinutes) return null
      if (startMinutes < dayStartMinutes || endMinutes > dayEndMinutes) return null
      if (!constrainedSlotSet.has(`${startMinutes}|${endMinutes}`)) return null

      const slotKey = `${day}|${start}|${end}`
      const rawId = item?.id
      const cellId = rawId != null && String(rawId).trim() !== '' ? String(rawId).trim() : ''
      const entry = { day, start, end, status }
      if (cellId) entry.id = cellId
      normalizedCellMap.set(slotKey, entry)
    }
    const normalizedCells = [...normalizedCellMap.values()]

    const cellsByDay = new Map()
    normalizedCells.forEach((item) => {
      if (!cellsByDay.has(item.day)) cellsByDay.set(item.day, [])
      cellsByDay.get(item.day).push(item)
    })
    for (const dayIntervals of cellsByDay.values()) {
      const sorted = [...dayIntervals].sort((a, b) => toMinutes(a.start) - toMinutes(b.start))
      for (let i = 1; i < sorted.length; i += 1) {
        if (toMinutes(sorted[i].start) < toMinutes(sorted[i - 1].end)) return null
      }
    }

    if (Number.isFinite(maxDailyLectures) && maxDailyLectures > 0) {
      const preferredByDay = new Map()
      normalizedCells.forEach((item) => {
        if (item.status !== 'preferred') return
        if (!preferredByDay.has(item.day)) preferredByDay.set(item.day, 0)
        preferredByDay.set(item.day, preferredByDay.get(item.day) + 1)
      })
      const exceedsLimit = [...preferredByDay.values()].some((count) => count > maxDailyLectures)
      if (exceedsLimit) return null
    }

    const selectedInstructorForHours = resolveInstructor(instructorIdentity)
    if (!selectedInstructorForHours) return null
    const weeklyMinHours = Number(selectedInstructorForHours.min_work_hours_per_week)
    const weeklyMaxHours = Number(selectedInstructorForHours.max_work_hours_per_week)
    if (!Number.isFinite(weeklyMinHours) || weeklyMinHours < 0) return null
    if (!Number.isFinite(weeklyMaxHours) || weeklyMaxHours < 0) return null
    if (weeklyMinHours > weeklyMaxHours) return null
    const unavailableSlotsCount = normalizedCells.filter((item) => item.status === 'unavailable').length
    const totalSchedulableSlots =
      constrainedDailySlots.length * enabledStudyDayValues.value.length - unavailableSlotsCount
    const chosenMinutes = totalSchedulableSlots * slotMinutes
    if (chosenMinutes < weeklyMinHours * 60) return null
    if (chosenMinutes > weeklyMaxHours * 60) return null

    return {
      instructor_id: instructorIdentity,
      cells: normalizedCells,
    }
  }

  function getByInstructorId(instructorId) {
    return instructorPreferences.value.find((item) => item.instructor_id === instructorId) ?? null
  }

  function getInstructorWeeklyHoursRange(instructorId) {
    const selectedInstructor = resolveInstructor(instructorId)
    if (!selectedInstructor) return null
    const min = Number(selectedInstructor.min_work_hours_per_week)
    const max = Number(selectedInstructor.max_work_hours_per_week)
    if (!Number.isFinite(min) || !Number.isFinite(max)) return null
    if (min < 0 || max < 0 || min > max) return null
    return { min, max }
  }

  async function upsertPreferenceForInstructorId(instructorId, draft) {
    const normalized = normalizeDraft(
      {
        ...draft,
        instructor_id: instructorId,
      },
      { allowUnknownInstructor: true },
    )
    if (!normalized) return false

    const userKey = `${instructorId || ''}`.trim()
    const previousCells = getByInstructorId(userKey)?.cells ?? []
    const saved = await instructorPreferencesService.syncAvailabilityFromNormalizedDraft(
      userKey,
      previousCells,
      normalized.cells,
    )

    const record = {
      ...(saved.instructor_id ? { instructor_id: saved.instructor_id } : { instructor_id: userKey }),
      cells: Array.isArray(saved.cells) ? [...saved.cells] : [],
    }

    const existingIndex = instructorPreferences.value.findIndex((item) => item.instructor_id === userKey)
    if (existingIndex === -1) {
      instructorPreferences.value = [...instructorPreferences.value, record]
    } else {
      const next = [...instructorPreferences.value]
      next.splice(existingIndex, 1, record)
      instructorPreferences.value = next
    }
    return true
  }

  return {
    instructorPreferences,
    availabilityGrid,
    enabledStudyDayValues,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromPreference,
    getByInstructorId,
    getInstructorWeeklyHoursRange,
    upsertPreferenceForInstructorId,
  }
})
