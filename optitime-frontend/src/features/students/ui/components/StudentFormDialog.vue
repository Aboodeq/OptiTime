<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="student-name"
        :label="t('pages.studentsManagement.form.name')"
        icon="bi bi-person"
        :model-value="draft.name"
        :placeholder="t('pages.studentsManagement.form.name')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name', $event)"
      />

      <AppInputField
        input-id="student-email"
        :label="t('pages.studentsManagement.form.email')"
        icon="bi bi-envelope"
        :model-value="draft.email"
        :placeholder="t('pages.studentsManagement.form.email')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:email', $event)"
      />

      <AppInputField
        input-id="student-password"
        :label="t('pages.studentsManagement.form.password')"
        icon="bi bi-lock"
        input-type="password"
        autocomplete="new-password"
        :model-value="draft.password"
        :placeholder="t('pages.studentsManagement.form.password')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:password', $event)"
      />

      <AppInputField
        input-id="student-university-number"
        :label="t('pages.studentsManagement.form.universityNumber')"
        icon="bi bi-upc-scan"
        :model-value="draft.university_number || t('pages.studentsManagement.form.universityNumberAuto')"
        :disabled="true"
      />

      <AppInputField
        input-id="student-completed-hours"
        :label="t('pages.studentsManagement.form.completedHours')"
        icon="bi bi-clock-history"
        input-type="number"
        :model-value="draft.completed_hours"
        :placeholder="t('pages.studentsManagement.form.completedHours')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:completedHours', $event)"
      />

      <AppInputField
        input-id="student-year-level"
        :label="t('pages.studentsManagement.form.yearLevel')"
        icon="bi bi-layers"
        input-type="number"
        :model-value="draft.year_level"
        :placeholder="t('pages.studentsManagement.form.yearLevelPlaceholder')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:yearLevel', $event)"
      />

      <AppAutocompleteField
        input-id="student-study-status"
        :label="t('pages.studentsManagement.form.studyStatus')"
        icon="bi bi-journal-check"
        :model-value="draft.study_status"
        :options="studyStatusOptions"
        :placeholder="t('pages.studentsManagement.form.studyStatus')"
        :allow-create="false"
        :disabled="!canEdit"
        @update:model-value="$emit('update:studyStatus', $event)"
      />

      <AppAutocompleteField
        input-id="student-faculty"
        :label="t('pages.studentsManagement.form.faculty')"
        icon="bi bi-building"
        :model-value="draft.faculty_id"
        :options="facultyOptions"
        :placeholder="t('pages.studentsManagement.form.facultyPlaceholder')"
        :empty-text="t('pages.studentsManagement.form.noFacultyFound')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:facultyId', $event)"
      />

      <AppAutocompleteField
        input-id="student-department"
        :label="t('pages.studentsManagement.form.department')"
        icon="bi bi-diagram-3"
        :model-value="draft.department_id"
        :options="departmentOptions"
        :placeholder="t('pages.studentsManagement.form.departmentPlaceholder')"
        :empty-text="t('pages.studentsManagement.form.noDepartmentFound')"
        :disabled="!canEdit || !draft.faculty_id"
        @update:model-value="$emit('update:departmentId', $event)"
      />

      <AppCheckboxField
        input-id="student-is-active"
        :label="t('pages.studentsManagement.form.isActive')"
        :model-value="draft.is_active"
        :disabled="!canEdit"
        @update:model-value="$emit('update:isActive', $event)"
      />

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.studentsManagement.actions.save') }}
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
import AppAutocompleteField from '@/components/common/AppAutocompleteField.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import AppInputField from '@/components/common/AppInputField.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  draft: { type: Object, required: true },
  facultyOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: true },
})

defineEmits([
  'cancel',
  'save',
  'update:name',
  'update:email',
  'update:password',
  'update:completedHours',
  'update:yearLevel',
  'update:studyStatus',
  'update:facultyId',
  'update:departmentId',
  'update:isActive',
])

const { t } = useI18n()
const studyStatusOptions = computed(() => [
  { value: 'regular', label: t('pages.studentsManagement.studyStatus.regular') },
  { value: 'suspended', label: t('pages.studentsManagement.studyStatus.suspended') },
  { value: 'graduated', label: t('pages.studentsManagement.studyStatus.graduated') },
])
const title = computed(() =>
  props.isEditing
    ? t('pages.studentsManagement.form.editTitle')
    : t('pages.studentsManagement.form.createTitle'),
)
</script>
