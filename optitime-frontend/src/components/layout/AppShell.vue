<template>
  <main class="dashboard-shell dashboard-shell--with-sidebar">
    <div class="dashboard-layout" :class="{ collapsed: isCollapsed, 'is-mobile': isMobile }">
      <div class="dashboard-layout__sidebar" :class="{ 'mobile-open': isMobileSidebarOpen }">
        <AppSidebar
          :sections="visibleSections"
          :role-key="roleKey"
          :role-color="roleColor"
          :collapsed="isCollapsed"
          :mobile-mode="isMobile"
          :mobile-open="isMobileSidebarOpen"
          :user-name="user?.name"
          :user-initial="userInitial"
          @toggle-collapse="toggleSidebarCollapse"
          @logout="handleLogout"
          @navigate="handleSidebarNavigate"
          @close-mobile="closeMobileSidebar"
        />
      </div>

      <div class="dashboard-layout__content">
        <AppTopBar
          :page-title="pageTitle"
          :role-color="roleColor"
          :user-initial="userInitial"
          :is-mobile="isMobile"
          @open-sidebar="openMobileSidebar"
        />

        <slot
          :user="user"
          :role-key="roleKey"
          :permissions="permissions"
          :handle-logout="handleLogout"
        />
      </div>
    </div>

    <button
      v-if="isMobileSidebarOpen"
      type="button"
      class="mobile-sidebar-overlay"
      :aria-label="t('common.actions.close')"
      @click="closeMobileSidebar"
    ></button>
  </main>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppTopBar from '@/components/layout/AppTopBar.vue'
import { useDashboardPage } from '@/features/dashboard/model/composables/useDashboardPage'

defineProps({
  pageTitle: {
    type: String,
    required: true,
  },
})

const { t } = useI18n()
const {
  user,
  permissions,
  roleColor,
  roleKey,
  visibleSections,
  userInitial,
  isCollapsed,
  isMobile,
  isMobileSidebarOpen,
  toggleSidebarCollapse,
  openMobileSidebar,
  closeMobileSidebar,
  handleSidebarNavigate,
  handleLogout,
} = useDashboardPage()
</script>
