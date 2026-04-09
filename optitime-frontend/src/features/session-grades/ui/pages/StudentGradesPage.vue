<template>
  <AppShell :page-title="t('routes.studentGrades')">
    <section class="dashboard-card w-100">
      <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.studentGrades.title') }}</h1>
        <p class="dashboard-card__meta mb-0">{{ t('pages.studentGrades.subtitle') }}</p>
      </div>

      <AppSectionPanel :title="t('pages.studentGrades.sections.grades')">
        <AppDataTable
          :columns="columns"
          :rows="rows"
          row-key="id"
          :empty-text="t('pages.studentGrades.empty.noGrades')"
        >
          <template #cell-course="{ row }">
            <div class="fw-semibold">{{ row.course_code }}</div>
            <div class="text-secondary small">{{ row.course_name }}</div>
          </template>
          <template #cell-time="{ row }">{{ row.day }} | {{ row.start }} - {{ row.end }}</template>
        </AppDataTable>
      </AppSectionPanel>
    </section>
  </AppShell>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useSessionGradesStore } from '@/features/session-grades/model/stores/sessionGrades.store'

const { t } = useI18n()
const sessionGradesStore = useSessionGradesStore()
sessionGradesStore.ensureInitialized()

const rows = computed(() => sessionGradesStore.studentGradesForCurrentUser)

const columns = computed(() => [
  { key: 'course', label: t('pages.studentGrades.columns.course') },
  { key: 'time', label: t('pages.studentGrades.columns.time') },
  { key: 'oral', label: t('pages.studentGrades.columns.oral') },
  { key: 'lab', label: t('pages.studentGrades.columns.lab') },
  { key: 'midterm', label: t('pages.studentGrades.columns.midterm') },
  { key: 'final', label: t('pages.studentGrades.columns.final') },
  { key: 'total', label: t('pages.studentGrades.columns.total') },
  { key: 'letter_grade', label: t('pages.studentGrades.columns.grade') },
])
</script>
