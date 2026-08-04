<template>
  <q-layout view="hHh LpR fFf" class="admin-layout bg-grey-1">

    <!-- HEADER (Mobile only — toggles sidebar) -->
    <q-header class="admin-header-mobile" elevated>
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          @click="drawerOpen = !drawerOpen"
        />
        <q-toolbar-title class="header-title text-weight-bold">
          Tindahan Admin
        </q-toolbar-title>
      </q-toolbar>
    </q-header>

    <!-- LEFT SIDEBAR -->
    <q-drawer
      v-model="drawerOpen"
      show-if-above
      :width="260"
      :breakpoint="1024"
      class="shadow-4"
      style="background: linear-gradient(180deg, #A71D20 0%, #821315 100%); color: #ffffff;"
    >
      <div class="sidebar-content">

        <!-- LOGO -->
        <div class="sidebar-logo">
          <img
            src="@/assets/tindahan-mobile.png"
            alt="Tindahan"
            class="logo-img"
          />
        </div>

        <!-- NAV ITEMS -->
        <q-list class="nav-list q-mt-md">
          <q-item
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            clickable
            v-ripple
            active-class="nav-active"
            class="nav-item"
          >
            <q-item-section avatar class="q-pr-sm min-w-0">
              <q-icon :name="item.icon" size="22px" />
            </q-item-section>
            <q-item-section class="nav-label text-weight-medium">
              {{ item.label }}
            </q-item-section>
          </q-item>
        </q-list>

        <!-- SPACER -->
        <div class="sidebar-spacer" />

        <!-- LOGOUT -->
        <div class="sidebar-footer">
          <q-separator class="sidebar-separator q-mb-sm q-mx-md" />
          <q-item
            clickable
            v-ripple
            class="nav-item logout-item"
            @click="handleLogout"
          >
            <q-item-section avatar class="q-pr-sm min-w-0">
              <q-icon name="logout" size="22px" />
            </q-item-section>
            <q-item-section class="nav-label text-weight-medium">
              Logout
            </q-item-section>
          </q-item>
        </div>

      </div>
    </q-drawer>

    <!-- MAIN CONTENT -->
    <q-page-container>
      <router-view />
    </q-page-container>

  </q-layout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'

const router = useRouter()
const drawerOpen = ref(true)

// Using modern, rounded Material icons to match the aesthetic
const navItems = [
  { label: 'Dashboard',  icon: 'grid_view',       path: '/admin/dashboard' },
  { label: 'Approvals',  icon: 'pending_actions', path: '/admin/approvals' },
  { label: 'Vendors',    icon: 'storefront',      path: '/admin/vendors' },
  { label: 'Consumers',  icon: 'groups',          path: '/admin/consumers' },
]

const handleLogout = async () => {
  try {
    await api.post('/logout')
  } catch {
    // Token may already be invalid
  }

  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  localStorage.removeItem('auth_role')

  router.push('/login')
}
</script>

<style scoped>
.admin-layout {
  font-family: 'Inter', 'Roboto', Arial, sans-serif;
}

/* ==========================================================
   SIDEBAR LAYOUT
========================================================== */
.sidebar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* ==========================================================
   LOGO
========================================================== */
.sidebar-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 20px 24px;
}

.logo-img {
  width: 190px;           /* Expanded width to fill more of the drawer */
  height: 65px;           /* Fixed height prevents the image from collapsing */
  object-fit: contain;    /* Ensures the image scales properly without distortion */
  display: block;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15)); /* Subtle pop against the dark red */
}

/* ==========================================================
   NAVIGATION ITEMS
========================================================== */
.nav-list {
  padding: 10px 0;
}

.nav-item {
  margin: 6px 16px;
  padding: 12px 16px;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.75); /* Muted white for inactive */
  transition: all 0.3s ease;
}

.nav-item :deep(.q-icon) {
  color: rgba(255, 255, 255, 0.75);
  transition: color 0.3s ease;
}

.nav-label {
  font-size: 14px;
  letter-spacing: 0.01em;
}

/* Hover State */
.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.nav-item:hover :deep(.q-icon) {
  color: #ffffff;
}

/* Active State (White pill with red text) */
.nav-active {
  background: #ffffff !important;
  color: #A71D20 !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.nav-active :deep(.q-icon) {
  color: #A71D20 !important;
}

/* Helper to tighten icon spacing */
.min-w-0 {
  min-width: 0 !important;
}

/* ==========================================================
   FOOTER & LOGOUT
========================================================== */
.sidebar-spacer {
  flex: 1;
}

.sidebar-footer {
  padding-bottom: 24px;
}

.sidebar-separator {
  background: rgba(255, 255, 255, 0.15);
  height: 1px;
}

.logout-item {
  color: rgba(255, 255, 255, 0.75);
}

.logout-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.logout-item:hover :deep(.q-icon) {
  color: #ffffff;
}

/* ==========================================================
   MOBILE HEADER
========================================================== */
.admin-header-mobile {
  background: #A71D20;
}

.header-title {
  font-size: 18px;
  letter-spacing: 0.02em;
}

/* Hide the top header on desktop sizes since the drawer handles navigation */
@media (min-width: 1025px) {
  .admin-header-mobile {
    display: none;
  }
}
</style>
