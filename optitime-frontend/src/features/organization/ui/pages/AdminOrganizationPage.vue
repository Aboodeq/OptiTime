<template>
  <AppShell :page-title="t('routes.organizationManagement')">
    <section class="dashboard-card w-100">
      <div class="organization-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.organization.title') }}</h1>
        <AppCan permission="faculties.create">
          <AppButton
            class="new-entity-btn"
            type="button"
            :tone-color="authStore.roleColor"
            @click="startCreateFaculty"
          >
            + {{ t('pages.organization.actions.newFaculty') }}
          </AppButton>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.organization.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <AppEntityGrid
        class="mb-3"
        :items="filteredFaculties"
        :show-search="canViewOrganization"
        :search-value="search"
        :search-placeholder="t('nav.topbar.quickSearch')"
        :empty-text="t('pages.organization.faculties.empty')"
        min-item-width="340px"
        @update:search-value="search = $event"
      >
        <template #item="{ item: faculty }">
          <OrganizationFacultyCard
            :faculty="faculty"
            :role-color="authStore.roleColor"
            :preview-departments="getDepartmentPreview(faculty)"
            :hidden-departments-count="getHiddenDepartmentsCount(faculty)"
            @add-department="startCreateDepartment(faculty.id)"
            @edit-faculty="startEditFaculty(faculty)"
            @delete-faculty="requestDeleteFaculty(faculty)"
            @edit-department="startEditDepartment(faculty.id, $event)"
            @delete-department="requestDeleteDepartmentFromFaculty(faculty, $event)"
          />
        </template>
      </AppEntityGrid>
    </section>

    <FacultyFormDialog
      :open="facultyDialogOpen"
      :is-editing="editingFaculty"
      :draft="facultyDraft"
      :can-edit="canCreateOrganization || canUpdateOrganization"
      @cancel="closeFacultyDialog"
      @save="handleSaveFaculty"
      @update:code="facultyDraft.code = $event"
      @update:name-ar="facultyDraft.name_ar = $event"
      @update:name-en="facultyDraft.name_en = $event"
      @update:graduation-hours="facultyDraft.graduation_hours = $event"
      @update:studying-level="facultyDraft.studying_level = $event"
      @update:color="facultyDraft.color = $event"
      @update:icon-url="facultyDraft.icon_url = $event"
      @update:is-active="facultyDraft.is_active = $event"
    />

    <DepartmentFormDialog
      :open="departmentDialogOpen"
      :is-editing="editingDepartment"
      :draft="departmentDraft"
      :can-edit="canCreateOrganization || canUpdateOrganization"
      @cancel="closeDepartmentDialog"
      @save="handleSaveDepartment"
      @update:code="departmentDraft.code = $event"
      @update:name-ar="departmentDraft.name_ar = $event"
      @update:name-en="departmentDraft.name_en = $event"
      @update:icon-url="departmentDraft.icon_url = $event"
      @update:is-active="departmentDraft.is_active = $event"
    />

    <AppConfirmDialog
      :open="Boolean(pendingDelete)"
      :title="t('pages.organization.confirmDelete.title')"
      :message="confirmDeleteMessage"
      :confirm-text="t('pages.organization.confirmDelete.confirm')"
      :cancel-text="t('pages.organization.confirmDelete.cancel')"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCan from '@/components/common/AppCan.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppEntityGrid from '@/components/common/AppEntityGrid.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useOrganizationPage } from '@/features/organization/model/composables/useOrganizationPage'
import DepartmentFormDialog from '@/features/organization/ui/components/DepartmentFormDialog.vue'
import FacultyFormDialog from '@/features/organization/ui/components/FacultyFormDialog.vue'
import OrganizationFacultyCard from '@/features/organization/ui/components/OrganizationFacultyCard.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const authStore = useAuthStore()
const {
  faculties,
  facultiesCount,
  departmentsCount,
  activeFacultiesCount,
  activeDepartmentsCount,
  facultyDialogOpen,
  editingFaculty,
  facultyDraft,
  departmentDialogOpen,
  editingDepartment,
  departmentDraft,
  startCreateFaculty,
  startEditFaculty,
  closeFacultyDialog,
  saveFaculty,
  removeFaculty,
  startCreateDepartment,
  startEditDepartment,
  closeDepartmentDialog,
  saveDepartment,
  removeDepartment,
} = useOrganizationPage()

const search = ref('')
const pendingDelete = ref(null)

const canViewOrganization = computed(() => authStore.hasPermission('faculties.view'))
const canCreateOrganization = computed(() => authStore.hasPermission('faculties.create'))
const canUpdateOrganization = computed(() => authStore.hasPermission('faculties.update'))
const canDeleteOrganization = computed(() => authStore.hasPermission('faculties.delete'))
const previewLimit = 3

const filteredFaculties = computed(() => {
  if (!canViewOrganization.value) return []
  const q = search.value.trim().toLowerCase()
  if (!q) return faculties.value
  return faculties.value.filter(
    (faculty) =>
      faculty.name_en.toLowerCase().includes(q) ||
      faculty.name_ar.toLowerCase().includes(q) ||
      faculty.code.toLowerCase().includes(q) ||
      faculty.departments.some(
        (department) =>
          department.name_en.toLowerCase().includes(q) ||
          department.name_ar.toLowerCase().includes(q) ||
          department.code.toLowerCase().includes(q),
      ),
  )
})

const statsCards = computed(() => [
  {
    id: 'faculties',
    value: facultiesCount.value,
    label: t('pages.organization.stats.faculties'),
    icon: 'bi bi-building',
    iconColor: '#8b5cf6',
  },
  {
    id: 'departments',
    value: departmentsCount.value,
    label: t('pages.organization.stats.departments'),
    icon: 'bi bi-diagram-3',
    iconColor: '#22c55e',
  },
  {
    id: 'active-faculties',
    value: activeFacultiesCount.value,
    label: t('pages.organization.stats.activeFaculties'),
    icon: 'bi bi-check2-circle',
    iconColor: '#0284c7',
  },
  {
    id: 'active-departments',
    value: activeDepartmentsCount.value,
    label: t('pages.organization.stats.activeDepartments'),
    icon: 'bi bi-check2-all',
    iconColor: '#e11d48',
  },
])

const confirmDeleteMessage = computed(() => {
  if (!pendingDelete.value) return ''
  return t('pages.organization.confirmDelete.message', {
    type: pendingDelete.value.typeLabel,
    name: pendingDelete.value.name,
  })
})

function requestDeleteFaculty(faculty) {
  if (!canDeleteOrganization.value) return
  pendingDelete.value = {
    kind: 'faculty',
    facultyId: faculty.id,
    name: faculty.name_en,
    typeLabel: t('pages.organization.entities.faculty'),
  }
}

function requestDeleteDepartmentFromFaculty(faculty, department) {
  if (!canDeleteOrganization.value) return
  pendingDelete.value = {
    kind: 'department',
    facultyId: faculty.id,
    departmentId: department.id,
    name: department.name_en,
    typeLabel: t('pages.organization.entities.department'),
  }
}

async function confirmDelete() {
  if (!canDeleteOrganization.value || !pendingDelete.value) return

  try {
    if (pendingDelete.value.kind === 'faculty') {
      await removeFaculty(pendingDelete.value.facultyId)
    } else if (pendingDelete.value.kind === 'department') {
      await removeDepartment(pendingDelete.value.facultyId, pendingDelete.value.departmentId)
    }
  } finally {
    pendingDelete.value = null
  }
}

async function handleSaveFaculty() {
  if (editingFaculty.value && !canUpdateOrganization.value) return
  if (!editingFaculty.value && !canCreateOrganization.value) return
  await saveFaculty()
}

async function handleSaveDepartment() {
  if (editingDepartment.value && !canUpdateOrganization.value) return
  if (!editingDepartment.value && !canCreateOrganization.value) return
  await saveDepartment()
}

function getFilteredDepartmentsForFaculty(faculty) {
  const q = search.value.trim().toLowerCase()
  if (!q) return faculty.departments
  return faculty.departments.filter(
    (department) =>
      department.name_en.toLowerCase().includes(q) ||
      department.name_ar.toLowerCase().includes(q) ||
      department.code.toLowerCase().includes(q),
  )
}

function getDepartmentPreview(faculty) {
  return getFilteredDepartmentsForFaculty(faculty).slice(0, previewLimit)
}

function getHiddenDepartmentsCount(faculty) {
  const filteredCount = getFilteredDepartmentsForFaculty(faculty).length
  return filteredCount > previewLimit ? filteredCount - previewLimit : 0
}
</script>

<style scoped>
.organization-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-entity-btn {
  padding: 0.6rem 1rem;
}
</style>
