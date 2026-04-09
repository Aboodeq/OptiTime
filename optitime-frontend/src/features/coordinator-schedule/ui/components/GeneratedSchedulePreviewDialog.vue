<template>
  <AppDialog :open="open" :title="t('pages.coordinatorWeeklySchedule.preview.title')" @close="$emit('cancel')">
    <p class="dashboard-card__meta mb-3">{{ t('pages.coordinatorWeeklySchedule.preview.subtitle') }}</p>
    <div v-if="metricRows.length > 0" class="preview-dialog__metrics">
      <div v-for="row in metricRows" :key="row.key" class="preview-dialog__metric">
        <span class="preview-dialog__metric-label">{{ row.label }}</span>
        <strong class="preview-dialog__metric-value">{{ row.value }}</strong>
      </div>
    </div>
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
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import WeeklyScheduleBoard from '@/features/coordinator-schedule/ui/components/WeeklyScheduleBoard.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  sessions: { type: Array, default: () => [] },
  evaluationMeta: { type: Object, default: () => ({}) },
  dayOptions: { type: Array, default: () => [] },
  timeSlots: { type: Array, default: () => [] },
  blockedSlotStarts: { type: Array, default: () => [] },
  conflictSessionIds: { type: Array, default: () => [] },
  canConfirm: { type: Boolean, default: true },
})

defineEmits(['confirm', 'cancel'])

const { t } = useI18n()

const metricRows = computed(() => {
  const meta = props.evaluationMeta && typeof props.evaluationMeta === 'object' ? props.evaluationMeta : {}
  const rows = [
    { key: 'fitness', label: 'Fitness', value: meta.fitness },
    { key: 'hard_violations', label: 'Hard Violations', value: meta.hard_violations },
    { key: 'soft_penalty', label: 'Soft Penalty', value: meta.soft_penalty },
    { key: 'generations', label: 'Generations', value: meta.generations },
    { key: 'backtracks', label: 'Backtracks', value: meta.backtracks },
    { key: 'recursive_steps', label: 'Recursive Steps', value: meta.recursive_steps },
  ]
  return rows
    .filter((row) => row.value !== undefined && row.value !== null && `${row.value}` !== '')
    .map((row) => ({ ...row, value: `${row.value}` }))
})
</script>

<style scoped>
.preview-dialog__metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.6rem;
  margin-bottom: 0.9rem;
}

.preview-dialog__metric {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.55rem 0.65rem;
  background: #f8fafc;
}

.preview-dialog__metric-label {
  display: block;
  font-size: 0.76rem;
  color: #64748b;
}

.preview-dialog__metric-value {
  display: block;
  font-size: 0.95rem;
}

.preview-dialog__actions {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
}
</style>
