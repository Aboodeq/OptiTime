import { apiFetch, apiJson } from '@/api/client'
import {
  boardSessionsToSyncPayload,
  mapCoordinatorPlanToDraftShape,
} from '@/features/coordinator-schedule/api/weeklyScheduleMappers'

/**
 * @param {string} semesterId
 */
export async function listPlans(semesterId) {
  const q = semesterId ? `?semester_id=${encodeURIComponent(semesterId)}` : ''
  const { data } = await apiJson(`/coordinator/schedules${q}`)
  return Array.isArray(data) ? data : []
}

/**
 * @param {Record<string, unknown>} payload
 */
export async function createPlan(payload) {
  const { data } = await apiJson('/coordinator/schedules', {
    method: 'POST',
    json: payload,
  })
  return data
}

/**
 * @param {string} planId
 */
export async function getPlan(planId) {
  const { data } = await apiJson(`/coordinator/schedules/${planId}`)
  return data
}

/**
 * @param {string} planId
 * @param {Record<string, unknown>} payload
 */
export async function updatePlanMetadata(planId, payload) {
  const { data } = await apiJson(`/coordinator/schedules/${planId}`, {
    method: 'PUT',
    json: payload,
  })
  return data
}

/**
 * @param {string} planId
 * @param {Array<Record<string, unknown>>} sessions
 */
export async function syncPlanSessions(planId, sessions) {
  const { data } = await apiJson(`/coordinator/schedules/${planId}/sessions`, {
    method: 'PUT',
    json: { sessions: boardSessionsToSyncPayload(sessions) },
  })
  return data
}

/**
 * @param {Record<string, unknown>} body
 * @returns {Promise<{ ok: boolean, status: number, data: Record<string, unknown>|null }>}
 */
export async function generateSchedule(body) {
  const response = await apiFetch('/schedule/generate', {
    method: 'POST',
    json: body,
  })
  const text = await response.text()
  let data = null
  if (text) {
    try {
      data = JSON.parse(text)
    } catch {
      data = { message: text }
    }
  }
  return { ok: response.ok, status: response.status, data }
}

/**
 * @param {Record<string, unknown>} body
 */
export async function publishFromGeneration(body) {
  const { data } = await apiJson('/coordinator/schedules/publish-from-generation', {
    method: 'POST',
    json: body,
  })
  return data
}

/**
 * Load semester draft plan (or create), return draft shape for the store.
 * @param {string} semesterId
 */
export async function resolveOrCreateDraftPlan(semesterId) {
  const plans = await listPlans(semesterId)
  const drafts = plans
    .filter((p) => p && `${p.status}` === 'draft')
    .sort((a, b) => {
      const ta = new Date(a.created_at || 0).getTime()
      const tb = new Date(b.created_at || 0).getTime()
      return tb - ta
    })
  let planId = drafts[0]?.id
  if (!planId) {
    const created = await createPlan({ semester_id: semesterId, status: 'draft' })
    planId = created?.id
  }
  if (!planId) {
    return { planId: '', semester_id: semesterId, status: 'draft', notes: '', sessions: [] }
  }
  const full = await getPlan(planId)
  return mapCoordinatorPlanToDraftShape(full)
}

export const coordinatorScheduleService = {
  listPlans,
  createPlan,
  getPlan,
  updatePlanMetadata,
  syncPlanSessions,
  generateSchedule,
  publishFromGeneration,
  resolveOrCreateDraftPlan,
}
