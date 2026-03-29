import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { AUTH_STORAGE_KEY } from '@/constants/storageKeys'
import { authService } from '@/features/auth/api/auth.service'

function loadSavedUser() {
  if (typeof window === 'undefined') return null

  try {
    const raw = window.localStorage.getItem(AUTH_STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

function saveUser(user) {
  if (typeof window === 'undefined') return

  try {
    if (!user) {
      window.localStorage.removeItem(AUTH_STORAGE_KEY)
      return
    }

    window.localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(user))
  } catch {
    // ignore storage errors
  }
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(loadSavedUser())
  const isAuthenticated = computed(() => Boolean(user.value))

  function setUser(userData) {
    user.value = userData
    saveUser(userData)
  }

  async function login(credentials) {
    const loggedInUser = await authService.login(credentials)
    setUser(loggedInUser)
    return loggedInUser
  }

  async function loginAsDemoRole(role) {
    const loggedInUser = await authService.loginAsDemoRole(role)
    setUser(loggedInUser)
    return loggedInUser
  }

  function logout() {
    setUser(null)
  }

  return {
    user,
    isAuthenticated,
    setUser,
    login,
    loginAsDemoRole,
    logout,
  }
})
