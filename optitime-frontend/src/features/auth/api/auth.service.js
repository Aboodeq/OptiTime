import { apiJson } from '@/api/client'
import { getStoredToken } from '@/lib/authSession'
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
      'roles.create',
      'roles.update',
      'roles.delete',
      'roles.view',
      'faculties.create',
      'faculties.update',
      'faculties.delete',
      'faculties.view',
      'users.create',
      'users.update',
      'users.delete',
      'users.view',
      'specializations.create',
      'specializations.update',
      'specializations.delete',
      'specializations.view',
      'semesters.create',
      'semesters.update',
      'semesters.delete',
      'semesters.view',
      'schedule_settings.view',
      'audit_logs.view',
      'profile.view',
      'profile.update',
      'notifications.view',
      'notifications.update',
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
      'notifications.view',
      'notifications.update',
      'profile.view',
      'profile.update',
      'schedules.view',
      'schedules.update',
      'schedules.create',
      'schedules.delete',
      'schedules.publish',
      'schedule.generate',
      'lecture_requests.view',
      'lecture_requests.update',
      'resources.view',
      'resources.create',
      'resources.update',
      'resources.delete',
      'coordinator_rooms.view',
      'coordinator_rooms.create',
      'coordinator_rooms.update',
      'coordinator_rooms.delete',
      'coordinator_courses.view',
      'coordinator_courses.create',
      'coordinator_courses.update',
      'coordinator_courses.delete',
      'courses.view',
      'courses.create',
      'courses.update',
      'courses.delete',
    ],
  },
  instructor: {
    id: 'demo-instructor',
    name: 'Demo Instructor',
    email: DEMO_CREDENTIALS.email,
    instructor: {
      id: 'demo-instructor-profile',
      user_id: 'demo-instructor',
      min_work_hours_per_week: 10,
      max_work_hours_per_week: 40,
    },
    role: {
      key: 'instructor',
      name: 'Instructor',
      color: '#2dc653',
    },
    permissions: [
      'dashboard.view',
      'notifications.view',
      'notifications.update',
      'profile.view',
      'profile.update',
      'instructor_availability.view',
      'instructor_availability.create',
      'instructor_availability.update',
      'instructor_availability.delete',
      'instructor.schedule.view',
      'instructor.schedule.export',
      'instructor.requests.view',
      'instructor.requests.create',
      'instructor.requests.update',
      'instructor.requests.delete',
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
      'notifications.view',
      'notifications.update',
      'profile.view',
      'profile.update',
      'student.schedule.view',
      'student.schedule.export',
      'student.grades.view',
      'student.grades.export',
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
      'courses.view',
      'semesters.view',
      'notifications.view',
      'notifications.update',
      'profile.view',
      'profile.update',
      'reports.classroom_occupancy',
      'reports.study_hours',
      'reports.lab_utilization',
      'reports.peak_periods',
      'reports.resource_utilization',
      'reports.compliance',
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
      'notifications.view',
      'notifications.update',
      'profile.view',
      'profile.update',
      'exam_sessions.view',
      'exam_sessions.update',
      'exam_grades.view',
      'exam_grades.update',
      'exam_grades.export',
    ],
  },
})

function isAuthDemoEnabled() {
  return import.meta.env.VITE_AUTH_DEMO === 'true'
}

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

function mapApiInstructorToApp(apiInstructor) {
  if (!apiInstructor || typeof apiInstructor !== 'object') return null
  const id = apiInstructor.id
  const userId = apiInstructor.user_id
  if (!id || !userId) return null
  return {
    id: String(id),
    user_id: String(userId),
    min_work_hours_per_week: Number(apiInstructor.min_work_hours_per_week),
    max_work_hours_per_week: Number(apiInstructor.max_work_hours_per_week),
  }
}

/**
 * Map Laravel `/auth/login` or `/user` payload to the shape used by the auth store.
 */
export function mapApiUserToAppUser(apiUser) {
  if (!apiUser || typeof apiUser !== 'object') return null

  const rawRole = apiUser.role
  const permissionCodes = []
  if (rawRole?.permissions?.length) {
    for (const p of rawRole.permissions) {
      if (typeof p === 'string') permissionCodes.push(p.replace(/\s+/g, ''))
      else if (p && typeof p === 'object' && typeof p.code === 'string') {
        permissionCodes.push(p.code.replace(/\s+/g, ''))
      }
    }
  }

  const role =
    rawRole && typeof rawRole === 'object'
      ? {
          key: rawRole.code ?? 'unknown',
          name: rawRole.name_en ?? rawRole.name ?? rawRole.code ?? 'Unknown',
          color: rawRole.sidebar_color ?? '#334155',
        }
      : {
          key: 'unknown',
          name: 'Unknown',
          color: '#334155',
        }

  const instructor = mapApiInstructorToApp(apiUser.instructor)

  return {
    id: apiUser.id,
    name: apiUser.full_name ?? apiUser.name ?? '',
    email: apiUser.email,
    avatar_url: apiUser.avatar_url ?? '',
    role,
    permissions: [...new Set(permissionCodes)],
    ...(instructor ? { instructor } : {}),
  }
}

export const authService = {
  isDemoMode: () => isAuthDemoEnabled(),

  async login(credentials) {
    if (isAuthDemoEnabled()) {
      await sleep(getDelay())
      const email = credentials.email.trim().toLowerCase()
      const password = credentials.password
      if (email === DEMO_CREDENTIALS.email && password === DEMO_CREDENTIALS.password) {
        return { token: 'demo-token', user: { ...DEMO_USERS.admin, name: 'Demo User' } }
      }
      throw createAuthError('INVALID_CREDENTIALS', 'Invalid credentials')
    }

    const { data } = await apiJson('/auth/login', {
      method: 'POST',
      json: {
        email: credentials.email.trim(),
        password: credentials.password,
      },
    })

    const token = data?.token
    const user = mapApiUserToAppUser(data?.user)
    if (!token || !user) {
      throw createAuthError('INVALID_CREDENTIALS', 'Invalid response')
    }

    return { token, user }
  },

  async loginAsDemoRole(role) {
    if (!isAuthDemoEnabled()) {
      throw createAuthError('DEMO_DISABLED', 'Demo login is disabled')
    }

    await sleep(450)
    const user = DEMO_USERS[role]
    if (!user) {
      throw createAuthError('INVALID_ROLE', 'Invalid credentials')
    }
    return { token: `demo-token-${role}`, user: { ...user } }
  },

  async logout() {
    if (isAuthDemoEnabled()) {
      return
    }

    const token = getStoredToken()
    if (!token || token.startsWith('demo-token')) {
      return
    }

    try {
      await apiJson('/auth/logout', { method: 'POST' })
    } catch {
      // still clear session locally
    }
  },

  async fetchCurrentUser() {
    if (isAuthDemoEnabled()) {
      const token = getStoredToken()
      if (!token?.startsWith('demo-token')) return null
      const role = token === 'demo-token' ? 'admin' : token.replace('demo-token-', '')
      const user = DEMO_USERS[role] ?? DEMO_USERS.admin
      return { ...user }
    }

    const { data } = await apiJson('/user')
    return mapApiUserToAppUser(data)
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
