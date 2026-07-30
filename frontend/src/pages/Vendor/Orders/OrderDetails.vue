<template>
  <q-page class="vendor-page">
    <div class="page-container" v-if="order">
      
      <!-- ================= BREADCRUMBS & TOP BAR ================= -->
      <div class="q-mb-md">
        <q-breadcrumbs class="text-grey-7" active-color="dark">
          <q-breadcrumbs-el label="Order List" to="/vendor/orders/list" />
          <q-breadcrumbs-el label="Order Details" />
        </q-breadcrumbs>
      </div>

      <div class="page-header q-mb-xl row items-center justify-between bg-white q-pa-md border-radius-12 shadow-1">
        <div class="row items-center">
          <h1 class="text-h5 text-weight-bold q-ma-none q-mr-md">Order #{{ order.order_id }}</h1>
          <q-chip size="sm" :color="getStatusColor(order.status)" text-color="white" class="text-weight-bold shadow-1 q-mr-md">
            {{ order.status }}
          </q-chip>
          <div class="text-subtitle2 text-grey-7">
            {{ formatDate(order.created_at) }} • {{ order.consumer?.full_name || 'Unknown Customer' }}
          </div>
        </div>
        
        <div class="row q-gutter-sm">
          <q-btn outline icon="print" label="Print" color="dark" no-caps class="btn-3d-outline" />
          
          <q-btn-dropdown outline color="dark" label="Update Status" no-caps class="btn-3d-outline bg-grey-2">
            <q-list>
              <q-item clickable v-close-popup @click="updateStatus('Preparing')">
                <q-item-section>
                  <q-item-label>Preparing</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="updateStatus('Ready for Pickup')">
                <q-item-section>
                  <q-item-label>Ready for Pickup</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="updateStatus('Picked up')">
                <q-item-section>
                  <q-item-label>Picked up</q-item-label>
                </q-item-section>
              </q-item>
              <q-item clickable v-close-popup @click="updateStatus('Cancelled')">
                <q-item-section>
                  <q-item-label class="text-red">Cancel Order</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>
        </div>
      </div>

      <div class="row q-col-gutter-lg">
        
        <!-- ================= LEFT COLUMN ================= -->
        <div class="col-12 col-md-8">
          
          <!-- Tracking & Timeline Grid -->
          <div class="row q-col-gutter-md q-mb-lg">
            <div class="col-12 col-md-6">
              <q-card class="premium-glass-card h-full">
                <q-card-section class="q-pa-lg">
                  <div class="text-h6 text-weight-bold text-dark q-mb-lg row items-center">
                    <div class="header-accent-red q-mr-sm"></div>
                    Order Status
                  </div>
                  
                  <q-timeline color="red-8">
                    <q-timeline-entry title="Order Placed" :subtitle="formatDate(order.created_at)" icon="shopping_cart" />
                    <q-timeline-entry title="Preparing" subtitle="Pending update" icon="soup_kitchen" color="grey-4" />
                    <q-timeline-entry title="Ready for Pickup" subtitle="Pending update" icon="inventory_2" color="grey-4" />
                    <q-timeline-entry title="Picked up" subtitle="Pending update" icon="check_circle" color="grey-4" />
                  </q-timeline>
                </q-card-section>
              </q-card>
            </div>
            
            <div class="col-12 col-md-6">
              <q-card class="premium-glass-card h-full overflow-hidden flex flex-center bg-grey-2" style="min-height: 300px;">
                <div class="text-center text-grey-6 q-pa-md">
                  <q-icon name="map" size="48px" class="q-mb-md opacity-50" />
                  <div class="text-subtitle1 text-weight-bold">Tracking Map Placeholder</div>
                  <div class="text-caption">Map integration will be rendered here.</div>
                </div>
              </q-card>
            </div>
          </div>

          <!-- Products Card -->
          <q-card class="premium-glass-card">
            <q-card-section class="q-pa-lg border-bottom">
              <div class="text-h6 text-weight-bold text-dark row items-center">
                <div class="header-accent-red q-mr-sm"></div>
                Purchased Items
              </div>
            </q-card-section>
            
            <q-list separator>
              <q-item v-for="item in order.items" :key="item.order_item_id" class="q-pa-md">
                <q-item-section avatar>
                  <q-avatar rounded size="56px" class="bg-grey-2">
                    <img v-if="item.inventory?.image_url" :src="item.inventory.image_url" />
                    <q-icon v-else name="inventory_2" color="grey-5" size="24px" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-subtitle1">{{ item.inventory?.product_name || 'Product' }}</q-item-label>
                  <q-item-label caption class="text-grey-7">₱{{ formatNumber(item.subtotal / item.quantity) }} per item</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="text-weight-bold text-dark">Qty: {{ item.quantity }}</div>
                </q-item-section>
                <q-item-section side>
                  <div class="text-h6 text-weight-bold text-dark">₱{{ formatNumber(item.subtotal) }}</div>
                </q-item-section>
              </q-item>
            </q-list>

            <q-card-section class="q-pa-lg bg-grey-1">
              <div class="row justify-between q-mb-sm text-grey-8">
                <div>Subtotal</div>
                <div class="text-weight-bold">₱{{ formatNumber(order.total_amount) }}</div>
              </div>
              <div class="row justify-between q-mb-sm text-grey-8">
                <div>Platform Fee</div>
                <div class="text-weight-bold">₱0.00</div>
              </div>
              <div class="border-dotted q-my-md"></div>
              <div class="row justify-between items-center text-dark">
                <div class="text-subtitle1 text-weight-bold">Total</div>
                <div class="text-h5 text-weight-bold text-red-8">₱{{ formatNumber(order.total_amount) }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= RIGHT COLUMN ================= -->
        <div class="col-12 col-md-4">
          
          <!-- Customer Info -->
          <q-card class="premium-glass-card q-mb-lg">
            <q-card-section class="q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark q-mb-lg row items-center">
                <div class="header-accent-red q-mr-sm"></div>
                Customer Info
              </div>
              
              <div class="row items-center q-mb-md">
                <q-avatar size="64px" class="q-mr-md shadow-2">
                  <img :src="order.consumer?.profile_picture_url || 'https://cdn.quasar.dev/img/avatar.png'">
                </q-avatar>
                <div>
                  <div class="text-h6 text-weight-bold">{{ order.consumer?.full_name || 'Unknown' }}</div>
                  <q-badge color="red-1" text-color="red-8" class="text-weight-bold q-pa-xs">Total Orders: {{ order.consumer?.total_orders || 1 }}</q-badge>
                </div>
              </div>

              <q-list class="q-mt-md">
                <q-item class="q-pa-none q-mb-sm">
                  <q-item-section avatar class="min-w-0 q-pr-sm">
                    <q-icon name="email" color="grey-6" />
                  </q-item-section>
                  <q-item-section class="text-dark">{{ order.consumer?.email || 'N/A' }}</q-item-section>
                </q-item>
                <q-item class="q-pa-none">
                  <q-item-section avatar class="min-w-0 q-pr-sm">
                    <q-icon name="phone" color="grey-6" />
                  </q-item-section>
                  <q-item-section class="text-dark">{{ order.consumer?.phone_number || 'N/A' }}</q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>

          <!-- Pick up Address -->
          <q-card class="premium-glass-card">
            <q-card-section class="q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark q-mb-lg row items-center">
                <div class="header-accent-red q-mr-sm"></div>
                Pick up Address
              </div>

              <div class="bg-red-50 border-radius-12 q-pa-md q-mb-md border-red-light">
                <div class="row items-start">
                  <q-icon name="storefront" color="red-8" size="24px" class="q-mr-md q-mt-xs" />
                  <div>
                    <div class="text-subtitle1 text-weight-bold text-dark">{{ order.store?.store_name || 'Vendor Store' }}</div>
                    <div class="text-grey-7 q-mt-xs text-caption">{{ order.store?.address || 'Store address not provided.' }}</div>
                  </div>
                </div>
              </div>

              <q-btn outline icon="directions" label="Get Directions" color="dark" class="full-width btn-3d-outline" no-caps />
            </q-card-section>
          </q-card>

        </div>

      </div>
    </div>

    <!-- Loading State -->
    <div v-else class="flex flex-center full-height" style="min-height: 60vh;">
      <q-spinner-dots size="40px" color="red-8" />
    </div>

  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'

const route = useRoute()
const $q = useQuasar()
const order = ref(null)

const getStatusColor = (status) => {
  switch (String(status).toLowerCase()) {
    case 'placed': return 'blue-6'
    case 'preparing': return 'amber-7'
    case 'ready for pickup': return 'orange-5'
    case 'picked up': return 'green-6'
    case 'completed': return 'green-6'
    case 'cancelled': return 'red-6'
    default: return 'grey-6'
  }
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDate = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const updateStatus = (newStatus) => {
  // Skeleton implementation for status update
  if (order.value) {
    order.value.status = newStatus
    $q.notify({ type: 'positive', message: `Order status updated to ${newStatus}` })
  }
}

const fetchOrderDetails = async () => {
  try {
    const res = await api.get(`/vendor/orders/${route.params.id}`)
    order.value = res.data
  } catch (error) {
    console.error('Failed to load order details', error)
  }
}

onMounted(() => {
  fetchOrderDetails()
})
</script>

<style scoped>
.vendor-page {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}
.premium-glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}
.h-full {
  height: 100%;
}
.border-radius-12 {
  border-radius: 12px;
}
.border-bottom {
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
.border-dotted {
  border-bottom: 1px dashed rgba(226, 232, 240, 1);
}
.header-accent-red {
  width: 4px;
  height: 20px;
  background: #B91C1C;
  border-radius: 4px;
  box-shadow: 2px 0 8px rgba(185, 28, 28, 0.3);
}
.btn-3d-outline {
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid #E2E8F0;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  transition: all 0.2s ease;
}
.btn-3d-outline:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  background: #F8FAFC !important;
}
.bg-red-50 {
  background-color: #FEF2F2;
}
.border-red-light {
  border: 1px solid #FEE2E2;
}
:deep(.q-timeline__title) {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 2px;
}
:deep(.q-timeline__subtitle) {
  font-size: 12px;
  color: #64748b;
  margin-bottom: 16px;
  opacity: 1;
}
</style>
