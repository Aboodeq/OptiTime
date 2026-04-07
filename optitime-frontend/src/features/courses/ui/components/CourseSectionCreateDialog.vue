<template>
  <AppDialog :open="open" :title="t('pages.coursesManagement.form.addSection')" @close="$emit('cancel')">
    <div class="d-grid gap-2">
      <AppInputField
        input-id="create-section-name"
        :label="t('pages.coursesManagement.form.sectionName')"
        icon="bi bi-tag"
        :model-value="draft.section_name"
        :placeholder="t('pages.coursesManagement.form.sectionNamePlaceholder')"
        @update:model-value="$emit('update:section_name', $event)"
      />
      <div class="d-grid grid-2 gap-2">
        <AppAutocompleteField
          input-id="create-section-type"
          :label="t('pages.coursesManagement.form.sectionType')"
          :model-value="draft.section_type"
          :options="sectionTypeOptions"
          :allow-create="false"
          @update:model-value="$emit('update:section_type', $event)"
        />
        <AppInputField
          input-id="create-section-capacity"
          :label="t('pages.coursesManagement.form.sectionCapacity')"
          icon="bi bi-people"
          input-type="number"
          :model-value="draft.capacity"
          :placeholder="t('pages.coursesManagement.form.sectionCapacityPlaceholder')"
          @update:model-value="$emit('update:capacity', $event)"
        />
      </div>
      <AppMultiSelectField
        input-id="create-section-instructors"
        :label="t('pages.coursesManagement.form.sectionInstructors')"
        :model-value="draft.instructor_ids"
        :options="instructorOptions"
        :placeholder="t('pages.coursesManagement.form.sectionInstructorsPlaceholder')"
        :allow-create="false"
        :empty-text="t('pages.coursesManagement.form.instructorsEmpty')"
        :disabled="!isDepartmentSelected"
        @update:model-value="$emit('update:instructor_ids', $event)"
      />
      <div class="d-flex justify-content-end gap-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canConfirm" @click="$emit('confirm')">
          {{ t('pages.coursesManagement.form.addSection') }}
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

const props = defineProps({
  open: { type: Boolean, default: false },
  draft: { type: Object, required: true },
  instructorOptions: { type: Array, default: () => [] },
  isDepartmentSelected: { type: Boolean, default: false },
})

defineEmits([
  'cancel',
  'confirm',
  'update:section_name',
  'update:section_type',
  'update:capacity',
  'update:instructor_ids',
])

const { t } = useI18n()
const sectionTypeOptions = computed(() => [
  { value: 'room', label: t('pages.coursesManagement.labels.room') },
  { value: 'lab', label: t('pages.coursesManagement.labels.lab') },
])

const canConfirm = computed(() => {
  const sectionName = props.draft.section_name?.trim()
  const capacity = Number(props.draft.capacity)
  return Boolean(sectionName) && Number.isInteger(capacity) && capacity > 0 && props.draft.instructor_ids.length > 0
})
</script>
