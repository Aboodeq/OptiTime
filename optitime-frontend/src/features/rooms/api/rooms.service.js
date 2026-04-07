const ROOMS_SEED = [
  {
    id: 'room-1',
    name_ar: 'قاعة A101',
    name_en: 'Room A101',
    type: 'class',
    capacity: 45,
    resource_ids: ['resource-1'],
    location_ar: 'المبنى A - الطابق 1',
    location_en: 'Building A - Floor 1',
    status: 'available',
    notes_ar: 'توجد سبورة ذكية',
    notes_en: 'Smart board installed',
  },
  {
    id: 'room-2',
    name_ar: 'مختبر B2',
    name_en: 'Lab B2',
    type: 'lab',
    capacity: 28,
    resource_ids: ['resource-2', 'resource-3'],
    location_ar: 'المبنى B - الطابق 2',
    location_en: 'Building B - Floor 2',
    status: 'available',
    notes_ar: 'مختبر شبكات',
    notes_en: 'Networking lab',
  },
  {
    id: 'room-3',
    name_ar: 'المدرج الرئيسي',
    name_en: 'Main Hall',
    type: 'hall',
    capacity: 180,
    resource_ids: ['resource-1'],
    location_ar: 'المبنى الرئيسي',
    location_en: 'Main Building',
    status: 'maintenance',
    notes_ar: 'تجري صيانة جهاز العرض',
    notes_en: 'Projector maintenance in progress',
  },
]

let roomsDb = ROOMS_SEED.map((item) => ({ ...item }))

function cloneRoom(room) {
  return {
    ...room,
    resource_ids: Array.isArray(room.resource_ids) ? [...room.resource_ids] : [],
  }
}

export const roomsService = {
  async getRooms() {
    return roomsDb.map(cloneRoom)
  },

  async createRoom(payload) {
    const room = {
      ...payload,
      id: `room-${Date.now()}`,
    }
    roomsDb = [...roomsDb, room]
    return cloneRoom(room)
  },

  async updateRoom(roomId, payload) {
    let updatedRoom = null
    roomsDb = roomsDb.map((item) => {
      if (item.id !== roomId) return item
      updatedRoom = {
        ...item,
        ...payload,
      }
      return updatedRoom
    })
    return updatedRoom ? cloneRoom(updatedRoom) : null
  },

  async deleteRoom(roomId) {
    const before = roomsDb.length
    roomsDb = roomsDb.filter((item) => item.id !== roomId)
    return roomsDb.length < before
  },
}
