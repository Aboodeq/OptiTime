<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="resource-name-ar"
        :label="t('pages.resourcesManagement.form.nameAr')"
        icon="bi bi-box-seam"
        :model-value="draft.name_ar"
        :placeholder="t('pages.resourcesManagement.form.nameArPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name_ar', $event)"
      />

      <AppInputField
        input-id="resource-name-en"
        :label="t('pages.resourcesManagement.form.nameEn')"
        icon="bi bi-box-seam"
        :model-value="draft.name_en"
        :placeholder="t('pages.resourcesManagement.form.nameEnPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name_en', $event)"
      />

      <AppInputField
        input-id="resource-type"
        :label="t('pages.resourcesManagement.form.type')"
        icon="bi bi-tag"
        :model-value="draft.type"
        :placeholder="t('pages.resourcesManagement.form.typePlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:type', $event)"
      />

      <AppInputField
        input-id="resource-quantity"
        :label="t('pages.resourcesManagement.form.quantity')"
        icon="bi bi-123"
        input-type="number"
        :model-value="draft.quantity"
        :placeholder="t('pages.resourcesManagement.form.quantity')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:quantity', $event)"
      />

      <AppInputField
        input-id="resource-location-ar"
        :label="t('pages.resourcesManagement.form.locationAr')"
        icon="bi bi-geo-alt"
        :model-value="draft.location_ar"
        :placeholder="t('pages.resourcesManagement.form.locationArPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:location_ar', $event)"
      />

      <AppInputField
        input-id="resource-location-en"
        :label="t('pages.resourcesManagement.form.locationEn')"
        icon="bi bi-geo-alt"
        :model-value="draft.location_en"
        :placeholder="t('pages.resourcesManagement.form.locationEnPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:location_en', $event)"
      />

      <AppAutocompleteField
        input-id="resource-status"
        :label="t('pages.resourcesManagement.form.status')"
        icon="bi bi-check2-circle"
        :model-value="draft.status"
        :options="statusOptions"
        :placeholder="t('pages.resourcesManagement.form.status')"
        :allow-create="false"
        :disabled="!canEdit"
        @update:model-value="$emit('update:status', $event)"
      />

      <AppTextareaField
        input-id="resource-notes-ar"
        :label="t('pages.resourcesManagement.form.notesAr')"
        :model-value="draft.notes_ar"
        :placeholder="t('pages.resourcesManagement.form.notesArPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:notes_ar', $event)"
      />

      <AppTextareaField
        input-id="resource-notes-en"
        :label="t('pages.resourcesManagement.form.notesEn')"
        :model-value="draft.notes_en"
        :placeholder="t('pages.resourcesManagement.form.notesEnPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:notes_en', $event)"
      />

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.resourcesManagement.actions.save') }}
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
import AppTextareaField from '@/components/common/AppTextareaField.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  draft: { type: Object, required: true },
  canEdit: { type: Boolean, default: true },
})

defineEmits([
  'cancel',
  'save',
  'update:name_ar',
  'update:name_en',
  'update:type',
  'update:quantity',
  'update:location_ar',
  'update:location_en',
  'update:status',
  'update:notes_ar',
  'update:notes_en',
])

const { t } = useI18n()
const statusOptions = computed(() => [
  { value: 'available', label: t('pages.resourcesManagement.status.available') },
  { value: 'maintenance', label: t('pages.resourcesManagement.status.maintenance') },
  { value: 'unavailable', label: t('pages.resourcesManagement.status.unavailable') },
])
const title = computed(() =>
  props.isEditing
    ? t('pages.resourcesManagement.form.editTitle')
    : t('pages.resourcesManagement.form.createTitle'),
)
</script>
