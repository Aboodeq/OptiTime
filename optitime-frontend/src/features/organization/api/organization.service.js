const INITIAL_FACULTIES = Object.freeze([
  {
    id: 'faculty-informatics',
    code: 'informatics',
    name_ar: 'كلية الهندسة المعلوماتية',
    name_en: 'Faculty of Informatics Engineering',
    color: '#4361ee',
    icon_url: '',
    is_active: true,
    departments: [
      {
        id: 'dept-software',
        code: 'software',
        name_ar: 'قسم هندسة البرمجيات',
        name_en: 'Software Engineering Department',
        icon_url: '',
        is_active: true,
      },
      {
        id: 'dept-networks',
        code: 'networks',
        name_ar: 'قسم الشبكات',
        name_en: 'Networks Department',
        icon_url: '',
        is_active: true,
      },
    ],
  },
  {
    id: 'faculty-business',
    code: 'business',
    name_ar: 'كلية إدارة الأعمال',
    name_en: 'Faculty of Business Administration',
    color: '#0f766e',
    icon_url: '',
    is_active: true,
    departments: [
      {
        id: 'dept-finance',
        code: 'finance',
        name_ar: 'قسم التمويل',
        name_en: 'Finance Department',
        icon_url: '',
        is_active: true,
      },
    ],
  },
])

function cloneFaculties() {
  return INITIAL_FACULTIES.map((faculty) => ({
    ...faculty,
    departments: faculty.departments.map((department) => ({ ...department })),
  }))
}

export const organizationService = {
  async getFaculties() {
    return cloneFaculties()
  },
}
