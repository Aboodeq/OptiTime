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
])
