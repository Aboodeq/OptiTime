<template>
  <AppDialog class="course-form-dialog" :open="open" :title="title" @close="$emit('cancel')">
    <div class="course-form-layout">
      <CourseBasicsPanel
        :draft="draft"
        :can-edit="canEdit"
        :faculty-options="facultyOptions"
        :department-options="departmentOptions"
        @update:code="$emit('update:code', $event)"
        @update:required_hours="$emit('update:required_hours', $event)"
        @update:name_ar="$emit('update:name_ar', $event)"
        @update:name_en="$emit('update:name_en', $event)"
        @update:faculty_id="$emit('update:faculty_id', $event)"
        @update:department_id="$emit('update:department_id', $event)"
        @update:room_consumed_hours="$emit('update:room_consumed_hours', $event)"
        @update:lab_consumed_hours="$emit('update:lab_consumed_hours', $event)"
        @update:min_student_year_level="$emit('update:min_student_year_level', $event)"
        @update:max_student_year_level="$emit('update:max_student_year_level', $event)"
      />

      <CoursePrerequisitesPanel
        :draft="draft"
        :can-edit="canEdit"
        :prerequisite-course-options="prerequisiteCourseOptions"
        @update:prerequisite_course_ids="$emit('update:prerequisite_course_ids', $event)"
      />

      <AppSectionPanel :title="t('pages.coursesManagement.form.sections')" icon="bi bi-grid-3x3-gap" tone="soft">
        <div class="d-flex justify-content-end mb-2">
          <AppButton :disabled="!canEdit" variant="outline" @click="openCreateSectionDialog">
            + {{ t('pages.coursesManagement.form.addSection') }}
          </AppButton>
        </div>

        <div class="sections-list">
          <CourseSectionCard
            v-for="(section, index) in sectionRows"
            :key="section.id"
            :section="section"
            :index="index"
            :sections-count="sectionRows.length"
            :can-edit="canEdit"
            :is-department-selected="Boolean(draft.department_id)"
            :instructor-options="instructorOptions"
            @update-section="emitSectionPatch"
            @remove="$emit('remove-section', $event)"
          />
          <div v-if="sectionRows.length === 0" class="text-secondary small">
            {{ t('pages.coursesManagement.form.sectionsEmpty') }}
          </div>
        </div>
      </AppSectionPanel>

      <div class="d-flex justify-content-end gap-2">
        <AppButton variant="outline" @click="$emit('cancel')">
          {{ t('common.actions.close') }}
        </AppButton>
        <AppButton :disabled="!canEdit" @click="$emit('save')">
          {{ t('pages.coursesManagement.actions.save') }}
        </AppButton>
      </div>
    </div>
  </AppDialog>

  <CourseSectionCreateDialog
    :open="isCreateSectionDialogOpen"
    :draft="newSection"
    :instructor-options="instructorOptions"
    :is-department-selected="Boolean(draft.department_id)"
    @cancel="closeCreateSectionDialog"
    @confirm="confirmAddSection"
    @update:section_name="newSection.section_name = $event"
    @update:section_type="newSection.section_type = $event"
    @update:capacity="newSection.capacity = $event"
    @update:instructor_ids="newSection.instructor_ids = $event"
  />
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import CourseBasicsPanel from '@/features/courses/ui/components/CourseBasicsPanel.vue'
import CoursePrerequisitesPanel from '@/features/courses/ui/components/CoursePrerequisitesPanel.vue'
import CourseSectionCard from '@/features/courses/ui/components/CourseSectionCard.vue'
import CourseSectionCreateDialog from '@/features/courses/ui/components/CourseSectionCreateDialog.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  draft: { type: Object, required: true },
  canEdit: { type: Boolean, default: true },
  facultyOptions: { type: Array, default: () => [] },
  departmentOptions: { type: Array, default: () => [] },
  instructorOptions: { type: Array, default: () => [] },
  prerequisiteCourseOptions: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'cancel',
  'save',
  'update:code',
  'update:name_ar',
  'update:name_en',
  'update:faculty_id',
  'update:department_id',
  'update:required_hours',
  'update:room_consumed_hours',
  'update:lab_consumed_hours',
  'update:min_student_year_level',
  'update:max_student_year_level',
  'update:prerequisite_course_ids',
  'add-section',
  'remove-section',
  'update-section',
])

const { t } = useI18n()
const isCreateSectionDialogOpen = ref(false)
const newSection = reactive({
  section_name: '',
  section_type: 'room',
  capacity: '',
  instructor_ids: [],
})

const title = computed(() =>
  props.isEditing
    ? t('pages.coursesManagement.form.editTitle')
    : t('pages.coursesManagement.form.createTitle'),
)

const sectionRows = computed(() => props.draft.sections || [])

function openCreateSectionDialog() {
  if (!props.canEdit) return
  isCreateSectionDialogOpen.value = true
}

function closeCreateSectionDialog() {
  isCreateSectionDialogOpen.value = false
  newSection.section_name = ''
  newSection.section_type = 'room'
  newSection.capacity = ''
  newSection.instructor_ids = []
}

function confirmAddSection() {
  emit('add-section', {
    section_name: newSection.section_name.trim(),
    section_type: newSection.section_type,
    capacity: Number(newSection.capacity),
    instructor_ids: [...newSection.instructor_ids],
  })
  closeCreateSectionDialog()
}

function emitSectionPatch(payload) {
  emit('update-section', payload)
}
</script>

<style scoped>
:deep(.course-form-dialog .app-dialog__panel) {
  width: min(100%, 980px);
  max-height: 92vh;
}

:deep(.course-form-dialog .app-dialog__body) {
  max-height: calc(92vh - 66px);
  overflow: auto;
}

.course-form-layout {
  display: grid;
  gap: 1rem;
  padding: 0.15rem 0.1rem 0.35rem;
}

.sections-list {
  display: grid;
  gap: 0.9rem;
  margin-top: 0.5rem;
}

:deep(.course-form-dialog .section-panel) {
  margin-bottom: 0.2rem;
}

:deep(.course-form-dialog .section-panel__body) {
  padding: 0.9rem;
}

:deep(.course-form-dialog .app-input-field),
:deep(.course-form-dialog .app-autocomplete-field),
:deep(.course-form-dialog .app-multi-select-field) {
  margin-bottom: 0.15rem;
}

:deep(.course-form-dialog .app-input-field__label),
:deep(.course-form-dialog .app-autocomplete-field__label),
:deep(.course-form-dialog .app-multi-select-field__label) {
  margin-bottom: 0.45rem;
}

@media (max-width: 991px) {
  :deep(.course-form-dialog .section-panel__body) {
    padding: 0.75rem;
  }
}
</style>
