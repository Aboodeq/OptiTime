<template>
  <AppShell :page-title="t('routes.examsDashboard')">
    <section class="dashboard-card w-100">
      <div class="mb-4">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.examsDashboard.title') }}</h1>
        <p class="dashboard-card__meta mb-0">{{ t('pages.examsDashboard.subtitle') }}</p>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label small text-secondary mb-1">{{ t('pages.examsDashboard.semesterLabel') }}</label>
          <select
            v-model="selectedSemesterId"
            class="form-select"
            :disabled="semestersLoading || !semesterOptions.length"
          >
            <option value="" disabled>{{ t('pages.examsDashboard.semesterPlaceholder') }}</option>
            <option v-for="opt in semesterOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <p v-if="semestersError" class="text-danger small mt-2 mb-0">{{ semestersError }}</p>
        </div>
      </div>

      <div v-if="sessionsLoading" class="text-secondary small py-3">
        {{ t('pages.examsDashboard.loadingSessions') }}
      </div>
      <p v-else-if="sessionsError" class="text-danger small">{{ sessionsError }}</p>

      <div v-else class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="kpi-tile">
            <div class="kpi-tile__value">{{ summary.sections }}</div>
            <div class="kpi-tile__label">{{ t('pages.examsDashboard.kpi.sections') }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="kpi-tile">
            <div class="kpi-tile__value">{{ summary.sessions }}</div>
            <div class="kpi-tile__label">{{ t('pages.examsDashboard.kpi.sessions') }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="kpi-tile kpi-tile--success">
            <div class="kpi-tile__value">{{ summary.done }}</div>
            <div class="kpi-tile__label">{{ t('pages.examsDashboard.kpi.done') }}</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="kpi-tile kpi-tile--warning">
            <div class="kpi-tile__value">{{ summary.pending }}</div>
            <div class="kpi-tile__label">{{ t('pages.examsDashboard.kpi.pending') }}</div>
          </div>
        </div>
      </div>

      <AppSectionPanel :title="t('pages.examsDashboard.quickActions')">
        <div class="d-flex flex-wrap gap-2">
          <AppButton v-if="canEnterGrades" variant="primary" @click="goToSessionGrades">
            <i class="bi bi-journal-text me-1"></i>
            {{ t('pages.examsDashboard.actions.enterGrades') }}
          </AppButton>
          <p v-else class="text-secondary small mb-0 align-self-center">
            {{ t('pages.examsDashboard.actions.noGradePermission') }}
          </p>
        </div>
      </AppSectionPanel>

      <AppSectionPanel v-if="flatSessions.length" :title="t('pages.examsDashboard.sectionsList')" class="mt-4">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>{{ t('pages.examsDashboard.table.course') }}</th>
                <th>{{ t('pages.examsDashboard.table.section') }}</th>
                <th>{{ t('pages.examsDashboard.table.session') }}</th>
                <th>{{ t('pages.examsDashboard.table.status') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in flatSessions" :key="row.schedule_session_id">
                <td>
                  <span class="fw-semibold">{{ row.course_code }}</span>
                  <span class="text-secondary d-block small">{{ row.course_name }}</span>
                </td>
                <td><code class="small">{{ shortId(row.section_id) }}</code></td>
                <td><code class="small">{{ shortId(row.schedule_session_id) }}</code></td>
                <td>
                  <span v-if="row.is_done" class="badge text-bg-success">{{
                    t('pages.examsSessionGrades.status.done')
                  }}</span>
                  <span v-else class="badge text-bg-warning text-dark">{{
                    t('pages.examsSessionGrades.status.notDone')
                  }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </AppSectionPanel>
    </section>
  </AppShell>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useExamsDashboardPage } from '@/features/exams/model/composables/useExamsDashboardPage'

const { t } = useI18n()

const {
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
} = useExamsDashboardPage()

function shortId(value) {
  const s = `${value || ''}`.trim()
  if (!s) return '—'
  return s.length > 10 ? `${s.slice(0, 8)}…` : s
}
</script>

<style scoped>
.kpi-tile {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  background: #f8fafc;
}

.kpi-tile--success {
  border-color: #6ee7b7;
  background: #ecfdf5;
}

.kpi-tile--warning {
  border-color: #fcd34d;
  background: #fffbeb;
}

.kpi-tile__value {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.kpi-tile__label {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.25rem;
}
</style>
