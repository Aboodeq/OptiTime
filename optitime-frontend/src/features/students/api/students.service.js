const STUDENTS_SEED = [
  {
    id: 'student-demo',
    name: 'Demo Student',
    email: 'demo@student.optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260000',
    completed_hours: 60,
    year_level: 3,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'student-1',
    name: 'Ahmad Nasser',
    email: 'ahmad.nasser@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260001',
    completed_hours: 72,
    year_level: 3,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'student-2',
    name: 'Maya Faris',
    email: 'maya.faris@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260002',
    completed_hours: 105,
    year_level: 4,
    study_status: 'graduated',
    faculty_id: 'faculty-business',
    department_id: 'dept-finance',
    is_active: true,
  },
  {
    id: 'student-3',
    name: 'Yousef Ali',
    email: 'yousef.ali@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260003',
    completed_hours: 26,
    year_level: 1,
    study_status: 'suspended',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-networks',
    is_active: false,
  },
  {
    id: 'student-4',
    name: 'Rama Issa',
    email: 'rama.issa@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260004',
    completed_hours: 61,
    year_level: 3,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'student-5',
    name: 'Khaled Taha',
    email: 'khaled.taha@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260005',
    completed_hours: 66,
    year_level: 3,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'student-6',
    name: 'Nour Hamed',
    email: 'nour.hamed@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260006',
    completed_hours: 58,
    year_level: 3,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'student-7',
    name: 'Hadi Saleh',
    email: 'hadi.saleh@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260007',
    completed_hours: 63,
    year_level: 3,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'student-8',
    name: 'Leen Darwish',
    email: 'leen.darwish@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260008',
    completed_hours: 70,
    year_level: 4,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
  {
    id: 'student-9',
    name: 'Samer Aziz',
    email: 'samer.aziz@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260009',
    completed_hours: 52,
    year_level: 3,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-networks',
    is_active: true,
  },
  {
    id: 'student-10',
    name: 'Dana Hafez',
    email: 'dana.hafez@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260010',
    completed_hours: 47,
    year_level: 3,
    study_status: 'regular',
    faculty_id: 'faculty-business',
    department_id: 'dept-finance',
    is_active: true,
  },
  {
    id: 'student-11',
    name: 'Mohammad Eid',
    email: 'mohammad.eid@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260011',
    completed_hours: 64,
    year_level: 4,
    study_status: 'regular',
    faculty_id: 'faculty-business',
    department_id: 'dept-finance',
    is_active: true,
  },
  {
    id: 'student-12',
    name: 'Jana Qassem',
    email: 'jana.qassem@optitime.com',
    password: 'Stud@123',
    university_number: 'UNI-20260012',
    completed_hours: 39,
    year_level: 2,
    study_status: 'regular',
    faculty_id: 'faculty-informatics',
    department_id: 'dept-software',
    is_active: true,
  },
]

let studentsDb = STUDENTS_SEED.map((item) => ({ ...item }))

function cloneStudent(student) {
  return { ...student }
}

function generateUniversityNumber() {
  const year = new Date().getFullYear()
  return `UNI-${year}${Math.floor(Math.random() * 9000 + 1000)}`
}

export const studentsService = {
  async getStudents() {
    return studentsDb.map(cloneStudent)
  },

  async createStudent(payload) {
    const student = {
      ...payload,
      id: `student-${Date.now()}`,
      university_number: generateUniversityNumber(),
    }
    studentsDb = [...studentsDb, student]
    return cloneStudent(student)
  },

  async updateStudent(studentId, payload) {
    let updatedStudent = null
    studentsDb = studentsDb.map((item) => {
      if (item.id !== studentId) return item
      updatedStudent = {
        ...item,
        ...payload,
      }
      return updatedStudent
    })
    return updatedStudent ? cloneStudent(updatedStudent) : null
  },

  async deleteStudent(studentId) {
    const before = studentsDb.length
    studentsDb = studentsDb.filter((item) => item.id !== studentId)
    return studentsDb.length < before
  },
}
