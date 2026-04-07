import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { coursesService } from '@/features/courses/api/courses.service'

function createEmptyDraft() {
  return {
    code: '',
    name_ar: '',
    name_en: '',
    faculty_id: '',
    department_id: '',
    required_hours: '',
    room_consumed_hours: '',
    lab_consumed_hours: '',
    sections: [
      {
        id: 'section-1',
        section_type: 'room',
        section_name: 'Section 1',
        capacity: '',
        instructor_ids: [],
      },
    ],
    min_student_year_level: '',
    max_student_year_level: '',
    prerequisite_course_ids: [],
  }
}

function normalizeIds(values) {
  if (!Array.isArray(values)) return []
  return [...new Set(values.filter((item) => typeof item === 'string' && item.trim()))]
}

function normalizeSectionType(value) {
  return value === 'lab' ? 'lab' : 'room'
}

function normalizeSections(values) {
  if (!Array.isArray(values)) return []
  return values
    .map((section, index) => {
      const rawCapacity = section?.capacity
      const hasCapacity = String(rawCapacity).trim() !== ''
      const capacity = hasCapacity ? Number(rawCapacity) : NaN
      const instructorIds = normalizeIds(section?.instructor_ids)
      const sectionType = normalizeSectionType(section?.section_type)
      const fallbackNamePrefix = sectionType === 'lab' ? 'Lab Group' : 'Section'
      return {
        id: section?.id?.trim() || `section-${index + 1}`,
        section_name: section?.section_name?.trim() || `${fallbackNamePrefix} ${index + 1}`,
        section_type: sectionType,
        capacity,
        hasCapacity,
        instructor_ids: instructorIds,
      }
    })
    .filter((section) => section.hasCapacity || section.instructor_ids.length > 0)
}

export const useCoursesStore = defineStore('courses', () => {
  const courses = ref([])
  const initialized = ref(false)

  const coursesCount = computed(() => courses.value.length)
  const coursesWithLabCount = computed(
    () => courses.value.filter((course) => course.sections.some((section) => section.section_type === 'lab')).length,
  )
  const coursesWithoutLabCount = computed(() => coursesCount.value - coursesWithLabCount.value)

  async function ensureInitialized() {
    if (initialized.value) return
    courses.value = await coursesService.getCourses()
    initialized.value = true
  }

  function buildDraftFromCourse(course) {
    return {
      code: course.code ?? '',
      name_ar: course.name_ar ?? '',
      name_en: course.name_en ?? '',
      faculty_id: course.faculty_id ?? '',
      department_id: course.department_id ?? '',
      required_hours: course.required_hours ?? '',
      room_consumed_hours: course.room_consumed_hours ?? '',
      lab_consumed_hours: course.lab_consumed_hours ?? '',
      sections:
        Array.isArray(course.sections) && course.sections.length > 0
          ? course.sections.map((section, index) => ({
              id: section.id ?? `section-${index + 1}`,
              section_type: normalizeSectionType(section.section_type),
              section_name:
                section.section_name ?? `${section.section_type === 'lab' ? 'Lab Group' : 'Section'} ${index + 1}`,
              capacity: section.capacity ?? '',
              instructor_ids: normalizeIds(section.instructor_ids),
            }))
          : [
              {
                id: 'section-1',
                section_type: 'room',
                section_name: 'Section 1',
                capacity: '',
                instructor_ids: [],
              },
            ],
      min_student_year_level: course.min_student_year_level ?? '',
      max_student_year_level: course.max_student_year_level ?? '',
      prerequisite_course_ids: normalizeIds(course.prerequisite_course_ids),
    }
  }

  function normalizeDraft(draft) {
    const code = draft.code?.trim().toUpperCase() || ''
    const nameAr = draft.name_ar?.trim() || ''
    const nameEn = draft.name_en?.trim() || ''
    const facultyId = draft.faculty_id?.trim() || ''
    const departmentId = draft.department_id?.trim() || ''
    const requiredHours = Number(draft.required_hours)
    const roomConsumedHoursRaw = draft.room_consumed_hours
    const labConsumedHoursRaw = draft.lab_consumed_hours
    const hasRoomConsumed = String(roomConsumedHoursRaw).trim() !== ''
    const hasLabConsumed = String(labConsumedHoursRaw).trim() !== ''
    const roomConsumedHours = hasRoomConsumed ? Number(roomConsumedHoursRaw) : null
    const labConsumedHours = hasLabConsumed ? Number(labConsumedHoursRaw) : null
    const sections = normalizeSections(draft.sections)
    const minStudentYearLevelRaw = draft.min_student_year_level
    const maxStudentYearLevelRaw = draft.max_student_year_level
    const hasMinLevel = String(minStudentYearLevelRaw).trim() !== ''
    const hasMaxLevel = String(maxStudentYearLevelRaw).trim() !== ''
    const minStudentYearLevel = hasMinLevel ? Number(minStudentYearLevelRaw) : null
    const maxStudentYearLevel = hasMaxLevel ? Number(maxStudentYearLevelRaw) : null
    const prerequisiteCourseIds = normalizeIds(draft.prerequisite_course_ids)

    if (!code || !nameAr || !nameEn || !facultyId || !departmentId) return null
    if (!Number.isInteger(requiredHours) || requiredHours <= 0) return null
    if (hasRoomConsumed && (!Number.isInteger(roomConsumedHours) || roomConsumedHours < 0)) return null
    if (hasLabConsumed && (!Number.isInteger(labConsumedHours) || labConsumedHours < 0)) return null
    if (sections.length === 0) return null
    if (
      sections.some(
        (section) =>
          !section.section_name ||
          !Number.isInteger(section.capacity) ||
          section.capacity <= 0 ||
          section.instructor_ids.length === 0,
      )
    ) {
      return null
    }
    if (hasMinLevel && (!Number.isInteger(minStudentYearLevel) || minStudentYearLevel < 1)) return null
    if (hasMaxLevel && (!Number.isInteger(maxStudentYearLevel) || maxStudentYearLevel < 1)) return null
    if (
      hasMinLevel &&
      hasMaxLevel &&
      minStudentYearLevel !== null &&
      maxStudentYearLevel !== null &&
      minStudentYearLevel > maxStudentYearLevel
    ) {
      return null
    }

    return {
      code,
      name_ar: nameAr,
      name_en: nameEn,
      faculty_id: facultyId,
      department_id: departmentId,
      required_hours: requiredHours,
      room_consumed_hours: roomConsumedHours,
      lab_consumed_hours: sections.some((section) => section.section_type === 'lab') ? labConsumedHours : null,
      sections: sections.map((section) => ({
        id: section.id,
        section_name: section.section_name,
        section_type: section.section_type,
        capacity: section.capacity,
        instructor_ids: section.instructor_ids,
      })),
      min_student_year_level: minStudentYearLevel,
      max_student_year_level: maxStudentYearLevel,
      prerequisite_course_ids: prerequisiteCourseIds,
    }
  }

  async function createCourseFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const created = await coursesService.createCourse(normalized)
    courses.value = [...courses.value, created]
    return true
  }

  async function updateCourseFromDraft(courseId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const updated = await coursesService.updateCourse(courseId, normalized)
    if (!updated) return false
    courses.value = courses.value.map((item) => (item.id === courseId ? updated : item))
    return true
  }

  async function deleteCourse(courseId) {
    const deleted = await coursesService.deleteCourse(courseId)
    if (!deleted) return false
    courses.value = courses.value.filter((item) => item.id !== courseId)
    return true
  }

  return {
    courses,
    coursesCount,
    coursesWithLabCount,
    coursesWithoutLabCount,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromCourse,
    createCourseFromDraft,
    updateCourseFromDraft,
    deleteCourse,
  }
})
