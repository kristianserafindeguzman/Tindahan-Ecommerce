<template>
  <q-page class="storefront-page" :style="showCheckoutBar ? { paddingBottom: checkoutBarHeight + 'px' } : null">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <template v-if="!orderPlaced">
        <span class="back-link" @click="router.push('/consumer/cart')">
          <q-icon name="o_arrow_back" size="15px" />
          Back to Cart
        </span>

        <h1 class="page-title">Checkout</h1>
        <p class="page-subtitle">Review your order before confirming.</p>
      </template>

      <div v-if="loading" class="checkout-loading">
        <q-spinner size="32px" />
        <p class="checkout-loading-text">Loading your order…</p>
      </div>

      <div v-else-if="orderPlaced" class="success-view">
        <div class="success-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
            <path d="M5 12.5l4.5 4.5L19 7.5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <h2 class="success-title">Order Placed!</h2>

        <p class="success-subtitle">
          Your order at <strong>{{ placedOrder.store?.store_name }}</strong> has been confirmed.<br class="subtitle-break" />
          We'll notify you when it's ready for pickup.
        </p>

        <div class="success-actions">
          <q-btn unelevated no-caps outline label="Continue Shopping" class="continue-btn" @click="router.push('/consumer/home')" />
          <q-btn unelevated no-caps label="View Order Details" class="view-order-btn" @click="viewOrderDetails" />
        </div>

        <div class="order-ref-card">
          <div class="order-ref-header">
            <div class="order-ref-header-info">
              <div class="order-ref-label">Order Reference</div>
              <div class="order-ref-id">#{{ placedOrder.order_id }}</div>
            </div>
            <div class="order-ref-pickup">
              <div class="order-ref-pickup-label">
                <q-icon name="o_schedule" size="12px" />
                Estimated Pickup
              </div>
              <div class="order-ref-pickup-value">{{ pickupTimeText }}</div>
            </div>
          </div>

          <q-separator class="card-divider order-ref-card-divider" />

          <div class="order-ref-body">
            <div v-for="item in placedOrder.items" :key="item.order_item_id" class="order-ref-item">
              <div class="order-ref-item-image">
                <img v-if="item.inventory?.image_url" :src="item.inventory.image_url" :alt="item.inventory?.product_name" />
                <q-icon v-else name="o_inventory_2" size="16px" />
              </div>
              <span class="order-ref-item-name"><strong class="order-ref-item-qty">{{ item.quantity }}x</strong> {{ item.inventory?.product_name || 'Item' }}</span>
              <span class="order-ref-item-price">₱{{ Number(item.subtotal).toFixed(2) }}</span>
            </div>

            <q-separator class="order-ref-separator" />

            <div class="order-ref-total">
              <span>Total</span>
              <span class="order-ref-total-amount">₱{{ Number(placedOrder.total_amount).toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <span class="need-help-link" @click="showContactSupport = true">
          <q-icon name="o_help" size="13px" />
          Need help with this order?
        </span>
      </div>

      <div v-else-if="!checkoutItems.length" class="checkout-empty">
        <q-icon name="o_shopping_cart" size="40px" class="checkout-empty-icon" />
        <p class="checkout-empty-text">There's nothing to check out.</p>
        <q-btn unelevated no-caps label="Back to Cart" class="browse-btn" @click="router.push('/consumer/cart')" />
      </div>

      <template v-else>
      <div class="checkout-layout">

        <div class="checkout-main">

          <!-- STORE INFO -->
          <div class="store-info-card">
            <div class="checkout-items-title">Pickup Location</div>
            <q-separator class="card-divider" />

            <div v-if="storeDetails?.latitude && storeDetails?.longitude" ref="storeMapEl" class="store-map-preview" />
            <div class="store-info-header">
              <q-icon name="o_location_on" size="20px" />
              <span class="store-info-name">{{ storeName }}</span>
            </div>
            <div v-if="storeAddressText" class="store-info-address">{{ storeAddressText }}</div>
          </div>

          <!-- ORDER ITEMS -->
          <div class="checkout-items-card">
            <div class="checkout-items-title">Order Items</div>
            <q-separator class="card-divider" />

            <div v-for="item in checkoutItems" :key="item.cartId" class="checkout-item">
              <div class="checkout-item-image">
                <img v-if="item.image" :src="item.image" :alt="item.name" />
                <q-icon v-else name="o_inventory_2" size="20px" />
              </div>

              <div class="checkout-item-info">
                <div class="checkout-item-name">{{ item.name }}</div>
                <div class="checkout-item-price">₱{{ item.price.toFixed(2) }} × {{ item.quantity }}</div>
              </div>

              <div class="checkout-item-line-total">₱{{ (item.price * item.quantity).toFixed(2) }}</div>
            </div>
          </div>

          <!-- PICKUP TIME -->
          <div class="pickup-time-card">
            <div class="checkout-items-title">Pickup Time</div>
            <q-separator class="card-divider" />

            <div class="pickup-time-options">
              <div
                class="time-option"
                :class="{ 'time-option-selected': pickupOption === 'asap' }"
                @click="selectPickupOption('asap')"
              >
                <span class="time-option-radio" />
                <div>
                  <div class="time-option-title">ASAP (10 - 15 mins)</div>
                  <div class="time-option-desc">We'll start preparing your order immediately.</div>
                </div>
              </div>

              <div
                class="time-option"
                :class="{ 'time-option-selected': pickupOption === 'schedule' }"
                @click="selectPickupOption('schedule')"
              >
                <span class="time-option-radio" />
                <div>
                  <div class="time-option-title">Schedule for later</div>
                  <div class="time-option-desc">
                    <span v-if="scheduledSlotText">{{ scheduledSlotText }}</span>
                    <span v-else>Choose a specific time today.</span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="pickupOption === 'schedule' && showScheduler" class="scheduler-panel">
              <div class="scheduler-label">Select Day</div>
              <div class="day-pills">
                <div
                  v-for="day in dayOptions"
                  :key="day.value"
                  class="day-pill"
                  :class="{ 'day-pill-selected': selectedDay === day.value }"
                  @click="selectedDay = day.value"
                >
                  <div class="day-pill-label">{{ day.label }}</div>
                  <div class="day-pill-date">{{ day.date }}</div>
                </div>
              </div>

              <div class="scheduler-label">Select Time</div>
              <q-select
                v-model="selectedSlot"
                :options="timeSlots"
                outlined
                dense
                hide-bottom-space
                behavior="menu"
                placeholder="Choose a time slot"
                class="time-select"
              />

              <q-btn
                unelevated
                no-caps
                label="Confirm Time"
                class="confirm-time-btn"
                :disable="!selectedSlot"
                @click="confirmSchedule"
              />
            </div>
          </div>

          <!-- CONTACT DETAILS -->
          <div class="contact-details-card">
            <div class="checkout-items-title">Contact Details</div>
            <q-separator class="card-divider" />

            <div class="details-display-row">
              <div class="contact-field">
                <span class="contact-field-icon">
                  <q-icon name="o_person" size="18px" />
                </span>
                <div>
                  <div class="contact-field-label">Full Name</div>
                  <div class="contact-field-value">{{ fullName || '—' }}</div>
                </div>
              </div>

              <q-separator vertical class="contact-field-divider" />

              <div class="contact-field">
                <span class="contact-field-icon">
                  <q-icon name="o_phone" size="18px" />
                </span>
                <div>
                  <div class="contact-field-label">Phone Number</div>
                  <div class="contact-field-value">{{ phoneNumber || '—' }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- PICKUP NOTICE -->
          <div class="pickup-info-card">
            <span class="pickup-info-icon">
              <q-icon name="o_storefront" size="16px" />
            </span>
            <div>
              <div class="pickup-info-title">Store Pickup</div>
              <div class="pickup-info-text">You'll pay and pick up this order at the store. No delivery.</div>
            </div>
          </div>

        </div>

        <aside v-if="!$q.screen.lt.md" class="checkout-summary">
          <div class="summary-title">Order Summary</div>
          <div class="summary-store">{{ storeName }}</div>

          <div v-for="item in checkoutItems" :key="item.cartId" class="summary-item-row">
            <div class="summary-item-image">
              <img v-if="item.image" :src="item.image" :alt="item.name" />
              <q-icon v-else name="o_inventory_2" size="14px" />
            </div>
            <div class="summary-item-info">
              <div class="summary-item-name">{{ item.name }}</div>
              <div class="summary-item-qty">Qty: {{ item.quantity }}</div>
            </div>
            <div class="summary-item-price">₱{{ (item.price * item.quantity).toFixed(2) }}</div>
          </div>

          <q-separator class="summary-separator" />

          <div class="summary-row summary-total">
            <span>Total</span>
            <span>₱{{ subtotal.toFixed(2) }}</span>
          </div>

          <q-btn
            unelevated
            no-caps
            label="Place Order"
            class="place-order-btn"
            :loading="placingOrder"
            @click="placeOrder"
          />
          <p class="summary-terms-note">
            By placing your order, you agree to our
            <a href="#" class="summary-terms-link" @click.prevent="showTerms = true">Terms of Service</a>
            and
            <a href="#" class="summary-terms-link" @click.prevent="showPrivacy = true">Privacy Policy</a>.
          </p>
        </aside>

      </div>

      <!-- Mobile/tablet: sticky checkout bar replaces the Order Summary sidebar, same pattern as ConsumerCart.vue. -->
      <div v-if="showCheckoutBar" ref="checkoutBarEl" class="checkout-sticky-bar">
        <div class="checkout-sticky-bar-top">
          <div class="checkout-sticky-bar-info">
            <div class="checkout-sticky-bar-title">Total</div>
            <div class="checkout-sticky-bar-subtitle">{{ checkoutItemCount }} item{{ checkoutItemCount === 1 ? '' : 's' }}</div>
          </div>
          <div class="checkout-sticky-bar-price">₱{{ subtotal.toFixed(2) }}</div>
        </div>

        <q-btn
          unelevated
          no-caps
          label="Place Order"
          class="place-order-btn"
          :loading="placingOrder"
          @click="placeOrder"
        />
      </div>
      </template>

    </div>

    <SiteFooter />

    <TermsModal v-model="showTerms" />
    <PrivacyModal v-model="showPrivacy" />
    <ContactSupportModal v-model="showContactSupport" />

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import TermsModal from '@/components/modals/TermsModal.vue'
import PrivacyModal from '@/components/modals/PrivacyModal.vue'
import ContactSupportModal from '@/components/modals/ContactSupportModal.vue'
import { useQuasar } from 'quasar'
import { useCart } from '@/composables/useCart'
import { useStores } from '@/composables/useStores'
import { api } from '@/boot/axios'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()

const { items, loading, fetchCart, checkout } = useCart()
const { stores, fetchStores } = useStores()

const user = ref({})
const placingOrder = ref(false)
const orderPlaced = ref(false)
const placedOrder = ref(null)
const showContactSupport = ref(false)

const placeOrder = async () => {
  placingOrder.value = true
  try {
    const data = await checkout(storeId.value)
    placedOrder.value = data.order
    orderPlaced.value = true
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to place order.'
    })
  } finally {
    placingOrder.value = false
  }
}

const viewOrderDetails = () => {
  if (!placedOrder.value) return
  localStorage.setItem('consumer_selected_order_id', placedOrder.value.order_id)
  router.push('/consumer/orders/details')
}

onMounted(async () => {
  fetchCart()
  fetchStores()
  try {
    const res = await api.get('/user')
    if (res.data?.user) user.value = res.data.user
  } catch (error) {
    console.error('Failed to load user profile', error)
  }
})

const showTerms = ref(false)
const showPrivacy = ref(false)

const storeId = computed(() => Number(route.query.storeId))

// Cart is the source of truth — this page just filters the shared cart down to the store being checked out.
const checkoutItems = computed(() => items.value.filter((item) => item.storeId === storeId.value))

const storeName = computed(() => checkoutItems.value[0]?.store || '')
const storeDetails = computed(() => stores.value.find((s) => s.id === storeId.value) || null)

const subtotal = computed(() => checkoutItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0))
const checkoutItemCount = computed(() => checkoutItems.value.reduce((sum, item) => sum + item.quantity, 0))

// Mobile/tablet: sticky checkout bar replaces the Order Summary sidebar.
const showCheckoutBar = computed(() => $q.screen.lt.md && !loading.value && !orderPlaced.value && checkoutItems.value.length > 0)

// Reserves exactly the bar's measured height (not a guessed px value) as bottom padding, so SiteFooter sits flush against it with no gap or overlap.
const checkoutBarEl = ref(null)
const checkoutBarHeight = ref(0)
let checkoutBarObserver = null

watch(checkoutBarEl, (el) => {
  checkoutBarObserver?.disconnect()
  checkoutBarObserver = null

  if (!el) {
    checkoutBarHeight.value = 0
    return
  }

  checkoutBarHeight.value = el.offsetHeight
  checkoutBarObserver = new ResizeObserver(() => {
    checkoutBarHeight.value = el.offsetHeight
  })
  checkoutBarObserver.observe(el)
})

const formatDistance = (meters) => {
  if (meters == null) return ''
  const rounded = Math.round(meters)
  if (rounded < 1000) return `${rounded} m away`
  return `${(meters / 1000).toFixed(1)} km away`
}

const storeAddressText = computed(() => {
  if (!storeDetails.value) return ''
  const dist = storeDetails.value.distance_meters != null ? formatDistance(storeDetails.value.distance_meters) : ''
  if (storeDetails.value.address && dist) return `${storeDetails.value.address} (${dist})`
  return storeDetails.value.address || dist
})

// CONTACT DETAILS — read-only, sourced from the logged-in profile.
const fullName = ref('')
const phoneNumber = ref('')

watch(user, (value) => {
  fullName.value = value.full_name || ''
  phoneNumber.value = value.phone_number || ''
})

// PICKUP TIME
const pickupOption = ref('asap')
const showScheduler = ref(false)
const selectedDay = ref('today')
const selectedSlot = ref('')
const confirmedSlotLabel = ref('')

const DAY_LABELS = ['Today', 'Tomorrow']

const dayOptions = computed(() => {
  const days = []
  for (let i = 0; i < 3; i++) {
    const date = new Date()
    date.setDate(date.getDate() + i)
    days.push({
      value: i === 0 ? 'today' : i === 1 ? 'tomorrow' : `day${i}`,
      label: DAY_LABELS[i] || date.toLocaleDateString('en-US', { weekday: 'short' }).toUpperCase(),
      date: date.toLocaleDateString('en-US', { day: '2-digit', month: 'short' })
    })
  }
  return days
})

const timeSlots = computed(() => {
  const formatTime = (date) => date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
  const start = new Date()
  start.setMinutes(Math.ceil(start.getMinutes() / 15) * 15 + 30, 0, 0)

  const slots = []
  for (let i = 0; i < 24; i++) {
    const from = new Date(start.getTime() + i * 15 * 60000)
    const to = new Date(from.getTime() + 15 * 60000)
    slots.push(`${formatTime(from)} - ${formatTime(to)}`)
  }
  return slots
})

const selectPickupOption = (option) => {
  pickupOption.value = option
  showScheduler.value = option === 'schedule'
}

const scheduledSlotText = computed(() => {
  if (!confirmedSlotLabel.value) return ''
  const dayLabel = dayOptions.value.find((day) => day.value === selectedDay.value)?.label || 'Today'
  return `${dayLabel}, ${confirmedSlotLabel.value}`
})

const confirmSchedule = () => {
  confirmedSlotLabel.value = selectedSlot.value
  showScheduler.value = false
}

const pickupTimeText = computed(() => {
  if (pickupOption.value === 'schedule' && scheduledSlotText.value) return scheduledSlotText.value
  return 'ASAP (10 - 15 mins)'
})

// STORE MAP PREVIEW — a small, non-interactive Leaflet "photo" pinning the store's location.
const storeMapEl = ref(null)
let storeMap = null
let storeMapMarker = null
let storeMapUnmounted = false

const storeMapIcon = L.divIcon({
  className: 'store-map-marker',
  html: `
    <svg width="30" height="40" viewBox="0 0 30 40">
      <path d="M15 0C6.7 0 0 6.7 0 15c0 11.25 15 25 15 25s15-13.75 15-25C30 6.7 23.3 0 15 0z" fill="#bd2427" stroke="#ffffff" stroke-width="1.5"/>
      <circle cx="15" cy="15" r="6.5" fill="#ffffff"/>
    </svg>
  `,
  iconSize: [30, 40],
  iconAnchor: [15, 40]
})

const initStoreMap = async () => {
  if (!storeDetails.value?.latitude || !storeDetails.value?.longitude) return

  const center = [storeDetails.value.latitude, storeDetails.value.longitude]

  // Map already live and its container still mounted — just move it to the (possibly new) store.
  if (storeMap && storeMapEl.value) {
    storeMap.setView(center, 16)
    if (storeMapMarker) storeMap.removeLayer(storeMapMarker)
    storeMapMarker = L.marker(center, { icon: storeMapIcon }).addTo(storeMap)
    return
  }

  // Container was removed (v-if toggled off) out from under a live map — tear it down before rebuilding.
  if (storeMap && !storeMapEl.value) {
    storeMap.remove()
    storeMap = null
    storeMapMarker = null
  }

  await nextTick()
  if (storeMapUnmounted || !storeMapEl.value) return

  storeMap = L.map(storeMapEl.value, {
    zoomControl: false,
    dragging: false,
    scrollWheelZoom: false,
    doubleClickZoom: false,
    touchZoom: false,
    boxZoom: false,
    keyboard: false
  }).setView(center, 16)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19
  }).addTo(storeMap)

  storeMapMarker = L.marker(center, { icon: storeMapIcon }).addTo(storeMap)
}

watch(storeDetails, () => initStoreMap())

// The success view replaces the form via v-if (not a component unmount), so clean up the map here since onBeforeUnmount won't fire.
watch(orderPlaced, (value) => {
  if (value && storeMap) {
    storeMap.remove()
    storeMap = null
    storeMapMarker = null
  }
})

onBeforeUnmount(() => {
  storeMapUnmounted = true
  if (storeMap) {
    storeMap.remove()
    storeMap = null
    storeMapMarker = null
  }
  checkoutBarObserver?.disconnect()
})
</script>

<style scoped>
.storefront-page {
  min-height: 100vh;

  display: flex;
  flex-direction: column;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

.page-content {
  flex: 1;

  width: 100%;
  max-width: 1000px;
  box-sizing: border-box;

  margin: 0 auto;

  padding: 24px;
}

.back-link {
  display: inline-flex;
  align-items: center;

  gap: 4px;
  margin-bottom: 14px;

  font-size: 13px;
  font-weight: 500;

  color: #767676;

  cursor: pointer;

  transition: color 0.15s;
}

.back-link:hover {
  color: #bd2427;
}

.page-title {
  margin: 0 0 4px;

  font-size: 22px;
  font-weight: 700;
  line-height: 1.3;

  color: #111111;
}

.page-subtitle {
  margin: 0 0 20px;

  font-size: 13px;

  color: #767676;
}

/* LOADING / EMPTY */

.checkout-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 16px;
  padding: 60px 0;

  color: #bd2427;
}

.checkout-loading-text {
  margin: 0;

  color: #8992a2;

  font-size: 14px;
}

/* SUCCESS VIEW */

.success-view {
  display: flex;
  flex-direction: column;
  align-items: center;

  max-width: 640px;
  margin: 0 auto;
  padding: 40px 16px 56px;

  text-align: center;
}

.success-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 84px;
  height: 84px;
  margin-bottom: 12px;

  border-radius: 50%;

  background: #dcfce7;
  color: #16a34a;
}

.success-title {
  margin: 0 0 8px;

  font-size: 27px;
  font-weight: 700;

  color: #111111;
}

.success-subtitle {
  margin: 0 0 20px;

  font-size: 15px;
  line-height: 1.5;

  color: #767676;
}

.success-subtitle strong {
  color: #111111;
}

.success-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;

  gap: 14px;
  margin-bottom: 32px;
}

.continue-btn {
  height: 48px;
  padding: 0 24px;

  border-radius: 6px;
  border: 1px solid #bd2427;

  background: #ffffff;
  color: #bd2427;

  font-size: 13.5px;
  font-weight: 600;

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.continue-btn:hover {
  background: #fdecec;

  transform: translateY(-1px);
}

.continue-btn:active {
  background: #fbdbdc;

  transform: translateY(0);
}

.continue-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.view-order-btn {
  height: 48px;
  padding: 0 24px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13.5px;
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.view-order-btn:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.view-order-btn:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.view-order-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.order-ref-card {
  width: 100%;
  box-sizing: border-box;

  margin-bottom: 20px;
  padding: 18px 24px;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);

  text-align: left;
}

.order-ref-header {
  display: flex;
  align-items: flex-start;

  gap: 12px;
}

.order-ref-header-info {
  flex: 1;
}

.order-ref-card-divider {
  margin: 16px 0 14px;

  background: #f0f0f0;
}

.order-ref-body {
  padding: 0;
}

.order-ref-label {
  margin-bottom: 4px;

  font-size: 11.5px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: #8992a2;
}

.order-ref-id {
  font-size: 17px;
  font-weight: 700;

  color: #111111;
}

.order-ref-pickup {
  text-align: right;

  white-space: nowrap;
}

.order-ref-pickup-label {
  display: flex;
  align-items: center;
  justify-content: flex-end;

  gap: 4px;

  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: #8992a2;
}

.order-ref-pickup-value {
  margin-top: 4px;

  font-size: 13.5px;
  font-weight: 700;

  color: #111111;
}

.order-ref-separator {
  margin: 14px 0;

  background: #f0f0f0;
}

.order-ref-item {
  display: grid;
  grid-template-columns: 40px 1fr auto;
  align-items: center;

  gap: 10px;
  padding: 7px 0;
}

.order-ref-item-image {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 40px;
  height: 40px;

  border-radius: 8px;
  border: 1px solid #e8e8e8;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  overflow: hidden;
}

.order-ref-item-image img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.order-ref-item-name {
  font-size: 13.5px;

  color: #555555;
}

.order-ref-item-price {
  font-size: 13.5px;
  font-weight: 600;

  color: #555555;
}

.order-ref-item-qty {
  margin-right: 4px;

  color: #bd2427;
}

.order-ref-total {
  display: flex;
  justify-content: space-between;

  padding-top: 8px;

  font-size: 16px;
  font-weight: 700;

  color: #111111;
}

.order-ref-total-amount {
  color: #111111;
}

.need-help-link {
  display: inline-flex;
  align-items: center;

  gap: 5px;

  font-size: 13px;

  color: #8992a2;

  cursor: pointer;

  transition: color 0.15s;
}

.need-help-link:hover {
  color: #bd2427;
}

.checkout-empty {
  display: flex;
  flex-direction: column;
  align-items: center;

  padding: 60px 24px;

  text-align: center;
}

.checkout-empty-icon {
  margin-bottom: 10px;

  color: #d8dce3;
}

.checkout-empty-text {
  margin: 0 0 20px;

  font-size: 14px;

  color: #8992a2;
}

.browse-btn {
  height: 48px;
  padding: 0 24px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13px;
  font-weight: 500;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.browse-btn:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

/* LAYOUT */

.checkout-layout {
  display: grid;
  grid-template-columns: 1fr 280px;

  gap: 20px;

  align-items: start;
}

.checkout-main {
  display: flex;
  flex-direction: column;

  gap: 14px;
}

/* CARD RECIPE — shared by store info, items, pickup time, contact details, and the pickup notice. */

.store-info-card,
.checkout-items-card,
.pickup-time-card,
.contact-details-card,
.pickup-info-card {
  padding: 16px;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.store-map-preview {
  position: relative;
  z-index: 0;
  isolation: isolate;

  width: 100%;
  height: 140px;
  margin-bottom: 12px;

  border-radius: 8px;

  overflow: hidden;
}

.store-map-preview :deep(.store-map-marker) {
  filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.35));
}

.store-info-header {
  display: flex;
  align-items: center;

  gap: 8px;

  font-size: 15px;
  font-weight: 700;

  color: #222222;
}

.store-info-header .q-icon {
  color: #bd2427;
}

.store-info-address {
  margin-top: 4px;
  margin-left: 28px;

  font-size: 12px;

  color: #8992a2;
}

.checkout-items-title {
  font-size: 13.5px;
  font-weight: 700;

  color: #111111;
}

.card-divider {
  margin: 10px 0 14px;

  background: #f0f0f0;
}

.checkout-item {
  display: grid;
  grid-template-columns: 44px 1fr auto;
  align-items: center;

  gap: 10px;
  padding: 8px 0;
}

.checkout-item + .checkout-item {
  border-top: 1px solid #f8f8f8;
}

.checkout-item-image {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 44px;
  height: 44px;

  border-radius: 8px;
  border: 1px solid #e8e8e8;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  overflow: hidden;
}

.checkout-item-image img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.checkout-item-info {
  min-width: 0;
}

.checkout-item-name {
  font-size: 13px;
  font-weight: 600;
  line-height: 1.35;

  color: #222222;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.checkout-item-price {
  margin-top: 2px;

  font-size: 12px;

  color: #8992a2;
}

.checkout-item-line-total {
  font-size: 13px;
  font-weight: 700;

  color: #bd2427;
}

/* PICKUP TIME */

.pickup-time-options {
  display: flex;

  gap: 10px;
}

.time-option {
  flex: 1;

  display: flex;
  align-items: flex-start;

  gap: 10px;
  padding: 12px;

  border-radius: 8px;
  border: 1px solid #e8e8e8;

  cursor: pointer;

  transition: border-color 0.15s, background-color 0.15s;
}

.time-option:hover {
  border-color: #f3c6c7;
}

.time-option-selected,
.time-option-selected:hover {
  border-color: #bd2427;
  background: #fdecec;
}

.time-option-radio {
  flex-shrink: 0;
  box-sizing: border-box;

  width: 16px;
  height: 16px;
  margin-top: 1px;

  border-radius: 50%;
  border: 2px solid #cbd5e1;

  background: #ffffff;

  transition: border-color 0.15s, border-width 0.15s;
}

.time-option-selected .time-option-radio {
  border-width: 5px;
  border-color: #bd2427;
}

.time-option-title {
  font-size: 13px;
  font-weight: 700;

  color: #222222;
}

.time-option-desc {
  margin-top: 2px;

  font-size: 11.5px;
  line-height: 1.4;

  color: #8992a2;
}

.time-option-selected .time-option-desc {
  color: #9c171b;
}

.scheduler-panel {
  margin-top: 14px;
  padding-top: 14px;

  border-top: 1px solid #f4f4f4;
}

.scheduler-label {
  margin-bottom: 8px;

  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: #8992a2;
}

.day-pills {
  display: flex;

  gap: 8px;
  margin-bottom: 16px;
}

.day-pill {
  flex: 1;

  text-align: center;
  padding: 8px 4px;

  border-radius: 8px;
  border: 1px solid #e8e8e8;

  cursor: pointer;

  transition: border-color 0.15s, background-color 0.15s;
}

.day-pill:hover {
  border-color: #f3c6c7;
}

.day-pill-selected,
.day-pill-selected:hover {
  border-color: #bd2427;
  background: #fdecec;
}

.day-pill-label {
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;

  color: #8992a2;
}

.day-pill-selected .day-pill-label {
  color: #9c171b;
}

.day-pill-date {
  margin-top: 2px;

  font-size: 13px;
  font-weight: 700;

  color: #222222;
}

.day-pill-selected .day-pill-date {
  color: #bd2427;
}

.time-select {
  margin-bottom: 14px;
}

.time-select :deep(.q-field__control) {
  height: 44px;

  border-radius: 8px;
}

.time-select :deep(.q-field__native) {
  font-size: 12.5px;

  color: #333333;
}

.confirm-time-btn {
  width: 100%;
  height: 44px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13px;
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.confirm-time-btn:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

/* CONTACT DETAILS */

.details-display-row {
  display: flex;
  align-items: center;

  gap: 16px;
}

.contact-field {
  flex: 1;

  display: flex;
  align-items: center;

  gap: 12px;
}

.contact-field-icon,
.pickup-info-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 32px;
  height: 32px;
  flex-shrink: 0;

  border-radius: 50%;

  background: #fdecec;
  color: #bd2427;
}

.contact-field-divider {
  height: 32px;
}

.contact-field-label {
  margin-bottom: 2px;

  font-size: 11.5px;
  font-weight: 500;

  color: #8992a2;
}

.contact-field-value {
  font-size: 14px;
  font-weight: 700;

  color: #222222;
}

.pickup-info-card {
  display: flex;
  align-items: flex-start;

  gap: 12px;
}

.pickup-info-title {
  margin-bottom: 2px;

  font-size: 13px;
  font-weight: 700;

  color: #111111;
}

.pickup-info-text {
  font-size: 12px;
  line-height: 1.4;

  color: #767676;
}

/* SUMMARY — same recipe as ConsumerCart.vue's Order Summary. */

.checkout-summary {
  position: sticky;
  top: 88px;

  padding: 18px;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.summary-title {
  margin-bottom: 4px;

  font-size: 14px;
  font-weight: 700;

  color: #111111;
}

.summary-store {
  margin-bottom: 12px;
  padding-bottom: 12px;

  border-bottom: 1px solid #f4f4f4;

  font-size: 12.5px;
  font-weight: 500;

  color: #767676;
}

.summary-item-row {
  display: grid;
  grid-template-columns: 28px 1fr auto;
  align-items: center;

  gap: 8px;
  padding: 5px 0;
}

.summary-item-image {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 28px;
  height: 28px;

  border-radius: 6px;
  border: 1px solid #e8e8e8;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  overflow: hidden;
}

.summary-item-image img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.summary-item-info {
  min-width: 0;
}

.summary-item-name {
  font-size: 12px;
  font-weight: 600;

  color: #222222;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.summary-item-qty {
  margin-top: 1px;

  font-size: 10.5px;

  color: #8992a2;
}

.summary-item-price {
  font-size: 12px;
  font-weight: 600;

  color: #555555;
}

.summary-row {
  display: flex;
  justify-content: space-between;

  margin-bottom: 8px;

  font-size: 13px;

  color: #555555;
}

.summary-separator {
  margin: 10px 0;
}

.summary-total {
  font-size: 15px;
  font-weight: 700;

  color: #111111;
}

.place-order-btn {
  width: 100%;
  height: 48px;
  margin-top: 14px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13.5px;
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.place-order-btn:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.place-order-btn:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.place-order-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.summary-terms-note {
  margin: 10px 0 0;

  font-size: 11px;
  line-height: 1.4;
  text-align: center;

  color: #8992a2;
}

.summary-terms-link {
  color: #bd2427;
  text-decoration: none;
}

.summary-terms-link:hover {
  text-decoration: underline;
}

/* MOBILE STICKY CHECKOUT BAR — same recipe as ConsumerCart.vue's .cart-checkout-bar. */

.checkout-sticky-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 100;

  padding: 12px 16px calc(12px + env(safe-area-inset-bottom, 0px));

  background: #ffffff;
  border-top: 1px solid #f0f0f0;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.06);
}

.checkout-sticky-bar-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.checkout-sticky-bar-info {
  min-width: 0;
}

.checkout-sticky-bar-title {
  font-size: 14px;
  font-weight: 700;

  color: #111111;
}

.checkout-sticky-bar-subtitle {
  margin-top: 2px;

  font-size: 12px;
  line-height: 1.4;

  color: #8992a2;
}

.checkout-sticky-bar-price {
  flex-shrink: 0;

  font-size: 16px;
  font-weight: 700;

  color: #bd2427;
}

/* RESPONSIVE */

@media (max-width: 800px) {
  .checkout-layout {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }

  .store-info-card,
  .checkout-items-card,
  .pickup-time-card,
  .contact-details-card,
  .pickup-info-card {
    padding: 12px 14px;
  }

  .pickup-time-options {
    flex-direction: column;
  }

  .details-display-row {
    flex-direction: column;
    align-items: stretch;

    gap: 10px;
  }

  .contact-field {
    gap: 10px;
  }

  .contact-field-divider {
    display: none;
  }

  .success-actions {
    flex-direction: column;
    width: 100%;
  }

  .continue-btn,
  .view-order-btn {
    width: 100%;
  }
}

@media (max-width: 700px) {
  .subtitle-break {
    display: none;
  }
}
</style>
