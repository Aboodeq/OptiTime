<template>
  <div class="app-data-table">
    <div v-if="showSearch" class="app-data-table__toolbar">
      <AppSearchField
        compact
        :placeholder="searchPlaceholder"
        :model-value="searchValue"
        @update:model-value="$emit('update:searchValue', $event)"
      />
    </div>

    <div class="table-responsive">
      <table class="table align-middle mb-0 app-data-table__table">
        <thead>
          <tr>
            <th v-for="column in columns" :key="column.key" :class="['text-start', column.headerClass]">
              {{ column.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="rows.length === 0">
            <td :colspan="columns.length" class="text-center text-secondary py-4">
              {{ emptyText }}
            </td>
          </tr>
          <tr v-for="row in rows" :key="row[rowKey]">
            <td
              v-for="column in columns"
              :key="`${row[rowKey]}-${column.key}`"
              :class="['text-start', column.cellClass]"
            >
              <slot :name="`cell-${column.key}`" :row="row">
                {{ row[column.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import AppSearchField from '@/components/common/AppSearchField.vue'

defineProps({
  columns: {
    type: Array,
    required: true,
  },
  rows: {
    type: Array,
    default: () => [],
  },
  rowKey: {
    type: String,
    default: 'id',
  },
  emptyText: {
    type: String,
    default: 'No records found',
  },
  showSearch: {
    type: Boolean,
    default: false,
  },
  searchValue: {
    type: String,
    default: '',
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...',
  },
})

defineEmits(['update:searchValue'])
</script>

<style scoped>
.app-data-table__toolbar {
  padding: 0.75rem 0.9rem;
  border-bottom: 1px solid #f1f5f9;
}

</style>
