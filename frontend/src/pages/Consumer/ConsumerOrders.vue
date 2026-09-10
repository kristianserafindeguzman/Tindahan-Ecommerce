<template>
  <q-page class="storefront-page">
    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">
      <h1 class="page-title">My Orders</h1>
      <p class="page-subtitle">Track, manage, and view your order history.</p>

      <div v-if="loading" class="orders-list">
        <div v-for="n in 3" :key="n" class="order-card">
          <div class="skeleton-order-top">
            <q-skeleton type="text" class="skeleton-line skeleton-line-short" />
            <q-skeleton type="text" class="skeleton-line skeleton-line-short" />
          </div>
          <q-skeleton type="rect" class="skeleton-order-item" />
          <div class="skeleton-order-bottom">
            <q-skeleton type="text" class="skeleton-line skeleton-line-short" />
          </div>
        </div>
      </div>

      <div v-else-if="!orders.length" class="orders-empty">
        <q-icon name="o_receipt_long" size="40px" class="orders-empty-icon" />
        <p class="orders-empty-text">You haven't placed any orders yet.</p>
        <q-btn unelevated no-caps label="Browse Products" class="browse-btn" @click="router.push('/consumer/home')" />
      </div>

      <div v-else>
        <!-- QTabs provides the tab roles, arrow-key navigation and sliding indicator, and v-model keeps the activeTab value the page filters on. -->
        <q-tabs
          v-model="activeTab"
          class="orders-tabs"
          align="left"
          no-caps
          narrow-indicator
          :breakpoint="0"
        >
          <q-tab v-for="tab in orderTabs" :key="tab.value" :name="tab.value" :label="tab.label" class="orders-tab" />
        </q-tabs>

        <div v-if="!displayedOrders.length" class="orders-empty">
          <q-icon name="o_receipt_long" size="40px" class="orders-empty-icon" />
          <p class="orders-empty-text">
            {{ activeTab === 'active' ? "You have no active orders right now." : "You don't have any past orders yet." }}
          </p>
        </div>

        <div v-else class="orders-layout">
          <div class="orders-list">
            <div v-for="order in displayedOrders" :key="order.order_id" class="order-card cursor-pointer" @click="viewOrder(order)">
              <div class="order-card-top">
                <div class="order-card-left">
                  <div class="order-store">{{ order.store?.store_name || 'Unknown Store' }}</div>
                  <div class="order-card-meta">
                    #{{ order.order_id }}
                    <span class="order-meta-dot">&bull;</span>
                    <q-icon name="o_location_on" size="12px" />
                    {{ orderAddressText(order) }}
                  </div>
                </div>
                <div class="order-card-right">
                  <div class="order-total">₱{{ parseFloat(order.total_amount).toFixed(2) }}</div>
                  <div class="order-item-count">{{ order.items?.length || 0 }} {{ order.items?.length === 1 ? 'Item' : 'Items' }}</div>
                </div>
              </div>

              <q-separator class="card-divider" />

              <div class="order-items">
                <div v-for="item in order.items" :key="item.order_item_id" class="order-item">
                  <div class="order-item-image">
                    <img v-if="item.inventory?.image_url" :src="item.inventory.image_url" :alt="item.inventory.product_name" />
                    <q-icon v-else name="o_inventory_2" size="20px" />
                  </div>
                  <div class="order-item-info">
                    <div class="order-item-name">{{ item.inventory?.product_name || 'Unavailable Product' }}</div>
                    <div class="order-item-qty">Qty: {{ item.quantity }}</div>
                  </div>
                  <div class="order-item-price">₱{{ parseFloat(item.subtotal).toFixed(2) }}</div>
                </div>
              </div>

              <q-separator class="card-divider" />

              <div class="order-card-bottom">
                <div class="order-card-bottom-row">
                  <span class="status-badge" :class="statusBadgeClass(order.status)">{{ formatStatus(order.status) }}</span>
                  <div class="order-card-right-mobile">
                    <div class="order-total">₱{{ parseFloat(order.total_amount).toFixed(2) }}</div>
                    <div class="order-item-count">{{ order.items?.length || 0 }} {{ order.items?.length === 1 ? 'Item' : 'Items' }}</div>
                  </div>
                </div>
                <div class="order-card-actions">
                  <q-btn
                    v-if="activeTab === 'past'"
                    unelevated
                    no-caps
                    icon="o_replay"
                    label="Reorder"
                    class="reorder-btn"
                    :loading="reorderingId === order.order_id"
                    @click.stop="reorderItems(order)"
                  />
                  <q-btn unelevated no-caps label="View Order Details" class="view-details-btn" @click.stop="viewOrder(order)" />
                </div>
              </div>

              <div v-if="order.cancellation_reason" class="cancellation-reason">
                <q-icon name="o_error_outline" size="16px" />
                <span>Cancelled: {{ order.cancellation_reason }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <SiteFooter />
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import { api } from '@/boot/axios'
import { useCart } from '@/composables/useCart'
import { formatDistance, calculateDistanceMeters } from '@/utils/distance'

const router = useRouter()
const $q = useQuasar()
const { addToCart } = useCart()
const orders = ref([])
const loading = ref(true)
const reorderingId = ref(null)

const orderTabs = [
  { value: 'active', label: 'Active Orders' },
  { value: 'past', label: 'Past Orders' }
]
const activeTab = ref('active')

const ACTIVE_STATUSES = ['placed', 'preparing', 'ready_for_pickup']
const PAST_STATUSES = ['cancelled', 'picked_up']

const activeOrders = computed(() => orders.value.filter((order) => ACTIVE_STATUSES.includes(order.status)))
const pastOrders = computed(() => orders.value.filter((order) => PAST_STATUSES.includes(order.status)))
const displayedOrders = computed(() => (activeTab.value === 'active' ? activeOrders.value : pastOrders.value))

const fetchOrders = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/consumer/orders')
    orders.value = data
  } catch (error) {
    console.error('Failed to load orders', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchOrders()
})

// Status badges use the same colour per status as the vendor order lists, so a status reads the same on both sides.
const STATUS_BADGE_CLASSES = {
  placed: 'status-badge-placed',
  preparing: 'status-badge-preparing',
  ready_for_pickup: 'status-badge-ready',
  picked_up: 'status-badge-picked-up',
  cancelled: 'status-badge-cancelled'
}

const statusBadgeClass = (status) => STATUS_BADGE_CLASSES[status] || 'status-badge-default'

const orderAddressText = (order) => {
  const address = order.store?.address
  const cLat = order.consumer_latitude
  const cLng = order.consumer_longitude
  const sLat = order.store?.latitude
  const sLng = order.store?.longitude

  // Raw coordinates are passed because calculateDistanceMeters returns null for a missing one, whereas Number(null) would measure from 0.
  const dist = formatDistance(calculateDistanceMeters(cLat, cLng, sLat, sLng))

  if (address && dist) return `${address} (${dist})`
  return address || dist || 'Address unavailable'
}

const formatStatus = (status) => {
  if (!status) return ''
  return status.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const viewOrder = (order) => {
  localStorage.setItem('consumer_selected_order_id', order.order_id)
  router.push('/consumer/orders/details')
}

const reorderItems = async (order) => {
  if (!order.items?.length) return
  reorderingId.value = order.order_id

  let successCount = 0
  let failCount = 0

  for (const item of order.items) {
    try {
      await addToCart(item.inventory_id, item.quantity)
      successCount++
    } catch {
      failCount++
    }
  }

  reorderingId.value = null

  if (!successCount) {
    $q.notify({ type: 'negative', message: 'None of these items are available anymore.' })
    return
  }

  $q.notify({
    type: 'positive',
    message: failCount
      ? `${successCount} item(s) added to cart. ${failCount} item(s) are no longer available.`
      : `${successCount} item(s) added to cart.`
  })
  router.push('/consumer/cart')
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

.page-title {
  margin: 0 0 4px;
  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.3;
  color: var(--c-text);
}

.page-subtitle {
  margin: 0 0 20px;
  font-size: var(--fs-sm);
  color: var(--c-subtle);
}

/* LOADING / EMPTY */
/* SKELETON LOADING STATE */

.skeleton-order-top {
  display: flex;
  justify-content: space-between;

  gap: 16px;
  margin-bottom: 16px;
}

/* QSkeleton draws the shimmer; only the geometry stays here. */
.skeleton-order-item {
  height: 48px;
  margin-bottom: 16px;

  border-radius: var(--r-md);
}

.skeleton-line {
  width: 45%;
  height: 12px;

  border-radius: var(--r-xs);
}

.skeleton-line-short {
  width: 30%;
}

.orders-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60px 24px;
  text-align: center;
}

.orders-empty-icon {
  margin-bottom: 10px;
  color: var(--c-border);
}

.orders-empty-text {
  margin: 0 0 20px;
  font-size: var(--fs-md);
  color: var(--c-muted);
}

.browse-btn {
  height: 48px;
  padding: 0 24px;
  border-radius: var(--r-sm);
  background: var(--c-brand);
  color: #ffffff;
  font-size: var(--fs-sm);
  font-weight: 500;
  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);
  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.browse-btn:hover {
  background: var(--c-brand-hover);
  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);
  transform: translateY(-1px);
}

/* TABS */
.orders-tabs {
  display: flex;
  gap: 28px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--c-border);
}

/* Undoes QTabs' own padding, uppercase and min-width to keep the flat underlined row this page uses. */
.orders-tab {
  padding: 0 0 12px;
  min-height: auto;
  min-width: auto;

  font-family: inherit;
  font-size: var(--fs-md);
  font-weight: 600;

  color: var(--c-muted);
}

.orders-tabs :deep(.q-tab__content) {
  padding: 0;
  min-width: auto;
}

.orders-tabs :deep(.q-tab--active) {
  color: var(--c-brand);
}

.orders-tabs :deep(.q-tab__indicator) {
  background: var(--c-brand);
  height: 2px;
}

/* The row is a plain underlined strip, not Quasar's scrollable tab bar. */
.orders-tabs :deep(.q-tabs__content) {
  gap: 28px;
}

.orders-tab:hover {
  color: var(--c-text-3);
}

/* Hides QTab's focus-helper block, which reads as a stray grey rectangle behind the label on this flat strip. */
.orders-tabs :deep(.q-focus-helper) {
  display: none;
}

/* Draws the focus ring on the label itself, since the old button rule drew a dashed box around Quasar's padding. */
.orders-tabs :deep(.q-tab:focus-visible) {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
  border-radius: var(--r-xs);
}

/* ORDERS LIST */
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.order-card {
  padding: 18px 20px;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);

  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;

  /* Plays on initial load AND on tab switch (Active/Past re-creates these cards via v-for :key) — a nice "refreshing" cue rather than a jarring swap. */
  animation: orders-fade-up 0.4s ease both;
}

/* PAGE ENTRANCE — opacity/transform only so it never shifts layout. */
@keyframes orders-fade-up {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-title {
  animation: orders-fade-up 0.5s ease both;
}

@media (prefers-reduced-motion: reduce) {
  .order-card,
  .page-title {
    animation: none;
  }
}

.order-card:hover {
  border-color: var(--c-brand-tint-3);

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.05);
  transform: translateY(-1px);
}

.card-divider {
  margin: 14px 0;

  background: var(--c-hairline);
}

.order-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;

  gap: 16px;
}

.order-store {
  font-size: var(--fs-lg);
  font-weight: 700;

  color: var(--c-text);
}

.order-card-meta {
  display: flex;
  align-items: center;

  gap: 4px;
  margin-top: 4px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.order-meta-dot {
  margin: 0 2px;
}

.order-card-right {
  flex-shrink: 0;

  text-align: right;
}

.order-total {
  font-size: var(--fs-lg);
  font-weight: 700;

  color: var(--c-text);
}

.order-item-count {
  margin-top: 4px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

/* ORDER ITEMS — restored, same recipe as the original card. */

.order-items {
  display: flex;
  flex-direction: column;

  gap: 12px;
}

.order-item {
  display: flex;
  align-items: center;

  gap: 12px;
}

.order-item-image {
  width: 48px;
  height: 48px;

  border-radius: var(--r-md);
  border: 1px solid var(--c-border);

  background: linear-gradient(145deg, var(--c-surface) 0%, var(--c-surface) 100%);
  color: var(--c-brand);

  display: flex;
  align-items: center;
  justify-content: center;

  overflow: hidden;
}

.order-item-image img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.order-item-info {
  flex: 1;
}

.order-item-name {
  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text);
}

.order-item-qty {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.order-item-price {
  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.order-card-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;
}

.order-card-bottom-row {
  display: flex;
  align-items: center;

  gap: 12px;
}

.order-card-right-mobile {
  display: none;
}

.status-badge {
  display: inline-flex;
  align-items: center;

  padding: 6px 12px;

  border-radius: var(--r-lg);

  font-size: var(--fs-sm);
  font-weight: 600;
}

.status-badge-placed {
  background: var(--c-info-tint);
  color: var(--c-info);
}

.status-badge-preparing {
  background: var(--c-status-wait-tint);
  color: var(--c-status-wait);
}

.status-badge-ready {
  background: var(--c-status-active-tint);
  color: var(--c-status-active);
}

.status-badge-picked-up {
  background: var(--c-success-tint);
  color: var(--c-success);
}

.status-badge-cancelled {
  background: var(--c-danger-tint);
  color: var(--c-danger);
}

.status-badge-default {
  background: var(--c-surface);
  color: var(--c-muted);
}

.order-card-actions {
  display: flex;
  flex-shrink: 0;

  gap: 10px;
}

.reorder-btn {
  height: 40px;
  padding: 0 18px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.reorder-btn:hover {
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);
  transform: translateY(-1px);
}

.reorder-btn:active {
  background: var(--c-brand-active);

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);
  transform: translateY(0);
}

.reorder-btn:focus-visible {
  outline: none !important;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.reorder-btn :deep(.q-focus-helper) {
  display: none;
}

.reorder-btn :deep(.q-icon) {
  margin-right: 0;

  font-size: 15px;
}

.reorder-btn :deep(.q-btn__content) {
  gap: 6px;
}

.view-details-btn {
  height: 40px;
  padding: 0 18px;

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
.view-details-btn :deep(.q-focus-helper) {
  display: none;
}

.view-details-btn:hover {
  border-color: var(--c-brand-tint-3);

  box-shadow: 0 4px 12px rgba(189, 36, 39, 0.08);
  transform: translateY(-1px);
}

.view-details-btn:focus-visible {
  outline: none !important;

  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.25);
}

.cancellation-reason {
  display: flex;
  align-items: center;

  gap: 8px;
  margin-top: 14px;
  padding: 10px 12px;

  border-radius: var(--r-md);

  background: var(--c-danger-tint);
  color: var(--c-danger);

  font-size: var(--fs-sm);
}

/* RESPONSIVE */

@media (max-width: 560px) {
  .page-content {
    padding: 16px;
  }

  .order-card {
    padding: 14px 16px;
  }

  .order-card-top {
    flex-direction: column;

    gap: 8px;
  }

  .order-card-right {
    display: none;
  }

  .order-card-right-mobile {
    display: block;

    text-align: right;
  }

  .order-total {
    font-size: var(--fs-md);
  }

  .order-item-image {
    width: 40px;
    height: 40px;
  }

  .status-badge {
    padding: 6px 12px;

    font-size: var(--fs-xs);
  }

  .order-card-bottom {
    flex-direction: column;
    align-items: stretch;

    gap: 10px;
  }

  .order-card-bottom-row {
    justify-content: space-between;

    gap: 10px;
  }

  .order-card-actions {
    flex-direction: column;
    width: 100%;
  }

  .reorder-btn,
  .view-details-btn {
    width: 100%;
  }
}
</style>
