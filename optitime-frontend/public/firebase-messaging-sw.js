/* eslint-disable no-undef */
importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js')
importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js')

firebase.initializeApp({
  apiKey: 'AIzaSyDkx_9GDLAARDL7ogJKcck21HkCj59v2Xc',
  authDomain: 'optitime-b9ab1.firebaseapp.com',
  projectId: 'optitime-b9ab1',
  storageBucket: 'optitime-b9ab1.firebasestorage.app',
  messagingSenderId: '95144000238',
  appId: '1:95144000238:web:a5adf9019bf2e66c44d1ff',
})

const messaging = firebase.messaging()

messaging.onBackgroundMessage((payload) => {
  const notificationTitle = payload?.notification?.title || payload?.data?.title || 'Notification'
  const notificationOptions = {
    body: payload?.notification?.body || payload?.data?.body || '',
    data: {
      route: payload?.data?.route || payload?.data?.link || '/notifications',
    },
  }
  self.registration.showNotification(notificationTitle, notificationOptions)
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  const route = event.notification?.data?.route || '/notifications'
  event.waitUntil(clients.openWindow(route))
})
