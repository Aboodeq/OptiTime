const STORAGE_KEY = 'optitime.coordinator-weekly-schedule.v1'

const SCHEDULES_SEED = [
  {
    id: 'schedule-s1',
    semester_id: 'semester-1',
    sessions: [
      {
        id: 'session-1',
        day: 'sun',
        start: '08:00',
        end: '09:00',
        course_id: 'course-1',
        section_id: 'course-1-sec-1',
        course_code: 'SWE-301',
        course_name: 'Systems Analysis and Design',
        instructor_id: 'instructor-1',
        instructor_name: 'Dr. Lina Hasan',
        room_id: 'room-1',
        room_name: 'Room A101',
      },
      {
        id: 'session-2',
        day: 'mon',
        start: '09:15',
        end: '10:15',
        course_id: 'course-1',
        section_id: 'course-1-sec-2',
        course_code: 'SWE-301',
        course_name: 'Systems Analysis and Design (Lab)',
        instructor_id: 'instructor-3',
        instructor_name: 'Eng. Reem Khaled',
        room_id: 'room-2',
        room_name: 'Lab B2',
      },
      {
        id: 'session-3',
        day: 'tue',
        start: '10:30',
        end: '11:30',
        course_id: 'course-2',
        section_id: 'course-2-sec-1',
        course_code: 'FIN-210',
        course_name: 'Principles of Finance',
        instructor_id: 'instructor-2',
        instructor_name: 'Dr. Omar Saad',
        room_id: 'room-4',
        room_name: 'Room A102',
      },
      {
        id: 'session-4',
        day: 'wed',
        start: '08:00',
        end: '09:00',
        course_id: 'course-3',
        section_id: 'course-3-sec-1',
        course_code: 'DB-220',
        course_name: 'Databases',
        instructor_id: 'instructor-1',
        instructor_name: 'Dr. Lina Hasan',
        room_id: 'room-4',
        room_name: 'Room A102',
      },
      {
        id: 'session-5',
        day: 'thu',
        start: '09:15',
        end: '10:15',
        course_id: 'course-3',
        section_id: 'course-3-sec-2',
        course_code: 'DB-220',
        course_name: 'Databases (Lab)',
        instructor_id: 'instructor-3',
        instructor_name: 'Eng. Reem Khaled',
        room_id: 'room-5',
        room_name: 'Lab B3',
      },
      {
        id: 'session-6',
        day: 'sun',
        start: '10:30',
        end: '11:30',
        course_id: 'course-2',
        section_id: 'course-2-sec-1',
        course_code: 'FIN-210',
        course_name: 'Principles of Finance',
        instructor_id: 'instructor-2',
        instructor_name: 'Dr. Omar Saad',
        room_id: 'room-1',
        room_name: 'Room A101',
      },
      {
        id: 'session-7',
        day: 'mon',
        start: '08:00',
        end: '09:00',
        course_id: 'course-4',
        section_id: 'course-4-sec-1',
        course_code: 'NET-330',
        course_name: 'Computer Networks',
        instructor_id: 'instructor-5',
        instructor_name: 'Eng. Basel Jaber',
        room_id: 'room-6',
        room_name: 'Room C201',
      },
      {
        id: 'session-8',
        day: 'tue',
        start: '09:15',
        end: '10:15',
        course_id: 'course-4',
        section_id: 'course-4-sec-2',
        course_code: 'NET-330',
        course_name: 'Computer Networks (Lab)',
        instructor_id: 'instructor-3',
        instructor_name: 'Eng. Reem Khaled',
        room_id: 'room-7',
        room_name: 'Networks Lab C3',
      },
      {
        id: 'session-9',
        day: 'wed',
        start: '10:30',
        end: '11:30',
        course_id: 'course-5',
        section_id: 'course-5-sec-1',
        course_code: 'SWE-340',
        course_name: 'Advanced Software Engineering',
        instructor_id: 'instructor-4',
        instructor_name: 'Dr. Yara Hamwi',
        room_id: 'room-6',
        room_name: 'Room C201',
      },
      {
        id: 'session-10',
        day: 'thu',
        start: '08:00',
        end: '09:00',
        course_id: 'course-5',
        section_id: 'course-5-sec-1',
        course_code: 'SWE-340',
        course_name: 'Advanced Software Engineering',
        instructor_id: 'instructor-1',
        instructor_name: 'Dr. Lina Hasan',
        room_id: 'room-4',
        room_name: 'Room A102',
      },
      {
        id: 'session-11',
        day: 'wed',
        start: '09:15',
        end: '10:15',
        course_id: 'course-6',
        section_id: 'course-6-sec-1',
        course_code: 'FIN-320',
        course_name: 'Financial Risk Management',
        instructor_id: 'instructor-6',
        instructor_name: 'Dr. Rana Khatib',
        room_id: 'room-8',
        room_name: 'Hall B',
      },
      {
        id: 'session-12',
        day: 'thu',
        start: '10:30',
        end: '11:30',
        course_id: 'course-6',
        section_id: 'course-6-sec-1',
        course_code: 'FIN-320',
        course_name: 'Financial Risk Management',
        instructor_id: 'instructor-2',
        instructor_name: 'Dr. Omar Saad',
        room_id: 'room-8',
        room_name: 'Hall B',
      },
      {
        id: 'session-13',
        day: 'sun',
        start: '09:15',
        end: '10:15',
        course_id: 'course-3',
        section_id: 'course-3-sec-1',
        course_code: 'DB-220',
        course_name: 'Databases',
        instructor_id: 'instructor-4',
        instructor_name: 'Dr. Yara Hamwi',
        room_id: 'room-6',
        room_name: 'Room C201',
      },
      {
        id: 'session-14',
        day: 'mon',
        start: '10:30',
        end: '11:30',
        course_id: 'course-1',
        section_id: 'course-1-sec-2',
        course_code: 'SWE-301',
        course_name: 'Systems Analysis and Design (Lab)',
        instructor_id: 'instructor-3',
        instructor_name: 'Eng. Reem Khaled',
        room_id: 'room-5',
        room_name: 'Lab B3',
      },
      {
        id: 'session-15',
        day: 'tue',
        start: '08:00',
        end: '09:00',
        course_id: 'course-1',
        section_id: 'course-1-sec-1',
        course_code: 'SWE-301',
        course_name: 'Systems Analysis and Design',
        instructor_id: 'instructor-4',
        instructor_name: 'Dr. Yara Hamwi',
        room_id: 'room-1',
        room_name: 'Room A101',
      },
      {
        id: 'session-16',
        day: 'thu',
        start: '10:30',
        end: '11:30',
        course_id: 'course-3',
        section_id: 'course-3-sec-1',
        course_code: 'DB-220',
        course_name: 'Databases',
        instructor_id: 'instructor-1',
        instructor_name: 'Dr. Lina Hasan',
        room_id: 'room-6',
        room_name: 'Room C201',
      },
      {
        id: 'session-17',
        day: 'mon',
        start: '10:30',
        end: '11:30',
        course_id: 'course-5',
        section_id: 'course-5-sec-1',
        course_code: 'SWE-340',
        course_name: 'Advanced Software Engineering',
        instructor_id: 'instructor-4',
        instructor_name: 'Dr. Yara Hamwi',
        room_id: 'room-4',
        room_name: 'Room A102',
      },
      {
        id: 'session-18',
        day: 'tue',
        start: '10:30',
        end: '11:30',
        course_id: 'course-1',
        section_id: 'course-1-sec-1',
        course_code: 'SWE-301',
        course_name: 'Systems Analysis and Design',
        instructor_id: 'instructor-demo',
        instructor_name: 'Demo Instructor',
        room_id: 'room-8',
        room_name: 'Hall B',
      },
      {
        id: 'session-19',
        day: 'thu',
        start: '09:15',
        end: '10:15',
        course_id: 'course-3',
        section_id: 'course-3-sec-1',
        course_code: 'DB-220',
        course_name: 'Databases',
        instructor_id: 'instructor-demo',
        instructor_name: 'Demo Instructor',
        room_id: 'room-1',
        room_name: 'Room A101',
      },
    ],
  },
]

let schedulesDb = loadInitialData()

function mergeSchedulesWithSeed(existingSchedules) {
  const merged = Array.isArray(existingSchedules)
    ? existingSchedules.map((item) => cloneSchedule(item))
    : []

  for (const seedSchedule of SCHEDULES_SEED) {
    const existingIndex = merged.findIndex((item) => item.semester_id === seedSchedule.semester_id)
    if (existingIndex === -1) {
      merged.push(cloneSchedule(seedSchedule))
      continue
    }

    const existing = merged[existingIndex]
    const sessionMap = new Map((existing.sessions ?? []).map((session) => [session.id, { ...session }]))
    for (const seedSession of seedSchedule.sessions ?? []) {
      if (!sessionMap.has(seedSession.id)) {
        sessionMap.set(seedSession.id, { ...seedSession })
      }
    }
    merged.splice(existingIndex, 1, {
      ...existing,
      sessions: [...sessionMap.values()],
    })
  }

  return merged
}

function loadInitialData() {
  if (typeof window === 'undefined') return SCHEDULES_SEED.map(cloneSchedule)
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    if (!raw) return SCHEDULES_SEED.map(cloneSchedule)
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) return SCHEDULES_SEED.map(cloneSchedule)
    return mergeSchedulesWithSeed(parsed)
  } catch {
    return SCHEDULES_SEED.map(cloneSchedule)
  }
}

function persistData() {
  if (typeof window === 'undefined') return
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(schedulesDb))
  } catch {
    // ignore storage errors
  }
}

function cloneSchedule(schedule) {
  return {
    ...schedule,
    sessions: Array.isArray(schedule.sessions)
      ? schedule.sessions.map((item) => ({ ...item }))
      : [],
  }
}

function normalizeAlgorithm(algorithm) {
  return algorithm === 'backtracking' ? 'backtracking' : 'genetic'
}

function rotateSessionsByOffset(sessions, offset) {
  const size = sessions.length
  if (size <= 1) return sessions.map((item) => ({ ...item }))
  const normalizedOffset = ((offset % size) + size) % size
  return sessions.map((item, index) => {
    const target = sessions[(index + normalizedOffset) % size]
    return {
      ...item,
      day: target.day,
      start: target.start,
      end: target.end,
      room_name: target.room_name,
    }
  })
}

export const coordinatorScheduleService = {
  async getCurrentSemesterSchedule(semesterId) {
    const normalizedSemesterId = `${semesterId || ''}`.trim()
    const current = schedulesDb.find((item) => item.semester_id === normalizedSemesterId)
    if (current) return cloneSchedule(current)

    const created = {
      id: `schedule-${Date.now()}`,
      semester_id: normalizedSemesterId,
      sessions: [],
    }
    schedulesDb = [...schedulesDb, created]
    persistData()
    return cloneSchedule(created)
  },

  async updateCurrentSemesterSchedule(semesterId, draft) {
    const normalizedSemesterId = `${semesterId || ''}`.trim()
    const payload = {
      semester_id: normalizedSemesterId,
      sessions: Array.isArray(draft?.sessions) ? draft.sessions.map((item) => ({ ...item })) : [],
    }
    const existingIndex = schedulesDb.findIndex((item) => item.semester_id === normalizedSemesterId)
    if (existingIndex === -1) {
      const created = { ...payload, id: `schedule-${Date.now()}` }
      schedulesDb = [...schedulesDb, created]
      persistData()
      return cloneSchedule(created)
    }
    const updated = { ...schedulesDb[existingIndex], ...payload }
    const next = [...schedulesDb]
    next.splice(existingIndex, 1, updated)
    schedulesDb = next
    persistData()
    return cloneSchedule(updated)
  },

  async generateSchedule({ semesterId, algorithm, baseDraft }) {
    const normalizedAlgorithm = normalizeAlgorithm(algorithm)
    const baseSessions = Array.isArray(baseDraft?.sessions)
      ? baseDraft.sessions.map((item) => ({ ...item }))
      : []
    const generatedSessions =
      normalizedAlgorithm === 'backtracking'
        ? rotateSessionsByOffset(baseSessions, 1)
        : rotateSessionsByOffset(baseSessions, 2)

    return {
      id: `generated-${Date.now()}`,
      semester_id: `${semesterId || ''}`.trim(),
      sessions: generatedSessions,
      algorithm: normalizedAlgorithm,
      generated_at: new Date().toISOString(),
    }
  },
}
