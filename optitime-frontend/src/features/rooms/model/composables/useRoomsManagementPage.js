import { storeToRefs } from 'pinia'
import { computed, ref } from 'vue'
import { useResourcesStore } from '@/features/resources/model/stores/resources.store'
import { useRoomsStore } from '@/features/rooms/model/stores/rooms.store'

export function useRoomsManagementPage(options = {}) {
  const { typeFilter = null, defaultType = '' } = options
  const roomsStore = useRoomsStore()
  const resourcesStore = useResourcesStore()
  roomsStore.ensureInitialized()
  resourcesStore.ensureInitialized()

  const { rooms, roomsCount, availableRoomsCount, unavailableRoomsCount } = storeToRefs(roomsStore)
  const { resources } = storeToRefs(resourcesStore)
  const activeRoomId = ref(null)
  const draft = ref(roomsStore.createEmptyDraft(defaultType))
  const isDialogOpen = ref(false)

  const isEditing = computed(() => Boolean(activeRoomId.value))
  const managedRooms = computed(() => {
    if (!typeFilter) return rooms.value
    return rooms.value.filter((room) => room.type === typeFilter)
  })
  const managedRoomsCount = computed(() => managedRooms.value.length)
  const managedAvailableRoomsCount = computed(
    () => managedRooms.value.filter((room) => room.status === 'available').length,
  )
  const managedUnavailableRoomsCount = computed(
    () => managedRoomsCount.value - managedAvailableRoomsCount.value,
  )
  const resourceOptions = computed(() =>
    resources.value.map((resource) => ({
      value: resource.id,
      label: resource.name,
    })),
  )

  function applyTypeRules(nextDraft) {
    if (!typeFilter) return nextDraft
    return {
      ...nextDraft,
      type: typeFilter,
    }
  }

  function startCreateRoom() {
    activeRoomId.value = null
    draft.value = applyTypeRules(roomsStore.createEmptyDraft(defaultType))
    isDialogOpen.value = true
  }

  function startEditRoom(room) {
    activeRoomId.value = room.id
    draft.value = applyTypeRules(roomsStore.buildDraftFromRoom(room))
    isDialogOpen.value = true
  }

  function closeDialog() {
    isDialogOpen.value = false
  }

  async function saveRoom() {
    const payload = applyTypeRules(draft.value)
    if (activeRoomId.value) {
      const updated = await roomsStore.updateRoomFromDraft(activeRoomId.value, payload)
      if (!updated) return false
      closeDialog()
      return true
    }

    const created = await roomsStore.createRoomFromDraft(payload)
    if (!created) return false
    closeDialog()
    return true
  }

  async function removeRoom(roomId) {
    const deleted = await roomsStore.deleteRoom(roomId)
    if (!deleted) return
    if (activeRoomId.value === roomId) {
      activeRoomId.value = null
      draft.value = roomsStore.createEmptyDraft(defaultType)
      closeDialog()
    }
  }

  return {
    rooms,
    roomsCount,
    availableRoomsCount,
    unavailableRoomsCount,
    managedRooms,
    managedRoomsCount,
    managedAvailableRoomsCount,
    managedUnavailableRoomsCount,
    resourceOptions,
    draft,
    isDialogOpen,
    isEditing,
    startCreateRoom,
    startEditRoom,
    closeDialog,
    saveRoom,
    removeRoom,
  }
}
