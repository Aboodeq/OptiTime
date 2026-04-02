import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/store/auth.store'
import {
  constraintsManagementRoute,
  dashboardRoute,
  auditLogsRoute,
  fallbackRoutes,
  forgotPasswordCodeRoute,
  forgotPasswordEmailRoute,
  forgotPasswordResetRoute,
  instructorsManagementRoute,
  loginRoute,
  notificationsRoute,
  organizationManagementRoute,
  rolesManagementRoute,
  semestersManagementRoute,
  specialitiesManagementRoute,
  studentsManagementRoute,
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
    rolesManagementRoute,
    organizationManagementRoute,
    usersManagementRoute,
    instructorsManagementRoute,
    studentsManagementRoute,
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
