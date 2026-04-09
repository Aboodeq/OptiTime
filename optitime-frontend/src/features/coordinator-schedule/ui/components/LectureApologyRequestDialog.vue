<template>
  <AppDialog :open="open" :title="t('pages.instructorWeeklySchedule.requests.apology.title')" @close="handleCancel">
    <div class="d-grid gap-3">
      <p class="request-dialog__hint mb-0">
        {{
          t('pages.instructorWeeklySchedule.requests.apology.hintWithDay', {
            day: lectureDayLabel || '-',
          })
        }}
      </p>

      <AppInputField
        input-id="apology-request-date"
        :label="t('pages.instructorWeeklySchedule.requests.fields.requestedDate')"
        icon="bi bi-calendar-event"
        input-type="date"
        :model-value="requestDate"
        :error="dateError"
        @update:model-value="requestDate = $event"
      />

      <AppTextareaField
        input-id="apology-request-note"
        :label="t('pages.instructorWeeklySchedule.requests.fields.reason')"
        :model-value="note"
        :placeholder="t('pages.instructorWeeklySchedule.requests.apology.reasonPlaceholder')"
        :rows="4"
        @update:model-value="note = $event"
      />
      <div v-if="noteError" class="request-dialog__error">{{ noteError }}</div>

      <div class="d-flex justify-content-end gap-2 pt-1">
        <AppButton variant="outline" type="button" @click="handleCancel">
          {{ t('common.actions.cancel') }}
        </AppButton>
        <AppButton type="button" :disabled="!canSubmit" @click="handleSubmit">
          {{ t('pages.instructorWeeklySchedule.requests.actions.submit') }}
        </AppButton>
      </div>
    </div>
  </AppDialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppDialog from '@/components/common/AppDialog.vue'
import AppInputField from '@/components/common/AppInputField.vue'
import AppTextareaField from '@/components/common/AppTextareaField.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  lectureDay: { type: String, default: '' },
  lectureDayLabel: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit'])
const { t } = useI18n()

const requestDate = ref('')
const note = ref('')
const DAY_TO_INDEX = Object.freeze({
  sun: 0,
  mon: 1,
  tue: 2,
  wed: 3,
  thu: 4,
  fri: 5,
  sat: 6,
})

const expectedDayIndex = computed(() => DAY_TO_INDEX[`${props.lectureDay || ''}`.toLowerCase()] ?? null)

const dateError = computed(() => {
  if (!requestDate.value) return t('pages.instructorWeeklySchedule.requests.errors.requestedDateRequired')
  if (!isFutureDate(requestDate.value)) {
    return t('pages.instructorWeeklySchedule.requests.errors.requestedDateFuture')
  }
  if (!isMatchingLectureDay(requestDate.value)) {
    return t('pages.instructorWeeklySchedule.requests.errors.requestedDateDayMismatch', {
      day: props.lectureDayLabel || '-',
    })
  }
  return ''
})

const noteError = computed(() => {
  if (!note.value.trim()) return t('pages.instructorWeeklySchedule.requests.errors.reasonRequired')
  return ''
})

const canSubmit = computed(() => !dateError.value && !noteError.value)

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) resetForm()
  },
)

function handleCancel() {
  emit('close')
}

function handleSubmit() {
  if (!canSubmit.value) return
  emit('submit', {
    requested_date: requestDate.value,
    note: note.value.trim(),
  })
}

function isFutureDate(value) {
  const today = new Date()
  const todayLocal = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const selected = new Date(`${value}T00:00:00`)
  return selected > todayLocal
}

function isMatchingLectureDay(value) {
  if (expectedDayIndex.value === null) return true
  const selected = new Date(`${value}T00:00:00`)
  return selected.getDay() === expectedDayIndex.value
}

function resetForm() {
  requestDate.value = ''
  note.value = ''
}
</script>

<style scoped>
.request-dialog__hint {
  color: #475569;
  font-size: 0.92rem;
}

.request-dialog__error {
  margin-top: -0.5rem;
  color: #e63946;
  font-size: 12px;
  font-weight: 600;
}
</style>
