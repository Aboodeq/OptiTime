import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export const useLectureRequestsStore = defineStore('lectureRequests', () => {
  const requests = ref([])
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
