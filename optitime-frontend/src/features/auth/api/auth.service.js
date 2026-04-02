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
    permissions: [
      'dashboard.view',
      'sidebar.view',
      'roles.create',
      'roles.update',
      'roles.delete',
      'roles.view',
      'organization.create',
      'organization.update',
      'organization.delete',
      'organization.view',
      'users.create',
      'users.update',
      'users.delete',
      'users.view',
      'instructors.create',
      'instructors.update',
      'instructors.delete',
      'instructors.view',
      'students.create',
      'students.update',
      'students.delete',
      'students.view',
      'specialities.create',
      'specialities.update',
      'specialities.delete',
      'specialities.view',
      'semesters.create',
      'semesters.update',
      'semesters.delete',
      'semesters.view',
      'constraints.manage',
      'auditLogs.view',
      'settings.manage',
      'notifications.view',
    ],
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
    permissions: [
      'dashboard.view',
      'sidebar.view',
      'notifications.view',
      'settings.view',
      'settings.profile.update',
      'settings.security.update',
      'settings.notifications.update',
      'schedule.view',
      'schedule.update',
      'schedule.generate',
      'instructorSchedule.requests.review',
      'resources.create',
      'resources.update',
      'resources.delete',
      'resources.view',
      'rooms.create',
      'rooms.update',
      'rooms.delete',
      'rooms.view',
      'courses.create',
      'courses.update',
      'courses.delete',
      'courses.view',
    ],
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
    permissions: [
      'dashboard.view',
      'sidebar.view',
      'notifications.view',
      'settings.view',
      'settings.profile.update',
      'settings.security.update',
      'settings.notifications.update',
      'instructorPreferences.self.view',
      'instructorPreferences.self.update',
      'instructorSchedule.self.view',
      'instructorSchedule.apologyRequest.create',
      'instructorSchedule.makeupRequest.create',
      'instructorSchedule.requests.self.update',
      'instructorSchedule.requests.self.delete',
    ],
  },
  student: {
    id: 'student-demo',
    name: 'Demo Student',
    email: DEMO_CREDENTIALS.email,
    role: {
      key: 'student',
      name: 'Student',
      color: '#f8961e',
    },
    permissions: [
      'dashboard.view',
      'sidebar.view',
      'notifications.view',
      'settings.view',
      'settings.profile.update',
      'settings.security.update',
      'settings.notifications.update',
      'studentSchedule.self.view',
      'studentGrades.view',
    ],
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
    permissions: [
      'dashboard.view',
      'sidebar.view',
      'courses.view',
      'semesters.view',
      'notifications.view',
      'settings.view',
      'settings.profile.update',
      'settings.security.update',
      'settings.notifications.update',
    ],
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
    permissions: [
      'dashboard.view',
      'sidebar.view',
      'notifications.view',
      'settings.view',
      'settings.profile.update',
      'settings.security.update',
      'settings.notifications.update',
      'studentGrades.create',
      'studentGrades.update',
      'studentGrades.delete',
    ],
  },
})

function getDelay() {
  const envValue = Number(import.meta.env.VITE_AUTH_DELAY_MS)
  return Number.isFinite(envValue) && envValue >= 0 ? envValue : 1000
}

function createAuthError(code, message) {
  return { code, message }
}

const resetCodeStore = new Map()

function normalizeEmail(email) {
  return String(email ?? '')
    .trim()
    .toLowerCase()
}

function getOrCreateResetEntry(email) {
  const normalizedEmail = normalizeEmail(email)
  const existing = resetCodeStore.get(normalizedEmail)
  if (existing) return existing

  const created = {
    code: '123456',
    verified: false,
  }
  resetCodeStore.set(normalizedEmail, created)
  return created
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

  async requestPasswordReset(email) {
    await sleep(getDelay())
    getOrCreateResetEntry(email)
    return { delivered: true }
  },

  async verifyPasswordResetCode({ email, code }) {
    await sleep(getDelay())

    const normalizedEmail = normalizeEmail(email)
    const entry = resetCodeStore.get(normalizedEmail)
    const normalizedCode = String(code ?? '').trim()

    if (!entry || normalizedCode !== entry.code) {
      throw createAuthError('INVALID_RESET_CODE', 'Invalid reset code')
    }

    entry.verified = true
    return { verified: true }
  },

  async resetPassword({ email, code, password }) {
    await sleep(getDelay())

    const normalizedEmail = normalizeEmail(email)
    const entry = resetCodeStore.get(normalizedEmail)
    const normalizedCode = String(code ?? '').trim()

    if (!entry || !entry.verified || normalizedCode !== entry.code) {
      throw createAuthError('RESET_NOT_ALLOWED', 'Reset password request is invalid')
    }

    if (typeof password !== 'string' || password.length < 8) {
      throw createAuthError('WEAK_PASSWORD', 'Password is too weak')
    }

    resetCodeStore.delete(normalizedEmail)
    return { success: true }
  },
}
