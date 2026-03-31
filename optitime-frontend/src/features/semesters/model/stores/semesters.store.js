import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { semestersService } from '@/features/semesters/api/semesters.service'

function createEmptyDraft() {
  return {
    name: '',
    code: '',
    academic_year: '',
    start_date: '',
    end_date: '',
    is_active: true,
  }
}

export const useSemestersStore = defineStore('semesters', () => {
  const semesters = ref([])
  const initialized = ref(false)

  const semestersCount = computed(() => semesters.value.length)
  const activeSemestersCount = computed(() => semesters.value.filter((semester) => semester.is_active).length)
  const inactiveSemestersCount = computed(() => semestersCount.value - activeSemestersCount.value)

  async function ensureInitialized() {
    if (initialized.value) return
    const payload = await semestersService.getSemesters()
    semesters.value = payload
    initialized.value = true
  }

  function buildDraftFromSemester(semester) {
    return {
      name: semester.name ?? '',
      code: semester.code ?? '',
      academic_year: semester.academic_year ?? '',
      start_date: semester.start_date ?? '',
      end_date: semester.end_date ?? '',
      is_active: Boolean(semester.is_active),
    }
  }

  function normalizeDraft(draft) {
    const name = draft.name?.trim() || ''
    const code = draft.code?.trim().toLowerCase() || ''
    const academicYear = draft.academic_year?.trim() || ''
    const startDate = draft.start_date?.trim() || ''
    const endDate = draft.end_date?.trim() || ''

    if (!name || !code || !academicYear || !startDate || !endDate) return null
    if (startDate > endDate) return null

    return {
      name,
      code,
      academic_year: academicYear,
      start_date: startDate,
      end_date: endDate,
      is_active: Boolean(draft.is_active),
    }
  }

  async function createSemesterFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const created = await semestersService.createSemester(normalized)
    semesters.value = [...semesters.value, created]
    return true
  }

  async function updateSemesterFromDraft(semesterId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const updated = await semestersService.updateSemester(semesterId, normalized)
    if (!updated) return false
    semesters.value = semesters.value.map((item) => (item.id === semesterId ? updated : item))
    return true
  }

  async function deleteSemester(semesterId) {
    const deleted = await semestersService.deleteSemester(semesterId)
    if (!deleted) return false
    semesters.value = semesters.value.filter((item) => item.id !== semesterId)
    return true
  }

  return {
    semesters,
    semestersCount,
    activeSemestersCount,
    inactiveSemestersCount,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromSemester,
    createSemesterFromDraft,
    updateSemesterFromDraft,
    deleteSemester,
  }
})
