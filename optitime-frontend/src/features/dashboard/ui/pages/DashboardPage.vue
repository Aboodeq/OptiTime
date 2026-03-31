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
          :page-title="t('routes.dashboard')"
          :role-color="roleColor"
          :user-initial="userInitial"
          :is-mobile="isMobile"
          @open-sidebar="openMobileSidebar"
        />

        <section class="dashboard-card">
          <span class="dashboard-card__label">{{ t('routes.dashboard') }}</span>
          <h1 class="h3 fw-bold mb-3">{{ t('pages.dashboard.title') }}</h1>
          <p class="dashboard-card__meta mb-4">{{ t('pages.dashboard.subtitle') }}</p>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <div class="p-3 rounded-4 border bg-light-subtle h-100">
                <div class="small text-secondary mb-1">{{ t('pages.dashboard.role') }}</div>
                <div class="fw-semibold">{{ roleKey ? t(`roles.${roleKey}`) : '-' }}</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 rounded-4 border bg-light-subtle h-100">
                <div class="small text-secondary mb-1">{{ t('pages.dashboard.email') }}</div>
                <div class="fw-semibold">{{ user?.email }}</div>
              </div>
            </div>
          </div>

          <p class="small text-secondary mb-3">
            {{ t('pages.dashboard.permissionCount', { count: permissions.length }) }}
          </p>

          <AppButton variant="outline" @click="handleLogout">
            {{ t('common.actions.logout') }}
          </AppButton>
        </section>
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
import AppButton from '@/components/common/AppButton.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppTopBar from '@/components/layout/AppTopBar.vue'
import { useDashboardPage } from '@/features/dashboard/model/composables/useDashboardPage'

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
