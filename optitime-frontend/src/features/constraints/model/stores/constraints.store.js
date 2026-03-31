import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
  cloneConstraintsSettings,
  createDefaultConstraintsSettings,
} from '@/features/constraints/model/constants/defaultConstraints'

const STORAGE_KEY = 'optitime.constraints.settings.v1'

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

function loadSavedSettings() {
  if (typeof window === 'undefined') return createDefaultConstraintsSettings()
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    if (!raw) return createDefaultConstraintsSettings()
    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== 'object') return createDefaultConstraintsSettings()

    // Backward compatibility: previous version stored settings keyed by semester id.
    if (!Array.isArray(parsed.hard_constraints)) {
      const firstValue = Object.values(parsed)[0]
      if (firstValue && typeof firstValue === 'object') {
        return normalizeSettingsShape(firstValue)
      }
      return createDefaultConstraintsSettings()
    }

    return normalizeSettingsShape(parsed)
  } catch {
    return createDefaultConstraintsSettings()
  }
}

function persistSettings(value) {
  if (typeof window === 'undefined') return
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
  } catch {
    // ignore storage errors
  }
}

function normalizeNumber(value) {
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : NaN
}

export const useConstraintsStore = defineStore('constraints', () => {
  const initialized = ref(false)
  const settings = ref(createDefaultConstraintsSettings())
  const activeSettings = computed(() => settings.value)

  function ensureInitialized() {
    if (initialized.value) return
    settings.value = loadSavedSettings()
    initialized.value = true
  }

  function saveActiveSettings() {
    persistSettings(settings.value)
    return true
  }

  function resetActiveSettings() {
    settings.value = createDefaultConstraintsSettings()
    persistSettings(settings.value)
    return true
  }

  function validateActiveSettings() {
    const settings = activeSettings.value
    if (!settings) return false

    const slotMinutes = normalizeNumber(settings.slot_minutes)
    const gapMinutes = normalizeNumber(settings.gap_minutes)
    const maxDailyLectures = normalizeNumber(settings.max_daily_lectures)
    const capacityThreshold = normalizeNumber(settings.capacity_threshold)

    if (!(slotMinutes > 0)) return false
    if (!(gapMinutes >= 0)) return false
    if (!(maxDailyLectures > 0)) return false
    if (!(capacityThreshold >= 0 && capacityThreshold <= 100)) return false
    if (!settings.day_start || !settings.day_end || settings.day_start >= settings.day_end) return false

    const hasInvalidBreak = settings.break_times.some(
      (item) => !item.start || !item.end || item.start >= item.end,
    )
    if (hasInvalidBreak) return false

    const hasInvalidLoadRange = settings.load_settings.some((item) => {
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
    saveActiveSettings,
    resetActiveSettings,
    validateActiveSettings,
    getDraftForActiveSemester,
    replaceActiveSettings,
  }
})
