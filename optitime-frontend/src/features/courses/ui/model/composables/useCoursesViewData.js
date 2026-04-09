import { computed } from 'vue'

export function useCoursesViewData({
  courses,
  faculties,
  instructors,
  facultyOptions,
  coursesCount,
  coursesWithLabCount,
  coursesWithoutLabCount,
  activeCoursesCount,
  inactiveCoursesCount,
  isCourseActiveThisSemester,
  isCourseToggleInFlight,
  search,
  t,
}) {
  const facultyMap = computed(() => {
    const map = new Map()
    for (const option of facultyOptions.value) map.set(option.value, option.label)
    return map
  })

  const departmentMap = computed(() => {
    const map = new Map()
    for (const faculty of faculties.value) {
      for (const department of faculty.departments || []) map.set(department.id, department.name_en)
    }
    return map
  })

  const instructorMap = computed(() => {
    const map = new Map()
    for (const instructor of instructors.value) map.set(instructor.id, instructor.name)
    return map
  })

  function mapInstructorNames(ids) {
    return ids.map((id) => instructorMap.value.get(id)).filter(Boolean)
  }

  function collectInstructorsBySection(sections = []) {
    const instructorRows = new Map()
    sections.forEach((section, index) => {
      const sectionName =
        section.section_name || `${section.section_type === 'lab' ? 'Lab Group' : 'Section'} ${index + 1}`
      const sectionItem = {
        section_type: section.section_type,
        section_name: sectionName,
        capacity: section.capacity ?? null,
      }
      for (const name of mapInstructorNames(section.instructor_ids || [])) {
        if (!instructorRows.has(name)) instructorRows.set(name, [])
        instructorRows.get(name).push(sectionItem)
      }
    })
    return Array.from(instructorRows.entries()).map(([instructor_name, sectionsForInstructor]) => ({
      instructor_name,
      sections: sectionsForInstructor,
    }))
  }

  const tableRows = computed(() =>
    courses.value.map((course) => ({
      ...course,
      faculty_name_en: facultyMap.value.get(course.faculty_id) || '-',
      department_name_en: departmentMap.value.get(course.department_id) || '-',
      sections: Array.isArray(course.sections) ? course.sections : [],
      section_instructor_rows: collectInstructorsBySection(course.sections),
      has_lab_component: (course.sections || []).some((section) => section.section_type === 'lab'),
      is_active_current_semester: isCourseActiveThisSemester(course.id),
      is_toggling_activation: isCourseToggleInFlight(course.id),
      room_consumed_hours: course.room_consumed_hours ?? null,
      lab_consumed_hours: (course.sections || []).some((section) => section.section_type === 'lab')
        ? course.lab_consumed_hours
        : null,
      min_student_year_level: course.min_student_year_level ?? null,
      max_student_year_level: course.max_student_year_level ?? null,
      prerequisite_course_names: (course.prerequisite_course_ids || [])
        .map((id) => courses.value.find((item) => item.id === id))
        .filter(Boolean)
        .map((item) => `${item.code} - ${item.name_en}`),
    })),
  )

  const filteredCourses = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return tableRows.value
    return tableRows.value.filter(
      (course) =>
        (course.code || '').toLowerCase().includes(q) ||
        (course.name_ar || '').toLowerCase().includes(q) ||
        (course.name_en || '').toLowerCase().includes(q) ||
        (course.faculty_name_en || '').toLowerCase().includes(q) ||
        (course.department_name_en || '').toLowerCase().includes(q) ||
        String(course.required_hours).includes(q),
    )
  })

  const statsCards = computed(() => [
    {
      id: 'courses',
      value: coursesCount.value,
      label: t('pages.coursesManagement.stats.totalCourses'),
      icon: 'bi bi-journals',
      iconColor: '#8b5cf6',
    },
    {
      id: 'active-current-semester',
      value: activeCoursesCount.value,
      label: t('pages.coursesManagement.stats.activeCurrentSemesterCourses'),
      icon: 'bi bi-check2-circle',
      iconColor: '#22c55e',
    },
    {
      id: 'inactive-current-semester',
      value: inactiveCoursesCount.value,
      label: t('pages.coursesManagement.stats.inactiveCurrentSemesterCourses'),
      icon: 'bi bi-slash-circle',
      iconColor: '#ef4444',
    },
    {
      id: 'room-only',
      value: coursesWithoutLabCount.value,
      label: t('pages.coursesManagement.stats.roomOnlyCourses'),
      icon: 'bi bi-door-open',
      iconColor: '#0ea5e9',
    },
    {
      id: 'room-lab',
      value: coursesWithLabCount.value,
      label: t('pages.coursesManagement.stats.roomLabCourses'),
      icon: 'bi bi-pc-display-horizontal',
      iconColor: '#f59e0b',
    },
  ])

  return { filteredCourses, tableRows, statsCards }
}
