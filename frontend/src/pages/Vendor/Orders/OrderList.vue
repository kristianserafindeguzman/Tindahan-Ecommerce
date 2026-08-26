<template>
  <q-page padding class="vendor-page relative-position" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= HEADER AREA (Responsive) ================= -->
      <div class="page-header q-mb-lg q-mt-sm">
        <div class="row items-center justify-between no-wrap">
          
          <!-- Title & Subtitle Group -->
          <div class="row items-center no-wrap col q-pr-sm">
            <div class="glass-icon-box q-mr-md shrink-none">
              <q-icon name="receipt_long" size="26px" class="text-brand-red" />
            </div>
            <div class="col">
              <!-- Desktop uses standard text-h4, mobile uses text-h5 -->
              <h1 class="text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight" :class="$q.screen.lt.md ? 'text-h5' : 'text-h4'" style="line-height: 1.1;">Order List</h1>
              <p class="text-blue-grey-5 q-mt-xs q-mb-none" :class="$q.screen.lt.md ? 'text-caption' : 'text-body1'" style="line-height: 1.3;">
                Manage and track all neighborhood customer orders.
              </p>
            </div>
          </div>

          <!-- Export Button Group -->
          <div class="col-auto flex flex-center">
            <!-- Desktop Export Button -->
            <q-btn 
              v-if="!$q.screen.lt.md"
              outline 
              icon="download" 
              label="Export Report" 
              color="red-9" 
              no-caps 
              class="btn-glass-outline text-weight-bold q-px-md" 
              :loading="isExporting" 
              @click="exportOrders" 
            />
            
            <!-- Mobile Export Button with Text -->
            <div v-else class="column items-center justify-center cursor-pointer" @click="exportOrders">
              <q-btn 
                outline 
                icon="download" 
                color="red-9" 
                class="btn-glass-outline" 
                style="padding: 8px;"
                round
                dense
                :loading="isExporting" 
              />
              <span class="text-red-9 text-weight-bold q-mt-xs" style="font-size: 10px; letter-spacing: 0.5px;">EXPORT</span>
            </div>
          </div>
          
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card" style="border-radius: 16px;">
        
        <!-- Search & Filters (Side-by-side on desktop, stacked on mobile) -->
        <q-card-section class="q-pa-md q-pa-lg-lg border-bottom row items-center justify-between q-col-gutter-y-md q-col-gutter-x-md">
          
          <!-- Search -->
          <div class="col-12 col-md-5 col-lg-4">
            <q-input v-model="search" outlined dense class="custom-glass-input exact-height" placeholder="Search Order ID or Customer...">
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>

          <!-- Filters (Connected group on desktop, swipeable on mobile) -->
          <div class="col-12 col-md-7 col-lg-8 flex justify-md-end scroll-container">
            <q-btn-group flat class="bg-slate-50 border-slate-light rounded-borders q-pa-xs items-stretch filter-group-wrapper">
              <q-btn v-for="status in statuses" :key="status" :label="status" 
                v-ripple
                :unelevated="activeStatus === status" 
                :flat="activeStatus !== status"
                :class="activeStatus === status ? 'bg-gradient-red text-white shadow-3' : 'text-blue-grey-6 hover-text-dark'" 
                no-caps 
                class="filter-pill q-px-md text-weight-bold transition-ease text-no-wrap" 
                style="font-size: 13px;"
                @click="activeStatus = status"
              />
            </q-btn-group>
          </div>
        </q-card-section>

        <!-- Table (Standard on desktop, Cards on mobile) -->
        <q-table
          :grid="$q.screen.lt.md"
          flat
          class="custom-premium-table"
          :class="{ 'bg-transparent': $q.screen.lt.md }"
          :rows="filteredOrders"
          :columns="columns"
          row-key="order_id"
          :loading="loading"
          @row-click="onRowClick"
          card-container-class="q-col-gutter-md q-pa-sm"
        >
          <!-- Loading State -->
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
                  <q-icon name="inbox" size="56px" color="blue-grey-3" />
                </div>
                <div class="text-h6 text-weight-bold text-blue-grey-8">No orders found</div>
              </div>
            </div>
          </template>

          <!-- ================= DESKTOP TABLE FORMATTERS ================= -->
          <template #body-cell-order_id="props">
            <q-td :props="props">
              <!-- Reverted back to strictly #ID for desktop view -->
              <span class="order-id-badge text-weight-bold text-red-8 q-px-sm q-py-xs bg-red-1 transition-ease">#{{ props.row.order_id }}</span>
            </q-td>
          </template>

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
          
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bolder status-chip q-px-md shadow-1" style="font-size: 13px;">
                {{ formatStatus(props.row.status) }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <q-btn flat round dense icon="chevron_right" color="blue-grey-4" class="hover-action-btn transition-ease" @click.stop="goToOrder(props.row.order_id)" />
            </q-td>
          </template>

          <!-- ================= MOBILE GRID CARD LAYOUT ================= -->
          <template #item="props">
            <div class="col-12 col-sm-6">
              <q-card class="mobile-grid-card q-pa-md transition-ease shadow-soft" bordered @click="onRowClick($event, props.row)">
                
                <div class="row justify-between items-center q-mb-sm">
                  <!-- Kept 'Order #1' exclusively for the mobile view layout -->
                  <span class="order-id-badge text-weight-bold text-red-8 q-px-sm bg-red-1" style="font-size: 13px; padding-top: 4px; padding-bottom: 4px;">Order #{{ props.row.order_id }}</span>
                  
                  <q-chip :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bold shadow-1 q-ma-none" style="font-size: 12px; height: 26px; padding: 0 12px;">
                    {{ formatStatus(props.row.status) }}
                  </q-chip>
                </div>
                
                <q-separator class="q-my-sm" color="grey-2" />
                
                <div class="row items-center q-mb-md">
                  <q-avatar size="44px" class="q-mr-md bg-blue-grey-1 shadow-1 border-white">
                    <img v-if="props.row.consumer?.profile_picture_url" :src="props.row.consumer.profile_picture_url">
                    <q-icon v-else name="person" color="blue-grey-6" size="24px" />
                  </q-avatar>
                  <div>
                    <div class="text-weight-bold text-blue-grey-9" style="font-size: 15px;">{{ props.row.consumer?.full_name || 'Unknown' }}</div>
                    <div class="text-caption text-blue-grey-5">{{ formatDate(props.row.created_at) }}</div>
                  </div>
                </div>
                
                <div class="row justify-between items-end">
                  <div>
                    <div class="text-weight-bold text-blue-grey-4" style="text-transform: uppercase; font-size: 11px;">Total Amount</div>
                    <div class="text-weight-bolder text-dark text-subtitle1" style="line-height: 1;">₱{{ formatNumber(props.row.total_amount) }}</div>
                  </div>
                  <q-btn flat round dense icon="chevron_right" color="blue-grey-3" />
                </div>
                
              </q-card>
            </div>
          </template>

        </q-table>
      </q-card>

      <!-- ================= PREMIUM MOBILE BOTTOM NAVIGATION ================= -->
      <div v-if="$q.screen.lt.md" class="mobile-bottom-nav row justify-around items-center">
        <div class="nav-item-wrapper" @click="router.push('/vendor/dashboard')">
          <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
            <q-icon name="home" size="26px" />
          </q-btn>
        </div>
        <div class="nav-item-wrapper" @click="router.push('/vendor/orders/list')">
          <!-- Active state applied to Orders -->
          <q-btn flat round class="mobile-nav-btn nav-active shadow-3">
            <q-icon name="receipt_long" size="24px" />
          </q-btn>
        </div>
        <div class="nav-item-wrapper" @click="router.push('/vendor/products/list')">
          <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
            <q-icon name="inventory_2" size="26px" />
          </q-btn>
        </div>
        <div class="nav-item-wrapper" @click="router.push('/vendor/sales')">
          <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
            <q-icon name="analytics" size="26px" />
          </q-btn>
        </div>
      </div>

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
  background: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}

/* Brand Colors */
.text-brand-red { color: #b91c1c !important; }
.bg-gradient-red { background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%) !important; }

/* Background Glows */
.bg-glow { position: absolute; width: 500px; height: 500px; border-radius: 50%; filter: blur(140px); z-index: 0; opacity: 0.15; pointer-events: none; }
.bg-glow-primary { top: -50px; left: -50px; background: radial-gradient(circle, rgba(185, 28, 28, 0.25) 0%, transparent 70%); }
.bg-glow-secondary { bottom: 100px; right: -50px; background: radial-gradient(circle, rgba(69, 10, 10, 0.25) 0%, transparent 70%); }

.tracking-tight { letter-spacing: -0.02em; }
.shrink-none { flex-shrink: 0; }

/* Icon Box */
.glass-icon-box {
  width: 48px; height: 48px; background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.9); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(185, 28, 28, 0.1); 
}

/* Glass Cards */
.premium-glass-card { background: rgba(255, 255, 255, 0.98); border: 1px solid rgba(241, 245, 249, 1); box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }

/* Inputs & Buttons */
.exact-height :deep(.q-field__control) { height: 40px !important; min-height: 40px !important; }
.filter-group-wrapper { height: 40px; }
.custom-glass-input :deep(.q-field__control) { background: rgba(241, 245, 249, 0.6); border-radius: 8px; }
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.8); }
.custom-glass-input :deep(.q-field--focused .q-field__control) { background: #ffffff; box-shadow: 0 0 0 2px rgba(185, 28, 28, 0.15); border-color: #b91c1c; }

.btn-glass-outline { border-radius: 8px !important; background: rgba(255, 255, 255, 0.9) !important; border: 1px solid currentColor; transition: all 0.2s ease; }
.btn-glass-outline:hover { background: #ffffff !important; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05); transform: translateY(-2px); }

/* Utilities */
.border-bottom { border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-light { border: 1px solid #e2e8f0; }
.transition-ease { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.hover-text-dark:hover { color: #1e293b !important; }
.opacity-80 { opacity: 0.85; }
.filter-pill { border-radius: 6px; }

/* Custom Premium Table Styling */
:deep(.custom-premium-table thead tr th) { background: rgba(248, 250, 252, 0.7); font-weight: 700; color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
:deep(.custom-premium-table tbody td) { padding: 16px 20px; border-bottom: 1px solid rgba(241, 245, 249, 1); cursor: pointer; transition: all 0.2s ease; }
:deep(.custom-premium-table tbody tr:hover) { background: #ffffff; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); transform: scale(1.002); z-index: 5; position: relative; }
:deep(.custom-premium-table tbody tr:hover td) { border-bottom-color: transparent; }
:deep(.custom-premium-table tbody tr:hover .order-id-badge) { background: rgba(185, 28, 28, 0.1) !important; color: #b91c1c !important; border-color: rgba(185, 28, 28, 0.4) !important; }

.order-id-badge { font-family: monospace; font-size: 13px; border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 6px; }
.status-chip { border: 1px solid rgba(255,255,255,0.8); }
.border-white { border: 2px solid #ffffff; }

/* Mobile Grid Card Styling */
.mobile-grid-card {
  background: #ffffff;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
  cursor: pointer;
}
.mobile-grid-card:active { transform: scale(0.98); background: #f8fafc; }

/* Native Swiping for Mobile Filters */
.scroll-container {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.scroll-container::-webkit-scrollbar { display: none; }

/* Mobile overrides */
@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { padding: 16px 12px calc(80px + env(safe-area-inset-bottom)) 12px !important; }
  .desktop-only { display: none !important; }
  .mobile-only { display: block !important; }
  .full-width-mobile { width: 100%; }
  .scroll-container {
    margin-left: -16px;
    margin-right: -16px;
    padding-left: 16px;
    padding-right: 16px;
    padding-bottom: 8px; /* Room for shadow */
  }

  /* Premium Glass Floating Bottom Navigation */
  .mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: calc(75px + env(safe-area-inset-bottom));
    padding-bottom: env(safe-area-inset-bottom);
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-top: 1px solid rgba(255, 255, 255, 0.5);
    z-index: 2000;
    box-shadow: 0 -10px 25px rgba(15, 23, 42, 0.05);
  }
  
  .nav-item-wrapper {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
  }
  
  .mobile-nav-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    padding: 0;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .nav-active {
    background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 8px 16px rgba(185, 28, 28, 0.35) !important;
    transform: translateY(-4px);
  }
  .nav-active .q-icon {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
  }
}
</style>