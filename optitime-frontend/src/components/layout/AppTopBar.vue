<template>
  <header class="topbar">
    <div class="page-meta">
      <span class="page-ttl">{{ pageTitle }}</span>
      <div class="breadcrumb-custom">
        <span>{{ t('common.appName') }}</span>
        <i :class="crumbIcon"></i>
        <span class="bc-active">{{ pageTitle }}</span>
      </div>
    </div>

    <div class="topbar-actions">
      <button
        v-if="isMobile"
        type="button"
        class="action-btn"
        :aria-label="t('nav.sidebar.open')"
        :title="t('nav.sidebar.open')"
        @click="$emit('open-sidebar')"
      >
        <i class="bi bi-list"></i>
      </button>

      <div class="search-bar">
        <i class="bi bi-search"></i>
        <input type="text" :placeholder="t('nav.topbar.quickSearch')" />
      </div>

      <button
        class="lang-btn"
        type="button"
        :title="t('nav.topbar.languageToggle')"
        @click="toggleLocale"
      >
        <i class="bi bi-translate"></i>
        <span class="lang-code">{{ uiStore.locale.toUpperCase() }}</span>
      </button>

      <div class="av-btn" :style="{ background: roleColor }">{{ userInitial }}</div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUiStore } from '@/store/ui.store'

defineProps({
  pageTitle: { type: String, required: true },
  roleColor: { type: String, default: '#334155' },
  userInitial: { type: String, default: '?' },
  isMobile: { type: Boolean, default: false },
})

defineEmits(['open-sidebar'])

const { t } = useI18n()
const uiStore = useUiStore()
const crumbIcon = computed(() => (uiStore.isRtl ? 'bi bi-chevron-left' : 'bi bi-chevron-right'))

function toggleLocale() {
  uiStore.setLocale(uiStore.locale === 'ar' ? 'en' : 'ar')
}
</script>

<style scoped>
.topbar { width:100%; background:#fff; border:1px solid #eef0f7; border-radius:14px; display:flex; align-items:center; justify-content:space-between; padding:10px 14px; margin-bottom:12px; gap:12px; }
.page-ttl { font-size:15px; font-weight:800; color:#1e2a3a; line-height:1; }
.breadcrumb-custom { display:flex; align-items:center; gap:5px; margin-top:3px; font-size:11px; color:#b0b8cc; }
.bc-active { color:#4361ee; font-weight:600; }
.topbar-actions { display:flex; align-items:center; gap:10px; flex:1; justify-content:flex-end; min-width:0; }
.search-bar { display:flex; align-items:center; gap:8px; background:#f7f8fc; border:1.5px solid #eef0f7; border-radius:11px; padding:7px 12px; min-width:160px; max-width:420px; width:100%; }
.search-bar input { background:none; border:none; outline:none; width:100%; font-size:13px; min-width:0; }
.action-btn { width:38px; height:38px; border-radius:11px; background:#f7f8fc; border:1.5px solid #eef0f7; color:#8090a8; }
.lang-btn { height:38px; border-radius:11px; background:#f7f8fc; border:1.5px solid #eef0f7; display:inline-flex; align-items:center; gap:8px; padding:0 10px; font-weight:800; color:#6b7280; }
.lang-code { font-size:12px; letter-spacing:0.4px; }
.av-btn { width:38px; height:38px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; color:#fff; }
@media (max-width: 992px) {
  .topbar { padding: 8px 10px; }
  .page-meta { display: none; }
  .topbar-actions { width: 100%; justify-content: flex-start; }
  .search-bar { min-width: 0; flex: 1; max-width: none; }
}
</style>
