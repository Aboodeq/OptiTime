<template>
  <AppShell :page-title="t('routes.notifications')">
    <section class="dashboard-card w-100">
      <div class="notifications-toolbar">
        <div>
          <h1 class="h4 fw-bold mb-1">{{ t('pages.notifications.title') }}</h1>
          <p class="dashboard-card__meta mb-0">{{ t('pages.notifications.subtitle') }}</p>
        </div>
        <AppButton
          type="button"
          :tone-color="authStore.roleColor"
          :disabled="!unreadCount"
          @click="handleMarkAllAsRead"
        >
          {{ t('pages.notifications.actions.markAllAsRead') }}
        </AppButton>
      </div>

      <AppStatsGrid :cards="statsCards" class="mb-3 mt-3" />

      <div class="notifications-list">
        <article
          v-for="notification in notifications"
          :key="notification.id"
          class="notification-item"
          :class="{ 'notification-item--unread': !notification.is_read }"
        >
          <div class="notification-item__content">
            <div class="notification-item__meta">
              <span class="badge text-bg-light border">
                {{ t(`pages.notifications.types.${notification.type}`) }}
              </span>
              <span class="badge" :class="priorityClass(notification.priority)">
                {{ t(`pages.notifications.priority.${notification.priority}`) }}
              </span>
              <small class="text-secondary">{{ formatDate(notification.created_at) }}</small>
            </div>
            <h2 class="h6 mb-1">{{ notification.title }}</h2>
            <p class="mb-0 text-secondary">{{ notification.message }}</p>
            <div
              v-if="approvedLectureRequestDetails(notification).length"
              class="notification-item__details text-secondary"
            >
              <p
                v-for="(line, index) in approvedLectureRequestDetails(notification)"
                :key="`${notification.id}-detail-${index}`"
                class="mb-0"
              >
                {{ line }}
              </p>
            </div>
            <button class="notification-item__open" type="button" @click="openNotification(notification)">
              {{ t('nav.topbar.viewAllNotifications') }}
            </button>
          </div>
          <div class="notification-item__actions">
            <AppIconButton
              v-if="!notification.is_read"
              icon="bi bi-check2-circle"
              variant="primary"
              :title="t('pages.notifications.actions.markAsRead')"
              :aria-label="t('pages.notifications.actions.markAsRead')"
              @click="handleMarkAsRead(notification.id)"
            />
            <AppIconButton
              icon="bi bi-trash3"
              variant="danger"
              :title="t('pages.notifications.actions.delete')"
              :aria-label="t('pages.notifications.actions.delete')"
              @click="handleDelete(notification.id)"
            />
          </div>
        </article>

        <p v-if="!notifications.length" class="text-secondary mb-0">
          {{ t('pages.notifications.empty') }}
        </p>
      </div>
    </section>
  </AppShell>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppButton from '@/components/common/AppButton.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useNotificationsPage } from '@/features/notifications/model/composables/useNotificationsPage'
import { useAuthStore } from '@/store/auth.store'

const { t, locale } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const { notifications, notificationsCount, unreadCount, readCount, markAsRead, markAllAsRead, removeNotification } =
  useNotificationsPage()

const statsCards = computed(() => [
  {
    id: 'total',
    value: notificationsCount.value,
    label: t('pages.notifications.stats.total'),
    icon: 'bi bi-bell',
    iconColor: '#4361ee',
  },
  {
    id: 'unread',
    value: unreadCount.value,
    label: t('pages.notifications.stats.unread'),
    icon: 'bi bi-bell-fill',
    iconColor: '#e11d48',
  },
  {
    id: 'read',
    value: readCount.value,
    label: t('pages.notifications.stats.read'),
    icon: 'bi bi-check2-all',
    iconColor: '#22c55e',
  },
])

function priorityClass(priority) {
  if (priority === 'high') return 'text-bg-danger'
  if (priority === 'medium') return 'text-bg-warning'
  return 'text-bg-secondary'
}

function formatDate(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString(locale.value === 'ar' ? 'ar-SY' : 'en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function approvedLectureRequestDetails(notification) {
  const payload = notification?.payload && typeof notification.payload === 'object' ? notification.payload : {}
  const status = `${payload?.status || ''}`.trim().toLowerCase()
  if (notification?.type !== 'lecture_request_reviewed' || status !== 'approved') {
    return []
  }

  const lectureRequest =
    payload?.lecture_request && typeof payload.lecture_request === 'object' ? payload.lecture_request : {}
  const session =
    payload?.schedule_session && typeof payload.schedule_session === 'object' ? payload.schedule_session : {}
  const course = session?.course && typeof session.course === 'object' ? session.course : {}
  const section = session?.section && typeof session.section === 'object' ? session.section : {}

  const requestedDate = `${lectureRequest?.requested_date || ''}`.trim()
  const courseCode = `${course?.code || ''}`.trim()
  const courseName = `${course?.name || ''}`.trim()
  const sectionName = `${section?.section_name || ''}`.trim()
  const day = `${session?.day_of_week || session?.day_value || ''}`.trim()
  const start = `${session?.start_time || ''}`.trim()
  const end = `${session?.end_time || ''}`.trim()

  const lines = []
  if (requestedDate) lines.push(`Date: ${requestedDate}`)
  const lectureLabel = [courseCode, courseName].filter(Boolean).join(' - ')
  if (lectureLabel || sectionName) {
    const sectionSuffix = sectionName ? ` (${sectionName})` : ''
    lines.push(`Lecture: ${lectureLabel || 'Lecture'}${sectionSuffix}`)
  }
  if (day && start && end) {
    lines.push(`Time: ${day}, ${start}-${end}`)
  }

  return lines
}

async function handleMarkAsRead(notificationId) {
  await markAsRead(notificationId)
}

async function openNotification(notification) {
  await markAsRead(notification.id)
  router.push(notification?.payload?.route || '/notifications')
}

async function handleMarkAllAsRead() {
  await markAllAsRead()
}

async function handleDelete(notificationId) {
  await removeNotification(notificationId)
}
</script>

<style scoped>
.notifications-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.notifications-list {
  display: grid;
  gap: 12px;
}

.notification-item {
  border: 1px solid #eef0f7;
  border-radius: 12px;
  padding: 12px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  background: #fff;
}

.notification-item--unread {
  border-left: 4px solid #4361ee;
}

.notification-item__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.notification-item__actions {
  display: flex;
  align-items: flex-start;
  gap: 6px;
}

.notification-item__open {
  margin-top: 8px;
  border: none;
  background: transparent;
  color: #4361ee;
  font-weight: 700;
  padding: 0;
}

.notification-item__details {
  margin-top: 6px;
  font-size: 0.9rem;
}
</style>
