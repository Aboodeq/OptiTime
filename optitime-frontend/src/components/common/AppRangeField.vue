<template>
  <div class="range-field">
    <span class="range-field__value" :style="valueStyle">{{ modelValue }}{{ unit }}</span>
    <input
      class="range-field__slider"
      type="range"
      :min="min"
      :max="max"
      :step="step"
      :value="modelValue"
      @input="$emit('update:modelValue', Number($event.target.value))"
    />
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Number, default: 0 },
  min: { type: Number, default: 0 },
  max: { type: Number, default: 100 },
  step: { type: Number, default: 1 },
  unit: { type: String, default: '%' },
  accentColor: { type: String, default: '#2aa8ca' },
  valueColor: { type: String, default: '#0f9ac2' },
})

defineEmits(['update:modelValue'])

const valueStyle = {
  color: props.valueColor,
}
</script>

<style scoped>
.range-field {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.55rem;
  align-items: center;
}

.range-field__value {
  font-size: 0.85rem;
  font-weight: 800;
}

.range-field__slider {
  width: 100%;
  accent-color: v-bind('props.accentColor');
}
</style>
