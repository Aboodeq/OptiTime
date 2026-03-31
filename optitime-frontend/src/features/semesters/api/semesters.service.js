const SEMESTERS_SEED = [
  {
    id: 'semester-1',
    name: 'Fall Semester',
    code: '2026-s1',
    academic_year: '2026-2027',
    start_date: '2026-09-01',
    end_date: '2027-01-15',
    is_active: true,
  },
  {
    id: 'semester-2',
    name: 'Spring Semester',
    code: '2026-s2',
    academic_year: '2026-2027',
    start_date: '2027-02-10',
    end_date: '2027-06-20',
    is_active: false,
  },
]

let semestersDb = SEMESTERS_SEED.map((item) => ({ ...item }))

function cloneSemester(semester) {
  return { ...semester }
}

export const semestersService = {
  async getSemesters() {
    return semestersDb.map(cloneSemester)
  },

  async createSemester(payload) {
    const semester = {
      ...payload,
      id: `semester-${Date.now()}`,
    }
    semestersDb = [...semestersDb, semester]
    return cloneSemester(semester)
  },

  async updateSemester(semesterId, payload) {
    let updatedSemester = null
    semestersDb = semestersDb.map((item) => {
      if (item.id !== semesterId) return item
      updatedSemester = {
        ...item,
        ...payload,
      }
      return updatedSemester
    })
    return updatedSemester ? cloneSemester(updatedSemester) : null
  },

  async deleteSemester(semesterId) {
    const before = semestersDb.length
    semestersDb = semestersDb.filter((item) => item.id !== semesterId)
    return semestersDb.length < before
  },
}
