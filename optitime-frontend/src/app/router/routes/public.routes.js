export const loginRoute = {
  path: '/login',
  name: 'login',
  component: () => import('@/views/auth/LoginView.vue'),
  meta: { guestOnly: true, titleKey: 'routes.login' },
}

export const dashboardRoute = {
  path: '/dashboard',
  name: 'dashboard',
  component: () => import('@/views/DashboardView.vue'),
  meta: { requiresAuth: true, titleKey: 'routes.dashboard' },
}

export const rolesManagementRoute = {
  path: '/admin/roles',
  name: 'roles-management',
  component: () => import('@/views/RolesManagementView.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['roles.view'],
    titleKey: 'routes.rolesManagement',
  },
}

export const fallbackRoutes = [
  { path: '/', redirect: '/login' },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]
