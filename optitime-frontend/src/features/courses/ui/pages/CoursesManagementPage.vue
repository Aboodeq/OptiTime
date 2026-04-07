<template>
  <AppShell :page-title="t('routes.coursesManagement')">
    <section class="dashboard-card w-100">
      <div class="courses-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.coursesManagement.title') }}</h1>
        <AppButton
          v-if="canCreateCourses"
          class="new-course-btn"
          type="button"
          :tone-color="authStore.roleColor"
          @click="startCreateCourse"
        >
          + {{ t('pages.coursesManagement.actions.newCourse') }}
        </AppButton>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.coursesManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <AppEntityGrid
        class="mb-3"
        :items="filteredCourses"
        :show-search="true"
        :search-value="search"
        :search-placeholder="t('nav.topbar.quickSearch')"
        :empty-text="t('pages.coursesManagement.table.empty')"
        min-item-width="340px"
        @update:search-value="search = $event"
      >
        <template #item="{ item: course }">
          <CourseCard
            :course="course"
            :can-edit="canUpdateCourses"
            :can-delete="canDeleteCourses"
            @edit="startEditCourse"
            @delete="requestDeleteCourse"
          />
        </template>
      </AppEntityGrid>
    </section>

    <CourseFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :can-edit="true"
      :faculty-options="facultyOptions"
      :department-options="departmentOptions"
      :instructor-options="instructorOptions"
      :prerequisite-course-options="prerequisiteCourseOptions"
      @cancel="closeDialog"
      @save="handleSaveCourse"
      @update:code="draft.code = $event"
      @update:name_ar="draft.name_ar = $event"
      @update:name_en="draft.name_en = $event"
      @update:faculty_id="setFacultyId($event)"
      @update:department_id="setDepartmentId($event)"
      @update:required_hours="draft.required_hours = $event"
      @update:room_consumed_hours="draft.room_consumed_hours = $event"
      @update:lab_consumed_hours="draft.lab_consumed_hours = $event"
      @update:min_student_year_level="draft.min_student_year_level = $event"
      @update:max_student_year_level="draft.max_student_year_level = $event"
      @update:prerequisite_course_ids="draft.prerequisite_course_ids = $event"
      @add-section="addSection($event)"
      @remove-section="removeSection"
      @update-section="updateSection($event.id, $event.patch)"
    />

    <AppConfirmDialog
      :open="Boolean(coursePendingDelete)"
      :title="t('pages.coursesManagement.confirmDelete.title')"
      :message="
        t('pages.coursesManagement.confirmDelete.message', {
          course: coursePendingDelete?.name_en ?? '',
        })
      "
      :confirm-text="t('pages.coursesManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.coursesManagement.confirmDelete.cancel')"
      @cancel="coursePendingDelete = null"
      @confirm="confirmDeleteCourse"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppEntityGrid from '@/components/common/AppEntityGrid.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useCoursesManagementPage } from '@/features/courses/model/composables/useCoursesManagementPage'
import CourseCard from '@/features/courses/ui/components/CourseCard.vue'
import CourseFormDialog from '@/features/courses/ui/components/CourseFormDialog.vue'
import { useCoursesViewData } from '@/features/courses/ui/model/composables/useCoursesViewData'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const {
  courses,
  faculties,
  instructors,
  coursesCount,
  coursesWithLabCount,
  coursesWithoutLabCount,
  facultyOptions,
  departmentOptions,
  instructorOptions,
  prerequisiteCourseOptions,
  draft,
  isDialogOpen,
  isEditing,
  startCreateCourse,
  startEditCourse,
  closeDialog,
  saveCourse,
  removeCourse,
  setFacultyId,
  setDepartmentId,
  addSection,
  removeSection,
  updateSection,
} = useCoursesManagementPage()

const search = ref('')
const coursePendingDelete = ref(null)
const canCreateCourses = computed(() => authStore.hasPermission('courses.create'))
const canUpdateCourses = computed(() => authStore.hasPermission('courses.update'))
const canDeleteCourses = computed(() => authStore.hasPermission('courses.delete'))

const { filteredCourses, statsCards } = useCoursesViewData({
  courses,
  faculties,
  instructors,
  facultyOptions,
  coursesCount,
  coursesWithLabCount,
  coursesWithoutLabCount,
  search,
  t,
})

function requestDeleteCourse(course) {
  if (!canDeleteCourses.value) return
  coursePendingDelete.value = course
}

async function confirmDeleteCourse() {
  if (!canDeleteCourses.value) return
  if (!coursePendingDelete.value) return
  await removeCourse(coursePendingDelete.value.id)
  coursePendingDelete.value = null
}

async function handleSaveCourse() {
  if (isEditing.value && !canUpdateCourses.value) return
  if (!isEditing.value && !canCreateCourses.value) return
  const saved = await saveCourse()
  if (!saved) {
    toast.error(t('pages.coursesManagement.errors.invalidForm'))
  }
}
</script>

<style scoped>
.courses-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-course-btn {
  padding: 0.6rem 1rem;
}

</style>
