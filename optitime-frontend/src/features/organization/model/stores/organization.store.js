import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { organizationService } from '@/features/organization/api/organization.service'

function createEmptyFacultyDraft() {
  return {
    code: '',
    name_ar: '',
    name_en: '',
    graduation_hours: 0,
    studying_level: 4,
    color: '#4361ee',
    icon_url: '',
    is_active: true,
  }
}

function createEmptyDepartmentDraft() {
  return {
    code: '',
    name_ar: '',
    name_en: '',
    icon_url: '',
    is_active: true,
  }
}

function normalizeFacultyDraft(draft) {
  const code = draft.code.trim().toLowerCase()
  const nameAr = draft.name_ar.trim()
  const nameEn = draft.name_en.trim()
  const graduationHours = Number.parseInt(draft.graduation_hours, 10)
  const studyingLevel = Number.parseInt(draft.studying_level, 10)

  if (
    !code ||
    !nameAr ||
    !nameEn ||
    Number.isNaN(graduationHours) ||
    graduationHours < 0 ||
    Number.isNaN(studyingLevel) ||
    studyingLevel < 1
  )
    return null

  return {
    code,
    name_ar: nameAr,
    name_en: nameEn,
    graduation_hours: graduationHours,
    studying_level: studyingLevel,
    color: typeof draft.color === 'string' && draft.color ? draft.color : '#4361ee',
    icon_url: typeof draft.icon_url === 'string' ? draft.icon_url : '',
    is_active: Boolean(draft.is_active),
  }
}

function normalizeDepartmentDraft(draft) {
  const code = draft.code.trim().toLowerCase()
  const nameAr = draft.name_ar.trim()
  const nameEn = draft.name_en.trim()

  if (!code || !nameAr || !nameEn) return null

  return {
    code,
    name_ar: nameAr,
    name_en: nameEn,
    icon_url: typeof draft.icon_url === 'string' ? draft.icon_url : '',
    is_active: Boolean(draft.is_active),
  }
}

export const useOrganizationStore = defineStore('organization', () => {
  const faculties = ref([])
  const initialized = ref(false)

  const facultiesCount = computed(() => faculties.value.length)
  const departmentsCount = computed(() =>
    faculties.value.reduce((sum, faculty) => sum + faculty.departments.length, 0),
  )
  const activeFacultiesCount = computed(
    () => faculties.value.filter((faculty) => faculty.is_active).length,
  )
  const activeDepartmentsCount = computed(() =>
    faculties.value.reduce(
      (sum, faculty) =>
        sum + faculty.departments.filter((department) => department.is_active).length,
      0,
    ),
  )

  async function ensureInitialized() {
    if (initialized.value) return
    faculties.value = await organizationService.getFaculties()
    initialized.value = true
  }

  function buildFacultyDraftFromItem(faculty) {
    return {
      code: faculty.code,
      name_ar: faculty.name_ar,
      name_en: faculty.name_en,
      graduation_hours: Number.isFinite(faculty.graduation_hours) ? faculty.graduation_hours : 0,
      studying_level: Number.isFinite(faculty.studying_level) ? faculty.studying_level : 4,
      color: faculty.color ?? '#4361ee',
      icon_url: faculty.icon_url ?? '',
      is_active: Boolean(faculty.is_active),
    }
  }

  function buildDepartmentDraftFromItem(department) {
    return {
      code: department.code,
      name_ar: department.name_ar,
      name_en: department.name_en,
      icon_url: department.icon_url ?? '',
      is_active: Boolean(department.is_active),
    }
  }

  function createFacultyFromDraft(draft) {
    const normalized = normalizeFacultyDraft(draft)
    if (!normalized) return false

    faculties.value = [
      ...faculties.value,
      {
        id: `faculty-${normalized.code}-${Date.now()}`,
        ...normalized,
        departments: [],
      },
    ]
    return true
  }

  function updateFacultyFromDraft(facultyId, draft) {
    const normalized = normalizeFacultyDraft(draft)
    if (!normalized) return false

    faculties.value = faculties.value.map((faculty) =>
      faculty.id === facultyId ? { ...faculty, ...normalized } : faculty,
    )
    return true
  }

  function deleteFaculty(facultyId) {
    faculties.value = faculties.value.filter((faculty) => faculty.id !== facultyId)
  }

  function createDepartmentFromDraft(facultyId, draft) {
    const normalized = normalizeDepartmentDraft(draft)
    if (!normalized) return false

    faculties.value = faculties.value.map((faculty) =>
      faculty.id === facultyId
        ? {
            ...faculty,
            departments: [
              ...faculty.departments,
              {
                id: `department-${normalized.code}-${Date.now()}`,
                ...normalized,
              },
            ],
          }
        : faculty,
    )
    return true
  }

  function updateDepartmentFromDraft(facultyId, departmentId, draft) {
    const normalized = normalizeDepartmentDraft(draft)
    if (!normalized) return false

    faculties.value = faculties.value.map((faculty) =>
      faculty.id === facultyId
        ? {
            ...faculty,
            departments: faculty.departments.map((department) =>
              department.id === departmentId ? { ...department, ...normalized } : department,
            ),
          }
        : faculty,
    )
    return true
  }

  function deleteDepartment(facultyId, departmentId) {
    faculties.value = faculties.value.map((faculty) =>
      faculty.id === facultyId
        ? {
            ...faculty,
            departments: faculty.departments.filter((department) => department.id !== departmentId),
          }
        : faculty,
    )
  }

  return {
    faculties,
    facultiesCount,
    departmentsCount,
    activeFacultiesCount,
    activeDepartmentsCount,
    ensureInitialized,
    createEmptyFacultyDraft,
    createEmptyDepartmentDraft,
    buildFacultyDraftFromItem,
    buildDepartmentDraftFromItem,
    createFacultyFromDraft,
    updateFacultyFromDraft,
    deleteFaculty,
    createDepartmentFromDraft,
    updateDepartmentFromDraft,
    deleteDepartment,
  }
})
