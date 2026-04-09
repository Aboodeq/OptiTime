import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useRolesStore } from '@/features/roles/model/stores/roles.store'

export function useRolesManagementPage() {
  const rolesStore = useRolesStore()
  rolesStore.ensureInitialized()

  const { roles, permissionCatalog } = storeToRefs(rolesStore)
  const activeRoleId = ref(null)
  const draft = ref(rolesStore.createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeRoleId.value))

  function startCreateRole() {
    activeRoleId.value = null
    draft.value = rolesStore.createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditRole(role) {
    activeRoleId.value = role.id
    draft.value = rolesStore.buildDraftFromRole(role)
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

  async function saveRole() {
    if (activeRoleId.value) {
      const updated = await rolesStore.updateRoleFromDraft(activeRoleId.value, draft.value)
      if (!updated) return false
      closeDialog()
      return true
    }

    const created = await rolesStore.createRoleFromDraft(draft.value)
    if (!created) return false
    closeDialog()
    return true
  }

  async function deleteRole(roleId) {
    await rolesStore.deleteRole(roleId)
    if (activeRoleId.value === roleId) {
      activeRoleId.value = null
      draft.value = rolesStore.createEmptyDraft()
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
