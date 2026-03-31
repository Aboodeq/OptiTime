import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { instructorsService } from '@/features/instructors/api/instructors.service'

function createEmptyDraft() {
  return {
    name: '',
    email: '',
    password: '',
    speciality_id: '',
    min_work_hours_per_week: '',
    max_work_hours_per_week: '',
    faculty_id: '',
    department_id: '',
    is_active: true,
  }
}

export const useInstructorsStore = defineStore('instructors', () => {
  const instructors = ref([])
  const initialized = ref(false)

  const instructorsCount = computed(() => instructors.value.length)
  const activeInstructorsCount = computed(
    () => instructors.value.filter((instructor) => instructor.is_active).length,
  )
  const inactiveInstructorsCount = computed(
    () => instructorsCount.value - activeInstructorsCount.value,
  )

  async function ensureInitialized() {
    if (initialized.value) return
    const payload = await instructorsService.getInstructors()
    instructors.value = payload.map((item) => ({ ...item, role: 'instructor' }))
    initialized.value = true
  }

  function buildDraftFromInstructor(instructor) {
    return {
      name: instructor.name,
      email: instructor.email,
      password: instructor.password ?? '',
      speciality_id: instructor.speciality_id ?? '',
      min_work_hours_per_week: instructor.min_work_hours_per_week ?? '',
      max_work_hours_per_week: instructor.max_work_hours_per_week ?? '',
      faculty_id: instructor.faculty_id ?? '',
      department_id: instructor.department_id ?? '',
      is_active: Boolean(instructor.is_active),
    }
  }

  function normalizeDraft(draft) {
    const name = draft.name?.trim() || ''
    const email = draft.email?.trim().toLowerCase() || ''
    const password = draft.password?.trim() || ''
    const specialityId = draft.speciality_id?.trim() || ''
    const minWorkHours = Number(draft.min_work_hours_per_week)
    const maxWorkHours = Number(draft.max_work_hours_per_week)
    const facultyId = draft.faculty_id?.trim() || ''
    const departmentId = draft.department_id?.trim() || ''
    if (!name || !email || !password || !specialityId || !facultyId || !departmentId) return null
    if (!Number.isFinite(minWorkHours) || !Number.isFinite(maxWorkHours)) return null
    if (minWorkHours < 0 || maxWorkHours <= 0 || minWorkHours > maxWorkHours) return null

    return {
      name,
      email,
      password,
      speciality_id: specialityId,
      min_work_hours_per_week: minWorkHours,
      max_work_hours_per_week: maxWorkHours,
      role: 'instructor',
      faculty_id: facultyId,
      department_id: departmentId,
      is_active: Boolean(draft.is_active),
    }
  }

  async function createInstructorFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const created = await instructorsService.createInstructor(normalized)
    instructors.value = [...instructors.value, created]
    return true
  }

  async function updateInstructorFromDraft(instructorId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const updated = await instructorsService.updateInstructor(instructorId, normalized)
    if (!updated) return false
    instructors.value = instructors.value.map((item) => (item.id === instructorId ? updated : item))
    return true
  }

  async function deleteInstructor(instructorId) {
    const deleted = await instructorsService.deleteInstructor(instructorId)
    if (!deleted) return false
    instructors.value = instructors.value.filter((item) => item.id !== instructorId)
    return true
  }

  return {
    instructors,
    instructorsCount,
    activeInstructorsCount,
    inactiveInstructorsCount,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromInstructor,
    createInstructorFromDraft,
    updateInstructorFromDraft,
    deleteInstructor,
  }
})
