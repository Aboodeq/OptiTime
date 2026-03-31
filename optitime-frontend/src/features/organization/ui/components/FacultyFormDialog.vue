<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="faculty-code"
        :label="t('pages.organization.form.code')"
        icon="bi bi-upc-scan"
        :model-value="draft.code"
        :placeholder="t('pages.organization.form.code')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:code', $event)"
      />

      <AppInputField
        input-id="faculty-name-ar"
        :label="t('pages.organization.form.nameAr')"
        icon="bi bi-translate"
        :model-value="draft.name_ar"
        :placeholder="t('pages.organization.form.nameAr')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:nameAr', $event)"
      />

      <AppInputField
        input-id="faculty-name-en"
        :label="t('pages.organization.form.nameEn')"
        icon="bi bi-fonts"
        :model-value="draft.name_en"
        :placeholder="t('pages.organization.form.nameEn')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:nameEn', $event)"
      />

      <AppInputField
        input-id="faculty-graduation-hours"
        :label="t('pages.organization.form.graduationHours')"
        icon="bi bi-mortarboard"
        input-type="number"
        :model-value="String(draft.graduation_hours ?? 0)"
        :placeholder="t('pages.organization.form.graduationHours')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:graduationHours', $event)"
      />

      <AppInputField
        input-id="faculty-studying-level"
        :label="t('pages.organization.form.studyingLevel')"
        icon="bi bi-layers"
        input-type="number"
        :model-value="String(draft.studying_level ?? 1)"
        :placeholder="t('pages.organization.form.studyingLevel')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:studyingLevel', $event)"
      />

      <AppColorField
        :label="t('pages.organization.form.color')"
        :model-value="draft.color"
        :disabled="!canEdit"
        @update:model-value="$emit('update:color', $event)"
      />

      <AppImageUploadField
        :label="t('pages.organization.form.icon')"
        :model-value="draft.icon_url"
        :hint="t('pages.organization.form.iconHint')"
        :accepted-text="t('pages.organization.form.iconAccepted')"
        :clear-text="t('pages.organization.form.iconClear')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:iconUrl', $event)"
      />

      <AppCheckboxField
        input-id="faculty-is-active"
        :label="t('pages.organization.form.isActive')"
        :model-value="draft.is_active"
        :disabled="!canEdit"
        @update:model-value="$emit('update:isActive', $event)"
      />

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.organization.actions.saveFaculty') }}
        </AppButton>
      </div>
    </div>
  </AppDialog>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppCheckboxField from '@/components/common/AppCheckboxField.vue'
import AppColorField from '@/components/common/AppColorField.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import AppImageUploadField from '@/components/common/AppImageUploadField.vue'
import AppInputField from '@/components/common/AppInputField.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  draft: { type: Object, required: true },
  canEdit: { type: Boolean, default: true },
})

defineEmits([
  'cancel',
  'save',
  'update:code',
  'update:nameAr',
  'update:nameEn',
  'update:graduationHours',
  'update:studyingLevel',
  'update:color',
  'update:iconUrl',
  'update:isActive',
])

const { t } = useI18n()
const title = computed(() =>
  props.isEditing
    ? t('pages.organization.form.editFacultyTitle')
    : t('pages.organization.form.createFacultyTitle'),
)
</script>
