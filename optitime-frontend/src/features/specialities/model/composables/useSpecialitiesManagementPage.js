import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useSpecialitiesStore } from '@/features/specialities/model/stores/specialities.store'

export function useSpecialitiesManagementPage() {
  const specialitiesStore = useSpecialitiesStore()
  specialitiesStore.ensureInitialized()

  const { specialities, specialitiesCount, activeSpecialitiesCount, inactiveSpecialitiesCount } =
    storeToRefs(specialitiesStore)
  const activeSpecialityId = ref(null)
  const draft = ref(specialitiesStore.createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeSpecialityId.value))

  function startCreateSpeciality() {
    activeSpecialityId.value = null
    draft.value = specialitiesStore.createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditSpeciality(speciality) {
    activeSpecialityId.value = speciality.id
    draft.value = specialitiesStore.buildDraftFromSpeciality(speciality)
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  async function saveSpeciality() {
    if (activeSpecialityId.value) {
      const updated = await specialitiesStore.updateSpecialityFromDraft(activeSpecialityId.value, draft.value)
      if (!updated) return false
      closeDialog()
      return true
    }

    const created = await specialitiesStore.createSpecialityFromDraft(draft.value)
    if (!created) return false
    closeDialog()
    return true
  }

  async function removeSpeciality(specialityId) {
    await specialitiesStore.deleteSpeciality(specialityId)
    if (activeSpecialityId.value === specialityId) {
      activeSpecialityId.value = null
      draft.value = specialitiesStore.createEmptyDraft()
      closeDialog()
    }
  }

  return {
    specialities,
    specialitiesCount,
    activeSpecialitiesCount,
    inactiveSpecialitiesCount,
    draft,
    isDialogOpen,
    isEditing,
    startCreateSpeciality,
    startEditSpeciality,
    closeDialog,
    saveSpeciality,
    removeSpeciality,
  }
}
