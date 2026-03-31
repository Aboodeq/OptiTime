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

function normalizePermissions(permissions) {
  if (!Array.isArray(permissions)) return []
  const mapped = permissions
    .filter((permission) => typeof permission === 'string')
    .map((permission) => permission.replace(/\s+/g, ''))
    .flatMap((permission) => {
      if (permission === 'roles.manage') {
        return ['roles.create', 'roles.update', 'roles.delete', 'roles.view']
      }
      if (permission === 'organization.manage') {
        return [
          'organization.create',
          'organization.update',
          'organization.delete',
          'organization.view',
        ]
      }
      if (permission === 'roled.delete') {
        return ['roles.delete']
      }
      return [permission]
    })

  return [...new Set(mapped)]
}

function normalizeUser(userData) {
  if (!userData || typeof userData !== 'object') return null

  const rawRole = userData.role
  const role =
    rawRole && typeof rawRole === 'object'
      ? {
          key: rawRole.key ?? 'unknown',
          name: rawRole.name ?? rawRole.key ?? 'Unknown',
          color: rawRole.color ?? '#334155',
        }
      : {
          key: typeof rawRole === 'string' ? rawRole : 'unknown',
          name: typeof rawRole === 'string' ? rawRole : 'Unknown',
          color: '#334155',
        }

  return {
    ...userData,
    role,
    permissions: normalizePermissions(userData.permissions),
  }
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref(normalizeUser(loadSavedUser()))
  const isAuthenticated = computed(() => Boolean(user.value))
  const role = computed(() => user.value?.role ?? null)
  const roleKey = computed(() => role.value?.key ?? null)
  const roleColor = computed(() => role.value?.color ?? '#334155')
  const permissions = computed(() => user.value?.permissions ?? [])

  function setUser(userData) {
    const normalizedUser = normalizeUser(userData)
    user.value = normalizedUser
    saveUser(normalizedUser)
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
    role,
    roleKey,
    roleColor,
    permissions,
    setUser,
    hasPermission,
    hasAnyPermission,
    login,
    loginAsDemoRole,
    logout,
  }
})
