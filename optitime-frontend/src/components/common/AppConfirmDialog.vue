<template>
  <AppDialog :open="open" :title="title" @close="$emit('cancel')">
    <p class="text-secondary mb-4">{{ message }}</p>
    <div class="d-flex justify-content-end gap-2">
      <AppButton variant="outline" @click="$emit('cancel')">
        {{ cancelText }}
      </AppButton>
      <AppButton @click="$emit('confirm')">
        {{ confirmText }}
      </AppButton>
    </div>
  </AppDialog>
</template>

<script setup>
import AppButton from '@/components/common/AppButton.vue'
import AppDialog from '@/components/common/AppDialog.vue'

defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
  message: { type: String, required: true },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
})

defineEmits(['confirm', 'cancel'])
</script>
