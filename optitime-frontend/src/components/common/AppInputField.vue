<template>
  <div class="app-input-field">
    <label v-if="!hideLabel" :for="inputId" class="app-input-field__label">{{ label }}</label>
    <div
      class="app-input-field__wrapper"
      :class="{ 'is-focused': focused, 'has-value': modelValue, 'is-invalid': error }"
    >
      <i :class="[icon, 'app-input-field__icon']"></i>
      <input
        :id="inputId"
        class="app-input-field__control"
        :type="inputType"
        :value="modelValue"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :disabled="disabled"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="$emit('focus')"
        @blur="$emit('blur')"
      />
      <AppIconButton
        v-if="revealable"
        class="app-input-field__toggle"
        :icon="revealed ? 'bi bi-eye-slash' : 'bi bi-eye'"
        variant="neutral"
        size="sm"
        :disabled="disabled"
        @click="$emit('toggle-visibility')"
      />
    </div>
    <div v-if="error" class="app-input-field__error">{{ error }}</div>
  </div>
</template>

<script setup>
import AppIconButton from '@/components/common/AppIconButton.vue'

defineProps({
  inputId: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  hideLabel: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  autocomplete: {
    type: String,
    default: 'off',
  },
  inputType: {
    type: String,
    default: 'text',
  },
  icon: {
    type: String,
    required: true,
  },
  error: {
    type: String,
    default: '',
  },
  focused: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  revealable: {
    type: Boolean,
    default: false,
  },
  revealed: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['update:modelValue', 'focus', 'blur', 'toggle-visibility'])
</script>

<style scoped>
.app-input-field__label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  display: block;
}

.app-input-field__wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  overflow: hidden;
  border: 1.5px solid #e5e7eb;
  border-radius: 12px;
  padding: 0 14px;
  background: #f9fafb;
  transition: all 0.2s ease;
}

.app-input-field__wrapper.is-focused {
  border-color: #4361ee;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.app-input-field__wrapper.has-value {
  background: #fff;
  border-color: #d1d5db;
}

.app-input-field__wrapper.is-invalid {
  border-color: #e63946;
  background: #fff;
}

.app-input-field__icon {
  color: #9ca3af;
  font-size: 15px;
  flex-shrink: 0;
}

.app-input-field__wrapper.is-focused .app-input-field__icon {
  color: #4361ee;
}

.app-input-field__control {
  flex: 1;
  border: none;
  background: transparent;
  padding: 13px 0;
  font-size: 14px;
  color: #1e2a3a;
  outline: none;
  direction: inherit;
  text-align: start;
}

.app-input-field__control::placeholder {
  color: #c0c5cc;
}

.app-input-field__control[type='time']::-webkit-calendar-picker-indicator {
  display: none;
  -webkit-appearance: none;
}

.app-input-field__control[type='time'] {
  appearance: textfield;
}

.app-input-field__toggle {
  color: #9ca3af;
  padding: 0;
  border-color: transparent;
  background: transparent;
}

.app-input-field__toggle:hover:not(:disabled) {
  color: #4361ee;
  border-color: transparent;
  background: transparent;
}

.app-input-field__error {
  margin-top: 0.4rem;
  color: #e63946;
  font-size: 12px;
  font-weight: 600;
}

.app-input-field__control:-webkit-autofill,
.app-input-field__control:-webkit-autofill:hover,
.app-input-field__control:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0 1000px #fff inset;
  -webkit-text-fill-color: #1e2a3a;
  transition: background-color 5000s ease-in-out 0s;
}
</style>
