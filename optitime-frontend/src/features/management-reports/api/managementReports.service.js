import { apiJson } from '@/api/client'
import { authService } from '@/features/auth/api/auth.service'

function normalizeOccupancyPayload(raw) {
  if (!raw || typeof raw !== 'object') {
    return { used_rooms: 0, total_rooms: 0, occupancy_rate: 0 }
  }
  const rateRaw = raw.occupancy_rate ?? raw.rate ?? 0
  const rate = typeof rateRaw === 'number' ? rateRaw : Number(rateRaw) || 0
  return {
    used_rooms: Number(raw.used_rooms) || 0,
    total_rooms: Number(raw.total_rooms) || 0,
    occupancy_rate: rate,
  }
}

function demoClassroomOccupancy() {
  return { used_rooms: 12, total_rooms: 40, occupancy_rate: 0.3 }
}

function demoLabUtilization() {
  return { lab_sessions: 48, lab_rooms: 6 }
}

function demoStudyHours() {
  return [
    { day_of_week: 0, sessions: 14 },
    { day_of_week: 2, sessions: 22 },
    { day_of_week: 4, sessions: 18 },
  ]
}

function demoPeakPeriods() {
  return [
    { start_time: '09:00:00', concurrent_sections: 8 },
    { start_time: '10:00:00', concurrent_sections: 11 },
  ]
}

function demoResources() {
  return [
    {
      id: 'demo-r1',
      name_en: 'Projector',
      name_ar: 'جهاز عرض',
      type: 'equipment',
      quantity: 12,
      status: 'available',
      location_en: 'Store A',
      location_ar: 'مخزن أ',
    },
    {
      id: 'demo-r2',
      name_en: 'Lab kit A',
      name_ar: 'مجموعة معمل أ',
      type: 'consumable',
      quantity: 40,
      status: 'available',
      location_en: 'Lab 3',
      location_ar: 'مختبر 3',
    },
  ]
}

function demoCompliance() {
  return { ok: true, violations: [] }
}

export const managementReportsService = {
  async getClassroomOccupancy(semesterId) {
    if (authService.isDemoMode()) {
      return demoClassroomOccupancy()
    }
    const { data } = await apiJson(
      `/management/reports/classroom-occupancy?semester_id=${encodeURIComponent(semesterId)}`,
      { method: 'GET' },
    )
    return normalizeOccupancyPayload(data)
  },

  async getLabUtilization(semesterId) {
    if (authService.isDemoMode()) {
      return demoLabUtilization()
    }
    const { data } = await apiJson(
      `/management/reports/lab-utilization?semester_id=${encodeURIComponent(semesterId)}`,
      { method: 'GET' },
    )
    if (!data || typeof data !== 'object') {
      return { lab_sessions: 0, lab_rooms: 0 }
    }
    return {
      lab_sessions: Number(data.lab_sessions) || 0,
      lab_rooms: Number(data.lab_rooms) || 0,
    }
  },

  async getStudyHoursDistribution(semesterId) {
    if (authService.isDemoMode()) {
      return demoStudyHours()
    }
    const { data } = await apiJson(
      `/management/reports/study-hours-distribution?semester_id=${encodeURIComponent(semesterId)}`,
      { method: 'GET' },
    )
    return Array.isArray(data) ? data : []
  },

  async getPeakPeriods(semesterId) {
    if (authService.isDemoMode()) {
      return demoPeakPeriods()
    }
    const { data } = await apiJson(
      `/management/reports/peak-periods?semester_id=${encodeURIComponent(semesterId)}`,
      { method: 'GET' },
    )
    return Array.isArray(data) ? data : []
  },

  async getResourceUtilization() {
    if (authService.isDemoMode()) {
      return demoResources()
    }
    const { data } = await apiJson('/management/reports/resource-utilization', { method: 'GET' })
    return Array.isArray(data) ? data : []
  },

  async getComplianceRestrictions(semesterId) {
    if (authService.isDemoMode()) {
      return demoCompliance()
    }
    const { data } = await apiJson(
      `/management/reports/compliance-restrictions?semester_id=${encodeURIComponent(semesterId)}`,
      { method: 'GET' },
    )
    if (!data || typeof data !== 'object') {
      return { ok: true, violations: [] }
    }
    return {
      ok: Boolean(data.ok),
      violations: Array.isArray(data.violations) ? data.violations : [],
    }
  },
}
