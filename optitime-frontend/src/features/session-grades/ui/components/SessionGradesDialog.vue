<template>
  <AppDialog
    :open="open"
    :title="t('pages.examsSessionGrades.sections.grades')"
    @close="$emit('close')"
  >
    <div v-if="session">
      <div class="selected-session mb-3">
        <div>
          <div class="fw-semibold">{{ session.course_code }} - {{ session.course_name }}</div>
          <div class="small text-secondary">
            {{ localizedDayLabel(session.day) }} | {{ session.start }} - {{ session.end }}
          </div>
        </div>
        <span class="selected-session__badge">
          {{ gradeRows.length }} {{ t('pages.examsSessionGrades.columns.students') }}
        </span>
      </div>

      <div class="selected-session__actions mb-3">
        <div class="done-status-text">
          <i :class="isSessionDone ? 'bi bi-check2-circle text-success' : 'bi bi-hourglass-split text-warning'"></i>
          <span>
            {{
              isSessionDone
                ? t('pages.examsSessionGrades.status.done')
                : t('pages.examsSessionGrades.status.notDone')
            }}
          </span>
        </div>

        <div class="selected-session__buttons">
          <AppButton
            v-if="showExportPdf"
            variant="outline"
            size="sm"
            :disabled="exportingPdf"
            @click="$emit('export-pdf')"
          >
            <i class="bi bi-file-earmark-pdf me-1"></i>
            {{ t('pages.examsSessionGrades.actions.downloadPdf') }}
          </AppButton>

          <AppButton
            v-if="!isSessionDone"
            variant="outline"
            size="sm"
            :disabled="togglingDone"
            @click="$emit('toggle-done')"
          >
            <i class="bi bi-check2-circle me-1"></i>
            {{ t('pages.examsSessionGrades.actions.markDone') }}
          </AppButton>
          <AppButton
            v-else-if="canReopenSession"
            variant="outline"
            size="sm"
            :disabled="togglingDone"
            @click="$emit('toggle-done')"
          >
            <i class="bi bi-arrow-counterclockwise me-1"></i>
            {{ t('pages.examsSessionGrades.actions.reopen') }}
          </AppButton>
        </div>
      </div>

      <div class="dialog-grade-table">
        <AppDataTable
          :columns="gradeColumns"
          :rows="gradeRows"
          :empty-text="t('pages.examsSessionGrades.empty.noStudents')"
          row-key="student_id"
        >
          <template #cell-student="{ row }">
            <div class="fw-semibold">{{ row.name }}</div>
            <div class="small text-secondary">{{ row.university_number }}</div>
          </template>

          <template #cell-oral="{ row }">
            <input
              :id="`oral-${row.student_id}`"
              class="score-input"
              type="number"
              min="0"
              max="25"
              step="0.5"
              :value="row.draft.oral"
              :disabled="isSessionDone"
              @input="$emit('update-score', { studentId: row.student_id, key: 'oral', value: $event.target.value })"
            />
          </template>

          <template #cell-lab="{ row }">
            <input
              :id="`lab-${row.student_id}`"
              class="score-input"
              type="number"
              min="0"
              max="25"
              step="0.5"
              :value="row.draft.lab"
              :disabled="isSessionDone"
              @input="$emit('update-score', { studentId: row.student_id, key: 'lab', value: $event.target.value })"
            />
          </template>

          <template #cell-midterm="{ row }">
            <input
              :id="`midterm-${row.student_id}`"
              class="score-input"
              type="number"
              min="0"
              max="25"
              step="0.5"
              :value="row.draft.midterm"
              :disabled="isSessionDone"
              @input="$emit('update-score', { studentId: row.student_id, key: 'midterm', value: $event.target.value })"
            />
          </template>

          <template #cell-final="{ row }">
            <input
              :id="`final-${row.student_id}`"
              class="score-input"
              type="number"
              min="0"
              max="25"
              step="0.5"
              :value="row.draft.final"
              :disabled="isSessionDone"
              @input="$emit('update-score', { studentId: row.student_id, key: 'final', value: $event.target.value })"
            />
          </template>

          <template #cell-total="{ row }">
            <span class="cell-total">{{ row.draft.total.toFixed(1) }}</span>
          </template>

          <template #cell-grade="{ row }">
            <span class="grade-pill">{{ row.draft.letter_grade }}</span>
          </template>

          <template #cell-actions="{ row }">
            <AppIconButton
              icon="bi bi-check2-circle"
              variant="primary"
              size="sm"
              :disabled="savingId === row.student_id || isSessionDone"
              :title="t('pages.examsSessionGrades.actions.save')"
              @click="$emit('save-row', row)"
            />
          </template>
        </AppDataTable>
      </div>
    </div>
  </AppDialog>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'

defineProps({
  open: { type: Boolean, default: false },
  session: { type: Object, default: null },
  gradeRows: { type: Array, default: () => [] },
  isSessionDone: { type: Boolean, default: false },
  togglingDone: { type: Boolean, default: false },
  savingId: { type: String, default: '' },
  canReopenSession: { type: Boolean, default: true },
  showExportPdf: { type: Boolean, default: false },
  exportingPdf: { type: Boolean, default: false },
})

defineEmits(['close', 'toggle-done', 'update-score', 'save-row', 'export-pdf'])

const { t } = useI18n()

const gradeColumns = computed(() => [
  { key: 'student', label: t('pages.examsSessionGrades.columns.student'), cellClass: 'cell-student' },
  { key: 'oral', label: t('pages.examsSessionGrades.columns.oral'), cellClass: 'cell-score' },
  { key: 'lab', label: t('pages.examsSessionGrades.columns.lab'), cellClass: 'cell-score' },
  { key: 'midterm', label: t('pages.examsSessionGrades.columns.midterm'), cellClass: 'cell-score' },
  { key: 'final', label: t('pages.examsSessionGrades.columns.final'), cellClass: 'cell-score' },
  { key: 'total', label: t('pages.examsSessionGrades.columns.total'), cellClass: 'cell-total-col' },
  { key: 'grade', label: t('pages.examsSessionGrades.columns.grade'), cellClass: 'cell-grade' },
  { key: 'actions', label: t('pages.examsSessionGrades.columns.actions'), cellClass: 'cell-actions' },
])

function localizedDayLabel(day) {
  if (!day) return '-'
  return t(`pages.constraintsManagement.days.${day}`)
}
</script>

<style scoped>
.selected-session {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.75rem;
}

.selected-session__badge {
  background: #eef2ff;
  color: #4338ca;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.3rem 0.6rem;
}

.selected-session__actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.selected-session__buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.done-status-text {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.84rem;
  font-weight: 600;
  color: #334155;
}

.score-input {
  width: 74px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  text-align: center;
  padding: 0.24rem 0.4rem;
  font-size: 0.84rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.2;
}

.score-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
}

.grade-pill {
  background: #ecfeff;
  color: #0e7490;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
}

.cell-total {
  font-size: 0.86rem;
  font-weight: 700;
  color: #0f172a;
}

.dialog-grade-table :deep(.app-data-table__table thead th) {
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
  padding-top: 0.55rem;
  padding-bottom: 0.55rem;
}

.dialog-grade-table :deep(.app-data-table__table tbody td) {
  font-size: 0.84rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  vertical-align: middle;
}

.dialog-grade-table :deep(.cell-student) {
  min-width: 140px;
}

.dialog-grade-table :deep(.cell-score),
.dialog-grade-table :deep(.cell-total-col),
.dialog-grade-table :deep(.cell-grade),
.dialog-grade-table :deep(.cell-actions) {
  text-align: center;
}
</style>
