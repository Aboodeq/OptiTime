import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  DEMO_ROLE_META,
  LOGIN_FEATURES,
  LOGIN_STATS,
} from '@/features/auth/model/constants/loginContent'

export function useLoginPageContent() {
  const { t } = useI18n()

  const features = computed(() =>
    LOGIN_FEATURES.map((feature) => ({
      ...feature,
      title: t(`pages.login.features.${feature.key}.title`),
      desc: t(`pages.login.features.${feature.key}.desc`),
    })),
  )

  const stats = computed(() =>
    LOGIN_STATS.map((stat) => ({
      ...stat,
      value: t(`pages.login.stats.${stat.key}.value`),
      label: t(`pages.login.stats.${stat.key}.label`),
    })),
  )

  const demoRoles = computed(() =>
    DEMO_ROLE_META.map((role) => ({
      ...role,
      label: t(`roles.${role.value}`),
    })),
  )

  return { features, stats, demoRoles }
}
