<template>
  <q-layout view="hHh LpR fFf" class="vendor-layout bg-slate-50">

    <!-- HEADER -->
    <q-header elevated class="premium-header border-bottom-dark">
      <q-toolbar class="q-px-lg" style="min-height: 70px;">
        <q-btn
          flat
          dense
          round
          icon="menu"
          color="white"
          class="menu-toggle-btn"
          @click="drawerOpen = !drawerOpen"
        />
        
        <div class="row items-center cursor-pointer q-ml-sm transition-transform hover-scale" @click="router.push('/vendor/dashboard')">
          <!-- Replaced hardcoded "Vendor Center" with dynamic storeName binding -->
          <q-toolbar-title class="header-title text-weight-bolder text-white tracking-tight">
            {{ storeName }}
          </q-toolbar-title>
        </div>

        <q-space />

        <!-- PROFILE DROPDOWN -->
        <q-btn flat no-caps class="profile-btn q-px-sm q-py-xs">
          <div class="row items-center no-wrap">
            <q-avatar size="36px" class="q-mr-sm shadow-1 bg-white">
              <img :src="userProfilePicture" v-if="userProfilePicture" />
              <q-icon name="person" color="red-9" size="24px" v-else />
            </q-avatar>
            <div class="text-weight-bold text-white q-mr-xs">{{ userName }}</div>
            <q-icon name="expand_more" size="20px" color="white" />
          </div>

          <q-menu auto-close class="profile-glass-menu" :offset="[0, 10]">
            <q-list style="min-width: 180px" class="q-py-sm">
              <q-item clickable v-ripple @click="router.push('/vendor/profile')" class="menu-item">
                <q-item-section avatar style="min-width: 36px">
                  <q-icon name="manage_accounts" color="blue-grey-8" size="20px" />
                </q-item-section>
                <q-item-section class="text-weight-medium text-blue-grey-9">Profile Settings</q-item-section>
              </q-item>
              
              <q-separator class="q-my-sm opacity-50" />

              <q-item clickable v-ripple @click="handleLogout" class="menu-item logout-item">
                <q-item-section avatar style="min-width: 36px">
                  <q-icon name="logout" color="red-8" size="20px" />
                </q-item-section>
                <q-item-section class="text-weight-bold text-red-8">Logout</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>

    <!-- LEFT SIDEBAR -->
    <q-drawer
      v-model="drawerOpen"
      show-if-above
      :width="280"
      :breakpoint="1024"
      class="premium-drawer"
    >
      <div class="sidebar-content">

        <!-- LOGO -->
        <div class="sidebar-logo q-mt-md">
          <div class="logo-glass-wrapper">
            <img
              src="@/assets/tindahan-mobile.png"
              alt="Tindahan"
              class="logo-img"
            />
          </div>
        </div>

        <!-- NAV ITEMS -->
        <div class="nav-container q-mt-lg">
          <q-list class="nav-list">
            <template v-for="(item, index) in navItems" :key="index">
              
              <!-- Standard Link -->
              <q-item
                v-if="!item.children"
                :to="item.path"
                clickable
                v-ripple
                active-class="nav-active"
                class="nav-item"
              >
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <q-icon :name="item.icon" size="22px" />
                </q-item-section>
                <q-item-section class="nav-label text-weight-bold">
                  {{ item.label }}
                </q-item-section>
              </q-item>

              <!-- Expansion Item (Nested Menu) -->
              <q-expansion-item
                v-else
                :icon="item.icon"
                :label="item.label"
                class="nav-item expansion-item"
                header-class="nav-label text-weight-bold q-px-none"
                expand-icon-class="text-white opacity-70"
              >
                <q-list class="nav-submenu">
                  <q-item
                    v-for="(child, childIndex) in item.children"
                    :key="childIndex"
                    :to="child.path"
                    clickable
                    v-ripple
                    active-class="nav-child-active"
                    class="nav-item nav-child-item"
                  >
                    <q-item-section class="nav-label text-weight-medium">
                      {{ child.label }}
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-expansion-item>

            </template>
          </q-list>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'

const router = useRouter()
const $q = useQuasar()
const drawerOpen = ref(true)

const userName = ref('Vendor')
const userProfilePicture = ref(null)
const storeName = ref('Loading...') // Initial state while fetching from API

onMounted(async () => {
  try {
    const res = await api.get('/user')
    
    // DEBUG: Look in your browser's Developer Console (F12) to see exactly what Laravel returns!
    console.log('Backend Response Data:', res.data)

    if (res.data && res.data.user) {
      const user = res.data.user
      userName.value = user.full_name || 'Vendor'
      userProfilePicture.value = user.profile_picture_url || null
      
      // Dynamic matching logic. It checks various common property names.
      // Once you check the console.log above, you can delete the checks you don't need.
      storeName.value = user.store_name 
        || user.store?.name 
        || user.shop?.name 
        || user.vendor?.store_name 
        || user.vendor?.name
        || res.data.store_name
        || 'Unnamed Store' 
    }
  } catch (error) {
    console.error('Error fetching user info:', error)
    userName.value = 'Vendor'
    storeName.value = 'Store Unavailable'
  }
})

const navItems = [
  { label: 'Dashboard', icon: 'grid_view', path: '/vendor/dashboard' },
  { 
    label: 'Orders', 
    icon: 'shopping_bag', 
    children: [
      { label: 'Order List', path: '/vendor/orders/list' },
      { label: 'Customer Order', path: '/vendor/orders/customers' }
    ] 
  },
  { 
    label: 'Products', 
    icon: 'inventory_2', 
    children: [
      { label: 'Product List', path: '/vendor/products/list' },
      { label: 'Product Category', path: '/vendor/products/categories' }
    ] 
  },
  { label: 'Sales', icon: 'trending_up', path: '/vendor/sales' }
]

const handleLogout = () => {
  $q.dialog({
    class: 'premium-glass-card',
    title: 'Confirm Logout',
    message: 'Are you sure you want to log out of your account?',
    cancel: { flat: true, color: 'blue-grey-6', label: 'Cancel', noCaps: true, class: 'q-px-md text-weight-bold' },
    ok: { unelevated: true, color: 'red-8', label: 'Logout', noCaps: true, class: 'q-px-md text-weight-bold shadow-2' },
    persistent: true
  }).onOk(async () => {
    try {
      await api.post('/logout')
    } catch {
      // Token may already be invalid
    }
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_role')
    router.push('/login')
  })
}
</script>

<style scoped>
/* Core Layout Utilities */
.vendor-layout {
  font-family: 'Inter', 'Roboto', Arial, sans-serif;
}
.bg-slate-50 {
  background-color: #f8fafc;
}
.tracking-tight {
  letter-spacing: -0.02em;
}
.hover-scale {
  transition: transform 0.2s ease;
}
.hover-scale:hover {
  transform: scale(1.02);
}

/* ==========================================================
   HEADER 
========================================================== */
.premium-header {
  background: linear-gradient(90deg, #991B1B 0%, #450A0A 100%) !important;
  color: #ffffff !important;
}
.border-bottom-dark {
  border-bottom: 1px solid rgba(0, 0, 0, 0.15);
}
.menu-toggle-btn {
  transition: all 0.2s ease;
}
.menu-toggle-btn:hover {
  background: rgba(255, 255, 255, 0.15);
}
.profile-btn {
  border-radius: 12px;
  transition: all 0.2s ease;
  padding: 4px 12px;
}
.profile-btn:hover {
  background: rgba(255, 255, 255, 0.15);
}
.profile-glass-menu {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 35px rgba(15, 23, 42, 0.15);
}
.menu-item {
  border-radius: 8px;
  margin: 0 8px;
  transition: all 0.2s;
}
.menu-item:hover {
  background: #f1f5f9;
}
.logout-item:hover {
  background: #fef2f2; 
}

/* ==========================================================
   SIDEBAR (Using :deep to pierce Quasar's shadow DOM)
========================================================== */
:deep(.q-drawer.premium-drawer),
:deep(.premium-drawer) {
  background: linear-gradient(180deg, #991B1B 0%, #450A0A 100%) !important; 
  color: #ffffff !important; 
  border-right: 1px solid #3f0909 !important;
}

.sidebar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  position: relative;
  z-index: 1;
  background: transparent !important;
}

/* ==========================================================
   LOGO - Enhanced contrast for dark red background
========================================================== */
.sidebar-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 20px 16px;
}
.logo-glass-wrapper {
  background: rgba(255, 255, 255, 0.95);
  padding: 10px 20px;
  border-radius: 16px;
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255,255,255,1);
  display: inline-flex;
  justify-content: center;
  align-items: center;
}
.logo-img {
  width: 150px;
  height: auto;
  object-fit: contain;
  display: block;
}

/* ==========================================================
   NAVIGATION ITEMS
========================================================== */
.nav-container {
  flex-grow: 1;
  overflow-y: auto;
}
.nav-container::-webkit-scrollbar { width: 4px; }
.nav-container::-webkit-scrollbar-track { background: transparent; }
.nav-container::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

.nav-list {
  padding: 0 16px 24px 16px;
}
.nav-item {
  border-radius: 10px;
  color: rgba(255, 255, 255, 0.75);
  margin-bottom: 6px;
  padding: 12px 16px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.nav-item :deep(.q-icon) {
  color: rgba(255, 255, 255, 0.75);
  transition: all 0.25s ease;
}
.nav-label {
  font-size: 14px;
  letter-spacing: 0.02em;
}

/* Hover States */
.nav-item:hover:not(.expansion-item) {
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
  transform: translateX(4px);
}
.nav-item:hover:not(.expansion-item) :deep(.q-icon) {
  color: #ffffff;
}

/* Active State */
.nav-active {
  background: #ffffff !important;
  color: #991B1B !important; 
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
  transform: translateX(4px);
}
.nav-active :deep(.q-icon) {
  color: #991B1B !important;
}

/* Nested Navigation */
.expansion-item {
  padding: 0; 
}
.expansion-item :deep(.q-item) {
  padding: 12px 16px;
  border-radius: 10px;
}
.expansion-item :deep(.q-item:hover) {
  background: rgba(255, 255, 255, 0.15);
}
.nav-submenu {
  position: relative;
  margin-left: 24px;
  padding-left: 12px;
  border-left: 1px solid rgba(255, 255, 255, 0.2);
  margin-top: 4px;
  margin-bottom: 8px;
}
.nav-child-item {
  padding: 10px 16px;
  margin-bottom: 4px;
  font-size: 13px;
  border-radius: 8px;
}
.nav-child-item:hover {
  transform: translateX(4px);
}
.nav-child-active {
  background: rgba(255, 255, 255, 0.2) !important;
  color: #ffffff !important;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.25);
}

.min-w-0 {
  min-width: 0 !important;
}
.opacity-70 {
  opacity: 0.7;
}
</style>