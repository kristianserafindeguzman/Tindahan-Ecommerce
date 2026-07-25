<template>
  <q-layout view="hHh LpR fFf" class="admin-layout text-dark">

    <!-- LEFT SIDEBAR -->
    <q-drawer
      v-model="drawerOpen"
      :width="240"
      :breakpoint="768"
      bordered
      class="admin-sidebar"
    >
      <div class="sidebar-content">

        <!-- LOGO -->
        <div class="sidebar-logo">
          <img
            src="@/assets/tindahan-mobile.png"
            alt="Tindahan"
            class="logo-img"
          />
          <span class="logo-text">Tindahan</span>
        </div>

        <q-separator class="sidebar-separator" />

        <!-- NAV ITEMS -->
        <q-list class="nav-list">
          <q-item
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            clickable
            active-class="nav-active"
            class="nav-item"
          >
            <q-item-section avatar>
              <q-icon :name="item.icon" />
            </q-item-section>
            <q-item-section>{{ item.label }}</q-item-section>
          </q-item>
        </q-list>

        <!-- SPACER -->
        <div class="sidebar-spacer" />

        <!-- LOGOUT -->
        <div class="sidebar-footer">
          <q-separator class="sidebar-separator" />
          <q-item
            clickable
            class="nav-item logout-item"
            @click="handleLogout"
          >
            <q-item-section avatar>
              <q-icon name="logout" />
            </q-item-section>
            <q-item-section>Logout</q-item-section>
          </q-item>
        </div>

      </div>
    </q-drawer>

    <!-- HEADER (mobile only — toggle sidebar) -->
    <q-header class="admin-header" elevated>
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          @click="drawerOpen = !drawerOpen"
        />
        <q-toolbar-title class="header-title">
          Tindahan Admin
        </q-toolbar-title>
      </q-toolbar>
    </q-header>

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

const navItems = [
  { label: 'Dashboard',  icon: 'dashboard',       path: '/admin/dashboard' },
  { label: 'Approvals',  icon: 'pending_actions',  path: '/admin/approvals' },
  { label: 'Vendors',    icon: 'store',            path: '/admin/vendors' },
  { label: 'Consumers',  icon: 'people',           path: '/admin/consumers' },
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
  font-family: 'Roboto', Arial, sans-serif;
}

/* SIDEBAR */

.admin-sidebar {
  background: #ffffff !important;
}

.sidebar-content {
  display: flex;
  flex-direction: column;

  height: 100%;
}

.sidebar-logo {
  display: flex;
  align-items: center;

  gap: 12px;

  padding: 20px 20px 16px;
}

.logo-img {
  width: 36px;
  height: 36px;

  object-fit: contain;
}

.logo-text {
  font-size: 18px;
  font-weight: 700;

  color: #333333;

  letter-spacing: 0.02em;
}

.sidebar-separator {
  background: rgba(255, 255, 255, 0.08);
}

/* NAV */

.nav-list {
  padding: 10px 0;
}

.nav-item {
  margin: 2px 10px;
  padding: 10px 14px;

  border-radius: 8px;

  color: #333333;

  font-size: 13px;
}

.nav-item :deep(.q-icon) {
  color: #555555;

  font-size: 20px;
}

.nav-item:hover {
  background: rgba(0, 0, 0, 0.06);

  color: #111111;
}

.nav-item:hover :deep(.q-icon) {
  color: #111111;
}

.nav-active {
  background: rgba(189, 36, 39, 0.1) !important;

  color: #bd2427 !important;
}

.nav-active :deep(.q-icon) {
  color: #bd2427 !important;
}

/* SPACER & FOOTER */

.sidebar-spacer {
  flex: 1;
}

.sidebar-footer {
  padding-bottom: 10px;
}

.logout-item {
  color: #555555;
}

.logout-item:hover {
  color: #ef4444;
}

.logout-item:hover :deep(.q-icon) {
  color: #ef4444;
}

/* HEADER (mobile) */

.admin-header {
  background: #1a1a2e;
}

.header-title {
  font-size: 16px;
  font-weight: 600;
}

/* DESKTOP: hide header */
@media (min-width: 769px) {
  .admin-header {
    display: none;
  }
}
</style>
