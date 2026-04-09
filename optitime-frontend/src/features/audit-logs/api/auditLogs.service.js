import { apiJson } from '@/api/client'

const DEFAULT_PER_PAGE = 100

function stripModelNamespace(description) {
  if (typeof description !== 'string' || !description) return description
  return description.replace(/^App\\Models\\/, '')
}

/**
 * Map Laravel audit log JSON (with nested actor) to the row shape used by the UI.
 */
export function mapApiAuditLogToRow(log) {
  const actor = log?.actor
  const actorName =
    actor && typeof actor.full_name === 'string' && actor.full_name.trim()
      ? actor.full_name.trim()
      : log?.actor_user_id
        ? ''
        : 'System'

  return {
    id: log.id,
    actor_user_id: log.actor_user_id ?? null,
    actor_name: actorName,
    event_type: log.event_type ?? 'info',
    module: log.module ?? '',
    action: log.action ?? '',
    description: stripModelNamespace(log.description) ?? '',
    target_table: log.target_table ?? null,
    target_id: log.target_id ?? null,
    request_id: log.request_id ?? null,
    ip_address: log.ip_address ?? null,
    user_agent: log.user_agent ?? null,
    metadata: log.metadata ?? null,
    before_data: log.before_data ?? null,
    after_data: log.after_data ?? null,
    created_at: log.created_at ?? '',
  }
}

export const auditLogsService = {
  /**
   * Fetches one page of audit logs (see Laravel paginator). Stats on the page reflect this batch only.
   */
  async getAuditLogs({ perPage = DEFAULT_PER_PAGE } = {}) {
    const qs = new URLSearchParams({ per_page: String(perPage) })
    const { data } = await apiJson(`/admin/audit-logs?${qs}`)
    const items = Array.isArray(data?.data) ? data.data : []
    return items.map(mapApiAuditLogToRow)
  },
}
