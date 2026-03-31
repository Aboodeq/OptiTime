export const HARD_CONSTRAINT_KEYS = Object.freeze([
  'no_instructor_overlap',
  'no_room_overlap',
  'room_capacity',
  'lab_for_lab',
  'working_hours',
])

export const SOFT_CONSTRAINT_KEYS = Object.freeze([
  'instructor_pref',
  'load_balance',
  'avoid_back_to_back',
  'student_gap',
  'morning_preference',
  'department_proximity',
])

export function createDefaultConstraintsSettings() {
  return {
    capacity_threshold: 90,
    day_start: '08:00',
    day_end: '16:00',
    slot_minutes: 60,
    gap_minutes: 15,
    max_daily_lectures: 4,
    hard_constraints: HARD_CONSTRAINT_KEYS.map((key) => ({ key, enabled: true })),
    soft_constraints: SOFT_CONSTRAINT_KEYS.map((key) => ({ key, enabled: true, weight: 1 })),
    study_days: [
      { value: 'sun', enabled: true },
      { value: 'mon', enabled: true },
      { value: 'tue', enabled: true },
      { value: 'wed', enabled: true },
      { value: 'thu', enabled: true },
      { value: 'fri', enabled: false },
      { value: 'sat', enabled: false },
    ],
    break_times: [
      { key: 'prayer_noon', start: '12:00', end: '12:30', enabled: true },
      { key: 'prayer_asr', start: '15:00', end: '15:20', enabled: true },
      { key: 'lunch_break', start: '13:30', end: '14:30', enabled: true },
    ],
    room_constraints: [
      { key: 'match_type', enabled: true },
      { key: 'room_proximity', enabled: true },
      { key: 'avoid_floor_scatter', enabled: true },
      { key: 'equipment_match', enabled: true },
      { key: 'max_occupancy_threshold', enabled: true },
    ],
    load_settings: [
      { key: 'instructor_load', min: 6, max: 20 },
      { key: 'coordinator_load', min: 4, max: 16 },
    ],
  }
}

export function cloneConstraintsSettings(settings) {
  return JSON.parse(JSON.stringify(settings))
}
