<template>
  <div class="feature-item">
    <div class="feature-icon">
      <i :class="feature.icon"></i>
    </div>
    <div>
      <div class="feature-title">{{ feature.title }}</div>
      <div class="feature-desc">{{ feature.desc }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  feature: {
    type: Object,
    required: true,
  },
})
</script>
