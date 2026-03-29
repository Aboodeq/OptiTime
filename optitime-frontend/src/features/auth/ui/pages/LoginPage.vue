<template>
  <div class="login-wrapper">
    <LoginMarketingPanel />

    <div class="login-right d-flex align-items-center justify-content-center">
      <LoginFormPanel />
    </div>
  </div>
</template>

<script setup>
import LoginFormPanel from '@/features/auth/ui/components/LoginFormPanel.vue'
import LoginMarketingPanel from '@/features/auth/ui/components/LoginMarketingPanel.vue'
</script>

<style src="../styles/login-page.css"></style>
