<template>
  <div class="app-search-field" :class="{ 'is-compact': compact }">
    <i class="bi bi-search app-search-field__icon"></i>
    <input
      type="search"
      class="app-search-field__input"
      :placeholder="placeholder"
      :value="modelValue"
      :disabled="disabled"
      @input="$emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Search...' },
  disabled: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
.app-search-field {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #f7f8fc;
  border: 1.5px solid #eef0f7;
  border-radius: 11px;
  padding: 7px 12px;
  width: 100%;
}

.app-search-field.is-compact {
  padding: 6px 10px;
}

.app-search-field__icon {
  color: #8090a8;
  font-size: 0.85rem;
}

.app-search-field__input {
  background: none;
  border: none;
  outline: none;
  width: 100%;
  min-width: 0;
  font-size: 13px;
}
</style>
