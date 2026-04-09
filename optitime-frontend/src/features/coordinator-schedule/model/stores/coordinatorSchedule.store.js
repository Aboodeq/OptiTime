import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import { useConstraintsStore } from '@/features/constraints/model/stores/constraints.store'
import { coordinatorScheduleService } from '@/features/coordinator-schedule/api/coordinatorSchedule.service'
import { useCoursesStore } from '@/features/courses/model/stores/courses.store'
import { useInstructorsStore } from '@/features/instructors/model/stores/instructors.store'
import { useRoomsStore } from '@/features/rooms/model/stores/rooms.store'
import { useSemestersStore } from '@/features/semesters/model/stores/semesters.store'
import { useStudentsStore } from '@/features/students/model/stores/students.store'

const VALID_DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat']
const PRIORITY_STUDENT_IDS = new Set(['student-4', 'student-6', 'student-8'])

function ensureArray(value) {
  return Array.isArray(value) ? value : []
}

function toMinutes(time) {
  if (typeof time !== 'string' || !/^\d{2}:\d{2}$/.test(time)) return NaN
  const [h, m] = time.split(':').map(Number)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return NaN
  return h * 60 + m
}

function toTime(minutes) {
  const safe = Math.max(0, minutes)
  const h = String(Math.floor(safe / 60)).padStart(2, '0')
  const m = String(safe % 60).padStart(2, '0')
  return `${h}:${m}`
}

function overlaps(startA, endA, startB, endB) {
  return startA < endB && endA > startB
}

function cloneDraft(draft) {
  return {
    semester_id: draft?.semester_id ?? '',
    sessions: ensureArray(draft?.sessions).map((item) => ({ ...item })),
  }
}

function buildCourseEnrollmentPlan({ course, students }) {
  const prioritizedStudents = [...students].sort((a, b) => {
    const aPriority = PRIORITY_STUDENT_IDS.has(a.id) ? 1 : 0
    const bPriority = PRIORITY_STUDENT_IDS.has(b.id) ? 1 : 0
    return bPriority - aPriority
  })

  const sections = Array.isArray(course.sections)
    ? course.sections.map((item) => ({
        section_id: item.id,
        capacity: Number(item.capacity) || 0,
        assigned_student_ids: [],
      }))
    : []
  if (sections.length === 0) {
    return {
      course_id: course.id,
      total_eligible: students.length,
      total_assigned: 0,
      overflow_count: students.length,
      sections: [],
    }
  }

  const primarySection = sections[0]
  for (const student of prioritizedStudents) {
    if (
      PRIORITY_STUDENT_IDS.has(student.id) &&
      primarySection &&
      primarySection.assigned_student_ids.length < primarySection.capacity
    ) {
      primarySection.assigned_student_ids.push(student.id)
      continue
    }

    let assigned = false
    for (let attempts = 0; attempts < sections.length; attempts += 1) {
      const index = attempts % sections.length
      const section = sections[index]
      if (section.assigned_student_ids.length < section.capacity) {
        section.assigned_student_ids.push(student.id)
        assigned = true
        break
      }
    }
    if (!assigned) {
      // no free capacity left in any section
      continue
    }
  }

  const totalAssigned = sections.reduce((sum, section) => sum + section.assigned_student_ids.length, 0)
  return {
    course_id: course.id,
    total_eligible: students.length,
    total_assigned: totalAssigned,
    overflow_count: Math.max(0, students.length - totalAssigned),
    sections,
  }
}

function normalizeText(value) {
  return `${value || ''}`.trim().toLowerCase()
}

function resolveInstructorIdForUser(instructors, user) {
  const userId = `${user?.id || ''}`.trim()
  const userEmail = normalizeText(user?.email)
  const userName = normalizeText(user?.name)
  const match = instructors.find((item) => {
    return (
      item.id === userId ||
      item.user_id === userId ||
      normalizeText(item.email) === userEmail ||
      normalizeText(item.name) === userName
    )
  })
  return match?.id ?? ''
}

function resolveStudentIdForUser(students, user) {
  const userId = `${user?.id || ''}`.trim()
  const userEmail = normalizeText(user?.email)
  const userName = normalizeText(user?.name)
  const match = students.find((item) => {
    return (
      item.id === userId ||
      item.user_id === userId ||
      normalizeText(item.email) === userEmail ||
      normalizeText(item.name) === userName
    )
  })
  return match?.id ?? ''
}

export const useCoordinatorScheduleStore = defineStore('coordinatorSchedule', () => {
  const initialized = ref(false)
  const loading = ref(false)
  const saving = ref(false)
  const generating = ref(false)
  const editableDraft = ref({ semester_id: '', sessions: [] })
  const generatedDraft = ref(null)
  const validationErrors = ref([])
  const conflictSessionIds = ref([])

  const constraintsStore = useConstraintsStore()
  const coursesStore = useCoursesStore()
  const instructorsStore = useInstructorsStore()
  const roomsStore = useRoomsStore()
  const semestersStore = useSemestersStore()
  const studentsStore = useStudentsStore()

  const activeSemester = computed(
    () => semestersStore.semesters.find((item) => item.is_active) ?? null,
  )

  const enabledStudyDayValues = computed(() => {
    const days = constraintsStore.activeSettings?.study_days ?? []
    return days.filter((item) => item.enabled).map((item) => item.value).filter((item) => VALID_DAYS.includes(item))
  })

  const timeSlots = computed(() => {
    const settings = constraintsStore.activeSettings
    const dayStartMinutes = toMinutes(settings?.day_start || '08:00')
    const dayEndMinutes = toMinutes(settings?.day_end || '16:00')
    const slotMinutes = Math.max(1, Number(settings?.slot_minutes) || 60)
    const gapMinutes = Math.max(0, Number(settings?.gap_minutes) || 0)
    const breaks = (settings?.break_times ?? []).filter((item) => item?.enabled)

    if (!Number.isFinite(dayStartMinutes) || !Number.isFinite(dayEndMinutes) || dayEndMinutes <= dayStartMinutes) {
      return []
    }

    const cycle = slotMinutes + gapMinutes
    const slots = []
    for (let start = dayStartMinutes; start + slotMinutes <= dayEndMinutes; start += cycle) {
      const end = start + slotMinutes
      const blocked = breaks.some((item) =>
        overlaps(start, end, toMinutes(item.start), toMinutes(item.end)),
      )
      slots.push({
        start: toTime(start),
        end: toTime(end),
        blocked,
      })
    }
    return slots
  })

  const blockedSlotStarts = computed(() =>
    timeSlots.value.filter((item) => item.blocked).map((item) => item.start),
  )

  const unblockedSlotSet = computed(() => {
    const entries = timeSlots.value
      .filter((item) => !item.blocked)
      .map((item) => `${item.start}|${item.end}`)
    return new Set(entries)
  })

  const activeCourseIds = computed(
    () =>
      new Set(
        coursesStore.semesterOfferings
          .filter((item) => item.semester_id === activeSemester.value?.id && item.is_active)
          .map((item) => item.course_id),
      ),
  )

  const enrollmentPlan = computed(() => {
    const activeCourses = coursesStore.courses.filter((item) => activeCourseIds.value.has(item.id))
    const activeStudents = studentsStore.students.filter(
      (item) => item.is_active && item.study_status === 'regular',
    )

    return activeCourses.map((course) => {
      const minLevel = Number(course.min_student_year_level)
      const maxLevel = Number(course.max_student_year_level)
      const hasMin = Number.isFinite(minLevel)
      const hasMax = Number.isFinite(maxLevel)
      const eligible = activeStudents.filter((student) => {
        if (student.faculty_id !== course.faculty_id) return false
        if (student.department_id !== course.department_id) return false
        if (hasMin && student.year_level < minLevel) return false
        if (hasMax && student.year_level > maxLevel) return false
        return true
      })
      return buildCourseEnrollmentPlan({ course, students: eligible })
    })
  })

  function analyzeDraft(draft) {
    const errors = []
    const conflicted = new Set()
    const daySet = new Set(enabledStudyDayValues.value)
    const roomIndex = new Map()
    const instructorIndex = new Map()

    const roomById = new Map(roomsStore.rooms.map((item) => [item.id, item]))
    const instructorById = new Map(instructorsStore.instructors.map((item) => [item.id, item]))
    const courseById = new Map(coursesStore.courses.map((item) => [item.id, item]))
    for (const session of ensureArray(draft?.sessions)) {
      const sessionId = `${session?.id || ''}`.trim()
      const day = `${session?.day || ''}`.trim()
      const start = `${session?.start || ''}`.trim()
      const end = `${session?.end || ''}`.trim()
      const courseId = `${session?.course_id || ''}`.trim()
      const sectionId = `${session?.section_id || ''}`.trim()
      const instructorId = `${session?.instructor_id || ''}`.trim()
      const roomId = `${session?.room_id || ''}`.trim()
      const startMinutes = toMinutes(start)
      const endMinutes = toMinutes(end)

      if (!day || !daySet.has(day)) {
        errors.push('invalid-day')
        if (sessionId) conflicted.add(sessionId)
        continue
      }
      if (!Number.isFinite(startMinutes) || !Number.isFinite(endMinutes) || startMinutes >= endMinutes) {
        errors.push('invalid-time-range')
        if (sessionId) conflicted.add(sessionId)
        continue
      }
      if (!unblockedSlotSet.value.has(`${start}|${end}`)) {
        errors.push('invalid-slot')
        if (sessionId) conflicted.add(sessionId)
        continue
      }
      if (!courseId || !sectionId || !instructorId || !roomId) {
        errors.push('missing-relations')
        if (sessionId) conflicted.add(sessionId)
        continue
      }

      const roomEntity = roomById.get(roomId)
      if (!roomEntity || roomEntity.status !== 'available') {
        errors.push('room-unavailable')
        if (sessionId) conflicted.add(sessionId)
        continue
      }
      const instructorEntity = instructorById.get(instructorId)
      if (!instructorEntity || !instructorEntity.is_active) {
        errors.push('instructor-unavailable')
        if (sessionId) conflicted.add(sessionId)
        continue
      }
      const courseEntity = courseById.get(courseId)
      if (!courseEntity || !activeCourseIds.value.has(courseId)) {
        errors.push('course-inactive')
        if (sessionId) conflicted.add(sessionId)
        continue
      }

      const section = (courseEntity.sections ?? []).find((item) => item.id === sectionId)
      if (!section) {
        errors.push('section-missing')
        if (sessionId) conflicted.add(sessionId)
        continue
      }
      if (!Array.isArray(section.instructor_ids) || !section.instructor_ids.includes(instructorId)) {
        errors.push('section-instructor-mismatch')
        if (sessionId) conflicted.add(sessionId)
        continue
      }
      const requiresLab = section.section_type === 'lab'
      if (requiresLab && roomEntity.type !== 'lab') {
        errors.push('room-type-mismatch')
        if (sessionId) conflicted.add(sessionId)
        continue
      }

      const roomKey = `${day}|${roomId}`
      const instructorKey = `${day}|${instructorId}`

      if (!roomIndex.has(roomKey)) roomIndex.set(roomKey, [])
      if (!instructorIndex.has(instructorKey)) instructorIndex.set(instructorKey, [])

      roomIndex.get(roomKey).push({ start: startMinutes, end: endMinutes, id: sessionId })
      instructorIndex.get(instructorKey).push({ start: startMinutes, end: endMinutes, id: sessionId })
    }

    for (const intervals of roomIndex.values()) {
      const sorted = [...intervals].sort((a, b) => a.start - b.start)
      for (let index = 1; index < sorted.length; index += 1) {
        if (sorted[index].start < sorted[index - 1].end) {
          errors.push('room-overlap')
          if (sorted[index].id) conflicted.add(sorted[index].id)
          if (sorted[index - 1].id) conflicted.add(sorted[index - 1].id)
        }
      }
    }
    for (const intervals of instructorIndex.values()) {
      const sorted = [...intervals].sort((a, b) => a.start - b.start)
      for (let index = 1; index < sorted.length; index += 1) {
        if (sorted[index].start < sorted[index - 1].end) {
          errors.push('instructor-overlap')
          if (sorted[index].id) conflicted.add(sorted[index].id)
          if (sorted[index - 1].id) conflicted.add(sorted[index - 1].id)
        }
      }
    }

    for (const coursePlan of enrollmentPlan.value) {
      if (coursePlan.overflow_count > 0) {
        errors.push(`capacity-exceeded-${coursePlan.course_id}`)
        const courseSessions = ensureArray(draft?.sessions).filter(
          (item) => item.course_id === coursePlan.course_id,
        )
        courseSessions.forEach((item) => {
          if (item?.id) conflicted.add(item.id)
        })
      }
    }

    const studentScheduleMap = new Map()
    const sessions = ensureArray(draft?.sessions)
    for (const session of sessions) {
      const courseId = `${session?.course_id || ''}`.trim()
      const sectionId = `${session?.section_id || ''}`.trim()
      const day = `${session?.day || ''}`.trim()
      const startMinutes = toMinutes(`${session?.start || ''}`.trim())
      const endMinutes = toMinutes(`${session?.end || ''}`.trim())
      const sessionId = `${session?.id || ''}`.trim()
      if (!courseId || !sectionId || !day || !Number.isFinite(startMinutes) || !Number.isFinite(endMinutes)) {
        continue
      }

      const coursePlan = enrollmentPlan.value.find((item) => item.course_id === courseId)
      const sectionPlan = coursePlan?.sections?.find((item) => item.section_id === sectionId)
      const studentIds = Array.isArray(sectionPlan?.assigned_student_ids)
        ? sectionPlan.assigned_student_ids
        : []

      for (const studentId of studentIds) {
        const key = `${studentId}|${day}`
        if (!studentScheduleMap.has(key)) studentScheduleMap.set(key, [])
        studentScheduleMap.get(key).push({ start: startMinutes, end: endMinutes, id: sessionId })
      }
    }

    for (const intervals of studentScheduleMap.values()) {
      const sorted = [...intervals].sort((a, b) => a.start - b.start)
      for (let index = 1; index < sorted.length; index += 1) {
        if (sorted[index].start < sorted[index - 1].end) {
          errors.push('student-overlap')
          if (sorted[index].id) conflicted.add(sorted[index].id)
          if (sorted[index - 1].id) conflicted.add(sorted[index - 1].id)
        }
      }
    }

    return {
      errors,
      conflictIds: [...conflicted],
    }
  }

  function validateDraft(draft) {
    const analysis = analyzeDraft(draft)
    validationErrors.value = analysis.errors
    conflictSessionIds.value = analysis.conflictIds
    return analysis.errors.length === 0
  }

  function getLectureStudents(session) {
    const courseId = `${session?.course_id || ''}`.trim()
    const sectionId = `${session?.section_id || ''}`.trim()
    if (!courseId || !sectionId) return []

    const plan = enrollmentPlan.value.find((item) => item.course_id === courseId)
    const sectionPlan = plan?.sections?.find((item) => item.section_id === sectionId)
    const studentsById = new Map(studentsStore.students.map((item) => [item.id, item]))
    const ids = Array.isArray(sectionPlan?.assigned_student_ids) ? sectionPlan.assigned_student_ids : []
    return ids
      .map((id) => studentsById.get(id))
      .filter(Boolean)
      .map((student) => ({
        id: student.id,
        name: student.name,
        university_number: student.university_number,
        year_level: student.year_level,
        study_status: student.study_status,
      }))
  }

  function getInstructorSessionsForUser(user) {
    const instructorId = resolveInstructorIdForUser(instructorsStore.instructors, user)
    if (!instructorId) return []
    return ensureArray(editableDraft.value?.sessions)
      .filter((session) => `${session?.instructor_id || ''}`.trim() === instructorId)
      .map((session) => ({ ...session }))
  }

  function getStudentSessionsForUser(user) {
    const studentId = resolveStudentIdForUser(studentsStore.students, user)
    if (!studentId) return []

    return ensureArray(editableDraft.value?.sessions)
      .filter((session) => {
        const courseId = `${session?.course_id || ''}`.trim()
        const sectionId = `${session?.section_id || ''}`.trim()
        if (!courseId || !sectionId) return false
        const plan = enrollmentPlan.value.find((item) => item.course_id === courseId)
        const sectionPlan = plan?.sections?.find((item) => item.section_id === sectionId)
        return Array.isArray(sectionPlan?.assigned_student_ids)
          ? sectionPlan.assigned_student_ids.includes(studentId)
          : false
      })
      .map((session) => ({ ...session }))
  }

  function enrichDraftRelations(draft) {
    const courseById = new Map(coursesStore.courses.map((item) => [item.id, item]))
    const courseByCode = new Map(
      coursesStore.courses.map((item) => [normalizeText(item.code), item]),
    )
    const instructorById = new Map(instructorsStore.instructors.map((item) => [item.id, item]))
    const instructorByName = new Map(
      instructorsStore.instructors.map((item) => [normalizeText(item.name), item]),
    )
    const roomById = new Map(roomsStore.rooms.map((item) => [item.id, item]))
    const roomByName = new Map(
      roomsStore.rooms.map((item) => [normalizeText(item.name_en), item]),
    )

    return {
      ...cloneDraft(draft),
      sessions: ensureArray(draft?.sessions).map((session) => {
        const next = { ...session }
        if (!next.course_id) {
          const resolvedCourse = courseByCode.get(normalizeText(next.course_code))
          next.course_id = resolvedCourse?.id ?? ''
        }
        if (!next.instructor_id) {
          const resolvedInstructor = instructorByName.get(normalizeText(next.instructor_name))
          next.instructor_id = resolvedInstructor?.id ?? ''
        }
        if (!next.room_id) {
          const resolvedRoom = roomByName.get(normalizeText(next.room_name))
          next.room_id = resolvedRoom?.id ?? ''
        }

        const course = courseById.get(next.course_id)
        if (!next.section_id && course) {
          const sectionWithInstructor = (course.sections ?? []).find((item) =>
            Array.isArray(item.instructor_ids) && item.instructor_ids.includes(next.instructor_id),
          )
          next.section_id = sectionWithInstructor?.id ?? course.sections?.[0]?.id ?? ''
        }

        const room = roomById.get(next.room_id)
        const instructor = instructorById.get(next.instructor_id)
        next.course_code = next.course_code || course?.code || ''
        next.course_name = next.course_name || course?.name_en || ''
        next.room_name = next.room_name || room?.name_en || ''
        next.instructor_name = next.instructor_name || instructor?.name || ''
        return next
      }),
    }
  }

  function sanitizeDraft(draft) {
    const daySet = new Set(enabledStudyDayValues.value)
    const slotSet = unblockedSlotSet.value
    return {
      ...cloneDraft(draft),
      sessions: ensureArray(draft?.sessions).filter((session) => {
        const day = `${session?.day || ''}`.trim()
        const start = `${session?.start || ''}`.trim()
        const end = `${session?.end || ''}`.trim()
        if (!daySet.has(day)) return false
        if (!slotSet.has(`${start}|${end}`)) return false
        return true
      }),
    }
  }

  async function ensureInitialized() {
    if (initialized.value) return
    loading.value = true
    await constraintsStore.ensureInitialized()
    await Promise.all([
      semestersStore.ensureInitialized(),
      roomsStore.ensureInitialized(),
      instructorsStore.ensureInitialized(),
      studentsStore.ensureInitialized(),
      coursesStore.ensureInitialized(),
    ])
    const semesterId = activeSemester.value?.id ?? ''
    const fetched = await coordinatorScheduleService.getCurrentSemesterSchedule(semesterId)
    const normalizedFetched = sanitizeDraft(enrichDraftRelations(fetched))
    editableDraft.value = cloneDraft(normalizedFetched)
    validateDraft(editableDraft.value)
    initialized.value = true
    loading.value = false
  }

  function moveSession(sessionId, nextDay, nextStart) {
    const slot = timeSlots.value.find((item) => item.start === nextStart && !item.blocked)
    if (!slot) return false
    editableDraft.value = {
      ...editableDraft.value,
      sessions: editableDraft.value.sessions.map((item) =>
        item.id === sessionId ? { ...item, day: nextDay, start: slot.start, end: slot.end } : item,
      ),
    }
    return true
  }

  async function saveEditableDraft() {
    editableDraft.value = enrichDraftRelations(editableDraft.value)
    editableDraft.value = sanitizeDraft(editableDraft.value)
    if (!validateDraft(editableDraft.value)) return false
    if (!activeSemester.value?.id) return false
    saving.value = true
    const saved = await coordinatorScheduleService.updateCurrentSemesterSchedule(
      activeSemester.value.id,
      editableDraft.value,
    )
    editableDraft.value = cloneDraft(saved)
    saving.value = false
    return true
  }

  async function generateSchedule(algorithm) {
    if (!activeSemester.value?.id) return false
    generating.value = true
    const generated = await coordinatorScheduleService.generateSchedule({
      semesterId: activeSemester.value.id,
      algorithm,
      baseDraft: editableDraft.value,
    })
    generatedDraft.value = sanitizeDraft(enrichDraftRelations(generated))
    generating.value = false
    return validateDraft(generatedDraft.value)
  }

  function clearGeneratedDraft() {
    generatedDraft.value = null
  }

  async function confirmAndSaveGeneratedSchedule() {
    if (!generatedDraft.value) return false
    editableDraft.value = cloneDraft(generatedDraft.value)
    const saved = await saveEditableDraft()
    if (!saved) return false
    generatedDraft.value = null
    return true
  }

  const kpis = computed(() => {
    const source = generatedDraft.value ?? editableDraft.value
    const sessions = ensureArray(source?.sessions)
    const totalSessions = sessions.length

    const roomLoad = new Map()
    const instructorLoad = new Map()

    sessions.forEach((item) => {
      roomLoad.set(item.room_name, (roomLoad.get(item.room_name) ?? 0) + 1)
      instructorLoad.set(item.instructor_name, (instructorLoad.get(item.instructor_name) ?? 0) + 1)
    })

    const roomUsageValues = [...roomLoad.values()]
    const avgRoomUsage =
      roomUsageValues.length > 0
        ? roomUsageValues.reduce((sum, value) => sum + value, 0) / roomUsageValues.length
        : 0

    const instructorValues = [...instructorLoad.values()]
    const minInstructorLoad = instructorValues.length > 0 ? Math.min(...instructorValues) : 0
    const maxInstructorLoad = instructorValues.length > 0 ? Math.max(...instructorValues) : 0
    const balanceScore = maxInstructorLoad === 0 ? 100 : Math.max(0, 100 - (maxInstructorLoad - minInstructorLoad) * 10)

    return {
      totalSessions,
      roomUtilization: `${Math.round(avgRoomUsage * 10) / 10}`,
      instructorBalance: `${balanceScore}%`,
      conflicts: validationErrors.value.length,
    }
  })

  watch(
    editableDraft,
    (nextValue) => {
      if (!nextValue) return
      const analysis = analyzeDraft(nextValue)
      validationErrors.value = analysis.errors
      conflictSessionIds.value = analysis.conflictIds
    },
    { deep: true },
  )

  return {
    loading,
    saving,
    generating,
    editableDraft,
    generatedDraft,
    activeSemester,
    enabledStudyDayValues,
    timeSlots,
    blockedSlotStarts,
    validationErrors,
    conflictSessionIds,
    kpis,
    enrollmentPlan,
    ensureInitialized,
    moveSession,
    saveEditableDraft,
    generateSchedule,
    clearGeneratedDraft,
    confirmAndSaveGeneratedSchedule,
    validateDraft,
    getLectureStudents,
    getInstructorSessionsForUser,
    getStudentSessionsForUser,
  }
})
