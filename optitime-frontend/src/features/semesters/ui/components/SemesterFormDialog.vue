<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="semester-name"
        :label="t('pages.semestersManagement.form.name')"
        icon="bi bi-bookmark"
        :model-value="draft.name"
        :placeholder="t('pages.semestersManagement.form.name')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name', $event)"
      />

      <AppInputField
        input-id="semester-code"
        :label="t('pages.semestersManagement.form.code')"
        icon="bi bi-upc-scan"
        :model-value="draft.code"
        :placeholder="t('pages.semestersManagement.form.codePlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:code', $event)"
      />

      <AppInputField
        input-id="semester-academic-year"
        :label="t('pages.semestersManagement.form.academicYear')"
        icon="bi bi-calendar3"
        :model-value="draft.academic_year"
        :placeholder="t('pages.semestersManagement.form.academicYearPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:academicYear', $event)"
      />

      <AppInputField
        input-id="semester-start-date"
        :label="t('pages.semestersManagement.form.startDate')"
        icon="bi bi-calendar-event"
        input-type="date"
        :model-value="draft.start_date"
        :disabled="!canEdit"
        @update:model-value="$emit('update:startDate', $event)"
      />

      <AppInputField
        input-id="semester-end-date"
        :label="t('pages.semestersManagement.form.endDate')"
        icon="bi bi-calendar-check"
        input-type="date"
        :model-value="draft.end_date"
        :disabled="!canEdit"
        @update:model-value="$emit('update:endDate', $event)"
      />

      <AppCheckboxField
        input-id="semester-is-active"
        :label="t('pages.semestersManagement.form.isActive')"
        :model-value="draft.is_active"
        :disabled="!canEdit"
        @update:model-value="$emit('update:isActive', $event)"
      />

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.semestersManagement.actions.save') }}
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

defineEmits([
  'cancel',
  'save',
  'update:name',
  'update:code',
  'update:academicYear',
  'update:startDate',
  'update:endDate',
  'update:isActive',
])

const { t } = useI18n()
const title = computed(() =>
  props.isEditing
    ? t('pages.semestersManagement.form.editTitle')
    : t('pages.semestersManagement.form.createTitle'),
)
</script>
