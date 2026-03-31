import { defineStore } from 'pinia'
import { ref } from 'vue'
import { userSettingsService } from '@/features/user-settings/api/userSettings.service'

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

  async function saveSettings(userId, nextSettings) {
    if (!userId) return false
    const saved = await userSettingsService.saveSettings(userId, nextSettings)
    if (!saved) return false
    settings.value = await userSettingsService.getSettings(userId)
    initializedUserId.value = userId
    return true
  }

  function createDraft() {
    if (!settings.value) return null
    return structuredClone(settings.value)
  }

  return {
    settings,
    ensureInitialized,
    saveSettings,
    createDraft,
  }
})
