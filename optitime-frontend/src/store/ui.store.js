import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { LOCALE_STORAGE_KEY } from '@/constants/storageKeys'

function normalizeLocale(locale) {
  return locale === 'en' ? 'en' : 'ar'
}

function loadSavedLocale() {
  if (typeof window === 'undefined') return 'ar'

  try {
    return normalizeLocale(window.localStorage.getItem(LOCALE_STORAGE_KEY))
  } catch {
    return 'ar'
  }
}

export const useUiStore = defineStore('ui', () => {
  const locale = ref(loadSavedLocale())
  const dir = computed(() => (locale.value === 'ar' ? 'rtl' : 'ltr'))
  const isRtl = computed(() => dir.value === 'rtl')

  function setLocale(nextLocale) {
    const normalized = normalizeLocale(nextLocale)
    locale.value = normalized

    if (typeof window === 'undefined') return

    try {
      window.localStorage.setItem(LOCALE_STORAGE_KEY, normalized)
    } catch {
      // ignore storage errors
    }
  }

  return { locale, dir, isRtl, setLocale }
})
