import { onMounted, ref } from 'vue'
import { useConstraintsStore } from '@/features/constraints/model/stores/constraints.store'

export function useConstraintsManagementPage() {
  const constraintsStore = useConstraintsStore()
  const draft = ref(constraintsStore.getDraftForActiveSemester())

  function resetDraftToSaved() {
    draft.value = constraintsStore.getDraftForActiveSemester()
  }

  async function saveDraft() {
    if (!draft.value) return false
    constraintsStore.replaceActiveSettings(draft.value)
    if (!constraintsStore.validateActiveSettings()) return false
    const ok = await constraintsStore.saveActiveSettings()
    if (!ok) return false
    draft.value = constraintsStore.getDraftForActiveSemester()
    return true
  }

  async function resetToDefault() {
    const ok = await constraintsStore.resetActiveSettings()
    if (!ok) return false
    draft.value = constraintsStore.getDraftForActiveSemester()
    return true
  }

  onMounted(async () => {
    await constraintsStore.ensureInitialized()
    resetDraftToSaved()
  })

  return {
    draft,
    saveDraft,
    resetDraftToSaved,
    resetToDefault,
  }
}
