<template>
  <AppShell :page-title="t('routes.examsSessionGrades')">
    <section class="dashboard-card w-100">
      <div class="mb-4">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.examsSessionGrades.title') }}</h1>
        <p class="dashboard-card__meta mb-0">{{ t('pages.examsSessionGrades.subtitle') }}</p>
        <p class="dashboard-card__meta mb-0">
          {{ t('pages.examsSessionGrades.activeSemester', { name: activeSemester?.name ?? '-' }) }}
        </p>
      </div>

      <AppSectionPanel :title="t('pages.examsSessionGrades.sections.sessions')">
        <div class="session-search-wrap mb-3">
          <i class="bi bi-search"></i>
          <input
            v-model="sessionSearch"
            type="text"
            class="session-search-input"
            :placeholder="t('pages.examsSessionGrades.searchPlaceholder')"
          />
        </div>

        <div v-if="filteredSessions.length === 0" class="text-secondary small py-2">
          {{ t('pages.examsSessionGrades.empty.noSessions') }}
        </div>

        <div class="session-grid">
          <button
            v-for="session in filteredSessions"
            :key="session.schedule_session_id"
            type="button"
            class="session-card"
            :class="{ 'is-active': selectedSession?.schedule_session_id === session.schedule_session_id }"
            :style="sessionAccentStyles[session.schedule_session_id]"
            @click="openGradesDialog(session.schedule_session_id)"
          >
            <div class="session-card__top">
              <strong>{{ session.course_code }}</strong>
              <span class="session-card__count">
                <i class="bi bi-people"></i>
                {{ session.student_count }}
              </span>
            </div>
            <div v-if="session.is_done" class="session-card__done">
              <i class="bi bi-check2-circle"></i>
              {{ t('pages.examsSessionGrades.status.done') }}
            </div>
            <div class="session-card__name">{{ session.course_name }}</div>
            <div class="session-card__meta">
              <span>{{ localizedDayLabel(session.day) }}</span>
              <span>{{ session.start }} - {{ session.end }}</span>
            </div>
            <div class="session-card__instructor">{{ session.instructor_name }}</div>
          </button>
        </div>
      </AppSectionPanel>
    </section>
  </AppShell>

  <SessionGradesDialog
    :open="gradesDialogOpen"
    :session="selectedSession"
    :grade-rows="gradeRows"
    :is-session-done="isSessionDone"
    :toggling-done="togglingDone"
    :saving-id="savingId"
    @close="gradesDialogOpen = false"
    @toggle-done="toggleDone"
    @update-score="({ studentId, key, value }) => updateDraft(studentId, key, value)"
    @save-row="saveRow"
  />
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { computeLetterGrade, computeTotal, normalizeScore } from '@/features/session-grades/model/constants/gradeScale'
import { useSessionGradesPage } from '@/features/session-grades/model/composables/useSessionGradesPage'
import SessionGradesDialog from '@/features/session-grades/ui/components/SessionGradesDialog.vue'

const { t } = useI18n()
const toast = useToast()
const {
  activeSemester,
  sessions,
  selectedSession,
  selectedStudents,
  selectSession,
  buildDraft,
  saveGradeDraft,
  markSessionDone,
} = useSessionGradesPage()

const sessionSearch = ref('')
const draftsByStudent = ref({})
const savingId = ref('')
const gradesDialogOpen = ref(false)
const togglingDone = ref(false)
const isSessionDone = computed(() => Boolean(selectedSession.value?.is_done))

const filteredSessions = computed(() => {
  const query = sessionSearch.value.trim().toLowerCase()
  if (!query) return sessions.value
  return sessions.value.filter((item) =>
    `${item.course_code} ${item.course_name} ${item.instructor_name}`.toLowerCase().includes(query),
  )
})

const COURSE_PALETTE = [
  { bg: '#eff6ff', border: '#93c5fd', text: '#1d4ed8' },
  { bg: '#f5f3ff', border: '#c4b5fd', text: '#6d28d9' },
  { bg: '#f0fdf4', border: '#86efac', text: '#15803d' },
  { bg: '#fff7ed', border: '#fdba74', text: '#c2410c' },
  { bg: '#fdf2f8', border: '#f9a8d4', text: '#be185d' },
  { bg: '#ecfeff', border: '#67e8f9', text: '#0e7490' },
  { bg: '#fefce8', border: '#fde047', text: '#a16207' },
  { bg: '#faf5ff', border: '#d8b4fe', text: '#7e22ce' },
  { bg: '#eef2ff', border: '#a5b4fc', text: '#4338ca' },
  { bg: '#f0f9ff', border: '#7dd3fc', text: '#0369a1' },
  { bg: '#f0fdfa', border: '#5eead4', text: '#0f766e' },
  { bg: '#f7fee7', border: '#bef264', text: '#4d7c0f' },
  { bg: '#fffbeb', border: '#fcd34d', text: '#b45309' },
  { bg: '#fef2f2', border: '#fca5a5', text: '#b91c1c' },
  { bg: '#fff1f2', border: '#fda4af', text: '#be123c' },
  { bg: '#f8fafc', border: '#cbd5e1', text: '#334155' },
  { bg: '#fafaf9', border: '#d6d3d1', text: '#57534e' },
  { bg: '#f9fafb', border: '#d1d5db', text: '#374151' },
  { bg: '#f5f3ff', border: '#c4b5fd', text: '#5b21b6' },
  { bg: '#ecfccb', border: '#a3e635', text: '#3f6212' },
  { bg: '#dcfce7', border: '#4ade80', text: '#166534' },
  { bg: '#e0f2fe', border: '#38bdf8', text: '#075985' },
  { bg: '#ede9fe', border: '#a78bfa', text: '#6d28d9' },
  { bg: '#fee2e2', border: '#f87171', text: '#991b1b' },
  { bg: '#fde68a', border: '#f59e0b', text: '#92400e' },
  { bg: '#fce7f3', border: '#f472b6', text: '#9d174d' },
  { bg: '#dbeafe', border: '#60a5fa', text: '#1e40af' },
  { bg: '#cffafe', border: '#22d3ee', text: '#155e75' },
  { bg: '#ede9fe', border: '#818cf8', text: '#3730a3' },
]

const sessionAccentStyles = computed(() => {
  const mapped = {}
  let previousIndex = -1

  for (const session of filteredSessions.value) {
    const seedValue = `${session.course_id || session.course_code || session.schedule_session_id || ''}`
    const hash = [...seedValue].reduce((sum, char) => sum + char.charCodeAt(0), 0)
    let paletteIndex = hash % COURSE_PALETTE.length

    // Keep stable-ish hashing, but avoid equal colors for adjacent cards.
    if (paletteIndex === previousIndex) {
      paletteIndex = (paletteIndex + 1) % COURSE_PALETTE.length
    }

    const tone = COURSE_PALETTE[paletteIndex]
    mapped[session.schedule_session_id] = {
      '--course-bg': tone.bg,
      '--course-border': tone.border,
      '--course-text': tone.text,
    }
    previousIndex = paletteIndex
  }

  return mapped
})

const gradeRows = computed(() =>
  selectedStudents.value.map((student) => {
    const existing = draftsByStudent.value[student.id]
    const draft = existing ?? buildDraft(selectedSession.value?.schedule_session_id, student.id)
    return {
      ...student,
      student_id: student.id,
      draft,
    }
  }),
)

function updateDraft(studentId, key, value) {
  if (!selectedSession.value) return
  const base =
    draftsByStudent.value[studentId] ??
    buildDraft(selectedSession.value.schedule_session_id, studentId)

  const next = {
    ...base,
    [key]: normalizeScore(value),
  }
  const total = computeTotal(next)
  next.total = total
  next.letter_grade = computeLetterGrade(total)
  draftsByStudent.value = {
    ...draftsByStudent.value,
    [studentId]: next,
  }
}

async function saveRow(row) {
  savingId.value = row.student_id
  const saved = await saveGradeDraft(row.draft)
  savingId.value = ''
  if (!saved) {
    toast.error(t('pages.examsSessionGrades.toasts.saveFailed'))
    return
  }
  toast.success(t('pages.examsSessionGrades.toasts.saved'))
}

function openGradesDialog(sessionId) {
  selectSession(sessionId)
  gradesDialogOpen.value = true
}

function localizedDayLabel(day) {
  if (!day) return '-'
  return t(`pages.constraintsManagement.days.${day}`)
}

async function toggleDone() {
  if (!selectedSession.value?.schedule_session_id) return
  togglingDone.value = true
  const nextDone = !isSessionDone.value
  const updated = await markSessionDone(selectedSession.value.schedule_session_id, nextDone)
  togglingDone.value = false
  if (!updated) {
    toast.error(t('pages.examsSessionGrades.toasts.saveFailed'))
    return
  }
  toast.success(
    nextDone
      ? t('pages.examsSessionGrades.toasts.markedDone')
      : t('pages.examsSessionGrades.toasts.reopened'),
  )
}
</script>

<style scoped>
.session-search-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.4rem 0.65rem;
}

.session-search-wrap i {
  color: #64748b;
}

.session-search-input {
  border: none;
  outline: none;
  width: 100%;
  font-size: 0.9rem;
}

.session-card {
  width: 100%;
  min-height: 132px;
  text-align: start;
  border: 1px solid var(--course-border, #e5e7eb);
  background: var(--course-bg, #fff);
  color: var(--course-text, #0f172a);
  border-radius: 12px;
  padding: 0.75rem;
  margin-bottom: 0.55rem;
  transition: all 0.15s ease;
}

.session-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 0.6rem;
}

.session-card:hover {
  border-color: #a5b4fc;
}

.session-card.is-active {
  border-color: #4f46e5;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.12);
}

.session-card__top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.session-card__count {
  font-size: 0.75rem;
  color: #475569;
  display: inline-flex;
  gap: 0.25rem;
  align-items: center;
}

.session-card__name {
  font-size: 0.85rem;
  color: color-mix(in oklab, var(--course-text, #475569) 65%, #334155);
}

.session-card__done {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: #047857;
  background: #ecfdf5;
  border: 1px solid #6ee7b7;
  border-radius: 999px;
  padding: 0.12rem 0.45rem;
  margin-bottom: 0.35rem;
}

.session-card__meta,
.session-card__instructor {
  display: flex;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: color-mix(in oklab, var(--course-text, #64748b) 55%, #64748b);
  margin-top: 0.25rem;
}
</style>
