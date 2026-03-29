<template>
  <main class="dashboard-shell">
    <section class="dashboard-card">
      <span class="dashboard-card__label">{{ t('routes.dashboard') }}</span>
      <h1 class="h3 fw-bold mb-3">{{ t('pages.dashboard.title') }}</h1>
      <p class="dashboard-card__meta mb-4">{{ t('pages.dashboard.subtitle') }}</p>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="p-3 rounded-4 border bg-light-subtle h-100">
            <div class="small text-secondary mb-1">{{ t('pages.dashboard.role') }}</div>
            <div class="fw-semibold">{{ t(`roles.${user?.role}`) }}</div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="p-3 rounded-4 border bg-light-subtle h-100">
            <div class="small text-secondary mb-1">{{ t('pages.dashboard.email') }}</div>
            <div class="fw-semibold">{{ user?.email }}</div>
          </div>
        </div>
      </div>

      <AppButton variant="outline" @click="handleLogout">
        {{ t('common.actions.logout') }}
      </AppButton>
    </section>
  </main>
</template>

<script setup>
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppButton from '@/components/common/AppButton.vue'
import { useAuthStore } from '@/store/auth.store'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()
const { user } = storeToRefs(authStore)

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>
