/**
 * Laravel API base URL including `/api` prefix (see RouteServiceProvider).
 */
export function getApiBaseUrl() {
  const raw = import.meta.env.VITE_API_BASE_URL
  if (typeof raw === 'string' && raw.trim()) {
    return raw.replace(/\/+$/, '')
  }
  return 'http://localhost:8000/api'
}
