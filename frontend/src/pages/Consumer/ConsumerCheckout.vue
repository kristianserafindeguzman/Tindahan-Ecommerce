<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <span class="back-link" @click="router.push('/consumer/cart')">
        <q-icon name="o_arrow_back" size="15px" />
        Back to Cart
      </span>

      <h1 class="page-title">Checkout</h1>
      <p class="page-subtitle">Review your order before confirming.</p>

      <div v-if="loading" class="checkout-loading">Loading your order…</div>

      <div v-else-if="!checkoutItems.length" class="checkout-empty">
        <q-icon name="o_shopping_cart" size="40px" class="checkout-empty-icon" />
        <p class="checkout-empty-text">There's nothing to check out.</p>
        <q-btn unelevated no-caps label="Back to Cart" class="browse-btn" @click="router.push('/consumer/cart')" />
      </div>

      <div v-else class="checkout-layout">

        <div class="checkout-main">

          <!-- STORE INFO -->
          <div class="store-info-card">
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

        <aside class="checkout-summary">
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

    </div>

    <SiteFooter />

    <TermsModal v-model="showTerms" />
    <PrivacyModal v-model="showPrivacy" />

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import TermsModal from '@/components/modals/TermsModal.vue'
import PrivacyModal from '@/components/modals/PrivacyModal.vue'
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

const placeOrder = async () => {
  placingOrder.value = true
  try {
    const data = await checkout(storeId.value)
    $q.notify({
      type: 'positive',
      message: 'Order placed successfully!'
    })
    router.push('/consumer/orders')
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to place order.'
    })
  } finally {
    placingOrder.value = false
  }
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
  padding: 60px 0;

  text-align: center;

  color: #8992a2;

  font-size: 14px;
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

/* RESPONSIVE */

@media (max-width: 800px) {
  .checkout-layout {
    grid-template-columns: 1fr;
  }

  .checkout-summary {
    position: static;
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

    gap: 12px;
  }

  .contact-field-divider {
    display: none;
  }
}
</style>
