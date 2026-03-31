const EXCLUDED_ROLE_KEYS = Object.freeze(['student', 'instructor'])

const MANAGEABLE_USERS = Object.freeze([
  {
    id: 'user-admin',
    name: 'System Admin',
    email: 'admin@optitime.com',
    password: 'Admin@123',
    role: 'admin',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'user-management',
    name: 'Academic Management',
    email: 'management@optitime.com',
    password: 'Manage@123',
    role: 'management',
    faculty_id: 'faculty-business',
    department_id: 'dept-finance',
    is_active: true,
  },
  {
    id: 'user-coordinator',
    name: 'Scheduling Coordinator',
    email: 'coordinator@optitime.com',
    password: 'Coord@123',
    role: 'coordinator',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-networks',
    is_active: true,
  },
  {
    id: 'user-exams',
    name: 'Exams Office',
    email: 'exams@optitime.com',
    password: 'Exams@123',
    role: 'exams',
    faculty_id: 'faculty-business',
    department_id: 'dept-finance',
    is_active: false,
  },
])

const MANAGEABLE_ROLES = Object.freeze([
  { key: 'admin', label: 'System admin' },
  { key: 'management', label: 'Management' },
  { key: 'coordinator', label: 'Resource coordinator' },
  { key: 'exams', label: 'Exams office' },
])

function cloneUser(user) {
  return { ...user }
}

export const usersService = {
  async getUsers() {
    return MANAGEABLE_USERS.map(cloneUser)
  },

  async getManageableRoles() {
    return MANAGEABLE_ROLES.map((role) => ({ ...role }))
  },

  getExcludedRoleKeys() {
    return [...EXCLUDED_ROLE_KEYS]
  },
}
