import { watch } from 'vue'

export function setupDocumentTitle({ router, i18n }) {
  function applyDocumentTitle() {
    const appName = i18n.global.t('common.appName')
    const titleKey = router.currentRoute.value?.meta?.titleKey
    if (!titleKey) {
      document.title = appName
      return
    }

    document.title = `${i18n.global.t(titleKey)} | ${appName}`
  }

  watch(
    [() => router.currentRoute.value.fullPath, () => i18n.global.locale.value],
    applyDocumentTitle,
    { immediate: true },
  )
}
