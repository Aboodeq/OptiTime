<template>
  <AppSectionPanel
    :title="section.section_name || `${t('pages.coursesManagement.form.sectionName')} ${index + 1}`"
    :subtitle="`${t('pages.coursesManagement.form.sectionType')}: ${section.section_type}`"
    tone="neutral"
  >
    <div class="grid-3">
      <AppInputField
        :input-id="`section-name-${index}`"
        :label="t('pages.coursesManagement.form.sectionName')"
        icon="bi bi-tag"
        :model-value="section.section_name"
        :placeholder="t('pages.coursesManagement.form.sectionNamePlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update-section', { id: section.id, patch: { section_name: $event } })"
      />
      <AppAutocompleteField
        :input-id="`section-type-${index}`"
        :label="t('pages.coursesManagement.form.sectionType')"
        :model-value="section.section_type"
        :options="sectionTypeOptions"
        :allow-create="false"
        :disabled="!canEdit"
        @update:model-value="$emit('update-section', { id: section.id, patch: { section_type: $event } })"
      />
      <AppInputField
        :input-id="`section-capacity-${index}`"
        :label="t('pages.coursesManagement.form.sectionCapacity')"
        icon="bi bi-people"
        input-type="number"
        :model-value="section.capacity"
        :placeholder="t('pages.coursesManagement.form.sectionCapacityPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update-section', { id: section.id, patch: { capacity: $event } })"
      />
    </div>
    <AppMultiSelectField
      :input-id="`section-instructors-${index}`"
      :label="t('pages.coursesManagement.form.sectionInstructors')"
      :model-value="section.instructor_ids"
      :options="instructorOptions"
      :placeholder="t('pages.coursesManagement.form.sectionInstructorsPlaceholder')"
      :allow-create="false"
      :empty-text="t('pages.coursesManagement.form.instructorsEmpty')"
      :disabled="!canEdit || !isDepartmentSelected"
      @update:model-value="$emit('update-section', { id: section.id, patch: { instructor_ids: $event } })"
    />
    <div class="d-flex justify-content-end">
      <AppButton :disabled="!canEdit || sectionsCount <= 1" variant="outline" @click="$emit('remove', section.id)">
        {{ t('pages.coursesManagement.form.removeSection') }}
      </AppButton>
    </div>
  </AppSectionPanel>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppAutocompleteField from '@/components/common/AppAutocompleteField.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppInputField from '@/components/common/AppInputField.vue'
import AppMultiSelectField from '@/components/common/AppMultiSelectField.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'

defineProps({
  section: { type: Object, required: true },
  index: { type: Number, required: true },
  sectionsCount: { type: Number, required: true },
  canEdit: { type: Boolean, default: true },
  isDepartmentSelected: { type: Boolean, default: false },
  instructorOptions: { type: Array, default: () => [] },
})

defineEmits(['update-section', 'remove'])

const { t } = useI18n()
const sectionTypeOptions = computed(() => [
  { value: 'room', label: t('pages.coursesManagement.labels.room') },
  { value: 'lab', label: t('pages.coursesManagement.labels.lab') },
])
</script>

<style scoped>
.grid-3 {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-top: 0.7rem;
}

@media (max-width: 991px) {
  .grid-3 {
    grid-template-columns: 1fr;
    gap: 0.75rem;
    margin-top: 0.55rem;
  }
}
</style>
