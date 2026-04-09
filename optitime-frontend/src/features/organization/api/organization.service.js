import { apiJson } from '@/api/client'

function sortByCode(a, b) {
  const ca = a.code ?? ''
  const cb = b.code ?? ''
  return String(ca).localeCompare(String(cb))
}

/**
 * Build nested faculties with `departments[]` from flat API lists.
 */
export function mergeFacultiesAndDepartments(facultyList, departmentList) {
  const faculties = Array.isArray(facultyList) ? [...facultyList].sort(sortByCode) : []
  const departments = Array.isArray(departmentList) ? [...departmentList].sort(sortByCode) : []

  const byId = new Map(faculties.map((f) => [f.id, { ...f, departments: [] }]))

  for (const dept of departments) {
    const facultyId = dept.faculty_id
    const slot = facultyId ? byId.get(facultyId) : null
    if (slot) {
      slot.departments.push({ ...dept })
    }
  }

  return [...byId.values()]
}

export const organizationService = {
  async getFacultiesWithDepartments() {
    const [{ data: facultyData }, { data: departmentData }] = await Promise.all([
      apiJson('/admin/faculties'),
      apiJson('/admin/departments'),
    ])
    return mergeFacultiesAndDepartments(facultyData, departmentData)
  },

  async createFaculty(body) {
    const { data } = await apiJson('/admin/faculties', {
      method: 'POST',
      json: body,
    })
    return data
  },

  async updateFaculty(id, body) {
    const { data } = await apiJson(`/admin/faculties/${id}`, {
      method: 'PUT',
      json: body,
    })
    return data
  },

  async deleteFaculty(id) {
    await apiJson(`/admin/faculties/${id}`, { method: 'DELETE' })
  },

  async createDepartment(body) {
    const { data } = await apiJson('/admin/departments', {
      method: 'POST',
      json: body,
    })
    return data
  },

  async updateDepartment(id, body) {
    const { data } = await apiJson(`/admin/departments/${id}`, {
      method: 'PUT',
      json: body,
    })
    return data
  },

  async deleteDepartment(id) {
    await apiJson(`/admin/departments/${id}`, { method: 'DELETE' })
  },
}
