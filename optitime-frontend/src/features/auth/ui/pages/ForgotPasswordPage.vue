<template>
  <div class="login-wrapper">
    <LoginMarketingPanel />

    <div class="login-right d-flex align-items-center justify-content-center">
      <div class="login-form-container">
        <LocaleSwitcher class="mb-4" />

        <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
          <div class="logo-icon-sm">
            <i class="bi bi-clock-history"></i>
          </div>
          <span class="fw-bold fs-5 mobile-brand-name">{{ t('common.appName') }}</span>
        </div>

        <div class="form-header mb-4">
          <h2 class="form-title">{{ t(stepContent.titleKey) }}</h2>
          <p class="form-subtitle">{{ t(stepContent.subtitleKey) }}</p>
        </div>

        <transition name="fade">
          <div v-if="errors.form" class="error-alert mb-4">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            {{ errors.form }}
          </div>
        </transition>

        <form @submit.prevent="submitCurrentStep">
          <div class="d-grid gap-4">
            <AppInputField
              v-if="step === 'email'"
              v-model="form.email"
              autocomplete="email"
              :disabled="isLoading"
              :error="errors.email"
              :focused="focusedField === 'email'"
              icon="bi bi-envelope"
              input-id="forgot-email"
              input-type="email"
              :label="t('pages.forgotPassword.email.label')"
              :placeholder="t('pages.forgotPassword.email.placeholder')"
              @blur="handleFieldBlur('email')"
              @focus="setFocusedField('email')"
            />

            <AppInputField
              v-if="step === 'code'"
              v-model="form.code"
              autocomplete="one-time-code"
              :disabled="isLoading"
              :error="errors.code"
              :focused="focusedField === 'code'"
              icon="bi bi-shield-lock"
              input-id="forgot-code"
              input-type="text"
              :label="t('pages.forgotPassword.code.label')"
              :placeholder="t('pages.forgotPassword.code.placeholder')"
              @blur="handleFieldBlur('code')"
              @focus="setFocusedField('code')"
            />

            <template v-if="step === 'reset'">
              <AppInputField
                v-model="form.password"
                autocomplete="new-password"
                :disabled="isLoading"
                :error="errors.password"
                :focused="focusedField === 'password'"
                icon="bi bi-lock"
                input-id="forgot-password"
                :input-type="showPassword ? 'text' : 'password'"
                :label="t('pages.forgotPassword.reset.passwordLabel')"
                :placeholder="t('pages.forgotPassword.reset.passwordPlaceholder')"
                revealable
                :revealed="showPassword"
                @blur="handleFieldBlur('password')"
                @focus="setFocusedField('password')"
                @toggle-visibility="togglePassword"
              />

              <AppInputField
                v-model="form.confirmPassword"
                autocomplete="new-password"
                :disabled="isLoading"
                :error="errors.confirmPassword"
                :focused="focusedField === 'confirmPassword'"
                icon="bi bi-shield-check"
                input-id="forgot-confirm-password"
                :input-type="showConfirmPassword ? 'text' : 'password'"
                :label="t('pages.forgotPassword.reset.confirmPasswordLabel')"
                :placeholder="t('pages.forgotPassword.reset.confirmPasswordPlaceholder')"
                revealable
                :revealed="showConfirmPassword"
                @blur="handleFieldBlur('confirmPassword')"
                @focus="setFocusedField('confirmPassword')"
                @toggle-visibility="toggleConfirmPassword"
              />
            </template>
          </div>

          <div v-if="step === 'code'" class="text-end mt-3 mb-4">
            <a href="#" class="forgot-link" @click.prevent="resendCode">
              {{
                isResendDisabled
                  ? t('pages.forgotPassword.code.resendDisabled', { seconds: remainingResendSeconds })
                  : t('pages.forgotPassword.code.resend')
              }}
            </a>
          </div>

          <div v-else class="mb-4"></div>

          <AppButton
            block
            class="mb-3"
            :disabled="isLoading"
            :icon="stepContent.buttonIcon"
            :loading="isLoading"
            :loading-label="t('pages.forgotPassword.actions.processing')"
            type="submit"
          >
            {{ t(stepContent.buttonLabelKey) }}
          </AppButton>
        </form>

        <AppButton block variant="outline" icon="bi bi-arrow-left" @click="goToLogin">
          {{ t('pages.forgotPassword.actions.backToLogin') }}
        </AppButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import AppButton from '@/components/common/AppButton.vue'
import AppInputField from '@/components/common/AppInputField.vue'
import { useForgotPasswordFlow } from '@/features/auth/model/composables/useForgotPasswordFlow'
import LocaleSwitcher from '@/features/auth/ui/components/LocaleSwitcher.vue'
import LoginMarketingPanel from '@/features/auth/ui/components/LoginMarketingPanel.vue'

const props = defineProps({
  step: {
    type: String,
    required: true,
  },
})

const { t } = useI18n()
const stepRef = toRef(props, 'step')
const {
  form,
  errors,
  focusedField,
  showPassword,
  showConfirmPassword,
  isLoading,
  isResendDisabled,
  remainingResendSeconds,
  setFocusedField,
  handleFieldBlur,
  togglePassword,
  toggleConfirmPassword,
  ensureStepAccess,
  submitCurrentStep,
  resendCode,
  goToLogin,
} = useForgotPasswordFlow(stepRef)
const stepContent = computed(() => {
  if (props.step === 'code') {
    return {
      titleKey: 'pages.forgotPassword.code.title',
      subtitleKey: 'pages.forgotPassword.code.subtitle',
      buttonLabelKey: 'pages.forgotPassword.actions.verifyCode',
      buttonIcon: 'bi bi-check2-circle',
    }
  }

  if (props.step === 'reset') {
    return {
      titleKey: 'pages.forgotPassword.reset.title',
      subtitleKey: 'pages.forgotPassword.reset.subtitle',
      buttonLabelKey: 'pages.forgotPassword.actions.resetPassword',
      buttonIcon: 'bi bi-shield-lock',
    }
  }

  return {
    titleKey: 'pages.forgotPassword.email.title',
    subtitleKey: 'pages.forgotPassword.email.subtitle',
    buttonLabelKey: 'pages.forgotPassword.actions.sendCode',
    buttonIcon: 'bi bi-send',
  }
})

onMounted(() => {
  ensureStepAccess()
})
</script>

<style src="../styles/login-page.css"></style>
