import { apiJson } from '@/api/client'

const UUID_RE =
  /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i

/**
 * @param {unknown} id
 * @returns {boolean}
 */
export function isUuid(id) {
  return typeof id === 'string' && UUID_RE.test(id)
}

/**
 * @param {Record<string, unknown>} apiCourse
 * @param {unknown[]} faculties
 */
export function mapApiCourseToRow(apiCourse, faculties = []) {
  const departmentId = apiCourse.department_id != null ? String(apiCourse.department_id) : ''
  let facultyId = ''
  if (departmentId && Array.isArray(faculties)) {
    for (const faculty of faculties) {
      if (faculty?.departments?.some((d) => d.id === departmentId)) {
        facultyId = faculty.id
        break
      }
    }
  }

  const sections = Array.isArray(apiCourse.sections)
    ? apiCourse.sections.map((sec) => ({
        id: sec.id,
        section_name: sec.section_name ?? '',
        section_type: sec.section_type === 'lab' ? 'lab' : 'room',
        capacity: sec.capacity,
        instructor_ids: Array.isArray(sec.instructors)
          ? sec.instructors.map((i) => i.id).filter(Boolean)
          : [],
      }))
    : []

  const cc = apiCourse.course_constraint
  const prereqRows = Array.isArray(apiCourse.course_prerequisites) ? apiCourse.course_prerequisites : []

  return {
    id: apiCourse.id,
    code: apiCourse.code ?? '',
    name_ar: apiCourse.name_ar ?? '',
    name_en: apiCourse.name_en ?? '',
    faculty_id: facultyId,
    department_id: departmentId,
    required_hours: apiCourse.required_hours,
    room_consumed_hours: apiCourse.room_consumed_hours,
    lab_consumed_hours: apiCourse.lab_consumed_hours,
    has_lab_component: Boolean(apiCourse.has_lab_component),
    sections,
    min_student_year_level: cc?.min_student_year_level ?? '',
    max_student_year_level: cc?.max_student_year_level ?? '',
    prerequisite_course_ids: prereqRows.map((row) => row.prerequisite_course_id).filter(Boolean),
  }
}

/**
 * @param {Record<string, unknown>} normalized - output of normalizeDraft in courses store
 */
function courseApiBodyFromNormalized(normalized) {
  const hasLabSection =
    Array.isArray(normalized.sections) && normalized.sections.some((s) => s.section_type === 'lab')
  const rawLab = normalized.lab_consumed_hours
  const safeLab =
    rawLab != null && Number.isInteger(rawLab) && rawLab >= 0 ? rawLab : null
  const hasLab = hasLabSection || safeLab != null
  return {
    department_id: normalized.department_id,
    code: normalized.code,
    name_ar: normalized.name_ar,
    name_en: normalized.name_en,
    required_hours: normalized.required_hours,
    has_lab_component: hasLab,
    room_consumed_hours: normalized.room_consumed_hours,
    lab_consumed_hours: hasLab ? safeLab : null,
    min_student_year_level: normalized.min_student_year_level ?? null,
    max_student_year_level: normalized.max_student_year_level ?? null,
    prerequisite_course_ids: Array.isArray(normalized.prerequisite_course_ids)
      ? normalized.prerequisite_course_ids
      : [],
  }
}

function normalizeOfferingRow(row) {
  if (!row || typeof row !== 'object') return row
  return {
    ...row,
    is_active:
      row.is_active === true ||
      row.is_active === 1 ||
      row.is_active === '1' ||
      row.is_active === 'true',
  }
}

export const coursesService = {
  /**
   * @param {unknown[]} faculties - tree from organization store (for faculty_id on each row)
   */
  async getCourses(faculties = []) {
    const { data } = await apiJson('/admin/courses')
    const rows = Array.isArray(data) ? data : []
    return rows.map((c) => mapApiCourseToRow(c, faculties))
  },

  /**
   * @param {Record<string, unknown>} payload - normalized draft
   * @param {unknown[]} faculties
   */
  async createCourse(payload, faculties = []) {
    const body = courseApiBodyFromNormalized(payload)
    const { data: course } = await apiJson('/admin/courses', {
      method: 'POST',
      json: body,
    })
    const courseId = course?.id
    if (!courseId) {
      throw new Error('Course create did not return id')
    }

    const sections = Array.isArray(payload.sections) ? payload.sections : []
    for (const section of sections) {
      const { data: created } = await apiJson('/admin/sections', {
        method: 'POST',
        json: {
          course_id: courseId,
          section_name: section.section_name,
          section_type: section.section_type,
          capacity: section.capacity,
        },
      })
      await apiJson(`/admin/sections/${created.id}/instructors`, {
        method: 'POST',
        json: { instructor_ids: section.instructor_ids ?? [] },
      })
    }

    const { data: full } = await apiJson(`/admin/courses/${courseId}`)
    return mapApiCourseToRow(full, faculties)
  },

  /**
   * @param {string} courseId
   * @param {Record<string, unknown>} payload - normalized draft
   * @param {unknown[]} faculties
   */
  async updateCourse(courseId, payload, faculties = []) {
    const body = courseApiBodyFromNormalized(payload)
    await apiJson(`/admin/courses/${courseId}`, {
      method: 'PUT',
      json: body,
    })

    const { data: freshCourse } = await apiJson(`/admin/courses/${courseId}`)
    const existingSections = Array.isArray(freshCourse.sections) ? freshCourse.sections : []
    const draftSections = Array.isArray(payload.sections) ? payload.sections : []

    for (const ex of existingSections) {
      const keep = draftSections.some((d) => isUuid(d.id) && d.id === ex.id)
      if (!keep) {
        await apiJson(`/admin/sections/${ex.id}`, { method: 'DELETE' })
      }
    }

    const { data: afterDelete } = await apiJson(`/admin/courses/${courseId}`)
    const remaining = Array.isArray(afterDelete.sections) ? afterDelete.sections : []
    const existingById = new Map(remaining.map((s) => [s.id, s]))

    for (const sec of draftSections) {
      let sectionId
      if (isUuid(sec.id) && existingById.has(sec.id)) {
        await apiJson(`/admin/sections/${sec.id}`, {
          method: 'PUT',
          json: {
            course_id: courseId,
            section_name: sec.section_name,
            section_type: sec.section_type,
            capacity: sec.capacity,
          },
        })
        sectionId = sec.id
      } else {
        const { data: created } = await apiJson('/admin/sections', {
          method: 'POST',
          json: {
            course_id: courseId,
            section_name: sec.section_name,
            section_type: sec.section_type,
            capacity: sec.capacity,
          },
        })
        sectionId = created.id
      }

      await apiJson(`/admin/sections/${sectionId}/instructors`, {
        method: 'POST',
        json: { instructor_ids: sec.instructor_ids ?? [] },
      })
    }

    const { data: full } = await apiJson(`/admin/courses/${courseId}`)
    return mapApiCourseToRow(full, faculties)
  },

  async deleteCourse(courseId) {
    await apiJson(`/admin/courses/${courseId}`, { method: 'DELETE' })
    return true
  },

  async getCurrentSemesterId() {
    const { data } = await apiJson('/admin/semesters')
    const list = Array.isArray(data) ? data : []
    const active = list.find((s) => s.is_active)
    return active?.id ?? list[0]?.id ?? ''
  },

  async getCourseOfferingsBySemester(semesterId) {
    if (!semesterId) return []
    const { data } = await apiJson(`/admin/course-offerings?semester_id=${encodeURIComponent(semesterId)}`)
    const list = Array.isArray(data) ? data : []
    return list.map((item) => normalizeOfferingRow({ ...item }))
  },

  async setCourseOfferingActivation({ semesterId, courseId, isActive }) {
    const { data } = await apiJson('/admin/course-semester', {
      method: 'POST',
      json: {
        course_id: courseId,
        semester_id: semesterId,
        status: isActive ? 'active' : 'inactive',
      },
    })
    return normalizeOfferingRow(data)
  },
}
