<template>
  <q-page class="storefront-page" :style="showActionsBar ? { paddingBottom: actionsBarHeight + 'px' } : null">
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
          <div class="status-card" :class="statusCardClass">
            <div class="status-card-top">
              <div class="status-label">Order Status</div>
              <div v-if="!['picked_up', 'cancelled'].includes(order.status)" class="status-expected">Expected Today</div>
            </div>
            <div class="status-title" :class="statusTitleClass">{{ statusTitle }}</div>
            <p class="status-desc">{{ statusDescription }}</p>

            <q-separator class="card-divider" />

            <div v-if="order.status !== 'cancelled'" class="status-steps" :class="{ 'status-steps-done': order.status === 'picked_up' }">
              <template v-for="(step, i) in statusSteps" :key="step.key">
                <div class="status-step">
                  <div
                    class="status-step-circle"
                    :class="{
                      'status-step-circle-done': isStatusActive(step.key) && !isStatusCurrent(step.key),
                      'status-step-circle-current': isStatusCurrent(step.key)
                    }"
                  >
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

            <!-- Desktop: in-flow with the rest of the store info card. -->
            <div v-if="!showActionsBar" class="order-actions-row">
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

      <!-- Mobile: fixed bottom bar, same buttons/styling as the desktop in-card row. -->
      <div v-if="showActionsBar" ref="actionsBarEl" class="order-actions-fixed">
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

    <div v-else class="order-loading">
      <q-spinner size="32px" />
      <p class="order-loading-text">Loading order…</p>
    </div>

    <SiteFooter />

    <q-dialog v-model="showCancelDialog" :position="$q.screen.lt.sm ? 'bottom' : 'standard'">
      <q-card class="cancel-dialog-card" :class="{ 'cancel-dialog-card-sheet': $q.screen.lt.sm }">
        <div v-if="$q.screen.lt.sm" class="cancel-dialog-drag-handle" />

        <q-btn flat round dense icon="close" class="cancel-dialog-close-btn" aria-label="Close" v-close-popup />

        <div class="cancel-dialog-scroll">
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
        </div>

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
          <q-btn flat round dense icon="close" class="receipt-close-btn" aria-label="Close receipt" v-close-popup />

          <div class="receipt-icon-circle">
            <q-icon name="o_storefront" size="26px" />
          </div>

          <div class="receipt-store-name">{{ order?.store?.store_name }}</div>
          <div v-if="order?.store?.address" class="receipt-store-address">
            <q-icon name="o_location_on" size="12px" />
            {{ order.store.address }}
          </div>

          <div class="receipt-ref-row">
            <span class="receipt-ref-badge">ORDER <strong class="receipt-ref-number">#{{ order?.order_id }}</strong></span>
          </div>

          <div class="receipt-date-banner">
            <q-icon name="o_calendar_month" size="14px" />
            <span>{{ formatReceiptDateParts(order?.created_at) }}</span>
          </div>

          <div class="receipt-items-header">
            <span>Items</span>
            <span>Amount</span>
          </div>

          <div class="receipt-items">
            <div v-for="item in order?.items" :key="item.order_item_id" class="receipt-item-row">
              <span class="receipt-item-qty">{{ item.quantity }}x</span>
              <span class="receipt-item-name">{{ item.inventory?.product_name || 'Product' }}</span>
              <span class="receipt-item-price">₱{{ formatNumber(item.subtotal) }}</span>
            </div>
          </div>

          <q-separator class="receipt-divider" />

          <div class="receipt-total-band">
            <span>Total</span>
            <span class="receipt-total-amount">₱{{ formatNumber(order?.total_amount) }}</span>
          </div>

          <div class="receipt-print-footer">Thank you for shopping with Tindahan!</div>
        </q-card>

        <q-btn v-if="order?.status === 'picked_up'" unelevated no-caps icon="o_download" label="Download PDF" class="receipt-download-btn" :loading="isExporting" @click="downloadReceipt" />
      </div>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { api } from '@/boot/axios'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import { formatDistance, calculateDistanceMeters } from '@/utils/distance'

const router = useRouter()
const $q = useQuasar()
const order = ref(null)

const showCancelDialog = ref(false)
const isCancelling = ref(false)
const showReceiptDialog = ref(false)
const isExporting = ref(false)

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

// Same icon set as the vendor's Order Timeline (OrderDetails.vue), for consistency across both sides.
const statusSteps = [
  { key: 'placed', label: 'Placed', icon: 'o_shopping_cart' },
  { key: 'preparing', label: 'Preparing', icon: 'o_inventory_2' },
  { key: 'ready_for_pickup', label: 'Ready', icon: 'o_storefront' },
  { key: 'picked_up', label: 'Picked Up', icon: 'o_task_alt' }
]

/*
  The two states you should be able to read without parsing the tracker. Everything else
  is mid-flight and stays on the default white card.
*/
const STATUS_CARD_TONES = {
  cancelled: 'danger',
  picked_up: 'success'
}

const statusCardClass = computed(() => {
  const tone = STATUS_CARD_TONES[order.value?.status]
  return tone ? `status-card--${tone}` : ''
})

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

const formatReceiptDateParts = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  const datePart = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
  const timePart = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
  return `${datePart} • ${timePart}`
}

const storeAddressText = computed(() => {
  const address = order.value?.store?.address
  const cLat = order.value?.consumer_latitude
  const cLng = order.value?.consumer_longitude
  const sLat = order.value?.store?.latitude
  const sLng = order.value?.store?.longitude

  // See ConsumerOrders: the guard lives in calculateDistanceMeters, and the raw
  // values are passed because Number(null) would coerce a missing coordinate to 0.
  const dist = formatDistance(calculateDistanceMeters(cLat, cLng, sLat, sLng))

  if (address && dist) return `${address} (${dist})`
  return address || dist
})

// isStatusActive covers "reached", which includes every completed step. The tracker
// needs "reached but behind us" and "where we are now" to look different, or all four
// discs render identically solid and nothing marks the current stage.
const isStatusCurrent = (step) => order.value?.status === step

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

const downloadReceipt = async () => {
  if (!order.value) return
  
  try {
    isExporting.value = true
    const response = await api.get(`/consumer/orders/${order.value.order_id}/receipt`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Tindahan-Receipt-#${order.value.order_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Download failed:', error)
    $q.notify({ type: 'negative', message: 'Failed to download receipt' })
  } finally {
    isExporting.value = false
  }
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

// Mobile: fixed bottom bar, replaces the in-card actions row.
const showActionsBar = computed(() => !!order.value && $q.screen.lt.sm)

// Reserves exactly the bar's measured height (not a guessed px value) as bottom padding, so SiteFooter sits flush against it with no gap or overlap.
const actionsBarEl = ref(null)
const actionsBarHeight = ref(0)
let actionsBarObserver = null

watch(actionsBarEl, (el) => {
  actionsBarObserver?.disconnect()
  actionsBarObserver = null

  if (!el) {
    actionsBarHeight.value = 0
    return
  }

  actionsBarHeight.value = el.offsetHeight
  actionsBarObserver = new ResizeObserver(() => {
    actionsBarHeight.value = el.offsetHeight
  })
  actionsBarObserver.observe(el)
})

onBeforeUnmount(() => actionsBarObserver?.disconnect())

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

  font-size: var(--fs-sm);
  font-weight: 500;

  color: var(--c-subtle);

  cursor: pointer;

  transition: color 0.15s;
}

.back-link:hover {
  color: var(--c-brand);
}

.page-title {
  margin: 0 0 20px;

  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.order-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 16px;
  padding: 80px 24px;

  color: var(--c-brand);
}

.order-loading-text {
  margin: 0;

  color: var(--c-muted);

  font-size: var(--fs-md);
}

/* LAYOUT */

.order-layout {
  display: grid;
  grid-template-columns: 1fr 280px;

  gap: 20px;

  align-items: start;

  animation: orderdetails-fade-up 0.5s ease both;
  animation-delay: 0.06s;
}

/* PAGE ENTRANCE — page load only (fresh DOM each navigation), opacity/transform only so it never shifts layout. */
@keyframes orderdetails-fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-title {
  animation: orderdetails-fade-up 0.5s ease both;
}

@media (prefers-reduced-motion: reduce) {
  .order-layout,
  .page-title {
    animation: none;
  }
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

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;
  box-shadow: var(--sh-card);
}

/* Terminal states take a tinted ground. Each border is a step deeper than its own fill
   so the card edge still reads, and each ground is a step lighter than the tints used
   inside it (cancellation note, tracker discs) so those do not disappear into it. */
.status-card--danger {
  border-color: var(--c-danger-line);
  background: var(--c-danger-tint);
}

.status-card--success {
  border-color: var(--c-success-line);
  background: var(--c-success-wash);
}

/* The eyebrow and the divider are neutrals tuned against a white card. Left alone on a
   tinted ground they read as grubby rather than quiet, so both take the card's own hue. */
.status-card--danger .status-label {
  color: var(--c-danger-muted);
}

.status-card--danger .card-divider {
  background: var(--c-danger-tint-2);
}

.status-card--success .status-label {
  color: var(--c-success-muted);
}

.status-card--success .card-divider {
  background: var(--c-success-tint);
}

.card-divider {
  margin: 16px 0 18px;

  background: var(--c-hairline);
}

/* STATUS CARD */

.status-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;
}


.status-label {
  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.status-expected {
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-subtle);
}

.status-title {
  margin-top: 6px;

  font-size: var(--fs-2xl);
  font-weight: 700;

  color: var(--c-text);
}

.status-title-cancelled {
  color: var(--c-danger);
}

.status-title-done {
  color: var(--c-success);
}

.status-desc {
  margin: 6px 0 0;

  font-size: var(--fs-sm);
  line-height: 1.5;

  color: var(--c-subtle);
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
  border: 2px solid var(--c-border);

  background: #ffffff;
  color: var(--c-muted);

  transition: background-color 0.2s, border-color 0.2s, color 0.2s;
}

/* Completed steps take the tinted-disc language used for every other leading icon in
   the app (.info-icon, .notif-icon, .promo-icon), rather than a second solid fill. */
.status-step-circle-done {
  border-color: transparent;

  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

/* Solid is reserved for where the order actually is, so one disc carries the emphasis. */
.status-step-circle-current {
  border-color: var(--c-brand);

  background: var(--c-brand);
  color: #ffffff;

  box-shadow: 0 0 0 4px var(--c-brand-tint);
}

.status-step-label {
  margin-top: 6px;

  font-size: var(--fs-2xs);
  font-weight: 600;

  color: var(--c-muted);

  text-align: center;
}

.status-step-label-active {
  color: var(--c-text);
}

.status-step-line {
  flex: 1;

  height: 2px;
  margin-top: 17px;

  background: var(--c-border);
}

.status-step-line-active {
  background: var(--c-brand);
}

.status-step-line-current {
  background: linear-gradient(to right, var(--c-brand) 50%, var(--c-border) 50%);
}

/* Picked up = done, so the whole tracker switches to the same green as
   .status-title-done — otherwise the connectors go green while the discs stay brand red
   and a finished order still reads as in progress. */
.status-steps-done .status-step-circle-done {
  background: var(--c-success-tint);
  color: var(--c-success);
}

.status-steps-done .status-step-circle-current {
  border-color: var(--c-success);

  background: var(--c-success);
  color: #ffffff;

  box-shadow: 0 0 0 4px var(--c-success-tint);
}

.status-steps-done .status-step-line-active {
  background: var(--c-success);
}

.cancellation-note {
  display: flex;
  align-items: flex-start;

  gap: 10px;
  margin-top: 18px;
  padding: 12px 14px;

  border-radius: var(--r-md);

  background: var(--c-danger-tint-2);
  color: var(--c-danger);
}

.cancellation-note-title {
  margin-bottom: 2px;

  font-size: var(--fs-sm);
  font-weight: 700;
}

.cancellation-note-text {
  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-danger);
}

/* STORE INFO */

.store-info-name-row {
  display: flex;
  align-items: center;

  gap: 8px;
}

.store-info-pin {
  color: var(--c-brand);
}

.store-info-name {
  font-size: var(--fs-lg);
  font-weight: 700;

  color: var(--c-text);
}

.store-info-address {
  margin-top: 6px;
  padding-left: 28px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
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

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.order-meta-value {
  display: flex;
  align-items: center;

  gap: 5px;

  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
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

  border-radius: var(--r-sm);
  border: 1px solid var(--c-border);
  outline: none !important;

  background: #ffffff;
  color: var(--c-text-2);

  font-size: var(--fs-sm);
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
  border-color: var(--c-brand-tint-3);

  box-shadow: 0 4px 12px rgba(189, 36, 39, 0.08);
  transform: translateY(-1px);
}

.cancel-order-btn:active:not(:disabled) {
  border-color: var(--c-brand-tint-3);

  transform: translateY(0);
}

.cancel-order-btn:focus-visible {
  outline: none !important;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.get-directions-btn {
  flex: 1;
  height: 48px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-sm);
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
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.get-directions-btn:active:not(:disabled) {
  background: var(--c-brand-active);

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

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;
  box-shadow: var(--sh-card);
}

.summary-title {
  margin-bottom: 4px;

  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.summary-store {
  margin-bottom: 12px;
  padding-bottom: 12px;

  border-bottom: 1px solid var(--c-hairline);

  font-size: var(--fs-sm);
  font-weight: 500;

  color: var(--c-subtle);
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

  border-radius: var(--r-sm);
  border: 1px solid var(--c-border);

  background: linear-gradient(145deg, var(--c-surface) 0%, var(--c-surface) 100%);
  color: var(--c-brand);

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
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text);

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.summary-item-qty {
  margin-top: 1px;

  font-size: var(--fs-2xs);

  color: var(--c-muted);
}

.summary-item-price {
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-3);
}

.summary-row {
  display: flex;
  justify-content: space-between;

  margin-bottom: 8px;

  font-size: var(--fs-sm);

  color: var(--c-text-3);
}

.summary-separator {
  margin: 10px 0;
}

.summary-total {
  font-size: var(--fs-lg);
  font-weight: 700;

  color: var(--c-text);
}

.view-receipt-btn {
  width: 100%;
  height: 52px;

  border-radius: var(--r-sm);
  border: 1px solid var(--c-border);
  outline: none !important;

  background: #ffffff;
  color: var(--c-text-2);
  box-shadow: var(--sh-card);

  font-size: var(--fs-sm);
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
  border-color: var(--c-brand-tint-3);

  box-shadow: 0 4px 12px rgba(189, 36, 39, 0.08);
  transform: translateY(-1px);
}

.view-receipt-btn:active {
  border-color: var(--c-brand-tint-3);

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

  border-radius: var(--r-xl);
}

/* Sheet mode: fixed header, scrollable reason list, fixed footer — same flex-column split as ProductFilters.vue's sheet mode. */
.cancel-dialog-card-sheet {
  display: flex;
  flex-direction: column;

  width: 100%;
  max-width: 100%;
  max-height: 88vh;

  padding: 0;

  border-radius: 16px 16px 0 0;
}

.cancel-dialog-drag-handle {
  flex-shrink: 0;

  width: 36px;
  height: 4px;
  margin: 10px auto 0;

  border-radius: var(--r-pill);

  background: var(--c-border-strong);
}

.cancel-dialog-card-sheet .cancel-dialog-scroll {
  flex: 1 1 auto;
  min-height: 0;

  overflow-y: auto;

  padding: 20px 24px 16px;
}

.cancel-dialog-card-sheet .cancel-dialog-actions {
  flex-shrink: 0;

  margin-top: 0;
  padding: 14px 24px calc(14px + env(safe-area-inset-bottom, 0px));

  border-top: 1px solid var(--c-hairline);
}

.cancel-dialog-close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 1;

  color: var(--c-muted);
}

.cancel-dialog-card-sheet .cancel-dialog-close-btn {
  top: 20px;
}

.cancel-dialog-title {
  padding-right: 28px;

  font-size: var(--fs-2xl);
  font-weight: 700;

  color: var(--c-text);
}

.cancel-dialog-text {
  margin: 6px 0 0;

  font-size: var(--fs-sm);

  color: var(--c-subtle);
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

  border-radius: var(--r-md);
  border: 1px solid var(--c-border);

  cursor: pointer;

  transition: border-color 0.15s, background-color 0.15s;
}

.cancel-reason-option:hover {
  border-color: var(--c-border-strong);
}

.cancel-reason-option-selected,
.cancel-reason-option-selected:hover {
  border-color: var(--c-brand);

  background: var(--c-brand-tint);
}

.cancel-reason-radio {
  flex-shrink: 0;
  box-sizing: border-box;

  width: 18px;
  height: 18px;

  border-radius: var(--r-pill);
  border: 2px solid var(--c-border);

  background: #ffffff;

  transition: border-color 0.15s, border-width 0.15s;
}

.cancel-reason-option-selected .cancel-reason-radio {
  border-width: 6px;
  border-color: var(--c-brand);
}

.cancel-reason-label {
  font-size: var(--fs-md);
  font-weight: 600;

  color: var(--c-text);
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

  border-radius: var(--r-sm);
  border: 1px solid var(--c-border-strong);

  background: #ffffff;
  color: var(--c-text-2);

  font-size: var(--fs-sm);
  font-weight: 600;

  transition: background-color 0.15s;
}

.keep-order-btn:hover {
  background: var(--c-surface);
}

.confirm-cancel-btn {
  flex: 1;
  height: 48px;

  border-radius: var(--r-sm);

  background: var(--c-danger);
  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(185, 28, 28, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.confirm-cancel-btn:hover:not(:disabled) {
  background: var(--c-brand-deep);

  box-shadow: 0 6px 16px rgba(185, 28, 28, 0.32);

  transform: translateY(-1px);
}

.confirm-cancel-btn:active:not(:disabled) {
  background: var(--c-brand-active);

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

  border-radius: var(--r-xl);
  text-align: center;
}

.receipt-close-btn {
  position: absolute;
  top: 10px;
  right: 10px;

  color: var(--c-muted);
}

.receipt-icon-circle {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 52px;
  height: 52px;
  margin: 0 auto 14px;

  border-radius: 999px !important;

  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.receipt-store-name {
  font-size: var(--fs-2xl);
  font-weight: 700;

  color: var(--c-text);
}

.receipt-store-address {
  display: flex;
  align-items: center;
  justify-content: center;

  gap: 4px;
  margin-top: 4px;

  font-size: var(--fs-sm);
  line-height: 1.4;

  color: var(--c-subtle);
}

.receipt-ref-row {
  display: flex;
  justify-content: center;

  margin-top: 12px;
}

.receipt-ref-badge {
  padding: 4px 12px;

  border-radius: var(--r-sm);

  background: var(--c-hairline);
  color: var(--c-text-2);

  font-size: var(--fs-xs);
  font-weight: 700;
  letter-spacing: 0.03em;
}

.receipt-ref-number {
  color: var(--c-brand);
}

.receipt-divider {
  margin: 16px 0;

  background: var(--c-hairline);
}

.receipt-date-banner {
  display: flex;
  align-items: center;
  justify-content: center;

  gap: 8px;
  margin-top: 14px;
  padding: 10px 14px;

  border-radius: var(--r-lg);

  background: var(--c-surface);
  color: var(--c-text-2);

  font-size: var(--fs-xs);
  font-weight: 600;

  text-align: center;
}

.receipt-date-banner .q-icon {
  color: var(--c-brand);
}

.receipt-items-header {
  display: flex;
  justify-content: space-between;

  margin: 16px 0 10px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;

  color: var(--c-muted);
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

  font-size: var(--fs-sm);

  text-align: left;
}

.receipt-item-qty {
  font-weight: 700;

  color: var(--c-brand);
}

.receipt-item-name {
  font-weight: 600;

  color: var(--c-text);
}

.receipt-item-price {
  font-weight: 700;

  color: var(--c-text);
}

.receipt-total-band {
  display: flex;
  justify-content: space-between;
  align-items: center;

  margin-top: 4px;

  font-size: var(--fs-xl);
  font-weight: 700;

  color: var(--c-text);
}

.receipt-total-amount {
  color: var(--c-text);
}

.receipt-download-btn {
  height: 46px;
  padding: 0 28px;

  border-radius: var(--r-pill);

  background: #ffffff;
  color: var(--c-text);

  font-size: var(--fs-sm);
  font-weight: 700;

  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);

  transition: background-color 0.15s, transform 0.2s;
}

.receipt-download-btn :deep(.q-icon) {
  margin-right: 0;

  font-size: 18px;
}

.receipt-download-btn :deep(.q-btn__content) {
  gap: 8px;
}

.receipt-download-btn:hover {
  background: var(--c-hairline);

  transform: translateY(-1px);
}

.receipt-print-footer {
  display: none;
}

/* PRINT — isolates the receipt card; positions are forced to `static` because Quasar's dialog transition leaves a `transform` on an ancestor that would break `position: fixed`. */

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
    display: flex !important;
    align-items: center;
    justify-content: center;

    width: 100% !important;
    min-height: 100vh;
  }

  .receipt-dialog-card {
    position: static !important;

    width: 100% !important;
    max-width: 420px !important;
    margin: 0 auto !important;
    padding: 36px 32px !important;

    box-shadow: none !important;
    border: 1px solid var(--c-border) !important;
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

    border-top: 1px dashed var(--c-border-strong);

    font-size: var(--fs-xs);

    color: var(--c-muted);
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
}

/* Mobile fixed action bar — rendering is gated by the same $q.screen.lt.sm check used in the template, not a separate media query. */
.order-actions-fixed {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 100;

  display: flex;

  gap: 10px;
  padding: 12px 16px calc(12px + env(safe-area-inset-bottom, 0px));

  background: #ffffff;
  border-top: 1px solid var(--c-hairline);
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.06);
}
</style>











