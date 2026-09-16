<template>  
  <q-layout view="hHh LpR fFf" class="admin-layout" :class="{ 'admin-layout--dark': $q.dark.isActive }">
    <!-- PHONE HEADER -->
    <q-header v-if="$q.screen.lt.md" class="admin-header">
      <q-toolbar class="q-px-md toolbar-mobile">
        <button
          type="button"
          class="header-logo-card"
          :aria-label="t('goToDashboard')"
          @click="router.push('/admin/dashboard')"
        >
          <img
            :src="logo"
            alt=""
            aria-hidden="true"
            class="header-logo-img"
          />
        </button>

        <q-space />

        <!-- Language Toggle -->
        <q-btn
          flat
          round
          dense
          class="header-action-btn q-mr-xs"
          :aria-label="t('switchLanguage')"
          @click="toggleLanguage"
        >
          <span class="lang-code-indicator">{{ lang === 'en' ? 'EN' : 'PH' }}</span>
          <q-tooltip>{{ lang === 'en' ? 'Palitan sa Filipino' : 'Switch to English' }}</q-tooltip>
        </q-btn>

        <!-- Dark Mode Toggle -->
        <q-btn
          flat
          round
          dense
          :icon="$q.dark.isActive ? 'o_light_mode' : 'o_dark_mode'"
          class="header-action-btn q-mr-xs"
          :aria-label="t('toggleTheme')"
          @click="toggleDarkMode"
        >
          <q-tooltip>{{ $q.dark.isActive ? t('lightMode') : t('darkMode') }}</q-tooltip>
        </q-btn>

        <!-- Notifications Bell -->
        <q-btn
          flat
          round
          dense
          icon="o_notifications"
          class="header-action-btn"
          to="/admin/notifications"
          :aria-label="unreadCount ? `${t('notifications')}, ${unreadCount}` : t('notifications')"
        >
          <q-badge v-if="unreadCount" floating rounded class="header-badge">
            {{ unreadCount > 99 ? '99+' : unreadCount }}
          </q-badge>
        </q-btn>

        <!-- Logout -->
        <q-btn
          flat
          round
          dense
          icon="o_logout"
          class="header-action-btn"
          :aria-label="t('signOut')"
          @click="handleLogout"
        />
      </q-toolbar>
    </q-header>

    <!-- SIDEBAR -->
    <q-drawer
      v-model="drawerOpen"
      :show-if-above="!$q.screen.lt.md"
      :width="264"
      :breakpoint="1024"
      class="admin-sidebar"
    >
      <div class="sidebar-layout column full-height no-wrap">
        <div class="sidebar-top">
          <button
            type="button"
            class="sidebar-logo"
            :aria-label="t('goToDashboard')"
            @click="router.push('/admin/dashboard')"
          >
            <img
              :src="logo"
              alt=""
              aria-hidden="true"
              class="sidebar-logo-img"
            />
          </button>
        </div>

        <!-- System Controls: Dark Mode & Language Switcher in Drawer -->
        <div class="sidebar-utility-controls">
          <button
            type="button"
            class="sidebar-utility-chip"
            @click="toggleDarkMode"
          >
            <q-icon :name="$q.dark.isActive ? 'o_light_mode' : 'o_dark_mode'" size="16px" />
            <span>{{ $q.dark.isActive ? t('lightMode') : t('darkMode') }}</span>
          </button>

          <button
            type="button"
            class="sidebar-utility-chip"
            @click="toggleLanguage"
          >
            <q-icon name="o_translate" size="16px" />
            <span>{{ lang === 'en' ? 'English' : 'Filipino' }}</span>
          </button>
        </div>

        <!-- Navigation items -->
        <nav class="sidebar-links-container col scroll" aria-label="Admin menu">
          <div
            v-for="group in localizedNavGroups"
            :key="group.label"
            class="sidebar-group"
          >
            <div class="sidebar-category-header">{{ group.label }}</div>
            <q-list class="sidebar-group-list">
              <q-item
                v-for="item in group.items"
                :key="item.path"
                :to="item.path"
                clickable
                v-ripple
                active-class="nav-item--active"
                class="nav-item"
              >
                <q-item-section avatar class="nav-avatar-slot">
                  <q-icon :name="item.icon" size="20px" class="nav-icon" />
                </q-item-section>
                <q-item-section class="nav-label">
                  {{ item.label }}
                </q-item-section>
                <q-item-section v-if="item.unread && unreadCount" side>
                  <span class="nav-badge">
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                  </span>
                </q-item-section>
              </q-item>
            </q-list>
          </div>
        </nav>

        <div class="sidebar-footer-area">
          <div class="sidebar-account">
            <q-avatar size="36px" class="sidebar-account-avatar">
              <q-icon name="o_admin_panel_settings" size="20px" />
            </q-avatar>
            <div class="sidebar-account-text">
              <div class="sidebar-account-name">{{ adminName }}</div>
              <div class="sidebar-account-role">{{ t('administrator') }}</div>
            </div>
            <q-btn
              flat
              round
              dense
              icon="o_logout"
              class="sidebar-signout"
              :aria-label="t('signOut')"
              @click="handleLogout"
            >
              <q-tooltip>{{ t('signOut') }}</q-tooltip>
            </q-btn>
          </div>
        </div>
      </div>
    </q-drawer>

    <q-page-container :class="{ 'mobile-pb': $q.screen.lt.md }">
      <router-view />
    </q-page-container>

    <!-- BOTTOM TABS -->
    <q-footer v-if="$q.screen.lt.md" class="admin-bottom-nav">
      <nav class="bottom-nav-inner" aria-label="Admin menu">
        <q-btn
          v-for="tab in localizedBottomTabs"
          :key="tab.path"
          flat
          no-caps
          :ripple="false"
          class="bottom-nav-tab"
          :class="{
            'bottom-nav-tab--active': $route.path.startsWith(tab.path)
          }"
          :to="tab.path"
        >
          <span class="bottom-nav-pill">
            <q-icon :name="tab.icon" size="24px" />
          </span>
          <span class="bottom-nav-label">{{ tab.label }}</span>
        </q-btn>
      </nav>
    </q-footer>
  </q-layout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuth } from '@/composables/useAuth'
import { useLanguage } from '@/composables/useLanguage'
import { useAdminNotifications } from '@/composables/useAdminNotifications'
// Same brand mark the vendor sidebar uses, so both modules show one logo — and
// since it already reads correctly on the vendor sidebar's dark red, it needs
// no light/dark swap here either.
import logo from '@/assets/tindahan-logo.png'
import '@/css/vendor-pages.scss'

const router = useRouter()
const $q = useQuasar()
const drawerOpen = ref(false)

const adminLayoutDict = {
  en: {
    overview: 'Overview',
    management: 'Management',
    dashboard: 'Dashboard',
    notifications: 'Notifications',
    approvals: 'Approvals',
    vendors: 'Vendors',
    consumers: 'Consumers',
    home: 'Home',
    administrator: 'Administrator',
    signOut: 'Sign out',
    darkMode: 'Dark Mode',
    lightMode: 'Light Mode',
    toggleTheme: 'Toggle Color Theme',
    switchLanguage: 'Switch Language',
    goToDashboard: 'Go to dashboard'
  },
  ph: {
    overview: 'Pangkalahatan',
    management: 'Pamamahala',
    dashboard: 'Dashboard',
    notifications: 'Mga Notipikasyon',
    approvals: 'Mga Pag-apruba',
    vendors: 'Mga Tindera',
    consumers: 'Mga Mamimili',
    home: 'Tahanan',
    administrator: 'Tagapangasiwa',
    signOut: 'Mag-sign Out',
    darkMode: 'Dark Mode',
    lightMode: 'Light Mode',
    toggleTheme: 'Palitan ang Tema',
    switchLanguage: 'Palitan ang Wika',
    goToDashboard: 'Pumunta sa dashboard'
  }
}

const { t, lang, setLanguage } = useLanguage(adminLayoutDict)

const toggleLanguage = () => {
  const nextLang = lang.value === 'en' ? 'ph' : 'en'
  setLanguage(nextLang)
}

// Quasar teleports every QMenu/QDialog/QTooltip's content to a node appended
// directly under <body>, outside .admin-layout's own DOM subtree, so no
// :deep() selector scoped to .admin-layout--dark can reach a popup dialog or
// dropdown menu — that's why they stayed the light-mode white and "crashed"
// visually in dark mode. The fix mirrors the vendor layout's: an "admin-dark-
// mode" body marker this layout toggles itself, which the unscoped dark-mode
// style block below reads instead, so it can only ever match while the admin
// module itself turned dark mode on.
const setDarkMode = (isDark) => {
  $q.dark.set(isDark)
  document.body.classList.toggle('admin-dark-mode', isDark)
}

const toggleDarkMode = () => {
  const nextState = !$q.dark.isActive
  setDarkMode(nextState)
  try {
    localStorage.setItem('admin_module_dark_mode', nextState ? '1' : '0')
  } catch {}
}

const readAdminName = () => {
  try {
    return JSON.parse(localStorage.getItem('auth_user') || '{}').full_name || 'Admin'
  } catch {
    return 'Admin'
  }
}
const adminName = ref(readAdminName())

const localizedNavGroups = computed(() => {
  return [
    {
      label: t('overview'),
      items: [
        { label: t('dashboard'), icon: 'o_dashboard', path: '/admin/dashboard' },
        {
          label: t('notifications'),
          icon: 'o_notifications',
          path: '/admin/notifications',
          unread: true
        }
      ]
    },
    {
      label: t('management'),
      items: [
        {
          label: t('approvals'),
          icon: 'o_pending_actions',
          path: '/admin/approvals'
        },
        { label: t('vendors'), icon: 'o_storefront', path: '/admin/vendors' },
        { label: t('consumers'), icon: 'o_groups', path: '/admin/consumers' }
      ]
    }
  ]
})

const localizedBottomTabs = computed(() => {
  return [
    { label: t('home'), icon: 'o_home', path: '/admin/dashboard' },
    { label: t('approvals'), icon: 'o_pending_actions', path: '/admin/approvals' },
    { label: t('vendors'), icon: 'o_storefront', path: '/admin/vendors' },
    { label: t('consumers'), icon: 'o_groups', path: '/admin/consumers' }
  ]
})

const { unreadCount, fetchNotifications } = useAdminNotifications()
let notificationTimer = null

onMounted(() => {
  try {
    const savedDark = localStorage.getItem('admin_module_dark_mode') === '1'
    setDarkMode(savedDark)
  } catch {
    setDarkMode(false)
  }

  fetchNotifications()
  notificationTimer = setInterval(fetchNotifications, 60000)
})

onBeforeUnmount(() => {
  clearInterval(notificationTimer)
  setDarkMode(false)
})

const { logout } = useAuth()
const handleLogout = () => logout()
</script>

<style scoped>
.admin-layout {
  --adm-ground: #f7f7f8;
  --adm-surface: #ffffff;
  --adm-line: #e8e3df;
  --adm-text: #292929;
  --adm-secondary: #77716d;
  --adm-active: #c9232a;
  --adm-chip-bg: rgba(0, 0, 0, 0.05);
  --adm-header-logo-width: 156px;
  --adm-header-logo-height: 68px;
  --adm-sidebar-logo-width: 220px;
  --adm-sidebar-logo-height: 96px;

  background-color: var(--adm-ground);
  color: var(--adm-text);
  min-height: 100vh;
}

.admin-layout :deep(.vp-page) {
  background: transparent;
}

/* =======================================================
   DEEP DARK MODE OVERRIDES — a navy/blue-hued dark theme
   (not plain black/gray), matching common admin dark UIs.
======================================================= */
.admin-layout--dark {
  --adm-ground: #0a0f1e;
  --adm-surface: #111a2e;
  --adm-surface-2: #16213a;
  --adm-line: #24314e;
  --adm-text: #eef2fb;
  --adm-secondary: #9fb1d1;
  --adm-active: #ff4d4d;
  --adm-active-strong: #e63232;
  --adm-active-deep: #bd2020;
  --adm-active-soft: rgba(255, 77, 77, 0.14);
  --adm-active-line: rgba(255, 77, 77, 0.32);
  --adm-active-glow: rgba(255, 52, 52, 0.3);
  --adm-chip-bg: rgba(96, 141, 255, 0.14);

  background-color: var(--adm-ground) !important;
  color: var(--adm-text);
  color-scheme: dark;
}

/* ---------------------------------------------------------
   PAGE-CONTENT TOKEN REMAP
--------------------------------------------------------- */
.admin-layout--dark {
  --c-bg: var(--adm-ground);
  --c-surface: var(--adm-surface);
  --c-surface-2: var(--adm-surface-2);
  --c-text: var(--adm-text);
  --c-text-2: #e2e8f9;
  --c-text-3: #c7d0e4;
  --c-muted: var(--adm-secondary);
  --c-subtle: var(--adm-secondary);
  --c-border: var(--adm-line);
  --c-border-strong: #3c4a6e;
  --c-hairline: var(--adm-line);

  --c-brand: var(--adm-active);
  --c-brand-dark: #d92727;
  --c-brand-deep: var(--adm-active-deep);
  --c-brand-active: var(--adm-active-strong);
  --c-brand-hover: #ff7474;
  --c-brand-tint: var(--adm-active-soft);
  --c-brand-tint-2: var(--adm-active-line);

  --c-danger: #ff5c5c;
  --c-danger-tint: rgba(255, 77, 77, 0.14);
  --c-warning: #fbbf24;
  --c-warning-tint: rgba(251, 191, 36, 0.16);
  --c-warning-line: rgba(251, 191, 36, 0.4);
  --c-warning-wash: rgba(251, 191, 36, 0.1);
  --c-success: #4ade80;
  --c-success-tint: rgba(74, 222, 128, 0.16);
  --c-info: #60a5fa;
  --c-info-tint: rgba(96, 165, 250, 0.16);
  --c-status-wait: #fbbf24;
  --c-status-wait-tint: rgba(251, 191, 36, 0.16);
  --c-status-active: #60a5fa;
  --c-status-active-tint: rgba(96, 165, 250, 0.16);

  --sh-card: 0 1px 3px rgba(0, 0, 0, 0.4);
  --sh-card-hover: 0 4px 14px rgba(0, 0, 0, 0.5);
  --sh-pop: 0 10px 30px rgba(0, 0, 0, 0.55);
  --sh-header: 0 1px 3px rgba(0, 0, 0, 0.4);
  --sh-dock: 0 -2px 10px rgba(0, 0, 0, 0.4);
  --sh-brand: 0 0 0 1px rgba(255, 77, 77, 0.12), 0 3px 10px rgba(255, 52, 52, 0.2);
  --sh-brand-hover: 0 0 0 1px rgba(255, 77, 77, 0.2), 0 5px 18px var(--adm-active-glow);
}

.admin-layout--dark :deep(.vp-search .q-field__control),
.admin-layout--dark :deep(.vp-input .q-field__control),
.admin-layout--dark :deep(.pl-format),
.admin-layout--dark :deep(.pl-format--active .pl-format-icon),
.admin-layout--dark :deep(.vp-dialog) {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text) !important;
}

/* Fix for .vp-chip (Filter chips) in Dark Mode */
.admin-layout--dark :deep(.vp-chip) {
  background-color: var(--adm-surface-2) !important;
  color: var(--adm-secondary) !important;
  border: 1px solid var(--adm-line) !important;
}

.admin-layout--dark :deep(.vp-chip:hover) {
  background-color: var(--adm-line) !important;
  color: var(--adm-text) !important;
}

.admin-layout--dark :deep(.vp-chip-count) {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text) !important;
}

.admin-layout--dark :deep(.vp-chip--active) {
  background-color: var(--adm-active-soft) !important;
  border-color: var(--adm-active) !important;
  color: var(--adm-active) !important;
}

.admin-layout--dark :deep(.vp-chip--active .vp-chip-count) {
  background-color: var(--adm-active) !important;
  color: #ffffff !important;
}

/* =========================================================
   ACTION BUTTONS ("Export Report", "Mark all as read")
========================================================= */
.admin-layout--dark :deep(.adm-export.vp-pill-btn),
.admin-layout--dark :deep(.adm-export) {
  border: 1px solid rgba(255, 77, 77, 0.42) !important;
  background: rgba(255, 77, 77, 0.1) !important;
  color: #ff7575 !important;
  font-weight: 600 !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
  transition: all 0.18s ease;
}

.admin-layout--dark :deep(.adm-export.vp-pill-btn:hover:not(.disabled)),
.admin-layout--dark :deep(.adm-export:hover:not(.disabled)) {
  background: rgba(255, 77, 77, 0.22) !important;
  border-color: #ff4d4d !important;
  color: #ffffff !important;
  box-shadow: 0 3px 12px rgba(255, 77, 77, 0.35);
}

.admin-layout--dark :deep(.adm-export.disabled),
.admin-layout--dark :deep(.adm-export[disabled]) {
  border-color: var(--adm-line) !important;
  background: rgba(255, 255, 255, 0.03) !important;
  color: var(--adm-secondary) !important;
  opacity: 0.45 !important;
  box-shadow: none !important;
}

/* Quasar field text */
.admin-layout--dark :deep(.q-field__native),
.admin-layout--dark :deep(.q-field__input),
.admin-layout--dark :deep(.q-field__marginal) {
  color: var(--adm-text) !important;
}

.admin-layout--dark :deep(.q-field__label) {
  color: var(--adm-secondary) !important;
}

.admin-layout--dark :deep(.q-field--outlined .q-field__control:before) {
  border-color: var(--adm-line) !important;
}

.admin-layout--dark :deep(.vp-menu-list),
.admin-layout--dark :deep(.q-menu) {
  background-color: var(--adm-surface) !important;
  color: var(--adm-text) !important;
}

.admin-layout--dark :deep(.q-item) {
  background-color: transparent;
}

.admin-layout--dark :deep(.q-item__label) {
  color: var(--adm-text);
}

.admin-layout--dark :deep(.q-item:hover) {
  background-color: var(--adm-chip-bg) !important;
}

.admin-layout--dark :deep(.q-badge) {
  color: #ffffff;
}

.admin-layout--dark :deep(.q-chip) {
  background: var(--adm-chip-bg);
  color: var(--adm-text);
}

/* Dashboard weekly-growth badges */
.admin-layout--dark :deep(.db-kpi .db-kpi-delta),
.admin-layout--dark :deep(.db-kpi .db-kpi-trend),
.admin-layout--dark :deep(.db-kpi .db-trend),
.admin-layout--dark :deep(.db-kpi .q-badge.text-positive),
.admin-layout--dark :deep(.db-kpi .q-chip.text-positive) {
  border: 1px solid rgba(74, 222, 128, 0.28) !important;
  background: rgba(34, 197, 94, 0.13) !important;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
  color: #6ee7a0 !important;
}

.admin-layout--dark :deep(.db-kpi .db-kpi-delta .q-icon),
.admin-layout--dark :deep(.db-kpi .db-kpi-trend .q-icon),
.admin-layout--dark :deep(.db-kpi .db-trend .q-icon),
.admin-layout--dark :deep(.db-kpi .q-badge.text-positive .q-icon),
.admin-layout--dark :deep(.db-kpi .q-chip.text-positive .q-icon) {
  color: #4ade80 !important;
}

/* Red accents */
.admin-layout--dark :deep(.db-kpi-icon--danger),
.admin-layout--dark :deep(.db-icon--danger),
.admin-layout--dark :deep(.db-tone-danger),
.admin-layout--dark :deep(.text-negative) {
  color: var(--adm-active) !important;
}

.admin-layout--dark :deep(.db-kpi-icon--danger),
.admin-layout--dark :deep(.db-icon--danger) {
  border: 1px solid var(--adm-active-line);
  background: var(--adm-active-soft) !important;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.05),
    0 0 18px rgba(255, 52, 52, 0.1);
}

.admin-layout--dark :deep(.bg-negative) {
  background: var(--adm-active-strong) !important;
}

.admin-layout--dark :deep(.q-separator) {
  background: var(--adm-line);
}

.admin-layout--dark :deep(.q-tooltip) {
  background: var(--adm-surface-2);
  color: var(--adm-text);
}

/* Legacy dashboard partials */
.admin-layout--dark :deep(.vp-card),
.admin-layout--dark :deep(.db-panel),
.admin-layout--dark :deep(.db-kpi) {
  background-color: var(--adm-surface) !important;
  border-color: var(--adm-line) !important;
  color: var(--adm-text) !important;
}

.admin-layout--dark :deep(.db-cols) {
  background-color: var(--adm-surface-2) !important;
  border-color: var(--adm-line) !important;
  color: var(--adm-secondary) !important;
}

.admin-layout--dark :deep(.db-row) {
  border-color: var(--adm-line) !important;
  color: #d5deef !important;
}

.admin-layout--dark :deep(.db-row:hover) {
  background-color: #18233d !important;
}

.admin-layout--dark :deep(.db-strong),
.admin-layout--dark :deep(.db-kpi-value),
.admin-layout--dark :deep(.db-panel-title),
.admin-layout--dark :deep(.db-act-time),
.admin-layout--dark :deep(.db-act-name),
.admin-layout--dark :deep(.db-ratio-value) {
  color: #f6f8fe !important;
}

.admin-layout--dark :deep(.db-soft),
.admin-layout--dark :deep(.db-kpi-label),
.admin-layout--dark :deep(.db-ratio-label),
.admin-layout--dark :deep(.db-act-text) {
  color: var(--adm-secondary) !important;
}

.admin-layout--dark :deep(.db-skel-hole) {
  background-color: var(--adm-surface) !important;
}

.admin-layout--dark .admin-header,
.admin-layout--dark .admin-bottom-nav,
.admin-layout--dark :deep(.q-drawer.admin-sidebar),
.admin-layout--dark :deep(.admin-sidebar) {
  background: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  border-color: var(--adm-line) !important;
}

.admin-layout--dark .sidebar-top {
  background-image: radial-gradient(rgba(96, 141, 255, 0.18) 1px, transparent 1px);
}

.admin-layout--dark .header-action-btn,
.admin-layout--dark .nav-icon,
.admin-layout--dark .sidebar-signout,
.admin-layout--dark .sidebar-category-header,
.admin-layout--dark .sidebar-account-role,
.admin-layout--dark .bottom-nav-tab {
  color: var(--adm-secondary) !important;
}

.admin-layout--dark .nav-item,
.admin-layout--dark .nav-label,
.admin-layout--dark .sidebar-account-name,
.admin-layout--dark .sidebar-account,
.admin-layout--dark .sidebar-utility-chip {
  color: var(--adm-text) !important;
}

.admin-layout--dark .nav-item--active,
.admin-layout--dark .nav-item--active:hover {
  background: linear-gradient(135deg, #ef3b3b, var(--adm-active-deep)) !important;
  box-shadow:
    0 0 0 1px rgba(255, 77, 77, 0.16),
    0 7px 20px rgba(255, 52, 52, 0.24);
  color: #ffffff !important;
}

.admin-layout--dark .nav-item--active .nav-icon {
  color: #ffffff !important;
}

.admin-layout--dark .bottom-nav-tab--active {
  color: var(--adm-active) !important;
}

/* HEADER */
.admin-header {
  border-bottom: 1px solid var(--adm-line);
  background: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.toolbar-mobile {
  min-height: 72px;
}

.header-action-btn {
  color: var(--adm-secondary);
  transition: background-color 0.15s, color 0.15s;
}

.header-action-btn:hover {
  background: var(--adm-chip-bg);
  color: var(--adm-active) !important;
}

.lang-code-indicator {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.05em;
}

.header-badge {
  background: var(--adm-active) !important;
  font-weight: 700;
  color: #ffffff;
}

.header-logo-card {
  display: grid;
  place-items: center;
  flex: 0 0 var(--adm-header-logo-width);
  width: var(--adm-header-logo-width);
  min-width: var(--adm-header-logo-width);
  max-width: var(--adm-header-logo-width);
  height: var(--adm-header-logo-height);
  min-height: var(--adm-header-logo-height);
  max-height: var(--adm-header-logo-height);
  align-items: center;
  padding: 0;
  overflow: hidden;
  border: none;
  border-radius: var(--r-control);
  background: transparent;
  cursor: pointer;
}

.header-logo-card:focus-visible {
  outline: 2px solid var(--adm-active);
  outline-offset: 2px;
}

.header-logo-img {
  grid-area: 1 / 1;
  display: block;
  flex: none;
  width: 100%;
  min-width: 100%;
  max-width: 100%;
  height: 100%;
  min-height: 100%;
  max-height: 100%;
  object-fit: contain;
  object-position: center;
  transform: scale(1.32);
  transform-origin: center;
  backface-visibility: hidden;
}

/* SIDEBAR — red like the vendor sidebar, but its own design so the two modules
   stay distinguishable at a glance: a diagonal gradient instead of vendor's
   vertical one, a diagonal hairline texture instead of its dot pattern, and a
   left accent bar on the active item instead of a solid white pill.

   Every rule below is qualified with .admin-layout:not(.admin-layout--dark) —
   not just :deep(.admin-sidebar) — for two reasons: (1) a few of these class
   names (.nav-item--active, .sidebar-top) already have their own dark-mode
   rule elsewhere in this file at equal selector specificity, so without the
   :not() guard, source order alone would decide the winner and this redesign
   would wrongly leak into dark mode; (2) --adm-text/secondary/line/chip-bg
   redefined directly on .admin-sidebar would otherwise win over dark mode's
   own values for that same element regardless of specificity, since a custom
   property declared directly on an element always beats one only inherited
   from an ancestor. Dark mode's own navy sidebar (further down, under
   .admin-layout--dark) is completely untouched by this block either way. */
.admin-layout:not(.admin-layout--dark) :deep(.q-drawer.admin-sidebar),
.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) {
  --adm-text: #ffffff;
  --adm-secondary: rgba(255, 255, 255, 0.75);
  --adm-line: rgba(255, 255, 255, 0.18);
  --adm-chip-bg: rgba(255, 255, 255, 0.14);

  border-right: none !important;
  background: linear-gradient(135deg, #c9232a 0%, #8a161b 55%, #430c0f 100%) !important;
  color: #ffffff !important;
}

/* A handful of spots read --adm-active (the brand red) directly rather than a
   text/surface token; left alone they'd paint red-on-red once the sidebar
   itself turns red, so they get an explicit white treatment instead. */
.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .nav-item:hover .nav-icon {
  color: #ffffff !important;
}

.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .nav-item--active,
.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .nav-item--active:hover {
  background: rgba(255, 255, 255, 0.18) !important;
  box-shadow: inset 3px 0 0 0 #ffffff;
  color: #ffffff !important;
}

.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .nav-item--active .nav-icon {
  color: #ffffff !important;
}

.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .nav-badge {
  background: #ffffff !important;
  color: #c9232a !important;
}

.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .sidebar-account-avatar {
  background: rgba(255, 255, 255, 0.18) !important;
  color: #ffffff !important;
}

.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .sidebar-signout:hover {
  background: rgba(255, 255, 255, 0.16) !important;
  color: #ffffff !important;
}

.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .sidebar-utility-chip:hover {
  border-color: #ffffff !important;
  color: #ffffff !important;
}

.sidebar-layout {
  position: relative;
  background: transparent;
}

.sidebar-top {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 26px 20px 12px;
}

.admin-layout:not(.admin-layout--dark) :deep(.admin-sidebar) .sidebar-top {
  background-image: repeating-linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.07) 0,
    rgba(255, 255, 255, 0.07) 1px,
    transparent 1px,
    transparent 14px
  );
}

.sidebar-logo {
  display: grid;
  place-items: center;
  flex: 0 0 var(--adm-sidebar-logo-width);
  width: var(--adm-sidebar-logo-width);
  min-width: var(--adm-sidebar-logo-width);
  max-width: var(--adm-sidebar-logo-width);
  height: var(--adm-sidebar-logo-height);
  min-height: var(--adm-sidebar-logo-height);
  max-height: var(--adm-sidebar-logo-height);
  padding: 0;
  overflow: hidden;
  border: none;
  background: transparent;
  cursor: pointer;
}

.sidebar-logo:focus-visible {
  outline: 2px solid var(--adm-active);
  outline-offset: 4px;
  border-radius: var(--r-control);
}

.sidebar-logo-img {
  grid-area: 1 / 1;
  display: block;
  flex: none;
  width: 100%;
  min-width: 100%;
  max-width: 100%;
  height: 100%;
  min-height: 100%;
  max-height: 100%;
  object-fit: contain;
  object-position: center;
  transform: scale(1.32);
  transform-origin: center;
  backface-visibility: hidden;
}

/* SIDEBAR CONTROLS */
.sidebar-utility-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px 12px;
}

.sidebar-utility-chip {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 32px;
  padding: 0 10px;
  border: 1px solid var(--adm-line);
  border-radius: 9999px;
  background: var(--adm-chip-bg);
  color: var(--adm-text);
  font-size: 11.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.sidebar-utility-chip:hover {
  border-color: var(--adm-active);
  color: var(--adm-active) !important;
}

/* NAVIGATION */
.sidebar-links-container {
  margin-top: 4px;
  padding: 12px;
  border-top: 1px solid var(--adm-line);
}

.sidebar-group + .sidebar-group {
  margin-top: 18px;
}

.sidebar-category-header {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 12px 6px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--adm-secondary);
}

.sidebar-category-header::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--adm-line);
}

.sidebar-group-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.nav-item {
  min-height: 42px;
  padding: 8px 12px !important;
  border-radius: var(--r-control);
  color: var(--adm-text);
  transition: background-color 0.15s, color 0.15s;
}

.nav-avatar-slot {
  display: flex;
  align-items: center;
  min-width: 32px !important;
  max-width: 32px !important;
  padding-right: 12px !important;
}

.nav-icon {
  color: var(--adm-secondary);
  transition: color 0.15s;
}

.nav-label {
  font-size: var(--fs-sm) !important;
  font-weight: 600 !important;
}

.nav-item:hover {
  background: var(--adm-chip-bg);
  color: var(--adm-text);
}

.nav-item:hover .nav-icon {
  color: var(--adm-active) !important;
}

.nav-item--active,
.nav-item--active:hover {
  background: var(--adm-active) !important;
  box-shadow: 0 4px 12px rgba(201, 35, 42, 0.28);
  color: #ffffff !important;
}

.nav-item--active .nav-icon {
  color: #ffffff !important;
}

.nav-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 6px;
  border-radius: var(--r-pill);
  background: var(--adm-active);
  font-size: 11px;
  font-weight: 700;
  color: #ffffff;
}

.nav-item--active .nav-badge {
  background: #ffffff;
  color: var(--adm-active);
}

/* FOOTER ACCOUNT */
.sidebar-footer-area {
  padding: 12px;
  border-top: 1px solid var(--adm-line);
}

.sidebar-account {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--adm-line);
  border-radius: var(--r-surface);
  background: var(--adm-chip-bg);
  color: var(--adm-text);
}

.sidebar-account-avatar {
  flex-shrink: 0;
  background: rgba(201, 35, 42, 0.12);
  color: var(--adm-active);
}

.sidebar-account-text {
  flex: 1;
  min-width: 0;
}

.sidebar-account-name {
  overflow: hidden;
  font-size: var(--fs-sm);
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sidebar-account-role {
  font-size: var(--fs-2xs);
  color: var(--adm-secondary);
}

.sidebar-signout {
  flex-shrink: 0;
  color: var(--adm-secondary);
  transition: background-color 0.15s, color 0.15s;
}

.sidebar-signout:hover {
  background: rgba(201, 35, 42, 0.12);
  color: var(--adm-active) !important;
}

/* BOTTOM TABS */
.admin-bottom-nav {
  z-index: 2000;
  padding-bottom: env(safe-area-inset-bottom);
  border-top: 1px solid var(--adm-line);
  background: var(--adm-surface) !important;
  color: var(--adm-text) !important;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

.bottom-nav-inner {
  display: flex;
  max-width: 560px;
  margin: 0 auto;
  padding: 0 6px;
}

.bottom-nav-tab {
  flex: 1 1 0;
  min-width: 0;
  height: 64px;
  min-height: 0;
  padding: 0;
  border-radius: 0;
  color: var(--adm-secondary);
  transition: color 0.15s;
}

.bottom-nav-tab :deep(.q-btn__content) {
  flex-direction: column;
  flex-wrap: nowrap;
  gap: 4px;
}

.bottom-nav-tab :deep(.q-focus-helper) {
  display: none;
}

.bottom-nav-pill {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 30px;
  border-radius: var(--r-pill);
  transition: background-color 0.2s;
}

.bottom-nav-label {
  overflow: hidden;
  max-width: 100%;
  font-size: var(--fs-xs);
  font-weight: 600;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bottom-nav-tab--active {
  color: var(--adm-active) !important;
}

.bottom-nav-tab--active .bottom-nav-pill {
  background: rgba(201, 35, 42, 0.12);
}

.admin-layout--dark .bottom-nav-tab--active .bottom-nav-pill {
  background: var(--adm-active-soft);
}

.bottom-nav-tab--active .bottom-nav-label {
  font-weight: 700;
}

.bottom-nav-tab:not(.bottom-nav-tab--active):hover .bottom-nav-pill {
  background: var(--adm-chip-bg);
}

.mobile-pb {
  padding-bottom: calc(72px + env(safe-area-inset-bottom));
}
</style>

<!--
  Quasar teleports every QMenu/QDialog/QTooltip's content to a node it
  appends directly under <body>, entirely outside .admin-layout's own DOM
  subtree — so no :deep() selector scoped to .admin-layout--dark (all of it
  above) can ever reach a dialog card or dropdown menu once it's open. That's
  why "View Store", "View Live Products" and the row-level ⋮ options menu
  stayed the light-mode white and looked broken against the rest of the dark
  admin UI. This mirrors the vendor layout's fix: an "admin-dark-mode" body
  marker this layout toggles itself (see setDarkMode in the script), read
  here instead of bare body.body--dark so it can only ever match while the
  admin module itself turned dark mode on, even though some of these class
  names (vp-dialog, vp-status, vp-chip) are shared with the vendor module.
-->
<style>
/* Re-declares the navy palette on <body> so a teleported node — whose real
   DOM parent is <body>, not .admin-layout--dark — still inherits the same
   var(--c-*) tokens every vp-* rule and dialog already reads. */
body.body--dark.admin-dark-mode {
  --c-surface: #111a2e;
  --c-surface-2: #16213a;
  --c-text: #eef2fb;
  --c-text-2: #e2e8f9;
  --c-text-3: #c7d0e4;
  --c-muted: #9fb1d1;
  --c-subtle: #9fb1d1;
  --c-border: #24314e;
  --c-border-strong: #3c4a6e;
  --c-hairline: #24314e;

  --c-brand: #ff4d4d;
  --c-brand-deep: #bd2020;
  --c-brand-active: #e63232;
  --c-brand-hover: #ff7474;
  --c-brand-tint: rgba(255, 77, 77, 0.14);
  --c-brand-tint-2: rgba(255, 77, 77, 0.32);

  --c-danger: #ff5c5c;
  --c-danger-tint: rgba(255, 77, 77, 0.14);
  --c-warning: #fbbf24;
  --c-warning-tint: rgba(251, 191, 36, 0.16);
  --c-success: #4ade80;
  --c-success-tint: rgba(74, 222, 128, 0.16);
  --c-info: #60a5fa;
  --c-info-tint: rgba(96, 165, 250, 0.16);
}

/* Every dialog card, whatever its own custom class — vp-dialog, spd (store
   profile), cpd (consumer profile), products-compact-dialog, adm-confirm,
   adm-form-dialog and so on all reduce to a plain <q-card> inside a
   <q-dialog>, so one generic selector reaches all of them, present or
   added later, instead of enumerating each page's own name for it. */
body.body--dark.admin-dark-mode .q-dialog .q-card {
  background-color: #111a2e !important;
  color: #eef2fb !important;
}

/* The store/consumer profile dialogs' own hardcoded white cards, and their
   close button that floats over the cover photo. */
body.body--dark.admin-dark-mode .sp-card,
body.body--dark.admin-dark-mode .cpd-head,
body.body--dark.admin-dark-mode .cpd-fact,
body.body--dark.admin-dark-mode .cpd-card {
  background-color: #16213a !important;
  border-color: #24314e !important;
  color: #eef2fb !important;
}

body.body--dark.admin-dark-mode .spd-close {
  background: rgba(22, 33, 58, 0.94) !important;
  color: #c7d0e4 !important;
}

/* The row-level ⋮ options menu (Approve/Reject/status change), teleported
   like every other menu. */
body.body--dark.admin-dark-mode .compact-status-menu {
  background: #111a2e !important;
  border-color: #24314e !important;
}

body.body--dark.admin-dark-mode .compact-menu-item {
  color: #e2e8f9 !important;
}

body.body--dark.admin-dark-mode .compact-menu-item:hover {
  background: #16213a !important;
}

body.body--dark.admin-dark-mode .compact-menu-avatar {
  color: #9fb1d1 !important;
}
</style>