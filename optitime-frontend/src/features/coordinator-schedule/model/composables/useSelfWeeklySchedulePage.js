import { computed, onMounted, ref } from 'vue'
import { instructorWeeklyScheduleService } from '@/features/coordinator-schedule/api/instructorWeeklySchedule.service'
import { studentWeeklyScheduleService } from '@/features/coordinator-schedule/api/studentWeeklySchedule.service'
import {
  deriveWeeklyBoardGrid,
  mapWeeklyApiItemToBoardSession,
} from '@/features/coordinator-schedule/api/weeklyScheduleMappers'

function ensureArray(value) {
  return Array.isArray(value) ? value : []
}

/**
 * @param {'instructor'|'student'} actor
 */
export function useSelfWeeklySchedulePage(actor) {
  const loading = ref(false)
  const sessions = ref([])
  /** @type {import('vue').Ref<{ id: string, name: string, code?: string, is_active?: boolean } | null>} */
  const activeSemester = ref(null)
  /** @type {import('vue').Ref<Record<string, unknown>|null>} */
  const boardFromApi = ref(null)

  const boardGrid = computed(() => deriveWeeklyBoardGrid(boardFromApi.value))

  const enabledStudyDayValues = computed(() => boardGrid.value.enabledStudyDayValues)

  const timeSlots = computed(() => boardGrid.value.timeSlots)

  const blockedSlotStarts = computed(() => boardGrid.value.blockedSlotStarts)

  const dayOptions = computed(() =>
    enabledStudyDayValues.value.map((day) => ({ value: day, label: day })),
  )

  async function loadWeekly() {
    loading.value = true
    try {
      const data =
        actor === 'instructor'
          ? await instructorWeeklyScheduleService.getWeeklySchedule()
          : await studentWeeklyScheduleService.getWeeklySchedule()
      const sem = data?.semester && typeof data.semester === 'object' ? data.semester : null
      activeSemester.value =
        sem && sem.id
          ? {
              id: `${sem.id}`,
              name: `${sem.name ?? ''}`,
              code: sem.code != null ? `${sem.code}` : '',
              is_active: Boolean(sem.is_active),
            }
          : null
      const items = ensureArray(data?.items)
      sessions.value = items.map((item) => mapWeeklyApiItemToBoardSession(item))
      boardFromApi.value =
        data?.board && typeof data.board === 'object' ? { ...data.board } : null
    } catch {
      sessions.value = []
      boardFromApi.value = null
      activeSemester.value = null
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadWeekly()
  })

  function getLectureStudents(session) {
    if (actor === 'student') return []
    const list = ensureArray(session?.students)
    if (list.length === 0) return []
    return list.map((s) => ({
      id: `${s?.id || ''}`.trim(),
      name: `${s?.name || ''}`.trim(),
      university_number: `${s?.university_number || ''}`.trim(),
      year_level: s?.year_level,
      study_status: `${s?.study_status || ''}`.trim(),
    }))
  }

  return {
    loading,
    activeSemester,
    dayOptions,
    timeSlots,
    blockedSlotStarts,
    sessions,
    getLectureStudents,
    reload: loadWeekly,
  }
}
