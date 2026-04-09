import { apiJson } from '@/api/client'

function mapApiResource(row) {
  if (!row || typeof row !== 'object') return row
  const q = row.quantity
  const quantity = typeof q === 'number' ? q : Number.parseInt(String(q), 10)
  return {
    ...row,
    quantity: Number.isFinite(quantity) ? quantity : 0,
  }
}

export const resourcesService = {
  async getResources() {
    const { data } = await apiJson('/admin/resources')
    const list = Array.isArray(data) ? data : []
    return list.map(mapApiResource)
  },

  async createResource(payload) {
    const { data } = await apiJson('/admin/resources', {
      method: 'POST',
      json: payload,
    })
    return mapApiResource(data)
  },

  async updateResource(resourceId, payload) {
    const { data } = await apiJson(`/admin/resources/${resourceId}`, {
      method: 'PUT',
      json: payload,
    })
    return mapApiResource(data)
  },

  async deleteResource(resourceId) {
    await apiJson(`/admin/resources/${resourceId}`, { method: 'DELETE' })
    return true
  },
}
