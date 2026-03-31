import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { usersService } from '@/features/users/api/users.service'

function createEmptyDraft() {
  return {
    name: '',
    email: '',
    password: '',
    role: 'management',
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

  async function ensureInitialized() {
    if (initialized.value) return
    const [usersPayload, rolesPayload] = await Promise.all([
      usersService.getUsers(),
      usersService.getManageableRoles(),
    ])
    manageableRoles.value = rolesPayload
    users.value = usersPayload.filter((user) => isRoleManageable(user.role))
    initialized.value = true
  }

  function isRoleManageable(roleKey) {
    return Boolean(roleKey) && !excludedRoleKeys.value.includes(roleKey)
  }

  function buildDraftFromUser(user) {
    return {
      name: user.name,
      email: user.email,
      password: user.password ?? '',
      role: user.role,
      faculty_id: user.faculty_id ?? '',
      department_id: user.department_id ?? '',
      is_active: Boolean(user.is_active),
    }
  }

  function normalizeDraft(draft) {
    const name = draft.name?.trim() || ''
    const email = draft.email?.trim().toLowerCase() || ''
    const password = draft.password?.trim() || ''
    const role = draft.role?.trim() || ''
    const facultyId = draft.faculty_id?.trim() || ''
    const departmentId = draft.department_id?.trim() || ''
    if (!name || !email || !password || !role || !facultyId || !departmentId) return null
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

  function createUserFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    users.value = [
      ...users.value,
      {
        id: `user-${normalized.role}-${Date.now()}`,
        ...normalized,
      },
    ]
    return true
  }

  function updateUserFromDraft(userId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    users.value = users.value.map((user) => (user.id === userId ? { ...user, ...normalized } : user))
    return true
  }

  function deleteUser(userId) {
    users.value = users.value.filter((user) => user.id !== userId)
  }

  return {
    users,
    manageableRoles,
    usersCount,
    activeUsersCount,
    inactiveUsersCount,
    ensureInitialized,
    isRoleManageable,
    createEmptyDraft,
    buildDraftFromUser,
    createUserFromDraft,
    updateUserFromDraft,
    deleteUser,
  }
})
