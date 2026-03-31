export const loginRoute = {
  path: '/login',
  name: 'login',
  component: () => import('@/features/auth/ui/pages/LoginPage.vue'),
  meta: { guestOnly: true, titleKey: 'routes.login' },
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
    requiredPermissions: ['organization.view'],
    titleKey: 'routes.organizationManagement',
  },
}

export const fallbackRoutes = [
  { path: '/', redirect: '/login' },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]
