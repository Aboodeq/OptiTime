<template>
  <AppDialog :open="open" :title="t('pages.coordinatorWeeklySchedule.details.title')" @close="$emit('close')">
    <div v-if="lecture" class="lecture-details">
      <div class="lecture-details__row">
        <strong>{{ t('pages.coordinatorWeeklySchedule.details.course') }}:</strong>
        <span>{{ lecture.course_code }} - {{ lecture.course_name }}</span>
      </div>
      <div class="lecture-details__row">
        <strong>{{ t('pages.coordinatorWeeklySchedule.details.section') }}:</strong>
        <span>{{ lecture.section_id || '-' }}</span>
      </div>
      <div class="lecture-details__row">
        <strong>{{ t('pages.coordinatorWeeklySchedule.details.instructor') }}:</strong>
        <span>{{ lecture.instructor_name }}</span>
      </div>
      <div class="lecture-details__row">
        <strong>{{ t('pages.coordinatorWeeklySchedule.details.room') }}:</strong>
        <span>{{ lecture.room_name }}</span>
      </div>
      <div class="lecture-details__row">
        <strong>{{ t('pages.coordinatorWeeklySchedule.details.day') }}:</strong>
        <span>{{ dayLabel }}</span>
      </div>
      <div class="lecture-details__row">
        <strong>{{ t('pages.coordinatorWeeklySchedule.details.time') }}:</strong>
        <span>{{ lecture.start }} - {{ lecture.end }}</span>
      </div>
      <div class="lecture-details__row">
        <strong>{{ t('pages.coordinatorWeeklySchedule.details.ids') }}:</strong>
        <span>
          C: {{ lecture.course_id || '-' }},
          S: {{ lecture.section_id || '-' }},
          I: {{ lecture.instructor_id || '-' }},
          R: {{ lecture.room_id || '-' }}
        </span>
      </div>
      <div v-if="canRequestApology || canRequestMakeup" class="lecture-details__actions">
        <AppButton
          v-if="canRequestApology"
          variant="outline"
          type="button"
          @click="$emit('request-apology', lecture)"
        >
          {{ t('pages.coordinatorWeeklySchedule.details.actions.requestApology') }}
        </AppButton>
        <AppButton
          v-if="canRequestMakeup"
          variant="outline"
          type="button"
          @click="$emit('request-makeup', lecture)"
        >
          {{ t('pages.coordinatorWeeklySchedule.details.actions.requestMakeup') }}
        </AppButton>
      </div>
      <div class="lecture-details__students">
        <h6 class="mb-2">{{ t('pages.coordinatorWeeklySchedule.details.studentsTitle') }}</h6>
        <AppSearchField
          v-model="query"
          compact
          class="lecture-details__search"
          :placeholder="t('pages.coordinatorWeeklySchedule.details.students.searchPlaceholder')"
        />
        <AppDataTable
          :columns="studentColumns"
          :rows="filteredStudents"
          row-key="id"
          :empty-text="t('pages.coordinatorWeeklySchedule.details.students.empty')"
        />
      </div>
    </div>
  </AppDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppDataTable from '@/components/common/AppDataTable.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import AppSearchField from '@/components/common/AppSearchField.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  lecture: { type: Object, default: null },
  dayLabel: { type: String, default: '-' },
  students: { type: Array, default: () => [] },
  canRequestApology: { type: Boolean, default: false },
  canRequestMakeup: { type: Boolean, default: false },
})

defineEmits(['close', 'request-apology', 'request-makeup'])

const { t } = useI18n()
const query = ref('')

const studentColumns = computed(() => [
  { key: 'name', label: t('pages.coordinatorWeeklySchedule.details.students.name') },
  {
    key: 'university_number',
    label: t('pages.coordinatorWeeklySchedule.details.students.universityNumber'),
  },
  { key: 'year_level', label: t('pages.coordinatorWeeklySchedule.details.students.year') },
  { key: 'study_status', label: t('pages.coordinatorWeeklySchedule.details.students.status') },
])

watch(
  () => props.lecture?.id,
  () => {
    query.value = ''
  },
)

const filteredStudents = computed(() => {
  const value = query.value.trim().toLowerCase()
  if (!value) return props.students
  return props.students.filter((student) => `${student.name || ''}`.toLowerCase().includes(value))
})
</script>

<style scoped>
.lecture-details {
  display: grid;
  gap: 0.65rem;
}

.lecture-details__row {
  display: flex;
  gap: 0.55rem;
  flex-wrap: wrap;
  font-size: 0.92rem;
}

.lecture-details__students {
  margin-top: 0.35rem;
}

.lecture-details__actions {
  display: flex;
  gap: 0.6rem;
  flex-wrap: wrap;
  margin-top: 0.15rem;
}

.lecture-details__search {
  margin-bottom: 0.55rem;
}
</style>
