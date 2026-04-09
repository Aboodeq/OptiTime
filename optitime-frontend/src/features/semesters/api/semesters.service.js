import { apiJson } from '@/api/client'

/**
 * Laravel date casts serialize as ISO strings; keep YYYY-MM-DD for forms and tables.
 */
function toYmd(value) {
  if (value == null || value === '') return ''
  const s = String(value)
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10)
  return s
}

function mapApiSemester(row) {
  if (!row || typeof row !== 'object') return row
  return {
    ...row,
    start_date: toYmd(row.start_date),
    end_date: toYmd(row.end_date),
  }
}

export const semestersService = {
  async getSemesters() {
    const { data } = await apiJson('/admin/semesters')
    const list = Array.isArray(data) ? data : []
    return list.map(mapApiSemester)
  },

  async createSemester(payload) {
    const { data } = await apiJson('/admin/semesters', {
      method: 'POST',
      json: payload,
    })
    return mapApiSemester(data)
  },

  async updateSemester(semesterId, payload) {
    const { data } = await apiJson(`/admin/semesters/${semesterId}`, {
      method: 'PUT',
      json: payload,
    })
    return mapApiSemester(data)
  },

  async deleteSemester(semesterId) {
    await apiJson(`/admin/semesters/${semesterId}`, { method: 'DELETE' })
    return true
  },
}
