<template>
  <div class="availability-grid-card">
    <div class="availability-grid-header">
      <div class="fw-semibold">{{ title }}</div>
      <div class="legend">
        <span class="legend-item legend-item--preferred">{{ preferredLabel }}</span>
        <span class="legend-item legend-item--available">{{ availableLabel }}</span>
        <span class="legend-item legend-item--unavailable">{{ unavailableLabel }}</span>
        <span class="legend-item legend-item--blocked">{{ blockedLabel }}</span>
      </div>
    </div>

    <div class="availability-grid-table-wrapper">
      <table class="availability-grid-table">
        <thead>
          <tr>
            <th>{{ timeHeader }}</th>
            <th v-for="day in dayOptions" :key="day.value">{{ day.label }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="slot in timeSlots" :key="slot.start">
            <td class="time-label">{{ slot.label }}</td>
            <td v-for="day in dayOptions" :key="`${day.value}-${slot.start}`">
              <button
                type="button"
                class="slot-cell"
                :class="`slot-cell--${getCellStatus(day.value, slot.start)}`"
                :disabled="getCellStatus(day.value, slot.start) === 'blocked'"
                @click="toggleSlot(day.value, slot.start, slot.end)"
              >
                <span v-if="getCellStatus(day.value, slot.start) === 'preferred'">{{ preferredLabel }}</span>
                <span v-else-if="getCellStatus(day.value, slot.start) === 'unavailable'">{{
                  unavailableLabel
                }}</span>
                <span v-else-if="getCellStatus(day.value, slot.start) === 'blocked'">{{ blockedLabel }}</span>
                <span v-else>{{ availableLabel }}</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  timeHeader: { type: String, required: true },
  preferredLabel: { type: String, required: true },
  availableLabel: { type: String, required: true },
  unavailableLabel: { type: String, required: true },
  blockedLabel: { type: String, required: true },
  dayOptions: { type: Array, default: () => [] },
  timeSlots: { type: Array, default: () => [] },
  modelValue: { type: Array, default: () => [] },
  blockedSlots: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const normalizedCells = computed(() =>
  (Array.isArray(props.modelValue) ? props.modelValue : []).filter(
    (item) => item?.day && item?.start && item?.end,
  ),
)

function getCellStatus(day, start) {
  if (props.blockedSlots.includes(start)) return 'blocked'
  const entry = normalizedCells.value.find((item) => item.day === day && item.start === start)
  if (!entry) return 'available'
  if (entry.status === 'unavailable') return 'unavailable'
  // Backward compatibility: old cells had no status and meant preferred.
  return 'preferred'
}

function toggleSlot(day, start, end) {
  if (props.blockedSlots.includes(start)) return
  const currentStatus = getCellStatus(day, start)
  if (currentStatus === 'preferred') {
    const next = normalizedCells.value.map((item) =>
      item.day === day && item.start === start ? { ...item, status: 'unavailable' } : item,
    )
    emit('update:modelValue', next)
    return
  }
  if (currentStatus === 'unavailable') {
    emit(
      'update:modelValue',
      normalizedCells.value.filter((item) => !(item.day === day && item.start === start)),
    )
    return
  }
  emit('update:modelValue', [...normalizedCells.value, { day, start, end, status: 'preferred' }])
}
</script>

<style scoped>
.availability-grid-card {
  background: #fff;
  border: 1px solid #eef0f7;
  border-radius: 16px;
  padding: 0.9rem;
}

.availability-grid-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.legend {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.legend-item {
  border-radius: 999px;
  padding: 0.15rem 0.55rem;
  font-size: 12px;
  font-weight: 600;
}

.legend-item--preferred {
  background: #fef3c7;
  color: #92400e;
}

.legend-item--available {
  background: #dcfce7;
  color: #166534;
}

.legend-item--unavailable {
  background: #fee2e2;
  color: #991b1b;
}

.legend-item--blocked {
  background: #e5e7eb;
  color: #374151;
}

.availability-grid-table-wrapper {
  overflow: auto;
  max-width: 100%;
}

.availability-grid-table {
  width: 100%;
  min-width: 760px;
  border-collapse: separate;
  border-spacing: 0.35rem;
}

.availability-grid-table th {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 2;
  white-space: nowrap;
}

.time-label {
  white-space: nowrap;
  font-size: 12px;
  font-weight: 600;
  position: sticky;
  inset-inline-start: 0;
  background: #fff;
  z-index: 1;
  box-shadow: 4px 0 8px -8px rgba(15, 23, 42, 0.25);
}

.slot-cell {
  width: 100%;
  border: 1px solid transparent;
  border-radius: 10px;
  min-height: 44px;
  font-size: 11px;
  font-weight: 600;
}

.slot-cell--preferred {
  background: #fef3c7;
  color: #92400e;
  border-color: #fde68a;
}

.slot-cell--available {
  background: #dcfce7;
  color: #166534;
  border-color: #bbf7d0;
}

.slot-cell--unavailable {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fecaca;
}

.slot-cell--blocked {
  background: #e5e7eb;
  color: #374151;
  border-color: #d1d5db;
  cursor: not-allowed;
}

@media (max-width: 992px) {
  .availability-grid-header {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 768px) {
  .availability-grid-card {
    padding: 0.65rem;
    border-radius: 12px;
  }

  .availability-grid-table {
    min-width: 100%;
    table-layout: fixed;
    border-spacing: 0.2rem;
  }

  .availability-grid-table th {
    font-size: 10px;
    position: static;
    top: auto;
  }

  .time-label {
    font-size: 10px;
    position: static;
    box-shadow: none;
  }

  .slot-cell {
    min-height: 34px;
    font-size: 9px;
    border-radius: 8px;
    padding: 0.2rem 0.1rem;
    line-height: 1.1;
  }

  .availability-grid-table th:first-child,
  .availability-grid-table td:first-child {
    width: 52px;
  }

  .legend-item {
    font-size: 10px;
    padding: 0.12rem 0.4rem;
  }
}
</style>
