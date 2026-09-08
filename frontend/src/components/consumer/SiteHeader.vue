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

        <div class="header-location" :title="address || 'Enter Address'" @click="toggleAddressMenu">
          <span class="header-location-pill" :class="{ 'header-location-expanded': addressMenuOpen }">
            <q-icon name="o_location_on" size="15px" />
            <span>{{ address || 'Enter Address' }}</span>
          </span>

          <!--
            One panel, two shells. Below 900px this is a bottom sheet with a backdrop,
            above it a dropdown anchored to the pill — QDialog and QMenu respectively,
            picked at runtime so the content is written once rather than duplicated.
            Both bring their own backdrop, outside-click, Escape and focus handling,
            which is what the hand-rolled .address-menu-backdrop and the document
            click listener used to do.
          -->
          <component
            :is="isAddressSheet ? QDialog : QMenu"
            v-model="addressMenuOpen"
            v-bind="isAddressSheet
              ? { class: 'address-sheet-dialog' }
              : { noParentEvent: true, anchor: 'bottom left', self: 'top left', offset: [0, 8], class: 'address-menu-menu' }"
            :position="isAddressSheet ? 'bottom' : undefined"
          >
          <div class="address-menu-panel" :class="{ 'address-menu-panel-sheet': isAddressSheet }" @click.stop>
            <div v-if="isAddressSheet" class="address-menu-drag-handle" />

            <div class="address-menu-scroll">
              <div class="address-menu-title">
                <q-icon name="o_location_on" size="16px" class="address-menu-title-icon" />
                <span>Address</span>
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
            </div>

            <div class="address-menu-footer">
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
          </component>
        </div>

        <!-- Mobile: search collapses to an icon that reveals the real field below, so
             the field's suggestion menu and submit logic stay a single instance. -->
        <!-- Compact bar's right-hand cluster: notifications, cart, then the menu.
             The menu sits last so the two content actions stay closest to the thumb. -->
        <div v-if="isCompactHeader" ref="mobileActionsRef" class="header-mobile-actions">
          <NotificationsMenu v-if="isLoggedIn" :anchor-target="mobileActionsRef" />

          <q-btn
            flat
            dense
            round
            icon="o_shopping_cart"
            :aria-label="isLoggedIn && cartItemCount ? `Cart, ${cartItemCount} items` : 'Cart'"
            class="header-mobile-btn"
            @click="router.push('/consumer/cart')"
          >
            <span v-if="isLoggedIn && cartItemCount" class="icon-badge-count">{{ cartItemCount }}</span>
          </q-btn>

          <q-btn
            flat
            dense
            round
            icon="menu"
            aria-label="Open menu"
            class="header-mobile-btn"
            @click="mobileMenuOpen = true"
          />
        </div>

        <div ref="headerActionsRef" class="header-actions">

          <!-- LOGGED IN -->
          <template v-if="isLoggedIn">
            <div class="icon-btn-wrap">
              <q-btn flat dense :ripple="false" class="icon-btn" @click="toggleNotificationsMenu">
                <q-icon name="o_notifications" :size="actionIconSize" />
                <span v-if="unreadNotificationCount" class="icon-badge-count">{{ unreadNotificationCount }}</span>

                <!-- no-parent-event: the button's own @click already decides whether to
                     open (and fires the fetch), so QMenu must not also toggle itself. It
                     still handles outside-click, Escape, focus and placement. -->
                <q-menu
                  v-model="notificationsMenuOpen"
                  no-parent-event
                  :target="headerActionsRef || undefined"
                  anchor="bottom right"
                  self="top right"
                  :offset="[0, 8]"
                  class="header-menu"
                >
                <div class="cart-menu-inner notifications-inner">
                  <div class="cart-menu-title notifications-title">
                    Notifications
                    <q-btn v-if="unreadNotificationCount" flat dense no-caps label="Mark all read" color="primary" size="sm" @click="markAllAsRead" />
                  </div>

                  <div v-if="!notifications.length" class="cart-menu-empty">No notifications yet.</div>

                  <div v-else class="notifications-scroll">
                    <div v-for="notif in notifications.slice(0, 10)" :key="notif.notification_id" class="cart-menu-item notification-item" style="cursor:pointer;" @click="handleNotificationClick(notif)">
                      <div class="cart-menu-item-info" :style="notif.is_read ? 'opacity: 0.7;' : 'font-weight: bold;'">
                        <div class="cart-menu-item-name">{{ notif.title }}</div>
                        <div class="cart-menu-item-meta" style="white-space: normal; line-height: 1.3;">{{ notif.message }}</div>
                      </div>
                    </div>
                  </div>

                  <!-- The panel caps at 10; this is the way to the rest. Same
                       treatment as the cart menu's View All. -->
                  <q-btn
                    unelevated
                    no-caps
                    label="View All Notifications"
                    class="cart-menu-view-all"
                    @click="notificationsMenuOpen = false; router.push('/consumer/notifications')"
                  />
                </div>
                </q-menu>
              </q-btn>
            </div>

            <div class="icon-btn-wrap">
              <q-btn flat dense :ripple="false" class="icon-btn" @click="handleCartIconClick">
                <q-icon name="o_shopping_cart" :size="actionIconSize" />
                <span v-if="cartItemCount" class="icon-badge-count">{{ cartItemCount }}</span>

              <!-- Desktop only — on mobile/tablet handleCartIconClick navigates straight to the Cart page instead of opening this. -->
                <q-menu
                  v-model="cartMenuOpen"
                  no-parent-event
                  :target="headerActionsRef || undefined"
                  anchor="bottom right"
                  self="top right"
                  :offset="[0, 8]"
                  class="header-menu"
                >
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
                    @click="cartMenuOpen = false; router.push('/consumer/cart')"
                  />
                </div>
                </q-menu>
              </q-btn>
            </div>

            <div class="icon-btn-wrap">
              <q-btn flat dense no-caps :ripple="false" class="account-btn" @click="toggleAccountMenu">
                <q-avatar :size="avatarSize" class="account-avatar">
                  <img v-if="userAvatar" :src="userAvatar" />
                  <q-icon v-else name="o_person" :size="avatarIconSize" />
                </q-avatar>
                <q-icon name="o_expand_more" size="16px" class="q-ml-xs" />

                <q-menu
                  v-model="accountMenuOpen"
                  no-parent-event
                  :target="headerActionsRef || undefined"
                  anchor="bottom right"
                  self="top right"
                  :offset="[0, 8]"
                  class="header-menu"
                >
                <div class="cart-menu-inner account-menu-inner">
                  <q-list>
                    <q-item clickable @click="accountMenuOpen = false; router.push('/consumer/profile')">
                      <q-item-section>My Profile</q-item-section>
                    </q-item>
                    <q-item clickable @click="accountMenuOpen = false; router.push('/consumer/orders')">
                      <q-item-section>My Orders</q-item-section>
                    </q-item>
                    <q-separator />
                    <q-item clickable @click="accountMenuOpen = false; handleLogout()">
                      <q-item-section class="text-red-9">Logout</q-item-section>
                    </q-item>
                  </q-list>
                </div>
                </q-menu>
              </q-btn>
            </div>
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

    <q-dialog v-model="mobileMenuOpen" position="left" full-height class="mobile-menu-dialog">
      <q-card class="mobile-menu">
        <div class="mobile-menu-head">
          <img src="@/assets/tindahan-mobile.png" alt="Tindahan" class="mobile-menu-logo" />
          <q-btn v-close-popup flat round dense icon="close" aria-label="Close menu" class="mobile-menu-close" />
        </div>

        <q-separator />

        <div class="mobile-menu-scroll">

          <q-list padding>
            <q-item
              v-for="tab in tabs"
              :key="tab.value"
              v-close-popup
              clickable
              :active="activeTab === tab.value"
              active-class="mobile-menu-item-active"
              class="mobile-menu-item"
              @click="goToTab(tab)"
            >
              <q-item-section avatar class="mobile-menu-avatar">
                <q-icon :name="tab.icon" size="22px" />
              </q-item-section>
              <q-item-section>{{ tab.label }}</q-item-section>
            </q-item>
          </q-list>

          <q-separator class="mobile-menu-rule" />

          <q-list v-if="isLoggedIn" padding>
            <q-item v-close-popup clickable class="mobile-menu-item" @click="router.push('/consumer/orders')">
              <q-item-section avatar class="mobile-menu-avatar">
                <q-icon name="o_receipt_long" size="22px" />
              </q-item-section>
              <q-item-section>My Orders</q-item-section>
            </q-item>

            <q-item v-close-popup clickable class="mobile-menu-item" @click="router.push('/consumer/profile')">
              <q-item-section avatar class="mobile-menu-avatar">
                <q-icon name="o_person" size="22px" />
              </q-item-section>
              <q-item-section>My Profile</q-item-section>
            </q-item>

            <q-item v-close-popup clickable class="mobile-menu-item mobile-menu-logout" @click="handleLogout">
              <q-item-section avatar class="mobile-menu-avatar">
                <q-icon name="o_logout" size="22px" />
              </q-item-section>
              <q-item-section>Logout</q-item-section>
            </q-item>
          </q-list>

          <div v-else class="mobile-menu-auth">
            <q-btn v-close-popup unelevated no-caps label="Log in" class="mobile-menu-login" @click="goToLogin" />
            <q-btn v-close-popup unelevated no-caps label="Sign up" class="mobile-menu-signup" @click="goToSignup" />
          </div>
        </div>
      </q-card>
    </q-dialog>
  </header>

  <!--
    Dims the page behind an open search on phones and tablets, where the suggestion
    panel covers most of the screen and needs separating from the content under it.
    Desktop keeps the page undimmed: the panel is a small dropdown there and the
    surrounding catalogue stays useful context.

    A sibling of <header>, not a child — inside it, the backdrop covered the header's
    own gradient and dulled the bar.
  -->
  <div
    v-if="suggestionsOpen && $q.screen.lt.md"
    class="search-backdrop"
    @click="closeSuggestions"
  />
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { QMenu, QDialog } from 'quasar'
import { api } from '@/boot/axios'
import { useProducts } from '@/composables/useProducts'
import { useStores } from '@/composables/useStores'
import { useCart } from '@/composables/useCart'
import { useAddress } from '@/composables/useAddress'
import { splitHighlightParts } from '@/utils/textHighlight'
import { useCategories } from '@/composables/useCategories'
import { clearAuthStorage } from '@/utils/authStorage'
import VendorLocationMap from '@/components/leaflet/VendorLocationMap.vue'
import NotificationsMenu from '@/components/consumer/NotificationsMenu.vue'

const router = useRouter()
const route = useRoute()
const $q = useQuasar()

const { address, setAddress, autoDetectAddress } = useAddress()
const draftAddress = ref('')
const draftLocation = ref(null)
// The dropdown panels align to the action cluster's right edge, not to their own
// button — see the q-menu :target bindings above.
const headerActionsRef = ref(null)
const mobileActionsRef = ref(null)

/**
 * Only one header dropdown may be open at a time.
 *
 * QMenu dismisses itself on a click outside its target, but the target here is the
 * whole action cluster, so clicking a sibling button counts as "inside" and leaves
 * the previous panel open. All three align to the same right edge, so they stacked
 * on top of each other. Each toggle closes the rest before opening.
 */
const closeHeaderMenus = (except) => {
  if (except !== 'notifications') notificationsMenuOpen.value = false
  if (except !== 'cart') cartMenuOpen.value = false
  if (except !== 'account') accountMenuOpen.value = false
  if (except !== 'address') addressMenuOpen.value = false
}

const addressMenuOpen = ref(false)

// Tablet and mobile both get the bottom sheet, same breakpoint as the Products/Stores filter sheets.
const isAddressSheet = computed(() => $q.screen.width < 900)

// Below this the bar collapses to [menu · logo · cart]. From 768 to 1023 the header
// keeps its original stacked rows instead, and from 1024 it is a single row.
// Must stay in step with the @media (max-width: 767px) block below.
const COMPACT_HEADER_MAX = 768
const isCompactHeader = computed(() => $q.screen.width < COMPACT_HEADER_MAX)

// The stacked tablet header is a touch surface, so its action icons match the compact
// bar's 24px rather than the desktop's denser 20px.
const isTabletHeader = computed(
  () => !isCompactHeader.value && $q.screen.width < 1024
)

const actionIconSize = computed(() => (isTabletHeader.value ? '24px' : '20px'))
const avatarSize = computed(() => (isTabletHeader.value ? '28px' : '24px'))
const avatarIconSize = computed(() => (isTabletHeader.value ? '19px' : '16px'))

const toggleAddressMenu = () => {
  const next = !addressMenuOpen.value
  closeHeaderMenus('address')
  addressMenuOpen.value = next
  if (addressMenuOpen.value) {
    draftAddress.value = address.value
    draftLocation.value = null
    closeSuggestions()
  }
}

// Same plain-div dropdown pattern as .search-suggestions, so clicking outside closes it.
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

/* ------------------------------------------------------- MOBILE HEADER (< md) */

const mobileMenuOpen = ref(false)
// A width change can strand either affordance open in a layout that no longer shows it.
watch(isCompactHeader, (compact) => {
  if (!compact) {
    mobileMenuOpen.value = false
  }
})

const cartMenuOpen = ref(false)

// Mobile/tablet skip the dropdown preview entirely — the icon just navigates straight to the Cart page.
const handleCartIconClick = () => {
  if ($q.screen.lt.md) {
    router.push('/consumer/cart')
    return
  }
  const next = !cartMenuOpen.value
  closeHeaderMenus('cart')
  cartMenuOpen.value = next
  if (cartMenuOpen.value) fetchCart()
}

// Renders for both guests and logged-in consumers, so it reads localStorage directly rather than relying on a route guard.
const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

const accountMenuOpen = ref(false)

const toggleAccountMenu = () => {
  const next = !accountMenuOpen.value
  closeHeaderMenus('account')
  accountMenuOpen.value = next
}

onMounted(() => {
  // This header remounts on every consumer page, and these three back the search
  // suggestions rather than anything on screen. The pages that actually display
  // products/stores/categories refetch them on their own mount, so re-requesting
  // here just queued a duplicate 27KB round trip per navigation. Empty means this
  // is the first consumer page of the session; changing the address still forces a
  // refresh through confirmAddress().
  if (!products.value.length) fetchProducts()
  if (!stores.value.length) fetchStores()
  if (!categories.value.length) fetchCategories()
  autoDetectAddress()
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

const notificationsMenuOpen = ref(false)

// Mirrors handleCartIconClick: below md the preview dropdown is skipped entirely and
// the icon goes straight to the full page, which is the better use of a phone screen
// than a panel that only shows ten rows.
const toggleNotificationsMenu = () => {
  if ($q.screen.lt.md) {
    closeHeaderMenus()
    router.push('/consumer/notifications')
    return
  }
  const next = !notificationsMenuOpen.value
  closeHeaderMenus('notifications')
  notificationsMenuOpen.value = next
  if (notificationsMenuOpen.value) fetchNotifications()
}

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
    notificationsMenuOpen.value = false
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

  clearAuthStorage()

  router.push('/login')
}

const goToLogin = () => {
  router.push('/login')
}

const goToSignup = () => {
  // LoginPage.vue opens the registration-choice dialog when it sees this query param.
  router.push({ path: '/login', query: { register: '1' } })
}

// searchInput is the user's live draft; searchQuery only changes on submit and is what actually triggers navigation.
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

// The autocomplete dropdown is a lightweight typeahead, so it's fine to live-filter off the draft input — only the actual navigation below is submit-only.
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

const logSearch = async (query) => {
  const token = localStorage.getItem('auth_token')

  // Don't log guest searches
  if (!token || !query) return

  try {
    const latitude = localStorage.getItem('consumer_lat')
    const longitude = localStorage.getItem('consumer_lng')

    if (!latitude || !longitude) {
      console.warn('Search log skipped: consumer location is not available.')
      return
    }

    const normalizedQuery = query.trim().toLowerCase()
    
    // 1. Match against product names (partial, case-insensitive)
    let matchedProducts = products.value.filter(p => 
        p.name?.toLowerCase().includes(normalizedQuery)
    )
    
    // 2. If no product name match, try descriptions
    if (matchedProducts.length === 0) {
        matchedProducts = products.value.filter(p =>
            p.description?.toLowerCase().includes(normalizedQuery)
        )
    }
    
    let categoryId = null;
    
    // 3. If products matched, use the first match's category
    if (matchedProducts.length > 0) {
        const firstMatch = matchedProducts[0]
        const matchedCategory = categories.value.find(c => 
            c.label?.toLowerCase() === firstMatch.category?.toLowerCase()
        )
        categoryId = matchedCategory?.id ?? matchedCategory?.category_id ?? null
    } else {
        // 4. Try direct category name match
        const matchedCategory = categories.value.find(c =>
            c.label?.toLowerCase().includes(normalizedQuery) || c.category_name?.toLowerCase().includes(normalizedQuery)
        )
        if (matchedCategory) {
            categoryId = matchedCategory.id ?? matchedCategory.category_id
        }
    }

    console.log('Search log data:', {
      query,
      category_id: categoryId,
      latitude,
      longitude
    })

    await api.post('/consumer/search-logs', {
      search_query: query.trim(),
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

// The only place a search is actually saved — fires on submit (searchQuery change), never while typing.
watch(searchQuery, (q) => {
  if (q) {
    saveRecentSearch(q)
  }

  closeSuggestions()
})

const selectSuggestion = (term) => {
  searchInput.value = term
  searchQuery.value = term
  // Trigger submission on select
  submitSearch()
}

// Enter, the search button, or a mobile keyboard's search key all land here.
const submitSearch = async () => {
  // If the user presses enter while a suggestion is highlighted, use that suggestion.
  // We temporarily clear the active index so the next call to submitSearch won't loop.
  if (
    activeSuggestionIndex.value >= 0 &&
    suggestionTerms.value[activeSuggestionIndex.value]
  ) {
    const term = suggestionTerms.value[activeSuggestionIndex.value]
    activeSuggestionIndex.value = -1
    selectSuggestion(term)
    return
  }

  const q = searchInput.value.trim()

  if (!q) return

  searchInput.value = q
  searchQuery.value = q

  // Navigate to search results synchronously to avoid UI delay
  navigateToSearchPage(q)
  closeSuggestions()
  
  if (q) {
    saveRecentSearch(q)
  }

  // Save the search to the database
  await logSearch(q)
}

// Just opens/refreshes the suggestions dropdown — typing never triggers a search on its own.
const onSearchInput = (val) => {
  // Quasar's clearable "x" emits null (not ''), which would break every .trim() call downstream.
  searchInput.value = val || ''
  activeSuggestionIndex.value = -1
  suggestionsOpen.value = true
}

// Keeps the input synced when ?q= changes from outside this component (browser back/forward, a recent-search chip).
watch(() => route.query.q, (q) => {
  const next = q || ''
  searchInput.value = next
  searchQuery.value = next
})

const tabs = [
  { label: 'Home', value: 'home', to: '/consumer/home', icon: 'o_home' },
  { label: 'Products', value: 'products', to: '/consumer/products', icon: 'o_inventory_2' },
  { label: 'Stores', value: 'stores', to: '/consumer/stores', icon: 'o_storefront' }
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
  /* Higher than any page-level fixed-bottom bar (all z-index: 100), so this stacking context always wins and its dropdowns never end up underneath one. */
  z-index: 200;

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
  padding: 9px 24px;
}

.header-logo {
  height: 50px;
  width: auto;

  object-fit: contain;

  flex-shrink: 0;

  cursor: pointer;
}

/* position:relative so dropdown panels (plain divs, not q-menu — see .icon-btn-wrap below) anchor
   here instead of to their own narrow icon: this button isn't the rightmost thing in the header
   (cart/account sit to its right), so a panel anchored to its own wrapper's right:0 can still run
   past the viewport's left edge on narrow screens. Anchoring to the full-width row fixes that. */
.header-actions {
  position: relative;

  display: flex;
  align-items: center;

  gap: 10px;

  margin-left: auto;

  flex-shrink: 0;
}

/* Wraps each icon-btn so its dropdown panel (a plain sibling div, not a q-menu) has somewhere to
   live — a q-btn's internal ripple wrapper can clip absolutely positioned children, so the panel
   can't be nested inside the q-btn itself. No position:relative here on purpose — the panel's
   position:absolute resolves against .header-actions above instead (see its comment). */
.icon-btn-wrap {
  display: flex;
  align-items: center;
}

.icon-btn {
  position: relative;

  width: 38px;
  height: 38px;

  border-radius: var(--r-md);

  color: #ffffff;

  transition: background-color 0.15s;
}

.icon-btn:hover {
  background: rgba(255, 255, 255, 0.14);
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

  border-radius: var(--r-pill);
  border: 1.5px solid var(--c-brand-deep);

  background: #ffffff;
  color: var(--c-brand);

  font-size: 10px;
  font-weight: 700;
  line-height: 1;
}

/* HEADER DROPDOWNS (notifications/cart/account) — panel contents only. QMenu supplies
   the shell, placement and dismissal; see .header-menu in the unscoped block below. */

.cart-menu-inner {
  width: 280px;
  padding: 14px;

  font-family: 'Roboto', Arial, sans-serif;
}

/* Account's list is much shorter than Cart/Notifications' content — let it hug its own width instead of forcing the shared 280px.
   Horizontal padding drops to 0 (unlike .cart-menu-inner's 14px) so each row's hover highlight spans the full panel width
   edge-to-edge, like a normal menu, instead of floating inside an inset box. */
.account-menu-inner {
  width: auto;
  min-width: 160px;
  padding: 6px 0;
}

.account-menu-inner :deep(.q-item) {
  min-height: 40px;
  padding: 9px 14px;
}

.cart-menu-title {
  margin-bottom: 10px;

  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.cart-menu-empty {
  padding: 16px 0;

  text-align: center;

  font-size: var(--fs-sm);

  color: var(--c-muted);
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
  border-top: 1px solid var(--c-hairline);
}

/* Title/"Mark all read" row stays outside .notifications-scroll below, so it never scrolls out of view. */
.notifications-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Notifications can have up to 10 items; caps at roughly 5 rows tall before scrolling. */
.notifications-scroll {
  max-height: 335px;
  margin: 0 -14px;
  padding: 0 14px;

  overflow-y: auto;
}

.cart-menu-item-image {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 36px;
  height: 36px;

  border-radius: var(--r-md);
  border: 1px solid var(--c-border);

  background: linear-gradient(145deg, var(--c-surface) 0%, var(--c-surface) 100%);
  color: var(--c-brand);

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
  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text);

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.cart-menu-item-meta {
  margin-top: 1px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.cart-menu-more {
  margin-top: 4px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.cart-menu-view-all {
  width: 100%;
  height: 44px;
  margin-top: 12px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 500;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.cart-menu-view-all:hover {
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.cart-menu-view-all:active {
  background: var(--c-brand-active);

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.cart-menu-view-all:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* ADDRESS PICKER — panel contents only, shared by the QMenu (desktop) and QDialog
   bottom sheet (below 900px). Chrome lives on .address-menu-menu / .address-sheet-dialog. */

/* Same plain-div dropdown recipe as .search-suggestions, not a q-menu — sidesteps Quasar's menu positioning engine entirely. */
/* QMenu places this and, via .address-menu-menu below, draws the border, radius and
   shadow. Leaving them here too produced a 4px Quasar corner wrapped around a 10px
   panel and two stacked shadows. */
.address-menu-panel {
  width: 460px;
  padding: 16px;
  box-sizing: border-box;

  /* Both shells need this. .address-menu-menu paints the dropdown, but the QDialog
     sheet has no such wrapper — without a surface here the sheet is transparent. */
  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

.address-menu-title {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 10px;

  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.address-menu-title-icon {
  color: var(--c-brand);
}

.address-menu-input {
  min-width: 0;
  margin-bottom: 20px;
}

.address-menu-input :deep(.q-field__control) {
  height: 46px;

  border-radius: var(--r-lg);
}

.address-menu-map {
  height: 280px;

  border: 1px solid var(--c-border);
}

/* Desktop: panel's own 16px padding already wraps title/input/map/footer, so the map needs no margin-bottom of its own. */
.address-menu-footer {
  padding-top: 20px;
}

.address-menu-confirm {
  width: 100%;
  height: 48px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.address-menu-confirm:hover {
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.address-menu-confirm:active {
  background: var(--c-brand-active);

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

  border-radius: var(--r-md);
  border: 1px solid rgba(255, 255, 255, 0.5);

  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 500;

  transition: background-color 0.15s;
}

.auth-btn:not(.auth-btn-primary):hover {
  background: rgba(255, 255, 255, 0.12);
}

.auth-btn-primary {
  border: none;

  background: #ffffff;
  color: var(--c-brand);

  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.auth-btn-primary:hover {
  background: var(--c-hairline);
}

.account-btn {
  gap: 2px;

  height: 38px;
  padding: 0 12px 0 10px;

  border-radius: var(--r-md);

  color: #ffffff;

  font-size: var(--fs-sm);
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

.q-item__section {
  font-size: var(--fs-sm);
}

.q-item__section--avatar {
  min-width: 0;
  padding-right: 12px;

  color: var(--c-text);
}

.q-item {
  min-height: 40px;
  padding: 8px 14px;
}

/* HEADER — MOBILE BAR CONTROLS */

.header-mobile-btn {
  width: 40px;
  height: 40px;

  color: #ffffff;
}

.header-mobile-actions {
  display: flex;
  align-items: center;

  gap: 2px;
}

.header-mobile-btn {
  position: relative;
}

/* SLIDE-IN MENU */

.mobile-menu {
  display: flex;
  flex-direction: column;

  width: 310px;
  max-width: 84vw;

  /* No explicit height: the dialog inner is pinned to every edge and stretches this
     panel to fill it. Setting height:100% here actually broke it — WebKit left the card
     at its content height, and an explicit height suppresses the stretch that works in
     both engines. Viewport units are avoided too; see the unscoped block below. */
  align-self: stretch;
  max-height: 100%;

  border-radius: 0;

  font-family: 'Roboto', Arial, sans-serif;
}


.mobile-menu-head {
  display: flex;
  align-items: center;
  justify-content: space-between;

  flex-shrink: 0;
  padding: 14px 8px 14px 18px;
}

.mobile-menu-logo {
  height: 58px;
  width: auto;

  object-fit: contain;
}

.mobile-menu-close {
  color: var(--c-muted);
}

.mobile-menu-scroll {
  flex: 1 1 auto;
  min-height: 0;

  overflow-y: auto;
  padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px));
}

.mobile-menu-rule {
  margin: 4px 0;
}

.mobile-menu-item {
  min-height: 48px;

  font-size: var(--fs-lg);
  font-weight: 500;

  color: var(--c-text-2);
}

.mobile-menu-avatar {
  min-width: 0;
  padding-right: 14px;

  color: var(--c-muted);
}

.mobile-menu-item-active {
  background: var(--c-brand-tint);
  color: var(--c-brand);
  font-weight: 700;
}

.mobile-menu-item-active .mobile-menu-avatar {
  color: var(--c-brand);
}

.mobile-menu-logout,
.mobile-menu-logout .mobile-menu-avatar {
  color: var(--c-danger);
}

.mobile-menu-auth {
  display: flex;
  flex-direction: column;

  gap: 10px;
  padding: 16px 18px;
}

.mobile-menu-login,
.mobile-menu-signup {
  height: 48px;

  border-radius: var(--r-sm);

  font-size: var(--fs-md);
  font-weight: 600;
}

.mobile-menu-login {
  border: 1px solid var(--c-brand);

  background: #ffffff;
  color: var(--c-brand);
}

.mobile-menu-signup {
  background: var(--c-brand);
  color: #ffffff;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);
}

/* HEADER — SEARCH BAR */

/* Fixed to the viewport so it covers the page as the user scrolls. 150 sits under the
   header (200), so the bar and its suggestion panel stay lit, and over page content
   including the fixed bottom bars (100). */
.search-backdrop {
  position: fixed;
  inset: 0;
  z-index: 150;

  background: rgba(17, 17, 17, 0.45);

  animation: search-dim-in 200ms ease-out;
}

@keyframes search-dim-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
  .search-backdrop {
    animation: none;
  }
}

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
  border-radius: var(--r-md);

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

  color: var(--c-muted);

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

  font-size: var(--fs-sm);
  font-family: inherit;

  color: var(--c-text);
}

.header-search-input :deep(.q-field__native)::placeholder {
  color: var(--c-muted);
}

.search-suggestions {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;

  max-height: 360px;
  overflow-y: auto;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;

  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);

  padding: 8px 0;

  z-index: 20;
}

.suggestions-section + .suggestions-section {
  margin-top: 4px;
  padding-top: 4px;

  border-top: 1px solid var(--c-hairline);
}

.suggestions-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 6px 14px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;

  color: var(--c-muted);
}

.suggestions-clear {
  font-size: var(--fs-2xs);
  font-weight: 600;
  text-transform: none;
  letter-spacing: normal;

  color: var(--c-brand);

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

  font-size: var(--fs-sm);
  color: var(--c-text-2);

  cursor: pointer;

  transition: background-color 0.1s;
}

.suggestion-item:hover,
.suggestion-item.suggestion-item-active {
  background: var(--c-brand-tint);
}

.suggestion-icon {
  flex-shrink: 0;

  color: var(--c-muted);
}

.suggestion-text {
  overflow: hidden;
  min-width: 0;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.suggestion-highlight {
  background: transparent;
  color: var(--c-brand);
  font-weight: 700;
}

.suggestions-empty {
  padding: 14px;

  font-size: var(--fs-sm);
  text-align: center;

  color: var(--c-muted);
}

.header-search-btn {
  width: 38px;
  height: 30px;
  min-width: 38px;
  min-height: 30px;
  margin-right: 5px;

  border-radius: var(--r-md);

  background: var(--c-brand);
  color: #ffffff;

  flex-shrink: 0;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.header-search-btn:hover {
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.header-search-btn:active {
  background: var(--c-brand-active);

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
  font-size: var(--fs-md);
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

/* position:relative anchors .address-menu-panel below it, same pattern as .header-search-wrap/.search-suggestions. */
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

  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.12);
  color: rgba(255, 255, 255, 0.9);

  font-size: var(--fs-sm);
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

/* Matches .address-menu-panel's own width, so the pill and the card below it line up edge to edge. */
.header-location-expanded {
  width: 460px;
  max-width: 460px;
}

.header-location-pill .q-icon {
  flex-shrink: 0;

  color: rgba(255, 255, 255, 0.7);
}

/* RESPONSIVE — below 1024px the bar collapses to one row:
     [menu]        [logo, centred]        [search] [cart]
   The nav, address and account links move into the slide-in menu, and the search field
   is revealed on demand as a second row rather than living there permanently. That takes
   the header from four stacked rows to one. */

@media (max-width: 767px) {
  .header-bar-inner {
    position: relative;

    flex-wrap: wrap;

    gap: 4px;
    row-gap: 10px;
    padding: 6px 12px 12px;
  }

  .header-logo {
    flex: 0 0 auto;

    height: 46px;
    /* Pushes the action cluster to the opposite edge. */
    margin: 0 auto 0 0;
  }

  /* position: relative anchors the notifications panel to this cluster rather than to
     the bell's own narrow button, which would let it run off the right edge. */
  .header-mobile-actions {
    position: relative;

    flex: 0 0 auto;
  }

  /* Nav and the desktop action cluster live in the slide-in menu now. */
  .header-nav,
  .header-actions {
    display: none;
  }

  /* Forces the wrap after the bar's own row. Without it the address pill still fitted
     beside the logo and rode up into row one, which also shoved the logo off centre. */
  .header-bar-inner::after {
    content: '';
    order: 1;

    width: 100%;
  }

  /* Row two: address on the left, search taking the rest — the address is capped so a
     long one cannot squeeze the field down to nothing. */
  .header-location {
    order: 2;

    flex: 0 1 auto;
    min-width: 0;
    max-width: 44%;
  }

  .header-location-pill {
    width: 100%;
    max-width: none;
    height: 38px;
  }

  .header-search-wrap {
    order: 3;

    flex: 1 1 0;
    min-width: 0;
    max-width: none;

    transition: flex-grow 0.25s ease;
  }

  /* Rounded to match the pill beside it, so row two reads as one pair of controls. */
  .header-search {
    border-radius: var(--r-pill);
  }

  /* Hidden on mobile: the field is the only thing on its row, so the disc was pure
     weight. Submitting still works via the keyboard return key and by tapping a
     suggestion. */
  .header-search-btn {
    display: none;
  }

  /* Reclaims the space the button occupied. */
  .header-search-input :deep(.q-field__control) {
    padding-right: 16px;
  }

  /* Expand-on-focus, same behaviour as the desktop bar: focusing search collapses the
     address to its pin and hands the width over. .header-location is a later sibling of
     .header-search-wrap, so the same general-sibling selector works here. */
  /* Focused, the search takes the whole row and the address disappears.

     It has to leave the flow, not just shrink: clamping it to max-width:0 kept it a flex
     item, the search grew to the full line, and the 4px gap then tipped the line over so
     the search wrapped onto a third row — the header grew 38px instead of staying put.
     Absolute positioning takes it out of the line entirely, and opacity still animates. */
  .header-search-wrap:focus-within ~ .header-location {
    position: absolute;
    left: 12px;

    opacity: 0;
    pointer-events: none;
  }

  .header-location {
    transition: opacity 0.2s ease;
  }

/* Fixed 460px overflows a phone screen, so cap it. The left/translateX centring that
     used to sit here existed only to re-centre an absolutely positioned panel; QMenu and
     QDialog place it now, and the stale transform dragged the sheet half off-screen. */
  .address-menu-panel {
    max-width: calc(100vw - 32px);
  }
}

/* TABLET (768–1023) — the header's original stacked arrangement:
     row 1  logo + actions
     row 2  address, centred
     row 3  search
     row 4  nav, divided off by a hairline
   There is no hamburger here: every destination is already on screen. */

@media (min-width: 768px) and (max-width: 1023px) {
  .header-bar-inner {
    flex-wrap: wrap;

    row-gap: 10px;
    padding: 12px 16px;
  }

  .header-logo {
    height: 48px;
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

  /* The pill is centred rather than stretched here, so hovering it should not imply it
     is a full-width control. */
  .header-location:hover .header-location-pill {
    background: rgba(255, 255, 255, 0.12);
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

  /* Matches the compact bar's 40px touch targets; the desktop bar keeps 38px. */
  .icon-btn,
  .account-btn {
    height: 42px;
  }

  .icon-btn {
    width: 42px;
  }

/* Same cap for a tablet held in portrait; placement is QMenu's job now. */
  .address-menu-panel {
    max-width: calc(100vw - 32px);
  }
}

/* Focus-to-grow search only makes sense on the single-row desktop header. */

@media (min-width: 1024px) {
  .header-search-wrap:focus-within {
    max-width: 640px;
  }

  .header-search-wrap:focus-within ~ .header-location .header-location-pill {
    max-width: 34px;
  }
}

/* QDialog position="bottom" pins and animates the sheet (see the sheet transitions in
   app.scss), so this only reshapes the panel for the full-width variant. */
.address-menu-panel-sheet {
  display: flex;
  flex-direction: column;

  width: 100%;
  max-width: 100%;
  max-height: 85vh;
  padding: 0;

  border: none;
  border-radius: var(--r-2xl) var(--r-2xl) 0 0;

  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.18);
}

.address-menu-drag-handle {
  flex-shrink: 0;

  width: 36px;
  height: 4px;
  margin: 10px auto 0;

  border-radius: var(--r-pill);

  background: var(--c-border-strong);
}

/* flex: 1 1 auto (not flex: 1) — sizes to content first, only scrolls when it actually overflows. */
.address-menu-panel-sheet .address-menu-scroll {
  flex: 1 1 auto;
  min-height: 0;

  /* Bottom padding kept small — the footer below already adds its own top padding, so a full 20px here would double the gap. */
  padding: 16px 20px 5px;

  overflow-y: auto;
}

.address-menu-panel-sheet .address-menu-footer {
  flex-shrink: 0;

  padding: 14px 20px calc(14px + env(safe-area-inset-bottom, 0px));

  border-top: 1px solid var(--c-hairline);
}

</style>

<style>
/* QMenu defaults to a 4px radius and its own elevation shadow, which read as a square
   corner and a doubled shadow around this 10px panel. Matching --r-lg lines the
   dropdown up with the cards on every consumer page.

   max-height is Quasar's viewport-fit cap; it is what put a scrollbar inside the panel
   on shorter windows. fit-content lets the menu take its natural height, and the cap
   only re-engages when the panel genuinely cannot fit. */
.q-menu.address-menu-menu {
  /* Quasar caps a QMenu at 65vh, which is 468px on a 720px laptop viewport — 11px
     short of this panel's 479px and enough to put a scrollbar through the middle of
     the map. Two classes to out-specify that single-class default. */
  max-height: calc(100vh - 88px);

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;
  box-shadow: var(--sh-pop);
}

/* Quasar insets a bottom-position dialog by 24px once the viewport is wide enough,
   which on a tablet turns the address sheet into a floating card with rounded top
   corners and a gap either side. The panel was full-bleed at every sheet width
   before the QDialog conversion, so this restores that. */
.address-sheet-dialog .q-dialog__inner {
  padding: 0;
}

.address-sheet-dialog .q-dialog__inner > div {
  width: 100%;
  max-width: 100%;
}

/* QMenu teleports to <body>, so this cannot live in the scoped block above.
   Carries over the look the panels used to draw themselves; placement, outside-click,
   Escape and focus handling now come from QMenu itself. */
.header-menu {
  max-width: calc(100vw - 32px);
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);

  font-family: 'Roboto', Arial, sans-serif;
}

/* Unscoped on purpose: QDialog teleports to <body> and its inner gets no scoped
   attribute, so a :deep() rule from the scoped block above never matches it. Keyed on
   .mobile-menu-dialog so this touches only this panel.

   Quasar's default 24px inset is what pushed the full-height card 24px below the fold —
   a gap at the top and the same amount clipped off the bottom. */
.mobile-menu-dialog .q-dialog__inner {
  /* Quasar's default 24px inset pushed the full-height card below the fold: a gap at the
     top and the same amount clipped off the bottom. */
  padding: 0;

  /* stretch, so the panel fills the inner's height rather than being centred inside it
     at its own intrinsic height. */
  align-items: stretch;
}

.mobile-menu-dialog .q-dialog__inner > div {
  border-radius: 0;
}
</style>















