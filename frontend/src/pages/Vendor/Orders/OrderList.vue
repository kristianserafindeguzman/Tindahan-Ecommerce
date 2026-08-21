<template>
  <q-page class="vendor-page relative-position">
    <!-- Subtle Ambient Background Glows (STRICTLY DARK RED) -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <!-- z-index: 1 applied to prevent modal stacking conflicts -->
    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-xl q-mt-sm row items-center justify-between">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md">
            <q-icon name="receipt_long" size="26px" class="text-brand-red" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">Order List</h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none">Manage and track all neighborhood customer orders.</p>
          </div>
        </div>
        <div class="row q-gutter-sm q-mt-sm q-mt-md-none">
          <q-btn outline icon="download" label="Export" color="red-9" no-caps class="btn-glass-outline text-weight-bold q-px-md" :loading="isExporting" @click="exportOrders" />
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card" style="border-radius: 16px;">
        <q-card-section class="q-pa-lg border-bottom row items-center justify-between q-col-gutter-y-md">
          
          <!-- Search -->
          <div class="col-12 col-lg-4 col-md-5">
            <q-input v-model="search" outlined dense class="custom-glass-input exact-height" placeholder="Search Order ID or Customer...">
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>

          <!-- Status Filters (Perfectly Aligned Height) -->
          <div class="col-12 col-lg-8 col-md-7 text-right flex justify-end">
            <q-btn-group flat class="bg-slate-50 border-slate-light rounded-borders q-pa-xs items-stretch filter-group-wrapper">
              <q-btn v-for="status in statuses" :key="status" :label="status" 
                v-ripple
                :unelevated="activeStatus === status" 
                :flat="activeStatus !== status"
                :class="activeStatus === status ? 'bg-gradient-red text-white shadow-3' : 'text-blue-grey-6 hover-text-dark'" 
                no-caps 
                class="filter-pill q-px-md text-weight-bold transition-ease" 
                style="font-size: 13px;"
                @click="activeStatus = status"
              />
            </q-btn-group>
          </div>
        </q-card-section>

        <!-- Table -->
        <q-table
          flat
          class="custom-premium-table"
          :rows="filteredOrders"
          :columns="columns"
          row-key="order_id"
          :loading="loading"
          @row-click="onRowClick"
        >
          <!-- Perfectly Centered Premium Loading State -->
          <template #loading>
            <q-inner-loading showing class="bg-white opacity-80" style="backdrop-filter: blur(4px); z-index: 10;">
              <q-spinner-dots size="50px" color="red-9" />
              <div class="text-red-9 text-weight-bold q-mt-sm tracking-tight">Fetching orders...</div>
            </q-inner-loading>
          </template>

          <!-- Empty State -->
          <template #no-data>
            <div class="full-width row flex-center q-pa-xl empty-state-glass" v-show="!loading">
              <div class="text-center z-top relative-position">
                <div class="empty-icon-wrapper q-mb-lg">
                  <q-icon name="inbox" size="56px" color="blue-grey-3" class="drop-shadow-icon" />
                  <div class="icon-pulse"></div>
                </div>
                <div class="text-h6 text-weight-bold text-blue-grey-8">No orders found</div>
                <div class="text-body2 text-blue-grey-5 q-mt-xs">Orders matching your current filters will appear here.</div>
              </div>
            </div>
          </template>

          <!-- Order ID Formatter -->
          <template #body-cell-order_id="props">
            <q-td :props="props">
              <span 
                class="order-id-badge text-weight-bold text-red-8 q-px-sm q-py-xs bg-red-1 transition-ease"
                style="border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 6px;"
              >
                #{{ props.row.order_id }}
              </span>
            </q-td>
          </template>

          <!-- Customer Formatter -->
          <template #body-cell-customer="props">
            <q-td :props="props">
              <div class="row items-center">
                <q-avatar size="32px" class="q-mr-sm bg-blue-grey-1 shadow-soft border-white">
                  <img v-if="props.row.consumer?.profile_picture_url" :src="props.row.consumer.profile_picture_url">
                  <q-icon v-else name="person" color="blue-grey-6" size="22px" />
                </q-avatar>
                <div class="text-weight-bold">{{ props.row.consumer?.full_name || 'Unknown' }}</div>
              </div>
            </q-td>
          </template>
          
          <!-- Status Formatter (Increased Size & Distinct Colors) -->
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bolder status-chip q-px-md shadow-1" style="font-size: 13px;">
                {{ formatStatus(props.row.status) }}
              </q-chip>
            </q-td>
          </template>

          <!-- Action Arrow -->
          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <div class="action-btn-wrapper">
                <q-btn flat round dense icon="chevron_right" color="blue-grey-4" class="hover-action-btn transition-ease" @click.stop="goToOrder(props.row.order_id)" />
              </div>
            </q-td>
          </template>
        </q-table>
      </q-card>

    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'

const router = useRouter()
const search = ref('')
const activeStatus = ref('All')
const statuses = ['All', 'Placed', 'Preparing', 'Ready for Pickup', 'Picked up', 'Cancelled']
const loading = ref(true)
const isExporting = ref(false)

const orders = ref([])

const columns = [
  { name: 'order_id', label: 'Order ID', field: 'order_id', align: 'left', sortable: true },
  { name: 'date', label: 'Date', field: row => formatDate(row.created_at), align: 'left', sortable: true },
  { name: 'customer', label: 'Customer', field: 'customer', align: 'left' },
  { name: 'price', label: 'Price (₱)', field: row => formatNumber(row.total_amount), align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: row => formatStatus(row.status), align: 'left' },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

const filteredOrders = computed(() => {
  return orders.value.filter(order => {
    const matchesSearch = search.value === '' || 
      String(order.order_id).includes(search.value) || 
      (order.consumer?.full_name || '').toLowerCase().includes(search.value.toLowerCase());
      
    const matchesStatus = activeStatus.value === 'All' || 
      formatStatus(order.status).toLowerCase() === activeStatus.value.toLowerCase();

    return matchesSearch && matchesStatus;
  })
})

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

const goToOrder = (id) => {
  router.push('/vendor/orders/' + id)
}

const onRowClick = (evt, row) => {
  goToOrder(row.order_id)
}

const exportOrders = async () => {
  try {
    isExporting.value = true
    const response = await api.get('/vendor/orders/export', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const dateStr = new Date().toISOString().split('T')[0]
    link.setAttribute('download', `Tindahan-Order-List-Report-${dateStr}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Export failed:', error)
  } finally {
    isExporting.value = false
  }
}

onMounted(async () => {
  try {
    setTimeout(async () => {
      const res = await api.get('/vendor/orders')
      if (res.data) {
        orders.value = res.data.data || res.data
      }
      loading.value = false
    }, 800)
  } catch (error) {
    console.error('Failed to load orders', error)
    loading.value = false
  }
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

/* Strictly Dark Red Brand Colors */
.text-brand-red { color: #B91C1C !important; }
.bg-gradient-red { background: linear-gradient(90deg, #B91C1C 0%, #450A0A 100%) !important; }

/* Subtle Ambient Glows (Fixed to Dark Red, no pink) */
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

/* Beautiful Header Glass Icon Box */
.glass-icon-box {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.1); 
}

/* Clean Glassmorphism Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(241, 245, 249, 1);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); 
}

/* Perfect Height Alignment for Search and Filters */
.exact-height :deep(.q-field__control) {
  height: 40px !important;
  min-height: 40px !important;
}
.filter-group-wrapper {
  height: 40px; 
}

/* Custom Inputs & Buttons */
.custom-glass-input :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.6);
  border-radius: 8px;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.8); }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 0 0 2px rgba(185, 28, 28, 0.15); 
  border-color: #B91C1C;
}

.btn-glass-outline {
  border-radius: 8px !important;
  background: rgba(255, 255, 255, 0.9) !important;
  border: 1px solid currentColor;
  transition: all 0.2s ease;
}
.btn-glass-outline:hover {
  background: #ffffff !important;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
  transform: translateY(-2px);
}

/* Utilities */
.border-bottom { border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-light { border: 1px solid #e2e8f0; }
.transition-ease { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.hover-text-dark:hover { color: #1e293b !important; }
.opacity-80 { opacity: 0.85; }

/* Filter Pills */
.filter-pill { 
  border-radius: 6px; 
}

/* Custom Premium Table Styling */
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7); backdrop-filter: blur(8px); font-weight: 700;
  color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(241, 245, 249, 1); 
  cursor: pointer;
  transition: all 0.2s ease;
}

/* Enhanced Row Hover Micro-Interactions */
:deep(.custom-premium-table tbody tr) {
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
:deep(.custom-premium-table tbody tr:hover) {
  background: #ffffff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
  transform: scale(1.002);
  z-index: 5;
  position: relative;
}
:deep(.custom-premium-table tbody tr:hover td) {
  border-bottom-color: transparent;
}
:deep(.custom-premium-table tbody tr:hover .hover-action-btn) {
  color: #B91C1C !important; 
  transform: translateX(4px) scale(1.1);
  background: rgba(185, 28, 28, 0.05);
}
:deep(.custom-premium-table tbody tr:hover .order-id-badge) {
  background: rgba(185, 28, 28, 0.1) !important;
  color: #B91C1C !important;
  border-color: rgba(185, 28, 28, 0.4) !important;
}

/* Specific Cell Styling */
.order-id-badge {
  font-family: monospace;
  font-size: 13px;
}
.status-chip { border: 1px solid rgba(255,255,255,0.8); }

.action-btn-wrapper {
  display: flex;
  justify-content: flex-end;
  align-items: center;
}

/* Empty State Styling */
.empty-state-glass {
  background: rgba(248, 250, 252, 0.5);
  border: 1px dashed #E2E8F0;
  border-radius: 12px;
}
</style>