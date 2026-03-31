import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/store/auth.store'
import {
  dashboardRoute,
  fallbackRoutes,
  loginRoute,
  organizationManagementRoute,
  rolesManagementRoute,
  usersManagementRoute,
} from './routes/public.routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    loginRoute,
    dashboardRoute,
    rolesManagementRoute,
    organizationManagementRoute,
    usersManagementRoute,
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

  if (to.meta?.requiredPermissions?.length && !authStore.hasAnyPermission(to.meta.requiredPermissions)) {
    return { name: 'dashboard' }
  }

  return true
})

export default router
