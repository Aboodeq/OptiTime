<template>
  <section class="schedule-kpis">
    <AppKpiCard :value="kpis.totalSessions" :label="t('pages.coordinatorWeeklySchedule.kpis.totalSessions')" icon="bi bi-collection" />
    <AppKpiCard :value="kpis.roomUtilization" :label="t('pages.coordinatorWeeklySchedule.kpis.roomUtilization')" icon="bi bi-door-open" />
    <AppKpiCard :value="kpis.instructorBalance" :label="t('pages.coordinatorWeeklySchedule.kpis.instructorBalance')" icon="bi bi-people" />
    <AppKpiCard :value="kpis.conflicts" :label="t('pages.coordinatorWeeklySchedule.kpis.conflicts')" icon="bi bi-exclamation-triangle" />
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppKpiCard from '@/components/common/AppKpiCard.vue'

defineProps({
  kpis: {
    type: Object,
    default: () => ({
      totalSessions: 0,
      roomUtilization: '0',
      instructorBalance: '0%',
      conflicts: 0,
    }),
  },
})

const { t } = useI18n()
</script>

<style scoped>
.schedule-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.75rem;
}

@media (max-width: 992px) {
  .schedule-kpis {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
