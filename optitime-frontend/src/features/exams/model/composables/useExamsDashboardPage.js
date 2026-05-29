import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/features/auth/api/auth.service'
import { examsService, flattenExamSessionsPayload } from '@/features/exams/api/exams.service'
import { useAuthStore } from '@/store/auth.store'

const DEMO_SEMESTER = Object.freeze({
  id: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
  name: 'Demo semester',
  code: 'demo',
})

export function useExamsDashboardPage() {
  const router = useRouter()
  const authStore = useAuthStore()
  const semesters = ref([])

  const selectedSemesterId = ref('')
  const semestersLoading = ref(false)
  const semestersError = ref('')
  const sessionsLoading = ref(false)
  const sessionsError = ref('')
  const rawSections = ref([])

  const semestersList = computed(() => {
    if (authService.isDemoMode()) return [DEMO_SEMESTER]
    return semesters.value ?? []
  })

  const semesterOptions = computed(() =>
    semestersList.value.map((s) => ({
      value: s.id,
      label: `${s.name ?? ''}${s.name && s.code ? ' — ' : ''}${s.code ?? ''}`.trim() || String(s.id),
    })),
  )

  const flatSessions = computed(() => flattenExamSessionsPayload(rawSections.value))

  const summary = computed(() => {
    const sessions = flatSessions.value
    const sectionIds = new Set(sessions.map((s) => s.section_id).filter(Boolean))
    const done = sessions.filter((s) => s.is_done).length
    const pending = sessions.length - done
    return {
      sections: sectionIds.size,
      sessions: sessions.length,
      done,
      pending,
    }
  })

  const canEnterGrades = computed(() => authStore.hasPermission('exam_grades.update'))

  async function initSemesters() {
    semestersLoading.value = true
    semestersError.value = ''
    try {
      if (authService.isDemoMode()) {
        selectedSemesterId.value = DEMO_SEMESTER.id
      } else {
        semesters.value = await examsService.getSemesters()
        if (semesters.value?.length && !selectedSemesterId.value) {
          const active = semesters.value.find((s) => s.is_active)
          selectedSemesterId.value = active?.id ?? semesters.value[0].id
        }
      }
    } catch (e) {
      semestersError.value = e?.message ?? 'Failed to load semesters'
    } finally {
      semestersLoading.value = false
    }
  }

  async function loadSessions() {
    if (!selectedSemesterId.value) return
    sessionsLoading.value = true
    sessionsError.value = ''
    try {
      rawSections.value = await examsService.getExamSessions(selectedSemesterId.value)
    } catch (e) {
      sessionsError.value = e?.message ?? 'Failed to load exam sessions'
      rawSections.value = []
    } finally {
      sessionsLoading.value = false
    }
  }

  function goToSessionGrades() {
    router.push({ name: 'exams-session-grades' })
  }

  onMounted(async () => {
    await initSemesters()
    await loadSessions()
  })

  watch(selectedSemesterId, () => {
    loadSessions()
  })

  return {
    selectedSemesterId,
    semesterOptions,
    semestersLoading,
    semestersError,
    sessionsLoading,
    sessionsError,
    flatSessions,
    summary,
    canEnterGrades,
    goToSessionGrades,
    loadSessions,
  }
}
