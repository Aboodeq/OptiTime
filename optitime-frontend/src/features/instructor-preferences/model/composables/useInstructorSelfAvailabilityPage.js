import { storeToRefs } from 'pinia'
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/store/auth.store'
import { useConstraintsStore } from '@/features/constraints/model/stores/constraints.store'
import { useInstructorPreferencesStore } from '@/features/instructor-preferences/model/stores/instructorPreferences.store'

export function useInstructorSelfAvailabilityPage() {
  const authStore = useAuthStore()
  const constraintsStore = useConstraintsStore()
  const preferencesStore = useInstructorPreferencesStore()

  const { enabledStudyDayValues } = storeToRefs(preferencesStore)
  const draft = ref(preferencesStore.createEmptyDraft())

  const instructorId = computed(() => authStore.user?.id ?? '')

  const dayOptions = computed(() => {
    const order = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat']
    return order
      .filter((day) => enabledStudyDayValues.value.includes(day))
      .map((day) => ({ value: day, label: day }))
  })

  const slotMinutes = computed(() => Number(constraintsStore.activeSettings?.slot_minutes) || 60)
  const gapMinutes = computed(() => Math.max(0, Number(constraintsStore.activeSettings?.gap_minutes) || 0))
  const dayStart = computed(() => constraintsStore.activeSettings?.day_start || '08:00')
  const dayEnd = computed(() => constraintsStore.activeSettings?.day_end || '16:00')
  const breakTimes = computed(() =>
    (constraintsStore.activeSettings?.break_times ?? [])
      .filter((item) => item?.enabled)
      .map((item) => ({ start: item.start, end: item.end })),
  )
  const weeklyHoursRange = computed(() =>
    preferencesStore.getInstructorWeeklyHoursRange(instructorId.value),
  )

  function initializeDraft() {
    const existing = preferencesStore.getByInstructorId(instructorId.value)
    draft.value = existing
      ? preferencesStore.buildDraftFromPreference(existing)
      : {
          ...preferencesStore.createEmptyDraft(),
          instructor_id: instructorId.value,
        }
  }

  async function saveAvailability() {
    return preferencesStore.upsertPreferenceForInstructorId(instructorId.value, draft.value)
  }

  onMounted(async () => {
    await Promise.all([constraintsStore.ensureInitialized(), preferencesStore.ensureInitialized()])
    initializeDraft()
  })

  return {
    dayOptions,
    slotMinutes,
    gapMinutes,
    dayStart,
    dayEnd,
    breakTimes,
    weeklyHoursRange,
    draft,
    saveAvailability,
  }
}
