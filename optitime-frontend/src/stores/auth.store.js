import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { authService } from '@/services/auth.service'

/** @typedef {import('@/types/auth').AuthUser} AuthUser */
/** @typedef {import('@/types/auth').LoginCredentials} LoginCredentials */

const AUTH_STORAGE_KEY = 'optitime.auth'

export const useAuthStore = defineStore('auth', () => {
  const storage = useLocalStorage(AUTH_STORAGE_KEY)
  /** @type {import('vue').Ref<AuthUser | null>} */
  const user = ref(null)
  const isAuthenticated = computed(() => Boolean(user.value))

  function hydrate() {
    user.value = storage.read(null)
  }

  /**
   * @param {LoginCredentials} credentials
   * @returns {Promise<AuthUser>}
   */
  async function login(credentials) {
    const nextUser = await authService.login(credentials)
    user.value = nextUser
    storage.write(nextUser)
    return nextUser
  }

  function logout() {
    user.value = null
    storage.remove()
  }

  return {
    user,
    isAuthenticated,
    login,
    logout,
    hydrate,
  }
})
