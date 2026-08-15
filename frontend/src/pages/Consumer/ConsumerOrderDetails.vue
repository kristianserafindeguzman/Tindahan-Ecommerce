<template>
  <q-page class="storefront-page">
    <SiteHeader />

    <div v-if="order" class="page-content">

      <span class="back-link" @click="router.push('/consumer/orders')">
        <q-icon name="o_arrow_back" size="15px" />
        Back to Orders
      </span>

      <h1 class="page-title">Order #{{ order.order_id }}</h1>

      <div class="order-layout">

        <div class="order-main">

          <!-- STATUS -->
          <div class="status-card">
            <div class="status-card-top">
              <div class="status-label">Order Status</div>
              <div v-if="!['picked_up', 'cancelled'].includes(order.status)" class="status-expected">Expected Today</div>
            </div>
            <div class="status-title" :class="statusTitleClass">{{ statusTitle }}</div>
            <p class="status-desc">{{ statusDescription }}</p>

            <q-separator class="card-divider" />

            <div v-if="order.status !== 'cancelled'" class="status-steps">
              <template v-for="(step, i) in statusSteps" :key="step.key">
                <div class="status-step">
                  <div class="status-step-circle" :class="{ 'status-step-circle-active': isStatusActive(step.key) }">
                    <q-icon :name="step.icon" size="16px" />
                  </div>
                  <div class="status-step-label" :class="{ 'status-step-label-active': isStatusActive(step.key) }">{{ step.label }}</div>
                </div>
                <div
                  v-if="i < statusSteps.length - 1"
                  class="status-step-line"
                  :class="{
                    'status-step-line-active': isStatusActive(statusSteps[i + 1].key),
                    'status-step-line-current': !isStatusActive(statusSteps[i + 1].key) && isStatusActive(step.key)
                  }"
                />
              </template>
            </div>

            <div v-if="order.status === 'cancelled'" class="cancellation-note">
              <q-icon name="o_error_outline" size="16px" />
              <div>
                <div class="cancellation-note-title">Cancellation Reason</div>
                <div class="cancellation-note-text">{{ order.cancellation_reason || 'No reason provided.' }}</div>
              </div>
            </div>
          </div>

          <!-- STORE INFO -->
          <div class="store-info-card">
            <div class="store-info-name-row">
              <q-icon name="o_location_on" size="20px" class="store-info-pin" />
              <span class="store-info-name">{{ order.store?.store_name }}</span>
            </div>
            <div v-if="storeAddressText" class="store-info-address">{{ storeAddressText }}</div>

            <q-separator class="card-divider" />

            <div class="order-meta-row">
              <div>
                <div class="order-meta-label">Pickup Time</div>
                <div class="order-meta-value">
                  <q-icon name="o_schedule" size="13px" />
                  ASAP (10 - 15 mins)
                </div>
              </div>
              <div class="order-meta-placed">
                <div class="order-meta-label">Order Placed</div>
                <div class="order-meta-value">{{ formatDate(order.created_at) }}</div>
              </div>
            </div>

            <q-separator class="card-divider" />

            <div class="order-actions-row">
              <q-btn
                v-if="order.status !== 'cancelled'"
                unelevated
                no-caps
                icon="o_cancel"
                label="Cancel Order"
                class="cancel-order-btn"
                :disable="order.status !== 'placed'"
                @click="showCancelDialog = true"
              >
                <q-tooltip v-if="order.status !== 'placed'">Cancellation is no longer allowed at this stage.</q-tooltip>
              </q-btn>
              <q-btn
                unelevated
                no-caps
                icon="o_directions"
                label="Get Directions"
                class="get-directions-btn"
                :disable="!hasDirections"
                @click="openDirections"
              />
            </div>
          </div>

        </div>

        <div class="order-summary-col">
          <aside class="order-summary">
            <div class="summary-title">Order Summary</div>
            <div class="summary-store">{{ order.store?.store_name }}</div>

            <div v-for="item in order.items" :key="item.order_item_id" class="summary-item-row">
              <div class="summary-item-image">
                <img v-if="item.inventory?.image_url" :src="item.inventory.image_url" :alt="item.inventory?.product_name" />
                <q-icon v-else name="o_inventory_2" size="14px" />
              </div>
              <div class="summary-item-info">
                <div class="summary-item-name">{{ item.inventory?.product_name || 'Product' }}</div>
                <div class="summary-item-qty">Qty: {{ item.quantity }}</div>
              </div>
              <div class="summary-item-price">₱{{ formatNumber(item.subtotal) }}</div>
            </div>

            <q-separator class="summary-separator" />

            <div class="summary-row summary-total">
              <span>Total</span>
              <span>₱{{ formatNumber(order.total_amount) }}</span>
            </div>
          </aside>

          <q-btn unelevated no-caps icon="o_receipt_long" label="View Receipt" class="view-receipt-btn" @click="showReceiptDialog = true" />
        </div>

      </div>

    </div>

    <div v-else class="order-loading">Loading order…</div>

    <SiteFooter />

    <q-dialog v-model="showCancelDialog">
      <q-card class="cancel-dialog-card">
        <q-btn flat round dense icon="close" class="cancel-dialog-close-btn" v-close-popup />

        <div class="cancel-dialog-title">Cancel Order?</div>
        <p class="cancel-dialog-text">Please select a reason for cancelling your order.</p>

        <q-separator class="card-divider" />

        <div class="cancel-reason-list">
          <div
            v-for="reason in cancellationReasonOptions"
            :key="reason"
            class="cancel-reason-option"
            :class="{ 'cancel-reason-option-selected': selectedCancelReason === reason }"
            @click="selectedCancelReason = reason"
          >
            <span class="cancel-reason-radio" />
            <span class="cancel-reason-label">{{ reason }}</span>
          </div>
        </div>

        <q-input
          v-if="selectedCancelReason === 'Other'"
          v-model="customCancelReason"
          type="textarea"
          placeholder="Tell us more…"
          outlined
          autogrow
          class="cancel-dialog-input"
        />

        <div class="cancel-dialog-actions">
          <q-btn unelevated no-caps flat label="Keep Order" class="keep-order-btn" v-close-popup />
          <q-btn
            unelevated
            no-caps
            label="Cancel Order"
            class="confirm-cancel-btn"
            :disable="!isCancelReasonValid"
            :loading="isCancelling"
            @click="confirmCancelOrder"
          />
        </div>
      </q-card>
    </q-dialog>

    <q-dialog v-model="showReceiptDialog">
      <div class="receipt-dialog-wrap">
        <q-card class="receipt-dialog-card">
          <q-btn flat round dense icon="close" class="receipt-close-btn" v-close-popup />

          <div class="receipt-icon-circle">
            <q-icon name="o_storefront" size="26px" />
          </div>

          <div class="receipt-store-name">{{ order?.store?.store_name }}</div>
          <div v-if="order?.store?.address" class="receipt-store-address">{{ order.store.address }}</div>

          <div class="receipt-ref-row">
            <span class="receipt-ref-badge">ORD-{{ order?.order_id }}</span>
          </div>

          <q-separator class="receipt-divider" />

          <div class="receipt-date">{{ formatReceiptDate(order?.created_at) }}</div>

          <div class="receipt-items">
            <div v-for="item in order?.items" :key="item.order_item_id" class="receipt-item-row">
              <span class="receipt-item-qty">{{ item.quantity }}x</span>
              <span class="receipt-item-name">{{ item.inventory?.product_name || 'Product' }}</span>
              <span class="receipt-item-price">₱{{ formatNumber(item.subtotal) }}</span>
            </div>
          </div>

          <q-separator class="receipt-divider" />

          <div class="receipt-total-row">
            <span>Total</span>
            <span class="receipt-total-amount">₱{{ formatNumber(order?.total_amount) }}</span>
          </div>

          <div class="receipt-print-footer">Thank you for shopping with Tindahan!</div>
        </q-card>

        <q-btn unelevated no-caps icon="o_download" label="Download" class="receipt-download-btn" @click="printOrder" />
      </div>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'

const router = useRouter()
const $q = useQuasar()
const order = ref(null)

const showCancelDialog = ref(false)
const isCancelling = ref(false)
const showReceiptDialog = ref(false)

const cancellationReasonOptions = ['I changed my mind', 'I ordered by mistake', 'Order is taking too long', 'Other']
const selectedCancelReason = ref('')
const customCancelReason = ref('')

const isCancelReasonValid = computed(() => {
  if (!selectedCancelReason.value) return false
  if (selectedCancelReason.value === 'Other') return customCancelReason.value.trim().length > 0
  return true
})

const finalCancelReason = computed(() => {
  return selectedCancelReason.value === 'Other' ? customCancelReason.value.trim() : selectedCancelReason.value
})

watch(showCancelDialog, (isOpen) => {
  if (!isOpen) {
    selectedCancelReason.value = ''
    customCancelReason.value = ''
  }
})

const statusSteps = [
  { key: 'placed', label: 'Placed', icon: 'o_check' },
  { key: 'preparing', label: 'Preparing', icon: 'o_soup_kitchen' },
  { key: 'ready_for_pickup', label: 'Ready', icon: 'o_inventory_2' },
  { key: 'picked_up', label: 'Picked Up', icon: 'o_home' }
]

const statusTitleClass = computed(() => {
  if (!order.value) return ''
  if (order.value.status === 'cancelled') return 'status-title-cancelled'
  if (order.value.status === 'picked_up') return 'status-title-done'
  return ''
})

const STATUS_TITLES = {
  placed: 'Order Placed',
  preparing: 'Order is Being Prepared',
  ready_for_pickup: 'Order is Ready for Pickup',
  picked_up: 'Order Has Been Picked Up',
  cancelled: 'Order Cancelled'
}

const statusTitle = computed(() => STATUS_TITLES[order.value?.status] || formatStatus(order.value?.status))

const statusDescription = computed(() => {
  switch (order.value?.status) {
    case 'placed': return "The store has received your order and will begin preparing it soon."
    case 'preparing': return 'The store is preparing your order.'
    case 'ready_for_pickup': return 'Your order is ready — head to the store to pick it up.'
    case 'picked_up': return 'This order has been picked up. Thanks for shopping with us!'
    case 'cancelled': return 'This order was cancelled.'
    default: return ''
  }
})

const formatStatus = (status) => {
  if (!status) return ''
  return status.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const formatDate = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const formatReceiptDate = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// Haversine distance — same formula as the backend's DistanceService, computed client-side since
// this endpoint doesn't return a precomputed distance_meters for a single order.
const calculateDistanceMeters = (lat1, lng1, lat2, lng2) => {
  const R = 6371000
  const toRad = (deg) => (deg * Math.PI) / 180
  const dLat = toRad(lat2 - lat1)
  const dLng = toRad(lng2 - lng1)
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}

const formatDistance = (meters) => {
  if (meters == null) return ''
  const rounded = Math.round(meters)
  if (rounded < 1000) return `${rounded} m away`
  return `${(meters / 1000).toFixed(1)} km away`
}

const storeAddressText = computed(() => {
  const address = order.value?.store?.address
  const cLat = order.value?.consumer_latitude
  const cLng = order.value?.consumer_longitude
  const sLat = order.value?.store?.latitude
  const sLng = order.value?.store?.longitude

  const dist = cLat != null && cLng != null && sLat != null && sLng != null
    ? formatDistance(calculateDistanceMeters(Number(cLat), Number(cLng), Number(sLat), Number(sLng)))
    : ''

  if (address && dist) return `${address} (${dist})`
  return address || dist
})

const isStatusActive = (step) => {
  if (!order.value) return false
  const flow = ['placed', 'preparing', 'ready_for_pickup', 'picked_up']
  const currentIndex = flow.indexOf(order.value.status)
  const stepIndex = flow.indexOf(step)
  return currentIndex >= stepIndex
}

const printOrder = () => {
  window.print()
}

const hasDirections = computed(() => {
  const o = order.value
  return !!(o?.consumer_latitude != null && o?.consumer_longitude != null && o?.store?.latitude != null && o?.store?.longitude != null)
})

const openDirections = () => {
  if (!hasDirections.value) return
  const { consumer_latitude: oLat, consumer_longitude: oLng } = order.value
  const { latitude: dLat, longitude: dLng } = order.value.store

  window.open(`https://www.google.com/maps/dir/?api=1&origin=${oLat},${oLng}&destination=${dLat},${dLng}`, '_blank')
}

const fetchOrderDetails = async () => {
  const id = localStorage.getItem('consumer_selected_order_id')
  if (!id) {
    router.replace('/consumer/orders')
    return
  }

  try {
    const res = await api.get(`/consumer/orders/${id}`)
    order.value = res.data
  } catch (error) {
    console.error('Failed to load order details', error)
    if (error.response?.status === 404 || error.response?.status === 403) {
      localStorage.removeItem('consumer_selected_order_id')
      router.replace('/consumer/orders')
    }
  }
}

const confirmCancelOrder = async () => {
  if (!order.value || !isCancelReasonValid.value) return
  isCancelling.value = true
  try {
    await api.patch(`/consumer/orders/${order.value.order_id}/cancel`, {
      cancellation_reason: finalCancelReason.value
    })
    $q.notify({ type: 'positive', message: 'Order cancelled successfully' })
    showCancelDialog.value = false
    selectedCancelReason.value = ''
    customCancelReason.value = ''
    fetchOrderDetails()
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to cancel order' })
  } finally {
    isCancelling.value = false
  }
}

onMounted(() => {
  fetchOrderDetails()
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

  padding: 24px 24px 56px;
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
  margin: 0 0 20px;

  font-size: 22px;
  font-weight: 700;
  line-height: 1.3;

  color: #111111;
}

.order-loading {
  padding: 80px 24px;

  text-align: center;

  color: #8992a2;

  font-size: 14px;
}

/* LAYOUT */

.order-layout {
  display: grid;
  grid-template-columns: 1fr 280px;

  gap: 20px;

  align-items: start;
}

.order-main {
  display: flex;
  flex-direction: column;

  gap: 18px;
}

/* CARD RECIPE — shared across status and store info cards. */

.status-card,
.store-info-card {
  padding: 20px 22px;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.card-divider {
  margin: 16px 0 18px;

  background: #f0f0f0;
}

/* STATUS CARD */

.status-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;
}

.status-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: #8992a2;
}

.status-expected {
  font-size: 12px;
  font-weight: 600;

  color: #767676;
}

.status-title {
  margin-top: 6px;

  font-size: 19px;
  font-weight: 700;

  color: #111111;
}

.status-title-cancelled {
  color: #b91c1c;
}

.status-title-done {
  color: #16a34a;
}

.status-desc {
  margin: 6px 0 0;

  font-size: 12.5px;
  line-height: 1.5;

  color: #767676;
}

.status-steps {
  display: flex;
  align-items: flex-start;

  margin-top: 4px;
}

.status-step {
  display: flex;
  flex-direction: column;
  align-items: center;

  flex-shrink: 0;

  width: 76px;
}

.status-step-circle {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 34px;
  height: 34px;

  border-radius: 50%;
  border: 2px solid #e2e2e2;

  background: #ffffff;
  color: #b0b0b8;

  transition: background-color 0.2s, border-color 0.2s, color 0.2s;
}

.status-step-circle-active {
  border-color: #bd2427;

  background: #bd2427;
  color: #ffffff;
}

.status-step-label {
  margin-top: 6px;

  font-size: 11px;
  font-weight: 600;

  color: #9ca3af;

  text-align: center;
}

.status-step-label-active {
  color: #222222;
}

.status-step-line {
  flex: 1;

  height: 2px;
  margin-top: 17px;

  background: #e2e2e2;
}

.status-step-line-active {
  background: #bd2427;
}

.status-step-line-current {
  background: linear-gradient(to right, #bd2427 50%, #e2e2e2 50%);
}

.cancellation-note {
  display: flex;
  align-items: flex-start;

  gap: 10px;
  margin-top: 18px;
  padding: 12px 14px;

  border-radius: 8px;

  background: #fef2f2;
  color: #b91c1c;
}

.cancellation-note-title {
  margin-bottom: 2px;

  font-size: 12.5px;
  font-weight: 700;
}

.cancellation-note-text {
  font-size: 12px;
  line-height: 1.4;

  color: #c0393a;
}

/* STORE INFO */

.store-info-name-row {
  display: flex;
  align-items: center;

  gap: 8px;
}

.store-info-pin {
  color: #bd2427;
}

.store-info-name {
  font-size: 15px;
  font-weight: 700;

  color: #222222;
}

.store-info-address {
  margin-top: 6px;
  padding-left: 28px;

  font-size: 12px;

  color: #8992a2;
}

.order-meta-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;

  gap: 12px;
}

.order-meta-placed {
  text-align: right;
}

.order-meta-label {
  margin-bottom: 4px;

  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: #8992a2;
}

.order-meta-value {
  display: flex;
  align-items: center;

  gap: 5px;

  font-size: 13px;
  font-weight: 700;

  color: #222222;
}

.order-meta-placed .order-meta-value {
  justify-content: flex-end;
}

.order-actions-row {
  display: flex;

  gap: 10px;
}

.cancel-order-btn {
  flex: 1;
  height: 48px;

  border-radius: 6px;
  border: 1px solid #e8e8e8;
  outline: none !important;

  background: #ffffff;
  color: #333333;

  font-size: 13px;
  font-weight: 600;

  transition: border-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

/* Hides Quasar's own focus/ripple overlay so it can't paint a stray ring over our border/hover treatment. */
.cancel-order-btn :deep(.q-focus-helper) {
  display: none;
}

.cancel-order-btn :deep(.q-icon) {
  margin-right: 0;

  font-size: 16px;
}

.cancel-order-btn :deep(.q-btn__content) {
  gap: 6px;
}

.cancel-order-btn:hover:not(:disabled) {
  border-color: #f3c6c7;

  box-shadow: 0 4px 12px rgba(189, 36, 39, 0.08);
  transform: translateY(-1px);
}

.cancel-order-btn:active:not(:disabled) {
  border-color: #f3c6c7;

  transform: translateY(0);
}

.cancel-order-btn:focus-visible {
  outline: none !important;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.get-directions-btn {
  flex: 1;
  height: 48px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13px;
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.get-directions-btn :deep(.q-icon) {
  margin-right: 0;

  font-size: 16px;
}

.get-directions-btn :deep(.q-btn__content) {
  gap: 6px;
}

.get-directions-btn:hover:not(:disabled) {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.get-directions-btn:active:not(:disabled) {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.get-directions-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* SUMMARY — same recipe as ConsumerCheckout.vue's Order Summary. */

.order-summary-col {
  position: sticky;
  top: 88px;

  display: flex;
  flex-direction: column;

  gap: 12px;
}

.order-summary {
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

.view-receipt-btn {
  width: 100%;
  height: 52px;

  border-radius: 6px;
  border: 1px solid #e8e8e8;
  outline: none !important;

  background: #ffffff;
  color: #333333;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);

  font-size: 13px;
  font-weight: 600;

  transition: border-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

/* Hides Quasar's own focus/ripple overlay so it can't paint a stray ring over our border/hover treatment. */
.view-receipt-btn :deep(.q-focus-helper) {
  display: none;
}

.view-receipt-btn :deep(.q-icon) {
  margin-right: 0;

  font-size: 16px;
}

.view-receipt-btn :deep(.q-btn__content) {
  gap: 6px;
}

.view-receipt-btn:hover {
  border-color: #f3c6c7;

  box-shadow: 0 4px 12px rgba(189, 36, 39, 0.08);
  transform: translateY(-1px);
}

.view-receipt-btn:active {
  border-color: #f3c6c7;

  transform: translateY(0);
}

.view-receipt-btn:focus-visible {
  outline: none !important;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* CANCEL DIALOG */

.cancel-dialog-card {
  position: relative;

  width: 420px;
  max-width: 92vw;

  padding: 24px;

  border-radius: 12px;
}

.cancel-dialog-close-btn {
  position: absolute;
  top: 12px;
  right: 12px;

  color: #666666;
}

.cancel-dialog-title {
  padding-right: 28px;

  font-size: 18px;
  font-weight: 700;

  color: #111111;
}

.cancel-dialog-text {
  margin: 6px 0 0;

  font-size: 13px;

  color: #767676;
}

.cancel-reason-list {
  display: flex;
  flex-direction: column;

  gap: 10px;
  margin-bottom: 4px;
}

.cancel-reason-option {
  display: flex;
  align-items: center;

  gap: 12px;
  padding: 14px 16px;

  border-radius: 8px;
  border: 1px solid #e2e2e2;

  cursor: pointer;

  transition: border-color 0.15s, background-color 0.15s;
}

.cancel-reason-option:hover {
  border-color: #d6d6da;
}

.cancel-reason-option-selected,
.cancel-reason-option-selected:hover {
  border-color: #bd2427;

  background: #fdecec;
}

.cancel-reason-radio {
  flex-shrink: 0;
  box-sizing: border-box;

  width: 18px;
  height: 18px;

  border-radius: 999px;
  border: 2px solid #cbd5e1;

  background: #ffffff;

  transition: border-color 0.15s, border-width 0.15s;
}

.cancel-reason-option-selected .cancel-reason-radio {
  border-width: 6px;
  border-color: #bd2427;
}

.cancel-reason-label {
  font-size: 13.5px;
  font-weight: 600;

  color: #222222;
}

.cancel-dialog-input {
  margin-top: 14px;
}

.cancel-dialog-actions {
  display: flex;
  justify-content: flex-end;

  gap: 10px;
  margin-top: 18px;
}

.keep-order-btn {
  flex: 1;
  height: 48px;

  border-radius: 6px;
  border: 1px solid #d6d6da;

  background: #ffffff;
  color: #333333;

  font-size: 13px;
  font-weight: 600;

  transition: background-color 0.15s;
}

.keep-order-btn:hover {
  background: #f7f7f8;
}

.confirm-cancel-btn {
  flex: 1;
  height: 48px;

  border-radius: 6px;

  background: #b91c1c;
  color: #ffffff;

  font-size: 13px;
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(185, 28, 28, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.confirm-cancel-btn:hover:not(:disabled) {
  background: #991616;

  box-shadow: 0 6px 16px rgba(185, 28, 28, 0.32);

  transform: translateY(-1px);
}

.confirm-cancel-btn:active:not(:disabled) {
  background: #7a1212;

  box-shadow: 0 2px 6px rgba(185, 28, 28, 0.28);

  transform: translateY(0);
}

.confirm-cancel-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.3);
}

.confirm-cancel-btn:disabled {
  box-shadow: none;
  opacity: 0.45;
}

/* RECEIPT DIALOG */

.receipt-dialog-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;

  gap: 16px;
}

.receipt-dialog-card {
  position: relative;

  width: 380px;
  max-width: 92vw;

  padding: 24px;

  border-radius: 12px;
  text-align: center;
}

.receipt-close-btn {
  position: absolute;
  top: 10px;
  right: 10px;

  color: #666666;
}

.receipt-icon-circle {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 52px;
  height: 52px;
  margin: 0 auto 14px;

  border-radius: 999px !important;

  background: #fdecec;
  color: #bd2427;
}

.receipt-store-name {
  font-size: 18px;
  font-weight: 700;

  color: #111111;
}

.receipt-store-address {
  margin-top: 4px;

  font-size: 12.5px;
  line-height: 1.4;

  color: #767676;
}

.receipt-ref-row {
  display: flex;
  justify-content: center;

  margin-top: 12px;
}

.receipt-ref-badge {
  padding: 4px 12px;

  border-radius: 999px;

  background: #f4f4f4;
  color: #333333;

  font-size: 11.5px;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.receipt-divider {
  margin: 18px 0;

  background: #f0f0f0;
}

.receipt-date {
  margin-bottom: 12px;

  font-size: 12px;

  color: #8992a2;
}

.receipt-items {
  display: flex;
  flex-direction: column;

  gap: 8px;
}

.receipt-item-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;

  gap: 8px;

  font-size: 13px;

  text-align: left;
}

.receipt-item-qty {
  font-weight: 600;

  color: #222222;
}

.receipt-item-name {
  font-weight: 600;

  color: #222222;
}

.receipt-item-price {
  font-weight: 700;

  color: #222222;
}

.receipt-total-row {
  display: flex;
  justify-content: space-between;

  margin-top: 4px;

  font-size: 16px;
  font-weight: 700;

  color: #111111;
}

.receipt-total-amount {
  color: #bd2427;
}

.receipt-download-btn {
  height: 46px;
  padding: 0 28px;

  border-radius: 999px;

  background: #ffffff;
  color: #111111;

  font-size: 13px;
  font-weight: 700;

  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);

  transition: background-color 0.15s, transform 0.2s;
}

.receipt-download-btn:hover {
  background: #f4f4f4;

  transform: translateY(-1px);
}

.receipt-print-footer {
  display: none;
}

/* PRINT — isolate just the receipt card as a clean, centered document; hide the rest of
   the page and Quasar's dialog chrome. Positions are forced to `static` (rather than
   `fixed`) because Quasar's dialog transition leaves a `transform` on an ancestor, which
   would otherwise turn `position: fixed` into "fixed relative to that ancestor" instead
   of the page. */

@media print {
  @page {
    margin: 14mm;
  }

  body * {
    visibility: hidden;
  }

  .receipt-dialog-card,
  .receipt-dialog-card * {
    visibility: visible;
  }

  :deep(.q-dialog__inner) {
    position: static !important;
    display: block !important;

    width: 100% !important;
    max-width: 100% !important;
    padding: 0 !important;
  }

  .receipt-dialog-wrap {
    position: static !important;
    display: block !important;

    width: 100% !important;
  }

  .receipt-dialog-card {
    position: static !important;

    width: 100% !important;
    max-width: 420px !important;
    margin: 0 auto !important;
    padding: 36px 32px !important;

    box-shadow: none !important;
    border: 1px solid #e2e2e2 !important;
    border-radius: 6px !important;
  }

  .receipt-close-btn,
  .receipt-download-btn {
    display: none !important;
  }

  .receipt-print-footer {
    display: block;

    margin-top: 26px;
    padding-top: 16px;

    border-top: 1px dashed #d6d6da;

    font-size: 11.5px;

    color: #8992a2;
    text-align: center;
  }
}

/* RESPONSIVE */

@media (max-width: 800px) {
  .order-layout {
    grid-template-columns: 1fr;
  }

  .order-summary-col {
    position: static;
  }
}

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }

  .status-card,
  .store-info-card {
    padding: 16px 16px;
  }

  .order-actions-row {
    flex-direction: column;
  }
}
</style>
