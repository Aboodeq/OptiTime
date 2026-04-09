<template>
  <AppResponsiveGrid :columns="2" :md-columns="1" gap="0.9rem">
    <AppSectionPanel
      :title="t('pages.constraintsManagement.sections.general')"
      :subtitle="t('pages.constraintsManagement.panelHints.time')"
      icon="bi bi-clock-history"
      tone="soft"
    >
      <div class="time-settings-rows">
        <AppSettingRow
          icon="bi bi-clock"
          :title="t('pages.constraintsManagement.form.dayStart')"
          :description="t('pages.constraintsManagement.formHints.dayStart')"
        >
          <AppInputField
            input-id="constraints-day-start"
            :label="t('pages.constraintsManagement.form.dayStart')"
            icon="bi bi-clock"
            input-type="time"
            :model-value="draft.day_start"
            @update:model-value="draft.day_start = $event"
          />
        </AppSettingRow>

        <AppSettingRow
          icon="bi bi-clock-fill"
          :title="t('pages.constraintsManagement.form.dayEnd')"
          :description="t('pages.constraintsManagement.formHints.dayEnd')"
        >
          <AppInputField
            input-id="constraints-day-end"
            :label="t('pages.constraintsManagement.form.dayEnd')"
            icon="bi bi-clock-fill"
            input-type="time"
            :model-value="draft.day_end"
            @update:model-value="draft.day_end = $event"
          />
        </AppSettingRow>

        <AppSettingRow
          icon="bi bi-hourglass-split"
          :title="t('pages.constraintsManagement.form.slotMinutes')"
          :description="t('pages.constraintsManagement.formHints.slotMinutes')"
        >
          <AppInputField
            input-id="constraints-slot-minutes"
            :label="t('pages.constraintsManagement.form.slotMinutes')"
            icon="bi bi-hourglass-split"
            input-type="number"
            :model-value="String(draft.slot_minutes ?? '')"
            @update:model-value="draft.slot_minutes = toNumber($event)"
          />
        </AppSettingRow>

        <AppSettingRow
          icon="bi bi-pause-circle"
          :title="t('pages.constraintsManagement.form.gapMinutes')"
          :description="t('pages.constraintsManagement.formHints.gapMinutes')"
        >
          <AppInputField
            input-id="constraints-gap-minutes"
            :label="t('pages.constraintsManagement.form.gapMinutes')"
            icon="bi bi-pause-circle"
            input-type="number"
            :model-value="String(draft.gap_minutes ?? '')"
            @update:model-value="draft.gap_minutes = toNumber($event)"
          />
        </AppSettingRow>

        <AppSettingRow
          icon="bi bi-list-ol"
          :title="t('pages.constraintsManagement.form.maxDailyLectures')"
          :description="t('pages.constraintsManagement.formHints.maxDailyLectures')"
        >
          <AppInputField
            input-id="constraints-max-daily-lectures"
            :label="t('pages.constraintsManagement.form.maxDailyLectures')"
            icon="bi bi-list-ol"
            input-type="number"
            :model-value="String(draft.max_daily_lectures ?? '')"
            @update:model-value="draft.max_daily_lectures = toNumber($event)"
          />
        </AppSettingRow>
      </div>
    </AppSectionPanel>

    <AppSectionPanel
      :title="t('pages.constraintsManagement.sections.studyDays')"
      :subtitle="t('pages.constraintsManagement.panelHints.studyDays')"
      icon="bi bi-calendar-week"
      tone="soft"
    >
      <div class="study-breaks-section">
        <div class="study-days-grid">
          <button
            v-for="day in draft.study_days"
            :key="day.value"
            type="button"
            class="study-day-chip"
            :class="{ 'study-day-chip--active': day.enabled }"
            @click="day.enabled = !day.enabled"
          >
            <span class="study-day-chip__label">{{ t(`pages.constraintsManagement.days.${day.value}`) }}</span>
            <small class="study-day-chip__code">{{ day.value }}</small>
          </button>
        </div>

        <div class="breaks-block">
          <h4 class="breaks-block__title">{{ t('pages.constraintsManagement.sections.breakTimes') }}</h4>
          <div v-for="breakTime in draft.break_times" :key="breakTime.key" class="break-row">
            <AppToggleSwitch
              :model-value="breakTime.enabled"
              @update:model-value="breakTime.enabled = $event"
            />
            <div class="break-row__content">
              <div class="break-row__name">
                {{ t(`pages.constraintsManagement.constraintLabels.${breakTime.key}`) }}
              </div>
              <small class="break-row__range">{{ breakTime.start }} — {{ breakTime.end }}</small>
            </div>
            <div class="break-row__inputs">
              <AppInputField
                :input-id="`break-start-${breakTime.key}`"
                label="Start"
                icon="bi bi-clock"
                input-type="time"
                :model-value="breakTime.start"
                @update:model-value="breakTime.start = $event"
              />
              <AppInputField
                :input-id="`break-end-${breakTime.key}`"
                label="End"
                icon="bi bi-clock-fill"
                input-type="time"
                :model-value="breakTime.end"
                @update:model-value="breakTime.end = $event"
              />
            </div>
          </div>
        </div>
      </div>
    </AppSectionPanel>
  </AppResponsiveGrid>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppInputField from '@/components/common/AppInputField.vue'
import AppResponsiveGrid from '@/components/common/AppResponsiveGrid.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppSettingRow from '@/components/common/AppSettingRow.vue'
import AppToggleSwitch from '@/components/common/AppToggleSwitch.vue'

defineProps({
  draft: { type: Object, required: true },
})

const { t } = useI18n()

function toNumber(value) {
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : 0
}
</script>

<style scoped>
.time-settings-rows {
  display: grid;
  gap: 0.35rem;
}

.time-settings-rows :deep(.app-input-field) {
  min-width: 210px;
  width: 100%;
}

.time-settings-rows :deep(.app-input-field__label) {
  display: none;
}

.study-breaks-section {
  display: grid;
  gap: 0.85rem;
  min-width: 0;
}

.study-days-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(4.5rem, 1fr));
  gap: 0.4rem;
  width: 100%;
  min-width: 0;
}

.study-day-chip {
  border: 1px solid #dfe3f1;
  background: #f9faff;
  border-radius: 10px;
  padding: 0.45rem 0.3rem;
  text-align: center;
  transition: all 0.2s ease;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
}

.study-day-chip--active {
  border-color: #6b5bf8;
  background: #eeecff;
}

.study-day-chip__label {
  display: block;
  font-size: clamp(0.62rem, 1.5vw + 0.35rem, 0.78rem);
  font-weight: 700;
  line-height: 1.2;
  overflow-wrap: break-word;
  hyphens: auto;
}

.study-day-chip__code {
  color: #9ca3b7;
  text-transform: capitalize;
}

.breaks-block {
  border-top: 1px solid #edf0fa;
  padding-top: 0.7rem;
}

.breaks-block__title {
  margin: 0 0 0.45rem;
  font-size: 0.86rem;
  font-weight: 800;
}

.break-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.65rem;
  align-items: center;
  padding: 0.35rem 0.25rem;
}

.break-row__name {
  font-size: 0.88rem;
  font-weight: 700;
}

.break-row__range {
  color: #9aa2b7;
}

.break-row__inputs {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 145px));
  gap: 0.4rem;
  justify-content: end;
}

.break-row__inputs :deep(.app-input-field) {
  width: 145px;
}

.break-row__inputs :deep(.app-input-field__label) {
  display: none;
}

.break-row__inputs :deep(.app-input-field__wrapper) {
  min-height: 38px;
}

.break-row__inputs :deep(.app-input-field__control) {
  padding: 8px 0;
}

@media (max-width: 992px) {
  .break-row {
    grid-template-columns: auto 1fr;
  }

  .break-row__inputs {
    grid-column: 1 / -1;
    justify-content: start;
  }
}

@media (max-width: 576px) {
  .study-days-grid {
    grid-template-columns: repeat(auto-fit, minmax(4.25rem, 1fr));
  }

  .break-row__inputs {
    grid-template-columns: 1fr;
  }

  .break-row__inputs :deep(.app-input-field) {
    width: 100%;
  }

  .time-settings-rows :deep(.app-input-field) {
    min-width: 0;
  }
}
</style>
