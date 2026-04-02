const NOTIFICATIONS_SEED = [
  {
    id: 'notif-1',
    title: 'Schedule updated',
    message: 'Your Tuesday lecture was moved from 10:00 to 11:00.',
    type: 'schedule',
    priority: 'high',
    is_read: false,
    created_at: '2026-03-31T07:30:00.000Z',
  },
  {
    id: 'notif-2',
    title: 'Room assignment changed',
    message: 'Database Systems now takes place in Lab B2.',
    type: 'announcement',
    priority: 'medium',
    is_read: false,
    created_at: '2026-03-30T12:05:00.000Z',
  },
  {
    id: 'notif-3',
    title: 'Reminder',
    message: 'Course registration closes in 2 days.',
    type: 'reminder',
    priority: 'low',
    is_read: true,
    created_at: '2026-03-29T09:15:00.000Z',
  },
]

let notificationsDb = NOTIFICATIONS_SEED.map((item) => ({ ...item }))

function cloneNotification(item) {
  return { ...item }
}

export const notificationsService = {
  async getNotifications() {
    return notificationsDb.map(cloneNotification)
  },

  async markAsRead(notificationId) {
    let changed = null
    notificationsDb = notificationsDb.map((item) => {
      if (item.id !== notificationId) return item
      changed = { ...item, is_read: true }
      return changed
    })
    return changed ? cloneNotification(changed) : null
  },

  async markAllAsRead() {
    notificationsDb = notificationsDb.map((item) => ({ ...item, is_read: true }))
    return notificationsDb.map(cloneNotification)
  },

  async deleteNotification(notificationId) {
    const before = notificationsDb.length
    notificationsDb = notificationsDb.filter((item) => item.id !== notificationId)
    return notificationsDb.length < before
  },
}
