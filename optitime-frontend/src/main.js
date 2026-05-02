import { createApp, watch } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import router from '@/app/router'
import { setupDocumentTitle } from '@/app/plugins/documentTitle'
import { setupLocaleEffects } from '@/app/plugins/localeEffects'
import i18n from '@/i18n'
import { useAuthStore } from '@/store/auth.store'
import {
  setupNotificationsRealtime,
  teardownNotificationsRealtime,
} from '@/features/notifications/model/composables/useNotificationsRealtime'
import { useUiStore } from '@/store/ui.store'
import App from './App.vue'
import 'vue-toastification/dist/index.css'
import '@/assets/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(i18n)
app.use(Toast, {
  position: 'top-right',
  timeout: 3000,
  newestOnTop: true,
  hideProgressBar: false,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
})

const uiStore = useUiStore(pinia)
setupLocaleEffects({ i18n, uiStore })
setupDocumentTitle({ router, i18n })

;(async () => {
  const authStore = useAuthStore()
  await authStore.restoreSession()
  watch(
    () => authStore.isAuthenticated,
    async (isAuthenticated) => {
      if (isAuthenticated) await setupNotificationsRealtime()
      else await teardownNotificationsRealtime()
    },
    { immediate: true },
  )
  app.mount('#app')
})()
