<template>
  <div class="app-image-upload-field">
    <label class="app-image-upload-field__label">{{ label }}</label>

    <div
      class="app-image-upload-field__dropzone"
      :class="{ 'is-dragging': isDragging, 'is-disabled': disabled }"
      role="button"
      tabindex="0"
      @dragover.prevent="onDragOver"
      @dragleave="onDragLeave"
      @drop.prevent="onDrop"
      @click="openPicker"
      @keydown.enter.prevent="openPicker"
      @keydown.space.prevent="openPicker"
    >
      <input
        ref="fileInputRef"
        class="d-none"
        type="file"
        accept="image/png,image/svg+xml"
        :disabled="disabled"
        @change="onFileInputChange"
      />

      <div v-if="modelValue" class="app-image-upload-field__preview-wrap">
        <img :src="modelValue" :alt="label" class="app-image-upload-field__preview" />
      </div>
      <div v-else class="app-image-upload-field__placeholder">
        <i class="bi bi-cloud-arrow-up"></i>
      </div>

      <div class="app-image-upload-field__text">
        <strong>{{ hint }}</strong>
        <small>{{ acceptedText }}</small>
      </div>
    </div>

    <div class="d-flex justify-content-between mt-2">
      <small class="text-secondary">{{ acceptedText }}</small>
      <button
        v-if="modelValue"
        class="btn btn-sm btn-outline-secondary"
        type="button"
        :disabled="disabled"
        @click="$emit('update:modelValue', '')"
      >
        {{ clearText }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, required: true },
  hint: { type: String, default: 'Upload icon or drop it here' },
  acceptedText: { type: String, default: 'SVG, PNG only' },
  clearText: { type: String, default: 'Remove' },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
const fileInputRef = ref(null)
const isDragging = ref(false)

function openPicker() {
  if (props.disabled) return
  fileInputRef.value?.click()
}

function onDragOver() {
  if (props.disabled) return
  isDragging.value = true
}

function onDragLeave() {
  isDragging.value = false
}

function onDrop(event) {
  isDragging.value = false
  if (props.disabled) return
  const file = event.dataTransfer?.files?.[0]
  if (!file) return
  handleFile(file)
}

function onFileInputChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  handleFile(file)
  event.target.value = ''
}

function handleFile(file) {
  const isSupported = ['image/png', 'image/svg+xml'].includes(file.type)
  if (!isSupported) return

  const reader = new FileReader()
  reader.onload = () => {
    emit('update:modelValue', typeof reader.result === 'string' ? reader.result : '')
  }
  reader.readAsDataURL(file)
}
</script>

<style scoped>
.app-image-upload-field__label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  display: block;
}

.app-image-upload-field__dropzone {
  border: 1.5px dashed #cbd5e1;
  border-radius: 12px;
  background: #f8fafc;
  min-height: 128px;
  padding: 0.75rem;
  display: grid;
  place-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.app-image-upload-field__dropzone:hover,
.app-image-upload-field__dropzone.is-dragging {
  border-color: #4361ee;
  background: #eef2ff;
}

.app-image-upload-field__dropzone.is-disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.app-image-upload-field__placeholder i {
  font-size: 1.2rem;
  color: #64748b;
}

.app-image-upload-field__preview-wrap {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  background: #fff;
}

.app-image-upload-field__preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.app-image-upload-field__text {
  text-align: center;
  display: grid;
  gap: 2px;
}

.app-image-upload-field__text strong {
  font-size: 0.85rem;
  color: #1f2937;
}

.app-image-upload-field__text small {
  font-size: 0.75rem;
  color: #64748b;
}
</style>
