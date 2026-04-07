const INSTRUCTORS_SEED = [
  {
    id: 'instructor-1',
    user_id: 'user-instructor-1',
    name: 'Dr. Lina Hasan',
    email: 'lina.hasan@optitime.com',
    password: 'Inst@123',
    speciality_id: 'speciality-software',
    min_work_hours_per_week: 10,
    max_work_hours_per_week: 16,
    role: 'instructor',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'instructor-2',
    user_id: 'user-instructor-2',
    name: 'Dr. Omar Saad',
    email: 'omar.saad@optitime.com',
    password: 'Inst@123',
    speciality_id: 'speciality-business',
    min_work_hours_per_week: 8,
    max_work_hours_per_week: 14,
    role: 'instructor',
    faculty_id: 'faculty-business',
    department_id: 'dept-finance',
    is_active: true,
  },
  {
    id: 'instructor-3',
    user_id: 'user-instructor-3',
    name: 'Eng. Reem Khaled',
    email: 'reem.khaled@optitime.com',
    password: 'Inst@123',
    speciality_id: 'speciality-networks',
    min_work_hours_per_week: 6,
    max_work_hours_per_week: 12,
    role: 'instructor',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-networks',
    is_active: false,
  },
  {
    id: 'instructor-demo',
    user_id: 'demo-instructor',
    name: 'Demo Instructor',
    email: 'demo@optitime.com',
    password: 'Inst@123',
    speciality_id: 'speciality-software',
    min_work_hours_per_week: 6,
    max_work_hours_per_week: 12,
    role: 'instructor',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
]

let instructorsDb = INSTRUCTORS_SEED.map((item) => ({ ...item }))

function cloneInstructor(instructor) {
  return { ...instructor }
}

export const instructorsService = {
  async getInstructors() {
    return instructorsDb.map(cloneInstructor)
  },

  async createInstructor(payload) {
    const instructor = {
      ...payload,
      id: `instructor-${Date.now()}`,
      role: 'instructor',
    }
    instructorsDb = [...instructorsDb, instructor]
    return cloneInstructor(instructor)
  },

  async updateInstructor(instructorId, payload) {
    let updatedInstructor = null
    instructorsDb = instructorsDb.map((item) => {
      if (item.id !== instructorId) return item
      updatedInstructor = {
        ...item,
        ...payload,
        role: 'instructor',
      }
      return updatedInstructor
    })
    return updatedInstructor ? cloneInstructor(updatedInstructor) : null
  },

  async deleteInstructor(instructorId) {
    const before = instructorsDb.length
    instructorsDb = instructorsDb.filter((item) => item.id !== instructorId)
    return instructorsDb.length < before
  },
}
