<template>
  <q-page class="vendor-page">
    <div class="page-container">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-lg row items-center justify-between">
        <div>
          <h1 class="text-h4 text-weight-bold q-ma-none">Order List</h1>
          <p class="text-subtitle1 text-grey-7 q-mt-sm q-mb-none">Manage and track all customer orders.</p>
        </div>
        <div class="row q-gutter-sm">
          <q-btn outline icon="filter_list" label="Filter" color="dark" no-caps class="btn-3d-outline" />
          <q-btn outline icon="download" label="Export" color="dark" no-caps class="btn-3d-outline" />
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card">
        <q-card-section class="q-pa-md border-bottom row items-center justify-between">
          
          <!-- Search -->
          <div class="col-12 col-sm-4 q-mb-sm-none q-mb-md">
            <q-input v-model="search" outlined dense class="custom-glass-input" placeholder="Search by Order ID or Customer...">
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>

          <!-- Status Filters -->
          <div class="col-12 col-sm-8 text-right">
            <q-btn-group flat class="bg-grey-2 border-radius-8 filter-group">
              <q-btn v-for="status in statuses" :key="status" :label="status" 
                :unelevated="activeStatus === status" 
                :flat="activeStatus !== status"
                :class="activeStatus === status ? 'bg-white text-dark shadow-1 text-weight-bold' : 'text-grey-7'" 
                no-caps size="sm" class="border-radius-8 q-px-md"
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
          <template #no-data>
            <div class="full-width row flex-center text-grey-6 q-pa-xl empty-state-glass">
              <div class="text-center">
                <q-icon name="inbox" size="48px" class="q-mb-md opacity-50" />
                <div class="text-subtitle1 text-weight-medium">No orders found</div>
                <div class="text-caption">Orders matching your filters will appear here.</div>
              </div>
            </div>
          </template>

          <template #body-cell-customer="props">
            <q-td :props="props">
              <div class="row items-center">
                <q-avatar size="32px" class="q-mr-sm">
                  <img :src="props.row.consumer?.profile_picture_url || 'https://cdn.quasar.dev/img/avatar.png'">
                </q-avatar>
                <div class="text-weight-bold">{{ props.row.consumer?.full_name || 'Unknown' }}</div>
              </div>
            </q-td>
          </template>
          
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip size="sm" :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bold shadow-1">
                {{ props.row.status }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <q-btn flat round dense icon="chevron_right" color="grey-7" @click.stop="goToOrder(props.row.order_id)" />
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

const orders = ref([])

const columns = [
  { name: 'order_id', label: 'Order ID', field: 'order_id', align: 'left', sortable: true },
  { name: 'date', label: 'Date', field: row => formatDate(row.created_at), align: 'left', sortable: true },
  { name: 'customer', label: 'Customer', field: 'customer', align: 'left' },
  { name: 'price', label: 'Price (₱)', field: row => formatNumber(row.total_amount), align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

const filteredOrders = computed(() => {
  return orders.value.filter(order => {
    const matchesSearch = search.value === '' || 
      String(order.order_id).includes(search.value) || 
      (order.consumer?.full_name || '').toLowerCase().includes(search.value.toLowerCase());
      
    const matchesStatus = activeStatus.value === 'All' || 
      order.status.toLowerCase() === activeStatus.value.toLowerCase();

    return matchesSearch && matchesStatus;
  })
})

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

const goToOrder = (id) => {
  router.push('/vendor/orders/' + id)
}

const onRowClick = (evt, row) => {
  goToOrder(row.order_id)
}

onMounted(async () => {
  try {
    const res = await api.get('/vendor/orders')
    if (res.data) {
      orders.value = res.data.data || res.data // handle paginated or unpaginated
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
.custom-glass-input :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.6);
  border-radius: 8px;
}
.border-bottom {
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
.border-radius-8 {
  border-radius: 8px;
}
.filter-group {
  display: inline-flex;
  padding: 4px;
}
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7); backdrop-filter: blur(8px); font-weight: 700;
  color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.5); cursor: pointer;
}
:deep(.custom-premium-table tbody tr:hover td) {
  background: rgba(241, 245, 249, 0.4);
}
.empty-state-glass {
  background: rgba(248, 250, 252, 0.5);
  border: 1px dashed #E2E8F0;
  border-radius: 12px;
}
</style>
