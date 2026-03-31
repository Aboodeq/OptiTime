<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="instructor-name"
        :label="t('pages.instructorsManagement.form.name')"
        icon="bi bi-person"
        :model-value="draft.name"
        :placeholder="t('pages.instructorsManagement.form.name')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name', $event)"
      />

      <AppInputField
        input-id="instructor-email"
        :label="t('pages.instructorsManagement.form.email')"
        icon="bi bi-envelope"
        :model-value="draft.email"
        :placeholder="t('pages.instructorsManagement.form.email')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:email', $event)"
      />

      <AppInputField
        input-id="instructor-password"
        :label="t('pages.instructorsManagement.form.password')"
        icon="bi bi-lock"
        input-type="password"
        autocomplete="new-password"
        :model-value="draft.password"
        :placeholder="t('pages.instructorsManagement.form.password')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:password', $event)"
      />

      <AppAutocompleteField
        input-id="instructor-speciality"
        :label="t('pages.instructorsManagement.form.speciality')"
        icon="bi bi-bookmark-star"
        :model-value="draft.speciality_id"
        :options="specialityOptions"
        :placeholder="t('pages.instructorsManagement.form.specialityPlaceholder')"
        :allow-create="canCreateSpecialities"
        :empty-text="t('pages.instructorsManagement.form.noSpecialityFound')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:specialityId', $event)"
        @create-option="$emit('create:speciality', $event)"
      />

      <AppInputField
        input-id="instructor-min-work-hours"
        :label="t('pages.instructorsManagement.form.minWorkHoursPerWeek')"
        icon="bi bi-clock"
        input-type="number"
        :model-value="draft.min_work_hours_per_week"
        :placeholder="t('pages.instructorsManagement.form.minWorkHoursPerWeek')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:minWorkHoursPerWeek', $event)"
      />

      <AppInputField
        input-id="instructor-max-work-hours"
        :label="t('pages.instructorsManagement.form.maxWorkHoursPerWeek')"
        icon="bi bi-clock-history"
        input-type="number"
        :model-value="draft.max_work_hours_per_week"
        :placeholder="t('pages.instructorsManagement.form.maxWorkHoursPerWeek')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:maxWorkHoursPerWeek', $event)"
      />

      <AppAutocompleteField
        input-id="instructor-faculty"
        :label="t('pages.instructorsManagement.form.faculty')"
        icon="bi bi-building"
        :model-value="draft.faculty_id"
        :options="facultyOptions"
        :placeholder="t('pages.instructorsManagement.form.facultyPlaceholder')"
        :allow-create="canCreateEntities"
        :empty-text="t('pages.instructorsManagement.form.noFacultyFound')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:facultyId', $event)"
        @create-option="$emit('create:faculty', $event)"
      />

      <AppAutocompleteField
        input-id="instructor-department"
        :label="t('pages.instructorsManagement.form.department')"
        icon="bi bi-diagram-3"
        :model-value="draft.department_id"
        :options="departmentOptions"
        :placeholder="t('pages.instructorsManagement.form.departmentPlaceholder')"
        :allow-create="canCreateEntities && Boolean(draft.faculty_id)"
        :empty-text="t('pages.instructorsManagement.form.noDepartmentFound')"
        :disabled="!canEdit || !draft.faculty_id"
        @update:model-value="$emit('update:departmentId', $event)"
        @create-option="$emit('create:department', $event)"
      />

      <AppCheckboxField
        input-id="instructor-is-active"
        :label="t('pages.instructorsManagement.form.isActive')"
        :model-value="draft.is_active"
        :disabled="!canEdit"
        @update:model-value="$emit('update:isActive', $event)"
      />

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.instructorsManagement.actions.save') }}
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
  specialityOptions: { type: Array, default: () => [] },
  facultyOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
  canCreateEntities: { type: Boolean, default: false },
  canCreateSpecialities: { type: Boolean, default: false },
  canEdit: { type: Boolean, default: true },
})

defineEmits([
  'cancel',
  'save',
  'update:name',
  'update:email',
  'update:password',
  'update:specialityId',
  'update:minWorkHoursPerWeek',
  'update:maxWorkHoursPerWeek',
  'update:facultyId',
  'update:departmentId',
  'create:speciality',
  'create:faculty',
  'create:department',
  'update:isActive',
])

const { t } = useI18n()
const title = computed(() =>
  props.isEditing
    ? t('pages.instructorsManagement.form.editTitle')
    : t('pages.instructorsManagement.form.createTitle'),
)
</script>
