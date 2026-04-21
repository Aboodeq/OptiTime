import { computed, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { authService } from '@/features/auth/api/auth.service'
import { managementReportsService } from '@/features/management-reports/api/managementReports.service'
import { useSemestersStore } from '@/features/semesters/model/stores/semesters.store'
import { useAuthStore } from '@/store/auth.store'

const DEMO_SEMESTER = Object.freeze({
  id: 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
  name: 'Demo semester',
  code: 'demo',
  academic_year: '2025',
  start_date: '2025-01-01',
  end_date: '2025-06-30',
  is_active: true,
})

function emptyPanelErrors() {
  return {
    classroom: '',
    lab: '',
    studyHours: '',
    peakPeriods: '',
    resources: '',
    compliance: '',
  }
}

export function useManagementReportsPage() {
  const authStore = useAuthStore()
  const semestersStore = useSemestersStore()
  const { semesters: storeSemesters } = storeToRefs(semestersStore)

  const selectedSemesterId = ref('')
  const semestersLoading = ref(false)
  const semestersLoadError = ref('')
  const reportsLoading = ref(false)

  const classroom = ref(null)
  const lab = ref(null)
  const studyHours = ref([])
  const peakPeriods = ref([])
  const resources = ref([])
  const compliance = ref(null)

  const panelErrors = ref(emptyPanelErrors())

  const semestersList = computed(() => {
    if (authService.isDemoMode()) {
      return [DEMO_SEMESTER]
    }
    return storeSemesters.value ?? []
  })

  const semesterOptions = computed(() =>
    semestersList.value.map((s) => ({
      value: s.id,
      label: `${s.name ?? ''}${s.name && s.code ? ' — ' : ''}${s.code ?? ''}`.trim() || String(s.id),
    })),
  )

  async function initSemesters() {
    semestersLoading.value = true
    semestersLoadError.value = ''
    try {
      if (authService.isDemoMode()) {
        selectedSemesterId.value = DEMO_SEMESTER.id
      } else {
        await semestersStore.ensureInitialized()
        if (storeSemesters.value?.length && !selectedSemesterId.value) {
          selectedSemesterId.value = storeSemesters.value[0].id
        }
      }
    } catch (e) {
      semestersLoadError.value = e?.message ?? 'Failed to load semesters'
    } finally {
      semestersLoading.value = false
    }
  }

  async function runPanel(key, fn) {
    try {
      await fn()
    } catch (e) {
      panelErrors.value = {
        ...panelErrors.value,
        [key]: e?.message ?? 'Request failed',
      }
    }
  }

  async function refreshReports() {
    panelErrors.value = emptyPanelErrors()

    if (!selectedSemesterId.value) {
      classroom.value = null
      lab.value = null
      studyHours.value = []
      peakPeriods.value = []
      compliance.value = null
      resources.value = []
      return
    }

    const sid = selectedSemesterId.value
    reportsLoading.value = true

    const tasks = []

    if (authStore.hasPermission('reports.classroom_occupancy')) {
      tasks.push(
        runPanel('classroom', async () => {
          classroom.value = await managementReportsService.getClassroomOccupancy(sid)
        }),
      )
    } else {
      classroom.value = null
    }

    if (authStore.hasPermission('reports.lab_utilization')) {
      tasks.push(
        runPanel('lab', async () => {
          lab.value = await managementReportsService.getLabUtilization(sid)
        }),
      )
    } else {
      lab.value = null
    }

    if (authStore.hasPermission('reports.study_hours')) {
      tasks.push(
        runPanel('studyHours', async () => {
          studyHours.value = await managementReportsService.getStudyHoursDistribution(sid)
        }),
      )
    } else {
      studyHours.value = []
    }

    if (authStore.hasPermission('reports.peak_periods')) {
      tasks.push(
        runPanel('peakPeriods', async () => {
          peakPeriods.value = await managementReportsService.getPeakPeriods(sid)
        }),
      )
    } else {
      peakPeriods.value = []
    }

    if (authStore.hasPermission('reports.compliance')) {
      tasks.push(
        runPanel('compliance', async () => {
          compliance.value = await managementReportsService.getComplianceRestrictions(sid)
        }),
      )
    } else {
      compliance.value = null
    }

    if (authStore.hasPermission('reports.resource_utilization')) {
      tasks.push(
        runPanel('resources', async () => {
          resources.value = await managementReportsService.getResourceUtilization()
        }),
      )
    } else {
      resources.value = []
    }

    await Promise.all(tasks.map((p) => p))
    reportsLoading.value = false
  }

  watch(selectedSemesterId, () => {
    refreshReports()
  })

  onMounted(async () => {
    await initSemesters()
  })

  return {
    selectedSemesterId,
    semestersLoading,
    semestersLoadError,
    reportsLoading,
    semesterOptions,
    classroom,
    lab,
    studyHours,
    peakPeriods,
    resources,
    compliance,
    panelErrors,
    authStore,
    refreshReports,
  }
}
