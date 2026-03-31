<template>
  <AppShell :page-title="t('routes.usersManagement')">
    <section class="dashboard-card w-100">
      <div class="users-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.usersManagement.title') }}</h1>
        <AppCan permission="users.create">
          <button class="new-user-btn" type="button" @click="startCreateUser">
            + {{ t('pages.usersManagement.actions.newUser') }}
          </button>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.usersManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <div class="users-table-card">
        <AppDataTable
          :columns="tableColumns"
          :rows="filteredUsers"
          row-key="id"
          :empty-text="t('pages.usersManagement.table.empty')"
          :show-search="canViewUsers"
          :search-value="search"
          :search-placeholder="t('nav.topbar.quickSearch')"
          @update:search-value="search = $event"
        >
          <template #cell-user="{ row }">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ row.name }}</span>
              <small class="text-secondary">{{ row.email }}</small>
            </div>
          </template>

          <template #cell-faculty="{ row }">
            {{ row.faculty_name || '-' }}
          </template>

          <template #cell-role="{ row }">
            <span class="badge text-bg-light border">{{ row.role }}</span>
          </template>

          <template #cell-department="{ row }">
            {{ row.department_name || '-' }}
          </template>

          <template #cell-status="{ row }">
            <span :class="row.is_active ? 'text-success' : 'text-secondary'">
              {{
                row.is_active
                  ? t('pages.usersManagement.status.active')
                  : t('pages.usersManagement.status.inactive')
              }}
            </span>
          </template>

          <template #cell-actions="{ row }">
            <div class="text-start">
              <AppCan permission="users.update">
                <button
                  class="btn btn-sm btn-outline-primary me-2"
                  :title="t('pages.usersManagement.actions.edit')"
                  :aria-label="t('pages.usersManagement.actions.edit')"
                  @click="startEditUser(row)"
                >
                  <i class="bi bi-pencil-square"></i>
                </button>
              </AppCan>
              <AppCan permission="users.delete">
                <button
                  class="btn btn-sm btn-outline-danger"
                  :title="t('pages.usersManagement.actions.delete')"
                  :aria-label="t('pages.usersManagement.actions.delete')"
                  @click="requestDeleteUser(row)"
                >
                  <i class="bi bi-trash3"></i>
                </button>
              </AppCan>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>

    <UserFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :role-options="manageableRoles"
      :faculty-options="facultyOptions"
      :department-options="departmentOptions"
      :can-create-entities="canCreateOrganization"
      :can-edit="canCreateUsers || canUpdateUsers"
      @cancel="closeDialog"
      @save="handleSaveUser"
      @update:name="draft.name = $event"
      @update:email="draft.email = $event"
      @update:password="draft.password = $event"
      @update:faculty-id="setFacultyId($event)"
      @update:department-id="setDepartmentId($event)"
      @update:role="draft.role = $event"
      @create:faculty="openCreateFacultyDialog($event)"
      @create:department="openCreateDepartmentDialog($event)"
      @update:is-active="draft.is_active = $event"
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
      :open="Boolean(userPendingDelete)"
      :title="t('pages.usersManagement.confirmDelete.title')"
      :message="t('pages.usersManagement.confirmDelete.message', { user: userPendingDelete?.name ?? '' })"
      :confirm-text="t('pages.usersManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.usersManagement.confirmDelete.cancel')"
      @cancel="userPendingDelete = null"
      @confirm="confirmDeleteUser"
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
import { useOrganizationStore } from '@/features/organization/model/stores/organization.store'
import DepartmentFormDialog from '@/features/organization/ui/components/DepartmentFormDialog.vue'
import FacultyFormDialog from '@/features/organization/ui/components/FacultyFormDialog.vue'
import { useUsersManagementPage } from '@/features/users/model/composables/useUsersManagementPage'
import UserFormDialog from '@/features/users/ui/components/UserFormDialog.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const {
  users,
  manageableRoles,
  faculties,
  facultyOptions,
  departmentOptions,
  usersCount,
  activeUsersCount,
  inactiveUsersCount,
  draft,
  isDialogOpen,
  isEditing,
  startCreateUser,
  startEditUser,
  closeDialog,
  reopenDialog,
  saveUser,
  removeUser,
  setFacultyId,
  setDepartmentId,
} = useUsersManagementPage()
const organizationStore = useOrganizationStore()
organizationStore.ensureInitialized()

const search = ref('')
const userPendingDelete = ref(null)
const shouldReturnToUserDialog = ref(false)
const facultyDialogOpen = ref(false)
const facultyDraft = ref(organizationStore.createEmptyFacultyDraft())
const departmentDialogOpen = ref(false)
const departmentDraft = ref(organizationStore.createEmptyDepartmentDraft())

const canViewUsers = computed(() => authStore.hasPermission('users.view'))
const canCreateUsers = computed(() => authStore.hasPermission('users.create'))
const canUpdateUsers = computed(() => authStore.hasPermission('users.update'))
const canDeleteUsers = computed(() => authStore.hasPermission('users.delete'))
const canCreateOrganization = computed(() => authStore.hasPermission('organization.create'))
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

const filteredUsers = computed(() => {
  if (!canViewUsers.value) return []
  const q = search.value.trim().toLowerCase()
  const rows = users.value.map((user) => ({
    ...user,
    faculty_name: facultyNameById.value.get(user.faculty_id) ?? '',
    department_name: departmentNameById.value.get(user.department_id) ?? '',
  }))
  if (!q) return rows
  return rows.filter(
    (user) =>
      user.name.toLowerCase().includes(q) ||
      user.email.toLowerCase().includes(q) ||
      user.role.toLowerCase().includes(q) ||
      user.faculty_name.toLowerCase().includes(q) ||
      user.department_name.toLowerCase().includes(q),
  )
})

const statsCards = computed(() => [
  {
    id: 'users',
    value: usersCount.value,
    label: t('pages.usersManagement.stats.totalUsers'),
    icon: 'bi bi-people',
    iconColor: '#8b5cf6',
  },
  {
    id: 'active',
    value: activeUsersCount.value,
    label: t('pages.usersManagement.stats.activeUsers'),
    icon: 'bi bi-person-check',
    iconColor: '#22c55e',
  },
  {
    id: 'inactive',
    value: inactiveUsersCount.value,
    label: t('pages.usersManagement.stats.inactiveUsers'),
    icon: 'bi bi-person-dash',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'user', label: t('pages.usersManagement.table.user') },
  { key: 'faculty', label: t('pages.usersManagement.table.faculty') },
  { key: 'role', label: t('pages.usersManagement.table.role') },
  { key: 'department', label: t('pages.usersManagement.table.department') },
  { key: 'status', label: t('pages.usersManagement.table.status') },
  ...(canUpdateUsers.value || canDeleteUsers.value
    ? [{ key: 'actions', label: t('pages.usersManagement.table.actions') }]
    : []),
])

function requestDeleteUser(user) {
  if (!canDeleteUsers.value) return
  userPendingDelete.value = user
}

function confirmDeleteUser() {
  if (!canDeleteUsers.value) return
  if (!userPendingDelete.value) return
  removeUser(userPendingDelete.value.id)
  userPendingDelete.value = null
}

function handleSaveUser() {
  if (isEditing.value && !canUpdateUsers.value) return
  if (!isEditing.value && !canCreateUsers.value) return
  const saved = saveUser()
  if (!saved) {
    toast.error(t('pages.usersManagement.errors.invalidForm'))
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
  shouldReturnToUserDialog.value = isDialogOpen.value
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
  if (shouldReturnToUserDialog.value) {
    reopenDialog()
    shouldReturnToUserDialog.value = false
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
  shouldReturnToUserDialog.value = isDialogOpen.value
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
  if (shouldReturnToUserDialog.value) {
    reopenDialog()
    shouldReturnToUserDialog.value = false
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
</script>

<style scoped>
.users-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-user-btn {
  background: linear-gradient(135deg, #0f766e, #0d9488);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 0.6rem 1rem;
  font-weight: 700;
}

.users-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}
</style>
