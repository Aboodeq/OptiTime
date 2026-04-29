import { getApiBaseUrl } from '@/api/config'
import { getStoredToken } from '@/lib/authSession'
import { useLoadingStore } from '@/store/loading.store'

/**
 * @param {string} path - e.g. `/auth/login` or `auth/login`
 * @param {RequestInit & { json?: unknown }} options
 * @returns {Promise<Response>}
 */
export async function apiFetch(path, options = {}) {
  const loadingStore = useLoadingStore()
  loadingStore.beginRequest()
  const base = getApiBaseUrl().replace(/\/+$/, '')
  let normalizedPath = path.startsWith('/') ? path : `/${path}`
  // Base already ends with /api; strip a duplicate /api prefix from the path (avoids /api/api/... → 404).
  if (/^\/api(\/|$)/.test(normalizedPath) && base.toLowerCase().endsWith('/api')) {
    normalizedPath = normalizedPath.replace(/^\/api(?=\/|$)/, '') || '/'
  }
  const url = `${base}${normalizedPath}`

  const headers = new Headers(options.headers ?? {})

  if (!headers.has('Accept')) {
    headers.set('Accept', 'application/json')
  }

  const { json: jsonBody, ...rest } = options
  let body = rest.body
  if (jsonBody !== undefined) {
    body = JSON.stringify(jsonBody)
    if (!headers.has('Content-Type')) {
      headers.set('Content-Type', 'application/json')
    }
  }

  const token = options.token ?? getStoredToken()
  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  try {
    return await fetch(url, {
      ...rest,
      headers,
      body,
    })
  } finally {
    loadingStore.endRequest()
  }
}

/**
 * @param {string} path
 * @param {RequestInit & { json?: unknown }} options
 * @returns {Promise<{ data: unknown, response: Response }>}
 */
export async function apiJson(path, options = {}) {
  const response = await apiFetch(path, options)

  const text = await response.text()
  let data = null
  if (text) {
    try {
      data = JSON.parse(text)
    } catch {
      data = { message: text }
    }
  }

  if (!response.ok) {
    const err = new Error(
      (data && typeof data === 'object' && data.message) || response.statusText || 'Request failed',
    )
    err.status = response.status
    err.data = data
    throw err
  }

  return { data, response }
}
