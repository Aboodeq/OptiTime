<template>
  <main class="page-shell d-flex align-items-center">
    <div class="container py-5">
      <section class="dashboard-card p-4 p-lg-5">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 mb-4">
          <div>
            <span class="brand-kicker mb-3">
              <span class="brand-dot"></span>
              Dashboard
            </span>
            <h1 class="h2 fw-bold mb-2">Welcome back, {{ user?.name }}</h1>
            <p class="text-soft mb-0">
              Your authenticated route guard, persisted session, and mock auth flow are all active.
            </p>
          </div>

          <div class="d-flex align-items-start">
            <BaseButton variant="secondary" @click="handleLogout">Logout</BaseButton>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="stat-chip h-100">
              <span class="text-uppercase text-soft small fw-semibold">Account</span>
              <strong class="mt-2">{{ user?.email }}</strong>
              <span class="text-soft">Authenticated demo user</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-chip h-100">
              <span class="text-uppercase text-soft small fw-semibold">Guard Status</span>
              <strong class="mt-2">Protected</strong>
              <span class="text-soft">Unauthenticated visitors are redirected to login</span>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-chip h-100">
              <span class="text-uppercase text-soft small fw-semibold">Persistence</span>
              <strong class="mt-2">Enabled</strong>
              <span class="text-soft">Refreshing the page keeps the session in localStorage</span>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-7">
            <div class="border rounded-4 p-4 h-100 bg-white bg-opacity-75">
              <h2 class="h5 fw-bold mb-3">Next implementation areas</h2>
              <p class="text-soft mb-0">
                This placeholder dashboard is intentionally lean so the project can grow from a
                clean foundation instead of carrying forward the complexity of the legacy copy app.
              </p>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="border rounded-4 p-4 h-100 bg-white bg-opacity-75">
              <h2 class="h5 fw-bold mb-3">What is wired already</h2>
              <ul class="mb-0 ps-3 text-soft">
                <li>Feature-first folder structure</li>
                <li>Reusable form primitives</li>
                <li>Zod validation with inline errors</li>
                <li>Toast-driven auth feedback</li>
              </ul>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>
</template>

<script setup>
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/base/BaseButton.vue'
import { useAuthStore } from '@/stores/auth.store'

const router = useRouter()
const authStore = useAuthStore()
const { user } = storeToRefs(authStore)

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>
