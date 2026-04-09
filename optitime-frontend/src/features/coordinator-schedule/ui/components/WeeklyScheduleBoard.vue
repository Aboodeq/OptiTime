<template>
  <div class="weekly-board">
    <table class="weekly-board__table">
      <thead>
        <tr>
          <th>{{ timeHeader }}</th>
          <th v-for="day in dayOptions" :key="day.value">{{ day.label }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="slot in timeSlots" :key="slot.start">
          <th>{{ slot.start }}</th>
          <td
            v-for="day in dayOptions"
            :key="`${day.value}-${slot.start}`"
            :class="cellClass(day.value, slot.start)"
            @dragover.prevent="handleDragOver(day.value, slot.start)"
            @drop.prevent="handleDrop(day.value, slot.start)"
          >
            <div class="weekly-board__session-list">
              <article
                v-for="session in sessionsByCell.get(`${day.value}|${slot.start}`) ?? []"
                :key="session.id"
                :class="sessionClass(session)"
                :draggable="draggable && !isBlocked(day.value, slot.start)"
                @dragstart="handleDragStart(session.id)"
                @click="emit('select-session', session)"
              >
                <div class="weekly-board__session-title">
                  {{ session.course_code }}
                </div>
                <div class="weekly-board__session-meta">
                  {{ session.room_name }}
                </div>
              </article>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  sessions: { type: Array, default: () => [] },
  dayOptions: { type: Array, default: () => [] },
  timeSlots: { type: Array, default: () => [] },
  blockedSlotStarts: { type: Array, default: () => [] },
  conflictSessionIds: { type: Array, default: () => [] },
  timeHeader: { type: String, default: 'Time' },
  draggable: { type: Boolean, default: false },
})

const emit = defineEmits(['move-session', 'select-session'])

const draggingSessionId = ref('')

const sessionsByCell = computed(() => {
  const map = new Map()
  for (const session of props.sessions) {
    const key = `${session.day}|${session.start}`
    if (!map.has(key)) map.set(key, [])
    map.get(key).push(session)
  }
  return map
})

function isBlocked(day, start) {
  return props.blockedSlotStarts.includes(start) || !props.dayOptions.some((item) => item.value === day)
}

function cellClass(day, start) {
  if (isBlocked(day, start)) return 'weekly-board__cell weekly-board__cell--blocked'
  return 'weekly-board__cell'
}

function handleDragStart(sessionId) {
  draggingSessionId.value = sessionId
}

function handleDragOver(day, start) {
  if (!draggingSessionId.value) return
  if (isBlocked(day, start)) return
}

function handleDrop(day, start) {
  if (!draggingSessionId.value) return
  if (isBlocked(day, start)) return
  emit('move-session', { sessionId: draggingSessionId.value, day, start })
  draggingSessionId.value = ''
}

function sessionClass(session) {
  const conflicted = props.conflictSessionIds.includes(session?.id)
  return conflicted
    ? 'weekly-board__session weekly-board__session--conflict'
    : 'weekly-board__session'
}
</script>

<style scoped>
.weekly-board {
  overflow: auto;
}
.weekly-board__table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}
.weekly-board__table th,
.weekly-board__table td {
  border: 1px solid #e5e7eb;
  padding: 0.5rem;
  text-align: center;
}
.weekly-board__table th {
  background: #f8fafc;
  font-size: 0.84rem;
}
.weekly-board__cell {
  min-width: 145px;
  height: 64px;
  background: #ffffff;
  vertical-align: top;
}
.weekly-board__cell--blocked {
  background: #f1f5f9;
}
.weekly-board__session-list {
  display: grid;
  gap: 0.45rem;
}
.weekly-board__session {
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  border-radius: 8px;
  padding: 0.42rem;
  cursor: move;
}
.weekly-board__session--conflict {
  background: #fee2e2;
  border-color: #ef4444;
}
.weekly-board__session-title {
  font-weight: 700;
  font-size: 0.84rem;
}
.weekly-board__session-meta {
  color: #64748b;
  font-size: 0.76rem;
}
</style>
