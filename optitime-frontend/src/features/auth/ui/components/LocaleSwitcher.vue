<template>
  <div class="locale-switcher">
    <span class="locale-switcher__label">{{ t('common.language') }}</span>
    <div class="locale-switcher__buttons">
      <AppButton
        class="locale-switcher__button"
        :class="{ active: uiStore.locale === 'ar' }"
        variant="plain"
        type="button"
        @click="uiStore.setLocale('ar')"
      >
        {{ t('common.arabic') }}
      </AppButton>
      <AppButton
        class="locale-switcher__button"
        :class="{ active: uiStore.locale === 'en' }"
        variant="plain"
        type="button"
        @click="uiStore.setLocale('en')"
      >
        {{ t('common.english') }}
      </AppButton>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import { useUiStore } from '@/store/ui.store'

const uiStore = useUiStore()
const { t } = useI18n()
</script>

<style scoped>
.locale-switcher {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.locale-switcher__label {
  font-size: 0.82rem;
  font-weight: 700;
  color: #64748b;
}

.locale-switcher__buttons {
  display: inline-flex;
  gap: 4px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 4px;
}

.locale-switcher__button {
  min-width: 76px;
  padding: 0.35rem 0.55rem;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
}

.locale-switcher__button:hover:not(:disabled) {
  background: #eef2f7;
}

.locale-switcher__button.active {
  background: #fff;
  color: #1e2a3a;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1);
}
</style>
