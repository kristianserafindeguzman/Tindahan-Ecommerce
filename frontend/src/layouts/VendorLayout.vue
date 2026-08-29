<template>
  <q-layout view="hHh LpR fFf" class="vendor-layout">

    <!-- ================= SARI-SARI STOREFRONT LOADING SCREEN ================= -->
    <transition name="fade-fast">
      <div v-if="isGlobalLoading" class="sari-loading-backdrop fixed-full flex flex-center z-max">
        <div class="sari-loading-card column flex-center text-center">
          
          <!-- Authentic Store Awning & Logo Frame -->
          <div class="storefront-card-top column flex-center q-mb-md">
            <div class="awning-roof row no-wrap">
              <span class="awning-red"></span><span class="awning-white"></span>
              <span class="awning-red"></span><span class="awning-white"></span>
              <span class="awning-red"></span><span class="awning-white"></span>
              <span class="awning-red"></span>
            </div>
            
            <div class="store-badge-frame flex flex-center">
              <img 
                src="@/assets/tindahan-mobile.png" 
                alt="Tindahan Logo" 
                class="loading-store-logo" 
                @error="$event.target.style.display='none'"
              />
              <div class="store-logo-fallback row items-center no-wrap">
                <q-icon name="storefront" size="28px" color="red-9" class="q-mr-xs" />
                <span class="text-weight-bolder text-red-9 text-subtitle1">Tindahan</span>
              </div>
            </div>
          </div>
          
          <div class="text-h6 text-weight-bolder text-dark tracking-tight row items-center justify-center no-wrap">
            <span>Opening Your Store</span>
            <span class="loading-dots"></span>
          </div>
          
          <div class="text-caption text-grey-7 q-mt-xs text-weight-medium">
            Setting up your sari-sari store dashboard
          </div>

          <div class="counter-progress-track q-mt-md">
            <div class="counter-progress-fill"></div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ================= DESKTOP HEADER (Brand Red Sari-Sari Storefront) ================= -->
    <q-header v-if="!$q.screen.lt.md" elevated class="sari-brand-header">
      <q-toolbar class="q-px-lg toolbar-desktop">
        
        <!-- Left: Drawer Toggle & Brand Logo -->
        <div class="row items-center no-wrap">
          <q-btn
            flat
            dense
            round
            icon="menu"
            color="white"
            class="header-action-btn q-mr-md"
            @click="drawerOpen = !drawerOpen"
          />
          
          <!-- Desktop Logo Plate -->
          <div 
            class="header-logo-card flex flex-center cursor-pointer q-mr-md"
            @click="router.push('/vendor/dashboard')"
          >
            <img 
              src="@/assets/tindahan-mobile.png" 
              alt="Tindahan Logo" 
              class="header-logo-img" 
              @error="$event.target.style.display='none'"
            />
            <div class="header-logo-fallback row items-center no-wrap">
              <q-icon name="storefront" size="20px" color="red-9" class="q-mr-xs" />
              <span class="text-weight-bolder text-red-9 text-body2">Tindahan</span>
            </div>
          </div>

          <!-- Store Title Tag -->
          <div class="store-title-badge column justify-center q-px-md q-py-xs">
            <span class="badge-sub-label">STORE MANAGEMENT</span>
            <span class="badge-store-title ellipsis">{{ storeName }}</span>
          </div>
        </div>

        <q-space />

        <!-- Right: Notification Bell & Profile Dropdown -->
        <div class="row items-center no-wrap q-gutter-x-sm">
          
          <!-- Desktop Notification Bell -->
          <q-btn flat round dense icon="notifications" color="white" class="header-action-btn relative-position">
            <q-badge color="amber-9" text-color="white" floating rounded class="text-weight-bolder">2</q-badge>
            
            <q-menu class="solid-paper-menu no-shadow" :offset="[0, 12]" anchor="bottom right" self="top right" style="border-radius: 12px; width: 340px; border: 2px solid #e2e8f0;">
              <div class="q-pa-md bg-white border-bottom-solid row items-center justify-between sticky-top z-top">
                <div class="row items-center">
                  <div style="width: 4px; height: 16px; background-color: #b91c1c; border-radius: 2px;" class="q-mr-sm"></div>
                  <div class="text-weight-bolder text-dark text-subtitle2">Notifications</div>
                </div>
                <q-btn flat dense no-caps label="Mark all read" color="grey-7" size="11px" class="text-weight-bold" />
              </div>
              
              <q-list class="scroll bg-grey-1" style="max-height: 50vh;">
                <q-item clickable v-ripple class="q-pa-md notification-card-item unread-paper-notification">
                  <q-item-section avatar top class="q-pr-sm min-w-0">
                    <div class="solid-icon-stamp bg-red-1 text-red-9">
                      <q-icon name="shopping_bag" size="20px" />
                    </div>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-bolder text-dark text-body2">New Order Placed</q-item-label>
                    <q-item-label caption class="text-grey-7 q-mt-xs font-medium leading-snug">Order #1024 has been submitted by customer.</q-item-label>
                    <q-item-label caption class="text-red-9 text-weight-bold q-mt-xs" style="font-size: 11px;">Just now</q-item-label>
                  </q-item-section>
                  <q-item-section side top>
                    <div class="unread-solid-tag"></div>
                  </q-item-section>
                </q-item>
              </q-list>
              
              <div class="q-pa-sm bg-white border-top-solid text-center sticky-bottom z-top">
                <q-btn unelevated no-caps label="View All Notifications" color="red-1" text-color="red-9" class="full-width text-weight-bold" style="border-radius: 8px;" />
              </div>
            </q-menu>
          </q-btn>

          <!-- Profile Trigger -->
          <q-btn flat no-caps class="vendor-profile-btn q-px-sm q-py-xs">
            <div class="row items-center no-wrap">
              <q-avatar size="34px" class="profile-frame q-mr-sm">
                <img :src="userProfilePicture" v-if="userProfilePicture" />
                <q-icon name="person" color="red-9" size="20px" v-else />
              </q-avatar>
              
              <div class="column text-left q-mr-sm">
                <span class="text-weight-bolder text-white ellipsis text-body2 leading-tight">{{ userName }}</span>
                <span class="text-caption text-red-2 leading-tight">Store Owner</span>
              </div>
              
              <q-icon name="keyboard_arrow_down" size="18px" color="white" />
            </div>

            <q-menu auto-close class="solid-paper-menu" :offset="[0, 10]">
              <div class="q-pa-md bg-grey-2 border-bottom-solid row items-center no-wrap">
                <q-avatar size="40px" class="q-mr-md bg-white border-solid-red">
                  <img :src="userProfilePicture" v-if="userProfilePicture" />
                  <q-icon name="person" color="red-9" size="22px" v-else />
                </q-avatar>
                <div class="column ellipsis">
                  <span class="text-weight-bolder text-dark text-body2 ellipsis">{{ userName }}</span>
                  <span class="text-caption text-grey-7 ellipsis">{{ storeName }}</span>
                </div>
              </div>

              <q-list style="min-width: 190px" class="q-py-xs">
                <q-item clickable v-ripple @click="router.push('/vendor/profile')" class="paper-menu-item q-my-xs">
                  <q-item-section avatar style="min-width: 32px">
                    <q-icon name="manage_accounts" color="grey-8" size="18px" />
                  </q-item-section>
                  <q-item-section class="text-weight-bold text-dark" style="font-size: 13px;">Profile Settings</q-item-section>
                </q-item>
                
                <q-separator class="q-my-xs" />

                <q-item clickable v-ripple @click="handleLogout" class="paper-menu-item logout-paper-item q-my-xs">
                  <q-item-section avatar style="min-width: 32px">
                    <q-icon name="logout" color="red-9" size="18px" />
                  </q-item-section>
                  <q-item-section class="text-weight-bold text-red-9" style="font-size: 13px;">Sign Out</q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>
        </div>
      </q-toolbar>
    </q-header>

    <!-- ================= MOBILE HEADER ================= -->
    <q-header v-else elevated class="sari-brand-header z-top">
      <q-toolbar class="q-px-md toolbar-mobile">
        
        <!-- Mobile Logo Plate -->
        <div 
          class="header-logo-card flex flex-center cursor-pointer" 
          @click="router.push('/vendor/dashboard')"
        >
          <img 
            src="@/assets/tindahan-mobile.png" 
            alt="Tindahan Logo" 
            class="header-logo-img"
            @error="$event.target.style.display='none'"
          />
          <div class="header-logo-fallback row items-center no-wrap">
            <q-icon name="storefront" size="18px" color="red-9" class="q-mr-xs" />
            <span class="text-weight-bolder text-red-9 text-body2">Tindahan</span>
          </div>
        </div>

        <q-space />

        <div class="row items-center no-wrap q-gutter-x-xs">
          <!-- Notification Button -->
          <q-btn flat round dense icon="notifications" color="white" class="header-action-btn relative-position">
            <q-badge color="amber-9" text-color="white" floating rounded class="text-weight-bolder">2</q-badge>
            
            <q-menu class="solid-paper-menu no-shadow" :offset="[0, 12]" anchor="bottom right" self="top right" style="border-radius: 12px; width: 330px; max-width: 90vw; border: 2px solid #e2e8f0;">
              <div class="q-pa-md bg-white border-bottom-solid row items-center justify-between sticky-top z-top">
                <div class="row items-center">
                  <div style="width: 4px; height: 16px; background-color: #b91c1c; border-radius: 2px;" class="q-mr-sm"></div>
                  <div class="text-weight-bolder text-dark text-subtitle2">Notifications</div>
                </div>
                <q-btn flat dense no-caps label="Mark all read" color="grey-7" size="11px" class="text-weight-bold" />
              </div>
              
              <q-list class="scroll bg-grey-1" style="max-height: 50vh;">
                <q-item clickable v-ripple class="q-pa-md notification-card-item unread-paper-notification">
                  <q-item-section avatar top class="q-pr-sm min-w-0">
                    <div class="solid-icon-stamp bg-red-1 text-red-9">
                      <q-icon name="shopping_bag" size="20px" />
                    </div>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-bolder text-dark text-body2">New Order Placed</q-item-label>
                    <q-item-label caption class="text-grey-7 q-mt-xs font-medium leading-snug">Order #1024 has been submitted by customer.</q-item-label>
                    <q-item-label caption class="text-red-9 text-weight-bold q-mt-xs" style="font-size: 11px;">Just now</q-item-label>
                  </q-item-section>
                  <q-item-section side top>
                    <div class="unread-solid-tag"></div>
                  </q-item-section>
                </q-item>
              </q-list>
              
              <div class="q-pa-sm bg-white border-top-solid text-center sticky-bottom z-top">
                <q-btn unelevated no-caps label="View All Notifications" color="red-1" text-color="red-9" class="full-width text-weight-bold" style="border-radius: 8px;" />
              </div>
            </q-menu>
          </q-btn>
          
          <q-btn flat round dense icon="logout" color="white" class="header-action-btn" @click="handleLogout" />
        </div>

      </q-toolbar>
    </q-header>

    <!-- ================= SARI-SARI RED SIDEBAR ================= -->
    <q-drawer
      v-model="drawerOpen"
      :show-if-above="!$q.screen.lt.md"
      :width="270"
      :breakpoint="1024"
      class="sari-sidebar-drawer"
    >
      <div class="sidebar-layout column full-height">

        <!-- Store Identity Section with Awning Header -->
        <div class="sidebar-brand-section q-pa-md">
          <div class="store-slate-container column flex-center text-center">
            <div class="slate-awning-strip row no-wrap">
              <span class="aw-red"></span><span class="aw-white"></span>
              <span class="aw-red"></span><span class="aw-white"></span>
              <span class="aw-red"></span><span class="aw-white"></span>
              <span class="aw-red"></span>
            </div>
            
            <!-- Store Emblem Box -->
            <div class="store-emblem-circle q-my-sm">
              <q-icon name="storefront" size="26px" color="red-9" />
            </div>
            
            <div class="text-weight-bolder text-white text-subtitle2 ellipsis full-width q-px-sm">
              {{ storeName }}
            </div>
            <div class="store-subtag q-mt-xs">
              SARI-SARI STORE PARTNER
            </div>
          </div>
        </div>

        <!-- Navigation Links (Aligned & Consistent Padding) -->
        <div class="sidebar-links-container col scroll q-px-md">
          <div class="sidebar-category-header q-px-sm q-pt-sm q-pb-xs">
            MAIN MENU
          </div>

          <q-list class="q-gutter-y-xs">
            <template v-for="(item, index) in navItems" :key="index">
              
              <!-- Direct Menu Item -->
              <q-item
                v-if="!item.children"
                :to="item.path"
                clickable
                v-ripple
                active-class="solid-nav-active"
                class="solid-nav-item uniform-menu-item"
              >
                <q-item-section avatar class="nav-avatar-slot">
                  <q-icon :name="item.icon" size="22px" class="nav-icon-glyph" />
                </q-item-section>
                <q-item-section class="nav-label-text text-weight-bold">
                  {{ item.label }}
                </q-item-section>
              </q-item>

              <!-- Expandable Menu Item -->
              <q-expansion-item
                v-else
                :icon="item.icon"
                :label="item.label"
                class="solid-nav-item solid-expansion-item uniform-menu-item"
                header-class="uniform-expansion-header"
                expand-icon-class="text-white opacity-80"
              >
                <q-list class="solid-subnav-list">
                  <q-item
                    v-for="(child, childIndex) in item.children"
                    :key="childIndex"
                    :to="child.path"
                    clickable
                    v-ripple
                    active-class="solid-sub-active"
                    class="solid-subnav-item"
                  >
                    <q-item-section class="subnav-label-text text-weight-bold">
                      {{ child.label }}
                    </q-item-section>
                  </q-item>
                </q-list>
              </q-expansion-item>

            </template>
          </q-list>
        </div>

        <!-- Sidebar Footer Action (Logout) -->
        <div class="sidebar-footer-area q-pa-md">
          <button type="button" class="sidebar-logout-card row items-center justify-center full-width cursor-pointer" @click="handleLogout">
            <q-icon name="logout" size="18px" class="q-mr-sm" />
            <span class="text-weight-bold" style="font-size: 13px;">Sign Out Account</span>
          </button>
        </div>

      </div>
    </q-drawer>

    <!-- ================= MAIN CONTENT ================= -->
    <q-page-container :class="{ 'mobile-pb': $q.screen.lt.md }">
      <router-view />
    </q-page-container>

    <!-- ================= MOBILE BOTTOM NAVIGATION ================= -->
    <q-footer v-if="$q.screen.lt.md" class="bg-white text-grey-8" style="box-shadow: 0 -3px 12px rgba(0,0,0,0.06); z-index: 2000; border-top: 1px solid #e2e8f0;">
      <div class="row no-wrap items-center justify-around bottom-nav-container relative-position" style="padding-bottom: env(safe-area-inset-bottom);">
        
        <!-- Orders -->
        <q-btn flat round dense no-caps class="nav-action-btn no-hover" :class="$route.path.includes('/vendor/orders') ? 'text-red-9' : 'text-grey-6'">
          <q-icon :name="$route.path.includes('/vendor/orders') ? 'receipt_long' : 'receipt_long'" size="24px" />
          
          <q-menu anchor="top middle" self="bottom middle" transition-show="jump-up" transition-hide="jump-down" class="solid-paper-menu no-shadow" :offset="[0, 14]">
            <q-list style="min-width: 200px" class="q-py-xs bg-grey-1">
              <q-item clickable v-ripple to="/vendor/orders/list" active-class="active-popup-item" class="popup-action-item">
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <div class="popup-icon-stamp"><q-icon name="list_alt" size="18px"/></div>
                </q-item-section>
                <q-item-section class="text-weight-bold text-caption">Order List</q-item-section>
              </q-item>
              
              <q-item clickable v-ripple to="/vendor/orders/customers" active-class="active-popup-item" class="popup-action-item">
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <div class="popup-icon-stamp"><q-icon name="people_outline" size="18px"/></div>
                </q-item-section>
                <q-item-section class="text-weight-bold text-caption">Customer Orders</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>

        <!-- Products -->
        <q-btn flat round dense no-caps class="nav-action-btn no-hover" :class="$route.path.includes('/vendor/products') ? 'text-red-9' : 'text-grey-6'">
          <q-icon :name="$route.path.includes('/vendor/products') ? 'inventory_2' : 'inventory_2'" size="24px" />
          
          <q-menu anchor="top middle" self="bottom middle" transition-show="jump-up" transition-hide="jump-down" class="solid-paper-menu no-shadow" :offset="[0, 14]">
            <q-list style="min-width: 200px" class="q-py-xs bg-grey-1">
              <q-item clickable v-ripple to="/vendor/products/list" active-class="active-popup-item" class="popup-action-item">
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <div class="popup-icon-stamp"><q-icon name="format_list_bulleted" size="18px"/></div>
                </q-item-section>
                <q-item-section class="text-weight-bold text-caption">Product List</q-item-section>
              </q-item>
              
              <q-item clickable v-ripple to="/vendor/products/categories" active-class="active-popup-item" class="popup-action-item">
                <q-item-section avatar class="q-pr-sm min-w-0">
                  <div class="popup-icon-stamp"><q-icon name="category" size="18px"/></div>
                </q-item-section>
                <q-item-section class="text-weight-bold text-caption">Categories</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>

        <!-- Center Home Button -->
        <div class="nav-placeholder flex flex-center relative-position">
          <div class="center-cutout flex flex-center">
            <q-btn 
              round 
              unelevated 
              to="/vendor/dashboard"
              class="shadow-2 center-store-home-btn"
              :class="$route.path === '/vendor/dashboard' ? 'bg-red-10' : 'bg-red-9'"
            >
              <q-icon name="home" size="24px" color="white" />
            </q-btn>
          </div>
        </div>

        <!-- Sales -->
        <q-btn flat round dense no-caps class="nav-action-btn no-hover" :class="$route.path.includes('/vendor/sales') ? 'text-red-9' : 'text-grey-6'" to="/vendor/sales">
          <q-icon :name="$route.path.includes('/vendor/sales') ? 'analytics' : 'analytics'" size="24px" />
        </q-btn>

        <!-- Profile -->
        <q-btn flat round dense no-caps class="nav-action-btn no-hover" :class="$route.path.includes('/vendor/profile') ? 'text-red-9' : 'text-grey-6'" to="/vendor/profile">
          <q-icon :name="$route.path.includes('/vendor/profile') ? 'person' : 'person_outline'" size="26px" />
        </q-btn>
        
      </div>
    </q-footer>

    <!-- ================= LOGOUT MODAL ================= -->
    <q-dialog v-model="showLogoutModal" persistent backdrop-filter="blur(4px)" :position="$q.screen.lt.md ? 'bottom' : 'standard'">
      <q-card 
        class="bg-white text-center overflow-hidden" 
        :style="$q.screen.lt.md ? 'width: 100%; border-radius: 20px 20px 0 0; padding-bottom: calc(16px + env(safe-area-inset-bottom));' : 'width: 380px; max-width: 90vw; border-radius: 16px;'"
      >
        <q-card-section class="q-pt-xl q-pb-md">
          <q-icon name="logout" size="44px" color="red-9" class="q-mb-md" />
          <h3 class="text-h6 text-weight-bolder text-dark q-mt-none q-mb-xs">Ready to Sign Out?</h3>
          <p class="text-body2 text-grey-7 q-mb-none font-medium q-px-md leading-snug">
            Are you sure you want to log out of your Tindahan vendor dashboard?
          </p>
        </q-card-section>
        
        <q-card-actions class="q-px-lg q-pt-sm q-pb-lg column q-gutter-y-sm">
          <q-btn 
            unelevated 
            label="Yes, Sign Out" 
            color="red-9" 
            @click="confirmLogout" 
            no-caps 
            class="full-width text-weight-bold" 
            size="md" 
            style="border-radius: 8px; height: 42px;"
            :loading="isLoggingOut" 
          />
          <q-btn 
            flat 
            label="Cancel" 
            text-color="grey-7" 
            v-close-popup 
            no-caps 
            class="full-width text-weight-bold q-ma-none" 
            size="md" 
            style="border-radius: 8px; background-color: #f1f5f9; height: 42px;" 
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

const router = useRouter()

const drawerOpen = ref(false)
const showLogoutModal = ref(false)
const isLoggingOut = ref(false)
const isGlobalLoading = ref(true)

const userName = ref('Vendor')
const userProfilePicture = ref(null)
const storeName = ref('Loading...')

onMounted(async () => {
  try {
    isGlobalLoading.value = true

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
    isGlobalLoading.value = false
  }
})

const navItems = [
  { label: 'Dashboard', icon: 'dashboard', path: '/vendor/dashboard' },
  { 
    label: 'Orders', 
    icon: 'receipt_long', 
    children: [
      { label: 'Order List', path: '/vendor/orders/list' },
      { label: 'Customer Orders', path: '/vendor/orders/customers' }
    ] 
  },
  { 
    label: 'Products', 
    icon: 'inventory_2', 
    children: [
      { label: 'Product List', path: '/vendor/products/list' },
      { label: 'Categories', path: '/vendor/products/categories' }
    ] 
  },
  { label: 'Sales Reports', icon: 'insights', path: '/vendor/sales' }
]

const handleLogout = () => {
  showLogoutModal.value = true
}

const confirmLogout = async () => {
  isLoggingOut.value = true
  try {
    await api.post('/logout')
  } catch { 
    // Ignore error
  }
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  localStorage.removeItem('auth_role')
  router.push('/login')
  isLoggingOut.value = false
}
</script>

<style scoped>
/* ================= GLOBAL BASE & PALETTE ================= */
.vendor-layout {
  background-color: #f8fafc;
}

.leading-tight { line-height: 1.15; }
.border-bottom-solid { border-bottom: 1px solid #e2e8f0; }
.border-top-solid { border-top: 1px solid #e2e8f0; }
.border-solid-red { border: 2px solid #fee2e2; }

/* ==========================================================
   AUTHENTIC SARI-SARI LOADING SCREEN (SOLID & CENTERED)
========================================================== */
.sari-loading-backdrop {
  background-color: rgba(248, 250, 252, 0.96);
  z-index: 99999;
}

.fade-fast-enter-active, .fade-fast-leave-active { transition: opacity 0.25s ease; }
.fade-fast-enter-from, .fade-fast-leave-to { opacity: 0; }

.sari-loading-card {
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 18px;
  padding: 34px 38px;
  width: 90%;
  max-width: 320px;
  box-shadow: 0 8px 24px rgba(185, 28, 28, 0.08);
}

.storefront-card-top {
  width: 140px;
  background: #ffffff;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
}

.awning-roof {
  width: 100%;
  height: 10px;
  display: flex;
}

.awning-red { flex: 1; background: #b91c1c; }
.awning-white { flex: 1; background: #ffffff; }

.store-badge-frame {
  padding: 12px 14px;
}

.loading-store-logo {
  height: 28px;
  width: auto;
  object-fit: contain;
  display: block;
}

.store-logo-fallback {
  display: none;
}
.loading-store-logo[style*="display: none"] + .store-logo-fallback {
  display: flex !important;
}

.counter-progress-track {
  width: 130px;
  height: 5px;
  background: #e2e8f0;
  border-radius: 99px;
  overflow: hidden;
  position: relative;
}

.counter-progress-fill {
  position: absolute;
  height: 100%;
  width: 45%;
  background: #b91c1c;
  border-radius: 99px;
  animation: bar-cycle 1.4s infinite ease-in-out;
}

@keyframes bar-cycle {
  0% { left: -45%; }
  100% { left: 100%; }
}

.loading-dots::after {
  content: '...';
  display: inline-block;
  animation: dots-sequence 1.5s steps(4, end) infinite;
  width: 1em;
  text-align: left;
}
@keyframes dots-sequence { 0%, 20% { content: ''; } 40% { content: '.'; } 60% { content: '..'; } 80%, 100% { content: '...'; } }

/* ==========================================================
   SARI-SARI RED HEADERS
========================================================== */
.sari-brand-header {
  background: linear-gradient(135deg, #b91c1c 0%, #991b1b 60%, #7f1d1d 100%) !important;
  border-bottom: 2px solid #7f1d1d;
  color: #ffffff !important;
}

.toolbar-desktop { min-height: 68px; }
.toolbar-mobile { min-height: 60px; }

.header-action-btn {
  background: rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.15);
}

/* White Logo Container Box */
.header-logo-card {
  background: #ffffff;
  border-radius: 8px;
  padding: 5px 12px;
  border: 1px solid rgba(255, 255, 255, 0.9);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
}

.header-logo-img {
  height: 22px;
  width: auto;
  max-width: 110px;
  object-fit: contain;
  display: block;
}

.header-logo-fallback {
  display: none;
}
.header-logo-img[style*="display: none"] + .header-logo-fallback {
  display: flex !important;
}

/* Store Title Plate */
.store-title-badge {
  background: rgba(0, 0, 0, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
}

.badge-sub-label {
  font-size: 8.5px;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #fecaca;
  line-height: 1;
}

.badge-store-title {
  font-size: 13.5px;
  font-weight: 800;
  color: #ffffff;
  max-width: 220px;
}

.vendor-profile-btn {
  background: rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 4px 10px;
}

.profile-frame {
  background: #ffffff;
}

/* Solid Dropdown Menus */
.solid-paper-menu {
  background: #ffffff;
  border: 2px solid #cbd5e1;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12) !important;
}

.paper-menu-item { border-radius: 6px; margin: 2px 8px; }
.paper-menu-item:hover { background: #f1f5f9; }
.logout-paper-item:hover { background: #fef2f2; }

.notification-card-item {
  border-bottom: 1px solid #e2e8f0;
}
.unread-paper-notification {
  background: #fef2f2;
}

.solid-icon-stamp {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #fee2e2;
  display: flex;
  align-items: center;
  justify-content: center;
}

.unread-solid-tag {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #b91c1c;
}

/* ==========================================================
   RED SARI-SARI SIDEBAR & MENU ALIGNMENT FIX
========================================================== */
:deep(.q-drawer.sari-sidebar-drawer),
:deep(.sari-sidebar-drawer) {
  background: linear-gradient(180deg, #991b1b 0%, #7f1d1d 100%) !important;
  color: #ffffff !important;
  border-right: 2px solid #661212 !important;
}

.sidebar-layout {
  position: relative;
  background: transparent;
}

.sidebar-brand-section {
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.store-slate-container {
  background: rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  padding: 14px 10px;
  position: relative;
  overflow: hidden;
}

.slate-awning-strip {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  display: flex;
}
.slate-awning-strip span { flex: 1; }
.aw-red { background: #b91c1c; }
.aw-white { background: #fee2e2; }

.store-emblem-circle {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.store-subtag {
  color: #fecaca;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.sidebar-category-header {
  font-size: 10px;
  font-weight: 800;
  color: #fca5a5;
  letter-spacing: 0.08em;
}

/* ==========================================================
   PERFECT VERTICAL AND HORIZONTAL MENU ALIGNMENT
========================================================== */
.uniform-menu-item {
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.88);
  min-height: 44px;
  padding: 8px 12px !important;
  transition: all 0.18s ease;
}

/* Ensure q-expansion-item internal header matches exact padding of q-item */
:deep(.uniform-expansion-header) {
  padding: 8px 12px !important;
  min-height: 44px !important;
  border-radius: 8px;
}

.nav-avatar-slot,
:deep(.uniform-expansion-header .q-item__section--avatar) {
  min-width: 36px !important;
  max-width: 36px !important;
  padding-right: 12px !important;
  display: flex;
  align-items: center;
}

.nav-icon-glyph,
:deep(.uniform-expansion-header .q-icon) {
  font-size: 22px !important;
}

.nav-label-text,
:deep(.uniform-expansion-header .q-item__section--main) {
  font-size: 13.5px !important;
  letter-spacing: -0.01em;
  font-weight: 700 !important;
}

.solid-nav-item:hover,
:deep(.uniform-expansion-header:hover) {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
}

.solid-nav-active {
  background: #ffffff !important;
  color: #991b1b !important;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
}

.solid-nav-active .nav-icon-glyph {
  color: #991b1b !important;
}

/* Submenu Items */
.solid-subnav-list {
  margin-left: 20px;
  padding-left: 8px;
  border-left: 2px solid rgba(255, 255, 255, 0.2);
  margin-top: 2px;
  margin-bottom: 6px;
}

.solid-subnav-item {
  border-radius: 6px;
  padding: 8px 12px;
  color: rgba(255, 255, 255, 0.78);
  min-height: 36px;
}

.solid-subnav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.solid-sub-active {
  background: rgba(255, 255, 255, 0.2) !important;
  color: #ffffff !important;
}

.subnav-label-text {
  font-size: 12.5px;
}

/* Sidebar Logout Card */
.sidebar-footer-area {
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.sidebar-logout-card {
  background: rgba(0, 0, 0, 0.22);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #fecaca;
  border-radius: 8px;
  padding: 10px;
  transition: all 0.2s ease;
}

.sidebar-logout-card:hover {
  background: #b91c1c;
  color: #ffffff;
  border-color: #b91c1c;
}

/* ==========================================================
   MOBILE BOTTOM NAVIGATION
========================================================== */
.bottom-nav-container { height: 60px; }
.nav-action-btn { width: 44px; height: 44px; }
.nav-placeholder { width: 52px; height: 100%; }

.center-cutout {
  position: absolute;
  top: -18px;
  width: 58px;
  height: 58px;
  background: #ffffff;
  border-radius: 50%;
  border: 2px solid #e2e8f0;
  box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.05);
  z-index: 2001;
}

.center-store-home-btn { width: 46px; height: 46px; }

.popup-action-item { border-radius: 8px; margin: 4px 6px; color: #1e293b; }
.popup-action-item:hover { background: #e2e8f0; }
.active-popup-item { background: #fee2e2 !important; color: #991b1b !important; }

.popup-icon-stamp {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
}

.active-popup-item .popup-icon-stamp {
  background: #991b1b;
  border-color: #991b1b;
  color: #ffffff;
}

.mobile-pb { padding-bottom: calc(72px + env(safe-area-inset-bottom)); }
.min-w-0 { min-width: 0 !important; }
.opacity-80 { opacity: 0.8; }
.opacity-50 { opacity: 0.5; }
</style>