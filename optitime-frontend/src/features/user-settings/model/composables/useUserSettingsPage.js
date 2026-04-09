import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { userSettingsService } from '@/features/user-settings/api/userSettings.service'
import { useUserSettingsStore } from '@/features/user-settings/model/stores/userSettings.store'
import { useAuthStore } from '@/store/auth.store'

export function useUserSettingsPage() {
  const authStore = useAuthStore()
  const userSettingsStore = useUserSettingsStore()
  const { settings } = storeToRefs(userSettingsStore)

  const draftProfile = ref({
    name: authStore.user?.name ?? '',
    email: authStore.user?.email ?? '',
    avatar_url: authStore.user?.avatar_url ?? '',
  })
  const draftNotifications = ref(null)
  const draftSecurity = ref({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  })

  const userId = computed(() => authStore.user?.id ?? null)
  const canUpdateProfile = computed(() => authStore.hasPermission('profile.update'))
  const canUpdateSecurity = computed(() => authStore.hasPermission('profile.update'))
  const canUpdateNotifications = computed(() => authStore.hasPermission('profile.update'))
  const canCreateBackup = computed(() => authStore.hasPermission('profile.update'))

  async function initialize() {
    if (!userId.value) return
    await userSettingsStore.ensureInitialized(userId.value)
    draftProfile.value = {
      name: authStore.user?.name ?? '',
      email: authStore.user?.email ?? '',
      avatar_url: authStore.user?.avatar_url ?? '',
    }
    draftNotifications.value = userSettingsStore.createDraft()?.notifications ?? null
  }

  async function saveProfile() {
    if (!canUpdateProfile.value) return false
    const updated = await userSettingsService.updateProfile(authStore.user, draftProfile.value)
    if (!updated) return false
    authStore.patchCurrentUser(updated)
    return true
  }

  async function updatePassword() {
    if (!canUpdateSecurity.value) return { ok: false, code: 'FORBIDDEN' }
    if (!userId.value) return { ok: false, code: 'NO_USER' }
    if (draftSecurity.value.newPassword !== draftSecurity.value.confirmPassword) {
      return { ok: false, code: 'PASSWORD_MISMATCH' }
    }

    const result = await userSettingsService.updatePassword(
      userId.value,
      draftSecurity.value.currentPassword,
      draftSecurity.value.newPassword,
    )
    if (result.ok) {
      draftSecurity.value = {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      }
    }
    return result
  }

  /**
   * @returns {Promise<{ ok: true } | { ok: false, code: string, status?: number }>}
   */
  async function saveNotifications() {
    if (!canUpdateNotifications.value) return { ok: false, code: 'FORBIDDEN' }
    if (!userId.value || !draftNotifications.value) return { ok: false, code: 'INVALID' }
    const nextSettings = {
      ...(settings.value ?? {}),
      notifications: { ...draftNotifications.value },
    }
    return userSettingsStore.saveSettings(userId.value, nextSettings)
  }

  async function createBackup() {
    if (!canCreateBackup.value) return null
    return userSettingsService.createSystemBackup(authStore.user, settings.value)
  }

  return {
    draftProfile,
    draftNotifications,
    draftSecurity,
    canUpdateProfile,
    canUpdateSecurity,
    canUpdateNotifications,
    canCreateBackup,
    initialize,
    saveProfile,
    updatePassword,
    saveNotifications,
    createBackup,
  }
}
