import { apiFetch, apiJson } from '@/api/client'

/**
 * @param {string} [semesterId] — omit to use active semester (server-side).
 */
export async function getWeeklySchedule(semesterId) {
  const q =
    semesterId != null && `${semesterId}`.trim() !== ''
      ? `?semester_id=${encodeURIComponent(semesterId)}`
      : ''
  const { data } = await apiJson(`/instructor/weekly-schedule${q}`)
  return data && typeof data === 'object' ? data : { schedule: null, items: [] }
}

/**
 * @param {string} [semesterId]
 * @returns {Promise<Blob>}
 */
export async function downloadWeeklyPdf(semesterId) {
  const q =
    semesterId != null && `${semesterId}`.trim() !== ''
      ? `?semester_id=${encodeURIComponent(semesterId)}`
      : ''
  const response = await apiFetch(`/instructor/weekly-schedule/pdf${q}`, {
    method: 'GET',
    headers: { Accept: 'application/pdf' },
  })
  if (!response.ok) {
    const text = await response.text()
    const err = new Error(text || response.statusText || 'PDF download failed')
    err.status = response.status
    throw err
  }
  return response.blob()
}

export const instructorWeeklyScheduleService = {
  getWeeklySchedule,
  downloadWeeklyPdf,
}
