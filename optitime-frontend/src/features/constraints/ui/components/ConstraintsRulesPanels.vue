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
          :model-value="constraint.enabled"
          :title="t(`pages.constraintsManagement.constraintLabels.${constraint.key}`)"
          :description="t(`pages.constraintsManagement.constraintDescriptions.${constraint.key}`)"
          :icon="constraintIcons[constraint.key]"
          @update:model-value="updateConstraint('soft_constraints', constraint.key, $event)"
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
          :model-value="constraint.enabled"
          :title="t(`pages.constraintsManagement.constraintLabels.${constraint.key}`)"
          :description="t(`pages.constraintsManagement.constraintDescriptions.${constraint.key}`)"
          :icon="constraintIcons[constraint.key]"
          @update:model-value="updateConstraint('hard_constraints', constraint.key, $event)"
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

const props = defineProps({
  draft: { type: Object, required: true },
  enabledSoftCount: { type: Number, required: true },
  enabledHardCount: { type: Number, required: true },
  constraintIcons: { type: Object, required: true },
})
const emit = defineEmits(['update:draft'])

const { t } = useI18n()

function updateConstraint(collectionKey, constraintKey, enabled) {
  emit('update:draft', {
    ...props.draft,
    [collectionKey]: props.draft[collectionKey].map((constraint) =>
      constraint.key === constraintKey ? { ...constraint, enabled } : constraint,
    ),
  })
}
</script>

<style scoped>
.constraints-rule-list {
  display: grid;
  gap: 0.25rem;
}
</style>
