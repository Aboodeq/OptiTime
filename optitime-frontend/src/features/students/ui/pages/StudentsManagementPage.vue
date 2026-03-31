<template>
  <AppShell :page-title="t('routes.studentsManagement')">
    <section class="dashboard-card w-100">
      <div class="students-toolbar">
        <h1 class="h4 fw-bold mb-0">{{ t('pages.studentsManagement.title') }}</h1>
        <AppCan permission="students.create">
          <AppButton
            class="new-student-btn"
            type="button"
            :tone-color="authStore.roleColor"
            @click="startCreateStudent"
          >
            + {{ t('pages.studentsManagement.actions.newStudent') }}
          </AppButton>
        </AppCan>
      </div>
      <p class="dashboard-card__meta mb-3">{{ t('pages.studentsManagement.subtitle') }}</p>

      <AppStatsGrid :cards="statsCards" class="mb-3" />

      <div class="students-table-card">
        <AppDataTable
          :columns="tableColumns"
          :rows="filteredStudents"
          row-key="id"
          :empty-text="t('pages.studentsManagement.table.empty')"
          :show-search="canViewStudents"
          :search-value="search"
          :search-placeholder="t('nav.topbar.quickSearch')"
          @update:search-value="search = $event"
        >
          <template #cell-student="{ row }">
            <div class="d-flex flex-column">
              <span class="fw-semibold">{{ row.name }}</span>
              <small class="text-secondary">{{ row.email }}</small>
            </div>
          </template>

          <template #cell-university-number="{ row }">
            <code>{{ row.university_number }}</code>
          </template>

          <template #cell-completed-hours="{ row }">
            {{ row.completed_hours }}
          </template>

          <template #cell-year-level="{ row }">
            {{ row.year_level }}
          </template>

          <template #cell-study-status="{ row }">
            {{ t(`pages.studentsManagement.studyStatus.${row.study_status}`) }}
          </template>

          <template #cell-faculty="{ row }">
            {{ row.faculty_name || '-' }}
          </template>

          <template #cell-department="{ row }">
            {{ row.department_name || '-' }}
          </template>

          <template #cell-status="{ row }">
            <span :class="row.is_active ? 'text-success' : 'text-secondary'">
              {{
                row.is_active
                  ? t('pages.studentsManagement.status.active')
                  : t('pages.studentsManagement.status.inactive')
              }}
            </span>
          </template>

          <template #cell-actions="{ row }">
            <div class="text-start">
              <AppCan permission="students.update">
                <AppIconButton
                  class="me-2"
                  icon="bi bi-pencil-square"
                  variant="primary"
                  :title="t('pages.studentsManagement.actions.edit')"
                  :aria-label="t('pages.studentsManagement.actions.edit')"
                  @click="startEditStudent(row)"
                />
              </AppCan>
              <AppCan permission="students.delete">
                <AppIconButton
                  icon="bi bi-trash3"
                  variant="danger"
                  :title="t('pages.studentsManagement.actions.delete')"
                  :aria-label="t('pages.studentsManagement.actions.delete')"
                  @click="requestDeleteStudent(row)"
                />
              </AppCan>
            </div>
          </template>
        </AppDataTable>
      </div>
    </section>

    <StudentFormDialog
      :open="isDialogOpen"
      :is-editing="isEditing"
      :draft="draft"
      :faculty-options="facultyOptions"
      :department-options="departmentOptions"
      :can-edit="canCreateStudents || canUpdateStudents"
      @cancel="closeDialog"
      @save="handleSaveStudent"
      @update:name="draft.name = $event"
      @update:email="draft.email = $event"
      @update:password="draft.password = $event"
      @update:completed-hours="draft.completed_hours = $event"
      @update:year-level="draft.year_level = $event"
      @update:study-status="draft.study_status = $event"
      @update:faculty-id="setFacultyId($event)"
      @update:department-id="setDepartmentId($event)"
      @update:is-active="draft.is_active = $event"
    />

    <AppConfirmDialog
      :open="Boolean(studentPendingDelete)"
      :title="t('pages.studentsManagement.confirmDelete.title')"
      :message="
        t('pages.studentsManagement.confirmDelete.message', { student: studentPendingDelete?.name ?? '' })
      "
      :confirm-text="t('pages.studentsManagement.confirmDelete.confirm')"
      :cancel-text="t('pages.studentsManagement.confirmDelete.cancel')"
      @cancel="studentPendingDelete = null"
      @confirm="confirmDeleteStudent"
    />
  </AppShell>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import AppCan from '@/components/common/AppCan.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppConfirmDialog from '@/components/common/AppConfirmDialog.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppIconButton from '@/components/common/AppIconButton.vue'
import AppStatsGrid from '@/components/common/AppStatsGrid.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useStudentsManagementPage } from '@/features/students/model/composables/useStudentsManagementPage'
import StudentFormDialog from '@/features/students/ui/components/StudentFormDialog.vue'
import { useAuthStore } from '@/store/auth.store'

const { t } = useI18n()
const toast = useToast()
const authStore = useAuthStore()
const {
  students,
  faculties,
  facultyOptions,
  departmentOptions,
  studentsCount,
  activeStudentsCount,
  inactiveStudentsCount,
  draft,
  isDialogOpen,
  isEditing,
  startCreateStudent,
  startEditStudent,
  closeDialog,
  saveStudent,
  removeStudent,
  setFacultyId,
  setDepartmentId,
} = useStudentsManagementPage()

const search = ref('')
const studentPendingDelete = ref(null)

const canViewStudents = computed(() => authStore.hasPermission('students.view'))
const canCreateStudents = computed(() => authStore.hasPermission('students.create'))
const canUpdateStudents = computed(() => authStore.hasPermission('students.update'))
const canDeleteStudents = computed(() => authStore.hasPermission('students.delete'))
const facultyNameById = computed(() => {
  const map = new Map()
  faculties.value.forEach((faculty) => {
    map.set(faculty.id, faculty.name_en)
  })
  return map
})
const departmentNameById = computed(() => {
  const map = new Map()
  faculties.value.forEach((faculty) => {
    faculty.departments.forEach((department) => {
      map.set(department.id, department.name_en)
    })
  })
  return map
})

const filteredStudents = computed(() => {
  if (!canViewStudents.value) return []
  const q = search.value.trim().toLowerCase()
  const rows = students.value.map((student) => ({
    ...student,
    faculty_name: facultyNameById.value.get(student.faculty_id) ?? '',
    department_name: departmentNameById.value.get(student.department_id) ?? '',
  }))
  if (!q) return rows
  return rows.filter(
    (student) =>
      student.name.toLowerCase().includes(q) ||
      student.email.toLowerCase().includes(q) ||
      student.university_number.toLowerCase().includes(q) ||
      student.study_status.toLowerCase().includes(q) ||
      student.faculty_name.toLowerCase().includes(q) ||
      student.department_name.toLowerCase().includes(q),
  )
})

const statsCards = computed(() => [
  {
    id: 'students',
    value: studentsCount.value,
    label: t('pages.studentsManagement.stats.totalStudents'),
    icon: 'bi bi-mortarboard',
    iconColor: '#8b5cf6',
  },
  {
    id: 'active',
    value: activeStudentsCount.value,
    label: t('pages.studentsManagement.stats.activeStudents'),
    icon: 'bi bi-person-check',
    iconColor: '#22c55e',
  },
  {
    id: 'inactive',
    value: inactiveStudentsCount.value,
    label: t('pages.studentsManagement.stats.inactiveStudents'),
    icon: 'bi bi-person-dash',
    iconColor: '#e11d48',
  },
])

const tableColumns = computed(() => [
  { key: 'student', label: t('pages.studentsManagement.table.student') },
  { key: 'university-number', label: t('pages.studentsManagement.table.universityNumber') },
  { key: 'completed-hours', label: t('pages.studentsManagement.table.completedHours') },
  { key: 'year-level', label: t('pages.studentsManagement.table.yearLevel') },
  { key: 'study-status', label: t('pages.studentsManagement.table.studyStatus') },
  { key: 'faculty', label: t('pages.studentsManagement.table.faculty') },
  { key: 'department', label: t('pages.studentsManagement.table.department') },
  { key: 'status', label: t('pages.studentsManagement.table.status') },
  ...(canUpdateStudents.value || canDeleteStudents.value
    ? [{ key: 'actions', label: t('pages.studentsManagement.table.actions') }]
    : []),
])

function requestDeleteStudent(student) {
  if (!canDeleteStudents.value) return
  studentPendingDelete.value = student
}

async function confirmDeleteStudent() {
  if (!canDeleteStudents.value) return
  if (!studentPendingDelete.value) return
  await removeStudent(studentPendingDelete.value.id)
  studentPendingDelete.value = null
}

async function handleSaveStudent() {
  if (isEditing.value && !canUpdateStudents.value) return
  if (!isEditing.value && !canCreateStudents.value) return
  const saved = await saveStudent()
  if (!saved) {
    toast.error(t('pages.studentsManagement.errors.invalidForm'))
  }
}
</script>

<style scoped>
.students-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
}

.new-student-btn {
  padding: 0.6rem 1rem;
}

.students-table-card {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
}
</style>
