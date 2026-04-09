<template>
  <div class="algorithm-picker" role="radiogroup" :aria-label="label">
    <AppButton
      class="algorithm-picker__item"
      :class="{ 'algorithm-picker__item--active': modelValue === 'genetic' }"
      variant="outline"
      @click="$emit('update:modelValue', 'genetic')"
    >
      <i class="bi bi-diagram-3"></i>
      <span class="algorithm-picker__text">
        <strong>{{ t('pages.coordinatorWeeklySchedule.algorithms.genetic') }}</strong>
        <small>{{ t('pages.coordinatorWeeklySchedule.algorithms.geneticDescription') }}</small>
      </span>
    </AppButton>
    <AppButton
      class="algorithm-picker__item"
      :class="{ 'algorithm-picker__item--active': modelValue === 'backtracking' }"
      variant="outline"
      @click="$emit('update:modelValue', 'backtracking')"
    >
      <i class="bi bi-arrow-counterclockwise"></i>
      <span class="algorithm-picker__text">
        <strong>{{ t('pages.coordinatorWeeklySchedule.algorithms.backtracking') }}</strong>
        <small>{{ t('pages.coordinatorWeeklySchedule.algorithms.backtrackingDescription') }}</small>
      </span>
    </AppButton>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'

defineProps({
  modelValue: { type: String, default: 'genetic' },
  label: { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const { t } = useI18n()
</script>

<style scoped>
.algorithm-picker {
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.algorithm-picker__item {
  border-color: #dbe3f3;
  background: #ffffff;
  color: #334155;
  border-radius: 10px;
  padding: 0.55rem 0.72rem;
  display: inline-flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.88rem;
  min-width: 220px;
}

.algorithm-picker__item i {
  font-size: 1.05rem;
  margin-top: 0.08rem;
}

.algorithm-picker__text {
  display: grid;
  text-align: start;
  gap: 0.1rem;
}

.algorithm-picker__text strong {
  font-size: 0.88rem;
  line-height: 1.1;
}

.algorithm-picker__text small {
  color: #64748b;
  font-size: 0.76rem;
  line-height: 1.25;
  font-weight: 500;
}

.algorithm-picker__item--active {
  border-color: #4f46e5;
  background: #eef2ff;
  color: #312e81;
}

@media (max-width: 768px) {
  .algorithm-picker__item {
    min-width: 100%;
  }
}
</style>
