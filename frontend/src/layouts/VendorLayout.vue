<template>
  <q-layout view="hHh LpR fFf" class="vendor-layout bg-slate-50">

    <!-- ================= PREMIUM LOADING SCREEN ================= -->
    <VendorLoading :showing="isGlobalLoading" message="Preparing your workspace..." />

    <!-- ================= DESKTOP HEADER ================= -->
    <q-header v-if="!$q.screen.lt.md" elevated class="premium-header border-bottom-dark">
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

    <!-- ================= MOBILE HEADER (Red Gradient) ================= -->
    <q-header v-else elevated class="premium-header z-top border-bottom-dark" style="z-index: 2000;">
      <q-toolbar class="q-px-md" style="min-height: 64px;">
        
        <!-- App Logo (Left - Enclosed in white to pop against red) -->
        <div 
          class="bg-white flex flex-center shadow-1 cursor-pointer hover-scale" 
          style="border-radius: 8px; padding: 6px 12px;"
          @click="router.push('/vendor/dashboard')"
        >
          <img 
            src="@/assets/tindahan-mobile.png" 
            alt="Tindahan Logo" 
            style="height: 24px; object-fit: contain;" 
          />
        </div>

        <q-space />

        <!-- Notifications & Logout (Right) -->
        <div class="row items-center q-gutter-x-sm">
          
          <!-- Interactive Notifications Button -->
          <q-btn flat round dense icon="notifications_none" color="white" class="menu-toggle-btn relative-position">
            <q-badge color="amber-8" text-color="white" floating rounded style="padding: 3px 5px; font-weight: 800; border: 1.5px solid #991B1B;">2</q-badge>
            
            <q-menu class="premium-glass-menu no-shadow" :offset="[0, 15]" anchor="bottom right" self="top right" style="border-radius: 16px; width: 360px; max-width: 90vw; overflow: hidden; border: 1px solid rgba(226, 232, 240, 0.8);">
              <!-- Header -->
              <div class="q-pa-md bg-white border-bottom-light row items-center justify-between sticky-top z-top">
                <div class="row items-center">
                  <div style="width: 4px; height: 18px; background-color: #b91c1c; border-radius: 2px;" class="q-mr-sm"></div>
                  <div class="text-weight-bolder text-slate-800 text-subtitle1 leading-none tracking-tight">Notifications</div>
                </div>
                <q-btn flat dense no-caps label="Mark as read" color="blue-grey-4" size="12px" class="text-weight-bold q-px-sm hover-text-dark" style="border-radius: 6px;" />
              </div>
              
              <!-- List -->
              <q-list class="scroll bg-slate-50" style="max-height: 50vh;">
                <!-- Unread Item -->
                <q-item clickable v-ripple class="q-pa-md notification-item unread-notification">
                  <q-item-section avatar top class="q-pr-sm min-w-0">
                    <div class="notification-icon-box bg-red-50 text-red-9 border-red-light">
                      <q-icon name="o_shopping_bag" size="22px" />
                    </div>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-bold text-slate-800 text-body2">New Order Received</q-item-label>
                    <q-item-label caption class="text-slate-500 q-mt-xs font-medium leading-snug">Order #1024 has been placed and is waiting for your confirmation.</q-item-label>
                    <q-item-label caption class="text-brand-red text-weight-bold q-mt-sm" style="font-size: 11px;">Just now</q-item-label>
                  </q-item-section>
                  <q-item-section side top>
                    <div class="unread-dot"></div>
                  </q-item-section>
                </q-item>

                <!-- Read Item -->
                <q-item clickable v-ripple class="q-pa-md notification-item">
                  <q-item-section avatar top class="q-pr-sm min-w-0">
                    <div class="notification-icon-box bg-white border-slate-light text-blue-grey-5 shadow-soft">
                      <q-icon name="o_inventory_2" size="22px" />
                    </div>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-bold text-slate-700 text-body2">Low Stock Alert</q-item-label>
                    <q-item-label caption class="text-slate-500 q-mt-xs font-medium leading-snug">"Premium Rice 5kg" is running low on inventory (Only 2 left).</q-item-label>
                    <q-item-label caption class="text-blue-grey-4 text-weight-bold q-mt-sm" style="font-size: 11px;">2 hours ago</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
              
              <!-- Footer -->
              <div class="q-pa-sm bg-white border-top-light text-center sticky-bottom z-top">
                <q-btn unelevated no-caps label="View all notifications" color="red-50" text-color="red-9" class="full-width text-weight-bold transition-ease shadow-1" style="border-radius: 10px;" />
              </div>
            </q-menu>
          </q-btn>
          
          <!-- Direct Logout Button -->
          <q-btn flat round dense icon="logout" color="white" class="menu-toggle-btn q-ml-xs" @click="handleLogout" />
        </div>

      </q-toolbar>
    </q-header>

    <!-- ================= LEFT SIDEBAR (Hidden on Mobile) ================= -->
    <q-drawer
      v-model="drawerOpen"
      :show-if-above="!$q.screen.lt.md"
      :width="280"
      :breakpoint="1024"
      class="premium-drawer"
    >
      <div class="sidebar-content">

        <!-- LOGO -->
        <div class="sidebar-logo q-mt-md">
          <div class="logo-glass-wrapper">
            <img src="@/assets/tindahan-mobile.png" alt="Tindahan" class="logo-img" />
          </div>
        </div>

        <!-- NAV ITEMS -->
        <div class="nav-container q-mt-lg">
          <q-list class="nav-list">
            <template v-for="(item, index) in navItems" :key="index">
              
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

    <!-- ================= MAIN CONTENT ================= -->
    <q-page-container :class="{ 'mobile-pb': $q.screen.lt.md }">
      <router-view />
    </q-page-container>

    <!-- ================= MINIMALIST MOBILE BOTTOM NAVIGATION ================= -->
    <q-footer v-if="$q.screen.lt.md" class="bg-white text-slate-500" style="box-shadow: 0 -4px 20px rgba(0,0,0,0.06); z-index: 2000;">
      <div class="row no-wrap items-center justify-around bottom-nav-container relative-position" style="padding-bottom: env(safe-area-inset-bottom);">
        
        <!-- Orders (Animated Premium Sub-menu) -->
        <q-btn flat round dense no-caps class="nav-icon-btn no-hover" :class="$route.path.includes('/vendor/orders') ? 'text-red-9' : 'text-grey-5'">
          <q-icon :name="$route.path.includes('/vendor/orders') ? 'receipt_long' : 'o_receipt_long'" size="28px" class="transition-transform" />
          
          <q-menu anchor="top middle" self="bottom middle" transition-show="jump-up" transition-hide="jump-down" class="premium-glass-menu no-shadow" :offset="[0, 16]">
            <q-list style="min-width: 210px" class="q-py-xs bg-slate-50">
              <q-item clickable v-ripple to="/vendor/orders/list" active-class="active-nav-popup" class="popup-nav-item">
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <div class="popup-icon-box"><q-icon name="list_alt" size="20px"/></div>
                </q-item-section>
                <q-item-section class="text-weight-bold text-subtitle2">Order List</q-item-section>
              </q-item>
              
              <q-item clickable v-ripple to="/vendor/orders/customers" active-class="active-nav-popup" class="popup-nav-item">
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <div class="popup-icon-box"><q-icon name="people_outline" size="20px"/></div>
                </q-item-section>
                <q-item-section class="text-weight-bold text-subtitle2">Customer Order</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>

        <!-- Products (Animated Premium Sub-menu) -->
        <q-btn flat round dense no-caps class="nav-icon-btn no-hover" :class="$route.path.includes('/vendor/products') ? 'text-red-9' : 'text-grey-5'">
          <q-icon :name="$route.path.includes('/vendor/products') ? 'inventory_2' : 'o_inventory_2'" size="28px" class="transition-transform" />
          
          <q-menu anchor="top middle" self="bottom middle" transition-show="jump-up" transition-hide="jump-down" class="premium-glass-menu no-shadow" :offset="[0, 16]">
            <q-list style="min-width: 210px" class="q-py-xs bg-slate-50">
              <q-item clickable v-ripple to="/vendor/products/list" active-class="active-nav-popup" class="popup-nav-item">
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <div class="popup-icon-box"><q-icon name="format_list_bulleted" size="20px"/></div>
                </q-item-section>
                <q-item-section class="text-weight-bold text-subtitle2">Product List</q-item-section>
              </q-item>
              
              <q-item clickable v-ripple to="/vendor/products/categories" active-class="active-nav-popup" class="popup-nav-item">
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <div class="popup-icon-box"><q-icon name="category" size="20px"/></div>
                </q-item-section>
                <q-item-section class="text-weight-bold text-subtitle2">Categories</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>

        <!-- CENTER FLOATING HOME BUTTON -->
        <div class="nav-placeholder flex flex-center relative-position">
          <div class="center-cutout flex flex-center">
            <q-btn 
              round 
              unelevated 
              to="/vendor/dashboard"
              class="shadow-4 pulse-animation center-home-btn"
              :class="$route.path === '/vendor/dashboard' ? 'bg-red-10' : 'bg-brand-red'"
            >
              <q-icon name="o_home" size="28px" color="white" />
            </q-btn>
          </div>
        </div>

        <!-- Sales -->
        <q-btn flat round dense no-caps class="nav-icon-btn no-hover" :class="$route.path.includes('/vendor/sales') ? 'text-red-9' : 'text-grey-5'" to="/vendor/sales">
          <q-icon :name="$route.path.includes('/vendor/sales') ? 'pie_chart' : 'pie_chart_outline'" size="28px" class="transition-transform" />
        </q-btn>

        <!-- Profile -->
        <q-btn flat round dense no-caps class="nav-icon-btn no-hover" :class="$route.path.includes('/vendor/profile') ? 'text-red-9' : 'text-grey-5'" to="/vendor/profile">
          <q-icon :name="$route.path.includes('/vendor/profile') ? 'person' : 'person_outline'" size="30px" class="transition-transform" />
        </q-btn>
        
      </div>
    </q-footer>

    <!-- ================= NATIVE ACTION SHEET LOGOUT ================= -->
    <q-dialog v-model="showLogoutModal" persistent backdrop-filter="blur(4px)" :position="$q.screen.lt.md ? 'bottom' : 'standard'">
      <q-card 
        class="bg-white text-center overflow-hidden" 
        :style="$q.screen.lt.md ? 'width: 100%; border-radius: 28px 28px 0 0; padding-bottom: calc(16px + env(safe-area-inset-bottom)); box-shadow: 0 -10px 40px rgba(0,0,0,0.1);' : 'width: 400px; max-width: 90vw; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);'"
      >
        <q-card-section class="q-pt-xl q-pb-md">
          <q-icon name="logout" size="48px" color="red-9" class="q-mb-md opacity-90" />
          <h3 class="text-h5 text-weight-bolder text-slate-800 q-mt-none q-mb-sm tracking-tight">Ready to leave?</h3>
          <p class="text-body1 text-slate-500 q-mb-none font-medium q-px-md leading-snug">
            Are you sure you want to securely log out of your Tindahan vendor account?
          </p>
        </q-card-section>
        
        <q-card-actions class="q-px-lg q-pt-sm q-pb-lg column q-gutter-y-md">
          <q-btn 
            unelevated 
            label="Yes, Log me out" 
            color="red-9" 
            @click="confirmLogout" 
            no-caps 
            class="full-width text-weight-bold" 
            size="lg" 
            style="border-radius: 14px;"
            :loading="isLoggingOut" 
          />
          <q-btn 
            flat 
            label="Cancel" 
            text-color="blue-grey-6" 
            v-close-popup 
            no-caps 
            class="full-width text-weight-bold q-ma-none" 
            size="lg" 
            style="border-radius: 14px; background-color: #f8fafc;" 
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-layout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'

// 1. Import the newly created loading component
import VendorLoading from '@/components/VendorLoading.vue'

const router = useRouter()

const drawerOpen = ref(false)
const showLogoutModal = ref(false)
const isLoggingOut = ref(false)

// 2. Add the global loading state variable
const isGlobalLoading = ref(true)

const userName = ref('Vendor')
const userProfilePicture = ref(null)
const storeName = ref('Loading...')

onMounted(async () => {
  try {
    isGlobalLoading.value = true // Ensure loading screen is visible at start

    const res = await api.get('/user')
    if (res.data && res.data.user) {
      const user = res.data.user
      userName.value = user.full_name || 'Vendor'
      userProfilePicture.value = user.profile_picture_url || null
      storeName.value = user.store?.store_name || user.store_name || user.shop?.name || res.data.store_name || 'My Store' 
    }
  } catch (error) {
    console.error('Error fetching user info:', error)
    userName.value = 'Vendor'
    storeName.value = 'Store Unavailable'
  } finally {
    // 3. Hide loading screen when data fetch is complete
    isGlobalLoading.value = false
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
  showLogoutModal.value = true
}

const confirmLogout = async () => {
  isLoggingOut.value = true
  try {
    await api.post('/logout')
  } catch { 
    // Ignore error, token may already be invalid
  }
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  localStorage.removeItem('auth_role')
  router.push('/login')
  isLoggingOut.value = false
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
.leading-none { line-height: 1; }
.hover-scale {
  transition: transform 0.2s ease;
}
.hover-scale:hover {
  transform: scale(1.02);
}
.bg-brand-red {
  background-color: #B91C1C !important;
}

/* Typography Extensions */
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }
.text-slate-600 { color: #475569; }
.text-slate-500 { color: #64748b; }
.text-slate-400 { color: #94a3b8; }
.font-medium { font-weight: 500; }
.leading-snug { line-height: 1.375; }

/* ==========================================================
   MOBILE UTILITIES & ANIMATIONS
========================================================== */
.border-bottom-dark { border-bottom: 1px solid #3f0909; }
.border-bottom-light { border-bottom: 1px solid rgba(226, 232, 240, 0.6); }
.border-top-light { border-top: 1px solid rgba(226, 232, 240, 0.6); }
.border-slate-light { border: 1px solid rgba(226, 232, 240, 0.8); }
.border-red-light { border: 1px solid rgba(254, 202, 202, 0.6); }
.shadow-soft { box-shadow: 0 2px 8px rgba(15,23,42,0.04); }

/* Remove sticky hover effect on mobile buttons */
.no-hover :deep(.q-focus-helper) { display: none !important; }

/* Bottom Nav Container */
.bottom-nav-container { 
  height: 64px; 
}

/* Base Nav Icon Button */
.nav-icon-btn {
  width: 50px;
  height: 50px;
  transition: transform 0.15s ease-in-out;
}
.nav-icon-btn:active { 
  transform: scale(0.85); 
}

/* Perfect Center Flex Alignment for the Cutout */
.nav-placeholder {
  width: 60px; /* Reserves standard width in flexbox */
  height: 100%;
}

.center-cutout {
  position: absolute;
  top: -24px; /* Protrude cleanly above the top of the navbar */
  width: 68px;
  height: 68px;
  background: #ffffff; /* Matches navbar */
  border-radius: 50%;
  box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.05); /* Soft outer glow to sell the cutout */
  z-index: 2001; /* Keep it above the other nav items */
}

/* Central Button without conflicting absolute positioning */
.center-home-btn {
  width: 54px; 
  height: 54px; 
}

/* Bounce Animation */
.pulse-animation {
  animation: entranceBounce 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
  transition: transform 0.15s ease-in-out, background-color 0.2s;
  position: absolute;
}
.pulse-animation:active { transform: scale(0.90); }

@keyframes entranceBounce {
  0% { transform: scale(0.5); opacity: 0; }
  100% { transform: scale(1); opacity: 1; }
}

/* Ensure page content isn't hidden behind the fixed mobile bottom nav */
.mobile-pb {
  padding-bottom: calc(85px + env(safe-area-inset-bottom));
}

/* ==========================================================
   HEADER & NOTIFICATIONS DROPDOWN
========================================================== */
.premium-header {
  background: linear-gradient(90deg, #991B1B 0%, #450A0A 100%) !important;
  color: #ffffff !important;
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
.hover-text-dark:hover { color: #1e293b !important; }

/* Premium Glass Styling for Global Menus */
.premium-glass-menu {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 35px rgba(15, 23, 42, 0.15) !important;
}

/* Profile Dropdown Items */
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

/* Notification UI */
.notification-item {
  transition: background 0.2s ease;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}
.notification-item:hover {
  background: #ffffff;
}
.unread-notification {
  background: #fef2f2;
  border-left: 3px solid #B91C1C;
}
.unread-notification:hover {
  background: #fee2e2;
}
.notification-icon-box {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.unread-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #B91C1C;
  box-shadow: 0 0 8px rgba(185, 28, 28, 0.4);
}
.sticky-top { position: sticky; top: 0; }
.sticky-bottom { position: sticky; bottom: 0; }
.transition-ease { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }

/* ==========================================================
   BOTTOM NAV POPUP MENUS
========================================================== */
.popup-nav-item {
  border-radius: 12px;
  margin: 4px 8px;
  transition: all 0.2s ease;
  color: #334155; 
}
.popup-nav-item:hover {
  background: #f8fafc;
}
.active-nav-popup {
  background: #fef2f2 !important;
  color: #B91C1C !important;
}
.popup-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #ffffff;
  border: 1px solid rgba(226, 232, 240, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  box-shadow: 0 2px 6px rgba(15,23,42,0.04);
  transition: all 0.2s ease;
}
.active-nav-popup .popup-icon-box {
  background: #B91C1C;
  border-color: #B91C1C;
  color: #ffffff;
  box-shadow: 0 4px 10px rgba(185, 28, 28, 0.2);
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
   NAVIGATION ITEMS (DESKTOP)
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

.min-w-0 { min-width: 0 !important; }
.opacity-70 { opacity: 0.7; }
</style>