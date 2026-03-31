import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { rolesService } from '@/features/roles/api/roles.service'
import { useAuthStore } from '@/store/auth.store'

function createEmptyDraft() {
  return {
    code: '',
    name_ar: '',
    name_en: '',
    sidebar_color: '#4361ee',
    description: '',
    is_active: true,
    permissions: ['dashboard.view'],
  }
}

export const useRolesStore = defineStore('roles', () => {
  const authStore = useAuthStore()
  const roles = ref([])
  const permissionCatalog = ref([])
  const initialized = ref(false)

  const rolesCount = computed(() => roles.value.length)
  const totalPermissions = computed(() =>
    roles.value.reduce((sum, role) => sum + role.permissions.length, 0),
  )
  const adminRoles = computed(
    () => roles.value.filter((role) => role.permissions.includes('roles.view')).length,
  )

  async function ensureInitialized() {
    if (initialized.value) return
    const [rolesPayload, permissionsPayload] = await Promise.all([
      rolesService.getRoles(),
      rolesService.getPermissionsCatalog(),
    ])
    roles.value = rolesPayload
    permissionCatalog.value = permissionsPayload
    initialized.value = true
  }

  function buildDraftFromRole(role) {
    return {
      code: role.code,
      name_ar: role.name_ar,
      name_en: role.name_en,
      sidebar_color: role.sidebar_color,
      description: role.description ?? '',
      is_active: Boolean(role.is_active),
      permissions: [...role.permissions],
    }
  }

  function normalizeDraft(draft) {
    const code = draft.code.trim().toLowerCase()
    const nameAr = draft.name_ar.trim()
    const nameEn = draft.name_en.trim()
    if (!code || !nameAr || !nameEn) return null

    return {
      code,
      name_ar: nameAr,
      name_en: nameEn,
      sidebar_color: draft.sidebar_color || '#4361ee',
      description: draft.description?.trim() || '',
      is_active: Boolean(draft.is_active),
      permissions: [...new Set(draft.permissions)],
    }
  }

  function createRoleFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false

    roles.value = [
      ...roles.value,
      {
        id: `role-${normalized.code}-${Date.now()}`,
        ...normalized,
      },
    ]
    return true
  }

  function syncCurrentSessionWithRoleUpdate(previousRole, nextRole) {
    if (!authStore.user || !previousRole || !nextRole) return
    if (authStore.roleKey !== previousRole.code) return

    authStore.patchCurrentUser({
      role: {
        key: nextRole.code,
        name: nextRole.name_en,
        color: nextRole.sidebar_color,
      },
      permissions: [...nextRole.permissions],
    })
  }

  function updateRoleFromDraft(roleId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const previousRole = roles.value.find((role) => role.id === roleId)
    if (!previousRole) return false

    const nextRole = { ...previousRole, ...normalized }

    roles.value = roles.value.map((role) =>
      role.id === roleId ? nextRole : role,
    )
    syncCurrentSessionWithRoleUpdate(previousRole, nextRole)
    return true
  }

  function deleteRole(roleId) {
    roles.value = roles.value.filter((role) => role.id !== roleId)
  }

  return {
    roles,
    permissionCatalog,
    rolesCount,
    totalPermissions,
    adminRoles,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromRole,
    createRoleFromDraft,
    updateRoleFromDraft,
    deleteRole,
  }
})
