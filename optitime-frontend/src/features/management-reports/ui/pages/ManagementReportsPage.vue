<template>
  <AppShell :page-title="t('routes.managementReports')">
    <section class="dashboard-card w-100">
      <div
        ref="reportPdfRef"
        class="management-reports-pdf-root bg-white rounded-3 border px-3 py-3 px-md-4 py-md-4"
      >
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
          <div class="min-w-0">
            <h1 class="h4 fw-bold mb-1">{{ t('pages.managementReports.title') }}</h1>
            <p class="dashboard-card__meta mb-1">{{ t('pages.managementReports.subtitle') }}</p>
            <p class="small text-secondary mb-0">{{ pdfMetaLine }}</p>
          </div>
          <AppButton
            type="button"
            variant="outline"
            class="management-reports-pdf-exclude flex-shrink-0"
            icon="bi bi-file-earmark-pdf"
            :disabled="pdfExporting || !selectedSemesterId || semestersLoading"
            :loading="pdfExporting"
            :loading-label="t('pages.managementReports.pdf.exporting')"
            :tone-color="authStore.roleColor"
            @click="handleExportPdf"
          >
            {{ t('pages.managementReports.pdf.download') }}
          </AppButton>
        </div>

      <div v-if="semestersLoading" class="text-secondary small mb-3">
        {{ t('pages.managementReports.loadingSemesters') }}
      </div>
      <div v-else-if="semestersLoadError" class="alert alert-warning" role="alert">
        {{ semestersLoadError }}
      </div>
      <div v-else class="mb-4 management-reports__semester-field">
        <AppAutocompleteField
          input-id="management-reports-semester"
          :label="t('pages.managementReports.semesterLabel')"
          :model-value="selectedSemesterId"
          :options="semesterOptions"
          :placeholder="t('pages.managementReports.semesterPlaceholder')"
          :empty-text="t('pages.managementReports.noSemesters')"
          icon="bi bi-calendar3"
          :disabled="!semesterOptions.length"
          @update:model-value="selectedSemesterId = $event"
        />
      </div>

      <div v-if="reportsLoading" class="text-secondary small mb-3">
        {{ t('pages.managementReports.loadingReports') }}
      </div>

      <div class="row g-3">
        <AppCan permission="reports.classroom_occupancy">
          <div class="col-lg-6">
            <AppSectionPanel
              :title="t('pages.managementReports.panels.classroom.title')"
              :subtitle="t('pages.managementReports.panels.classroom.subtitle')"
              icon="bi bi-building"
              tone="soft"
            >
              <p v-if="panelErrors.classroom" class="text-danger small mb-0">
                {{ panelErrors.classroom }}
              </p>
              <template v-else-if="classroom">
                <ManagementReportsChart
                  v-if="showClassroomChart"
                  variant="doughnut"
                  class="mb-3"
                  :labels="classroomChartLabels"
                  :values="classroomChartValues"
                  :rtl="isRtl"
                  :aria-label="t('pages.managementReports.charts.classroomAria')"
                  :height-px="220"
                />
                <dl class="row mb-0 small">
                  <dt class="col-sm-5 text-secondary">{{ t('pages.managementReports.panels.classroom.used') }}</dt>
                  <dd class="col-sm-7">{{ classroom.used_rooms }}</dd>
                  <dt class="col-sm-5 text-secondary">{{ t('pages.managementReports.panels.classroom.total') }}</dt>
                  <dd class="col-sm-7">{{ classroom.total_rooms }}</dd>
                  <dt class="col-sm-5 text-secondary">{{ t('pages.managementReports.panels.classroom.rate') }}</dt>
                  <dd class="col-sm-7 fw-semibold">{{ formatOccupancyRate(classroom.occupancy_rate) }}</dd>
                </dl>
              </template>
            </AppSectionPanel>
          </div>
        </AppCan>

        <AppCan permission="reports.lab_utilization">
          <div class="col-lg-6">
            <AppSectionPanel
              :title="t('pages.managementReports.panels.lab.title')"
              :subtitle="t('pages.managementReports.panels.lab.subtitle')"
              icon="bi bi-flask-fill"
              tone="soft"
            >
              <p v-if="panelErrors.lab" class="text-danger small mb-0">{{ panelErrors.lab }}</p>
              <template v-else-if="lab">
                <i
                  class="bi bi-flask-fill management-reports__lab-chart-mark-icon d-block mb-2"
                  aria-hidden="true"
                />
                <ManagementReportsChart
                  variant="bar"
                  class="mb-3"
                  :labels="labChartLabels"
                  :values="labChartValues"
                  :dataset-label="t('pages.managementReports.charts.labDataset')"
                  :rtl="isRtl"
                  :aria-label="t('pages.managementReports.charts.labAria')"
                  :height-px="240"
                />
                <dl class="row mb-0 small">
                  <dt class="col-sm-5 text-secondary">{{ t('pages.managementReports.panels.lab.sessions') }}</dt>
                  <dd class="col-sm-7">{{ lab.lab_sessions }}</dd>
                  <dt class="col-sm-5 text-secondary">{{ t('pages.managementReports.panels.lab.rooms') }}</dt>
                  <dd class="col-sm-7">{{ lab.lab_rooms }}</dd>
                  <template v-if="labDerived?.sessionsPerRoom != null">
                    <dt class="col-sm-5 text-secondary">
                      {{ t('pages.managementReports.panels.lab.sessionsPerRoom') }}
                    </dt>
                    <dd class="col-sm-7 fw-semibold">{{ formatDecimal(labDerived.sessionsPerRoom) }}</dd>
                  </template>
                </dl>
              </template>
            </AppSectionPanel>
          </div>
        </AppCan>

        <AppCan permission="reports.study_hours">
          <div class="col-lg-6">
            <AppSectionPanel
              :title="t('pages.managementReports.panels.studyHours.title')"
              :subtitle="t('pages.managementReports.panels.studyHours.subtitle')"
              icon="bi bi-bar-chart"
              tone="neutral"
            >
              <p v-if="panelErrors.studyHours" class="text-danger small mb-0">
                {{ panelErrors.studyHours }}
              </p>
              <p v-else-if="!studyHours.length" class="text-secondary small mb-0">
                {{ t('pages.managementReports.empty.noPublishedPlan') }}
              </p>
              <div v-else>
                <p class="small text-secondary mb-2">{{ t('pages.managementReports.panels.studyHours.scaleHint') }}</p>
                <ManagementReportsChart
                  variant="bar"
                  class="mb-3"
                  :labels="studyHoursChartLabels"
                  :values="studyHoursChartValues"
                  :dataset-label="t('pages.managementReports.panels.studyHours.sessions')"
                  :rtl="isRtl"
                  :aria-label="t('pages.managementReports.charts.studyHoursAria')"
                  :height-px="280"
                />
                <div class="table-responsive">
                  <table class="table table-sm mb-0">
                    <thead>
                      <tr>
                        <th>{{ t('pages.managementReports.panels.studyHours.day') }}</th>
                        <th>{{ t('pages.managementReports.panels.studyHours.sessions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, idx) in studyHoursSorted" :key="`sh-${idx}`">
                        <td>{{ formatStudyDay(row) }}</td>
                        <td>{{ row.sessions }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </AppSectionPanel>
          </div>
        </AppCan>

        <AppCan permission="reports.peak_periods">
          <div class="col-lg-6">
            <AppSectionPanel
              :title="t('pages.managementReports.panels.peak.title')"
              :subtitle="t('pages.managementReports.panels.peak.subtitle')"
              icon="bi bi-clock-history"
              tone="neutral"
            >
              <p v-if="panelErrors.peakPeriods" class="text-danger small mb-0">
                {{ panelErrors.peakPeriods }}
              </p>
              <p v-else-if="!peakPeriods.length" class="text-secondary small mb-0">
                {{ t('pages.managementReports.empty.noPublishedPlan') }}
              </p>
              <div v-else>
                <p class="small text-secondary mb-2">{{ t('pages.managementReports.panels.peak.scaleHint') }}</p>
                <ManagementReportsChart
                  variant="horizontalBar"
                  class="mb-3"
                  :labels="peakChartLabels"
                  :values="peakChartValues"
                  :dataset-label="t('pages.managementReports.panels.peak.concurrent')"
                  bar-color="rgba(13, 202, 240, 0.8)"
                  :rtl="isRtl"
                  :aria-label="t('pages.managementReports.charts.peakAria')"
                  :height-px="Math.min(420, 140 + peakPeriods.length * 28)"
                />
                <div class="table-responsive">
                  <table class="table table-sm mb-0">
                    <thead>
                      <tr>
                        <th>{{ t('pages.managementReports.panels.peak.startTime') }}</th>
                        <th>{{ t('pages.managementReports.panels.peak.concurrent') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(row, idx) in peakPeriods" :key="`pk-${idx}`">
                        <td><code>{{ row.start_time }}</code></td>
                        <td>{{ row.concurrent_sections }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </AppSectionPanel>
          </div>
        </AppCan>

        <AppCan permission="reports.resource_utilization">
          <div class="col-12">
            <AppSectionPanel
              :title="t('pages.managementReports.panels.resources.title')"
              :subtitle="t('pages.managementReports.panels.resources.subtitle')"
              icon="bi bi-box-seam"
              tone="neutral"
            >
              <p v-if="panelErrors.resources" class="text-danger small mb-0">
                {{ panelErrors.resources }}
              </p>
              <p v-else-if="!resources.length" class="text-secondary small mb-0">
                {{ t('pages.managementReports.empty.noResources') }}
              </p>
              <p v-else class="small text-secondary mb-2">
                {{ t('pages.managementReports.panels.resources.catalogNote') }}
              </p>
              <template v-if="resources.length">
                <p v-if="showResourcesChart" class="small text-secondary mb-2">
                  {{ t('pages.managementReports.charts.resourcesByQuantity') }}
                </p>
                <ManagementReportsChart
                  v-if="showResourcesChart"
                  variant="horizontalBar"
                  class="mb-3"
                  :labels="resourcesChartLabels"
                  :values="resourcesChartValues"
                  :dataset-label="t('pages.managementReports.panels.resources.quantity')"
                  :rtl="isRtl"
                  :aria-label="t('pages.managementReports.charts.resourcesAria')"
                  :height-px="Math.min(480, 120 + resourcesChartSlice.length * 22)"
                />
              </template>
              <div v-if="resources.length" class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th>{{ t('pages.managementReports.panels.resources.name') }}</th>
                      <th>{{ t('pages.managementReports.panels.resources.type') }}</th>
                      <th>{{ t('pages.managementReports.panels.resources.quantity') }}</th>
                      <th>{{ t('pages.managementReports.panels.resources.status') }}</th>
                      <th>{{ t('pages.managementReports.panels.resources.location') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="row in resources" :key="row.id ?? row.name_en">
                      <td>{{ resourceName(row) }}</td>
                      <td>{{ row.type ?? '—' }}</td>
                      <td>{{ row.quantity ?? '—' }}</td>
                      <td>{{ row.status ?? '—' }}</td>
                      <td>{{ resourceLocation(row) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </AppSectionPanel>
          </div>
        </AppCan>

        <AppCan permission="reports.compliance">
          <div class="col-lg-6">
            <AppSectionPanel
              :title="t('pages.managementReports.panels.compliance.title')"
              :subtitle="t('pages.managementReports.panels.compliance.subtitle')"
              icon="bi bi-shield-check"
              :tone="complianceTone"
            >
              <p v-if="panelErrors.compliance" class="text-danger small mb-0">
                {{ panelErrors.compliance }}
              </p>
              <template v-else-if="compliance">
                <p class="mb-2">
                  <span
                    class="badge"
                    :class="compliance.ok ? 'text-bg-success' : 'text-bg-warning'"
                  >
                    {{
                      compliance.ok
                        ? t('pages.managementReports.panels.compliance.ok')
                        : t('pages.managementReports.panels.compliance.issues')
                    }}
                  </span>
                </p>
                <p class="small text-secondary mb-2">
                  {{
                    t('pages.managementReports.panels.compliance.violationCount', {
                      count: compliance.violations.length,
                    })
                  }}
                </p>
                <ul
                  v-if="compliance.violations.length"
                  class="small mb-0 ps-3 management-reports__violations"
                >
                  <li v-for="(violation, vIdx) in compliance.violations" :key="`v-${vIdx}`">
                    <span class="fw-semibold">{{ formatViolationType(violation) }}</span>
                    —
                    <span class="text-secondary">{{ formatViolationSessions(violation) }}</span>
                  </li>
                </ul>
              </template>
            </AppSectionPanel>
          </div>
        </AppCan>
      </div>
      </div>
    </section>
  </AppShell>
</template>

<script setup>
import { computed, nextTick, ref, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import AppAutocompleteField from '@/components/common/AppAutocompleteField.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppCan from '@/components/common/AppCan.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppShell from '@/components/layout/AppShell.vue'
import ManagementReportsChart from '@/features/management-reports/ui/components/ManagementReportsChart.vue'
import { useManagementReportsPage } from '@/features/management-reports/model/composables/useManagementReportsPage'
import { useAuthStore } from '@/store/auth.store'

const { t, locale } = useI18n()
const toast = useToast()
const authStore = useAuthStore()

const reportPdfRef = ref(null)
const pdfExporting = ref(false)

const {
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
} = useManagementReportsPage()

const selectedSemesterLabel = computed(() => {
  const id = selectedSemesterId.value
  const opt = semesterOptions.value.find((o) => o.value === id)
  return opt?.label ?? ''
})

const pdfMetaLine = computed(() => {
  const sem = selectedSemesterLabel.value || selectedSemesterId.value || '—'
  const loc = unref(locale) === 'en' ? 'en-GB' : 'ar'
  const generatedAt = new Date().toLocaleString(loc)
  return t('pages.managementReports.pdf.metaLine', { semester: sem, generatedAt })
})

async function handleExportPdf() {
  if (!selectedSemesterId.value) {
    toast.error(t('pages.managementReports.pdf.needSemester'))
    return
  }
  const el = reportPdfRef.value
  if (!el) return

  pdfExporting.value = true
  await nextTick()
  try {
    let waitMs = 0
    while (reportsLoading.value && waitMs < 8000) {
      await new Promise((resolve) => setTimeout(resolve, 150))
      waitMs += 150
    }
    const { downloadDomAsPdf } = await import(
      '@/features/management-reports/lib/exportManagementReportPdf.js'
    )
    await downloadDomAsPdf(el, selectedSemesterLabel.value || selectedSemesterId.value)
    toast.success(t('pages.managementReports.pdf.success'))
  } catch (e) {
    console.error(e)
    toast.error(t('pages.managementReports.pdf.failed'))
  } finally {
    pdfExporting.value = false
  }
}

const complianceTone = computed(() => {
  if (!compliance.value) return 'neutral'
  return compliance.value.ok ? 'soft' : 'hard'
})

const isRtl = computed(() => String(unref(locale) || '').toLowerCase().startsWith('ar'))

const classroomChartLabels = computed(() => [
  t('pages.managementReports.charts.usedRooms'),
  t('pages.managementReports.charts.unusedRooms'),
])

const classroomChartValues = computed(() => {
  const c = classroom.value
  if (!c) return []
  const used = Number(c.used_rooms) || 0
  const total = Number(c.total_rooms) || 0
  return [used, Math.max(0, total - used)]
})

const showClassroomChart = computed(() => (Number(classroom.value?.total_rooms) || 0) > 0)

const labChartLabels = computed(() => [
  t('pages.managementReports.panels.lab.sessions'),
  t('pages.managementReports.panels.lab.rooms'),
])

const labChartValues = computed(() => {
  const l = lab.value
  if (!l) return []
  return [Number(l.lab_sessions) || 0, Number(l.lab_rooms) || 0]
})

const labDerived = computed(() => {
  const l = lab.value
  if (!l) return null
  const rooms = Number(l.lab_rooms) || 0
  const sessions = Number(l.lab_sessions) || 0
  if (rooms <= 0) return { sessionsPerRoom: null }
  return { sessionsPerRoom: sessions / rooms }
})

const studyHoursSorted = computed(() => {
  const rows = [...(studyHours.value ?? [])]
  rows.sort((a, b) => (Number(a?.day_of_week) || 0) - (Number(b?.day_of_week) || 0))
  return rows
})

const studyHoursChartLabels = computed(() => studyHoursSorted.value.map((r) => formatStudyDay(r)))

const studyHoursChartValues = computed(() =>
  studyHoursSorted.value.map((r) => Number(r?.sessions) || 0),
)

const peakChartLabels = computed(() =>
  (peakPeriods.value ?? []).map((r) => String(r?.start_time ?? '').slice(0, 8)),
)

const peakChartValues = computed(() =>
  (peakPeriods.value ?? []).map((r) => Number(r?.concurrent_sections) || 0),
)

const resourcesChartSlice = computed(() => {
  const rows = [...(resources.value ?? [])]
  rows.sort((a, b) => (Number(b.quantity) || 0) - (Number(a.quantity) || 0))
  return rows.slice(0, 20)
})

const resourcesChartLabels = computed(() =>
  resourcesChartSlice.value.map((r) => truncateLabel(resourceName(r), 40)),
)

const resourcesChartValues = computed(() =>
  resourcesChartSlice.value.map((r) => Number(r.quantity) || 0),
)

const showResourcesChart = computed(() =>
  resourcesChartSlice.value.some((r) => Number(r.quantity) > 0),
)

function truncateLabel(text, maxLen) {
  const s = String(text ?? '')
  if (s.length <= maxLen) return s
  return `${s.slice(0, Math.max(0, maxLen - 1))}…`
}

function formatOccupancyRate(rate) {
  const n = typeof rate === 'number' ? rate : Number(rate) || 0
  return `${(n * 100).toFixed(1)}%`
}

function formatDecimal(n) {
  const x = typeof n === 'number' ? n : Number(n)
  if (!Number.isFinite(x)) return '—'
  return x.toFixed(2)
}

function formatViolationType(violation) {
  const key = violation?.type
  if (!key) return '—'
  const path = `pages.managementReports.panels.compliance.types.${key}`
  const translated = t(path)
  return translated !== path ? translated : key
}

function formatViolationSessions(violation) {
  const items = Array.isArray(violation?.items) ? violation.items : []
  if (!items.length) return '—'
  return t('pages.managementReports.panels.compliance.sessionIds', { ids: items.join(', ') })
}

function formatStudyDay(row) {
  const d = row?.day_of_week
  if (d === undefined || d === null) return '—'
  return t('pages.managementReports.panels.studyHours.dayValue', { day: d })
}

function resourceName(row) {
  if (!row || typeof row !== 'object') return '—'
  const loc = String(unref(locale) || '').toLowerCase().startsWith('ar') ? 'ar' : 'en'
  const key = loc === 'ar' ? 'name_ar' : 'name_en'
  return row[key] || row.name_en || row.name_ar || row.name || '—'
}

function resourceLocation(row) {
  if (!row || typeof row !== 'object') return '—'
  const loc = String(unref(locale) || '').toLowerCase().startsWith('ar') ? 'ar' : 'en'
  const key = loc === 'ar' ? 'location_ar' : 'location_en'
  return row[key] || row.location_en || row.location_ar || '—'
}
</script>

<style scoped>
.management-reports__semester-field {
  max-width: 28rem;
}

.management-reports__lab-chart-mark-icon {
  font-size: 1.35rem;
  color: var(--bs-info, #0dcaf0);
  line-height: 1;
}

.management-reports__violations li + li {
  margin-top: 0.25rem;
}
</style>
