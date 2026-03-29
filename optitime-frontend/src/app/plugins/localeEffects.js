import { watch } from 'vue'
import { applyBootstrapCss } from '@/utils/bootstrapCss'

function applyDocumentLocale(locale) {
  const lang = locale === 'en' ? 'en' : 'ar'
  const dir = lang === 'ar' ? 'rtl' : 'ltr'
  document.documentElement.lang = lang
  document.documentElement.dir = dir
}

export function setupLocaleEffects({ i18n, uiStore }) {
  function applyLocale(locale) {
    i18n.global.locale.value = locale
    applyDocumentLocale(locale)
    applyBootstrapCss(locale)
  }

  applyLocale(uiStore.locale)
  watch(
    () => uiStore.locale,
    (locale) => applyLocale(locale),
  )
}
