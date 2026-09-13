<template>
  <q-layout view="hHh LpR fFf" class="admin-layout">
    <!-- PHONE HEADER — a slim white bar with the logo, the bell and sign-out; phones move between pages with the bottom tabs. -->
    <q-header v-if="$q.screen.lt.md" class="admin-header">
      <q-toolbar class="q-px-md toolbar-mobile">
        <button
          type="button"
          class="header-logo-card"
          aria-label="Go to dashboard"
          @click="router.push('/admin/dashboard')"
        >
          <img
            src="@/assets/tindahan-mobile.png"
            alt="Tindahan"
            class="header-logo-img"
          />
        </button>

        <q-space />

        <!-- On phones the bell opens the notifications page, which has room for the whole list. -->
        <q-btn
          flat
          round
          dense
          icon="o_notifications"
          class="header-action-btn"
          to="/admin/notifications"
          :aria-label="
            unreadCount
              ? `Notifications, ${unreadCount} unread`
              : 'Notifications'
          "
        >
          <q-badge v-if="unreadCount" floating rounded class="header-badge">{{
            unreadCount > 99 ? '99+' : unreadCount
          }}</q-badge>
        </q-btn>
        <q-btn
          flat
          round
          dense
          icon="o_logout"
          class="header-action-btn"
          aria-label="Sign out"
          @click="handleLogout"
        />
      </q-toolbar>
    </q-header>

    <!-- SIDEBAR — a white frame on desktop: the logo on top, the menu, and the admin's account at the bottom. -->
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
            aria-label="Go to dashboard"
            @click="router.push('/admin/dashboard')"
          >
            <img
              src="@/assets/tindahan-mobile.png"
              alt="Tindahan"
              class="sidebar-logo-img"
            />
          </button>
        </div>

        <!-- Every page is one tap away, grouped under small headings. -->
        <nav class="sidebar-links-container col scroll" aria-label="Admin menu">
          <div
            v-for="group in navGroups"
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
                <q-item-section class="nav-label">{{
                  item.label
                }}</q-item-section>
                <q-item-section v-if="item.unread && unreadCount" side>
                  <span class="nav-badge">{{
                    unreadCount > 99 ? '99+' : unreadCount
                  }}</span>
                </q-item-section>
              </q-item>
            </q-list>
          </div>
        </nav>

        <div class="sidebar-footer-area">
          <!-- Who is signed in, with sign-out one tap away. -->
          <div class="sidebar-account">
            <q-avatar size="36px" class="sidebar-account-avatar">
              <q-icon name="o_admin_panel_settings" size="20px" />
            </q-avatar>
            <div class="sidebar-account-text">
              <div class="sidebar-account-name">{{ adminName }}</div>
              <div class="sidebar-account-role">Administrator</div>
            </div>
            <q-btn
              flat
              round
              dense
              icon="o_logout"
              class="sidebar-signout"
              aria-label="Sign out"
              @click="handleLogout"
            >
              <q-tooltip>Sign out</q-tooltip>
            </q-btn>
          </div>
        </div>
      </div>
    </q-drawer>

    <q-page-container :class="{ 'mobile-pb': $q.screen.lt.md }">
      <router-view />
    </q-page-container>

    <!-- BOTTOM TABS — phones reach the four admin pages from here, an icon in a pill over its label. -->
    <q-footer v-if="$q.screen.lt.md" class="admin-bottom-nav bg-white">
      <nav class="bottom-nav-inner" aria-label="Admin menu">
        <q-btn
          v-for="tab in bottomTabs"
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
          <span class="bottom-nav-pill"
            ><q-icon :name="tab.icon" size="24px"
          /></span>
          <span class="bottom-nav-label">{{ tab.label }}</span>
        </q-btn>
      </nav>
    </q-footer>
  </q-layout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { useAuth } from '@/composables/useAuth'
import { useAdminNotifications } from '@/composables/useAdminNotifications'
// The vendor pages' shared look, which the admin pages build on too.
import '@/css/vendor-pages.scss'

const router = useRouter()
const $q = useQuasar()
const drawerOpen = ref(false)

// The signed-in admin's name, read from the sign-in record, so the sidebar needs no extra request.
const readAdminName = () => {
  try {
    return (
      JSON.parse(localStorage.getItem('auth_user') || '{}').full_name || 'Admin'
    )
  } catch {
    return 'Admin'
  }
}
const adminName = ref(readAdminName())

const navGroups = [
  {
    label: 'Overview',
    items: [
      { label: 'Dashboard', icon: 'o_dashboard', path: '/admin/dashboard' },
      {
        label: 'Notifications',
        icon: 'o_notifications',
        path: '/admin/notifications',
        unread: true
      }
    ]
  },
  {
    label: 'Management',
    items: [
      {
        label: 'Approvals',
        icon: 'o_pending_actions',
        path: '/admin/approvals'
      },
      { label: 'Vendors', icon: 'o_storefront', path: '/admin/vendors' },
      { label: 'Consumers', icon: 'o_groups', path: '/admin/consumers' }
    ]
  }
]

const bottomTabs = [
  { label: 'Home', icon: 'o_home', path: '/admin/dashboard' },
  { label: 'Approvals', icon: 'o_pending_actions', path: '/admin/approvals' },
  { label: 'Vendors', icon: 'o_storefront', path: '/admin/vendors' },
  { label: 'Consumers', icon: 'o_groups', path: '/admin/consumers' }
]

// New applications and sign-ups show up without a refresh: the list loads now and again every minute.
const { unreadCount, fetchNotifications } = useAdminNotifications()
let notificationTimer = null

// The admin side is light only, so a dark theme saved by the old switch, and the old one-time loading screen's marker, are cleared.
onMounted(() => {
  $q.dark.set(false)
  try {
    localStorage.removeItem('admin_dark_mode')
    sessionStorage.removeItem('admin_session_loaded')
  } catch {
    // Storage can be unavailable, such as in some private windows.
  }
  fetchNotifications()
  notificationTimer = setInterval(fetchNotifications, 60000)
})

onBeforeUnmount(() => clearInterval(notificationTimer))

// The same log-out confirmation as the vendor and consumer pages, which also clears every saved sign-in value.
const { logout } = useAuth()
const handleLogout = () => logout()
</script>

<style scoped>
/* COLOURS — a warm ground, white sidebar and cards, dark menu text, warm grey secondary text and a strong red for the current page. */
.admin-layout {
  --adm-ground: #f7f7f7;
  --adm-line: #e8e3df;
  --adm-text: #292929;
  --adm-secondary: #77716d;
  --adm-active: #c9232a;

  /* Plain white, the same as the vendor pages. */
  background-color: #f7f7f8;
}

.admin-layout :deep(.vp-page) {
  background: transparent;
}

/* HEADER — white, with the colours reversed from the vendor's red bar. */
.admin-header {
  border-bottom: 1px solid var(--adm-line);

  background: #ffffff !important;
  box-shadow: 0 1px 3px rgba(17, 17, 17, 0.04);

  color: var(--c-text) !important;
}

.toolbar-mobile {
  min-height: 60px;
}

.header-action-btn {
  color: var(--c-text-2);

  transition:
    background-color 0.15s,
    color 0.15s;
}

.header-action-btn:hover {
  background: var(--c-brand-tint);

  color: var(--c-brand);
}

.header-badge {
  background: var(--c-brand) !important;

  font-weight: 700;
}

.header-logo-card {
  display: flex;
  align-items: center;

  padding: 0 4px;

  border: none;
  border-radius: var(--r-control);

  background: transparent;

  cursor: pointer;
}

.header-logo-card:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

.header-logo-img {
  display: block;

  width: 116px;
  height: 58px;

  object-fit: contain;
}

/* SIDEBAR — white with a warm hairline edge and dark menu text; the current page is a red pill, the reverse of the vendor's white one. */
:deep(.q-drawer.admin-sidebar),
:deep(.admin-sidebar) {
  border-right: 1px solid var(--adm-line) !important;

  /* White, set off from the white page by its thin edge line. */
  background: #ffffff !important;

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

  padding: 26px 20px 8px;
}

/* The banner's dot grid, faintly, behind the logo. */
.sidebar-top {
  background-image: radial-gradient(
    rgba(201, 35, 42, 0.12) 1px,
    transparent 1px
  );
  background-size: 16px 16px;
}

.sidebar-logo {
  padding: 0;

  border: none;

  background: transparent;

  cursor: pointer;
}

.sidebar-logo:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 4px;
  border-radius: var(--r-control);
}

/* The same size and spacing as the vendor sidebar's logo. */
.sidebar-logo-img {
  display: block;

  width: auto;
  max-width: 220px;
  height: 112px;

  object-fit: contain;
}

/* NAV GROUPS */
.sidebar-links-container {
  margin-top: 12px;
  padding: 16px 12px 12px;

  border-top: 1px solid var(--adm-line);
}

.sidebar-group + .sidebar-group {
  margin-top: 18px;
}

.sidebar-category-header {
  padding: 0 12px 6px;

  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;

  color: var(--adm-secondary);
}

/* A thin rule after each heading, so the groups read as sections. */
.sidebar-category-header {
  display: flex;
  align-items: center;

  gap: 10px;
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

  transition:
    background-color 0.15s,
    color 0.15s;
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
  background: var(--adm-ground);

  color: var(--adm-text);
}

.nav-item:hover .nav-icon {
  color: var(--adm-active);
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

/* The unread count beside Notifications. */
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

/* ACCOUNT — the admin's card at the foot of the sidebar, with its sign-out button. */
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

  background: var(--adm-ground);

  color: var(--adm-text);
}

.sidebar-account-avatar {
  flex-shrink: 0;

  background: var(--c-brand-tint);

  color: var(--c-brand);
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

  transition:
    background-color 0.15s,
    color 0.15s;
}

.sidebar-signout:hover {
  background: var(--c-brand-tint);

  color: var(--c-brand);
}

/* BOTTOM TABS — the vendor tab bar: 64px cells with the icon in a pill over its label. */
.admin-bottom-nav {
  z-index: 2000;

  padding-bottom: env(safe-area-inset-bottom);

  border-top: 1px solid var(--c-border);

  box-shadow: var(--sh-dock);
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

  color: var(--c-subtle);

  transition: color 0.15s;
}

.bottom-nav-tab :deep(.q-btn__content) {
  flex-direction: column;
  flex-wrap: nowrap;

  gap: 4px;
}

/* Quasar's hover wash would fill the whole cell, so the pill is the hover state instead. */
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
  color: var(--c-brand);
}

.bottom-nav-tab--active .bottom-nav-pill {
  background: var(--c-brand-tint);
}

.bottom-nav-tab--active .bottom-nav-label {
  font-weight: 700;
}

.bottom-nav-tab:not(.bottom-nav-tab--active):hover .bottom-nav-pill {
  background: var(--c-surface);
}

.bottom-nav-tab:focus-visible .bottom-nav-pill {
  box-shadow: 0 0 0 2px var(--c-brand);
}

@media (prefers-reduced-motion: reduce) {
  .bottom-nav-tab,
  .bottom-nav-pill {
    transition: none;
  }
}

.mobile-pb {
  padding-bottom: calc(72px + env(safe-area-inset-bottom));
}
</style>
