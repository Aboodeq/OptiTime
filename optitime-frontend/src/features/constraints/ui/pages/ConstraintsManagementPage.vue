<template>
  <AppShell :page-title="t('routes.constraintsManagement')">
    <section class="dashboard-card w-100 constraints-page">
      <div class="constraints-toolbar">
        <div>
          <h1 class="h4 fw-bold mb-0">{{ t('pages.constraintsManagement.title') }}</h1>
          <p class="dashboard-card__meta mb-0">{{ t('pages.constraintsManagement.subtitle') }}</p>
        </div>
      </div>

      <div class="constraints-control-row">
        <AppButton
          type="button"
          class="save-all-legacy-btn"
          icon="bi bi-check2-circle"
          @click="handleSave"
        >
          {{ t('pages.constraintsManagement.actions.saveAll') }}
        </AppButton>
        <AppButton type="button" variant="outline" @click="handleResetToSaved">
          {{ t('pages.constraintsManagement.actions.resetUnsaved') }}
        </AppButton>
        <AppButton type="button" variant="outline" @click="handleResetToDefaults">
          {{ t('pages.constraintsManagement.actions.resetDefaults') }}
        </AppButton>
      </div>

      <div v-if="draft" class="constraints-body">
        <ConstraintsOverviewCards
          :draft="draft"
          :enabled-study-days-count="enabledStudyDaysCount"
          :enabled-soft-count="enabledSoftCount"
          :enabled-hard-count="enabledHardCount"
        />
        <ConstraintsRulesPanels
          :draft="draft"
          :enabled-soft-count="enabledSoftCount"
          :enabled-hard-count="enabledHardCount"
          :constraint-icons="constraintIcons"
          @update:draft="updateDraft"
        />
        <ConstraintsTimingAndDays :draft="draft" @update:draft="updateDraft" />
        <ConstraintsRoomPanel
          :draft="draft"
          :visible-room-constraints="visibleRoomConstraints"
          :constraint-icons="constraintIcons"
          @update:draft="updateDraft"
        />
      </div>
    </section>
  </AppShell>
</template>

<script setup>
import { computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useConstraintsManagementPage } from '@/features/constraints/model/composables/useConstraintsManagementPage'
import ConstraintsOverviewCards from '@/features/constraints/ui/components/ConstraintsOverviewCards.vue'
import ConstraintsRoomPanel from '@/features/constraints/ui/components/ConstraintsRoomPanel.vue'
import ConstraintsRulesPanels from '@/features/constraints/ui/components/ConstraintsRulesPanels.vue'
import ConstraintsTimingAndDays from '@/features/constraints/ui/components/ConstraintsTimingAndDays.vue'

const { t } = useI18n()
const toast = useToast()
const { draft, saveDraft, resetDraftToSaved, resetToDefault } = useConstraintsManagementPage()

const enabledSoftCount = computed(
  () => draft.value?.soft_constraints.filter((item) => item.enabled).length ?? 0,
)
const enabledHardCount = computed(
  () => draft.value?.hard_constraints.filter((item) => item.enabled).length ?? 0,
)
const enabledStudyDaysCount = computed(
  () => draft.value?.study_days.filter((item) => item.enabled).length ?? 0,
)
const visibleRoomConstraints = computed(() =>
  (draft.value?.room_constraints ?? []).filter((rule) => rule.key !== 'max_occupancy_threshold'),
)

const constraintIcons = Object.freeze({
  no_instructor_overlap: 'bi bi-person-x',
  no_room_overlap: 'bi bi-door-closed',
  room_capacity: 'bi bi-people',
  lab_for_lab: 'bi bi-pc-display',
  working_hours: 'bi bi-clock-history',
  instructor_pref: 'bi bi-person-heart',
  load_balance: 'bi bi-bar-chart',
  avoid_back_to_back: 'bi bi-arrow-left-right',
  student_gap: 'bi bi-arrows-collapse',
  morning_preference: 'bi bi-sunrise',
  department_proximity: 'bi bi-diagram-3',
  match_type: 'bi bi-building',
  room_proximity: 'bi bi-geo-alt',
  avoid_floor_scatter: 'bi bi-layers',
  equipment_match: 'bi bi-tools',
  max_occupancy_threshold: 'bi bi-speedometer2',
})

function handleSave() {
  const saved = saveDraft()
  if (!saved) {
    toast.error(t('pages.constraintsManagement.errors.invalidForm'))
    return
  }
  toast.success(t('pages.constraintsManagement.toasts.saved'))
}

function handleResetToDefaults() {
  const reset = resetToDefault()
  if (!reset) return
  toast.success(t('pages.constraintsManagement.toasts.defaultsReset'))
}

function handleResetToSaved() {
  resetDraftToSaved()
}

function updateDraft(nextDraft) {
  draft.value = nextDraft
}
</script>

<style scoped>
.constraints-page {
  background: #f7f8ff;
}

.constraints-toolbar {
  margin-bottom: 1rem;
}

.constraints-control-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-bottom: 1rem;
}

.save-all-legacy-btn {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #fff;
  border-radius: 12px;
  padding: 0.55rem 0.9rem;
  box-shadow: 0 8px 20px rgba(220, 38, 38, 0.28);
}

.save-all-legacy-btn:hover {
  transform: translateY(-1px);
}

.constraints-body {
  display: grid;
  gap: 1rem;
}

@media (max-width: 576px) {
  .constraints-control-row {
    display: grid;
    grid-template-columns: 1fr;
  }
}
</style>
