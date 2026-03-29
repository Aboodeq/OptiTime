import { reactive, ref } from 'vue'
import { DEMO_CREDENTIALS } from '@/services/auth.service'
import { useAuthStore } from '@/stores/auth.store'
import { loginSchema } from '../schemas/login.schema'

function getFieldErrors(form) {
  const result = loginSchema.safeParse(form)

  if (result.success) {
    return {
      valid: true,
      errors: {
        email: '',
        password: '',
      },
    }
  }

  const fieldErrors = result.error.flatten().fieldErrors

  return {
    valid: false,
    errors: {
      email: fieldErrors.email?.[0] ?? '',
      password: fieldErrors.password?.[0] ?? '',
    },
  }
}

export function useLoginForm(options = {}) {
  const authStore = useAuthStore()
  const form = reactive({
    email: '',
    password: '',
  })
  const touched = reactive({
    email: false,
    password: false,
  })
  const errors = reactive({
    email: '',
    password: '',
    form: '',
  })
  const isSubmitting = ref(false)

  function updateFieldError(field) {
    const { errors: nextErrors } = getFieldErrors(form)
    errors[field] = nextErrors[field]
  }

  function validateForm() {
    const { valid, errors: nextErrors } = getFieldErrors(form)
    errors.email = nextErrors.email
    errors.password = nextErrors.password
    return valid
  }

  function setFieldValue(field, value) {
    form[field] = value
    errors.form = ''

    if (touched[field]) {
      updateFieldError(field)
    }
  }

  function touchField(field) {
    touched[field] = true
    updateFieldError(field)
  }

  async function submit() {
    touched.email = true
    touched.password = true
    errors.form = ''

    if (!validateForm()) {
      return false
    }

    isSubmitting.value = true

    try {
      const user = await authStore.login({
        email: form.email.trim(),
        password: form.password,
      })

      await options.onSuccess?.(user)
      return true
    } catch (error) {
      errors.form = error?.message || 'Invalid credentials'
      await options.onError?.(error)
      return false
    } finally {
      isSubmitting.value = false
    }
  }

  async function loginWithDemo() {
    setFieldValue('email', DEMO_CREDENTIALS.email)
    setFieldValue('password', DEMO_CREDENTIALS.password)
    return submit()
  }

  return {
    form,
    errors,
    isSubmitting,
    submit,
    loginWithDemo,
    setFieldValue,
    touchField,
    demoCredentials: DEMO_CREDENTIALS,
  }
}
