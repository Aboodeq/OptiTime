import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { constraintsService } from '@/features/constraints/api/constraints.service'
import {
  cloneConstraintsSettings,
  createDefaultConstraintsSettings,
} from '@/features/constraints/model/constants/defaultConstraints'

function reorderByKey(defaultItems, currentItems, keyField = 'key') {
  const currentMap = new Map(
    (Array.isArray(currentItems) ? currentItems : [])
      .filter((item) => item && typeof item === 'object' && item[keyField])
      .map((item) => [item[keyField], item]),
  )

  const ordered = defaultItems.map((base) =>
    currentMap.has(base[keyField]) ? { ...base, ...currentMap.get(base[keyField]) } : { ...base },
  )

  const defaultKeys = new Set(defaultItems.map((item) => item[keyField]))
  const newExtras = (Array.isArray(currentItems) ? currentItems : []).filter(
    (item) =>
      item &&
      typeof item === 'object' &&
      item[keyField] &&
      !defaultKeys.has(item[keyField]),
  )
  return [...ordered, ...newExtras]
}

function normalizeSettingsShape(value) {
  const defaults = createDefaultConstraintsSettings()
  const merged = { ...defaults, ...(value && typeof value === 'object' ? value : {}) }

  merged.hard_constraints = reorderByKey(defaults.hard_constraints, merged.hard_constraints, 'key')
  merged.soft_constraints = reorderByKey(defaults.soft_constraints, merged.soft_constraints, 'key')
  merged.room_constraints = reorderByKey(defaults.room_constraints, merged.room_constraints, 'key')
  merged.break_times = reorderByKey(defaults.break_times, merged.break_times, 'key')
  merged.study_days = reorderByKey(defaults.study_days, merged.study_days, 'value')
  merged.load_settings = reorderByKey(defaults.load_settings, merged.load_settings, 'key')

  return merged
}

function normalizeNumber(value) {
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : NaN
}

/** Strip read-only `id` before PUT (schedule setting row id). */
function toApiPayload(settings) {
  const clone = cloneConstraintsSettings(settings)
  if (clone && typeof clone === 'object' && 'id' in clone) {
    delete clone.id
  }
  return clone
}

export const useConstraintsStore = defineStore('constraints', () => {
  const initialized = ref(false)
  const settings = ref(createDefaultConstraintsSettings())
  const activeSettings = computed(() => settings.value)

  async function reloadFromApi() {
    const raw = await constraintsService.getCurrent()
    settings.value = normalizeSettingsShape(raw)
  }

  async function ensureInitialized() {
    if (initialized.value) return
    try {
      await reloadFromApi()
    } catch {
      settings.value = createDefaultConstraintsSettings()
    }
    initialized.value = true
  }

  async function saveActiveSettings() {
    if (!validateActiveSettings()) return false
    try {
      const payload = toApiPayload(settings.value)
      await constraintsService.updateCurrent(payload)
      await reloadFromApi()
      return true
    } catch {
      return false
    }
  }

  async function resetActiveSettings() {
    settings.value = createDefaultConstraintsSettings()
    if (!validateActiveSettings()) return false
    try {
      const payload = toApiPayload(settings.value)
      await constraintsService.updateCurrent(payload)
      await reloadFromApi()
      return true
    } catch {
      return false
    }
  }

  function validateActiveSettings() {
    const settingsVal = activeSettings.value
    if (!settingsVal) return false

    const slotMinutes = normalizeNumber(settingsVal.slot_minutes)
    const gapMinutes = normalizeNumber(settingsVal.gap_minutes)
    const maxDailyLectures = normalizeNumber(settingsVal.max_daily_lectures)
    const capacityThreshold = normalizeNumber(settingsVal.capacity_threshold)

    if (!(slotMinutes > 0)) return false
    if (!(gapMinutes >= 0)) return false
    if (!(maxDailyLectures > 0)) return false
    if (!(capacityThreshold >= 0 && capacityThreshold <= 100)) return false
    if (!settingsVal.day_start || !settingsVal.day_end || settingsVal.day_start >= settingsVal.day_end)
      return false

    const hasInvalidBreak = settingsVal.break_times.some(
      (item) => !item.start || !item.end || item.start >= item.end,
    )
    if (hasInvalidBreak) return false

    const hasInvalidLoadRange = settingsVal.load_settings.some((item) => {
      const min = normalizeNumber(item.min)
      const max = normalizeNumber(item.max)
      return !(min >= 0 && max >= min)
    })
    if (hasInvalidLoadRange) return false

    return true
  }

  function getDraftForActiveSemester() {
    return cloneConstraintsSettings(activeSettings.value)
  }

  function replaceActiveSettings(nextSettings) {
    settings.value = normalizeSettingsShape(cloneConstraintsSettings(nextSettings))
  }

  return {
    activeSettings,
    ensureInitialized,
    reloadFromApi,
    saveActiveSettings,
    resetActiveSettings,
    validateActiveSettings,
    getDraftForActiveSemester,
    replaceActiveSettings,
  }
})
