import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '@/features/auth/api/auth.service'
import { lectureRequestsService } from '@/features/lecture-requests/api/lectureRequests.service'

export const useLectureRequestsStore = defineStore('lectureRequests', () => {
  const instructorRequests = ref([])
  const coordinatorRequests = ref([])
  const loadingInstructor = ref(false)
  const loadingCoordinator = ref(false)
  const instructorLoaded = ref(false)
  const coordinatorLoaded = ref(false)

  const totalInstructorRequests = computed(() => instructorRequests.value.length)
  const totalCoordinatorRequests = computed(() => coordinatorRequests.value.length)

  async function loadInstructorRequests() {
    loadingInstructor.value = true
    try {
      instructorRequests.value = await lectureRequestsService.instructorList()
      instructorLoaded.value = true
    } finally {
      loadingInstructor.value = false
    }
  }

  async function loadCoordinatorRequests() {
    loadingCoordinator.value = true
    try {
      coordinatorRequests.value = await lectureRequestsService.coordinatorList()
      coordinatorLoaded.value = true
    } finally {
      loadingCoordinator.value = false
    }
  }

  /**
   * @param {{ schedule_session_id: string, request_type: string, requested_date: string, note?: string, instructor_id?: string }} payload
   */
  async function createInstructorRequest(payload) {
    try {
      const row = await lectureRequestsService.instructorCreate(payload)
      if (!row) return false
      instructorRequests.value = [row, ...instructorRequests.value.filter((r) => r.id !== row.id)]
      if (authService.isDemoMode()) {
        coordinatorRequests.value = await lectureRequestsService.coordinatorList()
      }
      return true
    } catch {
      return false
    }
  }

  async function reviewCoordinatorRequest(id, { status, review_note: reviewNote }) {
    try {
      const row = await lectureRequestsService.coordinatorUpdate(id, {
        status,
        review_note: reviewNote,
      })
      if (!row) return false
      coordinatorRequests.value = coordinatorRequests.value.map((r) => (r.id === row.id ? row : r))
      if (authService.isDemoMode()) {
        instructorRequests.value = await lectureRequestsService.instructorList()
      }
      return true
    } catch {
      return false
    }
  }

  async function instructorUpdatePending(id, partial) {
    try {
      const row = await lectureRequestsService.instructorUpdate(id, partial)
      if (!row) return false
      instructorRequests.value = instructorRequests.value.map((r) => (r.id === row.id ? row : r))
      if (authService.isDemoMode()) {
        coordinatorRequests.value = await lectureRequestsService.coordinatorList()
      }
      return true
    } catch {
      return false
    }
  }

  async function deleteInstructorRequest(id) {
    try {
      if (authService.isDemoMode()) {
        const ok = await lectureRequestsService.demoDeletePending(id)
        if (!ok) return false
        instructorRequests.value = instructorRequests.value.filter((r) => r.id !== id)
        coordinatorRequests.value = coordinatorRequests.value.filter((r) => r.id !== id)
        return true
      }
      const ok = await lectureRequestsService.instructorDelete(id)
      if (!ok) return false
      instructorRequests.value = instructorRequests.value.filter((r) => r.id !== id)
      return true
    } catch {
      return false
    }
  }

  async function coordinatorEditPending(id, partial) {
    if (!authService.isDemoMode()) return false
    const row = await lectureRequestsService.demoCoordinatorEditPending(id, partial)
    if (!row) return false
    coordinatorRequests.value = coordinatorRequests.value.map((r) => (r.id === row.id ? row : r))
    instructorRequests.value = await lectureRequestsService.instructorList()
    return true
  }

  async function deletePending(id) {
    if (authService.isDemoMode()) {
      const ok = await lectureRequestsService.demoDeletePending(id)
      if (!ok) return false
      instructorRequests.value = instructorRequests.value.filter((r) => r.id !== id)
      coordinatorRequests.value = coordinatorRequests.value.filter((r) => r.id !== id)
      return true
    }
    return false
  }

  return {
    instructorRequests,
    coordinatorRequests,
    loadingInstructor,
    loadingCoordinator,
    instructorLoaded,
    coordinatorLoaded,
    totalInstructorRequests,
    totalCoordinatorRequests,
    loadInstructorRequests,
    loadCoordinatorRequests,
    createInstructorRequest,
    reviewCoordinatorRequest,
    instructorUpdatePending,
    coordinatorEditPending,
    deletePending,
    deleteInstructorRequest,
  }
})
