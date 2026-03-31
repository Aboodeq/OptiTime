<template>
  <AppResponsiveGrid :columns="5" :lg-columns="3" :md-columns="2" :sm-columns="1" gap="0.65rem">
    <AppKpiCard
      :value="`${draft.capacity_threshold}%`"
      :label="t('pages.constraintsManagement.form.capacityThreshold')"
      icon="bi bi-percent"
    />
    <AppKpiCard
      :value="enabledStudyDaysCount"
      :label="t('pages.constraintsManagement.stats.studyDays')"
      icon="bi bi-calendar-week"
    />
    <AppKpiCard
      :value="draft.max_daily_lectures"
      :label="t('pages.constraintsManagement.form.maxDailyLectures')"
      icon="bi bi-list-ol"
    />
    <AppKpiCard
      :value="enabledSoftCount"
      :label="t('pages.constraintsManagement.sections.softConstraints')"
      :badge="`${enabledSoftCount}/${draft.soft_constraints.length}`"
      icon="bi bi-sliders"
    />
    <AppKpiCard
      :value="enabledHardCount"
      :label="t('pages.constraintsManagement.sections.hardConstraints')"
      :badge="`${enabledHardCount}/${draft.hard_constraints.length}`"
      icon="bi bi-shield-check"
    />
  </AppResponsiveGrid>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppResponsiveGrid from '@/components/common/AppResponsiveGrid.vue'
import AppKpiCard from '@/components/common/AppKpiCard.vue'

defineProps({
  draft: { type: Object, required: true },
  enabledStudyDaysCount: { type: Number, required: true },
  enabledSoftCount: { type: Number, required: true },
  enabledHardCount: { type: Number, required: true },
})

const { t } = useI18n()
</script>
