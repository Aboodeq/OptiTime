<template>
  <AppShell :page-title="t('routes.rolesManagement')">
    <section class="dashboard-card w-100">
      <div class="roles-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.rolesManagement.title') }}</h1>
        <AppCan permission="roles.create">
          <button class="new-role-btn" type="button" @click="startCreateRole">
            + {{ t('pages.rolesManagement.actions.newRole') }}
          </button>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.rolesManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <div class="roles-table-card">
        <AppDataTable
          :columns="tableColumns"
          :rows="filteredRoles"
          row-key="id"
          :empty-text="t('pages.rolesManagement.table.empty')"
          :show-search="canViewRoles"
          :search-value="search"
          :search-placeholder="t('nav.topbar.quickSearch')"
          @update:search-value="search = $event"
        >
          <template #cell-role="{ row }">
            <div class="d-flex align-items-center gap-2">
              <span class="role-dot" :style="{ background: row.sidebar_color }"></span>
              <div class="d-flex flex-column">
                <span class="fw-semibold">{{ row.name_en }}</span>
                <small class="text-secondary">{{ row.name_ar }}</small>
              </div>
            </div>
          </template>

          <template #cell-identifier="{ row }">
            <code>{{ row.code }}</code>
          </template>

          <template #cell-permissions="{ row }">
            {{ row.permissions.length }}
          </template>

          <template #cell-actions="{ row }">
            <div class="text-start">
              <AppCan permission="roles.update">
                <button
                  class="btn btn-sm btn-outline-primary me-2"
                  :title="t('pages.rolesManagement.actions.edit')"
                  :aria-label="t('pages.rolesManagement.actions.edit')"
                  @click="startEditRole(row)"
                >
                  <i class="bi bi-pencil-square"></i>
                </button>
              </AppCan>
              <AppCan permission="roles.delete">
                <button
                  class="btn btn-sm btn-outline-danger"
                  :title="t('pages.rolesManagement.actions.delete')"
                  :aria-label="t('pages.rolesManagement.actions.delete')"
                  @click="requestDeleteRole(row)"
                >
                  <i class="bi bi-trash3"></i>
                </button>
              </AppCan>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>

    <RoleFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :permission-catalog="permissionCatalog"
      :can-edit-role="canCreateRoles || canUpdateRoles"
      :can-edit-permissions="canCreateRoles || canUpdateRoles"
      @cancel="closeDialog"
      @save="handleSaveRole"
      @update:code="draft.code = $event"
      @update:name-ar="draft.name_ar = $event"
      @update:name-en="draft.name_en = $event"
      @update:sidebar-color="draft.sidebar_color = $event"
      @update:description="draft.description = $event"
      @update:is-active="draft.is_active = $event"
      @toggle-permission="toggleDraftPermission"
    />

    <AppConfirmDialog
      :open="Boolean(rolePendingDelete)"
      :title="t('pages.rolesManagement.confirmDelete.title')"
      :message="t('pages.rolesManagement.confirmDelete.message', { role: rolePendingDelete?.name_en ?? '' })"
      :confirm-text="t('pages.rolesManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.rolesManagement.confirmDelete.cancel')"
      @cancel="rolePendingDelete = null"
      @confirm="confirmDeleteRole"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCan from '@/components/common/AppCan.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import RoleFormDialog from '@/features/roles/ui/components/RoleFormDialog.vue'
import { useRolesManagementPage } from '@/features/roles/model/composables/useRolesManagementPage'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const authStore = useAuthStore()
const {
  roles,
  draft,
  isDialogOpen,
  isEditing,
  permissionCatalog,
  startCreateRole,
  startEditRole,
  closeDialog,
  toggleDraftPermission,
  saveRole,
  deleteRole,
} = useRolesManagementPage()

const search = ref('')
const rolePendingDelete = ref(null)

const filteredRoles = computed(() => {
  if (!canViewRoles.value) return []
  const q = search.value.trim().toLowerCase()
  if (!q) return roles.value
  return roles.value.filter(
    (role) =>
      role.name_en.toLowerCase().includes(q) ||
      role.name_ar.toLowerCase().includes(q) ||
      role.code.toLowerCase().includes(q) ||
      role.permissions.some((permission) => permission.toLowerCase().includes(q)),
  )
})

const stats = computed(() => ({
  rolesCount: roles.value.length,
  totalPermissions: roles.value.reduce((sum, role) => sum + role.permissions.length, 0),
  adminRoles: roles.value.filter((role) => role.permissions.includes('roles.view')).length,
}))

const canViewRoles = computed(() => authStore.hasPermission('roles.view'))
const canCreateRoles = computed(() => authStore.hasPermission('roles.create'))
const canUpdateRoles = computed(() => authStore.hasPermission('roles.update'))
const canDeleteRoles = computed(() => authStore.hasPermission('roles.delete'))

const statsCards = computed(() => [
  {
    id: 'roles',
    value: stats.value.rolesCount,
    label: t('pages.rolesManagement.table.role'),
    icon: 'bi bi-shield-lock',
    iconColor: '#8b5cf6',
  },
  {
    id: 'permissions',
    value: stats.value.totalPermissions,
    label: t('pages.rolesManagement.table.permissions'),
    icon: 'bi bi-key',
    iconColor: '#22c55e',
  },
  {
    id: 'admin',
    value: stats.value.adminRoles,
    label: t('pages.rolesManagement.stats.adminRoles'),
    icon: 'bi bi-person-check',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'role', label: t('pages.rolesManagement.table.role') },
  { key: 'identifier', label: t('pages.rolesManagement.table.identifier') },
  { key: 'permissions', label: t('pages.rolesManagement.table.permissions') },
  ...(canUpdateRoles.value || canDeleteRoles.value
    ? [{ key: 'actions', label: t('pages.rolesManagement.table.actions') }]
    : []),
])

function requestDeleteRole(role) {
  if (!canDeleteRoles.value) return
  rolePendingDelete.value = role
}

function confirmDeleteRole() {
  if (!canDeleteRoles.value) return
  if (!rolePendingDelete.value) return
  deleteRole(rolePendingDelete.value.id)
  rolePendingDelete.value = null
}

function handleSaveRole() {
  if (isEditing.value && !canUpdateRoles.value) return
  if (!isEditing.value && !canCreateRoles.value) return
  saveRole()
}
</script>

<style scoped>
.role-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  display: inline-block;
}

.roles-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-role-btn {
  background: linear-gradient(135deg, #ef4444, #be123c);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 0.6rem 1rem;
  font-weight: 700;
}

.roles-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}

.table th,
.table td {
  vertical-align: middle;
}

</style>
