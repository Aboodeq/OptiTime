<template>
  <div class="stat-box">
    <div class="stat-value">{{ stat.value }}</div>
    <div class="stat-label">{{ stat.label }}</div>
  </div>
</template>

<script setup>
defineProps({
  stat: {
    type: Object,
    required: true,
  },
})
</script>
