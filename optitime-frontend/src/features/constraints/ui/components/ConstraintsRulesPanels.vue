<template>
  <AppResponsiveGrid :columns="2" :md-columns="1" gap="0.9rem">
    <AppSectionPanel
      :title="t('pages.constraintsManagement.sections.softConstraints')"
      :subtitle="t('pages.constraintsManagement.panelHints.soft')"
      :badge="`${enabledSoftCount}/${draft.soft_constraints.length}`"
      icon="bi bi-sliders"
      tone="soft"
    >
      <div class="constraints-rule-list">
        <AppToggleItemRow
          v-for="constraint in draft.soft_constraints"
          :key="constraint.key"
          v-model="constraint.enabled"
          :title="t(`pages.constraintsManagement.constraintLabels.${constraint.key}`)"
          :description="t(`pages.constraintsManagement.constraintDescriptions.${constraint.key}`)"
          :icon="constraintIcons[constraint.key]"
        />
      </div>
    </AppSectionPanel>

    <AppSectionPanel
      :title="t('pages.constraintsManagement.sections.hardConstraints')"
      :subtitle="t('pages.constraintsManagement.panelHints.hard')"
      :badge="`${enabledHardCount}/${draft.hard_constraints.length}`"
      icon="bi bi-shield-check"
      tone="hard"
    >
      <div class="constraints-rule-list">
        <AppToggleItemRow
          v-for="constraint in draft.hard_constraints"
          :key="constraint.key"
          v-model="constraint.enabled"
          :title="t(`pages.constraintsManagement.constraintLabels.${constraint.key}`)"
          :description="t(`pages.constraintsManagement.constraintDescriptions.${constraint.key}`)"
          :icon="constraintIcons[constraint.key]"
        />
      </div>
    </AppSectionPanel>
  </AppResponsiveGrid>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppResponsiveGrid from '@/components/common/AppResponsiveGrid.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppToggleItemRow from '@/components/common/AppToggleItemRow.vue'

defineProps({
  draft: { type: Object, required: true },
  enabledSoftCount: { type: Number, required: true },
  enabledHardCount: { type: Number, required: true },
  constraintIcons: { type: Object, required: true },
})

const { t } = useI18n()
</script>

<style scoped>
.constraints-rule-list {
  display: grid;
  gap: 0.25rem;
  width: 100%;
  min-width: 0;
}
</style>
