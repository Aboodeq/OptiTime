import { apiJson } from '@/api/client'

export const EXCLUDED_ROLE_KEYS = Object.freeze(['student', 'instructor'])

/**
 * Map Laravel user + nested faculties to the list row shape used by the UI.
 */
export function mapApiUserToRow(apiUser, faculties = []) {
  const roleKey = apiUser.role?.code ?? ''
  const departmentId = apiUser.department_id ?? ''
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
    id: apiUser.id,
    name: apiUser.full_name ?? '',
    email: apiUser.email,
    role: roleKey,
    role_id: apiUser.role_id,
    department_id: departmentId || '',
    faculty_id: facultyId,
    is_active: Boolean(apiUser.is_active),
  }
}

export const usersService = {
  getExcludedRoleKeys() {
    return [...EXCLUDED_ROLE_KEYS]
  },

  async getUsersRaw() {
    const { data } = await apiJson('/admin/users')
    return Array.isArray(data) ? data : []
  },

  async getRolesRaw() {
    const { data } = await apiJson('/admin/roles')
    return Array.isArray(data) ? data : []
  },

  /**
   * Roles assignable in the admin user form (excludes student/instructor).
   */
  async getManageableRoles() {
    const roles = await this.getRolesRaw()
    const excluded = new Set(EXCLUDED_ROLE_KEYS)
    return roles
      .filter((r) => r.code && !excluded.has(r.code))
      .map((r) => ({
        id: r.id,
        key: r.code,
        label: r.name_en ?? r.code,
      }))
      .sort((a, b) => a.key.localeCompare(b.key))
  },

  async createUser(body) {
    const { data } = await apiJson('/admin/users', {
      method: 'POST',
      json: body,
    })
    return data
  },

  async updateUser(id, body) {
    const { data } = await apiJson(`/admin/users/${id}`, {
      method: 'PUT',
      json: body,
    })
    return data
  },

  async deleteUser(id) {
    await apiJson(`/admin/users/${id}`, { method: 'DELETE' })
  },
}
