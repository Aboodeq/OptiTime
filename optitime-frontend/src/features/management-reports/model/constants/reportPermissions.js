/**
 * Matches Laravel `permission:reports.*` middleware on `/management/reports/*`.
 * Used for route meta, sidebar, and per-panel fetches.
 */
export const MANAGEMENT_REPORT_PERMISSIONS = Object.freeze([
  'reports.classroom_occupancy',
  'reports.lab_utilization',
  'reports.study_hours',
  'reports.peak_periods',
  'reports.resource_utilization',
  'reports.compliance',
])
