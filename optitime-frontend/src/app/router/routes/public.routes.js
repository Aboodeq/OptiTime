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
    requiredPermissions: ['instructors.view'],
    titleKey: 'routes.instructorsManagement',
  },
}

export const studentsManagementRoute = {
  path: '/admin/students',
  name: 'students-management',
  component: () => import('@/features/students/ui/pages/StudentsManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['students.view'],
    titleKey: 'routes.studentsManagement',
  },
}

export const specialitiesManagementRoute = {
  path: '/admin/specialities',
  name: 'specialities-management',
  component: () => import('@/features/specialities/ui/pages/SpecialitiesManagementPage.vue'),
  meta: {
    requiresAuth: true,
    requiredPermissions: ['specialities.view'],
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
    requiredPermissions: ['constraints.view'],
    titleKey: 'routes.constraintsManagement',
  },
}

export const fallbackRoutes = [
  { path: '/', redirect: '/login' },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]
