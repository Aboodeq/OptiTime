export const loginRoute = {
  path: '/login',
  name: 'login',
  component: () => import('@/features/auth/ui/pages/LoginPage.vue'),
  meta: { guestOnly: true, titleKey: 'routes.login' },
}

export const forgotPasswordEmailRoute = {
  path: '/forgot-password',
  name: 'forgot-password-email',
  component: () => import('@/features/auth/ui/pages/ForgotPasswordPage.vue'),
  props: { step: 'email' },
  meta: { guestOnly: true, titleKey: 'routes.forgotPasswordEmail' },
}

export const forgotPasswordCodeRoute = {
  path: '/forgot-password/code',
  name: 'forgot-password-code',
  component: () => import('@/features/auth/ui/pages/ForgotPasswordPage.vue'),
  props: { step: 'code' },
  meta: { guestOnly: true, titleKey: 'routes.forgotPasswordCode' },
}

export const forgotPasswordResetRoute = {
  path: '/forgot-password/reset',
  name: 'forgot-password-reset',
  component: () => import('@/features/auth/ui/pages/ForgotPasswordPage.vue'),
  props: { step: 'reset' },
  meta: { guestOnly: true, titleKey: 'routes.forgotPasswordReset' },
}

export const dashboardRoute = {
  path: '/dashboard',
  name: 'dashboard',
  component: () => import('@/features/dashboard/ui/pages/DashboardPage.vue'),
  meta: { requiresAuth: true, titleKey: 'routes.dashboard' },
}

export const rolesManagementRoute = {
  path: '/admin/roles',
  name: 'roles-management',
  component: () => import('@/features/roles/ui/pages/RolesManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['roles.view'],
    titleKey: 'routes.rolesManagement',
  },
}

export const organizationManagementRoute = {
  path: '/admin/organization',
  name: 'organization-management',
  component: () => import('@/features/organization/ui/pages/AdminOrganizationPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['faculties.view'],
    titleKey: 'routes.organizationManagement',
  },
}

export const usersManagementRoute = {
  path: '/admin/users',
  name: 'users-management',
  component: () => import('@/features/users/ui/pages/UsersManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['users.view'],
    titleKey: 'routes.usersManagement',
  },
}

export const instructorsManagementRoute = {
  path: '/admin/instructors',
  name: 'instructors-management',
  component: () => import('@/features/instructors/ui/pages/InstructorsManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['users.view'],
    titleKey: 'routes.instructorsManagement',
  },
}

export const instructorSelfAvailabilityRoute = {
  path: '/instructor/availability',
  name: 'instructor-self-availability',
  component: () =>
    import('@/features/instructor-preferences/ui/pages/InstructorSelfAvailabilityPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['instructor_availability.view'],
    titleKey: 'routes.instructorSelfAvailability',
  },
}

export const instructorWeeklyScheduleRoute = {
  path: '/instructor/weekly-schedule',
  name: 'instructor-weekly-schedule',
  component: () =>
    import('@/features/coordinator-schedule/ui/pages/InstructorWeeklySchedulePage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['instructor.schedule.view'],
    titleKey: 'routes.instructorWeeklySchedule',
  },
}

export const instructorLectureRequestsRoute = {
  path: '/instructor/lecture-requests',
  name: 'instructor-lecture-requests',
  component: () =>
    import('@/features/lecture-requests/ui/pages/InstructorLectureRequestsPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['instructor.requests.create'],
    titleKey: 'routes.instructorLectureRequests',
  },
}

export const studentWeeklyScheduleRoute = {
  path: '/student/weekly-schedule',
  name: 'student-weekly-schedule',
  component: () => import('@/features/coordinator-schedule/ui/pages/StudentWeeklySchedulePage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['student.schedule.view'],
    titleKey: 'routes.studentWeeklySchedule',
  },
}

export const studentGradesRoute = {
  path: '/student/grades',
  name: 'student-grades',
  component: () => import('@/features/session-grades/ui/pages/StudentGradesPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['student.grades.view'],
    titleKey: 'routes.studentGrades',
  },
}

export const examsSessionGradesRoute = {
  path: '/exams/session-grades',
  name: 'exams-session-grades',
  component: () => import('@/features/session-grades/ui/pages/ExamsSessionGradesPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['exam_grades.update'],
    titleKey: 'routes.examsSessionGrades',
  },
}

export const coordinatorWeeklyScheduleRoute = {
  path: '/coordinator/weekly-schedule',
  name: 'coordinator-weekly-schedule',
  component: () =>
    import('@/features/coordinator-schedule/ui/pages/CoordinatorWeeklySchedulePage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['schedules.view'],
    titleKey: 'routes.coordinatorWeeklySchedule',
  },
}

export const coordinatorLectureRequestsRoute = {
  path: '/coordinator/lecture-requests',
  name: 'coordinator-lecture-requests',
  component: () =>
    import('@/features/lecture-requests/ui/pages/CoordinatorLectureRequestsPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['lecture_requests.view'],
    titleKey: 'routes.coordinatorLectureRequests',
  },
}

export const studentsManagementRoute = {
  path: '/admin/students',
  name: 'students-management',
  component: () => import('@/features/students/ui/pages/StudentsManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['users.view'],
    titleKey: 'routes.studentsManagement',
  },
}

export const resourcesManagementRoute = {
  path: '/admin/resources',
  name: 'resources-management',
  component: () => import('@/features/resources/ui/pages/ResourcesManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['resources.view'],
    titleKey: 'routes.resourcesManagement',
  },
}

export const roomsManagementRoute = {
  path: '/admin/rooms',
  name: 'rooms-management',
  component: () => import('@/features/rooms/ui/pages/RoomsManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['rooms.view'],
    titleKey: 'routes.roomsManagement',
  },
}

export const coursesManagementRoute = {
  path: '/admin/courses',
  name: 'courses-management',
  component: () => import('@/features/courses/ui/pages/CoursesManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['courses.view'],
    titleKey: 'routes.coursesManagement',
  },
}

export const specialitiesManagementRoute = {
  path: '/admin/specialities',
  name: 'specialities-management',
  component: () => import('@/features/specialities/ui/pages/SpecialitiesManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['specializations.view'],
    titleKey: 'routes.specialitiesManagement',
  },
}

export const semestersManagementRoute = {
  path: '/admin/semesters',
  name: 'semesters-management',
  component: () => import('@/features/semesters/ui/pages/SemestersManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['semesters.view'],
    titleKey: 'routes.semestersManagement',
  },
}

export const constraintsManagementRoute = {
  path: '/admin/constraints',
  name: 'constraints-management',
  component: () => import('@/features/constraints/ui/pages/ConstraintsManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['schedule_settings.view'],
    titleKey: 'routes.constraintsManagement',
  },
}

export const userSettingsRoute = {
  path: '/settings',
  name: 'user-settings',
  component: () => import('@/features/user-settings/ui/pages/UserSettingsPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['profile.view'],
    titleKey: 'routes.userSettings',
  },
}

export const notificationsRoute = {
  path: '/notifications',
  name: 'notifications',
  component: () => import('@/features/notifications/ui/pages/NotificationsPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['notifications.view'],
    titleKey: 'routes.notifications',
  },
}

export const auditLogsRoute = {
  path: '/admin/audit-logs',
  name: 'audit-logs',
  component: () => import('@/features/audit-logs/ui/pages/AuditLogsPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['audit_logs.view'],
    titleKey: 'routes.auditLogs',
  },
}

export const fallbackRoutes = [
  { path: '/', redirect: '/login' },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]
