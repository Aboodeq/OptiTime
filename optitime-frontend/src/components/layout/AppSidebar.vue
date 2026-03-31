<template>
  <aside class="sidebar" :class="{ collapsed }" :style="sidebarStyle">
    <div class="logo-area">
      <div class="logo-box">
        <i class="bi bi-clock-history"></i>
      </div>
      <transition name="fade-text">
        <div v-if="!collapsed" class="logo-texts">
          <span class="logo-name">{{ t('common.appName') }}</span>
          <span class="logo-sub">{{ roleLabel }}</span>
        </div>
      </transition>
      <AppIconButton
        v-if="mobileMode && mobileOpen"
        class="mobile-close-btn"
        icon="bi bi-x-lg"
        variant="neutral"
        size="md"
        :aria-label="t('common.actions.close')"
        :title="t('common.actions.close')"
        @click="emit('close-mobile')"
      />
    </div>

    <nav class="nav-area" aria-label="Sidebar navigation">
      <div v-for="section in sections" :key="section.id" class="nav-group">
        <transition name="fade-text">
          <div v-if="!collapsed" class="nav-group-title">{{ t(section.titleKey) }}</div>
        </transition>
        <RouterLink
          v-for="item in section.items"
          :key="item.id"
          :to="{ name: item.routeName }"
          class="nav-link"
          active-class="active"
          @click="emit('navigate')"
        >
          <div class="nav-icon-wrap">
            <i :class="item.icon" aria-hidden="true"></i>
          </div>
          <transition name="fade-text">
            <span v-if="!collapsed" class="nav-label">{{ t(item.labelKey) }}</span>
          </transition>
        </RouterLink>
      </div>

      <p v-if="sections.length === 0" class="app-sidebar__empty">
        {{ t('pages.sidebar.empty') }}
      </p>
    </nav>

    <div class="sidebar-footer">
      <AppButton class="toggle-btn" variant="plain" @click="toggleCollapse">
        <i :class="toggleIcon"></i>
        <transition name="fade-text">
          <span v-if="!collapsed">{{ t('nav.sidebar.collapse') }}</span>
        </transition>
      </AppButton>

      <div class="user-row">
        <div class="user-av" :style="{ background: roleColor }">{{ userInitial }}</div>
        <transition name="fade-text">
          <div v-if="!collapsed" class="user-info">
            <div class="user-nm">{{ userName }}</div>
            <div class="user-rl">{{ roleLabel }}</div>
          </div>
        </transition>
        <transition name="fade-text">
          <AppIconButton
            v-if="!collapsed"
            class="logout-btn"
            icon="bi bi-box-arrow-right"
            variant="neutral"
            size="md"
            :title="t('common.actions.logout')"
            :aria-label="t('common.actions.logout')"
            @click="emit('logout')"
          />
        </transition>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import AppButton from '@/components/common/AppButton.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import { useUiStore } from '@/store/ui.store'

const props = defineProps({
  sections: {
    type: Array,
    default: () => [],
  },
  roleKey: {
    type: String,
    default: 'admin',
  },
  roleColor: {
    type: String,
    default: '#334155',
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
  mobileMode: {
    type: Boolean,
    default: false,
  },
  mobileOpen: {
    type: Boolean,
    default: false,
  },
  userName: {
    type: String,
    default: '',
  },
  userInitial: {
    type: String,
    default: '?',
  },
})

const emit = defineEmits(['toggle-collapse', 'logout', 'navigate', 'close-mobile'])
const { t } = useI18n()
const uiStore = useUiStore()

const sidebarStyle = computed(() => ({
  '--role-color': props.roleColor,
}))

const roleLabel = computed(() =>
  props.roleKey ? t(`roles.${props.roleKey}`) : t('pages.dashboard.role'),
)

const collapsed = computed(() => (props.mobileMode ? false : props.collapsed))

const toggleIcon = computed(() => {
  if (uiStore.isRtl) return collapsed.value ? 'bi bi-chevron-left' : 'bi bi-chevron-right'
  return collapsed.value ? 'bi bi-chevron-right' : 'bi bi-chevron-left'
})

function toggleCollapse() {
  emit('toggle-collapse')
}
</script>

<style scoped>
.sidebar {
  width: var(--sidebar-width);
  min-width: var(--sidebar-width);
  height: calc(100vh - 2.5rem);
  max-height: calc(100vh - 2.5rem);
  background: var(--color-surface);
  display: flex;
  flex-direction: column;
  border-inline-end: 1px solid #eef0f7;
  box-shadow: 0 0 24px rgba(67, 97, 238, 0.06);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  border-radius: 16px;
  --fade-shift: 6px;
}
.sidebar.collapsed {
  width: var(--sidebar-collapsed);
  min-width: var(--sidebar-collapsed);
}
.sidebar.collapsed .nav-link {
  justify-content: center;
  padding-inline: 0;
}
.sidebar.collapsed .nav-icon-wrap {
  margin-inline: auto;
}

.logo-area {
  padding: 20px 18px;
  display: flex;
  align-items: center;
  gap: 13px;
  border-bottom: 1px solid #f3f4f9;
  min-height: 70px;
}
.mobile-close-btn {
  margin-inline-start: auto;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid #eef0f7;
  background: #fff;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.logo-box {
  width: 42px;
  height: 42px;
  min-width: 42px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--role-color), #7209b7);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: #fff;
  box-shadow: 0 4px 14px rgba(67, 97, 238, 0.35);
  flex-shrink: 0;
}
.logo-texts {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  white-space: nowrap;
}
.logo-name {
  font-size: 17px;
  font-weight: 800;
  color: #1e2a3a;
  letter-spacing: -0.3px;
  line-height: 1;
}
.logo-sub {
  font-size: 10.5px;
  color: #a0a8b8;
  margin-top: 3px;
  font-weight: 500;
}

.nav-area {
  flex: 1;
  padding: 18px 12px;
  overflow-y: auto;
}
.nav-area::-webkit-scrollbar {
  width: 0;
}
.nav-group {
  margin-bottom: 8px;
}
.nav-group-title {
  font-size: 9.5px;
  font-weight: 700;
  color: #c0c8d8;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  padding: 6px 10px 4px;
  white-space: nowrap;
}
.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 10px;
  border-radius: 12px;
  color: #6b7280;
  text-decoration: none;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  transition:
    background 0.2s,
    color 0.2s;
  text-align: start;
}
.nav-link:hover {
  background: rgba(67, 97, 238, 0.06);
  color: #4361ee;
}
.nav-link.active {
  background: rgba(67, 97, 238, 0.11);
  color: #4361ee;
}
.nav-icon-wrap {
  width: 36px;
  height: 36px;
  min-width: 36px;
  border-radius: 11px;
  background: #f7f8fc;
  border: 1px solid #eef0f7;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #8b95a7;
  font-size: 16px;
}
.nav-link.active .nav-icon-wrap,
.nav-link:hover .nav-icon-wrap {
  background: rgba(67, 97, 238, 0.13);
  border-color: rgba(67, 97, 238, 0.3);
  color: #4361ee;
}
.nav-label {
  white-space: nowrap;
}
.app-sidebar__empty {
  margin: 8px 10px 0;
  font-size: 12px;
  color: #9ca3af;
}

.sidebar-footer {
  padding: 14px 12px 12px;
  border-top: 1px solid #f3f4f9;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.toggle-btn {
  width: 100%;
  border: 1.5px solid #eef0f7;
  border-color: #eef0f7;
  border-radius: 12px;
  padding: 10px;
  background: #f7f8fc;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  color: #6b7280;
  box-shadow: none;
  justify-content: flex-start;
}
.toggle-btn:hover:not(:disabled) {
  transform: none;
  box-shadow: none;
  background: #eef2f7;
}
.sidebar.collapsed .toggle-btn {
  justify-content: center;
  padding-inline: 0;
}
.toggle-btn i {
  font-size: 16px;
  width: 36px;
  height: 36px;
  min-width: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 1px solid #eef0f7;
  border-radius: 11px;
}
.user-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid #eef0f7;
}
.sidebar.collapsed .user-row {
  justify-content: center;
  padding-inline: 0;
}
.user-av {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 800;
  font-size: 14px;
}
.user-info {
  overflow: hidden;
  text-align: start;
}
.user-nm {
  font-weight: 800;
  font-size: 12.5px;
  color: #1e2a3a;
}
.user-rl {
  font-size: 10.5px;
  color: #9ca3af;
}
.logout-btn {
  margin-inline-start: auto;
  background: #f7f8fc;
  color: #9ca3af;
  flex-shrink: 0;
  border-color: #eef0f7;
}
.logout-btn:hover {
  color: #e63946;
  background: #fff1f2;
}
.fade-text-enter-active {
  transition:
    opacity 0.2s,
    transform 0.2s;
}
.fade-text-leave-active {
  transition: opacity 0.1s;
}
.fade-text-enter-from {
  opacity: 0;
  transform: translateX(var(--fade-shift));
}
.fade-text-leave-to {
  opacity: 0;
}

:global(html[dir='rtl']) .sidebar {
  direction: rtl;
  --fade-shift: -6px;
}

:global(html[dir='ltr']) .sidebar {
  direction: ltr;
  --fade-shift: 6px;
}

@media (max-width: 992px) {
  .toggle-btn {
    display: none;
  }
}
</style>
