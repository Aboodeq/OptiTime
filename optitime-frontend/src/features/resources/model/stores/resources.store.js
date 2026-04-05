import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { resourcesService } from '@/features/resources/api/resources.service'

const RESOURCE_STATUS_OPTIONS = Object.freeze(['available', 'maintenance', 'unavailable'])

function createEmptyDraft() {
  return {
    name: '',
    type: '',
    quantity: '',
    location: '',
    status: 'available',
    notes: '',
  }
}

export const useResourcesStore = defineStore('resources', () => {
  const resources = ref([])
  const initialized = ref(false)

  const resourcesCount = computed(() => resources.value.length)
  const availableResourcesCount = computed(
    () => resources.value.filter((resource) => resource.status === 'available').length,
  )
  const unavailableResourcesCount = computed(
    () => resourcesCount.value - availableResourcesCount.value,
  )

  async function ensureInitialized() {
    if (initialized.value) return
    resources.value = await resourcesService.getResources()
    initialized.value = true
  }

  function buildDraftFromResource(resource) {
    return {
      name: resource.name,
      type: resource.type,
      quantity: resource.quantity ?? '',
      location: resource.location ?? '',
      status: resource.status ?? 'available',
      notes: resource.notes ?? '',
    }
  }

  function normalizeDraft(draft) {
    const name = draft.name?.trim() || ''
    const type = draft.type?.trim().toLowerCase() || ''
    const location = draft.location?.trim() || ''
    const notes = draft.notes?.trim() || ''
    const status = draft.status?.trim() || ''
    const quantity = Number(draft.quantity)

    if (!name || !type) return null
    if (!Number.isFinite(quantity) || quantity < 0) return null
    if (!RESOURCE_STATUS_OPTIONS.includes(status)) return null

    return {
      name,
      type,
      quantity,
      location,
      status,
      notes,
    }
  }

  async function createResourceFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const created = await resourcesService.createResource(normalized)
    resources.value = [...resources.value, created]
    return true
  }

  async function updateResourceFromDraft(resourceId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const updated = await resourcesService.updateResource(resourceId, normalized)
    if (!updated) return false
    resources.value = resources.value.map((item) => (item.id === resourceId ? updated : item))
    return true
  }

  async function deleteResource(resourceId) {
    const deleted = await resourcesService.deleteResource(resourceId)
    if (!deleted) return false
    resources.value = resources.value.filter((item) => item.id !== resourceId)
    return true
  }

  return {
    resources,
    resourcesCount,
    availableResourcesCount,
    unavailableResourcesCount,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromResource,
    createResourceFromDraft,
    updateResourceFromDraft,
    deleteResource,
  }
})
