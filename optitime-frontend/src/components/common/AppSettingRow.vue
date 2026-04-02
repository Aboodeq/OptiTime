<template>
  <div class="setting-row">
    <div class="setting-row__leading">
      <i :class="[icon, 'setting-row__icon']"></i>
    </div>
    <div class="setting-row__content">
      <div class="setting-row__title">{{ title }}</div>
      <div v-if="description" class="setting-row__description">{{ description }}</div>
    </div>
    <div class="setting-row__end">
      <slot />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  icon: { type: String, required: true },
  title: { type: String, required: true },
  description: { type: String, default: '' },
  endMinWidth: { type: String, default: '200px' },
})
</script>

<style scoped>
.setting-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.75rem;
  align-items: center;
  padding: 0.55rem 0.6rem;
  border-radius: 12px;
}

.setting-row:hover {
  background: #f8f9fe;
}

.setting-row__leading {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #eef1ff;
  border: 1px solid #dfe5ff;
}

.setting-row__icon {
  color: #5d4cf4;
  font-size: 1rem;
}

.setting-row__title {
  font-weight: 700;
  font-size: 0.92rem;
}

.setting-row__description {
  margin-top: 0.15rem;
  color: #98a0b3;
  font-size: 0.75rem;
}

.setting-row__end {
  min-width: v-bind('props.endMinWidth');
}

@media (max-width: 768px) {
  .setting-row {
    grid-template-columns: auto 1fr;
  }

  .setting-row__end {
    grid-column: 1 / -1;
    min-width: 0;
  }
}
</style>
