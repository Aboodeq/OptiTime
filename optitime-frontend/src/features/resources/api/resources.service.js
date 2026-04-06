const RESOURCES_SEED = [
  {
    id: 'resource-1',
    name_ar: 'جهاز عرض المدرج الرئيسي',
    name_en: 'Main Hall Projector',
    type: 'projector',
    quantity: 2,
    location_ar: 'المدرج الرئيسي',
    location_en: 'Main Hall',
    status: 'available',
    notes_ar: 'يدعم 4K',
    notes_en: '4K ready',
  },
  {
    id: 'resource-2',
    name_ar: 'مجموعة حواسيب المخبر',
    name_en: 'Lab Desktop Set',
    type: 'computer',
    quantity: 24,
    location_ar: 'المخبر A',
    location_en: 'Lab A',
    status: 'available',
    notes_ar: 'ويندوز 11',
    notes_en: 'Windows 11',
  },
  {
    id: 'resource-3',
    name_ar: 'قرص SSD خارجي',
    name_en: 'External SSD',
    type: 'disk',
    quantity: 12,
    location_ar: 'مستودع تقنية المعلومات',
    location_en: 'IT Storage',
    status: 'maintenance',
    notes_ar: 'يحتاج فحص حالة',
    notes_en: 'Need health check',
  },
]

let resourcesDb = RESOURCES_SEED.map((item) => ({ ...item }))

function cloneResource(resource) {
  return { ...resource }
}

export const resourcesService = {
  async getResources() {
    return resourcesDb.map(cloneResource)
  },

  async createResource(payload) {
    const resource = {
      ...payload,
      id: `resource-${Date.now()}`,
    }
    resourcesDb = [...resourcesDb, resource]
    return cloneResource(resource)
  },

  async updateResource(resourceId, payload) {
    let updatedResource = null
    resourcesDb = resourcesDb.map((item) => {
      if (item.id !== resourceId) return item
      updatedResource = {
        ...item,
        ...payload,
      }
      return updatedResource
    })
    return updatedResource ? cloneResource(updatedResource) : null
  },

  async deleteResource(resourceId) {
    const before = resourcesDb.length
    resourcesDb = resourcesDb.filter((item) => item.id !== resourceId)
    return resourcesDb.length < before
  },
}
