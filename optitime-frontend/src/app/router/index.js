import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/store/auth.store'
import {
  coursesManagementRoute,
  coordinatorLectureRequestsRoute,
  constraintsManagementRoute,
  coordinatorWeeklyScheduleRoute,
  dashboardRoute,
  auditLogsRoute,
  fallbackRoutes,
  forgotPasswordCodeRoute,
  forgotPasswordEmailRoute,
  forgotPasswordResetRoute,
  instructorLectureRequestsRoute,
  instructorSelfAvailabilityRoute,
  instructorWeeklyScheduleRoute,
  instructorsManagementRoute,
  loginRoute,
  managementReportsRoute,
  notificationsRoute,
  organizationManagementRoute,
  resourcesManagementRoute,
  roomsManagementRoute,
  rolesManagementRoute,
  semestersManagementRoute,
  studentWeeklyScheduleRoute,
  specialitiesManagementRoute,
  studentsManagementRoute,
  studentGradesRoute,
  examsDashboardRoute,
  examsSessionGradesRoute,
  userSettingsRoute,
  usersManagementRoute,
} from './routes/public.routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    loginRoute,
    forgotPasswordEmailRoute,
    forgotPasswordCodeRoute,
    forgotPasswordResetRoute,
    dashboardRoute,
    managementReportsRoute,
    rolesManagementRoute,
    organizationManagementRoute,
    usersManagementRoute,
    instructorsManagementRoute,
    instructorSelfAvailabilityRoute,
    instructorWeeklyScheduleRoute,
    instructorLectureRequestsRoute,
    coordinatorWeeklyScheduleRoute,
    coordinatorLectureRequestsRoute,
    studentWeeklyScheduleRoute,
    studentGradesRoute,
    examsDashboardRoute,
    examsSessionGradesRoute,
    studentsManagementRoute,
    resourcesManagementRoute,
    roomsManagementRoute,
    coursesManagementRoute,
    specialitiesManagementRoute,
    semestersManagementRoute,
    constraintsManagementRoute,
    auditLogsRoute,
    userSettingsRoute,
    notificationsRoute,
    ...fallbackRoutes,
  ],
})

router.beforeEach((to) => {
  const authStore = useAuthStore()

  if (to.meta?.requiresAuth && !authStore.user) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta?.guestOnly && authStore.user) {
    const redirect =
      typeof to.query.redirect === 'string' && to.query.redirect.startsWith('/')
        ? to.query.redirect
        : '/dashboard'

    return { path: redirect === '/login' ? '/dashboard' : redirect }
  }

  if (
    to.meta?.requiredPermissions?.length &&
    !authStore.hasAnyPermission(to.meta.requiredPermissions)
  ) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
