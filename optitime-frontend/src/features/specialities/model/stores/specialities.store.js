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

  async function reloadSpecialities() {
    specialities.value = await specialitiesService.getSpecialities()
  }

  async function ensureInitialized() {
    if (initialized.value) return
    try {
      await reloadSpecialities()
    } catch {
      specialities.value = []
    }
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
    await specialitiesService.createSpeciality(normalized)
    await reloadSpecialities()
    return true
  }

  async function updateSpecialityFromDraft(specialityId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    await specialitiesService.updateSpeciality(specialityId, normalized)
    await reloadSpecialities()
    return true
  }

  async function deleteSpeciality(specialityId) {
    await specialitiesService.deleteSpeciality(specialityId)
    await reloadSpecialities()
  }

  return {
    specialities,
    specialitiesCount,
    activeSpecialitiesCount,
    inactiveSpecialitiesCount,
    ensureInitialized,
    reloadSpecialities,
    createEmptyDraft,
    buildDraftFromSpeciality,
    createSpecialityFromDraft,
    updateSpecialityFromDraft,
    deleteSpeciality,
  }
})
