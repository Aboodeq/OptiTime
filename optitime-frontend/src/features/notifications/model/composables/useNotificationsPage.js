import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { useNotificationsStore } from '@/features/notifications/model/stores/notifications.store'

function byNewestDate(a, b) {
  return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
}

export function useNotificationsPage() {
  const notificationsStore = useNotificationsStore()
  notificationsStore.ensureInitialized()

  const { notifications, notificationsCount, unreadCount, readCount } =
    storeToRefs(notificationsStore)

  const sortedNotifications = computed(() => [...notifications.value].sort(byNewestDate))

  async function markAsRead(notificationId) {
    return notificationsStore.markNotificationAsRead(notificationId)
  }

  async function markAllAsRead() {
    return notificationsStore.markAllNotificationsAsRead()
  }

  async function removeNotification(notificationId) {
    return notificationsStore.deleteNotification(notificationId)
  }

  return {
    notifications: sortedNotifications,
    notificationsCount,
    unreadCount,
    readCount,
    markAsRead,
    markAllAsRead,
    removeNotification,
  }
}
