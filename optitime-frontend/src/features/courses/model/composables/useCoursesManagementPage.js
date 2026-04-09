import { storeToRefs } from 'pinia'
import { computed, onMounted, ref } from 'vue'
import { useCoursesStore } from '@/features/courses/model/stores/courses.store'
import { useInstructorsStore } from '@/features/instructors/model/stores/instructors.store'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'

export function useCoursesManagementPage() {
  const coursesStore = useCoursesStore()
  coursesStore.ensureInitialized()
  const instructorsStore = useInstructorsStore()
  instructorsStore.ensureInitialized()
  const organizationStore = useOrganizationStore()
  onMounted(async () => {
    await organizationStore.ensureInitialized()
  })

  const { courses, coursesCount, coursesWithLabCount, coursesWithoutLabCount } = storeToRefs(coursesStore)
  const { activeCoursesCount, inactiveCoursesCount, currentSemesterId } = storeToRefs(coursesStore)
  const { instructors } = storeToRefs(instructorsStore)
  const { faculties } = storeToRefs(organizationStore)
  const activeCourseId = ref(null)
  const draft = ref(coursesStore.createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeCourseId.value))

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

  const instructorOptions = computed(() =>
    instructors.value
      .filter(
        (instructor) =>
          instructor.is_active &&
          instructor.faculty_id === draft.value.faculty_id &&
          instructor.department_id === draft.value.department_id,
      )
      .map((instructor) => ({
        value: instructor.id,
        label: instructor.name,
      })),
  )

  const prerequisiteCourseOptions = computed(() =>
    courses.value
      .filter((course) => course.id !== activeCourseId.value)
      .map((course) => ({
        value: course.id,
        label: `${course.code} - ${course.name_en}`,
      })),
  )

  function cleanInstructorSelections() {
    const allowed = new Set(instructorOptions.value.map((item) => item.value))
    draft.value.sections = (draft.value.sections || []).map((section) => ({
      ...section,
      instructor_ids: (section.instructor_ids || []).filter((id) => allowed.has(id)),
    }))
  }

  function setFacultyId(facultyId) {
    draft.value.faculty_id = facultyId
    const hasDepartment = departmentOptions.value.some((option) => option.value === draft.value.department_id)
    if (!hasDepartment) draft.value.department_id = ''
    cleanInstructorSelections()
  }

  function setDepartmentId(departmentId) {
    draft.value.department_id = departmentId
    cleanInstructorSelections()
  }

  function addSection(newSection = {}) {
    const nextIndex = (draft.value.sections?.length || 0) + 1
    const nextType =
      newSection.section_type ||
      (draft.value.sections?.some((section) => section.section_type === 'room') ? 'lab' : 'room')
    const fallbackName = `${nextType === 'lab' ? 'Lab Group' : 'Section'} ${nextIndex}`
    draft.value.sections = [
      ...(draft.value.sections || []),
      {
        id: `section-${nextIndex}`,
        section_type: nextType,
        section_name: newSection.section_name?.trim() || fallbackName,
        capacity: newSection.capacity ?? '',
        instructor_ids: Array.isArray(newSection.instructor_ids) ? [...newSection.instructor_ids] : [],
      },
    ]
  }

  function removeSection(sectionId) {
    const sections = (draft.value.sections || []).filter((section) => section.id !== sectionId)
    draft.value.sections = sections.length > 0 ? sections : draft.value.sections
  }

  function updateSection(sectionId, patch) {
    draft.value.sections = (draft.value.sections || []).map((section) =>
      section.id === sectionId ? { ...section, ...patch } : section,
    )
  }

  function startCreateCourse() {
    activeCourseId.value = null
    draft.value = coursesStore.createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditCourse(course) {
    activeCourseId.value = course.id
    draft.value = coursesStore.buildDraftFromCourse(course)
    cleanInstructorSelections()
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  async function saveCourse() {
    if (activeCourseId.value) {
      const updated = await coursesStore.updateCourseFromDraft(activeCourseId.value, draft.value)
      if (!updated) return false
      closeDialog()
      return true
    }
    const created = await coursesStore.createCourseFromDraft(draft.value)
    if (!created) return false
    closeDialog()
    return true
  }

  async function removeCourse(courseId) {
    const deleted = await coursesStore.deleteCourse(courseId)
    if (!deleted) return
    if (activeCourseId.value === courseId) {
      activeCourseId.value = null
      draft.value = coursesStore.createEmptyDraft()
      closeDialog()
    }
  }

  async function toggleCourseActivation(courseId) {
    const updatedOffering = await coursesStore.toggleCourseActivation(courseId)
    return Boolean(updatedOffering)
  }

  return {
    courses,
    faculties,
    instructors,
    coursesCount,
    coursesWithLabCount,
    coursesWithoutLabCount,
    activeCoursesCount,
    inactiveCoursesCount,
    currentSemesterId,
    facultyOptions,
    departmentOptions,
    instructorOptions,
    prerequisiteCourseOptions,
    draft,
    isDialogOpen,
    isEditing,
    startCreateCourse,
    startEditCourse,
    closeDialog,
    saveCourse,
    removeCourse,
    toggleCourseActivation,
    isCourseActiveThisSemester: coursesStore.isCourseActiveThisSemester,
    isCourseToggleInFlight: coursesStore.isCourseToggleInFlight,
    setFacultyId,
    setDepartmentId,
    addSection,
    removeSection,
    updateSection,
  }
}
