<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="room-name-ar"
        :label="t('pages.roomsManagement.form.nameAr')"
        icon="bi bi-door-open"
        :model-value="draft.name_ar"
        :placeholder="t('pages.roomsManagement.form.nameArPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name_ar', $event)"
      />

      <AppInputField
        input-id="room-name-en"
        :label="t('pages.roomsManagement.form.nameEn')"
        icon="bi bi-door-open"
        :model-value="draft.name_en"
        :placeholder="t('pages.roomsManagement.form.nameEnPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name_en', $event)"
      />

      <AppAutocompleteField
        input-id="room-type"
        :label="t('pages.roomsManagement.form.type')"
        icon="bi bi-diagram-2"
        :model-value="draft.type"
        :options="typeOptions"
        :placeholder="t('pages.roomsManagement.form.type')"
        :allow-create="false"
        :disabled="!canEdit || lockType"
        @update:model-value="$emit('update:type', $event)"
      />

      <AppInputField
        input-id="room-capacity"
        :label="t('pages.roomsManagement.form.capacity')"
        icon="bi bi-people"
        input-type="number"
        :model-value="draft.capacity"
        :placeholder="t('pages.roomsManagement.form.capacity')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:capacity', $event)"
      />

      <AppInputField
        input-id="room-location-ar"
        :label="t('pages.roomsManagement.form.locationAr')"
        icon="bi bi-geo-alt"
        :model-value="draft.location_ar"
        :placeholder="t('pages.roomsManagement.form.locationArPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:location_ar', $event)"
      />

      <AppInputField
        input-id="room-location-en"
        :label="t('pages.roomsManagement.form.locationEn')"
        icon="bi bi-geo-alt"
        :model-value="draft.location_en"
        :placeholder="t('pages.roomsManagement.form.locationEnPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:location_en', $event)"
      />

      <AppMultiSelectField
        input-id="room-resources"
        :label="t('pages.roomsManagement.form.resources')"
        icon="bi bi-box-seam"
        :model-value="draft.resource_ids"
        :options="resourceOptions"
        :placeholder="t('pages.roomsManagement.form.resourcesPlaceholder')"
        :allow-create="canCreateResources"
        :empty-text="t('pages.roomsManagement.form.resourcesEmpty')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:resource_ids', $event)"
        @create-option="$emit('create:resource', $event)"
      />

      <AppAutocompleteField
        input-id="room-status"
        :label="t('pages.roomsManagement.form.status')"
        icon="bi bi-check2-circle"
        :model-value="draft.status"
        :options="statusOptions"
        :placeholder="t('pages.roomsManagement.form.status')"
        :allow-create="false"
        :disabled="!canEdit"
        @update:model-value="$emit('update:status', $event)"
      />

      <AppTextareaField
        input-id="room-notes-ar"
        :label="t('pages.roomsManagement.form.notesAr')"
        :model-value="draft.notes_ar"
        :placeholder="t('pages.roomsManagement.form.notesArPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:notes_ar', $event)"
      />

      <AppTextareaField
        input-id="room-notes-en"
        :label="t('pages.roomsManagement.form.notesEn')"
        :model-value="draft.notes_en"
        :placeholder="t('pages.roomsManagement.form.notesEnPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:notes_en', $event)"
      />

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.roomsManagement.actions.save') }}
        </AppButton>
      </div>
    </div>
  </AppDialog>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAutocompleteField from '@/components/common/AppAutocompleteField.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import AppInputField from '@/components/common/AppInputField.vue'
import AppMultiSelectField from '@/components/common/AppMultiSelectField.vue'
import AppTextareaField from '@/components/common/AppTextareaField.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  draft: { type: Object, required: true },
  canEdit: { type: Boolean, default: true },
  canCreateResources: { type: Boolean, default: false },
  resourceOptions: { type: Array, default: () => [] },
  lockType: { type: Boolean, default: false },
  titleKey: { type: String, default: 'pages.roomsManagement' },
})

defineEmits([
  'cancel',
  'save',
  'update:name_ar',
  'update:name_en',
  'update:type',
  'update:capacity',
  'update:resource_ids',
  'update:location_ar',
  'update:location_en',
  'update:status',
  'update:notes_ar',
  'update:notes_en',
  'create:resource',
])

const { t } = useI18n()
const statusOptions = computed(() => [
  { value: 'available', label: t('pages.roomsManagement.status.available') },
  { value: 'maintenance', label: t('pages.roomsManagement.status.maintenance') },
  { value: 'unavailable', label: t('pages.roomsManagement.status.unavailable') },
])
const typeOptions = computed(() => [
  { value: 'class', label: t('pages.roomsManagement.types.class') },
  { value: 'lab', label: t('pages.roomsManagement.types.lab') },
  { value: 'hall', label: t('pages.roomsManagement.types.hall') },
])
const title = computed(() =>
  props.isEditing ? t(`${props.titleKey}.form.editTitle`) : t(`${props.titleKey}.form.createTitle`),
)
</script>
