import { storeToRefs } from 'pinia'
import { computed, onMounted, ref } from 'vue'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'
import { useStudentsStore } from '@/features/students/model/stores/students.store'

export function useStudentsManagementPage() {
  const studentsStore = useStudentsStore()
  studentsStore.ensureInitialized()
  const organizationStore = useOrganizationStore()
  onMounted(async () => {
    await organizationStore.ensureInitialized()
  })

  const { students, studentsCount, activeStudentsCount, inactiveStudentsCount } = storeToRefs(studentsStore)
  const { faculties } = storeToRefs(organizationStore)
  const activeStudentId = ref(null)
  const draft = ref(studentsStore.createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeStudentId.value))
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

  function startCreateStudent() {
    activeStudentId.value = null
    draft.value = studentsStore.createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditStudent(student) {
    activeStudentId.value = student.id
    draft.value = studentsStore.buildDraftFromStudent(student)
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  async function saveStudent() {
    if (activeStudentId.value) {
      const updated = await studentsStore.updateStudentFromDraft(activeStudentId.value, draft.value)
      if (!updated) return false
      closeDialog()
      return true
    }

    const created = await studentsStore.createStudentFromDraft(draft.value)
    if (!created) return false
    closeDialog()
    return true
  }

  async function removeStudent(studentId) {
    const deleted = await studentsStore.deleteStudent(studentId)
    if (!deleted) return
    if (activeStudentId.value === studentId) {
      activeStudentId.value = null
      draft.value = studentsStore.createEmptyDraft()
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

  return {
    students,
    faculties,
    facultyOptions,
    departmentOptions,
    studentsCount,
    activeStudentsCount,
    inactiveStudentsCount,
    draft,
    isDialogOpen,
    isEditing,
    startCreateStudent,
    startEditStudent,
    closeDialog,
    saveStudent,
    removeStudent,
    setFacultyId,
    setDepartmentId,
  }
}
