import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { notificationsService } from '@/features/notifications/api/notifications.service'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  const initialized = ref(false)

  const notificationsCount = computed(() => notifications.value.length)
  const unreadCount = computed(() => notifications.value.filter((item) => !item.is_read).length)
  const readCount = computed(() => notificationsCount.value - unreadCount.value)

  async function ensureInitialized() {
    if (initialized.value) return
    const payload = await notificationsService.getNotifications()
    notifications.value = payload
    initialized.value = true
  }

  async function markNotificationAsRead(notificationId) {
    const updated = await notificationsService.markAsRead(notificationId)
    if (!updated) return false
    notifications.value = notifications.value.map((item) =>
      item.id === notificationId ? updated : item,
    )
    return true
  }

  async function markAllNotificationsAsRead() {
    const updated = await notificationsService.markAllAsRead()
    notifications.value = updated
    return true
  }

  async function deleteNotification(notificationId) {
    const deleted = await notificationsService.deleteNotification(notificationId)
    if (!deleted) return false
    notifications.value = notifications.value.filter((item) => item.id !== notificationId)
    return true
  }

  return {
    notifications,
    notificationsCount,
    unreadCount,
    readCount,
    ensureInitialized,
    markNotificationAsRead,
    markAllNotificationsAsRead,
    deleteNotification,
  }
})
