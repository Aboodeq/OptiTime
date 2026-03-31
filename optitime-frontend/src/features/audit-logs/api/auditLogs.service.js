const AUDIT_LOGS_SEED = [
  {
    id: 'log-1',
    actor_user_id: 'demo-admin',
    actor_name: 'Demo Admin',
    event_type: 'success',
    module: 'users',
    action: 'create_user',
    description: 'Created user "Nour Al-Saleh".',
    target_table: 'users',
    target_id: 'usr-1001',
    request_id: 'req-20260331-001',
    ip_address: '192.168.1.10',
    user_agent: 'Mozilla/5.0',
    metadata: { method: 'POST', route: '/api/users' },
    before_data: null,
    after_data: { id: 'usr-1001', email: 'nour@optitime.com', role: 'coordinator' },
    created_at: '2026-03-31T08:10:00.000Z',
  },
  {
    id: 'log-2',
    actor_user_id: 'demo-admin',
    actor_name: 'Demo Admin',
    event_type: 'info',
    module: 'roles',
    action: 'update_role_permissions',
    description: 'Updated permissions for role "management".',
    target_table: 'roles',
    target_id: 'role-management',
    request_id: 'req-20260331-002',
    ip_address: '192.168.1.10',
    user_agent: 'Mozilla/5.0',
    metadata: { method: 'PUT', route: '/api/roles/role-management/permissions' },
    before_data: { permissions_count: 6 },
    after_data: { permissions_count: 10 },
    created_at: '2026-03-31T08:22:00.000Z',
  },
  {
    id: 'log-3',
    actor_user_id: 'demo-management',
    actor_name: 'Demo Management',
    event_type: 'warning',
    module: 'constraints',
    action: 'update_schedule_settings',
    description: 'Changed daily max lectures from 4 to 6.',
    target_table: 'schedule_settings',
    target_id: 'ss-01',
    request_id: 'req-20260331-003',
    ip_address: '192.168.1.15',
    user_agent: 'Mozilla/5.0',
    metadata: { method: 'PUT', route: '/api/schedule-settings/ss-01' },
    before_data: { max_daily_lectures: 4 },
    after_data: { max_daily_lectures: 6 },
    created_at: '2026-03-31T09:30:00.000Z',
  },
  {
    id: 'log-4',
    actor_user_id: null,
    actor_name: 'System',
    event_type: 'error',
    module: 'auth',
    action: 'failed_login',
    description: 'Failed login attempt due to invalid credentials.',
    target_table: 'users',
    target_id: null,
    request_id: 'req-20260331-004',
    ip_address: '10.10.0.8',
    user_agent: 'Mozilla/5.0',
    metadata: { method: 'POST', route: '/api/auth/login' },
    before_data: null,
    after_data: null,
    created_at: '2026-03-31T10:04:00.000Z',
  },
]

let auditLogsDb = AUDIT_LOGS_SEED.map((item) => ({ ...item }))

function cloneLog(item) {
  return { ...item }
}

export const auditLogsService = {
  async getAuditLogs() {
    return auditLogsDb.map(cloneLog)
  },
}
