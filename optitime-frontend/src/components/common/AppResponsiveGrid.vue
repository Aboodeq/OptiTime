<template>
  <div class="app-responsive-grid" :style="gridStyle">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  columns: {
    type: Number,
    default: 1,
  },
  lgColumns: {
    type: Number,
    default: null,
  },
  mdColumns: {
    type: Number,
    default: null,
  },
  smColumns: {
    type: Number,
    default: null,
  },
  gap: {
    type: String,
    default: '1rem',
  },
})

const gridStyle = computed(() => ({
  '--grid-gap': props.gap,
  '--grid-cols': String(props.columns),
  '--grid-cols-lg': String(props.lgColumns ?? props.columns),
  '--grid-cols-md': String(props.mdColumns ?? props.lgColumns ?? props.columns),
  '--grid-cols-sm': String(props.smColumns ?? props.mdColumns ?? props.lgColumns ?? props.columns),
}))
</script>

<style scoped>
.app-responsive-grid {
  display: grid;
  gap: var(--grid-gap, 1rem);
  grid-template-columns: repeat(var(--grid-cols, 1), minmax(0, 1fr));
}

@media (max-width: 1200px) {
  .app-responsive-grid {
    grid-template-columns: repeat(var(--grid-cols-lg, var(--grid-cols, 1)), minmax(0, 1fr));
  }
}

@media (max-width: 992px) {
  .app-responsive-grid {
    grid-template-columns: repeat(var(--grid-cols-md, var(--grid-cols-lg, var(--grid-cols, 1))), minmax(0, 1fr));
  }
}

@media (max-width: 576px) {
  .app-responsive-grid {
    grid-template-columns: repeat(
      var(--grid-cols-sm, var(--grid-cols-md, var(--grid-cols-lg, var(--grid-cols, 1)))),
      minmax(0, 1fr)
    );
  }
}
</style>
