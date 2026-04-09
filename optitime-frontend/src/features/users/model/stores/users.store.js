import { defineStore } from 'pinia'
import { computed, ref, unref } from 'vue'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'
import { mapApiUserToRow, usersService } from '@/features/users/api/users.service'

function resolveFacultyIdFromDepartment(departmentId, faculties) {
  if (!departmentId || !Array.isArray(faculties)) return ''
  for (const faculty of faculties) {
    if (faculty.departments?.some((d) => d.id === departmentId)) {
      return faculty.id
    }
  }
  return ''
}

function createEmptyDraft() {
  return {
    name: '',
    email: '',
    password: '',
    role: '',
    faculty_id: '',
    department_id: '',
    is_active: true,
  }
}

export const useUsersStore = defineStore('users', () => {
  const users = ref([])
  const manageableRoles = ref([])
  const initialized = ref(false)
  const excludedRoleKeys = ref(usersService.getExcludedRoleKeys())

  const usersCount = computed(() => users.value.length)
  const activeUsersCount = computed(() => users.value.filter((user) => user.is_active).length)
  const inactiveUsersCount = computed(() => usersCount.value - activeUsersCount.value)

  function isRoleManageable(roleKey) {
    return Boolean(roleKey) && !excludedRoleKeys.value.includes(roleKey)
  }

  function roleIdForKey(roleKey) {
    return manageableRoles.value.find((r) => r.key === roleKey)?.id ?? null
  }

  async function reloadUsers() {
    const orgStore = useOrganizationStore()
    await orgStore.ensureInitialized()
    const raw = await usersService.getUsersRaw()
    const facultiesList = unref(orgStore.faculties) ?? []
    users.value = raw
      .map((u) => mapApiUserToRow(u, facultiesList))
      .filter((u) => isRoleManageable(u.role))
  }

  async function ensureInitialized() {
    if (initialized.value) return
    try {
      manageableRoles.value = await usersService.getManageableRoles()
      await reloadUsers()
    } catch {
      users.value = []
      manageableRoles.value = []
    }
    initialized.value = true
  }

  function buildDraftFromUser(user, faculties = []) {
    const facultyId =
      user.faculty_id ||
      resolveFacultyIdFromDepartment(user.department_id, faculties) ||
      ''

    return {
      name: user.name,
      email: user.email,
      password: '',
      role: user.role,
      faculty_id: facultyId,
      department_id: user.department_id ?? '',
      is_active: Boolean(user.is_active),
    }
  }

  function normalizeCreateDraft(draft) {
    const name = draft.name?.trim() || ''
    const email = draft.email?.trim().toLowerCase() || ''
    const password = draft.password?.trim() || ''
    const role = draft.role?.trim() || ''
    const facultyId = draft.faculty_id?.trim() || ''
    const departmentId = draft.department_id?.trim() || ''
    if (!name || !email || !password || !role) return null
    if (!isRoleManageable(role)) return null

    return {
      name,
      email,
      password,
      role,
      faculty_id: facultyId,
      department_id: departmentId,
      is_active: Boolean(draft.is_active),
    }
  }

  function normalizeUpdateDraft(draft) {
    const name = draft.name?.trim() || ''
    const email = draft.email?.trim().toLowerCase() || ''
    const password = draft.password?.trim() || ''
    const role = draft.role?.trim() || ''
    const facultyId = draft.faculty_id?.trim() || ''
    const departmentId = draft.department_id?.trim() || ''
    if (!name || !email || !role) return null
    if (!isRoleManageable(role)) return null

    return {
      name,
      email,
      password: password || undefined,
      role,
      faculty_id: facultyId,
      department_id: departmentId,
      is_active: Boolean(draft.is_active),
    }
  }

  function toApiCreatePayload(normalized) {
    const roleId = roleIdForKey(normalized.role)
    if (!roleId) return null

    return {
      full_name: normalized.name,
      email: normalized.email,
      password: normalized.password,
      role_id: roleId,
      department_id: normalized.department_id || null,
      is_active: normalized.is_active,
    }
  }

  function toApiUpdatePayload(normalized) {
    const roleId = roleIdForKey(normalized.role)
    if (!roleId) return null

    const payload = {
      full_name: normalized.name,
      email: normalized.email,
      role_id: roleId,
      department_id: normalized.department_id || null,
      is_active: normalized.is_active,
    }
    if (normalized.password) {
      payload.password = normalized.password
    }
    return payload
  }

  async function createUserFromDraft(draft) {
    const normalized = normalizeCreateDraft(draft)
    if (!normalized) return false
    const body = toApiCreatePayload(normalized)
    if (!body) return false

    await usersService.createUser(body)
    await reloadUsers()
    return true
  }

  async function updateUserFromDraft(userId, draft) {
    const normalized = normalizeUpdateDraft(draft)
    if (!normalized) return false
    const body = toApiUpdatePayload(normalized)
    if (!body) return false

    await usersService.updateUser(userId, body)
    await reloadUsers()
    return true
  }

  async function deleteUser(userId) {
    await usersService.deleteUser(userId)
    await reloadUsers()
  }

  function createEmptyDraftWithDefaults() {
    const d = createEmptyDraft()
    d.role = manageableRoles.value[0]?.key ?? ''
    return d
  }

  return {
    users,
    manageableRoles,
    usersCount,
    activeUsersCount,
    inactiveUsersCount,
    ensureInitialized,
    isRoleManageable,
    createEmptyDraft: createEmptyDraftWithDefaults,
    buildDraftFromUser,
    createUserFromDraft,
    updateUserFromDraft,
    deleteUser,
  }
})
