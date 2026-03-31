<template>
  <div class="app-entity-grid">
    <div v-if="showSearch" class="app-entity-grid__toolbar">
      <AppSearchField
        compact
        :placeholder="searchPlaceholder"
        :model-value="searchValue"
        @update:model-value="$emit('update:searchValue', $event)"
      />
    </div>

    <div
      v-if="items.length > 0"
      class="app-entity-grid__content"
      :style="{ '--entity-grid-min': minItemWidth, '--entity-grid-gap': gap }"
    >
      <template v-for="(item, index) in items" :key="item?.id ?? index">
        <slot name="item" :item="item" :index="index" />
      </template>
    </div>

    <div v-else class="text-center text-secondary py-4">
      {{ emptyText }}
    </div>
  </div>
</template>

<script setup>
import AppSearchField from '@/components/common/AppSearchField.vue'

defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  showSearch: {
    type: Boolean,
    default: true,
  },
  searchValue: {
    type: String,
    default: '',
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...',
  },
  emptyText: {
    type: String,
    default: 'No records found',
  },
  minItemWidth: {
    type: String,
    default: '320px',
  },
  gap: {
    type: String,
    default: '0.85rem',
  },
})

defineEmits(['update:searchValue'])
</script>

<style scoped>
.app-entity-grid {
  border: 1px solid #eef0f7;
  border-radius: 16px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
}

.app-entity-grid__toolbar {
  padding: 0.75rem 0.9rem;
  border-bottom: 1px solid #f1f5f9;
}

.app-entity-grid__content {
  padding: 0.9rem;
  display: grid;
  gap: var(--entity-grid-gap, 0.85rem);
  grid-template-columns: repeat(auto-fill, minmax(min(100%, var(--entity-grid-min, 320px)), 1fr));
}
</style>
