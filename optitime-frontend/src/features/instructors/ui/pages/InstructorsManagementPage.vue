<template>
  <AppShell :page-title="t('routes.instructorsManagement')">
    <section class="dashboard-card w-100">
      <div class="instructors-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.instructorsManagement.title') }}</h1>
        <AppCan permission="instructors.create">
          <button class="new-instructor-btn" type="button" @click="startCreateInstructor">
            + {{ t('pages.instructorsManagement.actions.newInstructor') }}
          </button>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.instructorsManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <div class="instructors-table-card">
        <AppDataTable
          :columns="tableColumns"
          :rows="filteredInstructors"
          row-key="id"
          :empty-text="t('pages.instructorsManagement.table.empty')"
          :show-search="canViewInstructors"
          :search-value="search"
          :search-placeholder="t('nav.topbar.quickSearch')"
          @update:search-value="search = $event"
        >
          <template #cell-instructor="{ row }">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ row.name }}</span>
              <small class="text-secondary">{{ row.email }}</small>
            </div>
          </template>

          <template #cell-speciality="{ row }">
            {{ row.speciality_name || '-' }}
          </template>

          <template #cell-faculty="{ row }">
            {{ row.faculty_name || '-' }}
          </template>

          <template #cell-department="{ row }">
            {{ row.department_name || '-' }}
          </template>

          <template #cell-workhours="{ row }">
            {{ row.min_work_hours_per_week }} - {{ row.max_work_hours_per_week }}
          </template>

          <template #cell-status="{ row }">
            <span :class="row.is_active ? 'text-success' : 'text-secondary'">
              {{
                row.is_active
                  ? t('pages.instructorsManagement.status.active')
                  : t('pages.instructorsManagement.status.inactive')
              }}
            </span>
          </template>

          <template #cell-actions="{ row }">
            <div class="text-start">
              <AppCan permission="instructors.update">
                <button
                  class="btn btn-sm btn-outline-primary me-2"
                  :title="t('pages.instructorsManagement.actions.edit')"
                  :aria-label="t('pages.instructorsManagement.actions.edit')"
                  @click="startEditInstructor(row)"
                >
                  <i class="bi bi-pencil-square"></i>
                </button>
              </AppCan>
              <AppCan permission="instructors.delete">
                <button
                  class="btn btn-sm btn-outline-danger"
                  :title="t('pages.instructorsManagement.actions.delete')"
                  :aria-label="t('pages.instructorsManagement.actions.delete')"
                  @click="requestDeleteInstructor(row)"
                >
                  <i class="bi bi-trash3"></i>
                </button>
              </AppCan>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>

    <InstructorFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :speciality-options="specialityOptions"
      :faculty-options="facultyOptions"
      :department-options="departmentOptions"
      :can-create-entities="canCreateOrganization"
      :can-create-specialities="canCreateSpecialities"
      :can-edit="canCreateInstructors || canUpdateInstructors"
      @cancel="closeDialog"
      @save="handleSaveInstructor"
      @update:name="draft.name = $event"
      @update:email="draft.email = $event"
      @update:password="draft.password = $event"
      @update:speciality-id="setSpecialityId($event)"
      @update:min-work-hours-per-week="draft.min_work_hours_per_week = $event"
      @update:max-work-hours-per-week="draft.max_work_hours_per_week = $event"
      @create:speciality="openCreateSpecialityDialog($event)"
      @update:faculty-id="setFacultyId($event)"
      @update:department-id="setDepartmentId($event)"
      @create:faculty="openCreateFacultyDialog($event)"
      @create:department="openCreateDepartmentDialog($event)"
      @update:is-active="draft.is_active = $event"
    />

    <SpecialityFormDialog
      :open="specialityDialogOpen"
      :is-editing="false"
      :draft="specialityDraft"
      :can-edit="canCreateSpecialities"
      @cancel="closeSpecialityDialog"
      @save="handleSaveSpeciality"
      @update:code="specialityDraft.code = $event"
      @update:name-ar="specialityDraft.name_ar = $event"
      @update:name-en="specialityDraft.name_en = $event"
      @update:is-active="specialityDraft.is_active = $event"
    />

    <FacultyFormDialog
      :open="facultyDialogOpen"
      :is-editing="false"
      :draft="facultyDraft"
      :can-edit="canCreateOrganization"
      @cancel="closeFacultyDialog"
      @save="handleSaveFaculty"
      @update:code="facultyDraft.code = $event"
      @update:name-ar="facultyDraft.name_ar = $event"
      @update:name-en="facultyDraft.name_en = $event"
      @update:color="facultyDraft.color = $event"
      @update:icon-url="facultyDraft.icon_url = $event"
      @update:is-active="facultyDraft.is_active = $event"
    />

    <DepartmentFormDialog
      :open="departmentDialogOpen"
      :is-editing="false"
      :draft="departmentDraft"
      :can-edit="canCreateOrganization"
      @cancel="closeDepartmentDialog"
      @save="handleSaveDepartment"
      @update:code="departmentDraft.code = $event"
      @update:name-ar="departmentDraft.name_ar = $event"
      @update:name-en="departmentDraft.name_en = $event"
      @update:icon-url="departmentDraft.icon_url = $event"
      @update:is-active="departmentDraft.is_active = $event"
    />

    <AppConfirmDialog
      :open="Boolean(instructorPendingDelete)"
      :title="t('pages.instructorsManagement.confirmDelete.title')"
      :message="
        t('pages.instructorsManagement.confirmDelete.message', {
          instructor: instructorPendingDelete?.name ?? '',
        })
      "
      :confirm-text="t('pages.instructorsManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.instructorsManagement.confirmDelete.cancel')"
      @cancel="instructorPendingDelete = null"
      @confirm="confirmDeleteInstructor"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import AppCan from '@/components/common/AppCan.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useInstructorsManagementPage } from '@/features/instructors/model/composables/useInstructorsManagementPage'
import InstructorFormDialog from '@/features/instructors/ui/components/InstructorFormDialog.vue'
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'
import DepartmentFormDialog from '@/features/organization/ui/components/DepartmentFormDialog.vue'
import FacultyFormDialog from '@/features/organization/ui/components/FacultyFormDialog.vue'
import { useSpecialitiesStore } from '@/features/specialities/model/stores/specialities.store'
import SpecialityFormDialog from '@/features/specialities/ui/components/SpecialityFormDialog.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const {
  instructors,
  faculties,
  specialities,
  facultyOptions,
  departmentOptions,
  specialityOptions,
  instructorsCount,
  activeInstructorsCount,
  inactiveInstructorsCount,
  draft,
  isDialogOpen,
  isEditing,
  startCreateInstructor,
  startEditInstructor,
  closeDialog,
  reopenDialog,
  saveInstructor,
  removeInstructor,
  setFacultyId,
  setDepartmentId,
  setSpecialityId,
} = useInstructorsManagementPage()
const organizationStore = useOrganizationStore()
organizationStore.ensureInitialized()
const specialitiesStore = useSpecialitiesStore()
specialitiesStore.ensureInitialized()

const search = ref('')
const instructorPendingDelete = ref(null)
const shouldReturnToDialog = ref(false)
const facultyDialogOpen = ref(false)
const facultyDraft = ref(organizationStore.createEmptyFacultyDraft())
const departmentDialogOpen = ref(false)
const departmentDraft = ref(organizationStore.createEmptyDepartmentDraft())
const specialityDialogOpen = ref(false)
const specialityDraft = ref(specialitiesStore.createEmptyDraft())

const canViewInstructors = computed(() => authStore.hasPermission('instructors.view'))
const canCreateInstructors = computed(() => authStore.hasPermission('instructors.create'))
const canUpdateInstructors = computed(() => authStore.hasPermission('instructors.update'))
const canDeleteInstructors = computed(() => authStore.hasPermission('instructors.delete'))
const canCreateSpecialities = computed(() => authStore.hasPermission('specialities.create'))
const canCreateOrganization = computed(() => authStore.hasPermission('organization.create'))
const specialityNameById = computed(() => {
  const map = new Map()
  specialities.value.forEach((speciality) => {
    map.set(speciality.id, speciality.name_en)
  })
  return map
})
const facultyNameById = computed(() => {
  const map = new Map()
  faculties.value.forEach((faculty) => {
    map.set(faculty.id, faculty.name_en)
  })
  return map
})
const departmentNameById = computed(() => {
  const map = new Map()
  faculties.value.forEach((faculty) => {
    faculty.departments.forEach((department) => {
      map.set(department.id, department.name_en)
    })
  })
  return map
})

const filteredInstructors = computed(() => {
  if (!canViewInstructors.value) return []
  const q = search.value.trim().toLowerCase()
  const rows = instructors.value.map((instructor) => ({
    ...instructor,
    speciality_name: specialityNameById.value.get(instructor.speciality_id) ?? '',
    faculty_name: facultyNameById.value.get(instructor.faculty_id) ?? '',
    department_name: departmentNameById.value.get(instructor.department_id) ?? '',
  }))
  if (!q) return rows
  return rows.filter(
    (instructor) =>
      instructor.name.toLowerCase().includes(q) ||
      instructor.email.toLowerCase().includes(q) ||
      instructor.speciality_name.toLowerCase().includes(q) ||
      instructor.faculty_name.toLowerCase().includes(q) ||
      instructor.department_name.toLowerCase().includes(q),
  )
})

const statsCards = computed(() => [
  {
    id: 'instructors',
    value: instructorsCount.value,
    label: t('pages.instructorsManagement.stats.totalInstructors'),
    icon: 'bi bi-person-workspace',
    iconColor: '#8b5cf6',
  },
  {
    id: 'active',
    value: activeInstructorsCount.value,
    label: t('pages.instructorsManagement.stats.activeInstructors'),
    icon: 'bi bi-person-check',
    iconColor: '#22c55e',
  },
  {
    id: 'inactive',
    value: inactiveInstructorsCount.value,
    label: t('pages.instructorsManagement.stats.inactiveInstructors'),
    icon: 'bi bi-person-dash',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'instructor', label: t('pages.instructorsManagement.table.instructor') },
  { key: 'speciality', label: t('pages.instructorsManagement.table.speciality') },
  { key: 'faculty', label: t('pages.instructorsManagement.table.faculty') },
  { key: 'department', label: t('pages.instructorsManagement.table.department') },
  { key: 'workhours', label: t('pages.instructorsManagement.table.workHoursPerWeek') },
  { key: 'status', label: t('pages.instructorsManagement.table.status') },
  ...(canUpdateInstructors.value || canDeleteInstructors.value
    ? [{ key: 'actions', label: t('pages.instructorsManagement.table.actions') }]
    : []),
])

function requestDeleteInstructor(instructor) {
  if (!canDeleteInstructors.value) return
  instructorPendingDelete.value = instructor
}

async function confirmDeleteInstructor() {
  if (!canDeleteInstructors.value) return
  if (!instructorPendingDelete.value) return
  await removeInstructor(instructorPendingDelete.value.id)
  instructorPendingDelete.value = null
}

async function handleSaveInstructor() {
  if (isEditing.value && !canUpdateInstructors.value) return
  if (!isEditing.value && !canCreateInstructors.value) return
  const saved = await saveInstructor()
  if (!saved) {
    toast.error(t('pages.instructorsManagement.errors.invalidForm'))
  }
}

function toCode(value, fallbackPrefix) {
  const base = value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
  return base || `${fallbackPrefix}-${Date.now()}`
}

function openCreateFacultyDialog(suggestedName) {
  if (!canCreateOrganization.value) return
  shouldReturnToDialog.value = isDialogOpen.value
  closeDialog()
  const name = (suggestedName || '').trim()
  facultyDraft.value = {
    ...organizationStore.createEmptyFacultyDraft(),
    code: toCode(name, 'faculty'),
    name_en: name,
    name_ar: name,
  }
  facultyDialogOpen.value = true
}

function closeFacultyDialog() {
  facultyDialogOpen.value = false
  if (shouldReturnToDialog.value) {
    reopenDialog()
    shouldReturnToDialog.value = false
  }
}

function handleSaveFaculty() {
  if (!canCreateOrganization.value) return
  const created = organizationStore.createFacultyFromDraft(facultyDraft.value)
  if (!created) return
  const added = faculties.value[faculties.value.length - 1]
  if (added) setFacultyId(added.id)
  closeFacultyDialog()
}

function openCreateDepartmentDialog(suggestedName) {
  if (!canCreateOrganization.value || !draft.value.faculty_id) return
  shouldReturnToDialog.value = isDialogOpen.value
  closeDialog()
  const name = (suggestedName || '').trim()
  departmentDraft.value = {
    ...organizationStore.createEmptyDepartmentDraft(),
    code: toCode(name, 'department'),
    name_en: name,
    name_ar: name,
  }
  departmentDialogOpen.value = true
}

function closeDepartmentDialog() {
  departmentDialogOpen.value = false
  if (shouldReturnToDialog.value) {
    reopenDialog()
    shouldReturnToDialog.value = false
  }
}

function handleSaveDepartment() {
  if (!canCreateOrganization.value || !draft.value.faculty_id) return
  const created = organizationStore.createDepartmentFromDraft(
    draft.value.faculty_id,
    departmentDraft.value,
  )
  if (!created) return
  const selectedFaculty = faculties.value.find((faculty) => faculty.id === draft.value.faculty_id)
  const added = selectedFaculty?.departments[selectedFaculty.departments.length - 1]
  if (added) setDepartmentId(added.id)
  closeDepartmentDialog()
}

function openCreateSpecialityDialog(suggestedName) {
  if (!canCreateSpecialities.value) return
  shouldReturnToDialog.value = isDialogOpen.value
  closeDialog()
  const name = (suggestedName || '').trim()
  specialityDraft.value = {
    ...specialitiesStore.createEmptyDraft(),
    code: toCode(name, 'speciality'),
    name_ar: name,
    name_en: name,
  }
  specialityDialogOpen.value = true
}

function closeSpecialityDialog() {
  specialityDialogOpen.value = false
  if (shouldReturnToDialog.value) {
    reopenDialog()
    shouldReturnToDialog.value = false
  }
}

async function handleSaveSpeciality() {
  if (!canCreateSpecialities.value) return
  const created = await specialitiesStore.createSpecialityFromDraft(specialityDraft.value)
  if (!created) return
  const added = specialities.value[specialities.value.length - 1]
  if (added) setSpecialityId(added.id)
  closeSpecialityDialog()
}
</script>

<style scoped>
.instructors-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-instructor-btn {
  background: linear-gradient(135deg, #0f766e, #0d9488);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 0.6rem 1rem;
  font-weight: 700;
}

.instructors-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}
</style>
