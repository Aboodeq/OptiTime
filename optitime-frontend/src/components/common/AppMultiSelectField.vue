<template>
  <div class="app-multi-select-field">
    <label :for="inputId" class="app-multi-select-field__label">{{ label }}</label>
    <div
      class="app-multi-select-field__wrapper"
      :class="{ 'app-multi-select-field__wrapper--disabled': disabled }"
    >
      <i v-if="icon" :class="[icon, 'app-multi-select-field__icon']"></i>
      <div v-if="selectedItems.length" class="app-multi-select-field__chips">
        <button
          v-for="item in selectedItems"
          :key="item.value"
          type="button"
          class="app-multi-select-field__chip"
          :disabled="disabled"
          @click="removeValue(item.value)"
        >
          <span>{{ item.label }}</span>
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <input
        :id="inputId"
        type="text"
        class="app-multi-select-field__input"
        :placeholder="placeholder"
        :value="searchValue"
        :disabled="disabled"
        @focus="isOpen = true"
        @input="onInput"
        @blur="onBlur"
      />
    </div>

    <div v-if="isOpen && !disabled" class="app-multi-select-field__menu">
      <AppButton
        v-for="option in filteredOptions"
        :key="option.value"
        type="button"
        variant="plain"
        class="app-multi-select-field__option"
        @mousedown.prevent="toggleOption(option.value)"
      >
        <span class="app-multi-select-field__option-main">
          <span
            v-for="(part, index) in getHighlightedParts(option.label)"
            :key="`${option.value}-${index}`"
            :class="{ 'app-multi-select-field__match': part.match }"
          >
            {{ part.text }}
          </span>
        </span>
        <i v-if="isSelected(option.value)" class="bi bi-check2 app-multi-select-field__check"></i>
      </AppButton>
      <AppButton
        v-if="allowCreate && canCreate"
        type="button"
        variant="plain"
        class="app-multi-select-field__create"
        @mousedown.prevent="createOption"
      >
        + {{ createText }}
      </AppButton>
      <div
        v-if="filteredOptions.length === 0 && !(allowCreate && canCreate)"
        class="app-multi-select-field__empty"
      >
        {{ emptyText }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  inputId: { type: String, required: true },
  label: { type: String, required: true },
  modelValue: { type: Array, default: () => [] },
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
    .filter(
      (option) => option && typeof option.value === 'string' && typeof option.label === 'string',
    )
    .map((option) => ({ value: option.value, label: option.label })),
)

const selectedSet = computed(() => new Set(props.modelValue.filter((item) => typeof item === 'string')))

const selectedItems = computed(() =>
  normalizedOptions.value.filter((option) => selectedSet.value.has(option.value)),
)

const filteredOptions = computed(() => {
  const q = searchValue.value.trim().toLowerCase()
  const remaining = normalizedOptions.value.filter((option) => !selectedSet.value.has(option.value))
  if (!q) return remaining
  return remaining.filter((option) => option.label.toLowerCase().includes(q))
})

const canCreate = computed(() => {
  const q = searchValue.value.trim().toLowerCase()
  if (!q) return false
  return !normalizedOptions.value.some((option) => option.label.toLowerCase() === q)
})

const createText = computed(() => `Add "${searchValue.value.trim()}"`)

function onInput(event) {
  searchValue.value = event.target.value
  isOpen.value = true
}

function onBlur() {
  window.setTimeout(() => {
    isOpen.value = false
    searchValue.value = ''
  }, 120)
}

function isSelected(value) {
  return selectedSet.value.has(value)
}

function toggleOption(value) {
  if (selectedSet.value.has(value)) {
    removeValue(value)
    return
  }
  emit('update:modelValue', [...selectedSet.value, value])
  searchValue.value = ''
}

function removeValue(value) {
  const next = [...selectedSet.value].filter((item) => item !== value)
  emit('update:modelValue', next)
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
.app-multi-select-field {
  position: relative;
}

.app-multi-select-field__label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  display: block;
}

.app-multi-select-field__wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  overflow: hidden;
  border: 1.5px solid #e5e7eb;
  border-radius: 12px;
  padding: 0 14px;
  background: #f9fafb;
  min-height: 46px;
}

.app-multi-select-field__wrapper--disabled {
  opacity: 0.8;
}

.app-multi-select-field__icon {
  color: #9ca3af;
  font-size: 15px;
  flex-shrink: 0;
}

.app-multi-select-field__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin: 8px 0;
}

.app-multi-select-field__chip {
  border: 1px solid #dbeafe;
  background: #eff6ff;
  color: #1e3a8a;
  border-radius: 999px;
  padding: 4px 8px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}

.app-multi-select-field__input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 13px 0;
  font-size: 14px;
  color: #1e2a3a;
  outline: none;
  min-width: 120px;
}

.app-multi-select-field__menu {
  margin-top: 6px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
  max-height: 210px;
  overflow-y: auto;
}

.app-multi-select-field__option,
.app-multi-select-field__create {
  width: 100%;
  border: none;
  background: #fff;
  text-align: start;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.app-multi-select-field__option:hover,
.app-multi-select-field__create:hover {
  background: #f8fafc;
}

.app-multi-select-field__option-main {
  flex: 1;
}

.app-multi-select-field__check {
  color: #0f766e;
  margin-inline-start: 8px;
}

.app-multi-select-field__create {
  color: #0f766e;
  font-weight: 600;
}

.app-multi-select-field__match {
  background: rgba(245, 158, 11, 0.3);
  border-radius: 4px;
  padding: 0 1px;
}

.app-multi-select-field__empty {
  padding: 10px 12px;
  color: #64748b;
  font-size: 13px;
}
</style>
