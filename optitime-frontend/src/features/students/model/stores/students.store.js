import { defineStore } from 'pinia'
import { computed, ref, unref } from 'vue'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'
import {
  mapApiStudentToRow,
  studentsService,
} from '@/features/students/api/students.service'

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

  async function reloadStudents() {
    const orgStore = useOrganizationStore()
    await orgStore.ensureInitialized()
    const raw = await studentsService.getStudentsRaw()
    const facultiesList = unref(orgStore.faculties) ?? []
    students.value = raw.map((row) => mapApiStudentToRow(row, facultiesList))
  }

  async function ensureInitialized() {
    if (initialized.value) return
    try {
      await reloadStudents()
    } catch {
      students.value = []
    }
    initialized.value = true
  }

  function buildDraftFromStudent(student) {
    return {
      name: student.name,
      email: student.email,
      password: '',
      university_number: student.university_number ?? '',
      completed_hours: student.completed_hours ?? '',
      year_level: student.year_level ?? '',
      study_status: student.study_status ?? 'regular',
      faculty_id: student.faculty_id ?? '',
      department_id: student.department_id ?? '',
      is_active: Boolean(student.is_active),
    }
  }

  function normalizeCreateDraft(draft) {
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

  function normalizeUpdateDraft(draft) {
    const name = draft.name?.trim() || ''
    const email = draft.email?.trim().toLowerCase() || ''
    const password = draft.password?.trim() || ''
    const completedHours = Number(draft.completed_hours)
    const yearLevel = Number(draft.year_level)
    const studyStatus = draft.study_status?.trim() || ''
    const facultyId = draft.faculty_id?.trim() || ''
    const departmentId = draft.department_id?.trim() || ''
    if (!name || !email || !facultyId || !departmentId) return null
    if (!Number.isFinite(completedHours) || completedHours < 0) return null
    if (!Number.isInteger(yearLevel) || yearLevel < 1 || yearLevel > 6) return null
    if (!STUDY_STATUS_OPTIONS.includes(studyStatus)) return null

    return {
      name,
      email,
      password: password || undefined,
      completed_hours: completedHours,
      year_level: yearLevel,
      study_status: studyStatus,
      faculty_id: facultyId,
      department_id: departmentId,
      is_active: Boolean(draft.is_active),
    }
  }

  function toApiCreatePayload(normalized) {
    return {
      full_name: normalized.name,
      email: normalized.email,
      password: normalized.password,
      department_id: normalized.department_id || null,
      completed_hours: normalized.completed_hours,
      year_level: normalized.year_level,
      study_status: normalized.study_status,
      is_active: normalized.is_active,
    }
  }

  function toApiUpdatePayload(normalized) {
    const payload = {
      full_name: normalized.name,
      email: normalized.email,
      department_id: normalized.department_id || null,
      completed_hours: normalized.completed_hours,
      year_level: normalized.year_level,
      study_status: normalized.study_status,
      is_active: normalized.is_active,
    }
    if (normalized.password) {
      payload.password = normalized.password
    }
    return payload
  }

  async function createStudentFromDraft(draft) {
    const normalized = normalizeCreateDraft(draft)
    if (!normalized) return false
    const body = toApiCreatePayload(normalized)
    await studentsService.createStudent(body)
    await reloadStudents()
    return true
  }

  async function updateStudentFromDraft(studentId, draft) {
    const normalized = normalizeUpdateDraft(draft)
    if (!normalized) return false
    const body = toApiUpdatePayload(normalized)
    await studentsService.updateStudent(studentId, body)
    await reloadStudents()
    return true
  }

  async function deleteStudent(studentId) {
    await studentsService.deleteStudent(studentId)
    await reloadStudents()
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
