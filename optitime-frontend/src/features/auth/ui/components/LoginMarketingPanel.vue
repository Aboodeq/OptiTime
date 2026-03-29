<template>
  <div class="login-left d-none d-lg-flex">
    <div class="left-content">
      <div class="brand-logo mb-5">
        <div class="logo-icon">
          <i class="bi bi-clock-history"></i>
        </div>
        <span class="brand-name">{{ t('common.appName') }}</span>
      </div>

      <h1 class="left-title">{{ t('pages.login.leftTitle') }}</h1>
      <p class="left-subtitle">{{ t('pages.login.leftSubtitle') }}</p>

      <div class="feature-list mt-5">
        <div v-for="feature in features" :key="feature.key" class="feature-item">
          <div class="feature-icon">
            <i :class="feature.icon"></i>
          </div>
          <div>
            <div class="feature-title">{{ feature.title }}</div>
            <div class="feature-desc">{{ feature.desc }}</div>
          </div>
        </div>
      </div>

      <div class="stats-row mt-5">
        <div v-for="stat in stats" :key="stat.key" class="stat-box">
          <div class="stat-value">{{ stat.value }}</div>
          <div class="stat-label">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <div class="deco-circle deco-1"></div>
    <div class="deco-circle deco-2"></div>
    <div class="deco-circle deco-3"></div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useLoginPageContent } from '@/features/auth/model/composables/useLoginPageContent'

const { t } = useI18n()
const { features, stats } = useLoginPageContent()
</script>
