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

        <div class="header-search-wrap">
          <div class="header-search">
            <q-icon name="o_search" size="17px" class="header-search-icon" />
            <q-input
              :model-value="searchInput"
              dense
              borderless
              clearable
              placeholder="Search products and stores"
              class="header-search-input"
              autocomplete="off"
              @keyup.enter="submitSearch"
              @keydown.down.prevent="moveActiveSuggestion(1)"
              @keydown.up.prevent="moveActiveSuggestion(-1)"
              @keydown.esc="closeSuggestions"
              @focus="openSuggestions"
              @blur="closeSuggestions"
              @update:model-value="onSearchInput"
            />
            <q-btn
              round
              unelevated
              dense
              icon="o_search"
              aria-label="Search"
              class="header-search-btn"
              @click="submitSearch"
            />
          </div>

          <div v-if="suggestionsOpen" class="search-suggestions" @mousedown.prevent>
            <template v-if="!searchInput.trim()">
              <div v-if="recentSearches.length" class="suggestions-section">
                <div class="suggestions-section-header">
                  <span>Recent Searches</span>
                  <span class="suggestions-clear" @click="clearRecentSearches">Clear</span>
                </div>
                <div
                  v-for="(term, i) in recentSearches"
                  :key="term"
                  class="suggestion-item"
                  :class="{ 'suggestion-item-active': activeSuggestionIndex === i }"
                  @click="selectSuggestion(term)"
                >
                  <q-icon name="o_history" size="16px" class="suggestion-icon" />
                  <span class="suggestion-text">{{ term }}</span>
                </div>
              </div>
              <div v-else class="suggestions-empty">Start typing to search products and stores.</div>
            </template>

            <template v-else>
              <div v-if="productSuggestions.length" class="suggestions-section">
                <div class="suggestions-section-header"><span>Products</span></div>
                <div
                  v-for="(product, i) in productSuggestions"
                  :key="`p${product.id}`"
                  class="suggestion-item"
                  :class="{ 'suggestion-item-active': activeSuggestionIndex === i }"
                  @click="selectSuggestion(product.name)"
                >
                  <q-icon name="o_inventory_2" size="16px" class="suggestion-icon" />
                  <span class="suggestion-text">
                    <template v-for="(part, pi) in splitHighlightParts(product.name, searchInput)" :key="pi">
                      <mark v-if="part.match" class="suggestion-highlight">{{ part.text }}</mark>
                      <template v-else>{{ part.text }}</template>
                    </template>
                  </span>
                </div>
              </div>

              <div v-if="storeSuggestions.length" class="suggestions-section">
                <div class="suggestions-section-header"><span>Stores</span></div>
                <div
                  v-for="(store, i) in storeSuggestions"
                  :key="`s${store.id}`"
                  class="suggestion-item"
                  :class="{ 'suggestion-item-active': activeSuggestionIndex === productSuggestions.length + i }"
                  @click="selectSuggestion(store.name)"
                >
                  <q-icon name="o_storefront" size="16px" class="suggestion-icon" />
                  <span class="suggestion-text">
                    <template v-for="(part, pi) in splitHighlightParts(store.name, searchInput)" :key="pi">
                      <mark v-if="part.match" class="suggestion-highlight">{{ part.text }}</mark>
                      <template v-else>{{ part.text }}</template>
                    </template>
                  </span>
                </div>
              </div>

              <div v-if="!productSuggestions.length && !storeSuggestions.length" class="suggestions-empty">
                No matches for "{{ searchInput }}"
              </div>
            </template>
          </div>
        </div>

        <div ref="headerLocationRef" class="header-location" :title="address || 'Enter Address'" @click="toggleAddressMenu">
          <span class="header-location-pill" :class="{ 'header-location-expanded': addressMenuOpen }">
            <q-icon name="o_location_on" size="15px" />
            <span>{{ address || 'Enter Address' }}</span>
          </span>

          <div v-if="addressMenuOpen" class="address-menu-panel" @click.stop>
            <div class="address-menu-title">
              Address
            </div>

            <q-input
              v-model="draftAddress"
              dense
              outlined
              hide-bottom-space
              placeholder="Enter your address"
              class="address-menu-input"
              @keyup.enter="confirmAddress"
            />

            <VendorLocationMap class="address-menu-map" @location-selected="onLocationSelected" />

            <q-btn
              unelevated
              no-caps
              label="Confirm Address"
              class="address-menu-confirm"
              :disable="!draftAddress.trim()"
              @click="confirmAddress"
            />
          </div>
        </div>

        <div class="header-actions">

          <!-- LOGGED IN -->
          <template v-if="isLoggedIn">
            <q-btn flat dense :ripple="false" class="icon-btn">
              <q-icon name="o_notifications" size="20px" />
              <span v-if="unreadNotificationCount" class="icon-badge-count">{{ unreadNotificationCount }}</span>

              <q-menu anchor="bottom right" self="top right" content-class="cart-menu-panel" @show="fetchNotifications">
                <div class="cart-menu-inner" style="width: 320px;">
                  <div class="cart-menu-title" style="display:flex; justify-content:space-between; align-items:center;">
                    Notifications
                    <q-btn v-if="unreadNotificationCount" flat dense no-caps label="Mark all read" color="primary" size="sm" @click="markAllAsRead" />
                  </div>

                  <div v-if="!notifications.length" class="cart-menu-empty">No notifications yet.</div>

                  <template v-else>
                    <div v-for="notif in notifications.slice(0, 10)" :key="notif.notification_id" class="cart-menu-item notification-item" style="cursor:pointer;" @click="handleNotificationClick(notif)">
                      <div class="cart-menu-item-info" :style="notif.is_read ? 'opacity: 0.7;' : 'font-weight: bold;'">
                        <div class="cart-menu-item-name">{{ notif.title }}</div>
                        <div class="cart-menu-item-meta" style="white-space: normal; line-height: 1.3;">{{ notif.message }}</div>
                      </div>
                    </div>
                  </template>
                </div>
              </q-menu>
            </q-btn>

            <q-btn flat dense :ripple="false" class="icon-btn">
              <q-icon name="o_shopping_cart" size="20px" />
              <span v-if="cartItemCount" class="icon-badge-count">{{ cartItemCount }}</span>

              <q-menu anchor="bottom right" self="top right" content-class="cart-menu-panel" @show="fetchCart">
                <div class="cart-menu-inner">
                  <div class="cart-menu-title">My Cart</div>

                  <div v-if="!cartItems.length" class="cart-menu-empty">Your cart is empty.</div>

                  <template v-else>
                    <div v-for="item in cartItems.slice(0, 4)" :key="item.cartId" class="cart-menu-item">
                      <div class="cart-menu-item-image">
                        <img v-if="item.image" :src="item.image" :alt="item.name" />
                        <q-icon v-else name="o_inventory_2" size="16px" />
                      </div>
                      <div class="cart-menu-item-info">
                        <div class="cart-menu-item-name">{{ item.name }}</div>
                        <div class="cart-menu-item-meta">Qty {{ item.quantity }} · ₱{{ item.price.toFixed(2) }}</div>
                      </div>
                    </div>
                    <div v-if="cartItems.length > 4" class="cart-menu-more">
                      +{{ cartItems.length - 4 }} more item{{ cartItems.length - 4 === 1 ? '' : 's' }}
                    </div>
                  </template>

                  <q-btn
                    unelevated
                    no-caps
                    label="View All Cart"
                    class="cart-menu-view-all"
                    v-close-popup
                    @click="router.push('/consumer/cart')"
                  />
                </div>
              </q-menu>
            </q-btn>

            <q-btn flat dense no-caps :ripple="false" class="account-btn">
              <q-avatar size="24px" class="account-avatar">
                <img v-if="userAvatar" :src="userAvatar" />
                <q-icon v-else name="o_person" size="16px" />
              </q-avatar>
              <q-icon name="o_expand_more" size="16px" class="q-ml-xs" />

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
          </template>

        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useProducts } from '@/composables/useProducts'
import { useStores } from '@/composables/useStores'
import { useCart } from '@/composables/useCart'
import { useAddress } from '@/composables/useAddress'
import { splitHighlightParts } from '@/utils/textHighlight'
import { useCategories } from '@/composables/useCategories'
import VendorLocationMap from '@/components/leaflet/VendorLocationMap.vue'

const router = useRouter()
const route = useRoute()

const { address, setAddress } = useAddress()
const draftAddress = ref('')
const draftLocation = ref(null)
const addressMenuOpen = ref(false)
const headerLocationRef = ref(null)

const toggleAddressMenu = () => {
  addressMenuOpen.value = !addressMenuOpen.value
  if (addressMenuOpen.value) {
    draftAddress.value = address.value
    draftLocation.value = null
    closeSuggestions()
  }
}

// Same plain-div dropdown pattern as .search-suggestions, so clicking outside closes it.
const closeAddressMenuOnOutsideClick = (event) => {
  if (addressMenuOpen.value && !headerLocationRef.value?.contains(event.target)) {
    addressMenuOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', closeAddressMenuOnOutsideClick))
onBeforeUnmount(() => document.removeEventListener('click', closeAddressMenuOnOutsideClick))

const onLocationSelected = (location) => {
  draftAddress.value = location.address
  draftLocation.value = location
}

const confirmAddress = () => {
  if (!draftAddress.value.trim()) return
  if (draftLocation.value) {
    setAddress(draftAddress.value.trim(), draftLocation.value.latitude, draftLocation.value.longitude)
  } else {
    setAddress(draftAddress.value.trim())
  }
  addressMenuOpen.value = false
  
  // Refresh stores and products to apply new distance sorting
  fetchStores()
  fetchProducts()
}

const { products, fetchProducts } = useProducts()
const { stores, fetchStores } = useStores()
const { categories, fetchCategories } = useCategories()
const { items: cartItems, itemCount: cartItemCount, fetchCart } = useCart()

// Renders for both guests and logged-in consumers, so it reads localStorage directly rather than relying on a route guard.
const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

onMounted(() => {
  fetchProducts()
  fetchStores()
  fetchCategories()
  // Cart routes are auth-gated — an unconditional fetch would 401 for guests.
  if (isLoggedIn.value) {
    fetchCart()
    fetchNotifications()
  }
})

const userAvatar = computed(() => {
  try {
    const user = JSON.parse(localStorage.getItem('auth_user') || '{}')
    return user.profile_picture_url || null
  } catch {
    return null
  }
})

const notifications = ref([])
const unreadNotificationCount = computed(() => notifications.value.filter(n => !n.is_read).length)

const fetchNotifications = async () => {
  if (!isLoggedIn.value) return
  try {
    const res = await api.get('/consumer/notifications')
    notifications.value = res.data
  } catch (err) {
    console.error('Failed to fetch notifications', err)
  }
}

const markAsRead = async (id) => {
  const notif = notifications.value.find(n => n.notification_id === id)
  if (notif && !notif.is_read) {
    notif.is_read = true
    try {
      await api.patch(`/consumer/notifications/${id}/read`)
    } catch (err) {}
  }
}

const handleNotificationClick = async (notif) => {
  if (!notif.is_read) {
    await markAsRead(notif.notification_id)
  }
  if (notif.order_id) {
    localStorage.setItem('consumer_selected_order_id', notif.order_id)
    router.push('/consumer/orders/details')
  }
}

const markAllAsRead = async () => {
  notifications.value.forEach(n => n.is_read = true)
  try {
    await api.post('/consumer/notifications/read-all')
  } catch (err) {}
}

const handleLogout = async () => {
  try {
    await api.post('/logout')
  } catch {
    // Token may already be invalid.
  }

  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  localStorage.removeItem('auth_role')

  // Clear address & coordinates upon logout
  setAddress('')

  router.push('/login')
}

const goToLogin = () => {
  router.push('/login')
}

const goToSignup = () => {
  // LoginPage.vue opens the registration-choice dialog when it sees this query param.
  router.push({ path: '/login', query: { register: '1' } })
}

// searchInput is the user's live draft (bound to the field); searchQuery only changes on submit,
// and is the sole trigger for actually navigating/searching.
const searchInput = ref(route.query.q || '')
const searchQuery = ref(route.query.q || '')
const suggestionsOpen = ref(false)
const activeSuggestionIndex = ref(-1)

const RECENT_SEARCHES_KEY = 'recent_searches'
const MAX_RECENT_SEARCHES = 6

function loadRecentSearches() {
  try {
    const raw = JSON.parse(localStorage.getItem(RECENT_SEARCHES_KEY) || '[]')
    return Array.isArray(raw) ? raw : []
  } catch {
    return []
  }
}

const recentSearches = ref(loadRecentSearches())

function saveRecentSearch(term) {
  const next = [term, ...recentSearches.value.filter((s) => s.toLowerCase() !== term.toLowerCase())].slice(0, MAX_RECENT_SEARCHES)
  recentSearches.value = next
  localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(next))
}

const clearRecentSearches = () => {
  recentSearches.value = []
  localStorage.removeItem(RECENT_SEARCHES_KEY)
}

// The autocomplete dropdown is a lightweight typeahead — it's fine for it to live-filter off the
// draft input. It's the actual page search/navigation below that's submit-only.
const productSuggestions = computed(() => {
  const q = searchInput.value.trim().toLowerCase()
  if (!q) return []
  return products.value.filter((p) => p.name.toLowerCase().includes(q)).slice(0, 4)
})

const storeSuggestions = computed(() => {
  const q = searchInput.value.trim().toLowerCase()
  if (!q) return []
  return stores.value.filter((s) => s.name.toLowerCase().includes(q)).slice(0, 3)
})

// Flat list backing arrow-key navigation — recent searches when empty, live matches once typing.
const suggestionTerms = computed(() => {
  if (!searchInput.value.trim()) return recentSearches.value
  return [...productSuggestions.value.map((p) => p.name), ...storeSuggestions.value.map((s) => s.name)]
})

const openSuggestions = () => {
  suggestionsOpen.value = true
  activeSuggestionIndex.value = -1
  addressMenuOpen.value = false
}

const closeSuggestions = () => {
  suggestionsOpen.value = false
  activeSuggestionIndex.value = -1
}

const moveActiveSuggestion = (delta) => {
  if (!suggestionsOpen.value) {
    openSuggestions()
    return
  }
  const len = suggestionTerms.value.length
  if (!len) return
  activeSuggestionIndex.value = (activeSuggestionIndex.value + delta + len) % len
}

function navigateToSearchPage(q) {
  const alreadyThere = route.path === '/consumer/search' && (route.query.q || '') === q
  if (alreadyThere) return

  const target = { path: '/consumer/search', query: q ? { q } : {} }
  if (route.path === '/consumer/search') {
    router.replace(target)
  } else {
    router.push(target)
  }
}

// The only place a search is actually performed — fires on submit (searchQuery change), never while typing.
// watch(searchQuery, (q) => {
//   if (q) saveRecentSearch(q)
//   closeSuggestions()
//   navigateToSearchPage(q)
// })

const logSearch = async (query) => {
  const token = localStorage.getItem('auth_token')

  // Don't log guest searches
  if (!token || !query) return

  try {
    const latitude = localStorage.getItem('consumer_lat')
    const longitude = localStorage.getItem('consumer_lng')

    // Location is required by search_logs
    if (!latitude || !longitude) {
      console.warn('Search log skipped: consumer location is not available.')
      return
    }

    const normalizedQuery = query.trim().toLowerCase()

    // Find the searched product
    const matchedProduct = products.value.find(
      (product) =>
        product.name?.trim().toLowerCase() === normalizedQuery
    )

    if (!matchedProduct) {
      console.warn(
        'Search log skipped: product could not be matched.',
        query
      )
      return
    }

    // Product has category NAME, not category_id
    const matchedCategory = categories.value.find(
      (category) =>
        category.label?.trim().toLowerCase() ===
        matchedProduct.category?.trim().toLowerCase()
    )

    if (!matchedCategory) {
      console.warn(
        'Search log skipped: category could not be matched.',
        matchedProduct.category
      )
      return
    }

    const categoryId = matchedCategory.category_id ?? matchedCategory.id

    console.log('Search log data:', {
      query,
      product: matchedProduct.name,
      category: matchedCategory.label,
      category_id: categoryId,
      latitude,
      longitude
    })

    await api.post('/consumer/search-logs', {
      search_query: query,
      category_id: categoryId,
      search_lat: Number(latitude),
      search_lng: Number(longitude),
    })

    console.log('Search log saved successfully.')
  } catch (error) {
    console.error(
      'Failed to save search log:',
      error.response?.data || error
    )
  }
}


watch(searchQuery, (q) => {
  if (q) {
    saveRecentSearch(q)
  }

  closeSuggestions()
})



const selectSuggestion = (term) => {
  searchInput.value = term
  searchQuery.value = term
}

// Enter, the search button, or a mobile keyboard's search key all land here.
const submitSearch = async () => {
  if (
    activeSuggestionIndex.value >= 0 &&
    suggestionTerms.value[activeSuggestionIndex.value]
  ) {
    selectSuggestion(suggestionTerms.value[activeSuggestionIndex.value])
    return
  }

  const q = searchInput.value.trim()

  if (!q) return

  searchInput.value = q
  searchQuery.value = q

  // Save the search to the database
  await logSearch(q)

  // Navigate to search results
  navigateToSearchPage(q)
}

// Just opens/refreshes the suggestions dropdown — typing never triggers a search on its own.
const onSearchInput = (val) => {
  // Quasar's clearable "x" emits null (not ''), which would break every .trim() call downstream.
  searchInput.value = val || ''
  activeSuggestionIndex.value = -1
  suggestionsOpen.value = true
}

// Keeps the input synced when ?q= changes from outside this component (browser back/forward, a
// recent-search chip clicked on the search page itself) without remounting this component.
watch(() => route.query.q, (q) => {
  const next = q || ''
  searchInput.value = next
  searchQuery.value = next
})

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

  gap: 10px;

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

/* MINI CART DROPDOWN — the teleported panel needs :deep() + content-class to reach, matching .search-suggestions. */

:deep(.cart-menu-panel) {
  border-radius: 10px;
  border: 1px solid #e8e8e8;

  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
}

.cart-menu-inner {
  width: 280px;
  padding: 14px;

  font-family: 'Roboto', Arial, sans-serif;
}

.cart-menu-title {
  margin-bottom: 10px;

  font-size: 13.5px;
  font-weight: 700;

  color: #111111;
}

.cart-menu-empty {
  padding: 16px 0;

  text-align: center;

  font-size: 12.5px;

  color: #8992a2;
}

.cart-menu-item {
  display: flex;
  align-items: center;

  gap: 10px;
  padding: 6px 0;
}

.notification-item {
  align-items: flex-start;
  padding: 10px 0;
}

.notification-item + .notification-item {
  border-top: 1px solid #f0f0f0;
}

.cart-menu-item-image {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 36px;
  height: 36px;

  border-radius: 8px;
  border: 1px solid #e8e8e8;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  overflow: hidden;
}

.cart-menu-item-image img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.cart-menu-item-info {
  min-width: 0;
}

.cart-menu-item-name {
  font-size: 12.5px;
  font-weight: 600;

  color: #222222;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.cart-menu-item-meta {
  margin-top: 1px;

  font-size: 11.5px;

  color: #8992a2;
}

.cart-menu-more {
  margin-top: 4px;

  font-size: 11.5px;

  color: #8992a2;
}

.cart-menu-view-all {
  width: 100%;
  height: 44px;
  margin-top: 12px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 12.5px;
  font-weight: 500;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.cart-menu-view-all:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.cart-menu-view-all:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.cart-menu-view-all:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* ADDRESS PICKER DROPDOWN — same teleported-panel recipe as .cart-menu-panel. */

/* Same plain-div dropdown recipe/positioning as .search-suggestions, not a q-menu — sidesteps Quasar's menu
   positioning engine entirely, and sized generously like the search dropdown rather than tied to the pill's width. */
.address-menu-panel {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  z-index: 20;

  width: 460px;
  padding: 16px;
  box-sizing: border-box;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;

  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);

  font-family: 'Roboto', Arial, sans-serif;
}

.address-menu-title {
  margin-bottom: 10px;

  font-size: 13.5px;
  font-weight: 700;

  color: #111111;
}

.address-menu-input {
  min-width: 0;
  margin-bottom: 10px;
}

.address-menu-input :deep(.q-field__control) {
  height: 46px;

  border-radius: 10px;
}

.address-menu-map {
  height: 260px;
  margin-bottom: 12px;

  border: 1px solid #e8e8e8;
}

.address-menu-confirm {
  width: 100%;
  height: 48px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13px;
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.address-menu-confirm:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.address-menu-confirm:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.address-menu-confirm:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.address-menu-confirm:disabled {
  opacity: 0.45;
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

/* The teleported .q-menu root doesn't carry this component's scope attribute, so :deep(.account-menu ...) can't reach it — target q-item/q-item-section directly instead. */
.q-item__section {
  font-size: 13px;
}

.q-item__section--avatar {
  min-width: 0;
  padding-right: 12px;

  color: #000000;
}

.q-item {
  min-height: 40px;
  padding: 8px 14px;
}

/* HEADER — SEARCH BAR */

.header-search-wrap {
  position: relative;

  flex: 1 1 auto;
  min-width: 140px;
  max-width: 400px;

  transition: max-width 0.25s ease, min-width 0.25s ease;
}

.header-search {
  display: flex;
  align-items: center;

  height: 38px;

  border: none;
  border-radius: 8px;

  background: #ffffff;

  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);

  overflow: hidden;

  transition: box-shadow 0.15s;
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

.search-suggestions {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;

  max-height: 360px;
  overflow-y: auto;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;

  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);

  padding: 8px 0;

  z-index: 20;
}

.suggestions-section + .suggestions-section {
  margin-top: 4px;
  padding-top: 4px;

  border-top: 1px solid #f4f4f4;
}

.suggestions-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 6px 14px;

  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;

  color: #9ca3af;
}

.suggestions-clear {
  font-size: 11px;
  font-weight: 600;
  text-transform: none;
  letter-spacing: normal;

  color: #bd2427;

  cursor: pointer;
}

.suggestions-clear:hover {
  text-decoration: underline;
}

.suggestion-item {
  display: flex;
  align-items: center;

  gap: 10px;
  min-width: 0;
  padding: 9px 14px;

  font-size: 13px;
  color: #333333;

  cursor: pointer;

  transition: background-color 0.1s;
}

.suggestion-item:hover,
.suggestion-item.suggestion-item-active {
  background: #fdecec;
}

.suggestion-icon {
  flex-shrink: 0;

  color: #9ca3af;
}

.suggestion-text {
  overflow: hidden;
  min-width: 0;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.suggestion-highlight {
  background: transparent;
  color: #bd2427;
  font-weight: 700;
}

.suggestions-empty {
  padding: 14px;

  font-size: 12.5px;
  text-align: center;

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

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.header-search-btn:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.header-search-btn:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.header-search-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
}

/* HEADER — NAV (inline, next to logo) */

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

/* HEADER — LOCATION */

/* position:relative anchors .address-menu-panel below it, same pattern as .header-search-wrap/.search-suggestions.
   The search bar no longer shrinks when this pill expands (see .header-search-wrap above) — that shrink, not the
   pill's own growth, was what pulled this whole block leftward and made the panel appear to "slide". */
.header-location {
  position: relative;

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

/* Matches .address-menu-panel's own width, so the pill and the card below it line up edge to edge.
   No animated transition on this — the reflow it causes happens instantly instead of visibly sliding. */
.header-location-expanded {
  width: 460px;
  max-width: 460px;
}

.header-location-pill .q-icon {
  flex-shrink: 0;

  color: rgba(255, 255, 255, 0.7);
}

/* RESPONSIVE — header stacks well before the content grids do; it has more inline content and gets cramped sooner. */

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
  }

  .header-location-pill {
    max-width: min(90%, 340px);
  }

  .header-location:hover .header-location-pill {
    background: rgba(255, 255, 255, 0.12);
  }

  /* Fixed 460px overflows a phone screen — center it under the pill and cap it to the viewport instead. */
  .address-menu-panel {
    left: 50%;
    transform: translateX(-50%);

    width: calc(100vw - 32px);
  }

  .header-search-wrap {
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

/* Focus-to-grow search only makes sense on the single-row desktop header. */

@media (min-width: 1025px) {
  .header-search-wrap:focus-within {
    max-width: 640px;
  }

  .header-search-wrap:focus-within ~ .header-location .header-location-pill {
    max-width: 34px;
  }
}
</style>
