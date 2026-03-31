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

      <div>
        <label class="form-label fw-semibold">{{ t('pages.rolesManagement.form.color') }}</label>
        <div class="d-flex align-items-center gap-2">
          <input
            :value="draft.sidebar_color"
            type="color"
            class="form-control form-control-color"
            :disabled="!canEditRole"
            @input="$emit('update:sidebarColor', $event.target.value)"
          />
          <span class="small text-secondary">{{ draft.sidebar_color }}</span>
        </div>
      </div>

      <div>
        <label class="form-label fw-semibold">{{ t('pages.rolesManagement.form.description') }}</label>
        <textarea
          class="form-control"
          rows="3"
          :value="draft.description"
          :disabled="!canEditRole"
          @input="$emit('update:description', $event.target.value)"
        ></textarea>
      </div>

      <div class="form-check">
        <input
          id="role-is-active"
          class="form-check-input"
          type="checkbox"
          :checked="draft.is_active"
          :disabled="!canEditRole"
          @change="$emit('update:isActive', $event.target.checked)"
        />
        <label for="role-is-active" class="form-check-label">
          {{ t('pages.rolesManagement.form.isActive') }}
        </label>
      </div>

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
import AppDialog from '@/components/common/AppDialog.vue'
import AppInputField from '@/components/common/AppInputField.vue'

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
