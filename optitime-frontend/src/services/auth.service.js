import { sleep } from '@/utils/sleep'

/** @typedef {import('@/types/auth').AuthError} AuthError */
/** @typedef {import('@/types/auth').AuthUser} AuthUser */
/** @typedef {import('@/types/auth').LoginCredentials} LoginCredentials */

/** @type {AuthUser} */
const DEMO_USER = Object.freeze({
  id: 'demo-user',
  name: 'Demo User',
  email: 'demo@optitime.com',
})

export const DEMO_CREDENTIALS = Object.freeze({
  email: DEMO_USER.email,
  password: 'password123',
})

function getDelay() {
  const envValue = Number(import.meta.env.VITE_AUTH_DELAY_MS)
  return Number.isFinite(envValue) && envValue >= 0 ? envValue : 1000
}

/**
 * @param {string} code
 * @param {string} message
 * @returns {AuthError}
 */
function createAuthError(code, message) {
  return { code, message }
}

export const authService = {
  /**
   * @param {LoginCredentials} credentials
   * @returns {Promise<AuthUser>}
   */
  async login(credentials) {
    await sleep(getDelay())

    const email = credentials.email.trim().toLowerCase()
    const password = credentials.password

    if (email === DEMO_CREDENTIALS.email && password === DEMO_CREDENTIALS.password) {
      return { ...DEMO_USER }
    }

    throw createAuthError('INVALID_CREDENTIALS', 'Invalid credentials')
  },
}
