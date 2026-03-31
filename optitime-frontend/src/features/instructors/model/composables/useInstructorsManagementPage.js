import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useInstructorsStore } from '@/features/instructors/model/stores/instructors.store'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'
import { useSpecialitiesStore } from '@/features/specialities/model/stores/specialities.store'

export function useInstructorsManagementPage() {
  const instructorsStore = useInstructorsStore()
  instructorsStore.ensureInitialized()
  const organizationStore = useOrganizationStore()
  organizationStore.ensureInitialized()
  const specialitiesStore = useSpecialitiesStore()
  specialitiesStore.ensureInitialized()

  const { instructors, instructorsCount, activeInstructorsCount, inactiveInstructorsCount } =
    storeToRefs(instructorsStore)
  const { faculties } = storeToRefs(organizationStore)
  const { specialities } = storeToRefs(specialitiesStore)
  const activeInstructorId = ref(null)
  const draft = ref(instructorsStore.createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeInstructorId.value))
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
  const specialityOptions = computed(() =>
    specialities.value.map((speciality) => ({
      value: speciality.id,
      label: speciality.name_en,
    })),
  )

  function startCreateInstructor() {
    activeInstructorId.value = null
    draft.value = instructorsStore.createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditInstructor(instructor) {
    activeInstructorId.value = instructor.id
    draft.value = instructorsStore.buildDraftFromInstructor(instructor)
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  function reopenDialog() {
    isDialogOpen.value = true
  }

  async function saveInstructor() {
    if (activeInstructorId.value) {
      const updated = await instructorsStore.updateInstructorFromDraft(activeInstructorId.value, draft.value)
      if (!updated) return false
      closeDialog()
      return true
    }

    const created = await instructorsStore.createInstructorFromDraft(draft.value)
    if (!created) return false
    closeDialog()
    return true
  }

  async function removeInstructor(instructorId) {
    await instructorsStore.deleteInstructor(instructorId)
    if (activeInstructorId.value === instructorId) {
      activeInstructorId.value = null
      draft.value = instructorsStore.createEmptyDraft()
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

  function setSpecialityId(specialityId) {
    draft.value.speciality_id = specialityId
  }

  return {
    instructors,
    faculties,
    specialities,
    facultyOptions,
    departmentOptions,
    specialityOptions,
    instructorsCount,
    activeInstructorsCount,
    inactiveInstructorsCount,
    draft,
    isDialogOpen,
    isEditing,
    startCreateInstructor,
    startEditInstructor,
    closeDialog,
    reopenDialog,
    saveInstructor,
    removeInstructor,
    setFacultyId,
    setDepartmentId,
    setSpecialityId,
  }
}
