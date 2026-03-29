<template>
  <div class="login-form-container">
    <LocaleSwitcher class="mb-4" />

    <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
      <div class="logo-icon-sm">
        <i class="bi bi-clock-history"></i>
      </div>
      <span class="fw-bold fs-5 mobile-brand-name">{{ t('common.appName') }}</span>
    </div>

    <div class="form-header mb-4">
      <h2 class="form-title">{{ t('pages.login.welcomeTitle') }}</h2>
      <p class="form-subtitle">{{ t('pages.login.welcomeSubtitle') }}</p>
    </div>

    <transition name="fade">
      <div v-if="errors.form" class="error-alert mb-4">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        {{ errors.form }}
      </div>
    </transition>

    <form @submit.prevent="handleLogin">
      <div class="form-group-custom mb-4">
        <label class="form-label-custom">{{ t('pages.login.emailLabel') }}</label>
        <div
          class="input-wrapper"
          :class="{ focused: focusedField === 'email', filled: form.email, invalid: errors.email }"
        >
          <i class="bi bi-envelope input-icon"></i>
          <input
            v-model="form.email"
            type="email"
            class="custom-input"
            :placeholder="t('pages.login.emailPlaceholder')"
            :disabled="isLoading"
            @focus="setFocusedField('email')"
            @blur="handleFieldBlur('email')"
          />
        </div>
        <div v-if="errors.email" class="field-error">{{ errors.email }}</div>
      </div>

      <div class="form-group-custom mb-2">
        <label class="form-label-custom">{{ t('pages.login.passwordLabel') }}</label>
        <div
          class="input-wrapper"
          :class="{
            focused: focusedField === 'password',
            filled: form.password,
            invalid: errors.password,
          }"
        >
          <i class="bi bi-lock input-icon"></i>
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            class="custom-input"
            :placeholder="t('pages.login.passwordPlaceholder')"
            :disabled="isLoading"
            @focus="setFocusedField('password')"
            @blur="handleFieldBlur('password')"
          />
          <button
            class="toggle-password"
            type="button"
            :disabled="isLoading"
            @click="togglePassword"
          >
            <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
          </button>
        </div>
        <div v-if="errors.password" class="field-error">{{ errors.password }}</div>
      </div>

      <div class="text-end mb-4">
        <a href="#" class="forgot-link" @click.prevent>{{ t('pages.login.forgotPassword') }}</a>
      </div>

      <button class="btn-login w-100 mb-4" :disabled="isLoading" type="submit">
        <span
          v-if="loadingMode !== 'login'"
          class="d-flex align-items-center justify-content-center gap-2"
        >
          <i class="bi bi-box-arrow-in-right"></i>
          {{ t('pages.login.login') }}
        </span>
        <span v-else class="d-flex align-items-center justify-content-center gap-2">
          <span class="spinner-border spinner-border-sm"></span>
          {{ t('pages.login.verifying') }}
        </span>
      </button>
    </form>

    <div class="divider mb-4">
      <span>{{ t('pages.login.quickDemo') }}</span>
    </div>

    <div class="demo-roles">
      <button
        v-for="role in demoRoles"
        :key="role.value"
        class="role-btn"
        :style="{ '--role-color': role.color, '--role-bg': role.bg }"
        :disabled="isLoading"
        type="button"
        @click="loginAsDemoRole(role.value)"
      >
        <span
          v-if="loadingMode === role.value"
          class="spinner-border spinner-border-sm"
          aria-hidden="true"
        ></span>
        <i v-else :class="role.icon"></i>
        <span>{{ role.label }}</span>
      </button>
    </div>

    <p class="login-footer mt-5">{{ t('pages.login.footer') }}</p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useLoginForm } from '@/features/auth/model/composables/useLoginForm'
import { useLoginPageContent } from '@/features/auth/model/composables/useLoginPageContent'
import LocaleSwitcher from './LocaleSwitcher.vue'

const { t } = useI18n()
const { demoRoles } = useLoginPageContent()
const {
  form,
  errors,
  focusedField,
  showPassword,
  loadingMode,
  isLoading,
  handleLogin,
  loginAsDemoRole,
  setFocusedField,
  handleFieldBlur,
  togglePassword,
} = useLoginForm()
</script>
