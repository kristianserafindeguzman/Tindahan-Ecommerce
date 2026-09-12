<template>
  <!-- One layout for both uses: a full page at /vendor/orders/:id, or embedded inside Customer Orders. -->
  <component :is="isEmbedded ? 'div' : QPage" :class="isEmbedded ? 'od-embedded' : 'vp-page'">
    <div :class="{ 'vp-container': !isEmbedded }">

      <!-- An order that can't be loaded says so instead of spinning forever. -->
      <div v-if="loadError" class="vp-card vp-empty od-missing">
        <div class="vp-empty-icon"><q-icon name="o_receipt_long" size="24px" /></div>
        <div class="vp-empty-title">Order not found</div>
        <div class="vp-empty-text">It may belong to another store, or it was removed.</div>
        <q-btn v-if="isEmbedded" outline no-caps color="primary" icon="o_arrow_back" label="Back to orders" class="vp-pill-btn od-missing-btn" @click="$emit('back')" />
        <q-btn v-else outline no-caps color="primary" icon="o_arrow_back" label="Back to Order List" to="/vendor/orders/list" class="vp-pill-btn od-missing-btn" />
      </div>

      <div v-else-if="!order" class="od-loading">
        <q-spinner-dots size="40px" color="primary" />
      </div>

      <div v-else class="od-body">

        <!-- HEADER — the back button beside the order number, its status and the actions. -->
        <header class="od-header">
          <div class="od-heading">
            <q-btn v-if="isEmbedded" flat dense icon="o_arrow_back" class="od-back-btn" aria-label="Back to orders" @click="$emit('back')">
              <q-tooltip>Back to orders</q-tooltip>
            </q-btn>
            <q-btn v-else flat dense icon="o_arrow_back" class="od-back-btn" aria-label="Back to Order List" to="/vendor/orders/list">
              <q-tooltip>Back to Order List</q-tooltip>
            </q-btn>

            <div class="od-heading-text">
              <div class="od-title-row">
                <component :is="isEmbedded ? 'h2' : 'h1'" class="od-title">Order #{{ order.order_id }}</component>
                <OrderStatusBadge :status="order.status" />
              </div>
              <div class="od-meta">
                <span>{{ placedDay(order.created_at) }}</span>
                <span class="od-sep" aria-hidden="true" />
                <span>{{ placedTime(order.created_at) }}</span>
                <span class="od-sep" aria-hidden="true" />
                <span>{{ customerName }}</span>
              </div>
            </div>
          </div>

          <div class="od-actions">
            <q-btn outline no-caps color="primary" icon="o_print" label="Print Receipt" class="vp-pill-btn od-action" :loading="isExporting" @click="printOrder" />

            <q-btn-dropdown
              v-if="!isFinal"
              unelevated
              no-caps
              color="primary"
              label="Update Status"
              dropdown-icon="o_expand_more"
              class="od-update-btn od-action"
              :loading="isUpdating"
            >
              <q-list class="od-status-list">
                <q-item v-if="order.status === 'placed'" v-close-popup clickable @click="updateStatus('preparing')">
                  <q-item-section avatar><span class="od-menu-icon vp-tone--preparing"><q-icon :name="statusIcon('preparing')" size="18px" /></span></q-item-section>
                  <q-item-section>Start preparing</q-item-section>
                </q-item>
                <q-item v-if="['placed', 'preparing'].includes(order.status)" v-close-popup clickable @click="updateStatus('ready_for_pickup')">
                  <q-item-section avatar><span class="od-menu-icon vp-tone--ready"><q-icon :name="statusIcon('ready_for_pickup')" size="18px" /></span></q-item-section>
                  <q-item-section>Ready for pickup</q-item-section>
                </q-item>
                <q-item v-if="order.status === 'ready_for_pickup'" v-close-popup clickable @click="updateStatus('picked_up')">
                  <q-item-section avatar><span class="od-menu-icon vp-tone--done"><q-icon :name="statusIcon('picked_up')" size="18px" /></span></q-item-section>
                  <q-item-section>Picked up</q-item-section>
                </q-item>
                <q-separator class="od-menu-sep" />
                <q-item v-close-popup clickable class="od-menu-danger" @click="promptCancelOrder">
                  <q-item-section avatar><span class="od-menu-icon vp-tone--cancelled"><q-icon :name="statusIcon('cancelled')" size="18px" /></span></q-item-section>
                  <q-item-section>Cancel order</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>

            <span v-else class="od-final"><q-icon name="o_lock" size="16px" /> Order finalized</span>
          </div>
        </header>

        <div v-if="order.status === 'cancelled'" class="od-cancel-note">
          <q-icon name="o_info" size="18px" class="od-cancel-note-icon" />
          <div>
            <div class="od-cancel-note-title">This order was cancelled</div>
            <div>{{ order.cancellation_reason || 'No reason was given.' }}</div>
          </div>
        </div>

        <!-- Two columns on wide cards, progress and items on the left, customer and pickup on the right; narrow ones stack them in the same order. -->
        <div class="od-grid">
          <div class="od-col od-col--main">

            <section class="vp-card od-card od-card--progress">
              <div class="od-card-head">
                <span class="od-card-title">Order Progress</span>
              </div>
              <ol class="od-steps" aria-label="Order progress">
                <li
                  v-for="step in steps"
                  :key="step.key"
                  class="od-step"
                  :class="[`od-step--${step.state}`, { 'od-step--current': step.current, 'od-step--line-done': step.lineDone }]"
                >
                  <span class="od-step-dot">
                    <q-icon :name="step.state === 'cancelled' ? 'close' : step.state === 'done' ? 'check' : step.icon" size="15px" />
                  </span>
                  <span class="od-step-text">
                    <span class="od-step-label">{{ step.label }}</span>
                    <span class="od-step-time">{{ step.time }}</span>
                  </span>
                </li>
              </ol>
            </section>

            <section class="vp-card od-card od-card--items">
              <div class="od-card-head">
                <span class="od-card-title">Items</span>
                <span class="od-pill">{{ productCount }}</span>
              </div>

              <ul class="od-items">
                <li v-for="item in items" :key="item.order_item_id" class="od-item">
                  <span class="od-item-img">
                    <img v-if="item.inventory?.image_url || item.image_url" :src="item.inventory?.image_url || item.image_url" alt="" />
                    <q-icon v-else name="o_inventory_2" size="22px" />
                  </span>
                  <div class="od-item-body">
                    <div class="od-item-name">{{ item.inventory?.product_name || item.product_name || 'Product' }}</div>
                    <div class="od-item-meta">₱{{ formatNumber(unitPrice(item)) }} × {{ item.quantity }}</div>
                  </div>
                  <div class="od-item-price">₱{{ formatNumber(lineTotal(item)) }}</div>
                </li>
              </ul>

              <dl class="od-totals">
                <div v-if="Number(order.platform_fee)" class="od-total-row">
                  <dt>Platform fee</dt>
                  <dd>₱{{ formatNumber(order.platform_fee) }}</dd>
                </div>
                <div class="od-total-row od-total-row--grand">
                  <dt>Total</dt>
                  <dd>₱{{ formatNumber(order.total_amount) }}</dd>
                </div>
              </dl>
            </section>
          </div>

          <div class="od-col od-col--side">

            <section class="vp-card od-card od-card--customer">
              <div class="od-card-head">
                <span class="od-card-title">Customer</span>
              </div>
              <div class="od-card-body">
                <div class="od-person">
                  <q-avatar size="42px" font-size="14px" class="vp-avatar od-person-avatar">
                    <img v-if="order.consumer?.profile_picture_url" :src="order.consumer.profile_picture_url" alt="" />
                    <span v-else>{{ getInitials(customerName) }}</span>
                  </q-avatar>
                  <div class="od-person-text">
                    <div class="od-strong">{{ customerName }}</div>
                    <div class="od-sub">{{ customerOrderCount }} order{{ customerOrderCount === 1 ? '' : 's' }} from your store</div>
                  </div>
                </div>
                <dl class="od-contact">
                  <div class="od-contact-row">
                    <dt><q-icon name="o_mail" size="16px" /><span class="vp-sr-only">Email</span></dt>
                    <dd>{{ order.consumer?.email || 'No email provided' }}</dd>
                  </div>
                  <div class="od-contact-row">
                    <dt><q-icon name="o_call" size="16px" /><span class="vp-sr-only">Phone</span></dt>
                    <dd>{{ order.consumer?.phone_number || order.customer_phone || 'No phone provided' }}</dd>
                  </div>
                </dl>
              </div>
            </section>

            <section class="vp-card od-card od-card--pickup">
              <div class="od-card-head">
                <span class="od-card-title">Pickup Location</span>
                <q-btn
                  outline
                  no-caps
                  color="primary"
                  icon="o_directions"
                  label="Directions"
                  class="vp-pill-btn od-head-btn"
                  :disable="!hasRoute"
                  @click="openDirections"
                />
              </div>
              <div class="od-card-body od-place">
                <span class="od-place-icon"><q-icon name="o_storefront" size="18px" /></span>
                <div class="od-place-text">
                  <div class="od-strong">{{ order.store?.store_name || 'Store' }}</div>
                  <div class="od-sub">{{ order.store?.address || 'No address saved' }}</div>
                </div>
              </div>
              <div v-if="hasRoute" class="od-map">
                <OrderTrackingMap
                  :storeLat="order.store?.latitude"
                  :storeLng="order.store?.longitude"
                  :consumerLat="order.consumer_latitude"
                  :consumerLng="order.consumer_longitude"
                  :storeName="order.store?.store_name"
                  :consumerName="customerName"
                  :interactive="false"
                />
              </div>
              <div v-else class="od-map-note">
                <q-icon name="o_location_off" size="16px" />
                The customer's location wasn't recorded, so there's no route to show.
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </component>

  <q-dialog v-model="showCancelDialog" persistent>
    <q-card class="od-dialog">
      <div class="od-dialog-head">
        <span class="od-dialog-icon"><q-icon name="o_cancel" size="22px" /></span>
        <div>
          <div class="od-dialog-title">Cancel order #{{ order?.order_id }}?</div>
          <div class="od-dialog-text">Choose or write a reason. This can't be undone.</div>
        </div>
      </div>

      <div class="od-dialog-body">
        <q-checkbox v-model="cancelReasonOutOfStock" dense color="primary" label="Item(s) out of stock" class="od-dialog-check" />
        <q-input
          v-model="cancelReasonText"
          type="textarea"
          outlined
          autogrow
          autofocus
          label="Reason"
          color="primary"
          :rules="[val => !!(val && val.trim()) || 'Enter a reason.']"
          class="od-dialog-input"
        />
      </div>

      <div class="od-dialog-actions">
        <q-btn v-close-popup outline no-caps color="primary" label="Keep Order" class="od-dialog-btn" />
        <q-btn unelevated no-caps color="primary" label="Cancel Order" class="od-dialog-btn" :loading="isUpdating" @click="confirmCancelOrder" />
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/boot/axios'
import { useQuasar, QPage } from 'quasar'
import OrderTrackingMap from '@/components/shared/OrderTrackingMap.vue'
import OrderStatusBadge from '@/components/vendor/OrderStatusBadge.vue'
import { statusIcon, statusLabel } from '@/utils/orderStatus'

const props = defineProps({
  orderId: { type: [String, Number], default: null },
  isEmbedded: { type: Boolean, default: false }
})

// "status-changed" lets an embedding page, such as Customer Orders, update its own list without reloading.
const emit = defineEmits(['back', 'status-changed'])

const route = useRoute()
const $q = useQuasar()
const order = ref(null)
const loadError = ref(false)
const isUpdating = ref(false)
const isExporting = ref(false)

const showCancelDialog = ref(false)
const cancelReasonOutOfStock = ref(false)
const cancelReasonText = ref('')

const FLOW = ['placed', 'preparing', 'ready_for_pickup', 'picked_up']

const items = computed(() => order.value?.items || [])
const productCount = computed(() => items.value.length)
const customerName = computed(() => order.value?.consumer?.full_name || order.value?.customer_name || 'Customer')
const customerOrderCount = computed(() => Number(order.value?.consumer?.total_orders || order.value?.customer_orders_count || 1))
const isFinal = computed(() => ['picked_up', 'cancelled', 'completed'].includes(order.value?.status))

// The route map needs both ends; without the customer's pin it shows a short note instead.
const hasRoute = computed(() => {
  const o = order.value
  return [o?.store?.latitude, o?.store?.longitude, o?.consumer_latitude, o?.consumer_longitude].every(v => v !== null && v !== undefined && v !== '')
})

watch(cancelReasonOutOfStock, val => {
  if (val) {
    cancelReasonText.value = 'Item out of stock'
  } else if (cancelReasonText.value === 'Item out of stock') {
    cancelReasonText.value = ''
  }
})

const promptCancelOrder = () => {
  cancelReasonOutOfStock.value = false
  cancelReasonText.value = ''
  showCancelDialog.value = true
}

const formatNumber = num => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

// The day and the time are separate header details, "Sat, Nov 28" and "08:30 PM", so each gets the same dot between them.
const placedDay = dateString => (dateString ? new Date(dateString).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }) : '')
const placedTime = dateString => (dateString ? new Date(dateString).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '')

const formatStepTime = dateString => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
}

const getInitials = name => {
  const parts = String(name || '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '?'
  return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase()
}

const lineTotal = item => Number(item.subtotal || Number(item.price || 0) * Number(item.quantity || 0))
const unitPrice = item => (Number(item.quantity) ? lineTotal(item) / Number(item.quantity) : Number(item.price || 0))

// A completed order counts as picked up, so every step shows as reached.
const isStatusActive = step => {
  const status = order.value?.status === 'completed' ? 'picked_up' : order.value?.status
  return FLOW.indexOf(status) >= FLOW.indexOf(step)
}

// The progress steps, with the same icons as every status badge, and the line after each one coloured once the next step is reached.
const steps = computed(() => {
  const o = order.value
  if (!o) return []

  const list = o.status === 'cancelled'
    ? [
        { key: 'placed', label: 'Placed', icon: statusIcon('placed'), state: 'done', time: formatStepTime(o.created_at) },
        { key: 'cancelled', label: 'Cancelled', icon: 'close', state: 'cancelled', time: formatStepTime(o.updated_at) }
      ]
    : [
        { key: 'placed', label: 'Placed', icon: statusIcon('placed'), time: formatStepTime(o.created_at) },
        { key: 'preparing', label: 'Preparing', icon: statusIcon('preparing'), time: o.preparing_at ? formatStepTime(o.preparing_at) : 'In progress' },
        { key: 'ready_for_pickup', label: 'Ready for pickup', icon: statusIcon('ready_for_pickup'), time: o.ready_at ? formatStepTime(o.ready_at) : 'At the counter' },
        { key: 'picked_up', label: 'Picked up', icon: statusIcon('picked_up'), time: o.picked_up_at ? formatStepTime(o.picked_up_at) : 'Done' }
      ].map(step => {
        const reached = isStatusActive(step.key)
        return { ...step, state: reached ? 'done' : 'upcoming', time: reached ? step.time : 'Pending' }
      })

  const lastReached = list.map(s => s.state !== 'upcoming').lastIndexOf(true)
  return list.map((step, i) => ({
    ...step,
    current: i === lastReached,
    lineDone: i < list.length - 1 && list[i + 1].state !== 'upcoming'
  }))
})

const printOrder = async () => {
  if (!order.value) return

  isExporting.value = true
  try {
    const response = await api.get(`/vendor/orders/${order.value.order_id}/export`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Tindahan-Customer-Order-#${order.value.order_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    setTimeout(() => window.URL.revokeObjectURL(url), 1000)
  } catch (error) {
    console.error('Print failed:', error)
    $q.notify({ type: 'negative', message: 'Failed to generate the receipt.' })
  } finally {
    isExporting.value = false
  }
}

const updateStatus = async (newStatus, reason = null) => {
  if (!order.value) return

  isUpdating.value = true
  try {
    const payload = { status: newStatus }
    if (reason) payload.cancellation_reason = reason

    const res = await api.patch(`/vendor/orders/${order.value.order_id}/status`, payload)
    order.value.status = res.data.order.status
    if (res.data.order.cancellation_reason) order.value.cancellation_reason = res.data.order.cancellation_reason
    emit('status-changed', { orderId: order.value.order_id, status: order.value.status, cancellationReason: order.value.cancellation_reason || null })
    $q.notify({ type: 'positive', message: `Order status updated to ${statusLabel(newStatus)}.` })
    showCancelDialog.value = false
  } catch (err) {
    console.error(err.response?.data || err)
    $q.notify({ type: 'negative', message: err.response?.data?.message || err.message || 'Something went wrong.' })
  } finally {
    isUpdating.value = false
  }
}

const confirmCancelOrder = () => {
  const reason = (cancelReasonText.value || '').trim()
  if (!reason) {
    $q.notify({ type: 'warning', message: 'Please give a cancellation reason.' })
    return
  }
  updateStatus('cancelled', reason)
}

const openDirections = () => {
  if (!order.value) return
  const oLat = order.value.consumer_latitude
  const oLng = order.value.consumer_longitude
  const dLat = order.value.store?.latitude
  const dLng = order.value.store?.longitude

  if (oLat && oLng && dLat && dLng) {
    window.open(`https://www.google.com/maps/dir/?api=1&origin=${oLat},${oLng}&destination=${dLat},${dLng}`, '_blank')
  }
}

let lastRequest = 0

const fetchOrderDetails = async () => {
  const id = props.orderId || route.params.id
  if (!id) return

  const requestId = ++lastRequest
  loadError.value = false
  try {
    const res = await api.get(`/vendor/orders/${id}`)
    // A slower answer for an order that is no longer open is dropped.
    if (requestId === lastRequest) order.value = res.data
  } catch (error) {
    console.error('Failed to load order details', error)
    if (requestId === lastRequest) loadError.value = true
  }
}

watch(() => props.orderId, newId => {
  if (newId) {
    order.value = null
    fetchOrderDetails()
  }
})

// The page stays mounted when only the order number changes, such as tapping a notification for another order, so it loads the new one.
watch(() => route.params.id, (newId, oldId) => {
  if (!props.isEmbedded && newId && newId !== oldId) {
    order.value = null
    fetchOrderDetails()
  }
})

onMounted(fetchOrderDetails)
</script>

<style scoped>
.od-embedded {
  width: 100%;

  animation: od-fade-in 0.25s ease both;
}

@keyframes od-fade-in {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: none; }
}

@media (prefers-reduced-motion: reduce) {
  .od-embedded {
    animation: none;
  }
}

.od-missing {
  padding-block: 56px;
}

.od-missing-btn {
  margin-top: 10px;
}

.od-loading {
  display: flex;
  align-items: center;
  justify-content: center;

  min-height: 40vh;
}

/* The body is a size container, so the columns follow the space it has, not the window. */
.od-body {
  container: od / inline-size;

  display: flex;
  flex-direction: column;

  gap: 16px;
}

/* HEADER */

.od-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;

  gap: 14px 20px;
  padding-bottom: 4px;
}

/* The back button sits beside the title, like an admin order page. */
.od-heading {
  display: flex;
  align-items: flex-start;

  gap: 14px;
  min-width: 0;
}

.od-heading-text {
  min-width: 0;
}

.od-back-btn {
  flex-shrink: 0;

  width: 38px;
  height: 38px;
  margin-top: 1px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  color: var(--c-text-3);

  transition: background-color 0.15s, border-color 0.15s, color 0.15s;
}

.od-back-btn:hover {
  border-color: var(--c-brand);

  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.od-title-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;

  gap: 6px 12px;
}

.od-title {
  margin: 0;

  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.od-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;

  gap: 2px 10px;
  margin-top: 4px;

  font-size: var(--fs-sm);

  color: var(--c-subtle);
}

/* A small dot between the meta details, quieter than icons. */
.od-sep {
  width: 3px;
  height: 3px;

  border-radius: 50%;

  background: var(--c-border-strong);
}

/* The left auto margin keeps the actions on the right, even when a tablet-width header wraps them under the title. */
.od-actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;

  gap: 8px;
  margin-left: auto;
}

.od-update-btn {
  height: 38px;
  min-height: 38px;
  padding: 0 12px 0 16px;

  border-radius: var(--r-control);

  font-size: var(--fs-sm);
  font-weight: 600;

  box-shadow: var(--sh-brand);
}

.od-final {
  display: inline-flex;
  align-items: center;

  gap: 6px;
  height: 38px;
  padding: 0 14px;

  border-radius: var(--r-control);

  background: var(--c-surface);

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text-3);
}

.od-status-list {
  min-width: 220px;
  padding: 6px;

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text-2);
}

.od-status-list .q-item {
  min-height: 42px;
  padding: 4px 10px;

  border-radius: var(--r-control);
}

.od-status-list .q-item__section--avatar {
  min-width: 0;
  padding-right: 12px;
}

.od-menu-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 30px;
  height: 30px;

  border-radius: var(--r-control);
}

.od-menu-sep {
  margin: 4px 6px;
}

.od-menu-danger {
  color: var(--c-danger);
}

.od-cancel-note {
  display: flex;
  align-items: flex-start;

  gap: 10px;
  padding: 12px 16px;

  border: 1px solid var(--c-danger-tint-2);
  border-radius: var(--r-surface);

  background: var(--c-danger-tint);

  font-size: var(--fs-sm);

  color: var(--c-text-2);
}

.od-cancel-note-icon {
  flex-shrink: 0;
  margin-top: 1px;

  color: var(--c-danger);
}

.od-cancel-note-title {
  font-weight: 700;

  color: var(--c-danger);
}

/* LAYOUT — narrow cards flow in one column in reading order; wide ones split into two columns. */

.od-grid {
  display: flex;
  flex-direction: column;

  gap: 16px;
}

.od-col {
  display: contents;
}

.od-card--progress { order: 1; }
.od-card--items { order: 2; }
.od-card--customer { order: 3; }
.od-card--pickup { order: 4; }

@container od (min-width: 760px) {
  .od-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    align-items: start;
  }

  .od-col {
    display: flex;
    flex-direction: column;

    gap: 16px;
    min-width: 0;
  }

  /* Each column keeps its written order; the phone order above applies only when the columns merge. */
  .od-col > .od-card {
    order: 0;
  }
}

/* CARDS */

.od-card {
  overflow: hidden;
}

.od-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 8px;
  min-height: 56px;
  padding: 10px 20px;

  border-bottom: 1px solid var(--c-hairline);
}

.od-card-title {
  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.od-pill {
  min-width: 24px;
  padding: 2px 9px;

  border-radius: var(--r-pill);

  background: var(--c-surface);

  font-size: var(--fs-2xs);
  font-weight: 700;
  text-align: center;

  color: var(--c-text-3);
}

.od-head-btn {
  height: 32px;
  min-height: 32px;
  padding: 0 12px;

  font-size: var(--fs-xs);
}

.od-card-body {
  padding: 16px 20px;
}

.od-strong {
  overflow: hidden;

  font-size: var(--fs-sm);
  font-weight: 700;
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text);
}

.od-sub {
  margin-top: 2px;

  font-size: var(--fs-xs);
  line-height: 1.45;

  color: var(--c-muted);
}

/* ITEMS */

.od-items {
  margin: 0;
  padding: 0 20px;

  list-style: none;
}

.od-item {
  display: flex;
  align-items: center;

  gap: 14px;
  padding: 14px 0;

  border-bottom: 1px solid var(--c-hairline);
}

.od-item:last-child {
  border-bottom: none;
}

.od-item-img {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 52px;
  height: 52px;
  overflow: hidden;

  border: 1px solid var(--c-hairline);
  border-radius: var(--r-control);

  background: var(--c-surface);
  color: var(--c-muted);
}

.od-item-img img {
  width: 100%;
  height: 100%;
  padding: 3px;

  object-fit: contain;
}

.od-item-body {
  flex: 1;
  min-width: 0;
}

.od-item-name {
  overflow: hidden;

  font-size: var(--fs-sm);
  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text);
}

.od-item-meta {
  margin-top: 3px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.od-item-price {
  flex-shrink: 0;

  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.od-totals {
  display: flex;
  flex-direction: column;

  gap: 8px;
  margin: 0;
  padding: 14px 20px 18px;

  border-top: 1px solid var(--c-hairline);

  background: var(--c-surface-2);
}

.od-total-row {
  display: flex;
  justify-content: space-between;

  font-size: var(--fs-sm);

  color: var(--c-text-3);
}

.od-total-row dt,
.od-total-row dd {
  margin: 0;
}

.od-total-row--grand {
  padding-top: 10px;

  border-top: 1px solid var(--c-border);

  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

/* With the subtotal gone, a total that opens the band needs no line of its own above it. */
.od-total-row--grand:first-child {
  padding-top: 0;

  border-top: none;
}

.od-total-row--grand dd {
  font-size: var(--fs-xl);
}

/* PICKUP */

.od-place {
  display: flex;
  align-items: flex-start;

  gap: 12px;
}

.od-card-body.od-place {
  padding-bottom: 14px;
}

.od-place-text {
  min-width: 0;
}

.od-place-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 36px;
  height: 36px;

  border-radius: var(--r-control);

  background: var(--c-brand-tint);
  color: var(--c-brand);
}

/* The map sits as a rounded frame inside the card, under the store's name. */
.od-map {
  height: 220px;
  margin: 0 20px 20px;
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);
}

/* The shared map keeps its own 300px minimum, so it is fitted to this slot here. */
.od-map :deep(.tracking-map-wrapper) {
  height: 100%;
  min-height: 0;

  border-radius: 0;
}

.od-map-note {
  display: flex;
  align-items: center;

  gap: 8px;
  margin: 0 20px 20px;
  padding: 10px 12px;

  border-radius: var(--r-control);

  background: var(--c-surface-2);

  font-size: var(--fs-xs);

  color: var(--c-text-3);
}

.od-map-note .q-icon {
  flex-shrink: 0;

  color: var(--c-muted);
}

/* PROGRESS — a vertical timeline, with the line after each reached step in green. */

.od-steps {
  display: flex;
  flex-direction: column;

  margin: 0;
  padding: 18px 20px;

  list-style: none;
}

.od-step {
  position: relative;

  display: flex;
  align-items: flex-start;

  gap: 12px;
  padding-bottom: 20px;
}

.od-step:last-child {
  padding-bottom: 0;
}

.od-step:not(:last-child)::after {
  content: '';

  position: absolute;
  top: 32px;
  bottom: 2px;
  left: 13px;

  width: 2px;

  border-radius: var(--r-pill);

  background: var(--c-border);
}

.od-step--line-done::after {
  background: var(--c-success) !important;
}

.od-step-dot {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 28px;
  height: 28px;

  border: 2px solid var(--c-border);
  border-radius: 50%;

  background: #ffffff;
  color: var(--c-muted);
}

.od-step--done .od-step-dot {
  border-color: var(--c-success);

  background: var(--c-success);
  color: #ffffff;
}

.od-step--current.od-step--done .od-step-dot {
  box-shadow: 0 0 0 4px var(--c-success-tint);
}

.od-step--cancelled .od-step-dot {
  border-color: var(--c-danger);

  background: var(--c-danger);
  color: #ffffff;

  box-shadow: 0 0 0 4px var(--c-danger-tint-2);
}

.od-step-text {
  display: flex;
  flex-direction: column;

  min-width: 0;
  padding-top: 3px;
}

.od-step-label {
  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.od-step--upcoming .od-step-label {
  font-weight: 600;

  color: var(--c-muted);
}

.od-step--cancelled .od-step-label {
  color: var(--c-danger);
}

.od-step-time {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

/* A wide progress card becomes a centred stepper: each dot in the middle of its column with its label under it, so the steps spread evenly across the card. */
.od-card--progress {
  container: odp / inline-size;
}

@container odp (min-width: 520px) {
  .od-steps {
    flex-direction: row;

    padding: 22px 20px 20px;
  }

  .od-step {
    flex: 1 1 0;
    flex-direction: column;
    align-items: center;

    gap: 10px;
    min-width: 0;
    padding-bottom: 0;

    text-align: center;
  }

  /* Runs from 8px past this dot to 8px before the next, whose centre sits one column (100%) further along. */
  .od-step:not(:last-child)::after {
    top: 13px;
    right: auto;
    bottom: auto;
    left: calc(50% + 22px);

    width: calc(100% - 44px);
    height: 2px;
  }

  .od-step-text {
    align-items: center;

    padding-top: 0;
  }
}

/* CUSTOMER */

.od-person {
  display: flex;
  align-items: center;

  gap: 12px;
  min-width: 0;
}

.od-person-avatar {
  font-weight: 700;
}

.od-person-text {
  min-width: 0;
}

.od-contact {
  display: flex;
  flex-direction: column;

  gap: 10px;
  margin: 14px 0 0;
  padding-top: 14px;

  border-top: 1px solid var(--c-hairline);
}

.od-contact-row {
  display: flex;
  align-items: center;

  gap: 10px;

  font-size: var(--fs-sm);

  color: var(--c-text-2);
}

.od-contact-row dt {
  display: flex;
  flex-shrink: 0;

  color: var(--c-muted);
}

.od-contact-row dd {
  min-width: 0;
  margin: 0;

  word-break: break-all;
}

/* Narrow cards put the actions on their own full-width row. */
@container od (max-width: 560px) {
  .od-title {
    font-size: var(--fs-2xl);
  }

  .od-header {
    align-items: stretch;
  }

  .od-actions {
    flex-basis: 100%;
  }

  .od-action {
    flex: 1 1 0;
  }

  .od-card-head,
  .od-card-body,
  .od-totals,
  .od-steps {
    padding-inline: 16px;
  }

  .od-items {
    padding-inline: 16px;
  }

  .od-map {
    height: 200px;
  }

  .od-map,
  .od-map-note {
    margin-inline: 16px;
  }
}

/* CANCEL DIALOG — the same shape as the profile dialogs. */

.od-dialog {
  width: 440px;
  max-width: calc(100vw - 32px);

  border-radius: var(--r-surface);

  box-shadow: var(--sh-pop);
}

.od-dialog-head {
  display: flex;
  align-items: flex-start;

  gap: 14px;
  padding: 24px 24px 0;
}

.od-dialog-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 44px;
  height: 44px;

  border-radius: var(--r-surface);

  background: var(--c-danger-tint);
  color: var(--c-danger);
}

.od-dialog-title {
  font-size: var(--fs-xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.od-dialog-text {
  margin-top: 4px;

  font-size: var(--fs-sm);
  line-height: 1.5;

  color: var(--c-subtle);
}

.od-dialog-body {
  padding: 18px 24px 4px;
}

.od-dialog-check {
  margin-bottom: 14px;

  font-size: var(--fs-sm);

  color: var(--c-text-2);
}

.od-dialog-input :deep(.q-field__control) {
  border-radius: var(--r-control);
}

.od-dialog-actions {
  display: flex;
  justify-content: flex-end;

  gap: 10px;
  padding: 12px 24px 24px;
}

.od-dialog-btn {
  min-width: 132px;
  height: 44px;

  border-radius: var(--r-control);

  font-size: var(--fs-sm);
  font-weight: 600;
}

@media (max-width: 480px) {
  .od-dialog-head {
    padding: 18px 18px 0;
  }

  .od-dialog-body {
    padding: 16px 18px 4px;
  }

  .od-dialog-actions {
    padding: 12px 18px 18px;
  }

  .od-dialog-btn {
    flex: 1;
    min-width: 0;
  }
}
</style>
