import { createI18n } from 'vue-i18n'
import { LOCALE_STORAGE_KEY } from '@/constants/storageKeys'

function isPlainObject(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
}

function deepMerge(target, source) {
  if (!isPlainObject(source)) return target

  for (const [key, value] of Object.entries(source)) {
    if (isPlainObject(value)) {
      if (!isPlainObject(target[key])) target[key] = {}
      deepMerge(target[key], value)
      continue
    }

    target[key] = value
  }

  return target
}

function mergeLocaleModules(modules) {
  const merged = {}

  for (const path of Object.keys(modules).sort()) {
    const mod = modules[path]
    const payload = mod?.default ?? mod
    deepMerge(merged, payload)
  }

  return merged
}

function getInitialLocale() {
  if (typeof window === 'undefined') return 'ar'

  try {
    const saved = window.localStorage.getItem(LOCALE_STORAGE_KEY)
    return saved === 'en' ? 'en' : 'ar'
  } catch {
    return 'ar'
  }
}

const arModules = import.meta.glob('./locales/ar/**/*.json', { eager: true })
const enModules = import.meta.glob('./locales/en/**/*.json', { eager: true })

const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale: getInitialLocale(),
  fallbackLocale: 'ar',
  messages: {
    ar: mergeLocaleModules(arModules),
    en: mergeLocaleModules(enModules),
  },
})

export default i18n
