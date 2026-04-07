<template>
  <article class="course-card">
    <header class="course-card__head" :style="{ background: headerGradient }">
      <div class="course-card__title-wrap">
        <h3 class="course-card__title mb-0">{{ course.name_en }}</h3>
        <small class="course-card__subtitle">{{ course.name_ar }}</small>
        <div class="course-card__code">{{ course.code }}</div>
        <div class="course-card__status-row">
          <span class="badge rounded-pill" :class="isActive ? 'course-card__status--active' : 'course-card__status--inactive'">
            {{ isActive ? t('pages.coursesManagement.status.active') : t('pages.coursesManagement.status.inactive') }}
          </span>
        </div>
      </div>
      <span class="badge rounded-pill course-card__type-badge">
        {{ course.has_lab_component ? t('pages.coursesManagement.classTypes.roomAndLab') : t('pages.coursesManagement.classTypes.roomOnly') }}
      </span>
    </header>

    <div class="course-card__meta">
      <div class="course-card__meta-row">
        <span class="course-card__meta-icon course-card__meta-icon--faculty">
          <i class="bi bi-building"></i>
        </span>
        <span><strong>{{ t('pages.coursesManagement.labels.faculty') }}:</strong> {{ course.faculty_name_en }}</span>
      </div>
      <div class="course-card__meta-row">
        <span class="course-card__meta-icon course-card__meta-icon--department">
          <i class="bi bi-diagram-2"></i>
        </span>
        <span><strong>{{ t('pages.coursesManagement.labels.department') }}:</strong> {{ course.department_name_en }}</span>
      </div>
      <div class="course-card__meta-row">
        <span class="course-card__meta-icon course-card__meta-icon--hours">
          <i class="bi bi-clock-history"></i>
        </span>
        <span><strong>{{ t('pages.coursesManagement.table.hours') }}:</strong> {{ course.required_hours }}</span>
      </div>
      <div class="course-card__meta-row">
        <span class="course-card__meta-icon course-card__meta-icon--room">
          <i class="bi bi-easel2"></i>
        </span>
        <span><strong>{{ t('pages.coursesManagement.labels.roomConsumedHours') }}:</strong> {{ course.room_consumed_hours ?? t('pages.coursesManagement.labels.notApplicable') }}</span>
      </div>
      <div class="course-card__meta-row">
        <span class="course-card__meta-icon course-card__meta-icon--lab">
          <i class="bi bi-pc-display"></i>
        </span>
        <span>
          <strong>{{ t('pages.coursesManagement.labels.labConsumedHours') }}:</strong>
          {{
            course.has_lab_component
              ? (course.lab_consumed_hours ?? t('pages.coursesManagement.labels.notApplicable'))
              : t('pages.coursesManagement.labels.notApplicable')
          }}
        </span>
      </div>
      <div class="course-card__meta-row">
        <span class="course-card__meta-icon course-card__meta-icon--year">
          <i class="bi bi-mortarboard"></i>
        </span>
        <span><strong>{{ t('pages.coursesManagement.labels.studentLevel') }}:</strong> {{ studentLevelText }}</span>
      </div>
      <div class="course-card__meta-row">
        <span class="course-card__meta-icon course-card__meta-icon--prereq">
          <i class="bi bi-link-45deg"></i>
        </span>
        <span><strong>{{ t('pages.coursesManagement.labels.prerequisites') }}:</strong> {{ prerequisitesText }}</span>
      </div>

      <div class="course-card__doctors-table">
        <div class="course-card__doctors-head">{{ t('pages.coursesManagement.labels.doctors') }}</div>
        <table class="course-card__doctors-grid">
          <tbody>
            <tr
              v-for="(row, index) in sectionInstructorRows"
              :key="`section-${course.id}-${index}`"
              class="course-card__doctors-row"
            >
              <td class="course-card__doctors-cell course-card__doctors-cell--instructor">
                {{ row.instructor_name || '-' }}
              </td>
              <td class="course-card__doctors-cell course-card__doctors-cell--sections">
                <span
                  v-for="(section, sectionIndex) in row.sections"
                  :key="`${row.instructor_name}-section-${sectionIndex}`"
                  class="course-card__chip"
                >
                  {{ section.section_type === 'lab' ? t('pages.coursesManagement.labels.lab') : t('pages.coursesManagement.labels.room') }}
                  -
                  {{ section.section_name || '-' }}
                  ({{ section.capacity ?? '-' }})
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="canEdit || canDelete || canToggleActivation" class="course-card__actions">
      <button
        v-if="canToggleActivation"
        class="btn btn-sm"
        :class="isActive ? 'btn-outline-danger' : 'btn-outline-success'"
        type="button"
        :disabled="isToggling"
        @click="$emit('toggle-activation', course)"
      >
        {{
          isToggling
            ? t('pages.coursesManagement.actions.savingStatus')
            : isActive
              ? t('pages.coursesManagement.actions.deactivate')
              : t('pages.coursesManagement.actions.activate')
        }}
      </button>
      <AppIconButton
        v-if="canEdit"
        icon="bi bi-pencil-square"
        variant="primary"
        size="md"
        :title="t('pages.coursesManagement.actions.edit')"
        :aria-label="t('pages.coursesManagement.actions.edit')"
        @click="$emit('edit', course)"
      />
      <AppIconButton
        v-if="canDelete"
        icon="bi bi-trash3"
        variant="danger"
        size="md"
        :title="t('pages.coursesManagement.actions.delete')"
        :aria-label="t('pages.coursesManagement.actions.delete')"
        @click="$emit('delete', course)"
      />
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppIconButton from '@/components/common/AppIconButton.vue'

const props = defineProps({
  course: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  canToggleActivation: { type: Boolean, default: false },
  isActive: { type: Boolean, default: false },
  isToggling: { type: Boolean, default: false },
})

defineEmits(['edit', 'delete', 'toggle-activation'])
const { t } = useI18n()

const gradientPalette = [
  ['#4361ee', '#3a0ca3'],
  ['#0ea5e9', '#2563eb'],
  ['#7c3aed', '#4f46e5'],
  ['#14b8a6', '#0f766e'],
  ['#f59e0b', '#d97706'],
  ['#ec4899', '#be185d'],
  ['#22c55e', '#15803d'],
  ['#ef4444', '#b91c1c'],
]

const headerGradient = computed(() => {
  const seed = String(
    props.course.id || props.course.code || `${props.course.name_en || ''}-${props.course.name_ar || ''}`,
  )
  const [start, end] = gradientPalette[hashCode(seed) % gradientPalette.length]
  return `linear-gradient(135deg, ${start}, ${end})`
})

const sectionInstructorRows = computed(() =>
  Array.isArray(props.course.section_instructor_rows) && props.course.section_instructor_rows.length > 0
    ? props.course.section_instructor_rows
    : [{ instructor_name: '-', sections: [] }],
)

const studentLevelText = computed(() => {
  const minLevel = props.course.min_student_year_level
  const maxLevel = props.course.max_student_year_level
  if (minLevel && maxLevel) return `${minLevel} - ${maxLevel}`
  if (minLevel) return `${minLevel}+`
  if (maxLevel) return `<= ${maxLevel}`
  return t('pages.coursesManagement.labels.notApplicable')
})

const prerequisitesText = computed(() => {
  const values = Array.isArray(props.course.prerequisite_course_names)
    ? props.course.prerequisite_course_names
    : []
  return values.length > 0 ? values.join(', ') : t('pages.coursesManagement.labels.notApplicable')
})

function hashCode(value) {
  return value.split('').reduce((acc, char) => (acc * 31 + char.charCodeAt(0)) >>> 0, 0)
}
</script>

<style scoped>
.course-card {
  border: none;
  border-radius: 18px;
  background: #fff;
  overflow: hidden;
  box-shadow: 0 10px 32px rgba(67, 97, 238, 0.08);
  transition: all 0.2s ease;
}

.course-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 42px rgba(67, 97, 238, 0.12);
}

.course-card__head {
  padding: 1rem;
  border-bottom: 1px solid #eef2ff;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  color: #fff;
}

.course-card__title {
  font-size: 0.95rem;
  font-weight: 900;
}

.course-card__subtitle {
  color: rgba(255, 255, 255, 0.9);
}

.course-card__code {
  margin-top: 3px;
  font-size: 0.7rem;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.82);
}

.course-card__status-row {
  margin-top: 0.4rem;
}

.course-card__status--active {
  background: rgba(34, 197, 94, 0.25);
  color: #f0fdf4;
  border: 1px solid rgba(240, 253, 244, 0.35);
}

.course-card__status--inactive {
  background: rgba(248, 113, 113, 0.25);
  color: #fff1f2;
  border: 1px solid rgba(255, 241, 242, 0.4);
}

.course-card__type-badge {
  background: rgba(255, 255, 255, 0.2) !important;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.35);
}

.course-card__meta {
  padding: 0.8rem 1rem;
  display: grid;
  gap: 0.35rem;
  color: #334155;
  font-size: 0.84rem;
}

.course-card__meta-row {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}

.course-card__meta-icon {
  width: 22px;
  height: 22px;
  border-radius: 7px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  flex-shrink: 0;
}

.course-card__meta-icon--faculty {
  color: #1d4ed8;
  background: #dbeafe;
}

.course-card__meta-icon--department {
  color: #0369a1;
  background: #e0f2fe;
}

.course-card__meta-icon--hours {
  color: #b45309;
  background: #fef3c7;
}

.course-card__meta-icon--year {
  color: #475569;
  background: #e2e8f0;
}

.course-card__meta-icon--prereq {
  color: #7c2d12;
  background: #ffedd5;
}

.course-card__meta-icon--room {
  color: #0f766e;
  background: #ccfbf1;
}

.course-card__meta-icon--lab {
  color: #7c3aed;
  background: #ede9fe;
}

.course-card__doctors-table {
  margin-top: 0.3rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

.course-card__doctors-head {
  background: #f8fafc;
  color: #334155;
  font-weight: 700;
  font-size: 0.77rem;
  padding: 0.45rem 0.6rem;
  border-bottom: 1px solid #e2e8f0;
}

.course-card__doctors-grid {
  width: 100%;
  border-collapse: collapse;
}

.course-card__doctors-row + .course-card__doctors-row {
  border-top: 1px solid #e2e8f0;
}

.course-card__doctors-cell {
  padding: 0.45rem 0.6rem;
  font-size: 0.78rem;
  color: #334155;
}

.course-card__doctors-cell--instructor {
  background: #f8fafc;
  font-weight: 700;
  border-inline-end: 1px solid #e2e8f0;
  white-space: nowrap;
}

.course-card__doctors-cell--sections {
  background: #f8fafc;
  font-weight: 600;
  border-inline-end: 1px solid #e2e8f0;
}

.course-card__doctors-cell--sections {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.course-card__chip {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 2px 8px;
  font-size: 0.72rem;
  background: #e2e8f0;
  color: #334155;
  white-space: nowrap;
}

.course-card__actions {
  padding: 0.7rem 1rem 1rem;
  display: flex;
  gap: 0.45rem;
}
</style>
