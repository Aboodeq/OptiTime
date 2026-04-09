import { apiJson } from '@/api/client'

function permissionCodesFromRole(role) {
  if (!role?.permissions?.length) return []
  return role.permissions
    .map((p) => (typeof p === 'string' ? p : p?.code))
    .filter(Boolean)
}

/**
 * Normalize API role for the UI: `permissions` is always an array of codes.
 */
export function mapRoleFromApi(role) {
  if (!role) return null
  return {
    ...role,
    permissions: permissionCodesFromRole(role),
  }
}

export const rolesService = {
  async getPermissionsCatalog() {
    const { data } = await apiJson('/admin/permissions')
    return Array.isArray(data) ? data : []
  },

  async getRoles() {
    const { data } = await apiJson('/admin/roles')
    const list = Array.isArray(data) ? data : []
    return list.map((r) => mapRoleFromApi(r)).filter(Boolean)
  },

  async createRole(body) {
    const { data } = await apiJson('/admin/roles', {
      method: 'POST',
      json: body,
    })
    return mapRoleFromApi(data)
  },

  async updateRole(id, body) {
    const { data } = await apiJson(`/admin/roles/${id}`, {
      method: 'PUT',
      json: body,
    })
    return mapRoleFromApi(data)
  },

  async deleteRole(id) {
    await apiJson(`/admin/roles/${id}`, { method: 'DELETE' })
  },
}
