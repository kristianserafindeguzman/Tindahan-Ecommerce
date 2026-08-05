<template>
  <q-page class="vendor-page relative-position">
    <!-- Subtle Ambient Background Glows (Strict Red, No Pink) -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-xl q-mt-sm row items-center justify-between">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md">
            <q-icon name="point_of_sale" size="26px" color="red-8" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">Sales Management</h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none">Monitor metrics, process manual entries, and view predictions.</p>
          </div>
        </div>

        <!-- Improved Calendar Picker -->
        <div class="q-mt-md q-mt-sm-none">
          <q-btn outline icon="calendar_today" color="dark" :label="displayDate" no-caps class="btn-glass-outline text-weight-bold q-px-lg">
            <q-popup-proxy cover transition-show="scale" transition-hide="scale">
              <q-date v-model="selectedDate" mask="YYYY/MM/DD" color="red-8" today-btn class="premium-glass-card">
                <div class="row items-center justify-end">
                  <q-btn v-close-popup label="Close" color="red-8" flat no-caps class="text-weight-bold" />
                </div>
              </q-date>
            </q-popup-proxy>
          </q-btn>
        </div>
      </div>

      <div class="row q-col-gutter-xl">
        
        <!-- ================= LEFT COLUMN (Metrics & Table) ================= -->
        <div class="col-12 col-md-8">
          
          <!-- Today's Revenue -->
          <q-card class="premium-glass-card q-mb-lg q-pa-md bg-gradient-red text-white">
            <q-card-section>
              <div class="text-subtitle2 text-white opacity-80 text-uppercase q-mb-sm">Today's Revenue</div>
              <div class="row items-center justify-between">
                <div class="text-h2 text-weight-bold">₱{{ formatNumber(metrics.revenue) }}</div>
                
                <!-- Dynamic Growth Rate (Hidden if no data) -->
                <div v-if="metrics.growthRate" class="row items-center text-green-3 text-weight-bold">
                  <q-icon name="trending_up" size="24px" class="q-mr-xs" />
                  +{{ metrics.growthRate }}% vs Yesterday
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- Bottom Metrics Grid -->
          <div class="row q-col-gutter-md q-mb-lg items-stretch">
            
            <!-- Avg Order Value -->
            <div class="col-12 col-sm-4">
              <q-card class="premium-glass-card h-full">
                <q-card-section class="column justify-between h-full">
                  <div class="row items-center justify-between q-mb-md">
                    <div class="text-subtitle2 text-grey-7 text-uppercase">Avg Order Value</div>
                    <q-avatar size="32px" color="grey-2" text-color="blue-grey-8" icon="receipt_long" />
                  </div>
                  <div class="text-h5 text-weight-bold text-dark">₱{{ formatNumber(metrics.avgOrderValue) }}</div>
                </q-card-section>
              </q-card>
            </div>
            
            <!-- Cancellation Rate -->
            <div class="col-12 col-sm-4">
              <q-card class="premium-glass-card h-full">
                <q-card-section class="column justify-between h-full">
                  <div class="row items-center justify-between q-mb-md">
                    <div class="text-subtitle2 text-grey-7 text-uppercase">Cancellation Rate</div>
                    <q-avatar size="32px" color="grey-2" text-color="red-8" icon="remove_shopping_cart" />
                  </div>
                  <div class="text-h5 text-weight-bold text-dark">{{ metrics.cancellationRate }}%</div>
                </q-card-section>
              </q-card>
            </div>

            <!-- ML Blueprint Card -->
            <div class="col-12 col-sm-4">
              <q-card class="premium-glass-card ml-blueprint-card bg-gradient-dark text-white h-full relative-position overflow-hidden">
                <div class="glow-amber"></div>
                <q-card-section class="relative-position z-top column justify-between h-full">
                  <div class="row items-center q-mb-md">
                    <q-icon name="auto_awesome" size="18px" color="amber-4" class="q-mr-sm" />
                    <div class="text-subtitle2 text-amber-2 text-uppercase" style="font-size: 11px;">Predicted Best Seller</div>
                  </div>
                  <!-- RANDOM FOREST ML INTEGRATION -->
                  <div class="text-h6 text-weight-bold text-white leading-tight">
                    {{ metrics.bestSellingCategory || 'Analyzing...' }}
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <!-- Transactions Table -->
          <q-card class="premium-glass-card">
            <q-card-section class="panel-header q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Recent Sales
              </div>
            </q-card-section>

            <q-table
              flat
              class="custom-premium-table bg-transparent"
              :rows="transactions"
              :columns="columns"
              row-key="order_id"
              hide-bottom
              :pagination="{ rowsPerPage: 5 }"
            >
              <!-- Explicit Empty State -->
              <template #no-data>
                <div class="full-width row flex-center q-pa-xl empty-state-glass">
                  <div class="text-center z-top relative-position">
                    <div class="empty-icon-wrapper q-mb-lg">
                      <q-icon name="receipt_long" size="56px" color="blue-grey-3" class="drop-shadow-icon" />
                    </div>
                    <div class="text-h6 text-weight-bold text-blue-grey-8">No recent sales recorded</div>
                    <div class="text-body2 text-blue-grey-5 q-mt-xs">Sales recorded today will appear here.</div>
                  </div>
                </div>
              </template>

              <!-- Status Formatter -->
              <template #body-cell-status="props">
                <q-td :props="props">
                  <q-chip 
                    size="sm" 
                    :color="getStatusColor(props.row.status).bg" 
                    :text-color="getStatusColor(props.row.status).text" 
                    class="text-weight-bold shadow-1"
                  >
                    {{ props.row.status }}
                  </q-chip>
                </q-td>
              </template>

              <!-- Total Formatter -->
              <template #body-cell-total="props">
                <q-td :props="props" class="text-weight-bold text-blue-grey-9">
                  ₱{{ formatNumber(props.row.total) }}
                </q-td>
              </template>
            </q-table>
          </q-card>
        </div>

        <!-- ================= RIGHT COLUMN (Manual Entry) ================= -->
        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card q-pa-sm manual-entry-card relative-position">
            
            <q-card-section class="q-pb-none q-pt-lg">
              <div class="row items-center q-mb-md">
                <div class="icon-premium-box bg-grey-2 border-grey-light text-red-8 q-mr-md">
                  <q-icon name="add_shopping_cart" size="24px" />
                </div>
                <div class="text-h6 text-weight-bold text-dark leading-tight">Manual Entry</div>
              </div>
            </q-card-section>

            <q-card-section class="q-pt-sm">
              <q-form @submit.prevent="confirmManualSale" class="q-gutter-y-lg">
                
                <div class="field-group">
                  <label class="input-label">Product Name <span class="text-red-8">*</span></label>
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
                    hide-bottom-space
                  >
                    <template v-slot:no-option>
                      <q-item>
                        <q-item-section class="text-italic text-grey-6">
                          No products found
                        </q-item-section>
                      </q-item>
                    </template>
                  </q-select>
                </div>

                <div class="row q-col-gutter-md">
                  <div class="col-6 field-group">
                    <label class="input-label">Quantity</label>
                    <q-input 
                      v-model.number="manualForm.quantity" 
                      type="number" 
                      outlined 
                      dense 
                      class="custom-glass-input" 
                      :rules="[val => val > 0 || 'Must be > 0']"
                      hide-bottom-space
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
                      hide-bottom-space
                    />
                  </div>
                </div>

                <!-- Reverted Estimated Total Display -->
                <div class="bg-grey-2 rounded-borders q-pa-md q-mt-md shadow-1">
                  <div class="row items-center justify-between">
                    <div class="text-subtitle2 text-grey-8">Estimated Total</div>
                    <div class="text-h6 text-weight-bold text-red-8">₱{{ formatNumber(estimatedTotal) }}</div>
                  </div>
                </div>

                <!-- Reverted Record Sale Button -->
                <div class="q-mt-sm">
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
import { ref, reactive, computed, onMounted } from 'vue'
import { useQuasar, date } from 'quasar'
import { api } from '@/boot/axios'

const $q = useQuasar()

// Set default date to today, formatted properly for QDate mask
const timeStamp = Date.now()
const selectedDate = ref(date.formatDate(timeStamp, 'YYYY/MM/DD'))

const displayDate = computed(() => {
  if (!selectedDate.value) return 'Select Date'
  const d = new Date(selectedDate.value.replace(/\//g, '-'))
  return date.formatDate(d, 'MMM DD, YYYY')
})

const metrics = reactive({
  revenue: 0,
  growthRate: null, // Tied to real data, hidden otherwise
  avgOrderValue: 0,
  cancellationRate: 0,
  bestSellingCategory: null
})
const transactions = ref([])
const submitting = ref(false)

const columns = [
  { name: 'order_id', label: 'Order ID', field: 'order_id', align: 'left', sortable: true },
  { name: 'product', label: 'Product', field: 'product', align: 'left' },
  { name: 'quantity', label: 'Items', field: 'quantity', align: 'left', sortable: true },
  { name: 'total', label: 'Total (₱)', field: 'total', align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'left' }
]

// Enhanced Status Colors returning background and text colors
const getStatusColor = (status) => {
  switch (String(status).toLowerCase()) {
    case 'completed': return { bg: 'green-6', text: 'white' }
    case 'cancelled': return { bg: 'red-8', text: 'white' }
    case 'pending': return { bg: 'orange-8', text: 'white' }
    default: return { bg: 'grey-6', text: 'white' }
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
    class: 'premium-glass-card',
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
        total_amount: estimatedTotal.value
      })
      $q.notify({ type: 'positive', message: 'Manual sale recorded successfully.', position: 'top-right' })
      manualForm.product = null
      manualForm.quantity = 1
      manualForm.unitPrice = 0
      
      await fetchSalesData()
    } catch (error) {
      $q.notify({ type: 'negative', message: 'Failed to record manual sale.', position: 'top-right' })
    } finally {
      submitting.value = false
    }
  })
}

const fetchSalesData = async () => {
  try {
    const [metricsRes, transRes, invRes] = await Promise.all([
      api.get('/vendor/sales/metrics'),
      api.get('/vendor/sales/transactions'),
      api.get('/vendor/products')
    ])
    
    if (metricsRes.data) {
      metrics.revenue = metricsRes.data.revenue || 0
      metrics.growthRate = metricsRes.data.growth_rate || null // Maps to backend property if it exists
      metrics.avgOrderValue = metricsRes.data.avg_order_value || 0
      metrics.cancellationRate = metricsRes.data.cancellation_rate || 0
      metrics.bestSellingCategory = metricsRes.data.best_selling_category || null
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
</script>

<style scoped>
/* Core Page Styling */
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1300px;
  margin: 0 auto;
}

/* Subtle Ambient Glows - STRICT RED, NO PINK */
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
  background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: 100px;
  right: -50px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.3) 0%, transparent 70%); 
}

/* Typography Utilities */
.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.opacity-80 { opacity: 0.8; }
.h-full { height: 100%; }

/* Beautiful Header Glass Icon Box */
.glass-icon-box {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.08);
}

/* Clean Glassmorphism Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

/* Gradients */
.bg-gradient-red {
  background: linear-gradient(135deg, #B91C1C 0%, #7F1D1D 100%);
  border: none;
  box-shadow: 0 15px 35px rgba(185, 28, 28, 0.2);
}
.bg-gradient-dark {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border: 1px solid #334155;
  box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}

/* Panel Header */
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

/* ML Card Specifics */
.glow-amber {
  position: absolute;
  top: -20px;
  right: -20px;
  width: 100px;
  height: 100px;
  background: radial-gradient(circle, rgba(251, 191, 36, 0.2) 0%, transparent 70%);
  border-radius: 50%;
  filter: blur(20px);
}

/* Inputs & Form Elements */
.input-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 6px;
}
.field-group { margin-bottom: 8px; }
.custom-glass-input :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.6); 
  border-radius: 8px;
  transition: all 0.3s ease;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.8); }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 2px 10px rgba(185, 28, 28, 0.06); 
}

/* Buttons */
.btn-glass-outline {
  border-radius: 8px !important;
  background: rgba(255, 255, 255, 0.8) !important;
  border: 1px solid rgba(203, 213, 225, 0.8);
  transition: all 0.2s ease;
}
.btn-glass-outline:hover {
  background: #ffffff !important;
  border-color: #e2e8f0;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
  transform: translateY(-1px);
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

/* Utilities */
.border-grey-light { border: 1px solid rgba(226, 232, 240, 0.8); }
.icon-premium-box {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Custom Premium Table Styling */
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7);
  backdrop-filter: blur(8px);
  font-weight: 700;
  color: #64748B; 
  text-transform: uppercase; 
  font-size: 11px; 
  letter-spacing: 0.05em; 
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(226, 232, 240, 0.8); 
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(226, 232, 240, 0.5); 
}

/* Empty State Styling */
.empty-state-glass {
  background: rgba(248, 250, 252, 0.5);
  border: 1px dashed #E2E8F0;
  border-radius: 12px;
  margin: 16px;
  width: calc(100% - 32px);
}
.empty-icon-wrapper {
  position: relative;
  display: inline-flex;
  justify-content: center;
  align-items: center;
}
.drop-shadow-icon { filter: drop-shadow(0 4px 6px rgba(15, 23, 42, 0.05)); opacity: 0.5; }
</style>