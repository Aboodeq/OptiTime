import { storeToRefs } from 'pinia'
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/store/auth.store'
import { useInstructorPreferencesStore } from '@/features/instructor-preferences/model/stores/instructorPreferences.store'

export function useInstructorSelfAvailabilityPage() {
  const authStore = useAuthStore()
  const preferencesStore = useInstructorPreferencesStore()

  const { enabledStudyDayValues, availabilityGrid } = storeToRefs(preferencesStore)
  const draft = ref(preferencesStore.createEmptyDraft())
  const pageLoading = ref(true)
  const saving = ref(false)

  const instructorId = computed(() => authStore.user?.id ?? '')

  const dayOptions = computed(() => {
    const order = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat']
    return order
      .filter((day) => enabledStudyDayValues.value.includes(day))
      .map((day) => ({ value: day, label: day }))
  })

  const slotMinutes = computed(() => Number(availabilityGrid.value?.slot_minutes) || 60)
  const gapMinutes = computed(() => Math.max(0, Number(availabilityGrid.value?.gap_minutes) || 0))
  const dayStart = computed(() => availabilityGrid.value?.day_start || '08:00')
  const dayEnd = computed(() => availabilityGrid.value?.day_end || '16:00')
  const breakTimes = computed(() =>
    (availabilityGrid.value?.break_times ?? [])
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
    saving.value = true
    try {
      return await preferencesStore.upsertPreferenceForInstructorId(instructorId.value, draft.value)
    } finally {
      saving.value = false
    }
  }

  onMounted(async () => {
    pageLoading.value = true
    try {
      await preferencesStore.ensureInitialized()
      initializeDraft()
    } finally {
      pageLoading.value = false
    }
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
    pageLoading,
    saving,
    saveAvailability,
  }
}
