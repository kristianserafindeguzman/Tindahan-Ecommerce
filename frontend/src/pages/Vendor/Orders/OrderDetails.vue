<template>
  <q-page class="vendor-page relative-position">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <div class="page-container relative-position" style="z-index: 1;" v-if="order">
      
      <!-- ================= BREADCRUMBS & TOP BAR ================= -->
      <div class="q-mb-md row items-center justify-between">
        <q-breadcrumbs v-if="!isEmbedded" class="text-slate-500 text-weight-medium" active-color="dark">
          <q-breadcrumbs-el label="Order List" to="/vendor/orders/list" />
          <q-breadcrumbs-el label="Order Details" />
        </q-breadcrumbs>
        <q-btn v-else flat icon="arrow_back" color="slate-700" label="Back to Orders" no-caps @click="$emit('back')" class="text-weight-bold" />
      </div>

      <!-- ================= HERO HEADER (RED GRADIENT) ================= -->
      <div class="page-header q-mb-xl row items-center justify-between header-gradient text-white q-pa-lg shadow-4">
        <div class="row items-center">
          <div class="icon-box-white q-mr-lg shadow-1">
            <q-icon name="receipt_long" size="32px" color="red-9" />
          </div>
          <div>
            <div class="row items-center q-mb-xs">
              <h1 class="text-h4 text-weight-bolder q-ma-none q-mr-md tracking-tight">Order #{{ order.order_id }}</h1>
              <q-chip size="sm" :color="getStatusColor(order.status)" text-color="white" class="text-weight-bolder shadow-1 q-px-md" style="font-size: 13px; min-height: 26px;">
                {{ formatStatus(order.status) }}
              </q-chip>
            </div>
            <div class="text-subtitle1 text-weight-medium" style="opacity: 0.85;">
              {{ formatDate(order.created_at) }} <span class="q-mx-sm">•</span> {{ order.consumer?.full_name || 'Unknown Customer' }}
            </div>
          </div>
        </div>
        
        <div class="row q-gutter-md q-mt-md q-md-none">
          <q-btn 
            unelevated 
            icon="print" 
            label="Print Receipt" 
            text-color="red-9" 
            class="bg-white text-weight-bold q-px-md btn-hero-action" 
            no-caps 
            :loading="isExporting" 
            @click="printOrder" 
          />
          
          <template v-if="order.status !== 'picked_up' && order.status !== 'cancelled' && order.status !== 'completed'">
            <q-btn-dropdown 
              :loading="isUpdating" 
              outline 
              color="white" 
              label="Update Status" 
              no-caps 
              class="text-weight-bold q-px-md btn-hero-outline"
            >
              <q-list class="premium-dropdown-list">
                <q-item clickable v-close-popup @click="updateStatus('preparing')" v-if="['placed'].includes(order.status)" class="hover-grey">
                  <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="inventory_2" color="purple-5" size="20px"/></q-item-section>
                  <q-item-section class="text-weight-medium">Pack / Prepare</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="updateStatus('ready_for_pickup')" v-if="['placed', 'preparing'].includes(order.status)" class="hover-grey">
                  <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="storefront" color="orange-6" size="20px"/></q-item-section>
                  <q-item-section class="text-weight-medium">Ready for Pickup</q-item-section>
                </q-item>
                <q-item clickable v-close-popup @click="updateStatus('picked_up')" v-if="['ready_for_pickup'].includes(order.status)" class="hover-grey">
                  <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="check_circle" color="green-6" size="20px"/></q-item-section>
                  <q-item-section class="text-weight-medium">Picked up (Complete)</q-item-section>
                </q-item>
                <q-separator class="q-my-xs" />
                <q-item clickable v-close-popup @click="promptCancelOrder" v-if="!['picked_up', 'cancelled'].includes(order.status)" class="hover-red">
                  <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="cancel" color="red-9" size="20px"/></q-item-section>
                  <q-item-section class="text-weight-bold text-red-9">Cancel Order</q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </template>
          <template v-else>
            <div class="bg-white text-slate-700 text-weight-bold q-px-lg q-py-sm shadow-1" style="border-radius: 8px; font-size: 14px;">
              <q-icon name="lock" class="q-mr-xs" size="16px" /> Order Finalized
            </div>
          </template>
        </div>
      </div>

      <div class="row q-col-gutter-xl">
        
        <!-- ================= LEFT COLUMN ================= -->
        <div class="col-12 col-md-8">
          
          <!-- Tracking & Timeline Grid -->
          <div class="row q-col-gutter-lg q-mb-xl">
            <div class="col-12 col-md-6">
              <q-card class="premium-glass-card h-full">
                <q-card-section class="q-pa-lg">
                  <div class="text-h6 text-weight-bold text-dark q-mb-lg row items-center">
                    <div class="header-accent-red q-mr-md"></div>
                    Order Timeline
                  </div>
                  
                  <q-timeline color="red-9" class="q-px-sm">
                    <q-timeline-entry title="Order Placed" :subtitle="formatDate(order.created_at)" icon="shopping_cart" />
                    
                    <q-timeline-entry title="Preparing & Packing" 
                      :subtitle="isStatusActive('preparing') ? 'Currently packing items' : 'Pending update'" 
                      icon="inventory_2" 
                      :color="isStatusActive('preparing') ? 'purple-5' : 'grey-4'" />
                      
                    <q-timeline-entry title="Ready for Pickup" 
                      :subtitle="isStatusActive('ready_for_pickup') ? 'Waiting at counter' : 'Pending update'" 
                      icon="storefront" 
                      :color="isStatusActive('ready_for_pickup') ? 'orange-6' : 'grey-4'" />
                      
                    <q-timeline-entry title="Picked up" 
                      :subtitle="isStatusActive('picked_up') ? 'Order completed' : 'Pending update'" 
                      icon="task_alt" 
                      :color="order.status === 'picked_up' ? 'green-6' : 'grey-4'" />
                  </q-timeline>
                </q-card-section>
              </q-card>
            </div>
            
            <div class="col-12 col-md-6">
              <q-card class="premium-glass-card h-full overflow-hidden border-slate-light" style="min-height: 350px; padding: 0;">
                <OrderTrackingMap 
                  :storeLat="order.store?.latitude"
                  :storeLng="order.store?.longitude"
                  :consumerLat="order.consumer_latitude"
                  :consumerLng="order.consumer_longitude"
                  :storeName="order.store?.store_name"
                  :consumerName="order.consumer?.full_name || 'Customer'"
                />
              </q-card>
            </div>
          </div>

          <!-- Products Card -->
          <q-card class="premium-glass-card shadow-4">
            <q-card-section class="q-pa-lg border-bottom bg-white" style="border-radius: 16px 16px 0 0;">
              <div class="text-h6 text-weight-bold text-dark row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Purchased Items
              </div>
            </q-card-section>
            
            <q-list separator class="q-px-md q-py-sm bg-white">
              <q-item v-for="item in order.items" :key="item.order_item_id" class="q-py-lg">
                <q-item-section avatar class="q-pr-md">
                  <q-avatar rounded size="64px" class="bg-slate-100 shadow-soft">
                    <img v-if="item.inventory?.image_url" :src="item.inventory.image_url" style="object-fit: cover;" />
                    <q-icon v-else name="inventory_2" color="blue-grey-3" size="32px" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-bold text-subtitle1 text-slate-800">{{ item.inventory?.product_name || 'Product' }}</q-item-label>
                  <q-item-label caption class="text-blue-grey-5 font-medium q-mt-xs">₱{{ formatNumber(item.subtotal / item.quantity) }} per item</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="text-weight-bold text-slate-600 bg-slate-100 q-px-sm q-py-xs" style="border-radius: 6px;">Qty: {{ item.quantity }}</div>
                </q-item-section>
                <q-item-section side class="q-pl-lg">
                  <div class="text-h6 text-weight-bold text-slate-800">₱{{ formatNumber(item.subtotal) }}</div>
                </q-item-section>
              </q-item>
            </q-list>

            <q-card-section class="q-pa-lg bg-grey-1" style="border-radius: 0 0 16px 16px; border-top: 1px solid #e2e8f0;">
              <div class="row justify-end">
                <div class="col-12 col-sm-6 col-md-5">
                  <div class="row justify-between q-mb-sm text-slate-600 font-medium">
                    <div>Subtotal</div>
                    <div class="text-weight-bold text-slate-800">₱{{ formatNumber(order.total_amount) }}</div>
                  </div>
                  <div class="row justify-between q-mb-md text-slate-600 font-medium">
                    <div>Platform Fee</div>
                    <div class="text-weight-bold text-slate-800">₱{{ formatNumber(order.platform_fee || 0) }}</div>
                  </div>
                  <div class="border-dotted q-my-md"></div>
                  <div class="row justify-between items-end text-dark q-mt-sm">
                    <div class="text-subtitle1 text-weight-bold">Total</div>
                    <div class="text-h4 text-weight-bolder text-brand-red tracking-tight">₱{{ formatNumber(order.total_amount) }}</div>
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- ================= RIGHT COLUMN ================= -->
        <div class="col-12 col-md-4">
          
          <!-- Cancellation Reason -->
          <q-slide-transition>
            <q-card class="premium-glass-card q-mb-lg border-red-light" v-if="order.status === 'cancelled'">
              <q-card-section class="q-pa-lg bg-red-50" style="border-radius: 16px;">
                <div class="text-subtitle1 text-weight-bolder text-red-9 q-mb-sm row items-center">
                  <q-icon name="error_outline" class="q-mr-sm" size="22px" />
                  Cancellation Reason
                </div>
                <div class="text-slate-800 font-medium">{{ order.cancellation_reason || 'No cancellation reason provided by the system.' }}</div>
              </q-card-section>
            </q-card>
          </q-slide-transition>

          <!-- Customer Info -->
          <q-card class="premium-glass-card q-mb-lg">
            <q-card-section class="q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark q-mb-lg row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Customer Info
              </div>
              
              <div class="row items-center q-mb-lg">
                <q-avatar size="64px" class="q-mr-md shadow-soft bg-slate-200 text-blue-grey-8 text-weight-bolder text-h5" style="border: 2px solid #fff;">
                  <img v-if="order.consumer?.profile_picture_url" :src="order.consumer.profile_picture_url">
                  <span v-else>{{ getInitials(order.consumer?.full_name) }}</span>
                </q-avatar>
                <div>
                  <div class="text-h6 text-weight-bold text-slate-800 leading-tight">{{ order.consumer?.full_name || 'Unknown' }}</div>
                  <q-badge color="red-50" text-color="red-9" class="text-weight-bold q-pa-sm q-mt-xs border-red-light" style="font-size: 11px; letter-spacing: 0.5px;">
                    <q-icon name="shopping_bag" size="14px" class="q-mr-xs" /> {{ order.consumer?.total_orders || 1 }} Total Orders
                  </q-badge>
                </div>
              </div>

              <div class="bg-slate-100 q-pa-md border-radius-12 border-slate-light">
                <div class="row items-center q-mb-sm">
                  <q-icon name="email" color="blue-grey-4" size="20px" class="q-mr-md" />
                  <div class="text-slate-700 font-medium">{{ order.consumer?.email || 'No email provided' }}</div>
                </div>
                <div class="row items-center">
                  <q-icon name="phone" color="blue-grey-4" size="20px" class="q-mr-md" />
                  <div class="text-slate-700 font-medium">{{ order.consumer?.phone_number || 'No phone provided' }}</div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- Pick up Address -->
          <q-card class="premium-glass-card">
            <q-card-section class="q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark q-mb-lg row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Pickup Location
              </div>

              <div class="row items-start q-mb-lg">
                <div class="bg-red-50 q-pa-sm rounded-borders q-mr-md border-red-light">
                  <q-icon name="storefront" color="red-9" size="24px" />
                </div>
                <div class="col">
                  <div class="text-subtitle1 text-weight-bold text-slate-800">{{ order.store?.store_name }}</div>
                  <div class="text-blue-grey-5 q-mt-xs text-caption font-medium leading-tight">{{ order.store?.address }}</div>
                </div>
              </div>

              <q-btn 
                outline 
                icon="directions" 
                label="Get Directions" 
                color="blue-grey-8" 
                class="full-width btn-glass-outline text-weight-bold" 
                no-caps 
                :disable="!order.consumer_latitude || !order.store?.latitude"
                @click="openDirections"
              />
            </q-card-section>
          </q-card>

        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else class="flex flex-center full-height" style="min-height: 60vh;">
      <q-spinner-dots size="50px" color="red-9" />
    </div>

    <!-- Cancellation Dialog -->
    <q-dialog v-model="showCancelDialog" persistent>
      <q-card style="min-width: 400px; border-radius: 16px;" class="bg-white shadow-4">
        <q-card-section class="q-pa-lg bg-red-50 border-bottom-red">
          <div class="text-h6 text-weight-bolder text-red-9 row items-center">
            <q-icon name="warning" size="28px" class="q-mr-sm" />
            Cancel Order
          </div>
        </q-card-section>

        <q-card-section class="q-pa-lg">
          <div class="text-body2 text-slate-600 q-mb-md">Please specify the reason for cancelling this order. This action cannot be undone.</div>
          
          <q-checkbox v-model="cancelReasonOutOfStock" label="Item(s) out of stock" class="q-mb-md text-slate-800 text-weight-medium" color="red-9" />
          
          <q-input
            v-model="cancelReasonText"
            type="textarea"
            label="Additional Details (Required)"
            outlined
            dense
            color="red-9"
            autofocus
            :rules="[val => !!val || 'Reason is required']"
            class="custom-glass-input"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-pa-md bg-slate-50 border-top-light">
          <q-btn flat label="Back" color="blue-grey-6" v-close-popup no-caps class="text-weight-bold" />
          <q-btn unelevated label="Confirm Cancellation" color="red-9" @click="confirmCancelOrder" :loading="isUpdating" no-caps class="text-weight-bold q-px-md" style="border-radius: 8px;" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import OrderTrackingMap from '@/components/shared/OrderTrackingMap.vue'

const props = defineProps({
  orderId: {
    type: [String, Number],
    default: null
  },
  isEmbedded: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['back'])

const route = useRoute()
const $q = useQuasar()
const order = ref(null)
const isUpdating = ref(false)
const isExporting = ref(false)

const showCancelDialog = ref(false)
const cancelReasonOutOfStock = ref(false)
const cancelReasonText = ref('')

watch(cancelReasonOutOfStock, (val) => {
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

const printOrder = async () => {
  if (!order.value) return
  
  try {
    isExporting.value = true
    const response = await api.get(`/vendor/orders/${order.value.order_id}/export`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Tindahan-Customer-Order-#${order.value.order_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Print failed:', error)
    $q.notify({ type: 'negative', message: 'Failed to generate print document' })
  } finally {
    isExporting.value = false
  }
}

const confirmCancelOrder = async () => {
  if (!cancelReasonText.value) {
    $q.notify({ type: 'warning', message: 'Please provide a cancellation reason.' })
    return
  }
  updateStatus('cancelled', cancelReasonText.value)
}

const getStatusColor = (status) => {
  switch (String(status).toLowerCase()) {
    case 'placed': return 'blue-6'
    case 'preparing': return 'purple-5'
    case 'ready_for_pickup': return 'orange-6'
    case 'picked_up': return 'green-6'
    case 'cancelled': return 'red-6'
    default: return 'grey-6'
  }
}

const formatStatus = (status) => {
  if (!status) return ''
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDate = (dateString) => {
  if (!dateString) return ''
  const d = new Date(dateString)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

// Generate initials for generic avatars
const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.trim().split(' ')
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase()
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

const isStatusActive = (step) => {
  const flow = ['placed', 'preparing', 'ready_for_pickup', 'picked_up']
  const currentIndex = flow.indexOf(order.value.status)
  const stepIndex = flow.indexOf(step)
  return currentIndex >= stepIndex
}

const updateStatus = async (newStatus, reason = null) => {
  if (order.value) {
    try {
      isUpdating.value = true
      const payload = { status: newStatus }
      if (reason) payload.cancellation_reason = reason
      
      const res = await api.patch(`/vendor/orders/${order.value.order_id}/status`, payload)
      order.value.status = res.data.order.status
      if (res.data.order.cancellation_reason) {
        order.value.cancellation_reason = res.data.order.cancellation_reason
      }
      $q.notify({ type: 'positive', message: `Order status updated to ${formatStatus(newStatus)}` })
      showCancelDialog.value = false
    } catch (err) {
      console.error(err.response?.data || err)
      const msg = err.response?.data?.message || err.message || "Unknown error occurred"
      $q.notify({ type: 'negative', message: msg })
    } finally {
      isUpdating.value = false
    }
  }
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

const fetchOrderDetails = async () => {
  const id = props.orderId || route.params.id
  if (!id) return
  
  try {
    const res = await api.get(`/vendor/orders/${id}`)
    order.value = res.data
  } catch (error) {
    console.error('Failed to load order details', error)
  }
}

watch(() => props.orderId, (newId) => {
  if (newId) {
    order.value = null
    fetchOrderDetails()
  }
})

onMounted(() => {
  fetchOrderDetails()
})
</script>

<style scoped>
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}

/* Brand Colors */
.text-brand-red { color: #B91C1C !important; }
.header-gradient { 
  background: linear-gradient(135deg, #B91C1C 0%, #7F1D1D 100%); 
  border-radius: 16px;
}
.bg-red-50 { background-color: #fef2f2 !important; }
.text-red-50 { color: #fef2f2 !important; }
.border-red-light { border: 1px solid #fca5a5 !important; }
.border-bottom-red { border-bottom: 1px solid #fecaca !important; }

.bg-slate-50 { background-color: #f8fafc; }
.bg-slate-100 { background-color: #f1f5f9; }
.bg-slate-200 { background-color: #e2e8f0; }
.text-slate-500 { color: #64748b; }
.text-slate-600 { color: #475569; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-light { border: 1px solid #e2e8f0; }

/* Subtle Ambient Glows */
.bg-glow {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(140px);
  z-index: 0;
  opacity: 0.15; 
  pointer-events: none;
}
.bg-glow-primary {
  top: -50px;
  left: -50px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.25) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: 100px;
  right: -50px;
  background: radial-gradient(circle, rgba(69, 10, 10, 0.25) 0%, transparent 70%);
}

.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.font-medium { font-weight: 500; }

/* Glass Icon Box for Header */
.icon-box-white {
  width: 56px;
  height: 56px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Premium Glassmorphism Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(241, 245, 249, 1);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); 
  border-radius: 16px;
}

/* Buttons */
.btn-hero-action {
  border-radius: 8px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.btn-hero-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.btn-hero-outline {
  border-radius: 8px;
  transition: all 0.2s ease;
  background: rgba(255,255,255,0.1);
}
.btn-hero-outline:hover {
  background: rgba(255,255,255,0.2);
}

.btn-glass-outline {
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid rgba(203, 213, 225, 0.8);
  transition: all 0.2s ease;
  height: 40px;
}
.btn-glass-outline:hover {
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
  transform: translateY(-1px);
}

/* Custom Dropdown Styling */
.premium-dropdown-list {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 8px;
  min-width: 200px;
}
.hover-grey:hover { background: rgba(241, 245, 249, 0.8); }
.hover-red:hover { background: rgba(254, 242, 242, 0.8); }

/* Utilities */
.border-bottom { border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.border-top-light { border-top: 1px solid #e2e8f0; }
.border-dotted { border-bottom: 2px dotted #cbd5e1; }
.border-radius-12 { border-radius: 12px; }
.shadow-soft { box-shadow: 0 2px 8px rgba(15,23,42,0.06); }
.h-full { height: 100%; }

.header-accent-red {
  width: 5px;
  height: 22px;
  background: linear-gradient(180deg, #B91C1C 0%, #7F1D1D 100%);
  border-radius: 6px;
  box-shadow: 2px 0 8px rgba(185, 28, 28, 0.2);
}

/* Inputs */
.custom-glass-input :deep(.q-field__control) {
  border-radius: 8px;
  background-color: #ffffff;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid #e2e8f0; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  box-shadow: 0 0 0 1px rgba(185, 28, 28, 0.15); 
  border-color: #B91C1C;
}

/* Timeline Customization */
:deep(.q-timeline__title) {
  font-size: 15px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 2px;
}
:deep(.q-timeline__subtitle) {
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  margin-bottom: 20px;
  opacity: 1;
}
:deep(.q-timeline__dot) {
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}
</style>