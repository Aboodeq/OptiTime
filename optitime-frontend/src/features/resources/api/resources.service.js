const RESOURCES_SEED = [
  {
    id: 'resource-1',
    name: 'Main Hall Projector',
    type: 'projector',
    quantity: 2,
    location: 'Main Hall',
    status: 'available',
    notes: '4K ready',
  },
  {
    id: 'resource-2',
    name: 'Lab Desktop Set',
    type: 'computer',
    quantity: 24,
    location: 'Lab A',
    status: 'available',
    notes: 'Windows 11',
  },
  {
    id: 'resource-3',
    name: 'External SSD',
    type: 'disk',
    quantity: 12,
    location: 'IT Storage',
    status: 'maintenance',
    notes: 'Need health check',
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
