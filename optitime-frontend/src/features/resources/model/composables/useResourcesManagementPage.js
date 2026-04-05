import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useResourcesStore } from '@/features/resources/model/stores/resources.store'

export function useResourcesManagementPage() {
  const resourcesStore = useResourcesStore()
  resourcesStore.ensureInitialized()

  const { resources, resourcesCount, availableResourcesCount, unavailableResourcesCount } =
    storeToRefs(resourcesStore)
  const activeResourceId = ref(null)
  const draft = ref(resourcesStore.createEmptyDraft())
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeResourceId.value))

  function startCreateResource() {
    activeResourceId.value = null
    draft.value = resourcesStore.createEmptyDraft()
    isDialogOpen.value = true
  }

  function startEditResource(resource) {
    activeResourceId.value = resource.id
    draft.value = resourcesStore.buildDraftFromResource(resource)
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  async function saveResource() {
    if (activeResourceId.value) {
      const updated = await resourcesStore.updateResourceFromDraft(activeResourceId.value, draft.value)
      if (!updated) return false
      closeDialog()
      return true
    }

    const created = await resourcesStore.createResourceFromDraft(draft.value)
    if (!created) return false
    closeDialog()
    return true
  }

  async function removeResource(resourceId) {
    const deleted = await resourcesStore.deleteResource(resourceId)
    if (!deleted) return
    if (activeResourceId.value === resourceId) {
      activeResourceId.value = null
      draft.value = resourcesStore.createEmptyDraft()
      closeDialog()
    }
  }

  return {
    resources,
    resourcesCount,
    availableResourcesCount,
    unavailableResourcesCount,
    draft,
    isDialogOpen,
    isEditing,
    startCreateResource,
    startEditResource,
    closeDialog,
    saveResource,
    removeResource,
  }
}
