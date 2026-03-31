<template>
  <div class="locale-switcher">
    <span class="locale-switcher__label">{{ t('common.language') }}</span>
    <div class="locale-switcher__buttons">
      <button
        class="locale-switcher__button"
        :class="{ active: uiStore.locale === 'ar' }"
        type="button"
        @click="uiStore.setLocale('ar')"
      >
        {{ t('common.arabic') }}
      </button>
      <button
        class="locale-switcher__button"
        :class="{ active: uiStore.locale === 'en' }"
        type="button"
        @click="uiStore.setLocale('en')"
      >
        {{ t('common.english') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useUiStore } from '@/store/ui.store'

const uiStore = useUiStore()
const { t } = useI18n()
</script>
