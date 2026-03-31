import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { specialitiesService } from '@/features/specialities/api/specialities.service'

function createEmptyDraft() {
  return {
    code: '',
    name_ar: '',
    name_en: '',
    is_active: true,
  }
}

export const useSpecialitiesStore = defineStore('specialities', () => {
  const specialities = ref([])
  const initialized = ref(false)
  const specialitiesCount = computed(() => specialities.value.length)
  const activeSpecialitiesCount = computed(
    () => specialities.value.filter((speciality) => speciality.is_active).length,
  )
  const inactiveSpecialitiesCount = computed(
    () => specialitiesCount.value - activeSpecialitiesCount.value,
  )

  async function ensureInitialized() {
    if (initialized.value) return
    specialities.value = await specialitiesService.getSpecialities()
    initialized.value = true
  }

  function buildDraftFromSpeciality(speciality) {
    return {
      code: speciality.code ?? '',
      name_ar: speciality.name_ar ?? '',
      name_en: speciality.name_en ?? '',
      is_active: Boolean(speciality.is_active),
    }
  }

  function normalizeDraft(draft) {
    const code = draft.code?.trim() || ''
    const nameAr = draft.name_ar?.trim() || ''
    const nameEn = draft.name_en?.trim() || ''
    if (!code || !nameAr || !nameEn) return null

    return {
      code: code.toLowerCase(),
      name_ar: nameAr,
      name_en: nameEn,
      is_active: Boolean(draft.is_active),
    }
  }

  async function createSpecialityFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const created = await specialitiesService.createSpeciality(normalized)
    specialities.value = [...specialities.value, created]
    return true
  }

  async function updateSpecialityFromDraft(specialityId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const updated = await specialitiesService.updateSpeciality(specialityId, normalized)
    if (!updated) return false
    specialities.value = specialities.value.map((item) => (item.id === specialityId ? updated : item))
    return true
  }

  async function deleteSpeciality(specialityId) {
    const deleted = await specialitiesService.deleteSpeciality(specialityId)
    if (!deleted) return false
    specialities.value = specialities.value.filter((item) => item.id !== specialityId)
    return true
  }

  return {
    specialities,
    specialitiesCount,
    activeSpecialitiesCount,
    inactiveSpecialitiesCount,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromSpeciality,
    createSpecialityFromDraft,
    updateSpecialityFromDraft,
    deleteSpeciality,
  }
})
