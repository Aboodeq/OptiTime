const SETTINGS_STORAGE_PREFIX = 'optitime.user-settings.v1'
const PASSWORD_STORAGE_PREFIX = 'optitime.user-password.v1'

function getSettingsStorageKey(userId) {
  return `${SETTINGS_STORAGE_PREFIX}.${userId}`
}

function getPasswordStorageKey(userId) {
  return `${PASSWORD_STORAGE_PREFIX}.${userId}`
}

function defaultSettings() {
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

export const userSettingsService = {
  async getSettings(userId) {
    if (!userId) return defaultSettings()
    return mergeSettings(readJson(getSettingsStorageKey(userId), defaultSettings()))
  },

  async saveSettings(userId, payload) {
    if (!userId) return false
    const normalized = mergeSettings(payload)
    writeJson(getSettingsStorageKey(userId), normalized)
    return true
  },

  async updateProfile(currentUser, profile) {
    if (!currentUser) return null
    const nextName = String(profile?.name ?? '').trim()
    const nextEmail = String(profile?.email ?? '').trim().toLowerCase()
    const nextAvatar = String(profile?.avatar_url ?? '').trim()
    if (!nextName || !nextEmail) return null
    return {
      ...currentUser,
      name: nextName,
      email: nextEmail,
      avatar_url: nextAvatar,
    }
  },

  async updatePassword(userId, currentPassword, nextPassword) {
    if (!userId) return { ok: false, code: 'NO_USER' }
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
