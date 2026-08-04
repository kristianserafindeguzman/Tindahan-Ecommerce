<template>
  <header class="site-header">
    <div class="header-bar">
      <div class="header-bar-inner">

        <img
          src="@/assets/tindahan-logo.png"
          alt="Tindahan Logo"
          class="header-logo"
          role="button"
          @click="router.push('/consumer/home')"
        />

        <q-tabs
          :model-value="activeTab"
          dense
          no-caps
          indicator-color="white"
          class="header-nav"
        >
          <q-tab
            v-for="tab in tabs"
            :key="tab.value"
            :name="tab.value"
            :label="tab.label"
            @click="goToTab(tab)"
          />
        </q-tabs>

        <div class="header-search" :class="{ 'header-search-compact': addressExpanded }">
          <q-icon name="search" size="17px" class="header-search-icon" />
          <q-input
            v-model="searchQuery"
            dense
            borderless
            placeholder="Search Products"
            class="header-search-input"
            @keyup.enter="handleSearch"
          />
          <q-btn
            round
            unelevated
            dense
            icon="search"
            aria-label="Search"
            class="header-search-btn"
            @click="handleSearch"
          />
        </div>

        <div class="header-location" :title="address" @click="addressExpanded = !addressExpanded">
          <span class="header-location-pill" :class="{ 'header-location-expanded': addressExpanded }">
            <q-icon name="location_on" size="15px" />
            <span>{{ address }}</span>
          </span>
        </div>

        <div class="header-actions">

          <!-- LOGGED IN -->
          <template v-if="isLoggedIn">
            <q-btn flat dense :ripple="false" class="icon-btn">
              <q-icon name="notifications" size="20px" />
              <span v-if="hasNotifications" class="icon-badge-dot" />
            </q-btn>

            <q-btn flat dense :ripple="false" class="icon-btn">
              <q-icon name="shopping_cart" size="20px" />
              <span v-if="cartCount" class="icon-badge-count">{{ cartCount }}</span>
            </q-btn>

            <q-btn flat dense no-caps :ripple="false" class="account-btn">
              <q-avatar size="24px" class="account-avatar">
                <img v-if="userAvatar" :src="userAvatar" />
                <q-icon v-else name="person" size="16px" />
              </q-avatar>
              <q-icon name="expand_more" size="16px" class="q-ml-xs" />

              <q-menu anchor="bottom right" self="top right" class="account-menu">
                <q-list style="min-width: 160px">
                  <q-item clickable v-close-popup @click="router.push('/consumer/profile')">
                    <q-item-section>My Profile</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup @click="router.push('/consumer/orders')">
                    <q-item-section>My Orders</q-item-section>
                  </q-item>
                  <q-separator />
                  <q-item clickable v-close-popup @click="handleLogout">
                    <q-item-section class="text-red-9">Logout</q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </template>

          <!-- GUEST -->
          <template v-else>
            <q-btn
              label="Log in"
              no-caps
              flat
              dense
              :ripple="false"
              class="auth-btn"
              @click="goToLogin"
            />
            <q-btn
              label="Sign up"
              no-caps
              unelevated
              dense
              class="auth-btn auth-btn-primary"
              @click="goToSignup"
            />

            <q-btn flat dense :ripple="false" class="icon-btn">
              <q-icon name="shopping_cart" size="20px" />
              <span v-if="cartCount" class="icon-badge-count">{{ cartCount }}</span>
            </q-btn>
          </template>

        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/boot/axios'

defineProps({
  address: {
    type: String,
    required: true
  }
})

const router = useRouter()
const route = useRoute()

// ==========================================================
// AUTH STATE — the header renders for both guests and logged-in
// consumers on routes that aren't all auth-guarded, so it reads
// localStorage directly rather than relying on a route guard.
// ==========================================================

const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

const userAvatar = computed(() => {
  try {
    const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
    return user.profile_picture_url || null
  } catch {
    return null
  }
})

// TODO: back with real notification/cart endpoints once they exist
const hasNotifications = ref(true)
const cartCount = ref(2)

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

const goToLogin = () => {
  router.push('/login')
}

const goToSignup = () => {
  // LoginPage.vue opens the Consumer/Vendor registration-choice dialog
  // automatically when it sees this query param — see the addition there.
  router.push({ path: '/login', query: { register: '1' } })
}

// ==========================================================
// SEARCH
// ==========================================================

const searchQuery = ref('')

const handleSearch = () => {
  // TODO: wire to real /products or /stores search endpoint once it exists
  console.log('Search:', searchQuery.value)
}

// ==========================================================
// SUBNAV TABS
// ==========================================================

const tabs = [
  { label: 'Home', value: 'home', to: '/consumer/home' },
  { label: 'Products', value: 'products', to: '/consumer/products' },
  { label: 'Stores', value: 'stores', to: '/consumer/stores' }
]

const activeTab = computed(() => {
  const match = tabs.find((tab) => tab.to === route.path)
  return match ? match.value : null
})

const goToTab = (tab) => {
  if (tab.to) router.push(tab.to)
}

// ==========================================================
// ADDRESS EXPAND/COLLAPSE (desktop-only interaction, see media
// queries below)
// ==========================================================

const addressExpanded = ref(false)
</script>

<style scoped>
.site-header {
  position: sticky;
  top: 0;
  z-index: 100;

  background:
    linear-gradient(
      90deg,
      #af2424 0%,
      #490f0f 100%
    );

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
}

.header-bar-inner {
  display: flex;
  align-items: center;

  gap: 24px;

  max-width: 1200px;

  margin: 0 auto;
  padding: 12px 24px;
}

.header-logo {
  height: 40px;
  width: auto;

  object-fit: contain;

  flex-shrink: 0;

  cursor: pointer;
}

.header-actions {
  display: flex;
  align-items: center;

  gap: 2px;

  margin-left: auto;

  flex-shrink: 0;
}

.icon-btn {
  position: relative;

  width: 38px;
  height: 38px;

  border-radius: 8px;

  color: #ffffff;

  transition: background-color 0.15s;
}

.icon-btn:hover {
  background: rgba(255, 255, 255, 0.14);
}

.icon-badge-dot {
  position: absolute;
  top: 7px;
  right: 7px;

  width: 7px;
  height: 7px;

  border-radius: 50%;
  border: 1.5px solid #9c171b;

  background: #ffffff;
}

.icon-badge-count {
  position: absolute;
  top: 2px;
  right: 2px;

  display: flex;
  align-items: center;
  justify-content: center;

  min-width: 16px;
  height: 16px;
  padding: 0 3px;

  border-radius: 999px;
  border: 1.5px solid #9c171b;

  background: #ffffff;
  color: #bd2427;

  font-size: 10px;
  font-weight: 700;
  line-height: 1;
}

.auth-btn {
  height: 38px;

  padding: 0 18px;

  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.5);

  color: #ffffff;

  font-size: 13px;
  font-weight: 500;

  transition: background-color 0.15s;
}

.auth-btn:not(.auth-btn-primary):hover {
  background: rgba(255, 255, 255, 0.12);
}

.auth-btn-primary {
  border: none;

  background: #ffffff;
  color: #bd2427;

  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.auth-btn-primary:hover {
  background: #f4f4f4;
}

.account-btn {
  gap: 2px;

  height: 38px;
  padding: 0 12px 0 10px;

  border-radius: 8px;

  color: #ffffff;

  font-size: 13px;
  font-weight: 500;

  transition: background-color 0.15s;
}

.account-btn:hover {
  background: rgba(255, 255, 255, 0.14);
}

.account-avatar {
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
}

.account-menu :deep(.q-item__section) {
  font-size: 13px;
}

/* =========================
   HEADER — SEARCH BAR
========================= */

.header-search {
  display: flex;
  align-items: center;

  flex: 1 1 auto;
  min-width: 140px;
  max-width: 400px;

  height: 38px;

  border: none;
  border-radius: 8px;

  background: #ffffff;

  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);

  overflow: hidden;

  transition: max-width 0.25s ease, min-width 0.25s ease, box-shadow 0.15s;
}

.header-search:focus-within {
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
}

.header-search-icon {
  margin-left: 14px;

  color: #9ca3af;

  flex-shrink: 0;
}

.header-search-input {
  flex: 1;
  min-width: 0;

  height: 100%;
}

.header-search-input :deep(.q-field__control),
.header-search-input :deep(.q-field__control):before,
.header-search-input :deep(.q-field__control):after {
  height: 100%;

  border: none;
}

.header-search-input :deep(.q-field__control) {
  padding: 0 10px;
}

.header-search-input :deep(.q-field__marginal) {
  height: auto;
}

.header-search-input :deep(.q-field__native) {
  padding: 0;

  font-size: 13px;
  font-family: inherit;

  color: #111827;
}

.header-search-input :deep(.q-field__native)::placeholder {
  color: #9ca3af;
}

.header-search-btn {
  width: 38px;
  height: 30px;
  min-width: 38px;
  min-height: 30px;
  margin-right: 5px;

  border-radius: 8px;

  background: #bd2427;
  color: #ffffff;

  flex-shrink: 0;

  transition: background-color 0.15s;
}

.header-search-btn:hover {
  background: #a91e21;
}

/* =========================
   HEADER — NAV (inline, next to logo)
========================= */

.header-nav {
  flex-shrink: 0;

  min-height: 0;
}

.header-nav :deep(.q-tabs__content) {
  gap: 22px;
}

.header-nav :deep(.q-tab) {
  position: relative;

  padding: 4px 0;
  min-height: 0;

  color: rgba(255, 255, 255, 0.72);

  transition: color 0.15s;
}

.header-nav :deep(.q-tab__content) {
  padding: 0;
  min-height: 0;
}

.header-nav :deep(.q-tab__label) {
  font-size: 13.5px;
  font-weight: 600;
  line-height: normal;
}

.header-nav :deep(.q-focus-helper) {
  display: none;
}

.header-nav :deep(.q-tab:hover) {
  color: #ffffff;
}

.header-nav :deep(.q-tab--active) {
  color: #ffffff;
}

.header-nav :deep(.q-tab__indicator) {
  position: absolute;
  left: 0;
  right: 0;
  bottom: -10px;

  height: 2px;
}

/* =========================
   HEADER — LOCATION
========================= */

.header-location {
  display: flex;
  align-items: center;

  min-width: 0;

  cursor: pointer;
}

.header-location-pill {
  display: flex;
  align-items: center;

  gap: 6px;
  min-width: 0;
  max-width: 300px;
  padding: 6px 10px;

  border-radius: 999px;

  background: rgba(255, 255, 255, 0.12);
  color: rgba(255, 255, 255, 0.9);

  font-size: 12.5px;
  font-weight: 500;

  transition: background-color 0.15s;
}

.header-location:hover .header-location-pill {
  background: rgba(255, 255, 255, 0.18);
}

.header-location-pill span {
  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.header-location-pill .q-icon {
  flex-shrink: 0;

  color: rgba(255, 255, 255, 0.7);
}

/* =========================
   RESPONSIVE
========================= */

/* Header needs to stack well before the content grids do — it has a lot
   of inline content (logo, nav, search, full address, account/cart) that
   gets cramped long before a 3-column product grid would. */
@media (max-width: 1024px) {
  .header-bar-inner {
    flex-wrap: wrap;

    row-gap: 10px;
    padding: 12px 16px;
  }

  .header-logo {
    height: 24px;
  }

  .header-actions {
    order: 1;
  }

  .header-location {
    order: 2;

    width: 100%;
    justify-content: center;

    cursor: default;
  }

  .header-location-pill {
    max-width: min(90%, 340px);
  }

  .header-location:hover .header-location-pill {
    background: rgba(255, 255, 255, 0.12);
  }

  .header-search {
    order: 3;

    width: 100%;
    max-width: none;
  }

  .header-nav {
    order: 4;

    width: 100%;

    padding-top: 4px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
  }

  .header-nav :deep(.q-tabs__content) {
    justify-content: space-around;
    width: 100%;
  }
}

/* Click-to-expand address / focus-to-grow search only makes sense on the
   single-row desktop header — on the stacked mobile/tablet layout both
   already show full-width, so the interaction is disabled there. */
@media (min-width: 1025px) {
  .header-search-compact {
    min-width: 0;
    max-width: 140px;
  }

  .header-search:focus-within {
    max-width: 640px;
  }

  .header-location-expanded {
    max-width: 480px;
  }

  .header-search:focus-within ~ .header-location .header-location-pill {
    max-width: 34px;
  }
}
</style>
