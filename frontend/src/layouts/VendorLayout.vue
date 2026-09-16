<template>  
  <q-layout view="hHh LpR fFf" class="vendor-layout" :class="{ 'vendor-layout--dark': $q.dark.isActive }">

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

          <!-- Mobile Dark Mode Toggle -->
          <q-btn
            flat
            round
            dense
            :icon="$q.dark.isActive ? 'o_light_mode' : 'o_dark_mode'"
            color="white"
            class="header-action-btn"
            :aria-label="$q.dark.isActive ? t('lightMode') : t('darkMode')"
            @click="toggleDarkMode"
          >
            <q-tooltip>{{ $q.dark.isActive ? t('lightMode') : t('darkMode') }}</q-tooltip>
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

        <!-- Dark Mode & Language Toggles — sit right below the logo, same as the admin sidebar -->
        <div class="sidebar-utility-controls">
          <button type="button" class="sidebar-utility-chip" @click="toggleDarkMode">
            <q-icon :name="$q.dark.isActive ? 'o_light_mode' : 'o_dark_mode'" size="16px" />
            <span>{{ $q.dark.isActive ? t('lightMode') : t('darkMode') }}</span>
          </button>

          <button type="button" class="sidebar-utility-chip" @click="toggleLanguage">
            <q-icon name="o_translate" size="16px" />
            <span>{{ lang === 'en' ? 'English' : 'Filipino' }}</span>
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import { useAuth } from '@/composables/useAuth'
import { useVendorNotifications } from '@/composables/useVendorNotifications'
import { useLanguage } from '@/composables/useLanguage'
import '@/css/vendor-pages.scss'

const router = useRouter()
const $q = useQuasar()

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
    signOut: 'Sign out',
    darkMode: 'Dark Mode',
    lightMode: 'Light Mode',
    storePanel: 'Panel'
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
    signOut: 'Mag-sign out',
    darkMode: 'Dark Mode',
    lightMode: 'Light Mode',
    storePanel: 'Panel'
  }
}

// Connect to the global language state
const { lang, toggleLanguage, t } = useLanguage(layoutDict)

// Dark mode is scoped to the vendor module: its own storage key, its own
// "vendor-dark-mode" body marker (so the global overrides below for teleported
// dialogs/menus can't be triggered by the admin module reusing the same class
// names), and both are cleared on unmount so nothing bleeds into the
// consumer/admin modules or the login page.
const setDarkMode = (isDark) => {
  $q.dark.set(isDark)
  document.body.classList.toggle('vendor-dark-mode', isDark)
}

const toggleDarkMode = () => {
  const nextState = !$q.dark.isActive
  setDarkMode(nextState)
  try {
    localStorage.setItem('vendor_module_dark_mode', nextState ? '1' : '0')
  } catch {}
}

const { unreadCount, fetchNotifications } = useVendorNotifications()

onMounted(async () => {
  try {
    const savedDark = localStorage.getItem('vendor_module_dark_mode') === '1'
    setDarkMode(savedDark)
  } catch {
    setDarkMode(false)
  }

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

onBeforeUnmount(() => {
  setDarkMode(false)
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

.sidebar-utility-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 16px 12px;
}

.sidebar-utility-chip {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;

  gap: 6px;
  height: 32px;
  padding: 0 10px;

  border: none;
  border-radius: 9999px;

  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.85);

  font-family: inherit;
  font-size: 11.5px;
  font-weight: 600;

  cursor: pointer;
  transition: all 0.15s ease;
}

.sidebar-utility-chip:hover {
  background: rgba(255, 255, 255, 0.16);
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

/* =======================================================
   DARK MODE — a navy/blue-hued theme, the same palette as
   the admin module. Scoped to .vendor-layout--dark so it
   never touches the consumer app, the admin module, or the
   login page (each has its own layout and is unaffected).
======================================================= */
.vendor-layout--dark {
  --vnd-ground: #0a0f1e;
  --vnd-surface: #111a2e;
  --vnd-surface-2: #16213a;
  --vnd-line: #24314e;
  --vnd-text: #eef2fb;
  --vnd-secondary: #9fb1d1;

  background-color: var(--vnd-ground) !important;
  color: var(--vnd-text);
  color-scheme: dark;
}

/* Page-content token remap: every vp-* class and page that already reads
   var(--c-*) picks this up automatically, the same approach as the admin layout. */
.vendor-layout--dark {
  --c-bg: var(--vnd-ground);
  --c-surface: var(--vnd-surface);
  --c-surface-2: var(--vnd-surface-2);
  --c-text: var(--vnd-text);
  --c-text-2: #e2e8f9;
  --c-text-3: #c7d0e4;
  --c-muted: var(--vnd-secondary);
  --c-subtle: var(--vnd-secondary);
  --c-border: var(--vnd-line);
  --c-border-strong: #3c4a6e;
  --c-hairline: var(--vnd-line);

  /* Quasar's own color="primary"/color="negative" buttons read these, not --c-*,
     so without this remap every "Save"/"Delete" button on a q-btn stays the
     compiled-in #bd2427/#c10015 — a dark red that all but disappears on navy. */
  --q-primary: #ff4d4d;
  --q-negative: #ff5c5c;

  --c-brand: #ff4d4d;
  --c-brand-dark: #d92727;
  --c-brand-deep: #bd2020;
  --c-brand-active: #e63232;
  --c-brand-hover: #ff7474;
  --c-brand-tint: rgba(255, 77, 77, 0.14);
  --c-brand-tint-2: rgba(255, 77, 77, 0.32);
  --c-brand-tint-3: rgba(255, 77, 77, 0.42);

  --c-danger: #ff5c5c;
  --c-danger-tint: rgba(255, 77, 77, 0.14);
  --c-danger-tint-2: rgba(255, 77, 77, 0.24);
  --c-warning: #fbbf24;
  --c-warning-tint: rgba(251, 191, 36, 0.16);
  --c-warning-wash: rgba(251, 191, 36, 0.1);
  --c-warning-line: rgba(251, 191, 36, 0.4);
  --c-success: #4ade80;
  --c-success-tint: rgba(74, 222, 128, 0.16);
  --c-success-wash: rgba(74, 222, 128, 0.1);
  --c-success-line: rgba(74, 222, 128, 0.4);
  --c-info: #60a5fa;
  --c-info-tint: rgba(96, 165, 250, 0.16);
  --c-info-wash: rgba(96, 165, 250, 0.1);
  --c-info-line: rgba(96, 165, 250, 0.4);
  --c-status-wait: #fbbf24;
  --c-status-wait-tint: rgba(251, 191, 36, 0.16);
  --c-status-active: #60a5fa;
  --c-status-active-tint: rgba(96, 165, 250, 0.16);

  /* Order lifecycle colours, tinted to stay legible on the navy ground. */
  --st-placed: #93c5fd;
  --st-placed-bg: rgba(96, 165, 250, 0.16);
  --st-preparing: #fcd34d;
  --st-preparing-bg: rgba(251, 191, 36, 0.16);
  --st-ready: #c4b5fd;
  --st-ready-bg: rgba(167, 139, 250, 0.18);
  --st-done: #86efac;
  --st-done-bg: rgba(74, 222, 128, 0.16);
  --st-cancelled: #fca5a5;
  --st-cancelled-bg: rgba(255, 77, 77, 0.14);

  --sh-card: 0 1px 3px rgba(0, 0, 0, 0.4);
  --sh-card-hover: 0 4px 14px rgba(0, 0, 0, 0.5);
  --sh-pop: 0 10px 30px rgba(0, 0, 0, 0.55);
  --sh-header: 0 1px 3px rgba(0, 0, 0, 0.4);
  --sh-dock: 0 -2px 10px rgba(0, 0, 0, 0.4);
  --sh-brand: 0 0 0 1px rgba(255, 77, 77, 0.12), 0 3px 10px rgba(255, 52, 52, 0.2);
  --sh-brand-hover: 0 0 0 1px rgba(255, 77, 77, 0.2), 0 5px 18px rgba(255, 52, 52, 0.3);
}

/* SHELL — header, sidebar and mobile dock move to the same navy surface as
   the admin module; the active nav item keeps a red accent so the brand still reads. */
.vendor-layout--dark .sari-brand-header,
.vendor-layout--dark .vendor-bottom-nav,
.vendor-layout--dark :deep(.q-drawer.sari-sidebar-drawer),
.vendor-layout--dark :deep(.sari-sidebar-drawer) {
  background: var(--vnd-surface) !important;
  background-image: none !important;
  color: var(--vnd-text) !important;
  border-color: var(--vnd-line) !important;
}

.vendor-layout--dark .solid-nav-active,
.vendor-layout--dark .solid-nav-active:hover {
  background: linear-gradient(135deg, #ef3b3b, var(--c-brand-deep)) !important;
  color: #ffffff !important;
  box-shadow: 0 0 0 1px rgba(255, 77, 77, 0.16), 0 7px 20px rgba(255, 52, 52, 0.24);
}

.vendor-layout--dark .solid-nav-active .nav-icon-glyph {
  color: #ffffff !important;
}

.vendor-layout--dark .sidebar-links-container,
.vendor-layout--dark .sidebar-footer-area {
  border-color: var(--vnd-line) !important;
}

.vendor-layout--dark .sidebar-account {
  border-color: rgba(255, 255, 255, 0.12) !important;
  background: rgba(255, 255, 255, 0.05) !important;
}

/* PAGE CONTENT — the vp-* design system's few hardcoded whites; everything
   else in it already reads var(--c-*) and follows the remap above. */
.vendor-layout--dark :deep(.vp-page),
.vendor-layout--dark :deep(.dash-page),
.vendor-layout--dark :deep(.profile-page) {
  background: var(--vnd-ground) !important;
}

.vendor-layout--dark :deep(.vp-status--cancelled) {
  background-color: var(--c-danger-tint) !important;
  color: var(--c-danger) !important;
}

/* Cards and rows outside the vp-* system that hardcode a white surface, one per page:
   the dashboard's KPI/insight cards, the profile's info cards, notifications, and the
   product-list's refresh chip. (Anything that only ever renders inside a q-dialog or
   q-menu — export/cancel/OTP dialogs, dropdown menus, the live-store preview — is NOT
   reachable from here; Quasar teleports that content to <body>, outside this whole
   .vendor-layout--dark subtree, so it's handled in the unscoped block below instead.) */
.vendor-layout--dark :deep(.dash-card),
.vendor-layout--dark :deep(.kpi-card),
.vendor-layout--dark :deep(.profile-card),
.vendor-layout--dark :deep(.danger-row),
.vendor-layout--dark :deep(.nt-item),
.vendor-layout--dark :deep(.pl-refresh-icon),
.vendor-layout--dark :deep(.segmented-btn--active),
.vendor-layout--dark :deep(.forecast-card .forecast-thumb),
.vendor-layout--dark :deep(.premium-glass-card),
.vendor-layout--dark :deep(.glass-card) {
  background-color: var(--vnd-surface) !important;
  border-color: var(--vnd-line) !important;
  color: var(--vnd-text) !important;
}

.vendor-layout--dark :deep(.vp-card),
.vendor-layout--dark :deep(.vp-search .q-field__control),
.vendor-layout--dark :deep(.vp-chip) {
  background-color: var(--vnd-surface) !important;
  color: var(--vnd-text) !important;
}

.vendor-layout--dark :deep(.vp-chip--active) {
  background-color: var(--c-brand-tint) !important;
}

.vendor-layout--dark :deep(.vp-chip-count) {
  background-color: var(--vnd-surface-2) !important;
  color: var(--vnd-text) !important;
}

.vendor-layout--dark :deep(.vp-chip--active .vp-chip-count) {
  background-color: var(--c-brand) !important;
  color: #ffffff !important;
}

.vendor-layout--dark :deep(.q-separator) {
  background: var(--vnd-line);
}

/* BUTTONS — Quasar's own color="primary"/color="negative" already read the
   --q-primary/--q-negative override above, so text and solid fills aren't the
   dim compiled-in red anymore; this layer redesigns the shapes themselves so a
   solid button glows instead of sitting flat, and an outline/flat one gets a
   tinted fill instead of just thin red text floating on the navy ground. */
.vendor-layout--dark :deep(.q-btn.bg-primary) {
  background: linear-gradient(135deg, #ff6a6a 0%, #e63232 60%, #bd2020 100%) !important;
  box-shadow: 0 4px 14px rgba(255, 77, 77, 0.35) !important;
}

.vendor-layout--dark :deep(.q-btn.bg-primary:hover) {
  box-shadow: 0 6px 18px rgba(255, 77, 77, 0.5) !important;
}

/* The Cancel button on every vendor dialog is this shape (outline, color="primary"):
   a soft red glow around the border, brightening on hover/focus, so it reads as a
   lit, clearly clickable control instead of thin red text floating on navy. */
.vendor-layout--dark :deep(.q-btn--outline.text-primary) {
  border-color: #ff4d4d !important;
  background: rgba(255, 77, 77, 0.1) !important;
  box-shadow: 0 0 0 1px rgba(255, 77, 77, 0.2), 0 0 14px rgba(255, 77, 77, 0.35) !important;
}

.vendor-layout--dark :deep(.q-btn--outline.text-primary:hover) {
  background: rgba(255, 77, 77, 0.2) !important;
  box-shadow: 0 0 0 1px rgba(255, 77, 77, 0.32), 0 0 22px rgba(255, 77, 77, 0.55) !important;
}

.vendor-layout--dark :deep(.q-btn--flat.text-primary:hover) {
  background: rgba(255, 77, 77, 0.12) !important;
}

.vendor-layout--dark :deep(.q-btn.bg-negative) {
  background: linear-gradient(135deg, #ff8a8a 0%, #e63232 60%, #bd2020 100%) !important;
  box-shadow: 0 4px 14px rgba(255, 92, 92, 0.35) !important;
}

.vendor-layout--dark :deep(.q-btn.bg-negative:hover) {
  box-shadow: 0 6px 18px rgba(255, 92, 92, 0.5) !important;
}

.vendor-layout--dark :deep(.q-btn--outline.text-negative) {
  border-color: #ff5c5c !important;
  background: rgba(255, 92, 92, 0.1) !important;
}

.vendor-layout--dark :deep(.q-btn--outline.text-negative:hover) {
  background: rgba(255, 92, 92, 0.2) !important;
}
</style>

<!--
  Quasar teleports every QMenu/QDialog/QTooltip's content to a node it appends
  directly under <body>, entirely outside .vendor-layout's own DOM subtree —
  the same reason the logout confirmation dialog needed its dark styling in an
  unscoped block instead of a scoped :global(). No amount of :deep() from the
  style block above can reach a vp-dialog, a dropdown menu, or the live-store
  preview, so their dark styling lives here.

  Everything below is gated on body.vendor-dark-mode, a marker this layout
  toggles itself alongside $q.dark (see setDarkMode in the script) — never on
  bare body.body--dark — so it can only ever match while the vendor module
  itself turned dark mode on, even though some of these class names (vp-*) are
  shared with the admin module.
-->
<style>
/* Re-declares the navy palette on <body> so a teleported node — whose real DOM
   parent is <body>, not .vendor-layout--dark — still inherits the same
   var(--c-*) tokens every vp-* rule and dialog already reads. */
body.body--dark.vendor-dark-mode {
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

  /* Same reasoning as the scoped block: color="primary"/color="negative" buttons
     inside a teleported dialog read these Quasar variables, not --c-brand. */
  --q-primary: #ff4d4d;
  --q-negative: #ff5c5c;

  --c-brand: #ff4d4d;
  --c-brand-deep: #bd2020;
  --c-brand-active: #e63232;
  --c-brand-hover: #ff7474;
  --c-brand-tint: rgba(255, 77, 77, 0.14);
  --c-brand-tint-2: rgba(255, 77, 77, 0.32);

  --c-danger: #ff5c5c;
  --c-danger-tint: rgba(255, 77, 77, 0.14);
}

/* Every dialog card, whatever its own custom class — vp-dialog, profile-dialog-card,
   dash-dialog, sr-dialog, od-dialog and so on all reduce to a plain <q-card> inside a
   <q-dialog>, so one generic selector reaches all of them instead of enumerating each
   page's own name for it (and covers any dialog added later, too). */
body.body--dark.vendor-dark-mode .q-dialog .q-card {
  background-color: #111a2e !important;
  color: #eef2fb !important;
}

/* Dialog icon tiles — vp-dialog-icon (the shared product/category/export/sales
   dialogs) and dialog-icon (the profile dialogs) — get the same deep gradient
   plus glow as the page-level .info-icon/.danger-icon, instead of the flat
   light-mode tint that reads muddy against navy once teleported to <body>. */
body.body--dark.vendor-dark-mode .vp-dialog-icon,
body.body--dark.vendor-dark-mode .dialog-icon {
  background: linear-gradient(145deg, rgba(255, 77, 77, 0.24) 0%, rgba(255, 77, 77, 0.08) 100%) !important;
  box-shadow: 0 0 0 1px rgba(255, 77, 77, 0.28), 0 0 18px rgba(255, 77, 77, 0.28) !important;
  color: #ff6a6a !important;
}

body.body--dark.vendor-dark-mode .vp-dialog-icon--danger,
body.body--dark.vendor-dark-mode .dialog-icon--danger {
  background: linear-gradient(145deg, rgba(255, 92, 92, 0.26) 0%, rgba(255, 92, 92, 0.1) 100%) !important;
  box-shadow: 0 0 0 1px rgba(255, 92, 92, 0.3), 0 0 18px rgba(255, 92, 92, 0.3) !important;
  color: #ff7a7a !important;
}

/* Same glow treatment for the View Product/Add Product dialogs' own icon
   tile, "pm-section-head" and "pm-dropzone-icon" (identical class names in
   both ProductDetailsModal.vue and AddProductModal.vue). */
body.body--dark.vendor-dark-mode .pm-section-head,
body.body--dark.vendor-dark-mode .pm-dropzone-icon {
  background: linear-gradient(145deg, rgba(255, 77, 77, 0.24) 0%, rgba(255, 77, 77, 0.08) 100%) !important;
  box-shadow: 0 0 0 1px rgba(255, 77, 77, 0.28), 0 0 18px rgba(255, 77, 77, 0.28) !important;
  color: #ff6a6a !important;
}

/* Elements that hardcode their own white surface on top of a (now dark) dialog
   card: the export-format tiles, the OTP boxes, the cancel-order reasons, the
   sales calendar dropdown, the live-store preview's status pill, the product
   dialogs' input fields and "no data" chart pill, and the photo-source rows
   (Camera/Gallery) in the View/Add Product dialogs. */
body.body--dark.vendor-dark-mode .otp-box,
body.body--dark.vendor-dark-mode .od-reason,
body.body--dark.vendor-dark-mode .sr-calendar,
body.body--dark.vendor-dark-mode .pl-format,
body.body--dark.vendor-dark-mode .preview-status.store-status--closed,
body.body--dark.vendor-dark-mode .pm-section .q-field__control,
body.body--dark.vendor-dark-mode .pm-chart-empty,
body.body--dark.vendor-dark-mode .pm-source {
  background-color: #16213a !important;
  border-color: #24314e !important;
  color: #eef2fb !important;
}

body.body--dark.vendor-dark-mode .pl-format--active .pl-format-icon {
  background-color: #16213a !important;
  color: #9fb1d1 !important;
}

/* The bell dropdown's notification rows and the bottom-nav's popup menus. */
body.body--dark.vendor-dark-mode .notif-item {
  background-color: #111a2e !important;
  border-bottom-color: #24314e !important;
}

body.body--dark.vendor-dark-mode .notif-item:hover {
  background-color: #16213a !important;
}

body.body--dark.vendor-dark-mode .popup-icon-stamp {
  background: #16213a !important;
  border: 1px solid #24314e !important;
  color: #9fb1d1 !important;
}

body.body--dark.vendor-dark-mode .active-popup-item .popup-icon-stamp {
  background: #ff4d4d !important;
  border-color: #ff4d4d !important;
  color: #ffffff !important;
}

/* Same button redesign as the scoped block above, repeated here for the
   confirm/cancel/delete buttons that live inside a teleported dialog. */
body.body--dark.vendor-dark-mode .q-btn.bg-primary {
  background: linear-gradient(135deg, #ff6a6a 0%, #e63232 60%, #bd2020 100%) !important;
  box-shadow: 0 4px 14px rgba(255, 77, 77, 0.35) !important;
}

body.body--dark.vendor-dark-mode .q-btn.bg-primary:hover {
  box-shadow: 0 6px 18px rgba(255, 77, 77, 0.5) !important;
}

/* Same red glow as the scoped block above, for the Cancel button inside a
   teleported dialog — which is where almost every one of them actually lives. */
body.body--dark.vendor-dark-mode .q-btn--outline.text-primary {
  border-color: #ff4d4d !important;
  background: rgba(255, 77, 77, 0.1) !important;
  box-shadow: 0 0 0 1px rgba(255, 77, 77, 0.2), 0 0 14px rgba(255, 77, 77, 0.35) !important;
}

body.body--dark.vendor-dark-mode .q-btn--outline.text-primary:hover {
  background: rgba(255, 77, 77, 0.2) !important;
  box-shadow: 0 0 0 1px rgba(255, 77, 77, 0.32), 0 0 22px rgba(255, 77, 77, 0.55) !important;
}

body.body--dark.vendor-dark-mode .q-btn--flat.text-primary:hover {
  background: rgba(255, 77, 77, 0.12) !important;
}

body.body--dark.vendor-dark-mode .q-btn.bg-negative {
  background: linear-gradient(135deg, #ff8a8a 0%, #e63232 60%, #bd2020 100%) !important;
  box-shadow: 0 4px 14px rgba(255, 92, 92, 0.35) !important;
}

body.body--dark.vendor-dark-mode .q-btn.bg-negative:hover {
  box-shadow: 0 6px 18px rgba(255, 92, 92, 0.5) !important;
}

body.body--dark.vendor-dark-mode .q-btn--outline.text-negative {
  border-color: #ff5c5c !important;
  background: rgba(255, 92, 92, 0.1) !important;
}

body.body--dark.vendor-dark-mode .q-btn--outline.text-negative:hover {
  background: rgba(255, 92, 92, 0.2) !important;
}
</style>