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
      <div class="d-grid gap-4">
        <AppInputField
          v-model="form.email"
          autocomplete="email"
          :disabled="isLoading"
          :error="errors.email"
          :focused="focusedField === 'email'"
          icon="bi bi-envelope"
          input-id="login-email"
          input-type="email"
          :label="t('pages.login.emailLabel')"
          :placeholder="t('pages.login.emailPlaceholder')"
          @blur="handleFieldBlur('email')"
          @focus="setFocusedField('email')"
        />

        <AppInputField
          v-model="form.password"
          autocomplete="current-password"
          :disabled="isLoading"
          :error="errors.password"
          :focused="focusedField === 'password'"
          icon="bi bi-lock"
          input-id="login-password"
          :input-type="showPassword ? 'text' : 'password'"
          :label="t('pages.login.passwordLabel')"
          :placeholder="t('pages.login.passwordPlaceholder')"
          revealable
          :revealed="showPassword"
          @blur="handleFieldBlur('password')"
          @focus="setFocusedField('password')"
          @toggle-visibility="togglePassword"
        />
      </div>

      <div class="text-end mb-4">
        <a href="#" class="forgot-link" @click.prevent="goToForgotPassword">
          {{ t('pages.login.forgotPassword') }}
        </a>
      </div>

      <AppButton
        block
        class="mb-4"
        :disabled="isLoading"
        icon="bi bi-box-arrow-in-right"
        :loading="isLoginLoading"
        :loading-label="t('pages.login.verifying')"
        type="submit"
      >
        {{ t('pages.login.login') }}
      </AppButton>
    </form>

    <div class="divider mb-4">
      <span>{{ t('pages.login.quickDemo') }}</span>
    </div>

    <div class="demo-roles">
      <DemoRoleButton
        v-for="role in demoRoles"
        :key="role.value"
        :disabled="isLoading"
        :loading="isRoleLoading(role.value)"
        :role="role"
        @select="loginAsDemoRole"
      />
    </div>

    <p class="login-footer mt-5">{{ t('pages.login.footer') }}</p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import AppButton from '@/components/common/AppButton.vue'
import AppInputField from '@/components/common/AppInputField.vue'
import { useLoginForm } from '@/features/auth/model/composables/useLoginForm'
import { useLoginPageContent } from '@/features/auth/model/composables/useLoginPageContent'
import DemoRoleButton from './DemoRoleButton.vue'
import LocaleSwitcher from './LocaleSwitcher.vue'

const { t } = useI18n()
const router = useRouter()
const { demoRoles } = useLoginPageContent()
const {
  form,
  errors,
  focusedField,
  showPassword,
  isLoading,
  isLoginLoading,
  handleLogin,
  loginAsDemoRole,
  isRoleLoading,
  setFocusedField,
  handleFieldBlur,
  togglePassword,
} = useLoginForm()

function goToForgotPassword() {
  router.push({ name: 'forgot-password-email' })
}
</script>
