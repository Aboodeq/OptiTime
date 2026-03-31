import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { BASE_SIDEBAR_SECTIONS } from '@/features/navigation/model/constants/sidebarNavigation'
import { useAuthStore } from '@/store/auth.store'

export function useSidebarNavigation() {
  const authStore = useAuthStore()
  const { roleKey, permissions } = storeToRefs(authStore)

  const visibleSections = computed(() =>
    BASE_SIDEBAR_SECTIONS.map((section) => ({
      ...section,
      items: section.items.filter((item) => {
        if (!item.permissions?.length) return true
        return item.permissions.some((permission) => permissions.value.includes(permission))
      }),
    })).filter((section) => section.items.length > 0),
  )

  return {
    roleKey,
    visibleSections,
  }
}
