<template>
  <AppSectionPanel
    :title="t('pages.constraintsManagement.sections.roomConstraints')"
    :subtitle="t('pages.constraintsManagement.panelHints.room')"
    icon="bi bi-building"
    tone="soft"
  >
    <div class="constraints-rule-list">
      <AppToggleItemRow
        v-for="rule in visibleRoomConstraints"
        :key="rule.key"
        v-model="rule.enabled"
        :title="t(`pages.constraintsManagement.constraintLabels.${rule.key}`)"
        :description="t(`pages.constraintsManagement.constraintDescriptions.${rule.key}`)"
        :icon="constraintIcons[rule.key]"
      />
    </div>
    <div class="room-threshold-row">
      <AppSettingRow
        icon="bi bi-speedometer2"
        :title="t('pages.constraintsManagement.constraintLabels.max_occupancy_threshold')"
      >
        <AppRangeField
          :model-value="draft.capacity_threshold"
          :min="0"
          :max="100"
          :step="1"
          @update:model-value="draft.capacity_threshold = $event"
        />
      </AppSettingRow>
    </div>
  </AppSectionPanel>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppRangeField from '@/components/common/AppRangeField.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppSettingRow from '@/components/common/AppSettingRow.vue'
import AppToggleItemRow from '@/components/common/AppToggleItemRow.vue'

defineProps({
  draft: { type: Object, required: true },
  visibleRoomConstraints: { type: Array, required: true },
  constraintIcons: { type: Object, required: true },
})

const { t } = useI18n()
</script>

<style scoped>
.constraints-rule-list {
  display: grid;
  gap: 0.25rem;
}

.room-threshold-row {
  border-top: 1px solid #edf0fa;
  margin-top: 0.45rem;
  padding-top: 0.5rem;
}
</style>
