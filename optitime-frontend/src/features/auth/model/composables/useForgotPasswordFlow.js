import { computed, onUnmounted, reactive, ref, unref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import {
  createForgotPasswordCodeSchema,
  createForgotPasswordEmailSchema,
  createForgotPasswordResetSchema,
} from '@/features/auth/model/schemas/forgot-password.schema'
import { useAuthStore } from '@/store/auth.store'

const RESEND_COOLDOWN_SECONDS = 30

export function useForgotPasswordFlow(stepRef) {
  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  const { t } = useI18n()

  const form = reactive({
    email: authStore.passwordRecovery.email || '',
    code: authStore.passwordRecovery.code || '',
    password: '',
    confirmPassword: '',
  })

  const errors = reactive({
    email: '',
    code: '',
    password: '',
    confirmPassword: '',
    form: '',
  })

  const focusedField = ref('')
  const showPassword = ref(false)
  const showConfirmPassword = ref(false)
  const loadingMode = ref('')
  const codeSentAt = ref(authStore.passwordRecovery.codeSentAt || 0)
  const nowTimestamp = ref(Date.now())
  let timerId = null

  const isLoading = computed(() => Boolean(loadingMode.value))
  const isResendDisabled = computed(() => remainingResendSeconds.value > 0 || isLoading.value)
  const remainingResendSeconds = computed(() => {
    if (!codeSentAt.value) return 0
    const elapsedSeconds = Math.floor((nowTimestamp.value - codeSentAt.value) / 1000)
    return Math.max(0, RESEND_COOLDOWN_SECONDS - elapsedSeconds)
  })

  function startCountdownTicker() {
    if (typeof window === 'undefined') return
    if (timerId) return
    timerId = window.setInterval(() => {
      nowTimestamp.value = Date.now()
    }, 1000)
  }

  function stopCountdownTicker() {
    if (typeof window === 'undefined') return
    if (!timerId) return
    window.clearInterval(timerId)
    timerId = null
  }

  function setFocusedField(field) {
    focusedField.value = field
  }

  function clearFocusedField() {
    focusedField.value = ''
  }

  function togglePassword() {
    showPassword.value = !showPassword.value
  }

  function toggleConfirmPassword() {
    showConfirmPassword.value = !showConfirmPassword.value
  }

  function clearErrors() {
    errors.email = ''
    errors.code = ''
    errors.password = ''
    errors.confirmPassword = ''
    errors.form = ''
  }

  function validateEmailStep() {
    const result = createForgotPasswordEmailSchema(t).safeParse({ email: form.email })
    errors.email = ''
    if (result.success) return true
    errors.email = result.error.flatten().fieldErrors.email?.[0] ?? ''
    return false
  }

  function validateCodeStep() {
    const result = createForgotPasswordCodeSchema(t).safeParse({ code: form.code })
    errors.code = ''
    if (result.success) return true
    errors.code = result.error.flatten().fieldErrors.code?.[0] ?? ''
    return false
  }

  function validateResetStep() {
    const result = createForgotPasswordResetSchema(t).safeParse({
      password: form.password,
      confirmPassword: form.confirmPassword,
    })
    errors.password = ''
    errors.confirmPassword = ''
    if (result.success) return true
    const fieldErrors = result.error.flatten().fieldErrors
    errors.password = fieldErrors.password?.[0] ?? ''
    errors.confirmPassword = fieldErrors.confirmPassword?.[0] ?? ''
    return false
  }

  function validateCurrentStep() {
    const step = unref(stepRef)
    if (step === 'email') return validateEmailStep()
    if (step === 'code') return validateCodeStep()
    return validateResetStep()
  }

  function validateField(field) {
    if (field === 'email') return validateEmailStep()
    if (field === 'code') return validateCodeStep()
    return validateResetStep()
  }

  async function ensureStepAccess() {
    const step = unref(stepRef)
    const hasEmail = Boolean(authStore.passwordRecovery.email)
    const isCodeVerified = authStore.passwordRecovery.isCodeVerified

    if (step === 'email') return true
    if (step === 'code' && !hasEmail) {
      await router.replace({ name: 'forgot-password-email' })
      return false
    }
    if (step === 'reset' && (!hasEmail || !isCodeVerified)) {
      await router.replace({ name: hasEmail ? 'forgot-password-code' : 'forgot-password-email' })
      return false
    }
    return true
  }

  async function submitCurrentStep() {
    const step = unref(stepRef)
    clearErrors()
    if (!validateCurrentStep()) return false

    loadingMode.value = step
    try {
      if (step === 'email') {
        await authStore.requestPasswordReset(form.email)
        codeSentAt.value = authStore.passwordRecovery.codeSentAt || Date.now()
        nowTimestamp.value = Date.now()
        toast.success(t('pages.forgotPassword.toasts.codeSent'))
        await router.push({ name: 'forgot-password-code' })
        return true
      }

      if (step === 'code') {
        await authStore.verifyPasswordResetCode(form.code)
        toast.success(t('pages.forgotPassword.toasts.codeVerified'))
        await router.push({ name: 'forgot-password-reset' })
        return true
      }

      await authStore.resetPassword(form.password)
      toast.success(t('pages.forgotPassword.toasts.passwordResetSuccess'))
      await router.push({ name: 'login' })
      return true
    } catch {
      errors.form = t('pages.forgotPassword.errors.generic')
      toast.error(errors.form)
      return false
    } finally {
      loadingMode.value = ''
    }
  }

  async function resendCode() {
    if (isResendDisabled.value) return false

    loadingMode.value = 'resend'
    try {
      await authStore.resendPasswordResetCode()
      codeSentAt.value = authStore.passwordRecovery.codeSentAt || Date.now()
      nowTimestamp.value = Date.now()
      toast.success(t('pages.forgotPassword.toasts.codeResent'))
      return true
    } catch {
      errors.form = t('pages.forgotPassword.errors.generic')
      toast.error(errors.form)
      return false
    } finally {
      loadingMode.value = ''
    }
  }

  function goToLogin() {
    authStore.clearPasswordRecovery()
    router.push({ name: 'login' })
  }

  function handleFieldBlur(field) {
    clearFocusedField()
    validateField(field)
  }

  watch(
    () => form.email,
    () => {
      if (errors.email) validateEmailStep()
      if (errors.form) errors.form = ''
    },
  )

  watch(
    remainingResendSeconds,
    (seconds) => {
      if (seconds > 0) {
        startCountdownTicker()
      } else {
        stopCountdownTicker()
      }
    },
    { immediate: true },
  )

  onUnmounted(() => {
    stopCountdownTicker()
  })

  watch(
    () => form.code,
    () => {
      if (errors.code) validateCodeStep()
      if (errors.form) errors.form = ''
    },
  )

  watch(
    () => [form.password, form.confirmPassword],
    () => {
      if (errors.password || errors.confirmPassword) validateResetStep()
      if (errors.form) errors.form = ''
    },
  )

  return {
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
  }
}
