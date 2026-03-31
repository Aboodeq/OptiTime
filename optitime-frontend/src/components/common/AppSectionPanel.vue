<template>
  <section class="section-panel" :class="toneClass">
    <header class="section-panel__head">
      <div class="section-panel__start">
        <i v-if="icon" :class="[icon, 'section-panel__icon']"></i>
        <div class="section-panel__title-wrap">
          <h3 class="section-panel__title">{{ title }}</h3>
          <p v-if="subtitle" class="section-panel__subtitle">{{ subtitle }}</p>
        </div>
      </div>
      <div class="section-panel__meta">
        <span v-if="badge" class="section-panel__badge">{{ badge }}</span>
      </div>
    </header>
    <div class="section-panel__body">
      <slot />
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  badge: { type: String, default: '' },
  icon: { type: String, default: '' },
  tone: {
    type: String,
    default: 'neutral',
    validator: (v) => ['neutral', 'soft', 'hard'].includes(v),
  },
})

const toneClass = computed(() => `section-panel--${props.tone}`)
</script>

<style scoped>
.section-panel {
  border: 1px solid #e7eaf6;
  border-radius: 16px;
  background: #fff;
}

.section-panel__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.65rem;
  padding: 0.72rem 0.85rem;
  border-bottom: 1px solid #eef1fb;
}

.section-panel__start {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  min-width: 0;
}

.section-panel--soft .section-panel__head {
  background: #f8f8ff;
}

.section-panel--hard .section-panel__head {
  background: #fff7f8;
}

.section-panel__title {
  margin: 0;
  font-size: 0.98rem;
  font-weight: 800;
}

.section-panel__subtitle {
  margin: 0.2rem 0 0;
  font-size: 0.74rem;
  color: #98a0b4;
}

.section-panel__meta {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.section-panel__badge {
  padding: 0.14rem 0.45rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 700;
  color: #6558db;
  background: #efeeff;
}

.section-panel--hard .section-panel__badge {
  color: #d94863;
  background: #ffecef;
}

.section-panel__icon {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #eef1ff;
  border: 1px solid #dfe5ff;
  color: #5d4cf4;
  font-size: 0.95rem;
}

.section-panel--hard .section-panel__icon {
  background: #ffeef1;
  border-color: #ffd7df;
  color: #d94863;
}

.section-panel__body {
  padding: 0.55rem;
}

@media (max-width: 576px) {
  .section-panel__head {
    align-items: flex-start;
  }

  .section-panel__meta {
    padding-top: 0.1rem;
  }

  .section-panel__badge {
    white-space: nowrap;
  }
}
</style>
