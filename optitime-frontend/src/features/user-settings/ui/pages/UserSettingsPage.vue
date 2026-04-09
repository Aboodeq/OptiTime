<template>
  <AppShell :page-title="t('routes.userSettings')">
    <section class="dashboard-card w-100 user-settings-page">
      <div class="user-settings-toolbar">
        <div>
          <h1 class="h4 fw-bold mb-0">{{ t('pages.userSettings.title') }}</h1>
          <p class="dashboard-card__meta mb-0">{{ t('pages.userSettings.subtitle') }}</p>
        </div>
      </div>

      <div class="user-settings-grid">
        <AppSectionPanel
          :title="t('pages.userSettings.sections.profile.title')"
          :subtitle="t('pages.userSettings.sections.profile.subtitle')"
          icon="bi bi-person-badge"
        >
          <div class="section-form-grid">
            <AppImageUploadField
              :model-value="draftProfile.avatar_url"
              :label="t('pages.userSettings.profile.avatar')"
              :hint="t('pages.userSettings.profile.avatarHint')"
              :accepted-text="t('pages.userSettings.profile.avatarAccepted')"
              :clear-text="t('pages.userSettings.profile.avatarClear')"
              accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
              :allowed-mime-types="[
                'image/png',
                'image/jpeg',
                'image/jpg',
                'image/webp',
                'image/svg+xml',
              ]"
              :disabled="!canUpdateProfile"
              @update:model-value="draftProfile.avatar_url = $event"
            />
            <AppInputField
              input-id="settings-profile-name"
              :label="t('pages.userSettings.profile.name')"
              icon="bi bi-person"
              :model-value="draftProfile.name"
              :placeholder="t('pages.userSettings.profile.namePlaceholder')"
              :disabled="!canUpdateProfile"
              @update:model-value="draftProfile.name = $event"
            />
            <AppInputField
              input-id="settings-profile-email"
              :label="t('pages.userSettings.profile.email')"
              icon="bi bi-envelope"
              :model-value="draftProfile.email"
              :placeholder="t('pages.userSettings.profile.emailPlaceholder')"
              :disabled="!canUpdateProfile"
              @update:model-value="draftProfile.email = $event"
            />
          </div>
          <div class="section-actions">
            <AppButton
              type="button"
              icon="bi bi-check2-circle"
              :disabled="!canUpdateProfile"
              @click="handleSaveProfile"
            >
              {{ t('pages.userSettings.actions.saveProfile') }}
            </AppButton>
          </div>
        </AppSectionPanel>

        <AppSectionPanel
          :title="t('pages.userSettings.sections.security.title')"
          :subtitle="t('pages.userSettings.sections.security.subtitle')"
          icon="bi bi-shield-lock"
          tone="hard"
        >
          <div class="section-form-grid">
            <AppInputField
              input-id="settings-password-current"
              :label="t('pages.userSettings.security.currentPassword')"
              icon="bi bi-key"
              :model-value="draftSecurity.currentPassword"
              input-type="password"
              autocomplete="current-password"
              :disabled="!canUpdateSecurity"
              @update:model-value="draftSecurity.currentPassword = $event"
            />
            <AppInputField
              input-id="settings-password-new"
              :label="t('pages.userSettings.security.newPassword')"
              icon="bi bi-lock"
              :model-value="draftSecurity.newPassword"
              input-type="password"
              autocomplete="new-password"
              :disabled="!canUpdateSecurity"
              @update:model-value="draftSecurity.newPassword = $event"
            />
            <AppInputField
              input-id="settings-password-confirm"
              :label="t('pages.userSettings.security.confirmPassword')"
              icon="bi bi-shield-check"
              :model-value="draftSecurity.confirmPassword"
              input-type="password"
              autocomplete="new-password"
              :disabled="!canUpdateSecurity"
              @update:model-value="draftSecurity.confirmPassword = $event"
            />
          </div>
          <div class="section-actions">
            <AppButton
              type="button"
              icon="bi bi-lock-fill"
              :disabled="!canUpdateSecurity"
              @click="handleUpdatePassword"
            >
              {{ t('pages.userSettings.actions.updatePassword') }}
            </AppButton>
          </div>
        </AppSectionPanel>

        <AppSectionPanel
          :title="t('pages.userSettings.sections.notifications.title')"
          :subtitle="t('pages.userSettings.sections.notifications.subtitle')"
          icon="bi bi-bell"
          tone="soft"
        >
          <div class="notifications-list">
            <AppSettingRow
              icon="bi bi-envelope-check"
              :title="t('pages.userSettings.notifications.emailScheduleUpdates')"
              :description="t('pages.userSettings.notifications.emailScheduleUpdatesHint')"
              end-min-width="56px"
            >
              <AppToggleSwitch
                :model-value="draftNotifications?.emailScheduleUpdates ?? false"
                :disabled="!canUpdateNotifications"
                @update:model-value="setNotification('emailScheduleUpdates', $event)"
              />
            </AppSettingRow>
            <AppSettingRow
              icon="bi bi-calendar2-week"
              :title="t('pages.userSettings.notifications.emailReminders')"
              :description="t('pages.userSettings.notifications.emailRemindersHint')"
              end-min-width="56px"
            >
              <AppToggleSwitch
                :model-value="draftNotifications?.emailReminders ?? false"
                :disabled="!canUpdateNotifications"
                @update:model-value="setNotification('emailReminders', $event)"
              />
            </AppSettingRow>
            <AppSettingRow
              icon="bi bi-megaphone"
              :title="t('pages.userSettings.notifications.pushAnnouncements')"
              :description="t('pages.userSettings.notifications.pushAnnouncementsHint')"
              end-min-width="56px"
            >
              <AppToggleSwitch
                :model-value="draftNotifications?.pushAnnouncements ?? false"
                :disabled="!canUpdateNotifications"
                @update:model-value="setNotification('pushAnnouncements', $event)"
              />
            </AppSettingRow>
            <AppSettingRow
              icon="bi bi-exclamation-triangle"
              :title="t('pages.userSettings.notifications.pushSystemAlerts')"
              :description="t('pages.userSettings.notifications.pushSystemAlertsHint')"
              end-min-width="56px"
            >
              <AppToggleSwitch
                :model-value="draftNotifications?.pushSystemAlerts ?? false"
                :disabled="!canUpdateNotifications"
                @update:model-value="setNotification('pushSystemAlerts', $event)"
              />
            </AppSettingRow>
            <AppSettingRow
              icon="bi bi-journal-text"
              :title="t('pages.userSettings.notifications.weeklyDigest')"
              :description="t('pages.userSettings.notifications.weeklyDigestHint')"
              end-min-width="56px"
            >
              <AppToggleSwitch
                :model-value="draftNotifications?.weeklyDigest ?? false"
                :disabled="!canUpdateNotifications"
                @update:model-value="setNotification('weeklyDigest', $event)"
              />
            </AppSettingRow>
          </div>
          <div class="section-actions">
            <AppButton
              type="button"
              icon="bi bi-bell-fill"
              :disabled="!canUpdateNotifications"
              @click="handleSaveNotifications"
            >
              {{ t('pages.userSettings.actions.saveNotifications') }}
            </AppButton>
          </div>
        </AppSectionPanel>

        <AppSectionPanel
          v-if="canCreateBackup"
          :title="t('pages.userSettings.sections.backup.title')"
          :subtitle="t('pages.userSettings.sections.backup.subtitle')"
          icon="bi bi-database-up"
        >
          <p class="backup-note mb-3">{{ t('pages.userSettings.backup.note') }}</p>
          <div class="section-actions">
            <AppButton type="button" icon="bi bi-download" @click="handleBackup">
              {{ t('pages.userSettings.actions.createBackup') }}
            </AppButton>
          </div>
        </AppSectionPanel>
      </div>
    </section>
  </AppShell>
</template>

<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'vue-toastification'
import AppButton from '@/components/common/AppButton.vue'
import AppImageUploadField from '@/components/common/AppImageUploadField.vue'
import AppInputField from '@/components/common/AppInputField.vue'
import AppSectionPanel from '@/components/common/AppSectionPanel.vue'
import AppSettingRow from '@/components/common/AppSettingRow.vue'
import AppToggleSwitch from '@/components/common/AppToggleSwitch.vue'
import AppShell from '@/components/layout/AppShell.vue'
import { useUserSettingsPage } from '@/features/user-settings/model/composables/useUserSettingsPage'

const { t } = useI18n()
const toast = useToast()

const {
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
} = useUserSettingsPage()

onMounted(() => {
  initialize()
})

function setNotification(key, value) {
  if (!draftNotifications.value) return
  draftNotifications.value[key] = value
}

async function handleSaveProfile() {
  const saved = await saveProfile()
  if (!saved) {
    toast.error(t('pages.userSettings.errors.profileInvalid'))
    return
  }
  toast.success(t('pages.userSettings.toasts.profileSaved'))
}

async function handleUpdatePassword() {
  const result = await updatePassword()
  if (result.ok) {
    toast.success(t('pages.userSettings.toasts.passwordUpdated'))
    return
  }

  const errorMap = {
    PASSWORD_MISMATCH: 'pages.userSettings.errors.passwordMismatch',
    INVALID_CURRENT_PASSWORD: 'pages.userSettings.errors.invalidCurrentPassword',
    WEAK_PASSWORD: 'pages.userSettings.errors.weakPassword',
    SAME_PASSWORD: 'pages.userSettings.errors.samePassword',
  }
  toast.error(t(errorMap[result.code] ?? 'pages.userSettings.errors.passwordUpdateFailed'))
}

const notificationsErrorKeys = {
  NO_TOKEN: 'pages.userSettings.errors.notificationsSaveNeedLogin',
  UNAUTHORIZED: 'pages.userSettings.errors.notificationsSaveNeedLogin',
  FORBIDDEN: 'pages.userSettings.errors.notificationsSaveForbidden',
  NETWORK_OR_SERVER: 'pages.userSettings.errors.notificationsSaveServer',
  INVALID: 'pages.userSettings.errors.notificationsSaveFailed',
  NO_USER: 'pages.userSettings.errors.notificationsSaveFailed',
}

async function handleSaveNotifications() {
  const result = await saveNotifications()
  if (!result.ok) {
    const key = notificationsErrorKeys[result.code] ?? 'pages.userSettings.errors.notificationsSaveFailed'
    toast.error(t(key))
    return
  }
  toast.success(t('pages.userSettings.toasts.notificationsSaved'))
}

async function handleBackup() {
  const backup = await createBackup()
  if (!backup) return
  const blob = new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `optitime-backup-${new Date().toISOString().slice(0, 10)}.json`
  link.click()
  window.URL.revokeObjectURL(url)
  toast.success(t('pages.userSettings.toasts.backupCreated'))
}
</script>

<style scoped>
.user-settings-page {
  background: #f7f8ff;
}

.user-settings-toolbar {
  margin-bottom: 1rem;
}

.user-settings-grid {
  display: grid;
  gap: 1rem;
}

.section-form-grid {
  display: grid;
  gap: 0.75rem;
}

.notifications-list {
  display: grid;
  gap: 0.35rem;
}

.section-actions {
  margin-top: 0.8rem;
  display: flex;
  justify-content: flex-end;
}

.backup-note {
  color: #667085;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .section-actions {
    justify-content: stretch;
  }

  .section-actions :deep(.app-button) {
    width: 100%;
  }
}
</style>
