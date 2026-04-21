<template>
  <section v-if="items.length" class="dashboard-quick-access mb-4">
    <h2 class="h5 fw-semibold mb-3">{{ title }}</h2>
    <div class="row g-3">
      <div v-for="item in items" :key="item.id" class="col-sm-6 col-lg-4">
        <RouterLink
          :to="{ name: item.routeName }"
          class="dashboard-quick-access__tile d-flex flex-column p-4 rounded-4 border bg-light-subtle h-100 text-decoration-none text-body"
        >
          <i :class="[item.icon, 'dashboard-quick-access__icon mb-2']" aria-hidden="true" />
          <span class="fw-semibold">{{ t(item.labelKey) }}</span>
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

defineProps({
  title: {
    type: String,
    required: true,
  },
  items: {
    type: Array,
    required: true,
  },
})

const { t } = useI18n()
</script>

<style scoped>
.dashboard-quick-access__tile {
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease;
}

.dashboard-quick-access__tile:hover {
  border-color: var(--bs-primary, #0d6efd);
  box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.06);
}

.dashboard-quick-access__tile:focus-visible {
  outline: 2px solid var(--bs-primary, #0d6efd);
  outline-offset: 2px;
}

.dashboard-quick-access__icon {
  font-size: 1.5rem;
  color: var(--bs-primary, #0d6efd);
}
</style>
