<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <div class="d-grid gap-3">
      <AppInputField
        input-id="role-name"
        :label="t('pages.rolesManagement.form.name')"
        icon="bi bi-person-badge"
        :model-value="draft.name"
        :placeholder="t('pages.rolesManagement.form.name')"
        :disabled="!canEditRole"
        @update:model-value="$emit('update:name', $event)"
      />

      <div>
        <label class="form-label fw-semibold">{{ t('pages.rolesManagement.form.color') }}</label>
        <div class="d-flex align-items-center gap-2">
          <input
            :value="draft.color"
            type="color"
            class="form-control form-control-color"
            :disabled="!canEditRole"
            @input="$emit('update:color', $event.target.value)"
          />
          <span class="small text-secondary">{{ draft.color }}</span>
        </div>
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

defineEmits(['cancel', 'save', 'update:name', 'update:color', 'toggle-permission'])

const { t } = useI18n()
const title = computed(() =>
  props.isEditing
    ? t('pages.rolesManagement.form.editTitle')
    : t('pages.rolesManagement.form.createTitle'),
)
</script>
