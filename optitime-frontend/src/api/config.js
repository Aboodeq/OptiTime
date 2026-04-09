/**
 * Laravel API base URL including `/api` prefix (see RouteServiceProvider).
 * If the env URL is only scheme + host (no path), `/api` is appended so requests
 * hit `.../api/admin/...` instead of `.../admin/...` (which returns 404).
 */
export function getApiBaseUrl() {
  const fallback = 'http://localhost:8000/api'
  const raw = import.meta.env.VITE_API_BASE_URL
  let base =
    typeof raw === 'string' && raw.trim() ? raw.trim().replace(/\/+$/, '') : fallback

  try {
    const u = new URL(base)
    if (u.pathname === '/' || u.pathname === '') {
      base = `${u.origin}/api`
    }
  } catch {
    return fallback
  }

  return base
}
