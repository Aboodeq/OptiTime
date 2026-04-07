const COURSES_SEED = [
  {
    id: 'course-1',
    code: 'SWE-301',
    name_ar: 'تحليل وتصميم النظم',
    name_en: 'Systems Analysis and Design',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    required_hours: 3,
    room_consumed_hours: 2,
    lab_consumed_hours: 3,
    sections: [
      {
        id: 'course-1-sec-1',
        section_name: 'Section A',
        section_type: 'room',
        capacity: 40,
        instructor_ids: ['instructor-1'],
      },
      {
        id: 'course-1-sec-2',
        section_name: 'Lab Group A',
        section_type: 'lab',
        capacity: 25,
        instructor_ids: ['instructor-3'],
      },
    ],
    min_student_year_level: 3,
    max_student_year_level: 5,
    prerequisite_course_ids: [],
  },
  {
    id: 'course-2',
    code: 'FIN-210',
    name_ar: 'مبادئ التمويل',
    name_en: 'Principles of Finance',
    faculty_id: 'faculty-business',
    department_id: 'dept-finance',
    required_hours: 2,
    room_consumed_hours: 2,
    lab_consumed_hours: null,
    sections: [
      {
        id: 'course-2-sec-1',
        section_name: 'Section A',
        section_type: 'room',
        capacity: 60,
        instructor_ids: ['instructor-2'],
      },
    ],
    min_student_year_level: 2,
    max_student_year_level: 4,
    prerequisite_course_ids: [],
  },
]

let coursesDb = COURSES_SEED.map((item) => ({ ...item }))
const CURRENT_SEMESTER_ID = 'semester-current'
let courseOfferingsDb = [
  { id: 'offering-1', course_id: 'course-1', semester_id: CURRENT_SEMESTER_ID, is_active: true },
  { id: 'offering-2', course_id: 'course-2', semester_id: CURRENT_SEMESTER_ID, is_active: false },
]

function cloneCourse(course) {
  return {
    ...course,
    sections: Array.isArray(course.sections)
      ? course.sections.map((section) => ({
          ...section,
          instructor_ids: Array.isArray(section.instructor_ids) ? [...section.instructor_ids] : [],
        }))
      : [],
    prerequisite_course_ids: Array.isArray(course.prerequisite_course_ids)
      ? [...course.prerequisite_course_ids]
      : [],
  }
}

export const coursesService = {
  async getCourses() {
    return coursesDb.map(cloneCourse)
  },

  async createCourse(payload) {
    const course = {
      ...payload,
      id: `course-${Date.now()}`,
    }
    coursesDb = [...coursesDb, course]
    return cloneCourse(course)
  },

  async updateCourse(courseId, payload) {
    let updatedCourse = null
    coursesDb = coursesDb.map((item) => {
      if (item.id !== courseId) return item
      updatedCourse = {
        ...item,
        ...payload,
      }
      return updatedCourse
    })
    return updatedCourse ? cloneCourse(updatedCourse) : null
  },

  async deleteCourse(courseId) {
    const before = coursesDb.length
    coursesDb = coursesDb.filter((item) => item.id !== courseId)
    courseOfferingsDb = courseOfferingsDb.filter((item) => item.course_id !== courseId)
    return coursesDb.length < before
  },

  async getCurrentSemesterId() {
    return CURRENT_SEMESTER_ID
  },

  async getCourseOfferingsBySemester(semesterId) {
    return courseOfferingsDb
      .filter((item) => item.semester_id === semesterId)
      .map((item) => ({ ...item }))
  },

  async setCourseOfferingActivation({ semesterId, courseId, isActive }) {
    const normalizedActive = Boolean(isActive)
    let updated = null
    courseOfferingsDb = courseOfferingsDb.map((item) => {
      if (item.semester_id !== semesterId || item.course_id !== courseId) return item
      updated = { ...item, is_active: normalizedActive }
      return updated
    })
    if (!updated) {
      updated = {
        id: `offering-${Date.now()}`,
        course_id: courseId,
        semester_id: semesterId,
        is_active: normalizedActive,
      }
      courseOfferingsDb = [...courseOfferingsDb, updated]
    }
    return { ...updated }
  },
}
