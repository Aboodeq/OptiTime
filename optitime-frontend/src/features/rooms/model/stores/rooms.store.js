import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { roomsService } from '@/features/rooms/api/rooms.service'

const ROOM_STATUS_OPTIONS = Object.freeze(['available', 'maintenance', 'unavailable'])
const ROOM_TYPE_OPTIONS = Object.freeze(['class', 'lab', 'hall'])

function createEmptyDraft(type = '') {
  return {
    name_ar: '',
    name_en: '',
    type,
    capacity: '',
    resource_ids: [],
    location_ar: '',
    location_en: '',
    status: 'available',
    notes_ar: '',
    notes_en: '',
  }
}

export const useRoomsStore = defineStore('rooms', () => {
  const rooms = ref([])
  const initialized = ref(false)

  const roomsCount = computed(() => rooms.value.length)
  const availableRoomsCount = computed(
    () => rooms.value.filter((room) => room.status === 'available').length,
  )
  const unavailableRoomsCount = computed(() => roomsCount.value - availableRoomsCount.value)

  async function ensureInitialized() {
    if (initialized.value) return
    rooms.value = await roomsService.getRooms()
    initialized.value = true
  }

  function resetCache() {
    rooms.value = []
    initialized.value = false
  }

  function buildDraftFromRoom(room) {
    return {
      name_ar: room.name_ar ?? '',
      name_en: room.name_en ?? '',
      type: room.type ?? '',
      capacity: room.capacity ?? '',
      resource_ids: Array.isArray(room.resource_ids) ? [...room.resource_ids] : [],
      location_ar: room.location_ar ?? '',
      location_en: room.location_en ?? '',
      status: room.status ?? 'available',
      notes_ar: room.notes_ar ?? '',
      notes_en: room.notes_en ?? '',
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
    const capacity = Number(draft.capacity)
    const resourceIds = Array.isArray(draft.resource_ids)
      ? [...new Set(draft.resource_ids.filter((item) => typeof item === 'string' && item.trim()))]
      : []

    if (!nameAr || !nameEn || !type) return null
    if (!Number.isFinite(capacity) || capacity <= 0) return null
    if (!ROOM_STATUS_OPTIONS.includes(status)) return null
    if (!ROOM_TYPE_OPTIONS.includes(type)) return null

    return {
      name_ar: nameAr,
      name_en: nameEn,
      type,
      capacity,
      resource_ids: resourceIds,
      location_ar: locationAr,
      location_en: locationEn,
      status,
      notes_ar: notesAr,
      notes_en: notesEn,
    }
  }

  async function createRoomFromDraft(draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const created = await roomsService.createRoom(normalized)
    rooms.value = [...rooms.value, created]
    return true
  }

  async function updateRoomFromDraft(roomId, draft) {
    const normalized = normalizeDraft(draft)
    if (!normalized) return false
    const updated = await roomsService.updateRoom(roomId, normalized)
    if (!updated) return false
    rooms.value = rooms.value.map((item) => (item.id === roomId ? updated : item))
    return true
  }

  async function deleteRoom(roomId) {
    const deleted = await roomsService.deleteRoom(roomId)
    if (!deleted) return false
    rooms.value = rooms.value.filter((item) => item.id !== roomId)
    return true
  }

  return {
    rooms,
    roomsCount,
    availableRoomsCount,
    unavailableRoomsCount,
    ensureInitialized,
    createEmptyDraft,
    buildDraftFromRoom,
    createRoomFromDraft,
    updateRoomFromDraft,
    deleteRoom,
    resetCache,
  }
})
