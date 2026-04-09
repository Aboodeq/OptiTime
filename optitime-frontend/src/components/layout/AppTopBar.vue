<template>
  <header class="topbar">
    <div class="page-meta">
      <span class="page-ttl">{{ pageTitle }}</span>
      <div class="breadcrumb-custom">
        <span>{{ t('common.appName') }}</span>
        <i :class="crumbIcon"></i>
        <span class="bc-active">{{ pageTitle }}</span>
      </div>
    </div>

    <div class="topbar-actions">
      <AppIconButton
        v-if="isMobile"
        class="action-btn"
        icon="bi bi-list"
        variant="neutral"
        size="md"
        :aria-label="t('nav.sidebar.open')"
        :title="t('nav.sidebar.open')"
        @click="$emit('open-sidebar')"
      />

      <div class="search-bar">
        <AppSearchField
          :model-value="quickSearch"
          :placeholder="t('nav.topbar.quickSearch')"
          compact
          @update:model-value="quickSearch = $event"
        />
      </div>

      <AppButton
        class="lang-btn"
        type="button"
        :title="t('nav.topbar.languageToggle')"
        @click="toggleLocale"
      >
        <i class="bi bi-translate"></i>
        <span class="lang-code">{{ uiStore.locale.toUpperCase() }}</span>
      </AppButton>

      <div ref="notificationsMenuRef" class="menu-wrap">
        <AppIconButton
          class="action-btn"
          icon="bi bi-bell"
          variant="neutral"
          size="md"
          :aria-label="t('nav.topbar.notifications')"
          :title="t('nav.topbar.notifications')"
          @click="toggleNotificationsMenu"
        />
        <span v-if="unreadCount > 0" class="notif-badge">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        <transition name="menu-pop">
          <div v-if="isNotificationsMenuOpen" class="menu-panel notifications-menu">
            <div class="menu-head">
              <strong>{{ t('nav.topbar.notifications') }}</strong>
              <button
                v-if="unreadCount"
                type="button"
                class="menu-link"
                @click="markAllAsRead"
              >
                {{ t('nav.topbar.markAllRead') }}
              </button>
            </div>
            <div v-if="topNotifications.length" class="menu-items">
              <button
                v-for="item in topNotifications"
                :key="item.id"
                type="button"
                class="notif-item"
                :class="{ 'notif-item--unread': !item.is_read }"
                @click="openNotification(item.id)"
              >
                <span class="notif-item__title">{{ item.title }}</span>
                <small class="notif-item__meta">{{ formatDate(item.created_at) }}</small>
              </button>
            </div>
            <p v-else class="menu-empty">{{ t('pages.notifications.empty') }}</p>
            <button type="button" class="menu-view-all" @click="goToNotifications">
              {{ t('nav.topbar.viewAllNotifications') }}
            </button>
          </div>
        </transition>
      </div>

      <div ref="userMenuRef" class="menu-wrap">
        <button
          type="button"
          class="avatar-trigger"
          :aria-label="t('nav.topbar.userMenu')"
          :title="t('nav.topbar.userMenu')"
          @click="toggleUserMenu"
        >
          <img
            v-if="userAvatarUrl"
            class="av-btn av-btn--image"
            :src="userAvatarUrl"
            alt="User avatar"
          />
          <div v-else class="av-btn" :style="{ background: roleColor }">{{ userInitial }}</div>
        </button>
        <transition name="menu-pop">
          <div v-if="isUserMenuOpen" class="menu-panel user-menu">
            <button type="button" class="menu-item" @click="goToSettings">
              <i class="bi bi-gear-fill" aria-hidden="true"></i>
              <span>{{ t('routes.userSettings') }}</span>
            </button>
            <button type="button" class="menu-item menu-item--danger" @click="handleLogout">
              <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
              <span>{{ t('common.actions.logout') }}</span>
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppButton from '@/components/common/AppButton.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import AppSearchField from '@/components/common/AppSearchField.vue'
import { useNotificationsStore } from '@/features/notifications/model/stores/notifications.store'
import { useAuthStore } from '@/store/auth.store'
import { useUiStore } from '@/store/ui.store'

const props = defineProps({
  pageTitle: { type: String, required: true },
  roleColor: { type: String, default: '#334155' },
  userInitial: { type: String, default: '?' },
  userAvatar: { type: String, default: '' },
  isMobile: { type: Boolean, default: false },
})

defineEmits(['open-sidebar'])

const { t, locale } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUiStore()
const notificationsStore = useNotificationsStore()
notificationsStore.ensureInitialized()

const quickSearch = ref('')
const isNotificationsMenuOpen = ref(false)
const isUserMenuOpen = ref(false)
const notificationsMenuRef = ref(null)
const userMenuRef = ref(null)

const crumbIcon = computed(() => (uiStore.isRtl ? 'bi bi-chevron-left' : 'bi bi-chevron-right'))
const unreadCount = computed(() => notificationsStore.unreadCount)
const topNotifications = computed(() =>
  [...notificationsStore.notifications]
    .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
    .slice(0, 5),
)
const userAvatarUrl = computed(() => props.userAvatar || authStore.user?.avatar_url || '')

function toggleLocale() {
  uiStore.setLocale(uiStore.locale === 'ar' ? 'en' : 'ar')
}

function toggleNotificationsMenu() {
  isNotificationsMenuOpen.value = !isNotificationsMenuOpen.value
  if (isNotificationsMenuOpen.value) isUserMenuOpen.value = false
}

function toggleUserMenu() {
  isUserMenuOpen.value = !isUserMenuOpen.value
  if (isUserMenuOpen.value) isNotificationsMenuOpen.value = false
}

function closeMenus() {
  isNotificationsMenuOpen.value = false
  isUserMenuOpen.value = false
}

function handleDocumentClick(event) {
  const target = event.target
  if (notificationsMenuRef.value && notificationsMenuRef.value.contains(target)) return
  if (userMenuRef.value && userMenuRef.value.contains(target)) return
  closeMenus()
}

async function openNotification(notificationId) {
  await notificationsStore.markNotificationAsRead(notificationId)
  closeMenus()
  router.push({ name: 'notifications' })
}

async function markAllAsRead() {
  await notificationsStore.markAllNotificationsAsRead()
}

function goToNotifications() {
  closeMenus()
  router.push({ name: 'notifications' })
}

function goToSettings() {
  closeMenus()
  router.push({ name: 'user-settings' })
}

async function handleLogout() {
  closeMenus()
  await authStore.logout()
  router.push('/login')
}

function formatDate(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString(locale.value === 'ar' ? 'ar-SY' : 'en-US', {
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  if (typeof document === 'undefined') return
  document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
  if (typeof document === 'undefined') return
  document.removeEventListener('click', handleDocumentClick)
})
</script>

<style scoped>
.topbar {
  width: 100%;
  background: #fff;
  border: 1px solid #eef0f7;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  margin-bottom: 12px;
  gap: 12px;
}
.page-ttl {
  font-size: 15px;
  font-weight: 800;
  color: #1e2a3a;
  line-height: 1;
}
.breadcrumb-custom {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 3px;
  font-size: 11px;
  color: #b0b8cc;
}
.bc-active {
  color: #4361ee;
  font-weight: 600;
}
.topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  justify-content: flex-end;
  min-width: 0;
}
.search-bar {
  min-width: 160px;
  max-width: 420px;
  width: 100%;
}
.action-btn {
  background: #f7f8fc;
  border-color: #eef0f7;
  color: #8090a8;
}
.lang-btn {
  height: 38px;
  border-radius: 11px;
  background: #f7f8fc;
  border: 1.5px solid #eef0f7;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 10px;
  font-weight: 800;
  color: #6b7280;
  background: #f7f8fc;
  border: 1.5px solid #eef0f7;
  box-shadow: none;
}
.lang-btn:hover:not(:disabled) {
  transform: none;
  box-shadow: none;
  background: #eef2f7;
}
.lang-code {
  font-size: 12px;
  letter-spacing: 0.4px;
}
.menu-wrap {
  position: relative;
}
.notif-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 18px;
  height: 18px;
  border-radius: 999px;
  background: #e11d48;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 5px;
  border: 2px solid #fff;
}
.avatar-trigger {
  border: none;
  background: transparent;
  padding: 0;
  display: inline-flex;
}
.av-btn {
  width: 38px;
  height: 38px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: #fff;
}

.av-btn--image {
  object-fit: cover;
  background: #fff;
  border: 1px solid #eef0f7;
}
.menu-panel {
  position: absolute;
  top: calc(100% + 10px);
  inset-inline-end: 0;
  width: 300px;
  max-width: min(300px, calc(100vw - 24px));
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
  z-index: 30;
  padding: 8px;
}
.user-menu {
  width: 190px;
}
.menu-head {
  padding: 6px 8px 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  border-bottom: 1px solid #f1f5f9;
}
.menu-link {
  border: none;
  background: transparent;
  color: #4361ee;
  font-size: 12px;
  font-weight: 700;
}
.menu-items {
  display: grid;
  gap: 4px;
  margin-top: 8px;
}
.notif-item {
  width: 100%;
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  background: #fff;
  text-align: left;
  padding: 8px;
  display: grid;
  gap: 2px;
}
.notif-item--unread {
  border-left: 3px solid #4361ee;
}
.notif-item__title {
  font-size: 12.5px;
  font-weight: 700;
  color: #1e293b;
}
.notif-item__meta {
  color: #94a3b8;
}
.menu-empty {
  margin: 10px 8px;
  color: #94a3b8;
  font-size: 12px;
}
.menu-view-all {
  width: 100%;
  border: none;
  background: #f8fafc;
  color: #334155;
  border-radius: 10px;
  padding: 8px;
  font-size: 12px;
  font-weight: 700;
  margin-top: 8px;
}
.menu-item {
  width: 100%;
  border: none;
  background: #fff;
  border-radius: 10px;
  padding: 9px 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #334155;
  font-weight: 600;
}
.menu-item:hover {
  background: #f8fafc;
}
.menu-item--danger {
  color: #be123c;
}
.menu-item--danger:hover {
  background: #fff1f2;
}
.menu-pop-enter-active,
.menu-pop-leave-active {
  transition:
    opacity 180ms ease,
    transform 180ms ease;
  transform-origin: top right;
}
.menu-pop-enter-from,
.menu-pop-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.98);
}
@media (max-width: 992px) {
  .topbar {
    padding: 8px 10px;
  }
  .page-meta {
    display: none;
  }
  .topbar-actions {
    width: 100%;
    justify-content: flex-start;
  }
  .search-bar {
    min-width: 0;
    flex: 1;
    max-width: none;
  }
  .menu-panel {
    max-width: calc(100vw - 20px);
  }
  .menu-pop-enter-active,
  .menu-pop-leave-active {
    transform-origin: top left;
  }
}
</style>
