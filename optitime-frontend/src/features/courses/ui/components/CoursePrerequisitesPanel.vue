<template>
  <AppSectionPanel :title="t('pages.coursesManagement.form.prerequisites')" icon="bi bi-sliders2" tone="neutral">
    <AppMultiSelectField
      input-id="course-prerequisites"
      :label="t('pages.coursesManagement.form.prerequisites')"
      icon="bi bi-link-45deg"
      :model-value="draft.prerequisite_course_ids"
      :options="prerequisiteCourseOptions"
      :placeholder="t('pages.coursesManagement.form.prerequisitesPlaceholder')"
      :allow-create="false"
      :empty-text="t('pages.coursesManagement.form.prerequisitesEmpty')"
      :disabled="!canEdit"
      @update:model-value="$emit('update:prerequisite_course_ids', $event)"
    />
  </AppSectionPanel>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppMultiSelectField from '@/components/common/AppMultiSelectField.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'

defineProps({
  draft: { type: Object, required: true },
  canEdit: { type: Boolean, default: true },
  prerequisiteCourseOptions: { type: Array, default: () => [] },
})

defineEmits(['update:prerequisite_course_ids'])

const { t } = useI18n()
</script>
