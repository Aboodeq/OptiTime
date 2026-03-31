import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'

export function useOrganizationPage() {
  const organizationStore = useOrganizationStore()
  organizationStore.ensureInitialized()

  const {
    faculties,
    facultiesCount,
    departmentsCount,
    activeFacultiesCount,
    activeDepartmentsCount,
  } = storeToRefs(organizationStore)

  const facultyDialogOpen = ref(false)
  const activeFacultyId = ref(null)
  const facultyDraft = ref(organizationStore.createEmptyFacultyDraft())

  const departmentDialogOpen = ref(false)
  const activeDepartmentId = ref(null)
  const departmentDraft = ref(organizationStore.createEmptyDepartmentDraft())
  const departmentFacultyId = ref(null)

  const editingFaculty = computed(() => Boolean(activeFacultyId.value))
  const editingDepartment = computed(() => Boolean(activeDepartmentId.value))

  function startCreateFaculty() {
    activeFacultyId.value = null
    facultyDraft.value = organizationStore.createEmptyFacultyDraft()
    facultyDialogOpen.value = true
  }

  function startEditFaculty(faculty) {
    activeFacultyId.value = faculty.id
    facultyDraft.value = organizationStore.buildFacultyDraftFromItem(faculty)
    facultyDialogOpen.value = true
  }

  function closeFacultyDialog() {
    facultyDialogOpen.value = false
  }

  function saveFaculty() {
    if (activeFacultyId.value) {
      const updated = organizationStore.updateFacultyFromDraft(
        activeFacultyId.value,
        facultyDraft.value,
      )
      if (!updated) return false
      closeFacultyDialog()
      return true
    }

    const created = organizationStore.createFacultyFromDraft(facultyDraft.value)
    if (!created) return false
    closeFacultyDialog()
    return true
  }

  function removeFaculty(facultyId) {
    organizationStore.deleteFaculty(facultyId)
  }

  function startCreateDepartment(facultyId) {
    activeDepartmentId.value = null
    departmentFacultyId.value = facultyId
    departmentDraft.value = organizationStore.createEmptyDepartmentDraft()
    departmentDialogOpen.value = true
  }

  function startEditDepartment(facultyId, department) {
    activeDepartmentId.value = department.id
    departmentFacultyId.value = facultyId
    departmentDraft.value = organizationStore.buildDepartmentDraftFromItem(department)
    departmentDialogOpen.value = true
  }

  function closeDepartmentDialog() {
    departmentDialogOpen.value = false
  }

  function saveDepartment() {
    if (!departmentFacultyId.value) return false

    if (activeDepartmentId.value) {
      const updated = organizationStore.updateDepartmentFromDraft(
        departmentFacultyId.value,
        activeDepartmentId.value,
        departmentDraft.value,
      )
      if (!updated) return false
      closeDepartmentDialog()
      return true
    }

    const created = organizationStore.createDepartmentFromDraft(
      departmentFacultyId.value,
      departmentDraft.value,
    )
    if (!created) return false
    closeDepartmentDialog()
    return true
  }

  function removeDepartment(facultyId, departmentId) {
    organizationStore.deleteDepartment(facultyId, departmentId)
  }

  return {
    faculties,
    facultiesCount,
    departmentsCount,
    activeFacultiesCount,
    activeDepartmentsCount,
    facultyDialogOpen,
    editingFaculty,
    facultyDraft,
    departmentDialogOpen,
    editingDepartment,
    departmentDraft,
    startCreateFaculty,
    startEditFaculty,
    closeFacultyDialog,
    saveFaculty,
    removeFaculty,
    startCreateDepartment,
    startEditDepartment,
    closeDepartmentDialog,
    saveDepartment,
    removeDepartment,
  }
}
