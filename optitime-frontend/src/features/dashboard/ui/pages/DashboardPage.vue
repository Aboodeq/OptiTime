<template>
  <AppShell :page-title="t('routes.dashboard')">
    <template #default="{ user, roleKey, permissions, handleLogout }">
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
    </template>
  </AppShell>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppShell from '@/components/layout/AppShell.vue'

const { t } = useI18n()
</script>
