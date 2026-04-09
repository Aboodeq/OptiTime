import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '@/features/auth/api/auth.service'
import { loadAuthSession, saveAuthSession } from '@/lib/authSession'

function normalizePermissions(permissions) {
  if (!Array.isArray(permissions)) return []
  return [
    ...new Set(
      permissions
        .filter((permission) => typeof permission === 'string')
        .map((permission) => permission.replace(/\s+/g, '')),
    ),
  ]
}

function normalizeUser(userData) {
  if (!userData || typeof userData !== 'object') return null

  const rawRole = userData.role
  const role =
    rawRole && typeof rawRole === 'object'
      ? {
          key: rawRole.key ?? rawRole.code ?? 'unknown',
          name: rawRole.name ?? rawRole.name_en ?? rawRole.key ?? 'Unknown',
          color: rawRole.color ?? rawRole.sidebar_color ?? '#334155',
        }
      : {
          key: typeof rawRole === 'string' ? rawRole : 'unknown',
          name: typeof rawRole === 'string' ? rawRole : 'Unknown',
          color: '#334155',
        }

  const normalizedPermissions = normalizePermissions(userData.permissions)

  return {
    ...userData,
    role,
    permissions: normalizedPermissions,
  }
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(normalizeUser(loadAuthSession().user))
  const isAuthenticated = computed(() => Boolean(user.value))
  const role = computed(() => user.value?.role ?? null)
  const roleKey = computed(() => role.value?.key ?? null)
  const roleColor = computed(() => role.value?.color ?? '#334155')
  const permissions = computed(() => user.value?.permissions ?? [])
  const passwordRecovery = ref({
    email: '',
    code: '',
    isCodeVerified: false,
    codeSentAt: 0,
  })

  function setUser(userData) {
    if (!userData) {
      user.value = null
      saveAuthSession(null, null)
      return
    }

    const normalizedUser = normalizeUser(userData)
    user.value = normalizedUser
    const { token } = loadAuthSession()
    saveAuthSession(token, normalizedUser)
  }

  function patchCurrentUser(partial) {
    if (!user.value) return
    setUser({ ...user.value, ...(partial && typeof partial === 'object' ? partial : {}) })
  }

  function hasPermission(permission) {
    if (!permission) return true
    return permissions.value.includes(permission)
  }

  function hasAnyPermission(permissionList) {
    if (!Array.isArray(permissionList) || permissionList.length === 0) return true
    return permissionList.some((permission) => hasPermission(permission))
  }

  async function login(credentials) {
    const { token, user: loggedInUser } = await authService.login(credentials)
    const normalizedUser = normalizeUser(loggedInUser)
    user.value = normalizedUser
    saveAuthSession(token, normalizedUser)
    return normalizedUser
  }

  async function loginAsDemoRole(roleKey) {
    const { token, user: loggedInUser } = await authService.loginAsDemoRole(roleKey)
    const normalizedUser = normalizeUser(loggedInUser)
    user.value = normalizedUser
    saveAuthSession(token, normalizedUser)
    return normalizedUser
  }

  /**
   * Refresh user from GET /api/user when a token exists (e.g. after reload).
   */
  async function restoreSession() {
    const { token, user: stored } = loadAuthSession()
    if (!token || !stored) return

    if (authService.isDemoMode() && String(token).startsWith('demo-token')) {
      user.value = normalizeUser(stored)
      return
    }

    try {
      const fresh = await authService.fetchCurrentUser()
      if (fresh) {
        const normalizedUser = normalizeUser(fresh)
        user.value = normalizedUser
        saveAuthSession(token, normalizedUser)
      }
    } catch {
      user.value = null
      saveAuthSession(null, null)
    }
  }

  function clearPasswordRecovery() {
    passwordRecovery.value = {
      email: '',
      code: '',
      isCodeVerified: false,
      codeSentAt: 0,
    }
  }

  async function requestPasswordReset(email) {
    const normalizedEmail = String(email ?? '')
      .trim()
      .toLowerCase()

    await authService.requestPasswordReset(normalizedEmail)
    passwordRecovery.value = {
      email: normalizedEmail,
      code: '',
      isCodeVerified: false,
      codeSentAt: Date.now(),
    }
    return true
  }

  async function resendPasswordResetCode() {
    const email = passwordRecovery.value.email
    if (!email) {
      throw { code: 'MISSING_EMAIL' }
    }
    await authService.requestPasswordReset(email)
    passwordRecovery.value = {
      ...passwordRecovery.value,
      code: '',
      codeSentAt: Date.now(),
      isCodeVerified: false,
    }
    return true
  }

  async function verifyPasswordResetCode(code) {
    const email = passwordRecovery.value.email
    if (!email) {
      throw { code: 'MISSING_EMAIL' }
    }
    await authService.verifyPasswordResetCode({ email, code })
    passwordRecovery.value = {
      ...passwordRecovery.value,
      code: String(code ?? '').trim(),
      isCodeVerified: true,
    }
    return true
  }

  async function resetPassword(password) {
    const { email, code, isCodeVerified } = passwordRecovery.value
    if (!email || !code || !isCodeVerified) {
      throw { code: 'RESET_NOT_ALLOWED' }
    }

    await authService.resetPassword({ email, code, password })
    clearPasswordRecovery()
    return true
  }

  async function logout() {
    await authService.logout()
    user.value = null
    saveAuthSession(null, null)
  }

  return {
    user,
    isAuthenticated,
    role,
    roleKey,
    roleColor,
    permissions,
    setUser,
    patchCurrentUser,
    hasPermission,
    hasAnyPermission,
    login,
    loginAsDemoRole,
    restoreSession,
    passwordRecovery,
    clearPasswordRecovery,
    requestPasswordReset,
    resendPasswordResetCode,
    verifyPasswordResetCode,
    resetPassword,
    logout,
  }
})
