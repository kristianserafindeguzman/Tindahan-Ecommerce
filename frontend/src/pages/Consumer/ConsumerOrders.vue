<template>
  <q-page class="storefront-page">
    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">
      <h1 class="page-title">My Orders</h1>
      <p class="page-subtitle">Track, manage, and view your order history.</p>

      <div v-if="loading" class="orders-loading">Loading your orders…</div>

      <div v-else-if="!orders.length" class="orders-empty">
        <q-icon name="o_receipt_long" size="40px" class="orders-empty-icon" />
        <p class="orders-empty-text">You haven't placed any orders yet.</p>
        <q-btn unelevated no-caps label="Browse Products" class="browse-btn" @click="router.push('/consumer/home')" />
      </div>

      <div v-else class="orders-layout">
        <div class="orders-list">
          <q-card v-for="order in orders" :key="order.order_id" class="order-card cursor-pointer" flat bordered @click="viewOrder(order)">
            <q-card-section>
              <div class="order-header">
                <div class="order-header-main">
                  <div class="order-id">#{{ order.order_id }}</div>
                  <div class="order-store">{{ order.store?.store_name || 'Unknown Store' }}</div>
                </div>
                <div class="order-header-side">
                  <q-chip :color="getStatusColor(order.status)" text-color="white" size="sm" class="status-chip">
                    {{ formatStatus(order.status) }}
                  </q-chip>
                </div>
              </div>
              <div class="order-meta">
                <span>{{ formatDate(order.created_at) }}</span>
                <span>&bull;</span>
                <span>{{ order.items?.length || 0 }} items</span>
                <span>&bull;</span>
                <span class="order-total">₱{{ parseFloat(order.total_amount).toFixed(2) }}</span>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="order-details-section">
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

              <div v-if="order.cancellation_reason" class="cancellation-reason">
                <q-icon name="o_info" size="16px" />
                <span>Cancelled: {{ order.cancellation_reason }}</span>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
    </div>

    <SiteFooter />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import { api } from '@/boot/axios'

const router = useRouter()
const orders = ref([])
const loading = ref(true)

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

const getStatusColor = (status) => {
  switch (status) {
    case 'placed': return 'blue'
    case 'preparing': return 'amber'
    case 'ready_for_pickup': return 'orange'
    case 'picked_up': return 'green'
    case 'cancelled': return 'red'
    default: return 'grey'
  }
}

const formatStatus = (status) => {
  if (!status) return ''
  return status.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const viewOrder = (order) => {
  localStorage.setItem('consumer_selected_order_id', order.order_id)
  router.push('/consumer/orders/details')
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
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
.orders-loading {
  padding: 60px 0;
  text-align: center;
  color: #8992a2;
  font-size: 14px;
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
  color: #d8dce3;
}

.orders-empty-text {
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

/* ORDERS LIST */
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.order-card {
  margin-bottom: 24px;
  border-radius: 12px;
  border-color: #e5e7eb;
  transition: transform 0.2s, box-shadow 0.2s;
}

.order-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.order-id {
  font-size: 15px;
  font-weight: 700;
  color: #222222;
}

.order-store {
  font-size: 13px;
  color: #555555;
  margin-top: 2px;
}

.status-chip {
  font-weight: 600;
}

.order-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #8992a2;
}

.order-total {
  font-weight: 700;
  color: #bd2427;
}

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
  border-radius: 8px;
  border: 1px solid #e8e8e8;
  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  color: #bd2427;
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
  font-size: 13px;
  font-weight: 600;
  color: #222222;
}

.order-item-qty {
  font-size: 12px;
  color: #8992a2;
  margin-top: 2px;
}

.order-item-price {
  font-size: 13px;
  font-weight: 700;
  color: #222222;
}

.cancellation-reason {
  margin-top: 16px;
  padding: 12px;
  background-color: #fff1f0;
  color: #cf1322;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}
</style>
