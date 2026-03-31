const SPECIALITIES_SEED = [
  {
    id: 'speciality-software',
    code: 'software-eng',
    name_ar: 'هندسة البرمجيات',
    name_en: 'Software Engineering',
    is_active: true,
  },
  {
    id: 'speciality-business',
    code: 'business-admin',
    name_ar: 'إدارة الأعمال',
    name_en: 'Business Administration',
    is_active: true,
  },
  {
    id: 'speciality-networks',
    code: 'computer-networks',
    name_ar: 'الشبكات الحاسوبية',
    name_en: 'Computer Networks',
    is_active: true,
  },
]

let specialitiesDb = SPECIALITIES_SEED.map((item) => ({ ...item }))

function cloneSpeciality(speciality) {
  return { ...speciality }
}

export const specialitiesService = {
  async getSpecialities() {
    return specialitiesDb.map(cloneSpeciality)
  },

  async createSpeciality(payload) {
    const speciality = {
      ...payload,
      id: `speciality-${Date.now()}`,
    }
    specialitiesDb = [...specialitiesDb, speciality]
    return cloneSpeciality(speciality)
  },

  async updateSpeciality(specialityId, payload) {
    let updatedSpeciality = null
    specialitiesDb = specialitiesDb.map((item) => {
      if (item.id !== specialityId) return item
      updatedSpeciality = { ...item, ...payload }
      return updatedSpeciality
    })
    return updatedSpeciality ? cloneSpeciality(updatedSpeciality) : null
  },

  async deleteSpeciality(specialityId) {
    const before = specialitiesDb.length
    specialitiesDb = specialitiesDb.filter((item) => item.id !== specialityId)
    return specialitiesDb.length < before
  },
}
