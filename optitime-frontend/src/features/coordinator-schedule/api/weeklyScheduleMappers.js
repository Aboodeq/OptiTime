/** @param {string|undefined|null} t */
export function substrTime(t) {
  if (t == null) return ''
  const s = String(t)
  return s.length >= 5 ? s.slice(0, 5) : s
}

const LONG_DAY_TO_SHORT = {
  sunday: 'sun',
  monday: 'mon',
  tuesday: 'tue',
  wednesday: 'wed',
  thursday: 'thu',
  friday: 'fri',
  saturday: 'sat',
}

/**
 * Weekly API item (instructor/student) → board session shape.
 * @param {Record<string, unknown>} item
 */
export function mapWeeklyApiItemToBoardSession(item) {
  const section = item.section && typeof item.section === 'object' ? item.section : {}
  const course =
    section.course && typeof section.course === 'object' ? section.course : {}
  const room = item.room && typeof item.room === 'object' ? item.room : {}
  const longDay = `${item.day_of_week || ''}`.trim().toLowerCase()
  const shortDay = LONG_DAY_TO_SHORT[longDay] || longDay
  const rawStudents = Array.isArray(item.students) ? item.students : []

  return {
    id: item.id,
    day: shortDay,
    start: substrTime(item.start_time),
    end: substrTime(item.end_time),
    course_id: `${item.course_id || course.id || ''}`.trim(),
    section_id: `${item.section_id || section.id || ''}`.trim(),
    instructor_id: `${item.instructor_id || ''}`.trim(),
    course_offering_id: `${item.course_offering_id || ''}`.trim(),
    section_instructor_id: `${item.section_instructor_id || ''}`.trim(),
    room_id: `${item.room_id || ''}`.trim(),
    course_code: `${course.code || ''}`.trim(),
    course_name: `${course.name || course.name_en || course.name_ar || ''}`.trim(),
    room_name: `${room.name || room.name_en || room.name_ar || ''}`.trim(),
    instructor_name: `${item.instructor_name || ''}`.trim(),
    students: rawStudents.map((s) => ({
      id: `${s?.id || ''}`.trim(),
      name: `${s?.name || ''}`.trim(),
      university_number: `${s?.university_number || ''}`.trim(),
      year_level: s?.year_level,
      study_status: `${s?.study_status || ''}`.trim(),
    })),
  }
}

/**
 * Laravel plan `show` session → board session.
 * @param {Record<string, unknown>} session
 */
export function mapCoordinatorApiSessionToBoard(session) {
  const si =
    (session.section_instructor && typeof session.section_instructor === 'object'
      ? session.section_instructor
      : null) ||
    (session.sectionInstructor && typeof session.sectionInstructor === 'object'
      ? session.sectionInstructor
      : null) ||
    {}
  const sec = si.section && typeof si.section === 'object' ? si.section : {}
  const courseFromSection = sec.course && typeof sec.course === 'object' ? sec.course : {}
  const co =
    (session.course_offering && typeof session.course_offering === 'object'
      ? session.course_offering
      : null) ||
    (session.courseOffering && typeof session.courseOffering === 'object'
      ? session.courseOffering
      : null) ||
    {}
  const courseFromOffering =
    co.course && typeof co.course === 'object' ? co.course : courseFromSection
  const room = session.room && typeof session.room === 'object' ? session.room : {}
  const instructor =
    si.instructor && typeof si.instructor === 'object' ? si.instructor : {}
  const rawSessionStudents = Array.isArray(session.session_students)
    ? session.session_students
    : Array.isArray(session.sessionStudents)
      ? session.sessionStudents
      : []
  const students = rawSessionStudents.map((row) => {
    const student = row?.student && typeof row.student === 'object' ? row.student : {}
    const user = student?.user && typeof student.user === 'object' ? student.user : {}
    return {
      id: `${student.user_id || ''}`.trim(),
      name: `${user.full_name || ''}`.trim(),
      university_number: `${student.university_number || ''}`.trim(),
      year_level: student.year_level,
      study_status: `${student.study_status || ''}`.trim(),
    }
  })

  return {
    id: session.id,
    day: `${session.day_value || session.day || ''}`.trim(),
    start: substrTime(session.start_time ?? session.start),
    end: substrTime(session.end_time ?? session.end),
    room_id: `${session.room_id || ''}`.trim(),
    section_instructor_id: `${session.section_instructor_id || ''}`.trim(),
    course_offering_id: `${session.course_offering_id || ''}`.trim(),
    course_id: `${sec.course_id || co.course_id || courseFromOffering.id || ''}`.trim(),
    section_id: `${si.section_id || sec.id || ''}`.trim(),
    instructor_id: `${si.instructor_id || ''}`.trim(),
    course_code: `${courseFromOffering.code || ''}`.trim(),
    course_name: `${courseFromOffering.name_en || courseFromOffering.name_ar || courseFromOffering.name || ''}`.trim(),
    room_name: `${room.name_en || room.name_ar || room.name || ''}`.trim(),
    instructor_name: `${instructor.full_name || instructor.name || ''}`.trim(),
    students,
  }
}

/**
 * @param {Record<string, unknown>|null|undefined} plan
 */
export function mapCoordinatorPlanToDraftShape(plan) {
  if (!plan || typeof plan !== 'object') {
    return {
      planId: '',
      semester_id: '',
      status: 'draft',
      notes: '',
      sessions: [],
    }
  }
  const sessions = Array.isArray(plan.sessions)
    ? plan.sessions.map((s) => mapCoordinatorApiSessionToBoard(s))
    : []
  return {
    planId: `${plan.id || ''}`.trim(),
    semester_id: `${plan.semester_id || ''}`.trim(),
    status: `${plan.status || 'draft'}`.trim(),
    notes: plan.notes ?? '',
    sessions,
  }
}

/**
 * Board sessions → PUT /schedules/{id}/sessions body rows.
 * @param {Array<Record<string, unknown>>} sessions
 */
export function boardSessionsToSyncPayload(sessions) {
  return (Array.isArray(sessions) ? sessions : []).map((s) => ({
    room_id: `${s.room_id || ''}`.trim(),
    section_instructor_id: `${s.section_instructor_id || ''}`.trim(),
    course_offering_id: `${s.course_offering_id || ''}`.trim(),
    day: `${s.day || ''}`.trim(),
    start: `${s.start || ''}`.trim(),
    end: `${s.end || ''}`.trim(),
  }))
}

/**
 * Board sessions → generator baseDraft.sessions (minimal keys).
 * @param {Array<Record<string, unknown>>} sessions
 */
export function boardSessionsToGenerateBaseSessions(sessions) {
  return (Array.isArray(sessions) ? sessions : []).map((s) => ({
    course_id: s.course_id,
    course_section_id: s.section_id,
    section_id: s.section_id,
    instructor_id: s.instructor_id,
    course_offering_id: s.course_offering_id,
    section_instructor_id: s.section_instructor_id,
    room_id: s.room_id,
    day: s.day,
    start: s.start,
    end: s.end,
    enrollment: 1,
    is_lab: false,
  }))
}

const BOARD_VALID_DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat']

function boardToMinutes(time) {
  if (typeof time !== 'string' || !/^\d{2}:\d{2}$/.test(time)) return NaN
  const [h, m] = time.split(':').map(Number)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return NaN
  return h * 60 + m
}

function boardToTime(minutes) {
  const safe = Math.max(0, minutes)
  const h = String(Math.floor(safe / 60)).padStart(2, '0')
  const m = String(safe % 60).padStart(2, '0')
  return `${h}:${m}`
}

function boardOverlaps(startA, endA, startB, endB) {
  return startA < endB && endA > startB
}

/**
 * Build weekly board columns/rows from API `board` payload (same shape as instructor availability grid context).
 * @param {Record<string, unknown>|null|undefined} board
 * @returns {{ enabledStudyDayValues: string[], timeSlots: Array<{ start: string, end: string, blocked: boolean }>, blockedSlotStarts: string[] }}
 */
export function deriveWeeklyBoardGrid(board) {
  const settings = board && typeof board === 'object' ? board : {}
  const studyDays = Array.isArray(settings.study_days) ? settings.study_days : []
  let enabledStudyDayValues = studyDays
    .filter((item) => item && item.enabled)
    .map((item) => `${item.value || ''}`.trim())
    .filter((item) => BOARD_VALID_DAYS.includes(item))

  if (enabledStudyDayValues.length === 0) {
    enabledStudyDayValues = ['sun', 'mon', 'tue', 'wed', 'thu']
  }

  const dayStartMinutes = boardToMinutes(`${settings.day_start || '08:00'}`.slice(0, 5))
  const dayEndMinutes = boardToMinutes(`${settings.day_end || '16:00'}`.slice(0, 5))
  const slotMinutes = Math.max(1, Number(settings.slot_minutes) || 60)
  const gapMinutes = Math.max(0, Number(settings.gap_minutes) || 0)
  const breaks = (Array.isArray(settings.break_times) ? settings.break_times : []).filter((item) => item?.enabled)

  const timeSlots = []
  if (Number.isFinite(dayStartMinutes) && Number.isFinite(dayEndMinutes) && dayEndMinutes > dayStartMinutes) {
    const cycle = slotMinutes + gapMinutes
    for (let start = dayStartMinutes; start + slotMinutes <= dayEndMinutes; start += cycle) {
      const end = start + slotMinutes
      const blocked = breaks.some((item) =>
        boardOverlaps(start, end, boardToMinutes(`${item.start}`.slice(0, 5)), boardToMinutes(`${item.end}`.slice(0, 5))),
      )
      timeSlots.push({
        start: boardToTime(start),
        end: boardToTime(end),
        blocked,
      })
    }
  }

  const blockedSlotStarts = timeSlots.filter((item) => item.blocked).map((item) => item.start)

  return { enabledStudyDayValues, timeSlots, blockedSlotStarts }
}
