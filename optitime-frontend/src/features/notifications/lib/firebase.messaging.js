import { initializeApp } from 'firebase/app'
import { getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging'

let appInstance = null
let messagingInstance = null

function firebaseConfig() {
  return {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
  }
}

function hasFirebaseConfig() {
  const cfg = firebaseConfig()
  return Boolean(cfg.apiKey && cfg.projectId && cfg.messagingSenderId && cfg.appId)
}

async function getMessagingInstance() {
  if (!(await isSupported()) || !hasFirebaseConfig()) return null
  if (!appInstance) appInstance = initializeApp(firebaseConfig())
  if (!messagingInstance) messagingInstance = getMessaging(appInstance)
  return messagingInstance
}

export async function requestFcmToken() {
  const messaging = await getMessagingInstance()
  const vapidKey = import.meta.env.VITE_FIREBASE_VAPID_KEY
  if (!messaging || !vapidKey) {
    console.warn('[FCM] Missing messaging instance or VAPID key')
    return null
  }
  if (typeof Notification !== 'undefined') {
    const permission = await Notification.requestPermission()
    if (permission !== 'granted') {
      console.warn('[FCM] Notification permission is not granted:', permission)
      return null
    }
  }
  try {
    const token = await getToken(messaging, {
      vapidKey,
      serviceWorkerRegistration: await navigator.serviceWorker.register('/firebase-messaging-sw.js'),
    })
    if (!token) {
      console.warn('[FCM] getToken returned empty token')
      return null
    }
    console.info('[FCM] Token acquired')
    return token
  } catch (error) {
    console.warn('[FCM] Failed to get token', error)
    return null
  }
}

export async function listenForegroundNotifications(onPayload) {
  const messaging = await getMessagingInstance()
  if (!messaging) return () => {}
  return onMessage(messaging, (payload) => {
    if (typeof onPayload === 'function') onPayload(payload)
  })
}
