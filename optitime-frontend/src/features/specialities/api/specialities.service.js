import { apiJson } from '@/api/client'

export const specialitiesService = {
  async getSpecialities() {
    const { data } = await apiJson('/admin/specializations')
    return Array.isArray(data) ? data : []
  },

  async createSpeciality(body) {
    const { data } = await apiJson('/admin/specializations', {
      method: 'POST',
      json: body,
    })
    return data
  },

  async updateSpeciality(id, body) {
    const { data } = await apiJson(`/admin/specializations/${id}`, {
      method: 'PUT',
      json: body,
    })
    return data
  },

  async deleteSpeciality(id) {
    await apiJson(`/admin/specializations/${id}`, { method: 'DELETE' })
  },
}
