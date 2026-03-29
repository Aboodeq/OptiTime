import { sleep } from '@/utils/sleep'

export const DEMO_CREDENTIALS = Object.freeze({
  email: 'demo@optitime.com',
  password: 'password123',
})

const DEMO_USERS = Object.freeze({
  admin: {
    id: 'demo-admin',
    name: 'Demo Admin',
    email: DEMO_CREDENTIALS.email,
    role: 'admin',
  },
  coordinator: {
    id: 'demo-coordinator',
    name: 'Demo Coordinator',
    email: DEMO_CREDENTIALS.email,
    role: 'coordinator',
  },
  instructor: {
    id: 'demo-instructor',
    name: 'Demo Instructor',
    email: DEMO_CREDENTIALS.email,
    role: 'instructor',
  },
  student: {
    id: 'demo-student',
    name: 'Demo Student',
    email: DEMO_CREDENTIALS.email,
    role: 'student',
  },
  management: {
    id: 'demo-management',
    name: 'Demo Management',
    email: DEMO_CREDENTIALS.email,
    role: 'management',
  },
  exams: {
    id: 'demo-exams',
    name: 'Demo Exams',
    email: DEMO_CREDENTIALS.email,
    role: 'exams',
  },
})

function getDelay() {
  const envValue = Number(import.meta.env.VITE_AUTH_DELAY_MS)
  return Number.isFinite(envValue) && envValue >= 0 ? envValue : 1000
}

function createAuthError(code, message) {
  return { code, message }
}

export const authService = {
  async login(credentials) {
    await sleep(getDelay())

    const email = credentials.email.trim().toLowerCase()
    const password = credentials.password

    if (email === DEMO_CREDENTIALS.email && password === DEMO_CREDENTIALS.password) {
      return { ...DEMO_USERS.admin, name: 'Demo User' }
    }

    throw createAuthError('INVALID_CREDENTIALS', 'Invalid credentials')
  },

  async loginAsDemoRole(role) {
    await sleep(450)

    const user = DEMO_USERS[role]

    if (!user) {
      throw createAuthError('INVALID_ROLE', 'Invalid credentials')
    }

    return { ...user }
  },
}
