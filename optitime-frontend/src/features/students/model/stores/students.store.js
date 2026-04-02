import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { studentsService } from '@/features/students/api/students.service'

const STUDY_STATUS_OPTIONS = Object.freeze(['regular', 'suspended', 'graduated'])

function createEmptyDraft() {
  return {
    name: '',
    email: '',
    password: '',
    university_number: '',
    completed_hours: '',
    year_level: '',
    study_status: 'regular',
    faculty_id: '',
    department_id: '',
    is_active: true,
  }
}

export const useStudentsStore = defineStore('students', () => {
  const students = ref([])
  const initialized = ref(false)

  const studentsCount = computed(() => students.value.length)
  const activeStudentsCount = computed(() => students.value.filter((student) => student.is_active).length)
  const inactiveStudentsCount = computed(() => studentsCount.value - activeStudentsCount.value)

  async function ensureInitialized() {
    if (initialized.value) return
    const payload = await studentsService.getStudents()
    students.value = payload
    initialized.value = true
  }

  function buildDraftFromStudent(student) {
    return {
      name: student.name,
      email: student.email,
      password: student.password ?? '',
      university_number: student.university_number ?? '',
      completed_hours: student.completed_hours ?? '',
      year_level: student.year_level ?? '',
      study_status: student.study_status ?? 'regular',
      faculty_id: student.faculty_id ?? '',
      department_id: student.department_id ?? '',
      is_active: Boolean(student.is_active),
    }
  }

  function normalizeDraft(draft) {
    const name = draft.name?.trim() || ''
    const email = draft.email?.trim().toLowerCase() || ''
    const password = draft.password?.trim() || ''
    const completedHours = Number(draft.completed_hours)
    const yearLevel = Number(draft.year_level)
    const studyStatus = draft.study_status?.trim() || ''
    const facultyId = draft.faculty_id?.trim() || ''
    const departmentId = draft.department_id?.trim() || ''
    if (!name || !email || !password || !facultyId || !departmentId) return null
    if (!Number.isFinite(completedHours) || completedHours < 0) return null
    if (!Number.isInteger(yearLevel) || yearLevel < 1 || yearLevel > 6) return null
    if (!STUDY_STATUS_OPTIONS.includes(studyStatus)) return null

    return {
      name,
      email,
      password,
      completed_hours: completedHours,
      year_level: yearLevel,
      study_status: studyStatus,
      faculty_id: facultyId,
      department_id: departmentId,
      is_active: Boolean(draft.is_active),
    }
  }

  async function createStudentFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const created = await studentsService.createStudent(normalized)
    students.value = [...students.value, created]
    return true
  }

  async function updateStudentFromDraft(studentId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const updated = await studentsService.updateStudent(studentId, normalized)
    if (!updated) return false
    students.value = students.value.map((item) => (item.id === studentId ? updated : item))
    return true
  }

  async function deleteStudent(studentId) {
    const deleted = await studentsService.deleteStudent(studentId)
    if (!deleted) return false
    students.value = students.value.filter((item) => item.id !== studentId)
    return true
  }

  return {
    students,
    studentsCount,
    activeStudentsCount,
    inactiveStudentsCount,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromStudent,
    createStudentFromDraft,
    updateStudentFromDraft,
    deleteStudent,
  }
})
