import { computed, reactive, ref, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { createLoginSchema } from '@/features/auth/model/schemas/login.schema'
import { useAuthStore } from '@/store/auth.store'

export function useLoginForm() {
  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  const authStore = useAuthStore()
  const { t } = useI18n()

  const form = reactive({
    email: '',
    password: '',
  })

  const errors = reactive({
    email: '',
    password: '',
    form: '',
  })

  const focusedField = ref('')
  const showPassword = ref(false)
  const loadingMode = ref('')

  const isLoading = computed(() => Boolean(loadingMode.value))
  const isLoginLoading = computed(() => loadingMode.value === 'login')
  const redirectTarget = computed(() => {
    const redirect = route.query.redirect
    return typeof redirect === 'string' && redirect.startsWith('/') && redirect !== '/login'
      ? redirect
      : '/dashboard'
  })

  function clearErrors() {
    errors.email = ''
    errors.password = ''
    errors.form = ''
  }

  function validate() {
    const schema = createLoginSchema(t)
    const result = schema.safeParse(form)

    errors.email = ''
    errors.password = ''

    if (result.success) return true

    const fieldErrors = result.error.flatten().fieldErrors
    errors.email = fieldErrors.email?.[0] ?? ''
    errors.password = fieldErrors.password?.[0] ?? ''
    return false
  }

  function validateField(field) {
    const schema = createLoginSchema(t)
    const result = schema.safeParse(form)

    if (result.success) {
      errors[field] = ''
      return true
    }

    const fieldErrors = result.error.flatten().fieldErrors
    errors[field] = fieldErrors[field]?.[0] ?? ''
    return !errors[field]
  }

  async function redirectAfterLogin() {
    await router.push(redirectTarget.value)
  }

  async function runAuthAction(mode, action) {
    loadingMode.value = mode

    try {
      await action()
      toast.success(t('pages.login.toasts.success'))
      await redirectAfterLogin()
      return true
    } catch {
      errors.form = t('pages.login.errors.invalidCredentials')
      toast.error(t('pages.login.toasts.invalidCredentials'))
      return false
    } finally {
      loadingMode.value = ''
    }
  }

  async function handleLogin() {
    errors.form = ''

    if (!validate()) {
      return false
    }

    return runAuthAction('login', () =>
      authStore.login({
        email: form.email.trim(),
        password: form.password,
      }),
    )
  }

  async function loginAsDemoRole(role) {
    clearErrors()
    return runAuthAction(role, () => authStore.loginAsDemoRole(role))
  }

  function setFocusedField(field) {
    focusedField.value = field
  }

  function clearFocusedField() {
    focusedField.value = ''
  }

  function handleFieldBlur(field) {
    clearFocusedField()
    validateField(field)
  }

  function togglePassword() {
    showPassword.value = !showPassword.value
  }

  function isRoleLoading(role) {
    return loadingMode.value === role
  }

  watch(
    () => form.email,
    () => {
      if (errors.email) validateField('email')
      if (errors.form) errors.form = ''
    },
  )

  watch(
    () => form.password,
    () => {
      if (errors.password) validateField('password')
      if (errors.form) errors.form = ''
    },
  )

  return {
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
  }
}
