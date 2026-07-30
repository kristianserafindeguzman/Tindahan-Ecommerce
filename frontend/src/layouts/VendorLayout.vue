<template>
  <q-layout view="hHh LpR fFf" class="vendor-layout bg-grey-1">

    <!-- HEADER (Always visible for Vendor) -->
    <q-header class="vendor-header" elevated style="background: #ffffff; color: #333;">
      <q-toolbar>
        <q-btn
          flat
          dense
          round
          icon="menu"
          color="dark"
          @click="drawerOpen = !drawerOpen"
        />
        
        <div class="row items-center cursor-pointer" @click="$router.push('/vendor/dashboard')">
          <q-toolbar-title class="header-title text-weight-bold text-dark q-ml-sm">
            Vendor Center
          </q-toolbar-title>
        </div>

        <q-space />

        <!-- PROFILE DROPDOWN -->
        <q-btn flat no-caps class="profile-btn q-px-sm">
          <div class="row items-center no-wrap">
            <q-avatar size="32px" class="q-mr-sm">
              <img :src="userProfilePicture" v-if="userProfilePicture" />
              <q-icon name="person" color="grey-7" size="24px" v-else />
            </q-avatar>
            <div class="text-weight-medium text-dark">{{ userName }}</div>
            <q-icon name="keyboard_arrow_down" size="20px" class="q-ml-xs text-dark" />
          </div>

          <q-menu auto-close class="profile-menu">
            <q-list style="min-width: 150px">
              <q-item clickable v-ripple @click="router.push('/vendor/profile')">
                <q-item-section avatar>
                  <q-icon name="manage_accounts" color="primary" />
                </q-item-section>
                <q-item-section>Profile Settings</q-item-section>
              </q-item>
              
              <q-separator />

              <q-item clickable v-ripple @click="handleLogout">
                <q-item-section avatar>
                  <q-icon name="logout" color="red-8" />
                </q-item-section>
                <q-item-section class="text-red-8">Logout</q-item-section>
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
          <template v-for="item in navItems" :key="item.label">
            
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
              <q-item-section class="nav-label text-weight-medium">
                {{ item.label }}
              </q-item-section>
            </q-item>

            <!-- Expansion Item -->
            <q-expansion-item
              v-else
              :icon="item.icon"
              :label="item.label"
              class="nav-item expansion-item"
              header-class="nav-label text-weight-medium"
              expand-icon-class="text-white"
            >
              <q-list class="q-pl-md">
                <q-item
                  v-for="child in item.children"
                  :key="child.label"
                  :to="child.path"
                  clickable
                  v-ripple
                  active-class="nav-active"
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

onMounted(async () => {
  try {
    const res = await api.get('/user')
    if (res.data && res.data.user) {
      const user = res.data.user
      userName.value = user.full_name || 'Vendor'
      userProfilePicture.value = user.profile_picture_url || null
    }
  } catch (error) {
    userName.value = 'Vendor'
  }
})

const navItems = [
  { label: 'Dashboard', icon: 'grid_view', path: '/vendor/dashboard' },
  { 
    label: 'Orders', 
    icon: 'shopping_bag', 
    children: [
      { label: 'Order list', path: '/vendor/orders/list' },
      { label: 'Customer order', path: '/vendor/orders/customers' }
    ] 
  },
  { 
    label: 'Products', 
    icon: 'inventory_2', 
    children: [
      { label: 'Product list', path: '/vendor/products/list' },
      { label: 'Product category', path: '/vendor/products/categories' }
    ] 
  },
  { label: 'Sales', icon: 'trending_up', path: '/vendor/sales' }
]

const handleLogout = () => {
  $q.dialog({
    class: 'glass-logout-dialog',
    title: 'Confirm Logout',
    message: 'Are you sure you want to log out of your account?',
    cancel: { flat: true, color: 'grey-7', label: 'Cancel', noCaps: true, class: 'glass-logout-btn-cancel q-px-md' },
    ok: { unelevated: true, label: 'Logout', noCaps: true, class: 'glass-logout-btn-ok q-px-md' },
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
.vendor-layout {
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
  width: 190px;
  height: 65px;
  object-fit: contain;
  display: block;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
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
  color: rgba(255, 255, 255, 0.75);
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

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.nav-item:hover :deep(.q-icon) {
  color: #ffffff;
}

.nav-active {
  background: #ffffff !important;
  color: #A71D20 !important;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.nav-active :deep(.q-icon) {
  color: #A71D20 !important;
}

.min-w-0 {
  min-width: 0 !important;
}

/* ==========================================================
   HEADER
========================================================== */
.profile-btn {
  border-radius: 8px;
  transition: background 0.3s;
}
.profile-btn:hover {
  background: rgba(0, 0, 0, 0.05);
}
.profile-menu {
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
</style>
