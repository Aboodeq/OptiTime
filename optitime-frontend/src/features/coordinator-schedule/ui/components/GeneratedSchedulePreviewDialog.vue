<template>
  <AppDialog :open="open" :title="t('pages.coordinatorWeeklySchedule.preview.title')" @close="$emit('cancel')">
    <p class="dashboard-card__meta mb-3">{{ t('pages.coordinatorWeeklySchedule.preview.subtitle') }}</p>
    <WeeklyScheduleBoard
      :sessions="sessions"
      :day-options="dayOptions"
      :time-slots="timeSlots"
      :blocked-slot-starts="blockedSlotStarts"
      :conflict-session-ids="conflictSessionIds"
      :draggable="false"
    />
    <div class="preview-dialog__actions">
      <AppButton type="button" variant="outline" @click="$emit('cancel')">
        {{ t('pages.coordinatorWeeklySchedule.actions.cancelPreview') }}
      </AppButton>
      <AppButton type="button" :disabled="!canConfirm" @click="$emit('confirm')">
        {{ t('pages.coordinatorWeeklySchedule.actions.confirmGenerated') }}
      </AppButton>
    </div>
  </AppDialog>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import WeeklyScheduleBoard from '@/features/coordinator-schedule/ui/components/WeeklyScheduleBoard.vue'

defineProps({
  open: { type: Boolean, default: false },
  sessions: { type: Array, default: () => [] },
  dayOptions: { type: Array, default: () => [] },
  timeSlots: { type: Array, default: () => [] },
  blockedSlotStarts: { type: Array, default: () => [] },
  conflictSessionIds: { type: Array, default: () => [] },
  canConfirm: { type: Boolean, default: true },
})

defineEmits(['confirm', 'cancel'])

const { t } = useI18n()
</script>

<style scoped>
.preview-dialog__actions {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
}
</style>
