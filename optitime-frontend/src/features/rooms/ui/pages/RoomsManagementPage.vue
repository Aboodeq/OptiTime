<template>
  <AppShell :page-title="t('routes.roomsManagement')">
    <section class="dashboard-card w-100">
      <div class="rooms-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.roomsManagement.title') }}</h1>
        <AppCan permission="rooms.create">
          <AppButton class="new-room-btn" type="button" :tone-color="authStore.roleColor" @click="startCreateRoom">
            + {{ t('pages.roomsManagement.actions.newRoom') }}
          </AppButton>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.roomsManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <div class="rooms-table-card">
        <AppDataTable
          :columns="tableColumns"
          :rows="filteredRooms"
          row-key="id"
          :empty-text="t('pages.roomsManagement.table.empty')"
          :show-search="canViewRooms"
          :search-value="search"
          :search-placeholder="t('nav.topbar.quickSearch')"
          @update:search-value="search = $event"
        >
          <template #cell-room="{ row }">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ row.name_ar }}</span>
              <small class="text-secondary">{{ row.name_en }}</small>
              <small class="text-secondary">{{ t(`pages.roomsManagement.types.${row.type}`) }}</small>
            </div>
          </template>

          <template #cell-capacity="{ row }">
            {{ row.capacity }}
          </template>

          <template #cell-resources="{ row }">
            {{ getRoomResourceNames(row) || '-' }}
          </template>

          <template #cell-location="{ row }">
            <div class="d-flex flex-column">
              <span>{{ row.location_ar || '-' }}</span>
              <small class="text-secondary">{{ row.location_en || '-' }}</small>
            </div>
          </template>

          <template #cell-status="{ row }">
            <span
              :class="{
                'text-success': row.status === 'available',
                'text-warning': row.status === 'maintenance',
                'text-danger': row.status === 'unavailable',
              }"
            >
              {{ t(`pages.roomsManagement.status.${row.status}`) }}
            </span>
          </template>

          <template #cell-notes="{ row }">
            <div class="d-flex flex-column">
              <span>{{ row.notes_ar || '-' }}</span>
              <small class="text-secondary">{{ row.notes_en || '-' }}</small>
            </div>
          </template>

          <template #cell-actions="{ row }">
            <div class="text-start">
              <AppCan permission="rooms.update">
                <AppIconButton
                  class="me-2"
                  icon="bi bi-pencil-square"
                  variant="primary"
                  :title="t('pages.roomsManagement.actions.edit')"
                  :aria-label="t('pages.roomsManagement.actions.edit')"
                  @click="startEditRoom(row)"
                />
              </AppCan>
              <AppCan permission="rooms.delete">
                <AppIconButton
                  icon="bi bi-trash3"
                  variant="danger"
                  :title="t('pages.roomsManagement.actions.delete')"
                  :aria-label="t('pages.roomsManagement.actions.delete')"
                  @click="requestDeleteRoom(row)"
                />
              </AppCan>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>

    <RoomFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :can-edit="canCreateRooms || canUpdateRooms"
      :can-create-resources="canCreateResources"
      :resource-options="resourceOptions"
      title-key="pages.roomsManagement"
      @cancel="closeDialog"
      @save="handleSaveRoom"
      @update:name_ar="draft.name_ar = $event"
      @update:name_en="draft.name_en = $event"
      @update:type="draft.type = $event"
      @update:capacity="draft.capacity = $event"
      @update:resource_ids="draft.resource_ids = $event"
      @update:location_ar="draft.location_ar = $event"
      @update:location_en="draft.location_en = $event"
      @update:status="draft.status = $event"
      @update:notes_ar="draft.notes_ar = $event"
      @update:notes_en="draft.notes_en = $event"
      @create:resource="openResourceCreateDialog"
    />

    <ResourceFormDialog
      :open="isResourceDialogOpen"
      :is-editing="false"
      :draft="resourceDraft"
      :can-edit="canCreateResources"
      @cancel="closeResourceDialog"
      @save="handleSaveResource"
      @update:name="resourceDraft.name = $event"
      @update:type="resourceDraft.type = $event"
      @update:quantity="resourceDraft.quantity = $event"
      @update:location="resourceDraft.location = $event"
      @update:status="resourceDraft.status = $event"
      @update:notes="resourceDraft.notes = $event"
    />

    <AppConfirmDialog
      :open="Boolean(roomPendingDelete)"
      :title="t('pages.roomsManagement.confirmDelete.title')"
      :message="
        t('pages.roomsManagement.confirmDelete.message', {
          room: roomPendingDelete?.name_en ?? '',
        })
      "
      :confirm-text="t('pages.roomsManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.roomsManagement.confirmDelete.cancel')"
      @cancel="roomPendingDelete = null"
      @confirm="confirmDeleteRoom"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppCan from '@/components/common/AppCan.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useResourcesStore } from '@/features/resources/model/stores/resources.store'
import ResourceFormDialog from '@/features/resources/ui/components/ResourceFormDialog.vue'
import { useRoomsManagementPage } from '@/features/rooms/model/composables/useRoomsManagementPage'
import RoomFormDialog from '@/features/rooms/ui/components/RoomFormDialog.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const resourcesStore = useResourcesStore()
const {
  managedRooms,
  managedRoomsCount,
  managedAvailableRoomsCount,
  managedUnavailableRoomsCount,
  resourceOptions,
  draft,
  isDialogOpen,
  isEditing,
  startCreateRoom,
  startEditRoom,
  closeDialog,
  saveRoom,
  removeRoom,
} = useRoomsManagementPage()
resourcesStore.ensureInitialized()

const search = ref('')
const roomPendingDelete = ref(null)
const isResourceDialogOpen = ref(false)
const resourceDraft = ref(resourcesStore.createEmptyDraft())
const roomDialogWasOpen = ref(false)

const canViewRooms = computed(() => authStore.hasPermission('rooms.view'))
const canCreateRooms = computed(() => authStore.hasPermission('rooms.create'))
const canUpdateRooms = computed(() => authStore.hasPermission('rooms.update'))
const canDeleteRooms = computed(() => authStore.hasPermission('rooms.delete'))
const canCreateResources = computed(() => authStore.hasPermission('resources.create'))

const resourceNameById = computed(() =>
  managedResourceOptionsMap(resourceOptions.value),
)

const filteredRooms = computed(() => {
  if (!canViewRooms.value) return []
  const q = search.value.trim().toLowerCase()
  if (!q) return managedRooms.value
  return managedRooms.value.filter(
    (room) =>
      (room.name_ar || '').toLowerCase().includes(q) ||
      (room.name_en || '').toLowerCase().includes(q) ||
      (room.type || '').toLowerCase().includes(q) ||
      getRoomResourceNames(room).toLowerCase().includes(q) ||
      (room.location_ar || '').toLowerCase().includes(q) ||
      (room.location_en || '').toLowerCase().includes(q) ||
      (room.status || '').toLowerCase().includes(q) ||
      (room.notes_ar || '').toLowerCase().includes(q) ||
      (room.notes_en || '').toLowerCase().includes(q),
  )
})

const statsCards = computed(() => [
  {
    id: 'rooms',
    value: managedRoomsCount.value,
    label: t('pages.roomsManagement.stats.totalRooms'),
    icon: 'bi bi-door-open',
    iconColor: '#8b5cf6',
  },
  {
    id: 'available',
    value: managedAvailableRoomsCount.value,
    label: t('pages.roomsManagement.stats.availableRooms'),
    icon: 'bi bi-check2-circle',
    iconColor: '#22c55e',
  },
  {
    id: 'unavailable',
    value: managedUnavailableRoomsCount.value,
    label: t('pages.roomsManagement.stats.unavailableRooms'),
    icon: 'bi bi-x-circle',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'room', label: t('pages.roomsManagement.table.room') },
  { key: 'capacity', label: t('pages.roomsManagement.table.capacity') },
  { key: 'resources', label: t('pages.roomsManagement.table.resources') },
  { key: 'location', label: t('pages.roomsManagement.table.location') },
  { key: 'status', label: t('pages.roomsManagement.table.status') },
  { key: 'notes', label: t('pages.roomsManagement.table.notes') },
  ...(canUpdateRooms.value || canDeleteRooms.value
    ? [{ key: 'actions', label: t('pages.roomsManagement.table.actions') }]
    : []),
])

function requestDeleteRoom(room) {
  if (!canDeleteRooms.value) return
  roomPendingDelete.value = room
}

async function confirmDeleteRoom() {
  if (!canDeleteRooms.value) return
  if (!roomPendingDelete.value) return
  await removeRoom(roomPendingDelete.value.id)
  roomPendingDelete.value = null
}

async function handleSaveRoom() {
  if (isEditing.value && !canUpdateRooms.value) return
  if (!isEditing.value && !canCreateRooms.value) return
  const saved = await saveRoom()
  if (!saved) {
    toast.error(t('pages.roomsManagement.errors.invalidForm'))
  }
}

function getRoomResourceNames(room) {
  if (!Array.isArray(room.resource_ids) || room.resource_ids.length === 0) {
    return ''
  }
  return room.resource_ids
    .map((resourceId) => resourceNameById.value.get(resourceId) || resourceId)
    .join(', ')
}

function managedResourceOptionsMap(options) {
  return new Map(options.map((option) => [option.value, option.label]))
}

function openResourceCreateDialog(name) {
  if (!canCreateResources.value) return
  roomDialogWasOpen.value = isDialogOpen.value
  closeDialog()
  resourceDraft.value = {
    ...resourcesStore.createEmptyDraft(),
    name,
    type: 'equipment',
    quantity: 1,
    location: draft.value.location_en || '',
  }
  isResourceDialogOpen.value = true
}

function closeResourceDialog() {
  isResourceDialogOpen.value = false
  if (roomDialogWasOpen.value) {
    roomDialogWasOpen.value = false
    isDialogOpen.value = true
  }
}

async function handleSaveResource() {
  if (!canCreateResources.value) return
  const created = await resourcesStore.createResourceFromDraft(resourceDraft.value)
  if (!created) {
    toast.error(t('pages.resourcesManagement.errors.invalidForm'))
    return
  }
  const createdId = created.id
  if (createdId && !draft.value.resource_ids.includes(createdId)) {
    draft.value.resource_ids = [...draft.value.resource_ids, createdId]
  }
  closeResourceDialog()
}
</script>

<style scoped>
.rooms-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-room-btn {
  padding: 0.6rem 1rem;
}

.rooms-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}
</style>
