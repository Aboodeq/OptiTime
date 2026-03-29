import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Toast from 'vue-toastification'
import App from './App.vue'
import router from '@/router'
import { useAuthStore } from '@/stores/auth.store'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'vue-toastification/dist/index.css'
import '@/assets/styles/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

const authStore = useAuthStore(pinia)
authStore.hydrate()

app.use(router)
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

app.mount('#app')
