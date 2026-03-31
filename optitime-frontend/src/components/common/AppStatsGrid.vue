<template>
  <div class="app-stats-grid">
    <article v-for="card in cards" :key="card.id" class="app-stats-grid__card">
      <div class="app-stats-grid__top">
        <i
          v-if="card.icon"
          :class="[card.icon, 'app-stats-grid__icon']"
          :style="{ color: card.iconColor }"
        ></i>
      </div>
      <div class="app-stats-grid__content">
        <div class="app-stats-grid__value">{{ card.value }}</div>
        <div class="app-stats-grid__label">{{ card.label }}</div>
      </div>
    </article>
  </div>
</template>

<script setup>
defineProps({
  cards: {
    type: Array,
    default: () => [],
  },
})
</script>

<style scoped>
.app-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.app-stats-grid__card {
  background: #fff;
  border: 1px solid #eef0f7;
  border-radius: 14px;
  padding: 0.85rem 1rem;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
  display: grid;
  grid-template-columns: auto 1fr;
  align-items: center;
  gap: 0.75rem;
  transition:
    transform 180ms ease,
    box-shadow 180ms ease;
}

.app-stats-grid__card:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
}

.app-stats-grid__top {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: #f8fafc;
}

.app-stats-grid__icon {
  font-size: 1rem;
}

.app-stats-grid__content {
  min-width: 0;
}

.app-stats-grid__value {
  font-size: 1.35rem;
  font-weight: 800;
  line-height: 1;
}

.app-stats-grid__label {
  color: #94a3b8;
  font-size: 0.8rem;
  margin-top: 0.35rem;
}

@media (max-width: 992px) {
  .app-stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>
