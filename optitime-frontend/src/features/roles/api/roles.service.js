const AVAILABLE_PERMISSIONS = Object.freeze([
  'dashboard.view',
  'roles.create',
  'roles.update',
  'roles.delete',
  'roles.view',
  'users.manage',
  'students.manage',
  'courses.manage',
  'courses.create',
  'courses.update',
  'courses.delete',
  'courses.view',
  'schedule.generate',
  'reports.view',
  'semesters.manage',
  'constraints.manage',
  'settings.manage',
  'notifications.view',
  'settings.view',
  'settings.profile.update',
  'settings.security.update',
  'settings.notifications.update',
  'settings.backup.create',
])

const INITIAL_ROLES = Object.freeze([
  {
    id: 'role-admin',
    code: 'admin',
    name_ar: 'مدير النظام',
    name_en: 'System admin',
    sidebar_color: '#e63946',
    description: 'System role with full role-management access.',
    is_active: true,
    permissions: [
      'dashboard.view',
      'roles.create',
      'roles.update',
      'roles.delete',
      'roles.view',
      'settings.manage',
    ],
  },
  {
    id: 'role-coordinator',
    code: 'coordinator',
    name_ar: 'منسق الموارد',
    name_en: 'Resource coordinator',
    sidebar_color: '#4361ee',
    description: '',
    is_active: true,
    permissions: [
      'dashboard.view',
      'courses.manage',
      'schedule.generate',
      'settings.view',
      'settings.profile.update',
      'settings.security.update',
      'settings.notifications.update',
    ],
  },
  {
    id: 'role-student',
    code: 'student',
    name_ar: 'طالب',
    name_en: 'Student',
    sidebar_color: '#f8961e',
    description: '',
    is_active: true,
    permissions: [
      'dashboard.view',
      'settings.view',
      'settings.profile.update',
      'settings.security.update',
      'settings.notifications.update',
    ],
  },
])

function cloneRole(role) {
  return {
    ...role,
    permissions: [...role.permissions],
  }
}

export const rolesService = {
  async getPermissionsCatalog() {
    return [...AVAILABLE_PERMISSIONS]
  },

  async getRoles() {
    return INITIAL_ROLES.map(cloneRole)
  },
}
