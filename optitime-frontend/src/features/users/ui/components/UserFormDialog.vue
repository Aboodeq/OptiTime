<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="user-name"
        :label="t('pages.usersManagement.form.name')"
        icon="bi bi-person"
        :model-value="draft.name"
        :placeholder="t('pages.usersManagement.form.name')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:name', $event)"
      />

      <AppInputField
        input-id="user-email"
        :label="t('pages.usersManagement.form.email')"
        icon="bi bi-envelope"
        :model-value="draft.email"
        :placeholder="t('pages.usersManagement.form.email')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:email', $event)"
      />

      <div>
        <AppInputField
          input-id="user-password"
          :label="t('pages.usersManagement.form.password')"
          icon="bi bi-lock"
          input-type="password"
          autocomplete="new-password"
          :model-value="draft.password"
          :placeholder="t('pages.usersManagement.form.password')"
          :disabled="!canEdit"
          @update:model-value="$emit('update:password', $event)"
        />
        <p v-if="isEditing" class="form-text text-secondary small mb-0 mt-1">
          {{ t('pages.usersManagement.form.passwordEditHint') }}
        </p>
      </div>

      <AppAutocompleteField
        input-id="user-faculty"
        :label="t('pages.usersManagement.form.faculty')"
        icon="bi bi-building"
        :model-value="draft.faculty_id"
        :options="facultyOptions"
        :placeholder="t('pages.usersManagement.form.facultyPlaceholder')"
        :allow-create="canCreateEntities"
        :empty-text="t('pages.usersManagement.form.noFacultyFound')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:facultyId', $event)"
        @create-option="$emit('create:faculty', $event)"
      />

      <AppAutocompleteField
        input-id="user-department"
        :label="t('pages.usersManagement.form.department')"
        icon="bi bi-diagram-3"
        :model-value="draft.department_id"
        :options="departmentOptions"
        :placeholder="t('pages.usersManagement.form.departmentPlaceholder')"
        :allow-create="canCreateEntities && Boolean(draft.faculty_id)"
        :empty-text="t('pages.usersManagement.form.noDepartmentFound')"
        :disabled="!canEdit"
        @update:model-value="$emit('update:departmentId', $event)"
        @create-option="$emit('create:department', $event)"
      />

      <div>
        <label class="form-label fw-semibold">{{ t('pages.usersManagement.form.role') }}</label>
        <select
          class="form-select"
          :disabled="!canEdit"
          :value="draft.role"
          @change="$emit('update:role', $event.target.value)"
        >
          <option v-for="roleOption in roleOptions" :key="roleOption.key" :value="roleOption.key">
            {{ roleOption.label }}
          </option>
        </select>
      </div>

      <AppCheckboxField
        input-id="user-is-active"
        :label="t('pages.usersManagement.form.isActive')"
        :model-value="draft.is_active"
        :disabled="!canEdit"
        @update:model-value="$emit('update:isActive', $event)"
      />

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.usersManagement.actions.save') }}
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
  roleOptions: { type: Array, default: () => [] },
  facultyOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
  canCreateEntities: { type: Boolean, default: false },
  canEdit: { type: Boolean, default: true },
})

defineEmits([
  'cancel',
  'save',
  'update:name',
  'update:email',
  'update:password',
  'update:facultyId',
  'update:departmentId',
  'update:role',
  'create:faculty',
  'create:department',
  'update:isActive',
])

const { t } = useI18n()
const title = computed(() =>
  props.isEditing
    ? t('pages.usersManagement.form.editTitle')
    : t('pages.usersManagement.form.createTitle'),
)
</script>
