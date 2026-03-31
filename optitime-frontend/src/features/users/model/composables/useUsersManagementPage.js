import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'
import { useUsersStore } from '@/features/users/model/stores/users.store'

export function useUsersManagementPage() {
  const usersStore = useUsersStore()
  usersStore.ensureInitialized()
  const organizationStore = useOrganizationStore()
  organizationStore.ensureInitialized()

  const { users, manageableRoles, usersCount, activeUsersCount, inactiveUsersCount } =
    storeToRefs(usersStore)
  const { faculties } = storeToRefs(organizationStore)
  const activeUserId = ref(null)
  const draft = ref(usersStore.createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeUserId.value))
  const facultyOptions = computed(() =>
    faculties.value.map((faculty) => ({
      value: faculty.id,
      label: faculty.name_en,
    })),
  )
  const departmentOptions = computed(() => {
    if (!draft.value.faculty_id) return []
    const faculty = faculties.value.find((item) => item.id === draft.value.faculty_id)
    if (!faculty) return []
    return faculty.departments.map((department) => ({
      value: department.id,
      label: department.name_en,
    }))
  })

  function startCreateUser() {
    activeUserId.value = null
    draft.value = usersStore.createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditUser(user) {
    activeUserId.value = user.id
    draft.value = usersStore.buildDraftFromUser(user)
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  function reopenDialog() {
    isDialogOpen.value = true
  }

  function saveUser() {
    if (activeUserId.value) {
      const updated = usersStore.updateUserFromDraft(activeUserId.value, draft.value)
      if (!updated) return false
      closeDialog()
      return true
    }

    const created = usersStore.createUserFromDraft(draft.value)
    if (!created) return false
    closeDialog()
    return true
  }

  function removeUser(userId) {
    usersStore.deleteUser(userId)
    if (activeUserId.value === userId) {
      activeUserId.value = null
      draft.value = usersStore.createEmptyDraft()
      closeDialog()
    }
  }

  function setFacultyId(facultyId) {
    draft.value.faculty_id = facultyId
    const hasDepartment = departmentOptions.value.some(
      (option) => option.value === draft.value.department_id,
    )
    if (!hasDepartment) draft.value.department_id = ''
  }

  function setDepartmentId(departmentId) {
    draft.value.department_id = departmentId
  }

  function createFacultyByName(nameEn) {
    const normalized = nameEn.trim()
    if (!normalized) return
    const existing = faculties.value.find((faculty) => faculty.name_en.toLowerCase() === normalized.toLowerCase())
    if (existing) {
      setFacultyId(existing.id)
      return
    }
    const code = normalized.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || `faculty-${Date.now()}`
    const created = organizationStore.createFacultyFromDraft({
      code,
      name_ar: normalized,
      name_en: normalized,
      color: '#4361ee',
      icon_url: '',
      is_active: true,
    })
    if (!created) return
    const added = faculties.value[faculties.value.length - 1]
    if (added) setFacultyId(added.id)
  }

  function createDepartmentByName(nameEn) {
    const normalized = nameEn.trim()
    if (!normalized || !draft.value.faculty_id) return
    const faculty = faculties.value.find((item) => item.id === draft.value.faculty_id)
    if (!faculty) return
    const existing = faculty.departments.find(
      (department) => department.name_en.toLowerCase() === normalized.toLowerCase(),
    )
    if (existing) {
      setDepartmentId(existing.id)
      return
    }
    const code =
      normalized.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') ||
      `department-${Date.now()}`
    const created = organizationStore.createDepartmentFromDraft(draft.value.faculty_id, {
      code,
      name_ar: normalized,
      name_en: normalized,
      icon_url: '',
      is_active: true,
    })
    if (!created) return
    const latestFaculty = faculties.value.find((item) => item.id === draft.value.faculty_id)
    const added = latestFaculty?.departments[latestFaculty.departments.length - 1]
    if (added) setDepartmentId(added.id)
  }

  return {
    users,
    manageableRoles,
    faculties,
    facultyOptions,
    departmentOptions,
    usersCount,
    activeUsersCount,
    inactiveUsersCount,
    draft,
    isDialogOpen,
    isEditing,
    startCreateUser,
    startEditUser,
    closeDialog,
    reopenDialog,
    saveUser,
    removeUser,
    setFacultyId,
    setDepartmentId,
    createFacultyByName,
    createDepartmentByName,
  }
}
