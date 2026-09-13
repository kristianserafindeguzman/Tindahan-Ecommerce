<template>  
  <q-layout view="hHh LpR fFf" class="vendor-layout">
    
    <!-- ================= MOBILE HEADER ================= -->  
    <q-header v-if="$q.screen.lt.md" class="sari-brand-header">  
      <q-toolbar class="q-px-md toolbar-mobile">
        
        <!-- Mobile Logo (Transparent) -->  
        <div class="header-logo-card flex flex-center cursor-pointer" @click="router.push('/vendor/dashboard')">  
          <img src="@/assets/tindahan-logo.png" alt="Tindahan Logo" class="header-logo-img" @error="$event.target.style.display='none'" />  
          <div class="header-logo-fallback row items-center no-wrap">  
            <q-icon name="storefront" size="24px" color="white" class="q-mr-xs" />  
            <span class="text-weight-bolder text-white text-body1">Tindahan</span>  
          </div>  
        </div>

        <q-space />

        <div class="row items-center no-wrap q-gutter-x-xs">
          <!-- Mobile Language Toggle — icon only, beside the bell -->
          <q-btn
            flat
            round
            dense
            icon="o_translate"
            color="white"
            class="header-action-btn"
            :aria-label="lang === 'en' ? 'Switch to Filipino' : 'Switch to English'"
            @click="toggleLanguage"
          >
            <q-tooltip>{{ lang === 'en' ? 'Filipino' : 'English' }}</q-tooltip>
          </q-btn>

          <q-btn flat round dense icon="notifications" color="white" class="header-action-btn relative-position" to="/vendor/notifications" aria-label="Notifications">  
            <q-badge v-if="unreadCount > 0" color="amber-9" text-color="white" floating rounded class="text-weight-bolder">  
              {{ unreadCount > 99 ? '99+' : unreadCount }}  
            </q-badge>  
          </q-btn>
          
          <q-btn flat round dense icon="logout" color="white" class="header-action-btn" @click="handleLogout" />  
        </div>

      </q-toolbar>  
    </q-header>

    <!-- ================= SIDEBAR ================= -->  
    <q-drawer  
      v-model="drawerOpen"  
      :show-if-above="!$q.screen.lt.md"  
      :width="264"  
      :breakpoint="1024"  
      class="sari-sidebar-drawer"  
    >  
      <div class="sidebar-layout column full-height no-wrap">

        <div class="sidebar-top">  
          <button type="button" class="sidebar-logo" aria-label="Go to dashboard" @click="router.push('/vendor/dashboard')">  
            <img src="@/assets/tindahan-logo.png" alt="Tindahan" class="sidebar-logo-img" />  
          </button>  
        </div>

        <nav class="sidebar-links-container col scroll" aria-label="Store menu">  
          <div v-for="group in localizedNavGroups" :key="group.label" class="sidebar-group">  
            <div class="sidebar-category-header">{{ group.label }}</div>  
            <q-list class="sidebar-group-list">  
              <q-item  
                v-for="item in group.items"  
                :key="item.path"  
                :to="item.path"  
                clickable  
                v-ripple  
                active-class="solid-nav-active"  
                class="solid-nav-item uniform-menu-item"  
              >  
                <q-item-section avatar class="nav-avatar-slot">  
                  <q-icon :name="item.icon" size="20px" class="nav-icon-glyph" />  
                </q-item-section>  
                <q-item-section class="nav-label-text">{{ item.label }}</q-item-section>  
              </q-item>            
            </q-list>  
          </div>  
        </nav>

        <div class="sidebar-footer-area">
          <!-- Desktop Language Toggle — restored to sit above the account/sign-out card -->
          <div class="sidebar-lang-toggle q-mb-sm">
            <q-btn flat dense no-caps full-width color="white" class="lang-btn" @click="toggleLanguage" icon="o_translate">
              <span class="q-ml-sm">{{ lang === 'en' ? 'English' : 'Filipino' }}</span>
            </q-btn>
          </div>

          <div class="sidebar-account">  
            <q-avatar size="36px" class="sidebar-account-avatar">  
              <img v-if="userProfilePicture" :src="userProfilePicture" alt="" />  
              <q-icon v-else name="o_person" size="20px" />  
            </q-avatar>  
            <div class="sidebar-account-text">  
              <div class="sidebar-account-name">  
                <q-skeleton v-if="profileLoading" dark type="text" width="104px" />  
                <template v-else>{{ userName }}</template>  
              </div>  
              <div class="sidebar-account-role">{{ t('storeOwner') }}</div>  
            </div>  
            <q-btn flat round dense icon="o_logout" class="sidebar-signout" aria-label="Sign out" @click="handleLogout">  
              <q-tooltip>{{ t('signOut') }}</q-tooltip>  
            </q-btn>  
          </div>  
        </div>

      </div>  
    </q-drawer>

    <!-- ================= MAIN CONTENT ================= -->  
    <q-page-container :class="{ 'mobile-pb': $q.screen.lt.md }">  
      <router-view />  
    </q-page-container>

    <!-- ================= MOBILE BOTTOM NAVIGATION ================= -->  
    <q-footer v-if="$q.screen.lt.md" class="vendor-bottom-nav bg-white">  
      <nav class="bottom-nav-inner" aria-label="Store menu">  
        <q-btn flat no-caps :ripple="false" class="bottom-nav-tab" :class="{ 'bottom-nav-tab--active': $route.path === '/vendor/dashboard' }" to="/vendor/dashboard">  
          <span class="bottom-nav-pill"><q-icon name="o_home" size="24px" /></span>  
          <span class="bottom-nav-label">{{ t('home') }}</span>  
        </q-btn>  
        <q-btn flat no-caps :ripple="false" class="bottom-nav-tab" :class="{ 'bottom-nav-tab--active': $route.path.includes('/vendor/orders') }">  
          <span class="bottom-nav-pill"><q-icon name="o_receipt_long" size="24px" /></span>  
          <span class="bottom-nav-label">{{ t('orders') }}</span>  
          <q-menu anchor="top middle" self="bottom middle" transition-show="jump-up" transition-hide="jump-down" class="solid-paper-menu" :offset="[0, 10]">  
            <q-list style="min-width: 200px" class="q-py-xs">  
              <q-item clickable v-ripple to="/vendor/orders/list" active-class="active-popup-item" class="popup-action-item">  
                <q-item-section avatar class="q-pr-sm min-w-0">  
                  <div class="popup-icon-stamp"><q-icon name="list_alt" size="18px" /></div>  
                </q-item-section>  
                <q-item-section class="text-weight-bold text-caption">{{ t('orderList') }}</q-item-section>  
              </q-item>  
              <q-item clickable v-ripple to="/vendor/orders/customers" active-class="active-popup-item" class="popup-action-item">  
                <q-item-section avatar class="q-pr-sm min-w-0">  
                  <div class="popup-icon-stamp"><q-icon name="people_outline" size="18px" /></div>  
                </q-item-section>  
                <q-item-section class="text-weight-bold text-caption">{{ t('customerOrders') }}</q-item-section>  
              </q-item>  
            </q-list>  
          </q-menu>  
        </q-btn>  
        <q-btn flat no-caps :ripple="false" class="bottom-nav-tab" :class="{ 'bottom-nav-tab--active': $route.path.includes('/vendor/products') }">  
          <span class="bottom-nav-pill"><q-icon name="o_inventory_2" size="24px" /></span>  
          <span class="bottom-nav-label">{{ t('products') }}</span>  
          <q-menu anchor="top middle" self="bottom middle" transition-show="jump-up" transition-hide="jump-down" class="solid-paper-menu" :offset="[0, 10]">  
            <q-list style="min-width: 200px" class="q-py-xs">  
              <q-item clickable v-ripple to="/vendor/products/list" active-class="active-popup-item" class="popup-action-item">  
                <q-item-section avatar class="q-pr-sm min-w-0">  
                  <div class="popup-icon-stamp"><q-icon name="format_list_bulleted" size="18px" /></div>  
                </q-item-section>  
                <q-item-section class="text-weight-bold text-caption">{{ t('productList') }}</q-item-section>  
              </q-item>  
              <q-item clickable v-ripple to="/vendor/products/categories" active-class="active-popup-item" class="popup-action-item">  
                <q-item-section avatar class="q-pr-sm min-w-0">  
                  <div class="popup-icon-stamp"><q-icon name="category" size="18px" /></div>  
                </q-item-section>  
                <q-item-section class="text-weight-bold text-caption">{{ t('categories') }}</q-item-section>  
              </q-item>  
            </q-list>  
          </q-menu>  
        </q-btn>  
        <q-btn flat no-caps :ripple="false" class="bottom-nav-tab" :class="{ 'bottom-nav-tab--active': $route.path.includes('/vendor/sales') }" to="/vendor/sales">  
          <span class="bottom-nav-pill"><q-icon name="o_analytics" size="24px" /></span>  
          <span class="bottom-nav-label">{{ t('sales') }}</span>  
        </q-btn>  
        <q-btn flat no-caps :ripple="false" class="bottom-nav-tab" :class="{ 'bottom-nav-tab--active': $route.path.includes('/vendor/profile') }" to="/vendor/profile">  
          <span class="bottom-nav-pill"><q-icon name="o_person" size="24px" /></span>  
          <span class="bottom-nav-label">{{ t('profileNav') }}</span>  
        </q-btn>  
      </nav>  
    </q-footer>

  </q-layout>  
</template>

<script setup>  
import { ref, computed, onMounted } from 'vue'  
import { useRouter } from 'vue-router'  
import { api } from '@/boot/axios'  
import { useAuth } from '@/composables/useAuth'  
import { useVendorNotifications } from '@/composables/useVendorNotifications'  
import { useLanguage } from '@/composables/useLanguage'
import '@/css/vendor-pages.scss'

const router = useRouter()

const drawerOpen = ref(false)  
const profileLoading = ref(true)
const userName = ref('Vendor')  
const userProfilePicture = ref(null)  
const storeName = ref('Loading...')

// Language Dictionary mapped explicitly for the Layout components
const layoutDict = {
  en: {
    home: 'Home',
    overview: 'Overview',
    orders: 'Orders',
    products: 'Products',
    account: 'Account',
    dashboard: 'Dashboard',
    sales: 'Sales Reports',
    orderList: 'Order List',
    customerOrders: 'Customer Orders',
    productList: 'Product List',
    categories: 'Categories',
    profile: 'Profile Settings',
    profileNav: 'Profile',
    storeOwner: 'Store Owner',
    signOut: 'Sign out'
  },
  ph: {
    home: 'Home',
    overview: 'Overview',
    orders: 'Mga Order',
    products: 'Mga Paninda',
    account: 'Account',
    dashboard: 'Dashboard',
    sales: 'Mga Benta',
    orderList: 'Listahan ng Order',
    customerOrders: 'Order ng Customers',
    productList: 'Listahan ng Paninda',
    categories: 'Mga Kategorya',
    profile: 'Profile Settings',
    profileNav: 'Profile',
    storeOwner: 'May-ari ng Tindahan',
    signOut: 'Mag-sign out'
  }
}

// Connect to the global language state
const { lang, toggleLanguage, t } = useLanguage(layoutDict)

const { unreadCount, fetchNotifications } = useVendorNotifications()

onMounted(async () => {  
  try {  
    const res = await api.get('/user')  
    if (res.data && res.data.user) {  
      const user = res.data.user  
      userName.value = user.full_name || 'Vendor'  
      userProfilePicture.value = user.profile_picture_url || null  
      storeName.value = user.store?.store_name || user.store_name || user.shop?.name || res.data.store_name || 'My Store'   
    }
    fetchNotifications()
  } catch (error) {  
    console.error('Error fetching user info:', error)  
    userName.value = 'Vendor'  
    storeName.value = 'Store Unavailable'  
  } finally {  
    profileLoading.value = false  
  }  
})

// Dynamically computes the navigation labels using the translation function
const localizedNavGroups = computed(() => [  
  {  
    label: t('overview'),  
    items: [  
      { label: t('dashboard'), icon: 'o_dashboard', path: '/vendor/dashboard' },  
      { label: t('sales'), icon: 'o_insights', path: '/vendor/sales' }  
    ]  
  },  
  {  
    label: t('orders'),  
    items: [  
      { label: t('orderList'), icon: 'o_receipt_long', path: '/vendor/orders/list' },  
      { label: t('customerOrders'), icon: 'o_people', path: '/vendor/orders/customers' }  
    ]  
  },  
  {  
    label: t('products'),  
    items: [  
      { label: t('productList'), icon: 'o_inventory_2', path: '/vendor/products/list' },  
      { label: t('categories'), icon: 'o_category', path: '/vendor/products/categories' }  
    ]  
  },  
  {  
    label: t('account'),  
    items: [  
      { label: t('profile'), icon: 'o_manage_accounts', path: '/vendor/profile' }  
    ]  
  }  
])

const { logout } = useAuth()  
const handleLogout = () => logout()  
</script>

<style scoped>  
.vendor-layout {  
  background-color: #ffffff;  
}

.leading-tight { line-height: 1.15; }  
.border-bottom-solid { border-bottom: 1px solid var(--c-border); }  
.border-top-solid { border-top: 1px solid var(--c-border); }  
.border-solid-red { border: 2px solid var(--c-brand-tint-2); }

.sari-brand-header {  
  background: linear-gradient(90deg, #af2424 0%, #490f0f 100%) !important;  
  color: #ffffff !important;  
  box-shadow: var(--sh-header);  
}

.toolbar-desktop { min-height: 68px; }  
.toolbar-mobile { min-height: 72px; }

.header-action-btn {  
  transition: background-color 0.15s;  
}  
.header-action-btn:hover {  
  background: rgba(255, 255, 255, 0.12);  
}

.header-logo-card {  
  background: transparent;  
  border-radius: var(--r-control);  
  padding: 0 4px;  
}

.header-logo-img {  
  height: 72px;  
  width: auto;  
  max-width: 240px;  
  object-fit: contain;  
  display: block;  
}

.header-logo-fallback {  
  display: none;  
}  
.header-logo-img[style*="display: none"] + .header-logo-fallback {  
  display: flex !important;  
}

.lang-btn {
  color: rgba(255, 255, 255, 0.8) !important;
  background: rgba(255, 255, 255, 0.05);
  border-radius: var(--r-control);
  transition: all 0.2s;
}

.lang-btn:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff !important;
}

.store-title-badge {  
  background: rgba(255, 255, 255, 0.12);  
  border-radius: var(--r-control);  
}

.badge-sub-label {  
  font-size: 9px;  
  font-weight: 700;  
  letter-spacing: 0.08em;  
  color: rgba(255, 255, 255, 0.72);  
  line-height: 1;  
}

.badge-store-title {  
  font-size: var(--fs-sm);  
  font-weight: 700;  
  color: #ffffff;  
  max-width: 220px;  
}

.vendor-profile-btn {  
  background: rgba(255, 255, 255, 0.12);  
  border-radius: var(--r-control);  
  padding: 4px 10px;  
}

.profile-frame {  
  background: #ffffff;  
}

.solid-paper-menu {  
  overflow: hidden;  
  background: #ffffff;  
  border: 1px solid var(--c-border);  
  border-radius: var(--r-surface);  
  box-shadow: var(--sh-pop) !important;  
}

.paper-menu-item { border-radius: var(--r-control); margin: 2px 8px; }  
.paper-menu-item:hover { background: var(--c-surface); }  
.logout-paper-item:hover { background: var(--c-brand-tint); }

.notification-card-item {  
  border-bottom: 1px solid var(--c-hairline);  
}  
.unread-paper-notification {  
  background: var(--c-brand-tint);  
}

.solid-icon-stamp {  
  width: 36px;  
  height: 36px;  
  border-radius: var(--r-control);  
  display: flex;  
  align-items: center;  
  justify-content: center;  
}

.unread-solid-tag {  
  width: 8px;  
  height: 8px;  
  border-radius: 50%;  
  background-color: var(--c-brand);  
}

:deep(.q-drawer.sari-sidebar-drawer),  
:deep(.sari-sidebar-drawer) {  
  background:  
    radial-gradient(circle, rgba(255, 255, 255, 0.07) 1.5px, transparent 1.5px) 0 0 / 22px 22px,  
    linear-gradient(180deg, #a82323 0%, #7a1818 55%, #490f0f 100%) !important;  
  color: #ffffff !important;  
  border-right: none !important;  
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

.sidebar-logo {  
  padding: 0;  
  border: none;  
  background: transparent;  
  cursor: pointer;  
}

.sidebar-logo:focus-visible {  
  outline: 2px solid #ffffff;  
  outline-offset: 4px;  
  border-radius: var(--r-control);  
}

.sidebar-logo-img {  
  display: block;  
  height: 148px;  
  max-width: 240px;  
  width: auto;  
  object-fit: contain;  
}

.sidebar-store {  
  display: flex;  
  align-items: center;  
  gap: 12px;  
  margin: 4px 16px 0;  
  padding: 10px;  
  border: 1px solid rgba(255, 255, 255, 0.16);  
  border-radius: var(--r-surface);  
  background: rgba(255, 255, 255, 0.1);  
}

.sidebar-store-photo {  
  display: flex;  
  align-items: center;  
  justify-content: center;  
  flex-shrink: 0;  
  width: 44px;  
  height: 44px;  
  overflow: hidden;  
  border: 2px solid #ffffff;  
  border-radius: var(--r-surface);  
  background: #ffffff;  
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);  
}

.sidebar-store-photo img {  
  width: 100%;  
  height: 100%;  
  object-fit: cover;  
}

.sidebar-store-text {  
  min-width: 0;  
}

.sidebar-store-name {  
  font-size: var(--fs-sm);  
  font-weight: 700;  
  color: #ffffff;  
}

.store-subtag {  
  margin-top: 2px;  
  color: rgba(255, 255, 255, 0.7);  
  font-size: 10px;  
  font-weight: 700;  
  letter-spacing: 0.06em;  
  text-transform: uppercase;  
}

.sidebar-links-container {  
  margin-top: 18px;  
  padding: 18px 12px 12px;  
  border-top: 1px solid rgba(255, 255, 255, 0.12);  
}

.sidebar-group + .sidebar-group {  
  margin-top: 18px;  
}

.sidebar-category-header {  
  padding: 0 12px 6px;  
  font-size: 10px;  
  font-weight: 700;  
  color: rgba(255, 255, 255, 0.55);  
  letter-spacing: 0.1em;  
  text-transform: uppercase;  
}

.sidebar-group-list {  
  display: flex;  
  flex-direction: column;  
  gap: 2px;  
}

.uniform-menu-item {  
  border-radius: var(--r-control);  
  color: rgba(255, 255, 255, 0.86);  
  min-height: 42px;  
  padding: 8px 12px !important;  
  transition: background-color 0.15s, color 0.15s;  
}

.nav-avatar-slot {  
  min-width: 32px !important;  
  max-width: 32px !important;  
  padding-right: 12px !important;  
  display: flex;  
  align-items: center;  
}

.nav-icon-glyph {  
  color: rgba(255, 255, 255, 0.75);  
}

.nav-label-text {  
  font-size: var(--fs-sm) !important;  
  font-weight: 600 !important;  
}

.solid-nav-item:hover {  
  background: rgba(255, 255, 255, 0.1);  
  color: #ffffff;  
}

.solid-nav-active,  
.solid-nav-active:hover {  
  background: #ffffff !important;  
  color: var(--c-brand) !important;  
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.16);  
}

.solid-nav-active .nav-icon-glyph {  
  color: var(--c-brand) !important;  
}

.sidebar-footer-area {  
  padding: 12px;  
  border-top: 1px solid rgba(255, 255, 255, 0.12);  
}

.sidebar-account {  
  display: flex;  
  align-items: center;  
  gap: 10px;  
  width: 100%;  
  padding: 8px 10px;  
  border: 1px solid rgba(255, 255, 255, 0.16);  
  border-radius: var(--r-surface);  
  background: rgba(255, 255, 255, 0.08);  
  color: #ffffff;  
  font-family: inherit;  
  text-align: left;  
}

.sidebar-account-avatar {  
  flex-shrink: 0;  
  background: #ffffff;  
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
  white-space: nowrap;  
  text-overflow: ellipsis;  
}

.sidebar-account-role {  
  font-size: var(--fs-2xs);  
  color: rgba(255, 255, 255, 0.7);  
}

.sidebar-signout {  
  flex-shrink: 0;  
  color: rgba(255, 255, 255, 0.8);  
  transition: background-color 0.15s, color 0.15s;  
}

.sidebar-signout:hover {  
  background: rgba(255, 255, 255, 0.14);  
  color: #ffffff;  
}

.vendor-bottom-nav {  
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
  max-width: 100%;  
  overflow: hidden;  
  white-space: nowrap;  
  text-overflow: ellipsis;  
  font-size: var(--fs-xs);  
  font-weight: 600;  
  line-height: 1.2;  
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

.popup-action-item { border-radius: var(--r-control); margin: 4px 6px; color: var(--c-text-2); }  
.popup-action-item:hover { background: var(--c-surface); }  
.active-popup-item { background: var(--c-brand-tint) !important; color: var(--c-brand) !important; }

.popup-icon-stamp {  
  width: 30px;  
  height: 30px;  
  border-radius: var(--r-control);  
  background: #ffffff;  
  border: 1px solid var(--c-border);  
  display: flex;  
  align-items: center;  
  justify-content: center;  
  color: var(--c-subtle);  
}

.active-popup-item .popup-icon-stamp {  
  background: var(--c-brand);  
  border-color: var(--c-brand);  
  color: #ffffff;  
}

.mobile-pb { padding-bottom: calc(72px + env(safe-area-inset-bottom)); }  
.min-w-0 { min-width: 0 !important; }  
.opacity-80 { opacity: 0.8; }  
.opacity-50 { opacity: 0.5; }  
</style>