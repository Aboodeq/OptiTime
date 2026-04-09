import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useConstraintsStore } from '@/features/constraints/model/stores/constraints.store'
import { useInstructorsStore } from '@/features/instructors/model/stores/instructors.store'
import {
  instructorPreferencesService,
  mapDraftToAvailabilityApiPayload,
} from '@/features/instructor-preferences/api/instructorPreferences.service'

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

  const instructorsStore = useInstructorsStore()
  const constraintsStore = useConstraintsStore()

  const enabledStudyDayValues = computed(() => {
    const settings = constraintsStore.activeSettings
    return (settings?.study_days ?? [])
      .filter((item) => item.enabled)
      .map((item) => item.value)
      .filter((value) => VALID_DAYS.has(value))
  })

  const enabledStudyDaySet = computed(() => new Set(enabledStudyDayValues.value))

  function resolveInstructor(identity) {
    const key = `${identity || ''}`.trim()
    if (!key) return null
    return (
      instructorsStore.instructors.find((item) => item.id === key || item.user_id === key || item.email === key) ??
      null
    )
  }

  async function ensureInitialized() {
    if (initialized.value) return
    await Promise.all([instructorsStore.ensureInitialized(), constraintsStore.ensureInitialized()])
    const payload = await instructorPreferencesService.getInstructorPreferences()
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
    const instructorId = selectedInstructor?.id ?? instructorIdentity

    const settings = constraintsStore.activeSettings
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

      normalizedCellMap.set(`${day}|${start}|${end}`, { day, start, end, status })
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
      instructor_id: instructorId,
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

    const payload = mapDraftToAvailabilityApiPayload(normalized)
    const saved = await instructorPreferencesService.upsertInstructorPreferenceByInstructorId(
      normalized.instructor_id,
      payload,
    )

    const existingIndex = instructorPreferences.value.findIndex(
      (item) => item.instructor_id === normalized.instructor_id,
    )
    if (existingIndex === -1) {
      instructorPreferences.value = [...instructorPreferences.value, saved]
    } else {
      const next = [...instructorPreferences.value]
      next.splice(existingIndex, 1, saved)
      instructorPreferences.value = next
    }
    return true
  }

  return {
    instructorPreferences,
    enabledStudyDayValues,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromPreference,
    getByInstructorId,
    getInstructorWeeklyHoursRange,
    upsertPreferenceForInstructorId,
  }
})
