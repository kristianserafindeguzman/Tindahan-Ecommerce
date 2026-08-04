<template>
  <q-page class="vendor-page">
    <div class="page-container">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-xl row items-center justify-between">
        <div>
          <h1 class="text-h4 text-weight-bold q-ma-none">Sales Management</h1>
          <p class="text-subtitle1 text-grey-7 q-mt-sm q-mb-none">Monitor and record retail transactions in real-time.</p>
        </div>
        <div>
          <q-btn outline icon="calendar_today" color="dark" :label="selectedDate || 'Select Date'" no-caps class="btn-3d-outline q-px-md">
            <q-popup-proxy cover transition-show="scale" transition-hide="scale">
              <q-date v-model="selectedDate" mask="YYYY-MM-DD" color="red-8" today-btn>
                <div class="row items-center justify-end q-mt-sm">
                  <q-btn label="Cancel" color="primary" flat v-close-popup />
                  <q-btn label="Okay" color="primary" flat @click="fetchSalesData" v-close-popup />
                </div>
              </q-date>
            </q-popup-proxy>
          </q-btn>
        </div>
      </div>

      <div class="row q-col-gutter-lg">
        
        <!-- ================= LEFT COLUMN ================= -->
        <div class="col-12 col-md-8">
          
          <!-- Today's Revenue -->
          <q-card class="premium-glass-card q-mb-lg q-pa-md bg-gradient-red text-white">
            <q-card-section>
              <div class="text-subtitle2 text-white opacity-80 text-uppercase q-mb-sm">REVENUE FOR {{ formattedSelectedDate.toUpperCase() }}</div>
              <div class="row items-center justify-between">
                <div class="text-h2 text-weight-bold">₱{{ formatNumber(metrics.revenue) }}</div>
                <div v-if="metrics.revenueGrowth !== null" class="row items-center text-weight-bold" :class="metrics.revenueGrowth >= 0 ? 'text-green-3' : 'text-red-3'">
                  <q-icon :name="metrics.revenueGrowth >= 0 ? 'trending_up' : 'trending_down'" size="24px" class="q-mr-xs" />
                  {{ metrics.revenueGrowth > 0 ? '+' : '' }}{{ metrics.revenueGrowth }}% vs Yesterday
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- Bottom Metrics Grid -->
          <div class="row q-col-gutter-md q-mb-lg">
            <div class="col-12 col-sm-4">
              <q-card class="premium-glass-card">
                <q-card-section>
                  <div class="text-subtitle2 text-grey-7 text-uppercase q-mb-sm">Avg Order Value</div>
                  <div class="text-h5 text-weight-bold text-dark">₱{{ formatNumber(metrics.avgOrderValue) }}</div>
                </q-card-section>
              </q-card>
            </div>
            
            <div class="col-12 col-sm-4">
              <q-card class="premium-glass-card">
                <q-card-section>
                  <div class="text-subtitle2 text-grey-7 text-uppercase q-mb-sm">Cancellation Rate</div>
                  <div class="text-h5 text-weight-bold text-dark">{{ metrics.cancellationRate }}%</div>
                </q-card-section>
              </q-card>
            </div>

            <!-- ML Blueprint Card -->
            <div class="col-12 col-sm-4">
              <q-card class="premium-glass-card ml-blueprint-card bg-gradient-dark text-white h-full">
                <q-card-section>
                  <div class="row items-center q-mb-sm">
                    <q-icon name="auto_awesome" size="18px" color="amber-4" class="q-mr-sm" />
                    <div class="text-subtitle2 text-amber-2 text-uppercase" style="font-size: 11px;">BEST SELLER FOR {{ formattedSelectedDate.toUpperCase() }}</div>
                  </div>
                  <!-- ML INTEGRATION BLUEPRINT: This card displays the best-selling category for the selected date. -->
                  <div class="text-h6 text-weight-bold text-white">{{ metrics.bestSellingCategory || 'No Data' }}</div>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <!-- Transactions Table -->
          <q-card class="premium-glass-card">
            <q-card-section class="panel-header q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Sales for {{ formattedSelectedDate }}
              </div>
            </q-card-section>

            <q-table
              flat
              class="custom-premium-table"
              :rows="transactions"
              :columns="columns"
              row-key="order_id"
              hide-bottom
              :pagination="{ rowsPerPage: 5 }"
            >
              <template #no-data>
                <div class="full-width row flex-center text-grey-6 q-pa-xl empty-state-glass">
                  <div class="text-center">
                    <q-icon name="receipt_long" size="48px" class="q-mb-md opacity-50" />
                    <div class="text-subtitle1 text-weight-medium">No recent transactions found</div>
                    <div class="text-caption">Sales recorded today will appear here.</div>
                  </div>
                </div>
              </template>

              <template #body-cell-status="props">
                <q-td :props="props">
                  <q-chip size="sm" :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bold shadow-1">
                    {{ props.row.status }}
                  </q-chip>
                </q-td>
              </template>
            </q-table>
          </q-card>
        </div>

        <!-- ================= RIGHT COLUMN ================= -->
        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card border-red-light q-pa-md">
            <q-card-section>
              <div class="row items-center q-mb-lg">
                <div class="icon-premium-box bg-red-50 border-red-light text-red-7 q-mr-md">
                  <q-icon name="add_shopping_cart" size="24px" />
                </div>
                <div class="text-h6 text-weight-bold text-dark">Manual Entry</div>
              </div>

              <q-form @submit.prevent="confirmManualSale" class="q-gutter-md">
                
                <div class="field-group">
                  <label class="input-label">Product Name</label>
                  <q-select
                    v-model="manualForm.product"
                    :options="inventoryOptions"
                    option-value="inventory_id"
                    option-label="product_name"
                    use-input
                    input-debounce="0"
                    @filter="filterInventory"
                    @update:model-value="onProductSelected"
                    outlined
                    dense
                    class="custom-glass-input"
                    placeholder="Search product..."
                    :rules="[val => !!val || 'Product is required']"
                  >
                    <template v-slot:no-option>
                      <q-item>
                        <q-item-section class="text-italic text-grey">
                          No products found
                        </q-item-section>
                      </q-item>
                    </template>
                  </q-select>
                </div>

                <div class="row q-col-gutter-sm">
                  <div class="col-6 field-group">
                    <label class="input-label">Quantity</label>
                    <q-input 
                      v-model.number="manualForm.quantity" 
                      type="number" 
                      outlined 
                      dense 
                      class="custom-glass-input" 
                      :rules="[val => val > 0 || 'Must be > 0']" 
                    />
                  </div>
                  <div class="col-6 field-group">
                    <label class="input-label">Unit Price (₱)</label>
                    <q-input 
                      v-model.number="manualForm.unitPrice" 
                      type="number" 
                      outlined 
                      dense 
                      class="custom-glass-input" 
                      :rules="[val => val >= 0 || 'Invalid price']" 
                    />
                  </div>
                </div>

                <div class="bg-grey-2 rounded-borders q-pa-md q-mt-md shadow-1">
                  <div class="row items-center justify-between">
                    <div class="text-subtitle2 text-grey-8">Estimated Total</div>
                    <div class="text-h6 text-weight-bold text-red-8">₱{{ formatNumber(estimatedTotal) }}</div>
                  </div>
                </div>

                <div class="q-mt-xl">
                  <q-btn type="submit" label="Record Sale" unelevated class="btn-premium text-white full-width bg-red-8" size="lg" no-caps :loading="submitting" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useQuasar, date } from 'quasar'
import { api } from '@/boot/axios'

const $q = useQuasar()

const selectedDate = ref(date.formatDate(Date.now(), 'YYYY-MM-DD'))

const formattedSelectedDate = computed(() => {
  if (!selectedDate.value) return 'All Time'
  
  const d = new Date(selectedDate.value)
  const today = new Date()
  
  if (d.toDateString() === today.toDateString()) {
    return 'Today'
  }
  
  return date.formatDate(d, 'MMMM D, YYYY')
})
const metrics = reactive({
  revenue: 0,
  avgOrderValue: 0,
  cancellationRate: 0,
  bestSellingCategory: null,
  revenueGrowth: null
})
const transactions = ref([])
const submitting = ref(false)

const columns = [
  { name: 'order_id', label: 'Order ID', field: 'order_id', align: 'left', sortable: true },
  { name: 'product', label: 'Product', field: 'product', align: 'left' },
  { name: 'quantity', label: 'Items', field: 'quantity', align: 'left', sortable: true },
  { name: 'total', label: 'Total (₱)', field: row => formatNumber(row.total), align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'left' }
]

const getStatusColor = (status) => {
  switch (String(status).toLowerCase()) {
    case 'completed': return 'green-6'
    case 'cancelled': return 'red-6'
    default: return 'grey-6'
  }
}

const formatNumber = (num) => {
  return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Manual Entry Form
const manualForm = reactive({
  product: null,
  quantity: 1,
  unitPrice: 0
})

const estimatedTotal = computed(() => {
  return (manualForm.quantity || 0) * (manualForm.unitPrice || 0)
})

const inventoryData = ref([])
const inventoryOptions = ref([])

const filterInventory = (val, update) => {
  if (val === '') {
    update(() => {
      inventoryOptions.value = inventoryData.value
    })
    return
  }
  update(() => {
    const needle = val.toLowerCase()
    inventoryOptions.value = inventoryData.value.filter(v => v.product_name.toLowerCase().indexOf(needle) > -1)
  })
}

const onProductSelected = (val) => {
  if (val) {
    manualForm.unitPrice = val.price || 0
  } else {
    manualForm.unitPrice = 0
  }
}

const confirmManualSale = () => {
  $q.dialog({
    title: 'Confirm Manual Sale',
    message: 'Are you sure you want to record this manual sale? This will affect your revenue and inventory counts.',
    cancel: { flat: true, color: 'grey-7', noCaps: true },
    ok: { unelevated: true, color: 'red-8', label: 'Record Sale', noCaps: true },
    persistent: true
  }).onOk(async () => {
    try {
      submitting.value = true
      await api.post('/vendor/sales/manual', {
        inventory_id: manualForm.product.inventory_id,
        quantity: manualForm.quantity,
        unit_price: manualForm.unitPrice,
        total_amount: estimatedTotal.value,
        sale_date: selectedDate.value
      })
      $q.notify({ type: 'positive', message: 'Manual sale recorded successfully.' })
      manualForm.product = null
      manualForm.quantity = 1
      manualForm.unitPrice = 0
      
      // Refresh Data
      await fetchSalesData()
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Failed to record manual sale.' })
    } finally {
      submitting.value = false
    }
  })
}

const fetchSalesData = async () => {
  try {
    const requestParams = selectedDate.value 
      ? { start_date: selectedDate.value, end_date: selectedDate.value } 
      : {};

    const [metricsRes, transRes, invRes] = await Promise.all([
      api.get('/vendor/sales/metrics', { params: requestParams }),
      api.get('/vendor/sales/transactions', { params: requestParams }),
      api.get('/vendor/products')
    ])
    
    console.log("Sales API Response (Metrics):", metricsRes.data);
    console.log("Sales API Response (Transactions):", transRes.data);
    
    if (metricsRes.data) {
      metrics.revenue = metricsRes.data.revenue || 0
      metrics.avgOrderValue = metricsRes.data.avg_order_value || 0
      metrics.cancellationRate = metricsRes.data.cancellation_rate || 0
      metrics.bestSellingCategory = metricsRes.data.best_selling_category || null
      metrics.revenueGrowth = metricsRes.data.revenue_growth !== undefined ? metricsRes.data.revenue_growth : null
    }
    
    transactions.value = transRes.data || []
    inventoryData.value = invRes.data || []
  } catch (error) {
    console.error('Failed to load sales data', error)
  }
}

onMounted(() => {
  fetchSalesData()
})

watch(selectedDate, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    fetchSalesData()
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
.bg-gradient-red {
  background: linear-gradient(135deg, #B91C1C 0%, #7F1D1D 100%);
  border: none;
  box-shadow: 0 15px 35px rgba(185, 28, 28, 0.2);
}
.opacity-80 {
  opacity: 0.8;
}
.h-full {
  height: 100%;
}
.bg-gradient-dark {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border: 1px solid #334155;
  box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}
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
.input-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 6px;
}
.field-group {
  margin-bottom: 8px;
}
.icon-premium-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bg-red-50 { background-color: #FEF2F2; } .border-red-light { border: 1px solid #FEE2E2; }
.panel-header {
  background: linear-gradient(90deg, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0.4) 100%);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
.header-accent-red {
  width: 4px;
  height: 24px;
  background: #B91C1C;
  border-radius: 4px;
  box-shadow: 2px 0 8px rgba(185, 28, 28, 0.3);
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
