import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { resourcesService } from '@/features/resources/api/resources.service'

const RESOURCE_STATUS_OPTIONS = Object.freeze(['available', 'maintenance', 'unavailable'])

function createEmptyDraft() {
  return {
    name_ar: '',
    name_en: '',
    type: '',
    quantity: '',
    location_ar: '',
    location_en: '',
    status: 'available',
    notes_ar: '',
    notes_en: '',
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

  function resetCache() {
    resources.value = []
    initialized.value = false
  }

  function buildDraftFromResource(resource) {
    return {
      name_ar: resource.name_ar ?? '',
      name_en: resource.name_en ?? '',
      type: resource.type,
      quantity: resource.quantity ?? '',
      location_ar: resource.location_ar ?? '',
      location_en: resource.location_en ?? '',
      status: resource.status ?? 'available',
      notes_ar: resource.notes_ar ?? '',
      notes_en: resource.notes_en ?? '',
    }
  }

  function normalizeDraft(draft) {
    const nameAr = draft.name_ar?.trim() || ''
    const nameEn = draft.name_en?.trim() || ''
    const type = draft.type?.trim().toLowerCase() || ''
    const locationAr = draft.location_ar?.trim() || ''
    const locationEn = draft.location_en?.trim() || ''
    const notesAr = draft.notes_ar?.trim() || ''
    const notesEn = draft.notes_en?.trim() || ''
    const status = draft.status?.trim() || ''
    const quantity = Number(draft.quantity)

    if (!nameAr || !nameEn || !type) return null
    if (!Number.isFinite(quantity) || quantity < 0) return null
    if (!RESOURCE_STATUS_OPTIONS.includes(status)) return null

    return {
      name_ar: nameAr,
      name_en: nameEn,
      type,
      quantity,
      location_ar: locationAr,
      location_en: locationEn,
      status,
      notes_ar: notesAr,
      notes_en: notesEn,
    }
  }

  async function createResourceFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const created = await resourcesService.createResource(normalized)
    resources.value = [...resources.value, created]
    return created
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
    resetCache,
  }
})
