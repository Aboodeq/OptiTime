<template>
  <RouterView />
  <AppGlobalLoadingOverlay />
</template>

<script setup>
import { onBeforeUnmount, onMounted } from 'vue'
import { RouterView } from 'vue-router'
import AppGlobalLoadingOverlay from '@/components/common/AppGlobalLoadingOverlay.vue'
import { useLoadingStore } from '@/store/loading.store'

const loadingStore = useLoadingStore()

function handleBeforeUnload(event) {
  if (!loadingStore.shouldBlockLeaving) return
  event.preventDefault()
  event.returnValue = ''
}

onMounted(() => {
  window.addEventListener('beforeunload', handleBeforeUnload)
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
})
</script>
