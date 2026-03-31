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
    role: {
      key: 'admin',
      name: 'System admin',
      color: '#e63946',
    },
    permissions: ['dashboard.view', 'sidebar.view'],
  },
  coordinator: {
    id: 'demo-coordinator',
    name: 'Demo Coordinator',
    email: DEMO_CREDENTIALS.email,
    role: {
      key: 'coordinator',
      name: 'Resource coordinator',
      color: '#4361ee',
    },
    permissions: ['dashboard.view', 'sidebar.view'],
  },
  instructor: {
    id: 'demo-instructor',
    name: 'Demo Instructor',
    email: DEMO_CREDENTIALS.email,
    role: {
      key: 'instructor',
      name: 'Instructor',
      color: '#2dc653',
    },
    permissions: ['dashboard.view', 'sidebar.view'],
  },
  student: {
    id: 'demo-student',
    name: 'Demo Student',
    email: DEMO_CREDENTIALS.email,
    role: {
      key: 'student',
      name: 'Student',
      color: '#f8961e',
    },
    permissions: ['dashboard.view', 'sidebar.view'],
  },
  management: {
    id: 'demo-management',
    name: 'Demo Management',
    email: DEMO_CREDENTIALS.email,
    role: {
      key: 'management',
      name: 'Management',
      color: '#0f766e',
    },
    permissions: ['dashboard.view', 'sidebar.view'],
  },
  exams: {
    id: 'demo-exams',
    name: 'Demo Exams',
    email: DEMO_CREDENTIALS.email,
    role: {
      key: 'exams',
      name: 'Exams office',
      color: '#dc2626',
    },
    permissions: ['dashboard.view', 'sidebar.view'],
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
