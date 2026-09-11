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
            <div class="glass-icon-box q-mr-md shrink-none" :style="$q.screen.lt.md ? 'width: 44px; height: 44px;' : 'width: 48px; height: 48px;'">
              <q-icon name="receipt_long" :size="$q.screen.lt.md ? '22px' : '26px'" class="text-brand-red" />
            </div>
            <div class="col">
              <h1 class="text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight" :class="$q.screen.lt.md ? 'text-h5' : 'text-h4'" style="line-height: 1.1;">Order List</h1>
              <p class="text-blue-grey-5 q-mt-xs q-mb-none" :class="$q.screen.lt.md ? 'text-caption' : 'text-body1'" style="line-height: 1.3;">
                Manage and track all neighborhood customer orders.
              </p>
            </div>
          </div>

          <!-- Desktop Export Button -->
          <div v-if="!$q.screen.lt.md" class="col-auto flex flex-center">
            <q-btn 
              outline 
              icon="download" 
              label="Export Report" 
              color="red-9" 
              no-caps 
              class="btn-glass-outline text-weight-bold q-px-md" 
              :loading="isExporting" 
              @click="exportOrders" 
            />
          </div>
          
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card" style="border-radius: 16px;">
        
        <!-- Search, Filters, & Mobile Export Controls -->
        <q-card-section class="q-pa-md q-pa-lg-lg border-bottom row items-center justify-between q-col-gutter-y-sm q-col-gutter-x-md">
          
          <!-- Search & Mobile Export Row (1-Line on mobile with Export label) -->
          <div class="col-12 col-md-5 col-lg-4">
            <div class="row items-center no-wrap q-gutter-x-xs full-width">
              <q-input 
                v-model="search" 
                outlined 
                dense 
                class="custom-glass-input exact-height col" 
                placeholder="Search Order ID or Customer..."
                hide-bottom-space
              >
                <template v-slot:prepend>
                  <q-icon name="search" size="18px" />
                </template>
                <template v-if="search" v-slot:append>
                  <q-icon name="close" size="16px" class="cursor-pointer text-slate-400" @click="search = ''" />
                </template>
              </q-input>

              <!-- Mobile-only Export Button with text label beside Search -->
              <q-btn
                v-if="$q.screen.lt.md"
                outline
                dense
                no-caps
                color="red-9"
                class="btn-mobile-export bg-white flex-shrink-0 q-px-sm"
                icon="download"
                label="Export"
                :loading="isExporting"
                @click="exportOrders"
              >
                <q-tooltip>Export Report</q-tooltip>
              </q-btn>
            </div>
          </div>

          <!-- Clean Horizontal Scrollable Filters -->
          <div class="col-12 col-md-7 col-lg-8 flex justify-md-end scroll-container-clean">
            <q-btn-group flat class="bg-slate-50 border-slate-light rounded-borders q-pa-xs items-stretch filter-group-wrapper">
              <q-btn 
                v-for="status in statuses" 
                :key="status" 
                :label="status" 
                v-ripple
                :unelevated="activeStatus === status" 
                :flat="activeStatus !== status"
                :class="activeStatus === status ? 'bg-gradient-red text-white shadow-1' : 'text-blue-grey-6 hover-text-dark'" 
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
          :rows="loading ? skeletonRows : filteredOrders"
          :columns="columns"
          row-key="order_id"
          @row-click="onRowClick"
          card-container-class="q-col-gutter-md q-pa-sm"
          :pagination="{ rowsPerPage: 10 }"
        >
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
              <q-skeleton v-if="loading" type="rect" width="60px" height="22px" style="border-radius: 6px;" />
              <span v-else class="order-id-badge text-weight-bold text-red-8 q-px-sm q-py-xs bg-red-1 transition-ease">#{{ props.row.order_id }}</span>
            </q-td>
          </template>

          <template #body-cell-date="props">
            <q-td :props="props">
              <q-skeleton v-if="loading" type="text" width="110px" height="20px" />
              <span v-else class="text-slate-600">{{ formatDate(props.row.created_at) }}</span>
            </q-td>
          </template>

          <template #body-cell-customer="props">
            <q-td :props="props">
              <div v-if="loading" class="row items-center no-wrap">
                <q-skeleton type="QAvatar" size="32px" class="q-mr-sm" />
                <q-skeleton type="text" width="130px" height="20px" />
              </div>
              <div v-else class="row items-center">
                <q-avatar size="32px" class="q-mr-sm bg-blue-grey-1 shadow-soft border-white">
                  <img v-if="props.row.consumer?.profile_picture_url" :src="props.row.consumer.profile_picture_url">
                  <q-icon v-else name="person" color="blue-grey-6" size="22px" />
                </q-avatar>
                <div class="text-weight-bold text-slate-800">{{ props.row.consumer?.full_name || 'Unknown' }}</div>
              </div>
            </q-td>
          </template>

          <!-- Non-bold Desktop Price -->
          <template #body-cell-price="props">
            <q-td :props="props">
              <q-skeleton v-if="loading" type="text" width="80px" height="20px" />
              <span v-else class="text-slate-800 price-regular">₱{{ formatNumber(props.row.total_amount) }}</span>
            </q-td>
          </template>
          
          <!-- Box with Rounded Corners Status Chip Desktop -->
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-skeleton v-if="loading" type="rect" width="102px" height="28px" style="border-radius: 7px;" />
              <q-chip 
                v-else
                dense
                square
                :color="getStatusColor(props.row.status)" 
                text-color="white" 
                class="status-box-chip q-px-md"
              >
                {{ formatStatus(props.row.status) }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <q-skeleton v-if="loading" type="QBtn" size="sm" class="float-right" />
              <q-btn v-else flat round dense icon="chevron_right" color="blue-grey-4" class="hover-action-btn transition-ease" @click.stop="goToOrder(props.row.order_id)" />
            </q-td>
          </template>

          <!-- ================= MOBILE GRID CARD LAYOUT ================= -->
          <template #item="props">
            <div class="col-12 col-sm-6">
              <!-- Mobile Skeleton Card -->
              <q-card v-if="loading" class="mobile-grid-card q-pa-md shadow-soft" bordered>
                <div class="row justify-between items-center q-mb-sm">
                  <q-skeleton type="rect" width="75px" height="24px" style="border-radius: 6px;" />
                  <q-skeleton type="rect" width="95px" height="26px" style="border-radius: 7px;" />
                </div>
                <q-separator class="q-my-sm" color="grey-2" />
                <div class="row items-center q-mb-md">
                  <q-skeleton type="QAvatar" size="44px" class="q-mr-md" />
                  <div class="col">
                    <q-skeleton type="text" width="120px" height="20px" />
                    <q-skeleton type="text" width="80px" height="16px" class="q-mt-xs" />
                  </div>
                </div>
                <div class="row justify-between items-end">
                  <div>
                    <q-skeleton type="text" width="70px" height="14px" />
                    <q-skeleton type="text" width="90px" height="22px" class="q-mt-xs" />
                  </div>
                  <q-skeleton type="QBtn" size="sm" />
                </div>
              </q-card>

              <!-- Mobile Loaded Card -->
              <q-card v-else class="mobile-grid-card q-pa-md transition-ease shadow-soft" bordered @click="onRowClick($event, props.row)">
                <div class="row justify-between items-center q-mb-sm">
                  <span class="order-id-badge text-weight-bold text-red-8 q-px-sm bg-red-1" style="font-size: 13px; padding-top: 4px; padding-bottom: 4px;">Order #{{ props.row.order_id }}</span>
                  
                  <q-chip 
                    dense
                    square
                    :color="getStatusColor(props.row.status)" 
                    text-color="white" 
                    class="status-box-chip q-ma-none" 
                    style="font-size: 12px; height: 26px; padding: 0 12px;"
                  >
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
                    <div class="text-weight-medium text-blue-grey-4" style="text-transform: uppercase; font-size: 11px;">Total Amount</div>
                    <div class="text-slate-800 text-subtitle1 price-regular" style="line-height: 1.1;">₱{{ formatNumber(props.row.total_amount) }}</div>
                  </div>
                  <q-btn flat round dense icon="chevron_right" color="blue-grey-3" />
                </div>
              </q-card>
            </div>
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

const skeletonRows = Array.from({ length: 6 }, (_, index) => ({
  order_id: `skeleton-${index}`
}))

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
      (order.consumer?.full_name || '').toLowerCase().includes(search.value.toLowerCase())
      
    const matchesStatus = activeStatus.value === 'All' || 
      formatStatus(order.status).toLowerCase() === activeStatus.value.toLowerCase()

    return matchesSearch && matchesStatus
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
  if (loading.value) return
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
    setTimeout(() => window.URL.revokeObjectURL(url), 1000)
  } catch (error) {
    console.error('Export failed:', error)
  } finally {
    isExporting.value = false
  }
}

onMounted(async () => {
  try {
    const res = await api.get('/vendor/orders')
    if (res.data) {
      orders.value = res.data.data || res.data
    }
  } catch (error) {
    console.error('Failed to load orders', error)
  } finally {
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

/* Non-bold Typography Helper */
.price-regular {
  font-weight: 500 !important;
}

/* Background Glows */
.bg-glow { position: absolute; width: 500px; height: 500px; border-radius: 50%; filter: blur(140px); z-index: 0; opacity: 0.15; pointer-events: none; }
.bg-glow-primary { top: -50px; left: -50px; background: radial-gradient(circle, rgba(185, 28, 28, 0.25) 0%, transparent 70%); }
.bg-glow-secondary { bottom: 100px; right: -50px; background: radial-gradient(circle, rgba(69, 10, 10, 0.25) 0%, transparent 70%); }

.tracking-tight { letter-spacing: -0.02em; }
.shrink-none { flex-shrink: 0; }

/* Icon Box */
.glass-icon-box {
  background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(8px);
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

/* Mobile Export Button with Label */
.btn-mobile-export {
  height: 40px !important;
  border-radius: 8px !important;
  border: 1px solid #fecdd3 !important;
  background-color: #fff1f2 !important;
  color: #b91c1c !important;
  font-weight: 700;
  font-size: 12.5px;
  letter-spacing: -0.01em;
  transition: all 0.2s ease;
}
.btn-mobile-export:active {
  background-color: #fee2e2 !important;
  transform: scale(0.97);
}

/* Utilities */
.border-bottom { border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.bg-slate-50 { background-color: #f8fafc; }
.border-slate-light { border: 1px solid #e2e8f0; }
.transition-ease { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.hover-text-dark:hover { color: #1e293b !important; }
.filter-pill { border-radius: 6px; }

/* Custom Premium Table Styling */
:deep(.custom-premium-table thead tr th) { background: rgba(248, 250, 252, 0.7); font-weight: 700; color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
:deep(.custom-premium-table tbody td) { padding: 16px 20px; border-bottom: 1px solid rgba(241, 245, 249, 1); cursor: pointer; transition: all 0.2s ease; }
:deep(.custom-premium-table tbody tr:hover) { background: #ffffff; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); transform: scale(1.002); z-index: 5; position: relative; }
:deep(.custom-premium-table tbody tr:hover td) { border-bottom-color: transparent; }
:deep(.custom-premium-table tbody tr:hover .order-id-badge) { background: rgba(185, 28, 28, 0.1) !important; color: #b91c1c !important; border-color: rgba(185, 28, 28, 0.4) !important; }

.order-id-badge { font-family: monospace; font-size: 13px; border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 6px; }

/* Soft Box Status Chip */
.status-box-chip {
  border-radius: 7px !important;
  font-size: 12.5px !important;
  font-weight: 700 !important;
  min-height: 28px;
  height: 28px;
  border: none !important;
  box-shadow: none !important;
  letter-spacing: -0.01em;
}

.border-white { border: 2px solid #ffffff; }

/* Mobile Grid Card Styling */
.mobile-grid-card {
  background: #ffffff;
  border: 1px solid #f1f5f9;
  border-radius: 16px;
  cursor: pointer;
}
.mobile-grid-card:active { transform: scale(0.98); background: #f8fafc; }

/* Clean horizontal scrolling for mobile filters */
.scroll-container-clean {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  width: 100%;
  padding-bottom: 2px;
}
.scroll-container-clean::-webkit-scrollbar { display: none; }

/* Mobile overrides */
@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { padding: 16px 12px 32px 12px !important; }
  .desktop-only { display: none !important; }
  .mobile-only { display: block !important; }
  .full-width-mobile { width: 100%; }
}
</style>