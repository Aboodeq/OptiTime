<template>
  <article class="kpi-card">
    <div v-if="icon" class="kpi-card__icon-wrap">
      <i :class="[icon, 'kpi-card__icon']"></i>
    </div>
    <div class="kpi-card__content">
      <div class="kpi-card__value">{{ value }}</div>
      <div class="kpi-card__label">{{ label }}</div>
    </div>
    <span v-if="badge" class="kpi-card__badge">{{ badge }}</span>
  </article>
</template>

<script setup>
defineProps({
  value: { type: [String, Number], required: true },
  label: { type: String, required: true },
  icon: { type: String, default: '' },
  badge: { type: String, default: '' },
})
</script>

<style scoped>
.kpi-card {
  position: relative;
  background: #fff;
  border: 1px solid #eceef6;
  border-radius: 14px;
  padding: 0.75rem 0.85rem;
  min-height: 74px;
  display: flex;
  align-items: center;
  gap: 0.7rem;
}

.kpi-card__content {
  min-width: 0;
}

.kpi-card__value {
  font-size: 1.45rem;
  font-weight: 800;
  line-height: 1;
}

.kpi-card__label {
  margin-top: 0.35rem;
  color: #8f96ab;
  font-size: 0.78rem;
}

.kpi-card__badge {
  position: absolute;
  top: 0.55rem;
  inset-inline-end: 0.55rem;
  padding: 0.15rem 0.42rem;
  border-radius: 999px;
  background: #f1f2ff;
  color: #6558db;
  font-size: 0.68rem;
  font-weight: 700;
}

.kpi-card__icon-wrap {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #eef1ff;
  border: 1px solid #dfe5ff;
  flex-shrink: 0;
}

.kpi-card__icon {
  color: #5d4cf4;
  font-size: 1rem;
}

@media (max-width: 576px) {
  .kpi-card {
    min-height: 68px;
  }

  .kpi-card__value {
    font-size: 1.25rem;
  }
}
</style>
