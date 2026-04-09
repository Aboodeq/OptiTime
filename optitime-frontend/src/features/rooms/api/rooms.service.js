import { apiJson } from '@/api/client'

function mapApiRoom(row) {
  if (!row || typeof row !== 'object') return row
  const c = row.capacity
  const capacity = typeof c === 'number' ? c : Number.parseInt(String(c), 10)
  const ids = row.resource_ids
  const resourceIds = Array.isArray(ids)
    ? ids.filter((id) => typeof id === 'string' && id.trim())
    : []
  return {
    ...row,
    capacity: Number.isFinite(capacity) ? capacity : 0,
    resource_ids: resourceIds,
  }
}

export const roomsService = {
  async getRooms() {
    const { data } = await apiJson('/admin/rooms')
    const list = Array.isArray(data) ? data : []
    return list.map(mapApiRoom)
  },

  async createRoom(payload) {
    const { data } = await apiJson('/admin/rooms', {
      method: 'POST',
      json: payload,
    })
    return mapApiRoom(data)
  },

  async updateRoom(roomId, payload) {
    const { data } = await apiJson(`/admin/rooms/${roomId}`, {
      method: 'PUT',
      json: payload,
    })
    return mapApiRoom(data)
  },

  async deleteRoom(roomId) {
    await apiJson(`/admin/rooms/${roomId}`, { method: 'DELETE' })
    return true
  },
}
