import { apiJson } from '@/api/client'

/**
 * Map Laravel admin instructor row + faculties tree to the table row shape used by the UI.
 * @param {Record<string, unknown>} apiRow
 * @param {unknown[]} faculties
 */
export function mapApiInstructorToRow(apiRow, faculties = []) {
  const departmentId = apiRow.department_id ?? ''
  let facultyId = ''
  if (departmentId && Array.isArray(faculties)) {
    for (const faculty of faculties) {
      if (faculty.departments?.some((d) => d.id === departmentId)) {
        facultyId = faculty.id
        break
      }
    }
  }

  return {
    id: apiRow.id,
    user_id: apiRow.user_id,
    name: apiRow.name ?? '',
    email: apiRow.email ?? '',
    speciality_id: apiRow.specialization_id ?? '',
    min_work_hours_per_week: apiRow.min_work_hours_per_week,
    max_work_hours_per_week: apiRow.max_work_hours_per_week,
    faculty_id: facultyId,
    department_id: departmentId,
    is_active: Boolean(apiRow.is_active),
    role: 'instructor',
  }
}

export const instructorsService = {
  async getInstructorsRaw() {
    const { data } = await apiJson('/admin/instructors')
    return Array.isArray(data) ? data : []
  },

  /**
   * @param {Record<string, unknown>} body
   */
  async createInstructor(body) {
    const { data } = await apiJson('/admin/instructors', {
      method: 'POST',
      json: body,
    })
    return data
  },

  /**
   * @param {string} id - instructor profile id (UUID)
   * @param {Record<string, unknown>} body
   */
  async updateInstructor(id, body) {
    const { data } = await apiJson(`/admin/instructors/${id}`, {
      method: 'PUT',
      json: body,
    })
    return data
  },

  async deleteInstructor(id) {
    await apiJson(`/admin/instructors/${id}`, { method: 'DELETE' })
  },
}
