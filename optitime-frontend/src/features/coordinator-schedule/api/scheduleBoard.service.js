import { apiJson } from '@/api/client'

/**
 * Read-only schedule grid + `settings_id` for generation (requires schedules.view).
 */
export async function getCoordinatorScheduleBoardContext() {
  const { data } = await apiJson('/coordinator/schedule-board-context')
  return data && typeof data === 'object' ? data : {}
}

export const scheduleBoardService = {
  getCoordinatorScheduleBoardContext,
}
