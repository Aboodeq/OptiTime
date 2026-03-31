<template>
  <slot v-if="allowed" />
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/store/auth.store'

const props = defineProps({
  permission: { type: String, default: '' },
  anyOf: { type: Array, default: () => [] },
  allOf: { type: Array, default: () => [] },
})

const authStore = useAuthStore()

const allowed = computed(() => {
  if (props.permission) return authStore.hasPermission(props.permission)

  if (props.allOf.length > 0) {
    return props.allOf.every((permission) => authStore.hasPermission(permission))
  }

  if (props.anyOf.length > 0) {
    return authStore.hasAnyPermission(props.anyOf)
  }

  return true
})
</script>
