<template>
  <main class="page-shell d-flex align-items-center">
    <div class="container py-4 py-lg-5">
      <div class="row justify-content-center align-items-center g-4 g-xl-5">
        <div class="col-lg-6">
          <section class="page-panel glass-card p-4 p-lg-5 h-100">
            <span class="brand-kicker mb-4">
              <span class="brand-dot"></span>
              {{ appName }}
            </span>
            <h1 class="page-title fw-bold mb-3">
              Clean scheduling starts with a calm sign-in flow.
            </h1>
            <p class="text-soft fs-5 mb-4">
              This rebuild keeps the login experience sharp and predictable while setting up the
              project for scalable feature development.
            </p>

            <div class="row g-3">
              <div class="col-sm-6">
                <div class="stat-chip h-100">
                  <span class="text-uppercase text-soft small fw-semibold">Validation</span>
                  <strong class="mt-2">Zod-backed</strong>
                  <span class="text-soft">Inline field feedback before the request fires</span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="stat-chip h-100">
                  <span class="text-uppercase text-soft small fw-semibold">Auth Mode</span>
                  <strong class="mt-2">Mock service</strong>
                  <span class="text-soft">Predictable demo credentials and realistic delay</span>
                </div>
              </div>
            </div>
          </section>
        </div>

        <div class="col-lg-5">
          <section class="page-panel glass-card p-4 p-lg-5">
            <div class="mb-4">
              <p class="text-uppercase small fw-semibold text-primary mb-2">Sign in</p>
              <h2 class="h3 fw-bold mb-2">Access your OptiTime workspace</h2>
              <p class="text-soft mb-0">
                Use the demo account or try invalid credentials to test the full feedback loop.
              </p>
            </div>

            <div v-if="errors.form" class="alert alert-danger rounded-4 mb-4" role="alert">
              {{ errors.form }}
            </div>

            <form novalidate @submit.prevent="handleLogin">
              <div class="d-grid gap-3">
                <FormField label="Email" for-id="email" :error="errors.email">
                  <BaseInput
                    id="email"
                    :model-value="form.email"
                    type="email"
                    autocomplete="email"
                    placeholder="name@company.com"
                    :disabled="isSubmitting"
                    :invalid="Boolean(errors.email)"
                    @update:model-value="setFieldValue('email', $event)"
                    @blur="touchField('email')"
                  />
                </FormField>

                <FormField label="Password" for-id="password" :error="errors.password">
                  <BaseInput
                    id="password"
                    :model-value="form.password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    :disabled="isSubmitting"
                    :invalid="Boolean(errors.password)"
                    @update:model-value="setFieldValue('password', $event)"
                    @blur="touchField('password')"
                  />
                </FormField>
              </div>

              <div class="d-grid gap-3 mt-4">
                <BaseButton
                  type="submit"
                  :loading="activeAction === 'login'"
                  :disabled="isSubmitting"
                  block
                >
                  {{ activeAction === 'login' ? 'Signing in...' : 'Login' }}
                </BaseButton>
                <BaseButton
                  variant="secondary"
                  :loading="activeAction === 'demo'"
                  :disabled="isSubmitting"
                  block
                  @click="handleDemoLogin"
                >
                  {{ activeAction === 'demo' ? 'Preparing demo...' : 'Quick Demo Login' }}
                </BaseButton>
              </div>
            </form>

            <div class="border rounded-4 p-3 mt-4 bg-white bg-opacity-75">
              <p class="fw-semibold mb-2">Demo credentials</p>
              <p class="text-soft mb-1"><strong>Email:</strong> {{ demoCredentials.email }}</p>
              <p class="text-soft mb-0">
                <strong>Password:</strong> {{ demoCredentials.password }}
              </p>
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import FormField from '@/components/base/FormField.vue'
import { useLoginForm } from '@/features/auth/composables/useLoginForm'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const activeAction = ref('')
const appName = import.meta.env.VITE_APP_NAME || 'OptiTime'

const redirectTarget = computed(() => {
  const redirect = route.query.redirect
  return typeof redirect === 'string' && redirect.startsWith('/') && redirect !== '/login'
    ? redirect
    : '/dashboard'
})

const {
  form,
  errors,
  isSubmitting,
  submit,
  loginWithDemo,
  setFieldValue,
  touchField,
  demoCredentials,
} = useLoginForm({
  onSuccess: async () => {
    toast.success('Login successful')
    await router.push(redirectTarget.value)
  },
  onError: () => {
    toast.error('Invalid credentials')
  },
})

async function handleLogin() {
  activeAction.value = 'login'
  await submit()
  activeAction.value = ''
}

async function handleDemoLogin() {
  activeAction.value = 'demo'
  await loginWithDemo()
  activeAction.value = ''
}
</script>
