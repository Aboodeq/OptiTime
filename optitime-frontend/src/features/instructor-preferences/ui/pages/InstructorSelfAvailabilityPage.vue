<template>
  <AppShell :page-title="t('routes.instructorSelfAvailability')">
    <section class="dashboard-card w-100 self-availability-page">
      <div class="self-availability-toolbar">
        <div>
          <h1 class="h4 fw-bold mb-0">{{ t('pages.instructorSelfAvailability.title') }}</h1>
          <p class="dashboard-card__meta mb-0">{{ t('pages.instructorSelfAvailability.subtitle') }}</p>
        </div>
        <AppButton type="button" :tone-color="authStore.roleColor" @click="handleSave">
          {{ t('pages.instructorSelfAvailability.actions.save') }}
        </AppButton>
      </div>

      <div class="self-availability-grid">
        <AppSectionPanel
          :title="t('pages.instructorSelfAvailability.sections.days')"
          :subtitle="t('pages.instructorSelfAvailability.hints.days')"
        >
          <p class="dashboard-card__meta mb-2">
            {{
              t('pages.instructorSelfAvailability.weeklyHoursRange', {
                min: weeklyHoursRange?.min ?? 0,
                max: weeklyHoursRange?.max ?? 0,
                current: weeklyChosenHours,
              })
            }}
          </p>
          <WeeklyAvailabilityGrid
            :model-value="draft.cells"
            :day-options="localizedDayOptions"
            :time-slots="timeSlots"
            :title="t('pages.instructorSelfAvailability.grid.title')"
            :time-header="t('pages.instructorSelfAvailability.grid.time')"
            :preferred-label="t('pages.instructorSelfAvailability.grid.preferred')"
            :available-label="t('pages.instructorSelfAvailability.grid.available')"
            :unavailable-label="t('pages.instructorSelfAvailability.grid.unavailable')"
            :blocked-label="t('pages.instructorSelfAvailability.grid.blocked')"
            :blocked-slots="blockedSlotStarts"
            @update:model-value="draft.cells = $event"
          />
        </AppSectionPanel>

      </div>
    </section>
  </AppShell>
</template>

<script setup>
import { computed } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useInstructorSelfAvailabilityPage } from '@/features/instructor-preferences/model/composables/useInstructorSelfAvailabilityPage'
import WeeklyAvailabilityGrid from '@/features/instructor-preferences/ui/components/WeeklyAvailabilityGrid.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const {
  dayOptions,
  slotMinutes,
  gapMinutes,
  dayStart,
  dayEnd,
  breakTimes,
  weeklyHoursRange,
  draft,
  saveAvailability,
} = useInstructorSelfAvailabilityPage()

const localizedDayOptions = computed(() => {
  const labels = {
    sun: t('pages.constraintsManagement.days.sun'),
    mon: t('pages.constraintsManagement.days.mon'),
    tue: t('pages.constraintsManagement.days.tue'),
    wed: t('pages.constraintsManagement.days.wed'),
    thu: t('pages.constraintsManagement.days.thu'),
    fri: t('pages.constraintsManagement.days.fri'),
    sat: t('pages.constraintsManagement.days.sat'),
  }
  return dayOptions.value.map((item) => ({
    value: item.value,
    label: labels[item.value] ?? item.value,
  }))
})

function toMinutes(time) {
  if (!time || typeof time !== 'string') return 0
  const [h, m] = time.split(':').map(Number)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return 0
  return h * 60 + m
}

function toTime(minutes) {
  const safe = Math.max(0, minutes)
  const h = String(Math.floor(safe / 60)).padStart(2, '0')
  const m = String(safe % 60).padStart(2, '0')
  return `${h}:${m}`
}

const timeSlots = computed(() => {
  const start = toMinutes(dayStart.value)
  const end = toMinutes(dayEnd.value)
  const lectureMinutes = Math.max(1, Number(slotMinutes.value) || 60)
  const gap = Math.max(0, Number(gapMinutes.value) || 0)
  const cycle = lectureMinutes + gap
  if (end <= start) return []
  const slots = []
  for (let value = start; value + lectureMinutes <= end; value += cycle) {
    slots.push({
      start: toTime(value),
      end: toTime(value + lectureMinutes),
      label: toTime(value),
    })
  }
  return slots
})

function overlaps(startA, endA, startB, endB) {
  return startA < endB && endA > startB
}

const blockedSlotStarts = computed(() => {
  const breaks = (breakTimes.value ?? []).filter((item) => item?.start && item?.end)
  if (breaks.length === 0) return []
  const blocked = new Set()
  for (const slot of timeSlots.value) {
    const slotStart = toMinutes(slot.start)
    const slotEnd = toMinutes(slot.end)
    const intersectsBreak = breaks.some((item) =>
      overlaps(slotStart, slotEnd, toMinutes(item.start), toMinutes(item.end)),
    )
    if (intersectsBreak) blocked.add(slot.start)
  }
  return [...blocked]
})

const unavailableSlotsCount = computed(
  () => (Array.isArray(draft.value.cells) ? draft.value.cells : []).filter((item) => item.status === 'unavailable')
    .length,
)
const slotsPerDay = computed(() => Math.max(0, timeSlots.value.length - blockedSlotStarts.value.length))
const chosenSlotsCount = computed(
  () => slotsPerDay.value * localizedDayOptions.value.length - unavailableSlotsCount.value,
)
const weeklyChosenHours = computed(
  () => (chosenSlotsCount.value * Math.max(1, Number(slotMinutes.value) || 60)) / 60,
)
const minRequiredChosenSlots = computed(() => {
  if (!weeklyHoursRange.value) return 0
  return Math.ceil((weeklyHoursRange.value.min * 60) / Math.max(1, Number(slotMinutes.value) || 60))
})

function sanitizeCellsAgainstConstraints() {
  const enabledDays = new Set(localizedDayOptions.value.map((item) => item.value))
  const blockedStarts = new Set(blockedSlotStarts.value)
  const validSlots = new Set(timeSlots.value.map((slot) => `${slot.start}|${slot.end}`))
  draft.value.cells = (Array.isArray(draft.value.cells) ? draft.value.cells : []).filter((item) => {
    if (!item?.day || !enabledDays.has(item.day)) return false
    if (!item?.start || !item?.end) return false
    if (!validSlots.has(`${item.start}|${item.end}`)) return false
    if (blockedStarts.has(item.start)) return false
    return true
  })
}

async function handleSave() {
  sanitizeCellsAgainstConstraints()
  if (chosenSlotsCount.value < minRequiredChosenSlots.value) {
    toast.error(
      t('pages.instructorSelfAvailability.errors.minWeeklyHoursRequired', {
        min: weeklyHoursRange?.value?.min ?? 0,
      }),
    )
    return
  }
  const saved = await saveAvailability()
  if (!saved) {
    toast.error(t('pages.instructorSelfAvailability.errors.invalidForm'))
    return
  }
  toast.success(t('pages.instructorSelfAvailability.toasts.saved'))
}
</script>

<style scoped>
.self-availability-page {
  background: #f7f8ff;
  width: 100%;
  max-width: 100%;
}

.self-availability-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.self-availability-grid {
  display: grid;
  gap: 1rem;
}

@media (max-width: 768px) {
  .self-availability-toolbar {
    flex-direction: column;
    align-items: stretch;
    gap: 0.6rem;
  }

  .self-availability-toolbar :deep(button) {
    width: 100%;
  }
}
</style>
