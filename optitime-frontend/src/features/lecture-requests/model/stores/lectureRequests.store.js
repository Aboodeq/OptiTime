import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

const INITIAL_REQUESTS = [
  {
    id: 'f326af2c-5472-4dc5-99d0-1b80aa100001',
    schedule_session_id: 'session-1',
    instructor_id: 'instructor-demo',
    request_type: 'apology',
    requested_date: '2026-04-14',
    note: 'Unexpected conference attendance.',
    status: 'pending',
    review_note: '',
    reviewed_at: null,
  },
  {
    id: 'cb87ea78-4870-49d0-bf45-1d6d15100002',
    schedule_session_id: 'session-2',
    instructor_id: 'instructor-demo',
    request_type: 'makeup',
    requested_date: '2026-04-20',
    note: 'Need extra practical session.',
    status: 'pending',
    review_note: '',
    reviewed_at: null,
  },
]

export const useLectureRequestsStore = defineStore('lectureRequests', () => {
  const requests = ref([...INITIAL_REQUESTS])
  const totalRequests = computed(() => requests.value.length)

  function createRequest(payload) {
    const requestType = payload?.request_type === 'makeup' ? 'makeup' : 'apology'
    requests.value = [
      ...requests.value,
      {
        id: crypto.randomUUID(),
        schedule_session_id: payload?.schedule_session_id || '',
        instructor_id: payload?.instructor_id || '',
        request_type: requestType,
        requested_date: payload?.requested_date || '',
        note: payload?.note || '',
        status: 'pending',
        review_note: '',
        reviewed_at: null,
      },
    ]
  }

  function updateRequest(id, partial) {
    const index = requests.value.findIndex((item) => item.id === id)
    if (index < 0) return false
    if (requests.value[index].status !== 'pending') return false
    requests.value[index] = {
      ...requests.value[index],
      ...partial,
    }
    return true
  }

  function deleteRequest(id) {
    const index = requests.value.findIndex((item) => item.id === id)
    if (index < 0) return false
    if (requests.value[index].status !== 'pending') return false
    requests.value = requests.value.filter((item) => item.id !== id)
    return true
  }

  return {
    requests,
    totalRequests,
    createRequest,
    updateRequest,
    deleteRequest,
  }
})
