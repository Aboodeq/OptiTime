import { AUTH_STORAGE_KEY } from '@/constants/storageKeys'

/**
 * Persisted auth payload: Sanctum token + normalized user for the SPA.
 * Legacy: localStorage held only the user object (no token) — treat as logged out for API calls.
 */
export function loadAuthSession() {
  if (typeof window === 'undefined') {
    return { token: null, user: null }
  }

  try {
    const raw = window.localStorage.getItem(AUTH_STORAGE_KEY)
    if (!raw) return { token: null, user: null }

    const parsed = JSON.parse(raw)
    if (parsed && typeof parsed === 'object' && 'user' in parsed) {
      return {
        token: typeof parsed.token === 'string' ? parsed.token : null,
        user: parsed.user && typeof parsed.user === 'object' ? parsed.user : null,
      }
    }

    // Legacy: entire blob was the user object
    if (parsed && typeof parsed === 'object' && (parsed.email || parsed.role)) {
      return { token: null, user: parsed }
    }
  } catch {
    // ignore
  }

  return { token: null, user: null }
}

export function saveAuthSession(token, user) {
  if (typeof window === 'undefined') return

  try {
    if (!user && !token) {
      window.localStorage.removeItem(AUTH_STORAGE_KEY)
      return
    }

    window.localStorage.setItem(
      AUTH_STORAGE_KEY,
      JSON.stringify({
        token: token ?? null,
        user: user ?? null,
      }),
    )
  } catch {
    // ignore storage errors
  }
}

export function getStoredToken() {
  return loadAuthSession().token
}
