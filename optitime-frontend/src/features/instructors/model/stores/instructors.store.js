import { defineStore } from 'pinia'
import { computed, ref, unref } from 'vue'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'
import {
  instructorsService,
  mapApiInstructorToRow,
} from '@/features/instructors/api/instructors.service'

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

  async function reloadInstructors() {
    const orgStore = useOrganizationStore()
    await orgStore.ensureInitialized()
    const raw = await instructorsService.getInstructorsRaw()
    const facultiesList = unref(orgStore.faculties) ?? []
    instructors.value = raw.map((row) => mapApiInstructorToRow(row, facultiesList))
  }

  async function ensureInitialized() {
    if (initialized.value) return
    try {
      await reloadInstructors()
    } catch {
      instructors.value = []
    }
    initialized.value = true
  }

  function buildDraftFromInstructor(instructor) {
    return {
      name: instructor.name,
      email: instructor.email,
      password: '',
      speciality_id: instructor.speciality_id ?? '',
      min_work_hours_per_week: instructor.min_work_hours_per_week ?? '',
      max_work_hours_per_week: instructor.max_work_hours_per_week ?? '',
      faculty_id: instructor.faculty_id ?? '',
      department_id: instructor.department_id ?? '',
      is_active: Boolean(instructor.is_active),
    }
  }

  function normalizeCreateDraft(draft) {
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
      faculty_id: facultyId,
      department_id: departmentId,
      is_active: Boolean(draft.is_active),
    }
  }

  function normalizeUpdateDraft(draft) {
    const name = draft.name?.trim() || ''
    const email = draft.email?.trim().toLowerCase() || ''
    const password = draft.password?.trim() || ''
    const specialityId = draft.speciality_id?.trim() || ''
    const minWorkHours = Number(draft.min_work_hours_per_week)
    const maxWorkHours = Number(draft.max_work_hours_per_week)
    const facultyId = draft.faculty_id?.trim() || ''
    const departmentId = draft.department_id?.trim() || ''
    if (!name || !email || !specialityId || !facultyId || !departmentId) return null
    if (!Number.isFinite(minWorkHours) || !Number.isFinite(maxWorkHours)) return null
    if (minWorkHours < 0 || maxWorkHours <= 0 || minWorkHours > maxWorkHours) return null

    return {
      name,
      email,
      password: password || undefined,
      speciality_id: specialityId,
      min_work_hours_per_week: minWorkHours,
      max_work_hours_per_week: maxWorkHours,
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
      specialization_id: normalized.speciality_id,
      min_work_hours_per_week: normalized.min_work_hours_per_week,
      max_work_hours_per_week: normalized.max_work_hours_per_week,
      is_active: normalized.is_active,
    }
  }

  function toApiUpdatePayload(normalized) {
    const payload = {
      full_name: normalized.name,
      email: normalized.email,
      department_id: normalized.department_id || null,
      specialization_id: normalized.speciality_id,
      min_work_hours_per_week: normalized.min_work_hours_per_week,
      max_work_hours_per_week: normalized.max_work_hours_per_week,
      is_active: normalized.is_active,
    }
    if (normalized.password) {
      payload.password = normalized.password
    }
    return payload
  }

  async function createInstructorFromDraft(draft) {
    const normalized = normalizeCreateDraft(draft)
    if (!normalized) return false
    const body = toApiCreatePayload(normalized)
    await instructorsService.createInstructor(body)
    await reloadInstructors()
    return true
  }

  async function updateInstructorFromDraft(instructorId, draft) {
    const normalized = normalizeUpdateDraft(draft)
    if (!normalized) return false
    const body = toApiUpdatePayload(normalized)
    await instructorsService.updateInstructor(instructorId, body)
    await reloadInstructors()
    return true
  }

  async function deleteInstructor(instructorId) {
    await instructorsService.deleteInstructor(instructorId)
    await reloadInstructors()
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
