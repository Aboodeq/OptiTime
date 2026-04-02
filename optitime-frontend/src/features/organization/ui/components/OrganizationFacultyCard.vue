<template>
  <article class="faculty-card">
    <header class="faculty-card__head" :style="{ background: headerGradient }">
      <div class="faculty-card__icon">
        <img
          v-if="faculty.icon_url"
          :src="faculty.icon_url"
          :alt="faculty.name_en"
          class="faculty-card__icon-image"
        />
        <i v-else :class="headerIcon"></i>
      </div>
      <div class="faculty-card__title-wrap">
        <h3 class="faculty-card__title mb-0">{{ faculty.name_en }}</h3>
        <small class="faculty-card__subtitle">{{ faculty.name_ar }}</small>
        <div class="faculty-card__code">{{ faculty.code }}</div>
      </div>
      <div class="faculty-card__head-actions">
        <span
          class="badge rounded-pill"
          :class="faculty.is_active ? 'text-bg-success' : 'text-bg-secondary'"
        >
          {{
            faculty.is_active
              ? t('pages.organization.status.active')
              : t('pages.organization.status.inactive')
          }}
        </span>
      </div>
    </header>

    <div class="faculty-card__stats">
      <div class="faculty-stat">
        <i class="bi bi-diagram-3"></i>
        <span
          >{{ faculty.departments.length }}
          {{ t('pages.organization.faculties.departments') }}</span
        >
      </div>
      <div class="faculty-stat">
        <i class="bi bi-mortarboard"></i>
        <span
          >{{ faculty.graduation_hours ?? 0 }}
          {{ t('pages.organization.faculties.graduationHours') }}</span
        >
      </div>
      <div class="faculty-stat">
        <i class="bi bi-layers"></i>
        <span>{{ faculty.studying_level ?? 1 }} {{ t('pages.organization.faculties.studyingLevel') }}</span>
      </div>
      <div class="faculty-stat">
        <i class="bi bi-check2-circle"></i>
        <span>{{ activeDepartments }} {{ t('pages.organization.status.active') }}</span>
      </div>
    </div>

    <div class="faculty-card__actions">
      <AppCan permission="organization.create">
        <AppIconButton
          class="fac-btn"
          icon="bi bi-plus-circle"
          variant="primary"
          size="md"
          :tone-color="roleColor"
          :title="t('pages.organization.actions.newDepartment')"
          :aria-label="t('pages.organization.actions.newDepartment')"
          @click="$emit('add-department')"
        />
      </AppCan>
      <AppCan permission="organization.update">
        <AppIconButton
          class="fac-btn"
          icon="bi bi-pencil-square"
          variant="primary"
          size="md"
          :tone-color="roleColor"
          :title="t('pages.organization.actions.edit')"
          :aria-label="t('pages.organization.actions.edit')"
          @click="$emit('edit-faculty')"
        />
      </AppCan>
      <AppCan permission="organization.delete">
        <AppIconButton
          class="fac-btn fac-btn--danger"
          icon="bi bi-trash3"
          variant="danger"
          size="md"
          :title="t('pages.organization.actions.delete')"
          :aria-label="t('pages.organization.actions.delete')"
          @click="$emit('delete-faculty')"
        />
      </AppCan>
    </div>

    <section class="faculty-card__departments">
      <div class="faculty-card__departments-head">
        <strong>{{ t('pages.organization.cards.departmentsPreview') }}</strong>
      </div>

      <div v-if="previewDepartments.length === 0" class="text-secondary small">
        {{ t('pages.organization.cards.noDepartments') }}
      </div>

      <ul v-else class="faculty-card__departments-list">
        <li
          v-for="department in previewDepartments"
          :key="department.id"
          class="faculty-card__department-item"
        >
          <div class="dept-icon">
            <img
              v-if="department.icon_url"
              :src="department.icon_url"
              :alt="department.name_en"
              class="dept-icon__image"
            />
            <i v-else class="bi bi-diagram-2"></i>
          </div>
          <div class="faculty-card__department-text">
            <span class="fw-semibold">{{ department.name_en }}</span>
            <small class="text-secondary">{{ department.name_ar }}</small>
          </div>
          <div class="dept-actions">
            <AppCan permission="organization.update">
              <AppIconButton
                class="dept-btn"
                icon="bi bi-pencil-square"
                variant="primary"
                size="sm"
                :tone-color="roleColor"
                :title="t('pages.organization.actions.edit')"
                :aria-label="t('pages.organization.actions.edit')"
                @click="$emit('edit-department', department)"
              />
            </AppCan>
            <AppCan permission="organization.delete">
              <AppIconButton
                class="dept-btn dept-btn--danger"
                icon="bi bi-trash3"
                variant="danger"
                size="sm"
                :title="t('pages.organization.actions.delete')"
                :aria-label="t('pages.organization.actions.delete')"
                @click="$emit('delete-department', department)"
              />
            </AppCan>
          </div>
        </li>
      </ul>

      <div v-if="hiddenDepartmentsCount > 0" class="small text-secondary">
        {{ t('pages.organization.cards.moreDepartments', { count: hiddenDepartmentsCount }) }}
      </div>
    </section>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCan from '@/components/common/AppCan.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'

const props = defineProps({
  faculty: { type: Object, required: true },
  roleColor: { type: String, default: '#4f46e5' },
  previewDepartments: { type: Array, default: () => [] },
  hiddenDepartmentsCount: { type: Number, default: 0 },
})

defineEmits([
  'add-department',
  'edit-faculty',
  'delete-faculty',
  'edit-department',
  'delete-department',
])

const { t } = useI18n()

const iconPalette = [
  'bi bi-building',
  'bi bi-bank',
  'bi bi-mortarboard',
  'bi bi-diagram-3',
  'bi bi-columns-gap',
]

function hashCode(value) {
  return value.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
}

const activeDepartments = computed(
  () => props.faculty.departments.filter((department) => department.is_active).length,
)
const fallbackGradientPalette = [
  'linear-gradient(135deg, #4361ee, #3a0ca3)',
  'linear-gradient(135deg, #0ea5e9, #2563eb)',
  'linear-gradient(135deg, #7c3aed, #4f46e5)',
]
const headerGradient = computed(() => {
  const color = props.faculty.color
  if (typeof color === 'string' && color.startsWith('#')) {
    return `linear-gradient(135deg, ${color}, ${shadeHexColor(color, -22)})`
  }
  return fallbackGradientPalette[hashCode(props.faculty.code) % fallbackGradientPalette.length]
})
const headerIcon = computed(() => iconPalette[hashCode(props.faculty.code) % iconPalette.length])

function shadeHexColor(hex, percent) {
  const cleaned = hex.replace('#', '')
  const normalized =
    cleaned.length === 3
      ? cleaned
          .split('')
          .map((c) => c + c)
          .join('')
      : cleaned

  const num = Number.parseInt(normalized, 16)
  const amt = Math.round(2.55 * percent)
  const r = Math.min(255, Math.max(0, (num >> 16) + amt))
  const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00ff) + amt))
  const b = Math.min(255, Math.max(0, (num & 0x0000ff) + amt))
  return `#${(0x1000000 + (r << 16) + (g << 8) + b).toString(16).slice(1)}`
}
</script>

<style scoped>
.faculty-card {
  border: none;
  border-radius: 18px;
  background: #fff;
  overflow: hidden;
  box-shadow: 0 10px 32px rgba(67, 97, 238, 0.08);
  transition: all 0.2s ease;
}

.faculty-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 42px rgba(67, 97, 238, 0.12);
}

.faculty-card__head {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 1rem;
  color: #fff;
}

.faculty-card__icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.2);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.faculty-card__icon-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.faculty-card__title {
  font-size: 0.95rem;
  font-weight: 900;
}

.faculty-card__subtitle {
  color: rgba(255, 255, 255, 0.9);
}

.faculty-card__code {
  margin-top: 3px;
  font-size: 0.7rem;
  letter-spacing: 0.08em;
  color: rgba(255, 255, 255, 0.8);
}

.faculty-card__head-actions {
  display: grid;
  gap: 6px;
  margin-inline-start: auto;
  justify-items: end;
}

.faculty-card__stats {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0.7rem 0.95rem;
  border-bottom: 1px solid #f1f5f9;
  background: #fafbff;
}

.faculty-stat {
  flex: 1;
  display: inline-flex;
  gap: 6px;
  align-items: center;
  font-size: 0.78rem;
  color: #64748b;
  font-weight: 700;
}

.faculty-card__actions {
  padding: 0.7rem 0.95rem;
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
  border-bottom: 1px solid #f8fafc;
}

.fac-btn {
  width: 32px;
  height: 32px;
  border-radius: 9px;
}

.fac-btn--danger {
  color: #be123c;
}

.fac-btn--danger:hover {
  background: #fff1f2;
  border-color: #fda4af;
}

.faculty-card__departments {
  padding: 0.75rem 0.95rem 0.9rem;
  display: grid;
  gap: 0.5rem;
}

.faculty-card__departments-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.faculty-card__departments-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.4rem;
}

.faculty-card__department-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  border: 1px solid #edf2f7;
  border-radius: 10px;
  padding: 0.45rem 0.5rem;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
  transition: box-shadow 160ms ease;
}

.faculty-card__department-item:hover {
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
}

.dept-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: #eef2ff;
  color: #4f46e5;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.dept-icon__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
}

.faculty-card__department-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.dept-actions {
  display: flex;
  gap: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.faculty-card__department-item:hover .dept-actions {
  opacity: 1;
}

.dept-btn {
  width: 26px;
  height: 26px;
  border-radius: 7px;
}

.dept-btn--danger {
  color: #be123c;
}
</style>
