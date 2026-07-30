<template>
  <q-page class="vendor-page">
    <div class="page-container">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-lg">
        <h1 class="text-h4 text-weight-bold q-ma-none">Inventory Management</h1>
        <p class="text-subtitle1 text-grey-7 q-mt-sm q-mb-none">Monitor and update your product catalog.</p>
      </div>

      <!-- ================= ML INSIGHTS (TOP ROW) ================= -->
      <div class="row q-col-gutter-md q-mb-xl">
        <!-- RANDOM FOREST ML INTEGRATION BLUEPRINT: These three cards will display predictive inventory insights powered by the Python/Flask ML microservice in the final sprint. Ensure the reactive props here are ready to receive external ML JSON data (e.g., predicted out-of-stock dates, seasonal trend multipliers). -->
        
        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card h-full">
            <q-card-section>
              <div class="row items-center q-mb-sm">
                <div class="icon-premium-box bg-red-50 border-red-light text-red-7 q-mr-sm" style="width:36px; height:36px;">
                  <q-icon name="warning" size="20px" />
                </div>
                <div class="text-subtitle2 text-grey-7 text-uppercase">Restock Alert</div>
              </div>
              <div class="text-h5 text-weight-bold text-dark q-mb-xs">{{ mlInsights.restockProduct || 'Analyzing...' }}</div>
              <div class="text-caption text-red-7 text-weight-bold">Predicted stock-out in {{ mlInsights.daysUntilStockout || 'N/A' }} days</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card h-full">
            <q-card-section>
              <div class="row items-center q-mb-sm">
                <div class="icon-premium-box bg-blue-50 border-blue-light text-blue-7 q-mr-sm" style="width:36px; height:36px;">
                  <q-icon name="trending_up" size="20px" />
                </div>
                <div class="text-subtitle2 text-grey-7 text-uppercase">Upcoming Trend</div>
              </div>
              <div class="text-h5 text-weight-bold text-dark q-mb-xs">{{ mlInsights.trendingCategory || 'Analyzing...' }}</div>
              <div class="text-caption text-blue-7 text-weight-bold">Expected {{ mlInsights.trendMultiplier || '0' }}x demand increase</div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card h-full">
            <q-card-section>
              <div class="row items-center q-mb-sm">
                <div class="icon-premium-box bg-amber-50 border-amber-light text-amber-7 q-mr-sm" style="width:36px; height:36px;">
                  <q-icon name="emoji_events" size="20px" />
                </div>
                <div class="text-subtitle2 text-grey-7 text-uppercase">Top Performance</div>
              </div>
              <div class="text-h5 text-weight-bold text-dark q-mb-xs">{{ mlInsights.topCategory || 'Analyzing...' }}</div>
              <div class="text-caption text-amber-8 text-weight-bold">Highest revenue driver this week</div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card">
        <q-card-section class="q-pa-md border-bottom row items-center justify-between">
          
          <div class="row q-gutter-sm items-center">
            <q-input v-model="search" outlined dense class="custom-glass-input" placeholder="Search products..." style="width: 300px;">
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
            <q-btn outline icon="filter_list" label="Filter" color="dark" no-caps class="btn-3d-outline" />
          </div>

          <div class="row q-gutter-sm">
            <q-btn outline icon="download" label="Export" color="dark" no-caps class="btn-3d-outline" />
            <q-btn unelevated icon="add" label="Add Product" color="red-8" no-caps class="btn-premium text-white" />
          </div>
        </q-card-section>

        <!-- Table -->
        <q-table
          flat
          class="custom-premium-table"
          :rows="filteredProducts"
          :columns="columns"
          row-key="inventory_id"
          :loading="loading"
        >
          <template #no-data>
            <div class="full-width row flex-center text-grey-6 q-pa-xl empty-state-glass">
              <div class="text-center">
                <q-icon name="inventory_2" size="48px" class="q-mb-md opacity-50" />
                <div class="text-subtitle1 text-weight-medium">No products found</div>
                <div class="text-caption">Your inventory is currently empty.</div>
              </div>
            </div>
          </template>

          <template #body-cell-image="props">
            <q-td :props="props">
              <q-avatar size="40px" square class="bg-grey-2 border-radius-8">
                <img v-if="props.row.image_url" :src="props.row.image_url" />
                <q-icon v-else name="image" color="grey-5" size="20px" />
              </q-avatar>
            </q-td>
          </template>
          
          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip size="sm" :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bold shadow-1">
                {{ props.row.status || 'Active' }}
              </q-chip>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <q-btn-dropdown flat round dense icon="more_vert" color="grey-7">
                <q-list style="min-width: 150px">
                  <q-item clickable v-close-popup>
                    <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="visibility" size="20px" color="blue-6" /></q-item-section>
                    <q-item-section>View</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup>
                    <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="edit" size="20px" color="amber-7" /></q-item-section>
                    <q-item-section>Edit</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup>
                    <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="block" size="20px" color="grey-6" /></q-item-section>
                    <q-item-section>Deactivate</q-item-section>
                  </q-item>
                  <q-item clickable v-close-popup>
                    <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="delete" size="20px" color="red-6" /></q-item-section>
                    <q-item-section class="text-red">Delete</q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </q-td>
          </template>
        </q-table>
      </q-card>

    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/boot/axios'

const search = ref('')
const loading = ref(true)
const products = ref([])

const mlInsights = ref({
  restockProduct: null,
  daysUntilStockout: null,
  trendingCategory: null,
  trendMultiplier: null,
  topCategory: null
})

const columns = [
  { name: 'image', label: 'Image', field: 'image', align: 'left' },
  { name: 'product_name', label: 'Name', field: 'product_name', align: 'left', sortable: true },
  { name: 'category', label: 'Category', field: row => row.category?.category_name || 'Uncategorized', align: 'left', sortable: true },
  { name: 'quantity', label: 'QTY', field: 'stock_quantity', align: 'left', sortable: true },
  { name: 'price', label: 'Price (₱)', field: row => formatNumber(row.price), align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

const filteredProducts = computed(() => {
  if (!search.value) return products.value
  const needle = search.value.toLowerCase()
  return products.value.filter(p => p.product_name.toLowerCase().includes(needle))
})

const getStatusColor = (status) => {
  switch (String(status || 'active').toLowerCase()) {
    case 'active': return 'green-6'
    case 'inactive': return 'grey-6'
    case 'out of stock': return 'red-6'
    default: return 'grey-6'
  }
}

const formatNumber = (num) => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const fetchProducts = async () => {
  try {
    const res = await api.get('/vendor/products')
    products.value = res.data || []
  } catch (error) {
    console.error('Failed to load products', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProducts()
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
.icon-premium-box {
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bg-red-50 { background-color: #FEF2F2; } .border-red-light { border: 1px solid #FEE2E2; }
.bg-blue-50 { background-color: #EFF6FF; } .border-blue-light { border: 1px solid #DBEAFE; }
.bg-amber-50 { background-color: #FFFBEB; } .border-amber-light { border: 1px solid #FEF3C7; }

.btn-premium {
  border-radius: 8px !important;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.3);
  transition: all 0.2s ease;
}
.btn-premium:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(185, 28, 28, 0.4);
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
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7); backdrop-filter: blur(8px); font-weight: 700;
  color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.5);
}
.empty-state-glass {
  background: rgba(248, 250, 252, 0.5);
  border: 1px dashed #E2E8F0;
  border-radius: 12px;
}
</style>
