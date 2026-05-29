import { defineStore } from 'pinia'
import { ref } from 'vue'
import { userSettingsService } from '@/features/user-settings/api/userSettings.service'

function cloneSettings(value) {
  if (!value || typeof value !== 'object') return null
  try {
    return JSON.parse(JSON.stringify(value))
  } catch {
    return null
  }
}

export const useUserSettingsStore = defineStore('userSettings', () => {
  const initializedUserId = ref(null)
  const settings = ref(null)

  async function ensureInitialized(userId) {
    if (!userId) {
      settings.value = null
      initializedUserId.value = null
      return
    }
    if (initializedUserId.value === userId && settings.value) return
    settings.value = await userSettingsService.getSettings(userId)
    initializedUserId.value = userId
  }

  /**
   * @returns {Promise<{ ok: true } | { ok: false, code: string, status?: number }>}
   */
  async function saveSettings(userId, nextSettings) {
    if (!userId) return { ok: false, code: 'NO_USER' }
    const result = await userSettingsService.saveSettings(userId, nextSettings)
    if (!result.ok) return result
    settings.value = result.settings
    initializedUserId.value = userId
    return { ok: true }
  }

  function createDraft() {
    if (!settings.value) return null
    return cloneSettings(settings.value)
  }

  return {
    settings,
    ensureInitialized,
    saveSettings,
    createDraft,
  }
})
