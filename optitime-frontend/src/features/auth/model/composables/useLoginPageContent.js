import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const ROLE_META = [
  { value: 'admin', icon: 'bi bi-shield-fill', color: '#e63946', bg: '#fff0f0' },
  {
    value: 'coordinator',
    icon: 'bi bi-calendar-check-fill',
    color: '#4361ee',
    bg: '#f0f3ff',
  },
  {
    value: 'instructor',
    icon: 'bi bi-person-badge-fill',
    color: '#2dc653',
    bg: '#f0fff4',
  },
  {
    value: 'student',
    icon: 'bi bi-mortarboard-fill',
    color: '#f8961e',
    bg: '#fff8f0',
  },
  {
    value: 'management',
    icon: 'bi bi-briefcase-fill',
    color: '#0f766e',
    bg: '#f0fdfa',
  },
  { value: 'exams', icon: 'bi bi-journal-check', color: '#dc2626', bg: '#fff1f2' },
]

export function useLoginPageContent() {
  const { t } = useI18n()

  const features = computed(() => [
    {
      key: 'autoScheduling',
      icon: 'bi bi-lightning-charge-fill',
      title: t('pages.login.features.autoScheduling.title'),
      desc: t('pages.login.features.autoScheduling.desc'),
    },
    {
      key: 'zeroConflicts',
      icon: 'bi bi-shield-check-fill',
      title: t('pages.login.features.zeroConflicts.title'),
      desc: t('pages.login.features.zeroConflicts.desc'),
    },
    {
      key: 'instantNotifications',
      icon: 'bi bi-bell-fill',
      title: t('pages.login.features.instantNotifications.title'),
      desc: t('pages.login.features.instantNotifications.desc'),
    },
  ])

  const stats = computed(() => [
    {
      key: 'generationTime',
      value: t('pages.login.stats.generationTime.value'),
      label: t('pages.login.stats.generationTime.label'),
    },
    {
      key: 'conflictFree',
      value: t('pages.login.stats.conflictFree.value'),
      label: t('pages.login.stats.conflictFree.label'),
    },
    {
      key: 'concurrentUsers',
      value: t('pages.login.stats.concurrentUsers.value'),
      label: t('pages.login.stats.concurrentUsers.label'),
    },
  ])

  const demoRoles = computed(() =>
    ROLE_META.map((role) => ({
      ...role,
      label: t(`roles.${role.value}`),
    })),
  )

  return { features, stats, demoRoles }
}
