import { apiJson } from '@/api/client'

export const constraintsService = {
  async getCurrent() {
    const { data } = await apiJson('/admin/schedule-settings/current')
    return data
  },

  /**
   * @param {Record<string, unknown>} body
   */
  async updateCurrent(body) {
    const { data } = await apiJson('/admin/schedule-settings/current', {
      method: 'PUT',
      json: body,
    })
    return data
  },
}
