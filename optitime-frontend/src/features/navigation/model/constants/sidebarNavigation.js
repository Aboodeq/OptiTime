export const BASE_SIDEBAR_SECTIONS = Object.freeze([
  {
    id: 'home',
    titleKey: 'nav.sections.home',
    items: [
      {
        id: 'dashboard',
        routeName: 'dashboard',
        icon: 'bi bi-grid-1x2-fill',
        labelKey: 'routes.dashboard',
        permissions: ['dashboard.view'],
      },
      {
        id: 'user-settings',
        routeName: 'user-settings',
        icon: 'bi bi-gear-fill',
        labelKey: 'routes.userSettings',
        permissions: ['settings.view'],
      },
    ],
  },
  {
    id: 'system',
    titleKey: 'nav.sections.system',
    items: [
      {
        id: 'roles-management',
        routeName: 'roles-management',
        icon: 'bi bi-shield-lock-fill',
        labelKey: 'routes.rolesManagement',
        permissions: ['roles.view'],
      },
      {
        id: 'organization-management',
        routeName: 'organization-management',
        icon: 'bi bi-diagram-3-fill',
        labelKey: 'routes.organizationManagement',
        permissions: ['organization.view'],
      },
      {
        id: 'users-management',
        routeName: 'users-management',
        icon: 'bi bi-people-fill',
        labelKey: 'routes.usersManagement',
        permissions: ['users.view'],
      },
      {
        id: 'instructors-management',
        routeName: 'instructors-management',
        icon: 'bi bi-mortarboard-fill',
        labelKey: 'routes.instructorsManagement',
        permissions: ['instructors.view'],
      },
      {
        id: 'students-management',
        routeName: 'students-management',
        icon: 'bi bi-mortarboard',
        labelKey: 'routes.studentsManagement',
        permissions: ['students.view'],
      },
      {
        id: 'specialities-management',
        routeName: 'specialities-management',
        icon: 'bi bi-bookmarks-fill',
        labelKey: 'routes.specialitiesManagement',
        permissions: ['specialities.view'],
      },
      {
        id: 'semesters-management',
        routeName: 'semesters-management',
        icon: 'bi bi-calendar3',
        labelKey: 'routes.semestersManagement',
        permissions: ['semesters.view'],
      },
      {
        id: 'constraints-management',
        routeName: 'constraints-management',
        icon: 'bi bi-sliders',
        labelKey: 'routes.constraintsManagement',
        permissions: ['constraints.view'],
      },
    ],
  },
])
