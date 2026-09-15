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
            :src="logoBlack"
            alt=""
            aria-hidden="true"
            class="header-logo-img header-logo-img--light"
            :class="{ 'logo-img--visible': !$q.dark.isActive }"
          />
          <img
            :src="logoColor"
            alt=""
            aria-hidden="true"
            class="header-logo-img header-logo-img--color"
            :class="{ 'logo-img--visible': $q.dark.isActive }"
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
              :src="logoBlack"
              alt=""
              aria-hidden="true"
              class="sidebar-logo-img sidebar-logo-img--light"
              :class="{ 'logo-img--visible': !$q.dark.isActive }"
            />
            <img
              :src="logoColor"
              alt=""
              aria-hidden="true"
              class="sidebar-logo-img sidebar-logo-img--color"
              :class="{ 'logo-img--visible': $q.dark.isActive }"
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
import logoBlack from '@/assets/tindahan-black.png'
import logoColor from '@/assets/tindahan-logo.png'
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

const toggleDarkMode = () => {
  const nextState = !$q.dark.isActive
  $q.dark.set(nextState)
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
    $q.dark.set(savedDark)
  } catch {
    $q.dark.set(false)
  }

  fetchNotifications()
  notificationTimer = setInterval(fetchNotifications, 60000)
})

onBeforeUnmount(() => {
  clearInterval(notificationTimer)
  $q.dark.set(false)
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
  /* True-red accents with controlled glow for clear visibility
     against the navy surfaces. */
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
   Admin pages (dashboard, approvals, vendors, consumers, and
   the shared vp-, pl-, and db- prefixed partials) are styled
   with the shared design tokens below rather than the --adm-
   ones above. In dark mode those tokens are redeclared here so
   every card, stat, table, chip, and field inside <router-view>
   inherits dark-appropriate values automatically. CSS custom
   properties inherit through the DOM, so this affects anything
   rendered inside .admin-layout without needing a rule per
   component; it does NOT touch vendor/consumer pages since
   those never render under a .admin-layout--dark ancestor.
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

/* Some shared partials (search fields, format pickers, summary
   boxes, thumbnails) hardcode #ffffff directly instead of a
   token, so the remap above can't reach them — caught here. */
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

/* Quasar field text, labels and placeholders default to a
   translucent black which is nearly invisible on a dark
   background — this is the real cause of "text not visible". */
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

/* Menu/list rows only get a transparent background here — text
   color is left to each item's own label/icon so tone colors
   (danger red, success green, etc.) aren't stripped out by a
   parent override cascading through currentColor. */
.admin-layout--dark :deep(.q-item) {
  background-color: transparent;
}

.admin-layout--dark :deep(.q-item__label) {
  color: var(--adm-text);
}

.admin-layout--dark :deep(.q-item:hover) {
  background-color: var(--adm-chip-bg) !important;
}

/* Generic badge/chip catch-all for components that don't use
   the vp-/pl- tone classes and would otherwise keep their
   light-mode look (white-on-white, etc.). */
.admin-layout--dark :deep(.q-badge) {
  color: #ffffff;
}

.admin-layout--dark :deep(.q-chip) {
  background: var(--adm-chip-bg);
  color: var(--adm-text);
}

/* Dashboard weekly-growth badges
   The previous generic chip/badge override left these pills with a stark
   white fill. These selectors cover the legacy and current dashboard names
   while staying scoped to KPI cards only. */
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

/* Refined dark-mode treatment for red dashboard accents. */
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

/* Legacy dashboard partials (kept for pages still using the
   older db- class names rather than the shared vp- ones). */
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

/* Both logo assets always render inside the exact same box.
   Intrinsic image dimensions cannot resize the header during a theme switch. */
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
  transform-origin: center;
  opacity: 0;
  pointer-events: none;
  backface-visibility: hidden;
  will-change: opacity;
  transition: opacity 160ms ease-out;
}

/* tindahan-black.png contains substantially more transparent padding than
   tindahan-logo.png. It needs a larger visual scale to appear equally sized. */
.header-logo-img--light {
  transform: scale(1.8);
}

.header-logo-img--color {
  transform: scale(1.32);
}

/* SIDEBAR */
:deep(.q-drawer.admin-sidebar),
:deep(.admin-sidebar) {
  border-right: 1px solid var(--adm-line) !important;
  background: var(--adm-surface) !important;
  color: var(--adm-text) !important;
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
  background-image: radial-gradient(rgba(201, 35, 42, 0.12) 1px, transparent 1px);
  background-size: 16px 16px;
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

/* The light and dark assets inherit one fixed sidebar size, preventing
   any shrink, enlargement, or layout shift while the source changes. */
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
  transform-origin: center;
  opacity: 0;
  pointer-events: none;
  backface-visibility: hidden;
  will-change: opacity;
  transition: opacity 160ms ease-out;
}

.sidebar-logo-img--light {
  transform: scale(2);
}

.sidebar-logo-img--color {
  transform: scale(1.32);
}

/* Only opacity changes during theme switching. Both preloaded logo assets
   keep their own fixed transform, preventing the previous scale/pop effect. */
.logo-img--visible {
  z-index: 1;
  opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
  .header-logo-img,
  .sidebar-logo-img {
    transition: none;
  }
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
