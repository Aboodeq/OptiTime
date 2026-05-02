import { useToast } from 'vue-toastification'
import { requestFcmToken, listenForegroundNotifications } from '@/features/notifications/lib/firebase.messaging'
import { notificationsService } from '@/features/notifications/api/notifications.service'
import { useNotificationsStore } from '@/features/notifications/model/stores/notifications.store'
import { useAuthStore } from '@/store/auth.store'

function mapFcmPayload(payload) {
  const now = new Date().toISOString()
  const data = payload?.data ?? {}
  const id = data.notification_id || `fcm-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`
  return {
    id,
    title: payload?.notification?.title || data.title || 'Notification',
    message: payload?.notification?.body || data.body || '',
    type: data.notification_type || 'announcement',
    priority: data.priority || 'high',
    is_read: false,
    created_at: now,
    payload: {
      route: data.route || data.link || '/notifications',
      ...data,
    },
  }
}

let stopListening = null
let activeToken = null
let pollTimer = null
const knownNotificationIds = new Set()

export async function setupNotificationsRealtime() {
  const authStore = useAuthStore()
  if (!authStore.isAuthenticated) return
  const notificationsStore = useNotificationsStore()
  const toast = useToast()
  await notificationsStore.ensureInitialized()
  for (const item of notificationsStore.notifications) {
    if (item?.id) knownNotificationIds.add(item.id)
  }

  const token = await requestFcmToken()
  if (token && token !== activeToken) {
    try {
      await notificationsService.registerDeviceToken({
        fcmToken: token,
        platform: 'web',
        deviceLabel: navigator.userAgent.slice(0, 70),
      })
      activeToken = token
      console.info('[FCM] Token registered in backend')
    } catch (error) {
      console.warn('[FCM] Failed to register token in backend', error)
    }
  } else if (!token) {
    console.warn('[FCM] Token not available; skipping backend registration')
  }

  if (!stopListening) {
    stopListening = await listenForegroundNotifications((payload) => {
      const notification = mapFcmPayload(payload)
      knownNotificationIds.add(notification.id)
      notificationsStore.pushRealtimeNotification(notification)
      toast.info(notification.title)
    })
  }

  // Fallback for cases where browser/FCM foreground delivery is delayed.
  if (!pollTimer) {
    pollTimer = setInterval(async () => {
      try {
        const latest = await notificationsService.getNotifications({ silent: true })
        const unseen = latest.filter((item) => item?.id && !knownNotificationIds.has(item.id))
        if (unseen.length === 0) return
        for (const item of unseen.reverse()) {
          knownNotificationIds.add(item.id)
          notificationsStore.pushRealtimeNotification(item)
        }
      } catch (error) {
        console.warn('[FCM] Polling fallback failed', error)
      }
    }, 8000)
  }
}

export async function teardownNotificationsRealtime() {
  if (typeof stopListening === 'function') {
    stopListening()
    stopListening = null
  }
  if (activeToken) {
    try {
      await notificationsService.unregisterDeviceToken(activeToken)
    } catch (error) {
      console.warn('[FCM] Failed to unregister token', error)
    }
    activeToken = null
  }
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
  knownNotificationIds.clear()
}
