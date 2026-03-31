import { computed, ref } from 'vue'

const AVAILABLE_PERMISSIONS = Object.freeze([
  'dashboard.view',
  'roles.create',
  'roles.update',
  'roles.delete',
  'roles.view',
  'users.manage',
  'courses.manage',
  'schedule.generate',
  'reports.view',
])

const INITIAL_ROLES = Object.freeze([
  {
    id: 'role-admin',
    name: 'System admin',
    color: '#e63946',
    permissions: ['dashboard.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.view'],
  },
  {
    id: 'role-coordinator',
    name: 'Resource coordinator',
    color: '#4361ee',
    permissions: ['dashboard.view', 'courses.manage', 'schedule.generate'],
  },
  {
    id: 'role-student',
    name: 'Student',
    color: '#f8961e',
    permissions: ['dashboard.view'],
  },
])

export function useRolesManagementPage() {
  const roles = ref(INITIAL_ROLES.map((role) => ({ ...role, permissions: [...role.permissions] })))
  const activeRoleId = ref(null)
  const draft = ref(createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeRoleId.value))
  const permissionCatalog = AVAILABLE_PERMISSIONS

  function createEmptyDraft() {
    return {
      name: '',
      color: '#4361ee',
      permissions: ['dashboard.view'],
    }
  }

  function startCreateRole() {
    activeRoleId.value = null
    draft.value = createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditRole(role) {
    activeRoleId.value = role.id
    draft.value = {
      name: role.name,
      color: role.color,
      permissions: [...role.permissions],
    }
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  function toggleDraftPermission(permission) {
    if (draft.value.permissions.includes(permission)) {
      draft.value.permissions = draft.value.permissions.filter((item) => item !== permission)
      return
    }
    draft.value.permissions = [...draft.value.permissions, permission]
  }

  function saveRole() {
    const name = draft.value.name.trim()
    if (!name) return false

    const normalized = {
      name,
      color: draft.value.color || '#4361ee',
      permissions: [...new Set(draft.value.permissions)],
    }

    if (activeRoleId.value) {
      roles.value = roles.value.map((role) =>
        role.id === activeRoleId.value ? { ...role, ...normalized } : role,
      )
      closeDialog()
      return true
    }

    roles.value = [
      ...roles.value,
      {
        id: `role-${Date.now()}`,
        ...normalized,
      },
    ]
    closeDialog()
    return true
  }

  function deleteRole(roleId) {
    roles.value = roles.value.filter((role) => role.id !== roleId)
    if (activeRoleId.value === roleId) {
      activeRoleId.value = null
      draft.value = createEmptyDraft()
      closeDialog()
    }
  }

  return {
    roles,
    draft,
    isDialogOpen,
    isEditing,
    permissionCatalog,
    startCreateRole,
    startEditRole,
    closeDialog,
    toggleDraftPermission,
    saveRole,
    deleteRole,
  }
}
