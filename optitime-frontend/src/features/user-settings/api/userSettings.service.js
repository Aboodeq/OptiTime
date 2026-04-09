import { apiJson } from '@/api/client'
import { authService, mapApiUserToAppUser } from '@/features/auth/api/auth.service'
import { getStoredToken } from '@/lib/authSession'

const SETTINGS_STORAGE_PREFIX = 'optitime.user-settings.v1'
const PASSWORD_STORAGE_PREFIX = 'optitime.user-password.v1'

function getSettingsStorageKey(userId) {
  return `${SETTINGS_STORAGE_PREFIX}.${userId}`
}

function getPasswordStorageKey(userId) {
  return `${PASSWORD_STORAGE_PREFIX}.${userId}`
}

export function defaultSettings() {
  return {
    notifications: {
      emailScheduleUpdates: true,
      emailReminders: true,
      pushAnnouncements: true,
      pushSystemAlerts: true,
      weeklyDigest: false,
    },
  }
}

function mergeSettings(payload) {
  const defaults = defaultSettings()
  const incoming = payload && typeof payload === 'object' ? payload : {}
  return {
    ...defaults,
    ...incoming,
    notifications: {
      ...defaults.notifications,
      ...(incoming.notifications && typeof incoming.notifications === 'object'
        ? incoming.notifications
        : {}),
    },
  }
}

function readJson(key, fallback) {
  if (typeof window === 'undefined') return fallback
  try {
    const raw = window.localStorage.getItem(key)
    if (!raw) return fallback
    const parsed = JSON.parse(raw)
    return parsed ?? fallback
  } catch {
    return fallback
  }
}

function writeJson(key, value) {
  if (typeof window === 'undefined') return
  try {
    window.localStorage.setItem(key, JSON.stringify(value))
  } catch {
    // ignore storage errors
  }
}

function readPassword(userId) {
  if (!userId) return null
  return readJson(getPasswordStorageKey(userId), null)
}

function writePassword(userId, value) {
  if (!userId) return
  writeJson(getPasswordStorageKey(userId), value)
}

/** @param {Record<string, unknown>} row */
function mapApiRowToNotifications(row) {
  return {
    emailScheduleUpdates: Boolean(row?.email_schedule_updates ?? true),
    emailReminders: Boolean(row?.email_reminders ?? true),
    pushAnnouncements: Boolean(row?.push_announcements ?? true),
    pushSystemAlerts: Boolean(row?.push_system_alerts ?? true),
    weeklyDigest: Boolean(row?.weekly_digest ?? false),
  }
}

function notificationsToApiBody(notifications) {
  return {
    email_schedule_updates: Boolean(notifications?.emailScheduleUpdates),
    email_reminders: Boolean(notifications?.emailReminders),
    push_announcements: Boolean(notifications?.pushAnnouncements),
    push_system_alerts: Boolean(notifications?.pushSystemAlerts),
    weekly_digest: Boolean(notifications?.weeklyDigest),
  }
}

function mapPasswordApiError(err) {
  const errors = err?.data?.errors
  if (errors && typeof errors === 'object') {
    if (Array.isArray(errors.current_password) && errors.current_password.length) {
      return { ok: false, code: 'INVALID_CURRENT_PASSWORD' }
    }
    if (Array.isArray(errors.password) && errors.password.length) {
      return { ok: false, code: 'WEAK_PASSWORD' }
    }
  }
  return { ok: false, code: 'PASSWORD_UPDATE_FAILED' }
}

async function getSettingsDemo(userId) {
  if (!userId) return defaultSettings()
  return mergeSettings(readJson(getSettingsStorageKey(userId), defaultSettings()))
}

async function saveSettingsDemo(userId, payload) {
  if (!userId) return { ok: false, code: 'NO_USER' }
  const normalized = mergeSettings(payload)
  writeJson(getSettingsStorageKey(userId), normalized)
  return { ok: true }
}

export const userSettingsService = {
  async getSettings(userId) {
    if (authService.isDemoMode()) {
      return getSettingsDemo(userId)
    }
    try {
      const { data } = await apiJson('/profile/settings', { method: 'GET' })
      return mergeSettings({
        notifications: mapApiRowToNotifications(data && typeof data === 'object' ? data : {}),
      })
    } catch {
      return defaultSettings()
    }
  },

  /**
   * Persist notification toggles. Returns `{ ok: true }` or `{ ok: false, code }` so the UI can explain failures.
   * @returns {Promise<{ ok: true } | { ok: false, code: string, status?: number }>}
   */
  async saveSettings(userId, payload) {
    if (authService.isDemoMode()) {
      return saveSettingsDemo(userId, payload)
    }
    if (!userId) return { ok: false, code: 'NO_USER' }
    if (!getStoredToken()) {
      return { ok: false, code: 'NO_TOKEN' }
    }
    const normalized = mergeSettings(payload)
    try {
      await apiJson('/profile/settings', {
        method: 'PUT',
        json: notificationsToApiBody(normalized.notifications),
      })
      return { ok: true }
    } catch (err) {
      const status = err?.status
      if (status === 401) return { ok: false, code: 'UNAUTHORIZED', status }
      if (status === 403) return { ok: false, code: 'FORBIDDEN', status }
      return { ok: false, code: 'NETWORK_OR_SERVER', status }
    }
  },

  async updateProfile(currentUser, profile) {
    const nextName = String(profile?.name ?? '').trim()
    const nextEmail = String(profile?.email ?? '').trim().toLowerCase()
    const nextAvatar = String(profile?.avatar_url ?? '').trim()
    if (!nextName || !nextEmail) return null

    if (authService.isDemoMode()) {
      if (!currentUser) return null
      return {
        ...currentUser,
        name: nextName,
        email: nextEmail,
        avatar_url: nextAvatar,
      }
    }

    try {
      const { data } = await apiJson('/profile', {
        method: 'PUT',
        json: {
          full_name: nextName,
          email: nextEmail,
          avatar_url: nextAvatar || null,
        },
      })
      return mapApiUserToAppUser(data)
    } catch {
      return null
    }
  },

  async updatePassword(userId, currentPassword, nextPassword) {
    if (!userId) return { ok: false, code: 'NO_USER' }

    if (authService.isDemoMode()) {
      const savedPassword = readPassword(userId) ?? 'password123'
      if (currentPassword !== savedPassword) {
        return { ok: false, code: 'INVALID_CURRENT_PASSWORD' }
      }
      if (typeof nextPassword !== 'string' || nextPassword.length < 8) {
        return { ok: false, code: 'WEAK_PASSWORD' }
      }
      if (nextPassword === currentPassword) {
        return { ok: false, code: 'SAME_PASSWORD' }
      }
      writePassword(userId, nextPassword)
      return { ok: true }
    }

    if (typeof nextPassword !== 'string' || nextPassword.length < 8) {
      return { ok: false, code: 'WEAK_PASSWORD' }
    }
    if (nextPassword === currentPassword) {
      return { ok: false, code: 'SAME_PASSWORD' }
    }

    try {
      await apiJson('/auth/change-password', {
        method: 'POST',
        json: {
          current_password: currentPassword,
          password: nextPassword,
          password_confirmation: nextPassword,
        },
      })
      return { ok: true }
    } catch (err) {
      return mapPasswordApiError(err)
    }
  },

  async createSystemBackup(user, settings) {
    const now = new Date()
    return {
      generatedAtIso: now.toISOString(),
      generatedBy: {
        id: user?.id ?? null,
        name: user?.name ?? null,
        email: user?.email ?? null,
        role: user?.role?.key ?? null,
      },
      userSettings: settings ?? defaultSettings(),
    }
  },
}
