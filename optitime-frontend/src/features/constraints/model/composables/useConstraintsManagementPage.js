import { ref } from 'vue'
import { useConstraintsStore } from '@/features/constraints/model/stores/constraints.store'

export function useConstraintsManagementPage() {
  const constraintsStore = useConstraintsStore()
  constraintsStore.ensureInitialized()

  const draft = ref(constraintsStore.getDraftForActiveSemester())

  function resetDraftToSaved() {
    draft.value = constraintsStore.getDraftForActiveSemester()
  }

  function saveDraft() {
    if (!draft.value) return false
    constraintsStore.replaceActiveSettings(draft.value)
    if (!constraintsStore.validateActiveSettings()) return false
    constraintsStore.saveActiveSettings()
    draft.value = constraintsStore.getDraftForActiveSemester()
    return true
  }

  function resetToDefault() {
    constraintsStore.resetActiveSettings()
    draft.value = constraintsStore.getDraftForActiveSemester()
    return true
  }

  return {
    draft,
    saveDraft,
    resetDraftToSaved,
    resetToDefault,
  }
}
