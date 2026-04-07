<template>
  <AppSectionPanel :title="t('pages.coursesManagement.form.createTitle')" icon="bi bi-journal-text" tone="soft">
    <div class="grid-2">
      <AppInputField
        input-id="course-code"
        :label="t('pages.coursesManagement.form.code')"
        icon="bi bi-upc-scan"
        :model-value="draft.code"
        :placeholder="t('pages.coursesManagement.form.codePlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:code', $event)"
      />
      <AppInputField
        input-id="course-hours"
        :label="t('pages.coursesManagement.form.requiredHours')"
        icon="bi bi-clock-history"
        input-type="number"
        :model-value="draft.required_hours"
        :placeholder="t('pages.coursesManagement.form.requiredHoursPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:required_hours', $event)"
      />
    </div>
    <div class="grid-2">
      <AppInputField
        input-id="course-name-ar"
        :label="t('pages.coursesManagement.form.nameAr')"
        icon="bi bi-journal-text"
        :model-value="draft.name_ar"
        :placeholder="t('pages.coursesManagement.form.nameArPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name_ar', $event)"
      />
      <AppInputField
        input-id="course-name-en"
        :label="t('pages.coursesManagement.form.nameEn')"
        icon="bi bi-journal-text"
        :model-value="draft.name_en"
        :placeholder="t('pages.coursesManagement.form.nameEnPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name_en', $event)"
      />
    </div>
    <div class="grid-2">
      <AppAutocompleteField
        input-id="course-faculty"
        :label="t('pages.coursesManagement.form.faculty')"
        icon="bi bi-diagram-3"
        :model-value="draft.faculty_id"
        :options="facultyOptions"
        :placeholder="t('pages.coursesManagement.form.facultyPlaceholder')"
        :allow-create="false"
        :disabled="!canEdit"
        @update:model-value="$emit('update:faculty_id', $event)"
      />
      <AppAutocompleteField
        input-id="course-department"
        :label="t('pages.coursesManagement.form.department')"
        icon="bi bi-building"
        :model-value="draft.department_id"
        :options="departmentOptions"
        :placeholder="t('pages.coursesManagement.form.departmentPlaceholder')"
        :allow-create="false"
        :disabled="!canEdit || !draft.faculty_id"
        @update:model-value="$emit('update:department_id', $event)"
      />
    </div>
    <div class="grid-2">
      <AppInputField
        input-id="course-room-consumed-hours"
        :label="t('pages.coursesManagement.form.roomConsumedHours')"
        icon="bi bi-easel2"
        input-type="number"
        :model-value="draft.room_consumed_hours"
        :placeholder="t('pages.coursesManagement.form.roomConsumedHoursPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:room_consumed_hours', $event)"
      />
      <AppInputField
        input-id="course-lab-consumed-hours"
        :label="t('pages.coursesManagement.form.labConsumedHours')"
        icon="bi bi-pc-display"
        input-type="number"
        :model-value="draft.lab_consumed_hours"
        :placeholder="t('pages.coursesManagement.form.labConsumedHoursPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:lab_consumed_hours', $event)"
      />
    </div>
    <div class="grid-2">
      <AppInputField
        input-id="course-min-year-level"
        :label="t('pages.coursesManagement.form.minStudentYearLevel')"
        icon="bi bi-1-circle"
        input-type="number"
        :model-value="draft.min_student_year_level"
        :placeholder="t('pages.coursesManagement.form.minStudentYearLevelPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:min_student_year_level', $event)"
      />
      <AppInputField
        input-id="course-max-year-level"
        :label="t('pages.coursesManagement.form.maxStudentYearLevel')"
        icon="bi bi-9-circle"
        input-type="number"
        :model-value="draft.max_student_year_level"
        :placeholder="t('pages.coursesManagement.form.maxStudentYearLevelPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:max_student_year_level', $event)"
      />
    </div>
  </AppSectionPanel>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppAutocompleteField from '@/components/common/AppAutocompleteField.vue'
import AppInputField from '@/components/common/AppInputField.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'

defineProps({
  draft: { type: Object, required: true },
  canEdit: { type: Boolean, default: true },
  facultyOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
})

defineEmits([
  'update:code',
  'update:required_hours',
  'update:name_ar',
  'update:name_en',
  'update:faculty_id',
  'update:department_id',
  'update:room_consumed_hours',
  'update:lab_consumed_hours',
  'update:min_student_year_level',
  'update:max_student_year_level',
])

const { t } = useI18n()
</script>

<style scoped>
.grid-2 {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 0.7rem;
}

@media (max-width: 991px) {
  .grid-2 {
    grid-template-columns: 1fr;
    gap: 0.75rem;
    margin-top: 0.55rem;
  }
}
</style>
