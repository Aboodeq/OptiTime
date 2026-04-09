export const GRADE_COMPONENT_KEYS = Object.freeze(['oral', 'lab', 'midterm', 'final'])

export const MAX_COMPONENT_SCORE = 25

export const LETTER_GRADE_SCALE = Object.freeze([
  { min: 95, letter: 'A+' },
  { min: 90, letter: 'A' },
  { min: 85, letter: 'B+' },
  { min: 80, letter: 'B' },
  { min: 75, letter: 'C+' },
  { min: 70, letter: 'C' },
  { min: 65, letter: 'D+' },
  { min: 60, letter: 'D' },
  { min: 0, letter: 'F' },
])

export function clampScore(value) {
  const numeric = Number(value)
  if (!Number.isFinite(numeric)) return 0
  return Math.max(0, Math.min(MAX_COMPONENT_SCORE, numeric))
}

export function normalizeScore(value) {
  const clamped = clampScore(value)
  return Math.round(clamped * 100) / 100
}

export function computeTotal(components) {
  return GRADE_COMPONENT_KEYS.reduce((sum, key) => sum + normalizeScore(components?.[key]), 0)
}

export function computeLetterGrade(total) {
  const normalizedTotal = Math.max(0, Math.min(100, Number(total) || 0))
  const matched = LETTER_GRADE_SCALE.find((item) => normalizedTotal >= item.min)
  return matched?.letter ?? 'F'
}
