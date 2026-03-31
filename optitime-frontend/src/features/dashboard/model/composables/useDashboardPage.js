import { storeToRefs } from 'pinia'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useSidebarNavigation } from '@/features/navigation/model/composables/useSidebarNavigation'
import { useAuthStore } from '@/store/auth.store'

export function useDashboardPage() {
  const router = useRouter()
  const authStore = useAuthStore()

  const { user, permissions, roleColor } = storeToRefs(authStore)
  const { roleKey, visibleSections } = useSidebarNavigation()

  const isCollapsed = ref(false)
  const isMobile = ref(false)
  const isMobileSidebarOpen = ref(false)
  const userInitial = computed(() => user.value?.name?.trim()?.charAt(0) || '?')

  function updateViewportState() {
    if (typeof window === 'undefined') return
    isMobile.value = window.innerWidth <= 992
  }

  watch(isMobile, (mobile) => {
    if (!mobile) isMobileSidebarOpen.value = false
  })

  onMounted(() => {
    updateViewportState()
    window.addEventListener('resize', updateViewportState)
  })

  onBeforeUnmount(() => {
    window.removeEventListener('resize', updateViewportState)
  })

  function toggleSidebarCollapse() {
    isCollapsed.value = !isCollapsed.value
  }

  function openMobileSidebar() {
    isMobileSidebarOpen.value = true
  }

  function closeMobileSidebar() {
    isMobileSidebarOpen.value = false
  }

  function handleSidebarNavigate() {
    if (isMobile.value) closeMobileSidebar()
  }

  function handleLogout() {
    closeMobileSidebar()
    authStore.logout()
    router.push('/login')
  }

  return {
    user,
    permissions,
    roleColor,
    roleKey,
    visibleSections,
    userInitial,
    isCollapsed,
    isMobile,
    isMobileSidebarOpen,
    toggleSidebarCollapse,
    openMobileSidebar,
    closeMobileSidebar,
    handleSidebarNavigate,
    handleLogout,
  }
}
