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
    ],
  },
])
