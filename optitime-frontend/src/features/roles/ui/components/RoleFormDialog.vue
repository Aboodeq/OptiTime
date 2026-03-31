<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="role-code"
        :label="t('pages.rolesManagement.form.code')"
        icon="bi bi-upc-scan"
        :model-value="draft.code"
        :placeholder="t('pages.rolesManagement.form.code')"
        :disabled="!canEditRole"
        @update:model-value="$emit('update:code', $event)"
      />

      <AppInputField
        input-id="role-name-ar"
        :label="t('pages.rolesManagement.form.nameAr')"
        icon="bi bi-translate"
        :model-value="draft.name_ar"
        :placeholder="t('pages.rolesManagement.form.nameAr')"
        :disabled="!canEditRole"
        @update:model-value="$emit('update:nameAr', $event)"
      />

      <AppInputField
        input-id="role-name-en"
        :label="t('pages.rolesManagement.form.nameEn')"
        icon="bi bi-fonts"
        :model-value="draft.name_en"
        :placeholder="t('pages.rolesManagement.form.nameEn')"
        :disabled="!canEditRole"
        @update:model-value="$emit('update:nameEn', $event)"
      />

      <AppColorField
        :label="t('pages.rolesManagement.form.color')"
        :model-value="draft.sidebar_color"
        :disabled="!canEditRole"
        @update:model-value="$emit('update:sidebarColor', $event)"
      />

      <AppTextareaField
        input-id="role-description"
        :label="t('pages.rolesManagement.form.description')"
        :model-value="draft.description"
        :disabled="!canEditRole"
        @update:model-value="$emit('update:description', $event)"
      />

      <AppCheckboxField
        input-id="role-is-active"
        :label="t('pages.rolesManagement.form.isActive')"
        :model-value="draft.is_active"
        :disabled="!canEditRole"
        @update:model-value="$emit('update:isActive', $event)"
      />

      <div>
        <label class="form-label fw-semibold">{{ t('pages.rolesManagement.form.permissions') }}</label>
        <div class="d-flex flex-wrap gap-2">
          <button
            v-for="permission in permissionCatalog"
            :key="permission"
            type="button"
            class="btn btn-sm"
            :class="draft.permissions.includes(permission) ? 'btn-primary' : 'btn-outline-secondary'"
            :disabled="!canEditPermissions"
            @click="$emit('toggle-permission', permission)"
          >
            {{ permission }}
          </button>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 pt-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEditRole" @click="$emit('save')">
          {{ t('pages.rolesManagement.actions.save') }}
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
import AppInputField from '@/components/common/AppInputField.vue'
import AppTextareaField from '@/components/common/AppTextareaField.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  draft: { type: Object, required: true },
  permissionCatalog: { type: Array, default: () => [] },
  canEditRole: { type: Boolean, default: true },
  canEditPermissions: { type: Boolean, default: true },
})

defineEmits([
  'cancel',
  'save',
  'update:code',
  'update:nameAr',
  'update:nameEn',
  'update:sidebarColor',
  'update:description',
  'update:isActive',
  'toggle-permission',
])

const { t } = useI18n()
const title = computed(() =>
  props.isEditing
    ? t('pages.rolesManagement.form.editTitle')
    : t('pages.rolesManagement.form.createTitle'),
)
</script>
