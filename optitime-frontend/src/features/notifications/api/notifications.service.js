import { apiJson } from '@/api/client'

function normalizeItem(item) {
  return {
    ...item,
    payload: item?.payload && typeof item.payload === 'object' ? item.payload : {},
  }
}

export const notificationsService = {
  async getNotifications(options = {}) {
    const { silent = false } = options
    const { data } = await apiJson('/notifications', { skipLoading: silent })
    const rows = Array.isArray(data?.data) ? data.data : []
    return rows.map(normalizeItem)
  },

  async markAsRead(notificationId) {
    await apiJson(`/notifications/${notificationId}/read`, { method: 'POST', skipLoading: true })
    return { id: notificationId, is_read: true }
  },

  async markAllAsRead() {
    const { data } = await apiJson('/notifications/read-all', { method: 'POST', skipLoading: true })
    const rows = Array.isArray(data?.data) ? data.data : []
    return rows.map(normalizeItem)
  },

  async deleteNotification(notificationId) {
    await apiJson(`/notifications/${notificationId}`, { method: 'DELETE', skipLoading: true })
    return true
  },

  async registerDeviceToken({ fcmToken, platform, deviceLabel }) {
    await apiJson('/notifications/devices', {
      method: 'POST',
      skipLoading: true,
      json: {
        fcm_token: fcmToken,
        platform,
        device_label: deviceLabel,
      },
    })
  },

  async unregisterDeviceToken(fcmToken) {
    await apiJson('/notifications/devices', {
      method: 'DELETE',
      skipLoading: true,
      json: {
        fcm_token: fcmToken,
      },
    })
  },
}
