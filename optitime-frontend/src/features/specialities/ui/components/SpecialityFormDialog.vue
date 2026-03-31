<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="speciality-code"
        :label="t('pages.specialitiesManagement.form.code')"
        icon="bi bi-upc-scan"
        :model-value="draft.code"
        :placeholder="t('pages.specialitiesManagement.form.code')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:code', $event)"
      />

      <AppInputField
        input-id="speciality-name-ar"
        :label="t('pages.specialitiesManagement.form.nameAr')"
        icon="bi bi-bookmark"
        :model-value="draft.name_ar"
        :placeholder="t('pages.specialitiesManagement.form.nameAr')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:nameAr', $event)"
      />

      <AppInputField
        input-id="speciality-name-en"
        :label="t('pages.specialitiesManagement.form.nameEn')"
        icon="bi bi-bookmark-star"
        :model-value="draft.name_en"
        :placeholder="t('pages.specialitiesManagement.form.nameEn')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:nameEn', $event)"
      />

      <AppCheckboxField
        input-id="speciality-is-active"
        :label="t('pages.specialitiesManagement.form.isActive')"
        :model-value="draft.is_active"
        :disabled="!canEdit"
        @update:model-value="$emit('update:isActive', $event)"
      />

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.specialitiesManagement.actions.save') }}
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
import AppDialog from '@/components/common/AppDialog.vue'
import AppInputField from '@/components/common/AppInputField.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  draft: { type: Object, required: true },
  canEdit: { type: Boolean, default: true },
})

defineEmits(['cancel', 'save', 'update:code', 'update:nameAr', 'update:nameEn', 'update:isActive'])

const { t } = useI18n()
const title = computed(() =>
  props.isEditing
    ? t('pages.specialitiesManagement.form.editTitle')
    : t('pages.specialitiesManagement.form.createTitle'),
)
</script>
