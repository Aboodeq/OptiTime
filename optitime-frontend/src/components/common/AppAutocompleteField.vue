<template>
  <div class="app-autocomplete-field">
    <label :for="inputId" class="app-autocomplete-field__label">{{ label }}</label>
    <div class="app-autocomplete-field__wrapper">
      <i v-if="icon" :class="[icon, 'app-autocomplete-field__icon']"></i>
      <input
        :id="inputId"
        type="text"
        class="app-autocomplete-field__input"
        :placeholder="placeholder"
        :value="searchValue"
        :disabled="disabled"
        @focus="isOpen = true"
        @input="onInput"
        @blur="onBlur"
      />
    </div>

    <div v-if="isOpen && !disabled" class="app-autocomplete-field__menu">
      <button
        v-for="option in filteredOptions"
        :key="option.value"
        type="button"
        class="app-autocomplete-field__option"
        @mousedown.prevent="selectOption(option)"
      >
        <span
          v-for="(part, index) in getHighlightedParts(option.label)"
          :key="`${option.value}-${index}`"
          :class="{ 'app-autocomplete-field__match': part.match }"
        >
          {{ part.text }}
        </span>
      </button>
      <button
        v-if="allowCreate && canCreate"
        type="button"
        class="app-autocomplete-field__create"
        @mousedown.prevent="createOption"
      >
        + {{ createText }}
      </button>
      <div v-if="filteredOptions.length === 0 && !(allowCreate && canCreate)" class="app-autocomplete-field__empty">
        {{ emptyText }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
  inputId: { type: String, required: true },
  label: { type: String, required: true },
  modelValue: { type: String, default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: '' },
  icon: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  allowCreate: { type: Boolean, default: false },
  emptyText: { type: String, default: 'No options' },
})

const emit = defineEmits(['update:modelValue', 'create-option'])
const isOpen = ref(false)
const searchValue = ref('')

const normalizedOptions = computed(() =>
  props.options
    .filter((option) => option && typeof option.value === 'string' && typeof option.label === 'string')
    .map((option) => ({ value: option.value, label: option.label })),
)

const selectedOption = computed(() =>
  normalizedOptions.value.find((option) => option.value === props.modelValue),
)

const filteredOptions = computed(() => {
  const q = searchValue.value.trim().toLowerCase()
  if (!q) return normalizedOptions.value
  return normalizedOptions.value.filter((option) => option.label.toLowerCase().includes(q))
})

const canCreate = computed(() => {
  const q = searchValue.value.trim().toLowerCase()
  if (!q) return false
  return !normalizedOptions.value.some((option) => option.label.toLowerCase() === q)
})

const createText = computed(() => `Add "${searchValue.value.trim()}"`)

watch(
  () => props.modelValue,
  () => {
    searchValue.value = selectedOption.value?.label ?? ''
  },
  { immediate: true },
)

function onInput(event) {
  searchValue.value = event.target.value
  isOpen.value = true
}

function onBlur() {
  window.setTimeout(() => {
    isOpen.value = false
    searchValue.value = selectedOption.value?.label ?? ''
  }, 120)
}

function selectOption(option) {
  emit('update:modelValue', option.value)
  searchValue.value = option.label
  isOpen.value = false
}

function createOption() {
  const raw = searchValue.value.trim()
  if (!raw) return
  emit('create-option', raw)
}

function getHighlightedParts(label) {
  const q = searchValue.value.trim().toLowerCase()
  if (!q) return [{ text: label, match: false }]

  const normalized = label.toLowerCase()
  const parts = []
  let start = 0

  while (start < label.length) {
    const hitIndex = normalized.indexOf(q, start)
    if (hitIndex === -1) {
      parts.push({ text: label.slice(start), match: false })
      break
    }

    if (hitIndex > start) {
      parts.push({ text: label.slice(start, hitIndex), match: false })
    }

    parts.push({ text: label.slice(hitIndex, hitIndex + q.length), match: true })
    start = hitIndex + q.length
  }

  return parts.length > 0 ? parts : [{ text: label, match: false }]
}
</script>

<style scoped>
.app-autocomplete-field {
  position: relative;
}

.app-autocomplete-field__label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  display: block;
}

.app-autocomplete-field__wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  overflow: hidden;
  border: 1.5px solid #e5e7eb;
  border-radius: 12px;
  padding: 0 14px;
  background: #f9fafb;
}

.app-autocomplete-field__icon {
  color: #9ca3af;
  font-size: 15px;
  flex-shrink: 0;
}

.app-autocomplete-field__input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 13px 0;
  font-size: 14px;
  color: #1e2a3a;
  outline: none;
}

.app-autocomplete-field__menu {
  margin-top: 6px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
  max-height: 210px;
  overflow-y: auto;
}

.app-autocomplete-field__option,
.app-autocomplete-field__create {
  width: 100%;
  border: none;
  background: #fff;
  text-align: start;
  padding: 10px 12px;
}

.app-autocomplete-field__option:hover,
.app-autocomplete-field__create:hover {
  background: #f8fafc;
}

.app-autocomplete-field__create {
  color: #0f766e;
  font-weight: 600;
}

.app-autocomplete-field__match {
  background: rgba(245, 158, 11, 0.3);
  border-radius: 4px;
  padding: 0 1px;
}

.app-autocomplete-field__empty {
  padding: 10px 12px;
  color: #64748b;
  font-size: 13px;
}
</style>
