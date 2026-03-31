import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useSemestersStore } from '@/features/semesters/model/stores/semesters.store'

export function useSemestersManagementPage() {
  const semestersStore = useSemestersStore()
  semestersStore.ensureInitialized()

  const { semesters, semestersCount, activeSemestersCount, inactiveSemestersCount } =
    storeToRefs(semestersStore)
  const activeSemesterId = ref(null)
  const draft = ref(semestersStore.createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeSemesterId.value))

  function startCreateSemester() {
    activeSemesterId.value = null
    draft.value = semestersStore.createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditSemester(semester) {
    activeSemesterId.value = semester.id
    draft.value = semestersStore.buildDraftFromSemester(semester)
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  async function saveSemester() {
    if (activeSemesterId.value) {
      const updated = await semestersStore.updateSemesterFromDraft(activeSemesterId.value, draft.value)
      if (!updated) return false
      closeDialog()
      return true
    }

    const created = await semestersStore.createSemesterFromDraft(draft.value)
    if (!created) return false
    closeDialog()
    return true
  }

  async function removeSemester(semesterId) {
    const deleted = await semestersStore.deleteSemester(semesterId)
    if (!deleted) return
    if (activeSemesterId.value === semesterId) {
      activeSemesterId.value = null
      draft.value = semestersStore.createEmptyDraft()
      closeDialog()
    }
  }

  return {
    semesters,
    semestersCount,
    activeSemestersCount,
    inactiveSemestersCount,
    draft,
    isDialogOpen,
    isEditing,
    startCreateSemester,
    startEditSemester,
    closeDialog,
    saveSemester,
    removeSemester,
  }
}
