import { apiJson } from '@/api/client'

/**
 * Map Laravel admin student row + faculties tree to the table row shape used by the UI.
 * @param {Record<string, unknown>} apiRow
 * @param {unknown[]} faculties
 */
export function mapApiStudentToRow(apiRow, faculties = []) {
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
    id: apiRow.id ?? apiRow.user_id,
    user_id: apiRow.user_id,
    name: apiRow.name ?? '',
    email: apiRow.email ?? '',
    university_number: apiRow.university_number ?? '',
    completed_hours: apiRow.completed_hours,
    year_level: apiRow.year_level,
    study_status: apiRow.study_status ?? 'regular',
    faculty_id: facultyId,
    department_id: departmentId,
    is_active: Boolean(apiRow.is_active),
  }
}

export const studentsService = {
  async getStudentsRaw() {
    const { data } = await apiJson('/admin/students')
    return Array.isArray(data) ? data : []
  },

  /**
   * @param {Record<string, unknown>} body
   */
  async createStudent(body) {
    const { data } = await apiJson('/admin/students', {
      method: 'POST',
      json: body,
    })
    return data
  },

  /**
   * @param {string} id - user id (student PK)
   * @param {Record<string, unknown>} body
   */
  async updateStudent(id, body) {
    const { data } = await apiJson(`/admin/students/${id}`, {
      method: 'PUT',
      json: body,
    })
    return data
  },

  async deleteStudent(id) {
    await apiJson(`/admin/students/${id}`, { method: 'DELETE' })
  },
}
