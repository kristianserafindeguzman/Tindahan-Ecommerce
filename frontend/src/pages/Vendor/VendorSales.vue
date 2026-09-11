<template>
  <q-page class="vendor-page relative-position" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= DESKTOP HEADER AREA ================= -->
      <div v-if="!$q.screen.lt.md" class="page-header q-mb-xl q-mt-sm">
        <div class="row items-center q-mb-sm">
          <div class="glass-icon-box q-mr-md">
            <q-icon name="point_of_sale" size="26px" color="red-8" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">Sales Management</h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none">Monitor metrics, process manual entries, and view predictions.</p>
          </div>
        </div>
      </div>

      <!-- ================= MOBILE HEADER AREA ================= -->
      <div v-else class="page-header q-mb-lg q-mt-sm">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md" style="width: 44px; height: 44px;">
            <q-icon name="point_of_sale" size="22px" class="text-brand-red" />
          </div>
          <div>
            <h1 class="text-h5 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight leading-tight">Sales Management</h1>
            <p class="text-caption text-blue-grey-5 q-mt-xs q-mb-none font-medium">Monitor metrics and track revenue.</p>
          </div>
        </div>
      </div>

      <div class="row q-col-gutter-lg q-col-gutter-md-xl">
        
        <!-- ================= LEFT COLUMN / MAIN CONTENT ================= -->
        <div class="col-12 col-md-8">
          
          <!-- Revenue Card with Integrated Date Picker -->
          <q-card class="q-mb-lg text-white" :class="$q.screen.lt.md ? 'bg-brand-red q-pa-md shadow-2' : 'premium-glass-card q-pa-md bg-gradient-red'" :style="$q.screen.lt.md ? 'border-radius: 12px;' : ''">
            <q-card-section :class="{ 'q-pa-sm': $q.screen.lt.md }">
              <div class="row items-center justify-between q-mb-sm">
                <div class="text-white opacity-80 text-uppercase text-weight-bold" :style="$q.screen.lt.md ? 'font-size: 11px; letter-spacing: 0.5px;' : ''">
                  REVENUE FOR {{ displayDate.toUpperCase() }}
                </div>
                
                <!-- Calendar Button -->
                <q-btn 
                  outline 
                  dense 
                  no-caps 
                  icon="calendar_month" 
                  label="Select Date" 
                  class="text-white text-weight-bold transition-ease hover-bg-white-20" 
                  style="border-radius: 8px; border-color: rgba(255,255,255,0.4); background: rgba(255,255,255,0.1); padding: 4px 12px; font-size: 11.5px; letter-spacing: 0.3px;"
                >
                  <q-popup-proxy anchor="bottom right" self="top right" :offset="[0, 8]" transition-show="jump-down" transition-hide="jump-up">
                    <q-card class="calendar-popover-card shadow-soft overflow-hidden" style="width: 320px; max-width: 90vw; border-radius: 14px; border: 1px solid #e2e8f0;">
                      <div class="calendar-popover-header row items-center justify-between q-px-md q-py-sm bg-gradient-red text-white">
                        <div class="row items-center no-wrap">
                          <q-icon name="event" size="18px" class="q-mr-xs" />
                          <span class="text-caption text-weight-bolder text-uppercase tracking-wide">Select Date</span>
                        </div>
                        <span class="text-caption text-weight-bold opacity-80">{{ selectedDate ? selectedDate.replace(/\//g, '-') : 'All Time' }}</span>
                      </div>
                      
                      <q-date v-model="selectedDate" mask="YYYY/MM/DD" color="red-9" flat class="custom-flat-date full-width" />
                      
                      <div class="row items-center justify-between q-pa-sm calendar-popover-footer border-top-solid bg-slate-50">
                        <div class="row q-gutter-x-xs">
                          <q-btn label="All Time" color="blue-grey-7" flat dense size="12px" class="text-weight-bold q-px-xs" @click="clearDate" v-close-popup />
                          <q-btn label="Today" color="blue-8" flat dense size="12px" class="text-weight-bold q-px-xs" @click="setToday" v-close-popup />
                        </div>
                        <q-btn label="Apply" color="red-9" unelevated dense size="12px" class="text-weight-bold q-px-md" style="border-radius: 6px;" @click="fetchSalesData" v-close-popup />
                      </div>
                    </q-card>
                  </q-popup-proxy>
                </q-btn>
              </div>

              <div class="row items-center justify-between">
                <div class="text-weight-bolder" :class="$q.screen.lt.md ? 'text-h3' : 'text-h2'" style="letter-spacing: -0.02em;">₱{{ formatNumber(metrics.revenue) }}</div>
                
                <!-- Dynamic Growth Rate (Hidden if no data) -->
                <div v-if="metrics.growthRate" class="row items-center text-green-3 text-weight-bold" :style="$q.screen.lt.md ? 'font-size: 12px;' : ''">
                  <q-icon name="trending_up" :size="$q.screen.lt.md ? '18px' : '24px'" class="q-mr-xs" />
                  +{{ metrics.growthRate }}% vs Yesterday
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- Desktop Metrics Grid -->
          <div v-if="!$q.screen.lt.md" class="row q-col-gutter-md q-mb-lg items-stretch">
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

            <div class="col-12 col-sm-4">
              <q-card class="premium-glass-card ml-blueprint-card bg-gradient-dark text-white h-full relative-position overflow-hidden">
                <div class="glow-amber"></div>
                <q-card-section class="relative-position z-top column justify-between h-full">
                  <div class="row items-center q-mb-md">
                    <q-icon name="auto_awesome" size="18px" color="amber-4" class="q-mr-sm" />
                    <div class="text-subtitle2 text-amber-2 text-uppercase" style="font-size: 11px;">BEST SELLER FOR {{ displayDate.toUpperCase() }}</div>
                  </div>
                  <div class="text-h6 text-weight-bold text-white leading-tight">
                    {{ metrics.bestSellingCategory || 'No Data' }}
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </div>

          <!-- Mobile Metrics Stacked Cards -->
          <div v-else class="q-mb-xl q-gutter-y-md">
            <!-- Avg Order Value -->
            <q-card bordered flat class="bg-white shadow-soft" style="border-radius: 10px; border-color: #e2e8f0;">
              <q-card-section class="q-pa-md row items-center justify-between">
                <div>
                  <div class="text-caption text-weight-bold text-blue-grey-6 text-uppercase q-mb-xs" style="font-size: 11px;">Avg Order Value</div>
                  <div class="text-h6 text-weight-bold text-slate-800 leading-tight">₱{{ formatNumber(metrics.avgOrderValue) }}</div>
                </div>
                <q-avatar size="38px" color="grey-2" text-color="blue-grey-6" icon="receipt_long" />
              </q-card-section>
            </q-card>

            <!-- Cancellation Rate -->
            <q-card bordered flat class="bg-white shadow-soft" style="border-radius: 10px; border-color: #e2e8f0;">
              <q-card-section class="q-pa-md row items-center justify-between">
                <div>
                  <div class="text-caption text-weight-bold text-blue-grey-6 text-uppercase q-mb-xs" style="font-size: 11px;">Cancellation Rate</div>
                  <div class="text-h6 text-weight-bold text-slate-800 leading-tight">{{ metrics.cancellationRate }}%</div>
                </div>
                <q-avatar size="38px" color="red-50" text-color="red-8" icon="remove_shopping_cart" />
              </q-card-section>
            </q-card>

            <!-- Best Seller Card -->
            <q-card class="shadow-soft" style="border-radius: 10px; background-color: #1e293b; border: 1px solid #334155;">
              <q-card-section class="q-pa-md row items-center justify-between no-wrap">
                <div class="col q-pr-sm">
                  <div class="text-caption text-weight-bold text-amber-5 text-uppercase q-mb-xs" style="font-size: 11px;">Best Seller</div>
                  <div class="text-h6 text-weight-bold text-white leading-tight ellipsis">{{ metrics.bestSellingCategory || 'No Data' }}</div>
                </div>
                <q-avatar size="38px" color="amber-9" text-color="white" icon="emoji_events" />
              </q-card-section>
            </q-card>
          </div>

          <!-- Transactions Table Container / Header (Mobile Clean Title) -->
          <div class="row items-center justify-between q-mb-md">
            <div class="row items-center">
              <div v-if="$q.screen.lt.md" style="width: 4px; height: 20px; background-color: #b91c1c; border-radius: 2px;" class="q-mr-sm"></div>
              <h2 class="text-h6 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight" :class="{ 'text-red-9': $q.screen.lt.md }" style="line-height: 1;">Sales Records</h2>
            </div>
          </div>

          <!-- Desktop Table -->
          <q-card v-if="!$q.screen.lt.md" class="premium-glass-card">
            <q-card-section class="panel-header q-pa-md">
              <div class="text-subtitle1 text-weight-bolder text-dark row items-center" style="font-size: 17px;">
                <div class="header-accent-red q-mr-md"></div>
                Sales for {{ displayDate }}
              </div>
            </q-card-section>

            <q-table
              v-if="transactions.length > 0"
              flat
              class="custom-premium-table bg-transparent"
              :rows="transactions"
              :columns="columns"
              row-key="order_id"
              hide-bottom
              :pagination="{ rowsPerPage: 5 }"
            >
              <template #body-cell-order_id="props">
                <q-td :props="props">
                  <span class="order-id-badge text-weight-bold text-red-8 q-px-sm q-py-xs bg-red-1 transition-ease" style="border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 6px;">
                    #{{ props.row.order_id }}
                  </span>
                </q-td>
              </template>
              <template #body-cell-status="props">
                <q-td :props="props">
                  <q-chip :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bolder status-chip q-px-md shadow-1" style="font-size: 13px;">
                    {{ formatStatus(props.row.status) }}
                  </q-chip>
                </q-td>
              </template>
              <template #body-cell-total="props">
                <q-td :props="props" class="text-weight-bold text-blue-grey-9">₱{{ formatNumber(props.row.total) }}</q-td>
              </template>
              <template #body-cell-daily_revenue="props">
                <q-td :props="props" class="text-weight-bold text-blue-grey-9">₱{{ formatNumber(props.row.daily_revenue) }}</q-td>
              </template>
            </q-table>

            <div v-else class="full-width q-pa-lg flex flex-center">
              <div class="bg-slate-50 border-slate-light rounded-borders q-pa-lg text-center shadow-soft" style="max-width: 420px; border-style: dashed; border-width: 2px;">
                <q-icon name="query_stats" size="56px" color="blue-grey-3" class="q-mb-md" />
                <div class="text-h6 text-weight-bolder text-blue-grey-9 q-mb-xs">No Sales Data Found</div>
                <div class="text-body2 text-blue-grey-6 q-mb-none">
                  There are no recorded transactions for <strong>{{ displayDate }}</strong>. As you process orders or add manual sales, they will appear here.
                </div>
              </div>
            </div>
          </q-card>

          <!-- MOBILE SALES RECORDS LIST -->
          <div v-if="$q.screen.lt.md" class="q-pb-xl">
            <!-- Empty State Feedback Dialog for Mobile with Single Primary Button -->
            <div v-if="transactions.length === 0" class="full-width text-center bg-slate-50 shadow-soft q-pa-lg border-slate-light" style="border-radius: 12px; border-style: dashed; border-width: 2px;">
              <q-icon name="query_stats" size="48px" color="blue-grey-3" class="q-mb-md drop-shadow-icon" />
              <div class="text-subtitle1 text-weight-bolder text-blue-grey-9 q-mb-xs">No Sales Data Found</div>
              <div class="text-caption text-blue-grey-6 q-mb-md">
                There are no recorded transactions for <strong>{{ displayDate }}</strong>.
              </div>
              <q-btn unelevated color="red-9" icon="add" label="Record a Sale" no-caps class="text-weight-bold full-width" style="border-radius: 8px; padding: 8px 16px;" @click="showMobileManualModal = true" />
            </div>

            <!-- Mobile Transactions Rendering -->
            <div v-else>
              <div v-for="row in transactions" :key="row.order_id || row.sale_date" class="q-mb-md">
                <q-card flat bordered class="bg-white shadow-soft" style="border-radius: 10px; border-color: #e2e8f0;">
                  <q-card-section class="q-pa-md">
                    <div class="row justify-between items-center q-mb-sm">
                      <div class="text-weight-bold text-slate-800" style="font-size: 15px;">
                        {{ selectedDate ? 'Order #' + row.order_id : row.sale_date }}
                      </div>
                      <q-chip v-if="selectedDate" :color="getStatusColor(row.status)" text-color="white" size="sm" class="text-weight-bolder q-ma-none" style="border-radius: 6px; height: 24px; padding: 0 10px;">
                        {{ formatStatus(row.status) }}
                      </q-chip>
                      <div v-else class="text-caption text-slate-500 font-medium">{{ row.total_items }} Items Sold</div>
                    </div>
                    
                    <div v-if="selectedDate" class="text-body2 text-slate-700 q-mb-sm font-medium" style="font-size: 13px;">{{ row.product }}</div>
                    
                    <div class="row justify-between items-end q-mt-sm">
                      <div v-if="selectedDate" class="text-caption text-slate-500">Qty: {{ row.quantity }}</div>
                      <div v-else class="text-caption text-slate-500">Daily Total Revenue</div>
                      <div class="text-weight-bold text-brand-red" style="font-size: 16px;">₱{{ formatNumber(selectedDate ? row.total : row.daily_revenue) }}</div>
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>

        </div>

        <!-- ================= RIGHT COLUMN (Manual Entry - Desktop Only) ================= -->
        <div v-if="!$q.screen.lt.md" class="col-12 col-md-4">
          <q-card class="premium-glass-card q-pa-sm manual-entry-card relative-position overflow-hidden">
            
            <div v-if="!selectedDate" class="absolute-full flex flex-center z-top" style="background: rgba(255,255,255,0.85); backdrop-filter: blur(4px); border-radius: inherit;">
              <div class="text-center q-pa-md">
                <q-icon name="edit_calendar" size="44px" color="blue-grey-4" class="q-mb-xs" />
                <div class="text-subtitle1 text-weight-bold text-dark leading-tight q-mb-xs">Select a Date</div>
                <div class="text-caption text-blue-grey-6">All Time date is selected. Please select a specific date from the calendar to manual entry sales.</div>
              </div>
            </div>
            
            <q-card-section class="q-pb-none q-pt-md">
              <div class="row items-center q-mb-xs">
                <div class="icon-compact-box bg-grey-2 border-grey-light text-red-8 q-mr-sm">
                  <q-icon name="add_shopping_cart" size="20px" />
                </div>
                <div class="text-h6 text-weight-bold text-dark leading-tight">Manual Entry</div>
              </div>
            </q-card-section>

            <q-card-section class="q-pt-sm q-pb-md">
              <q-form @submit.prevent="confirmManualSale">
                <div class="q-mb-md">
                  <div class="text-caption text-weight-bold text-blue-grey-8 q-mb-xs" style="font-size: 13px;">
                    Product Name <span class="text-red">*</span>
                  </div>
                  <q-select 
                    v-model="manualForm.product" 
                    :options="inventoryOptions" 
                    option-value="inventory_id" 
                    option-label="product_name" 
                    :use-input="!manualForm.product" 
                    clearable 
                    @clear="manualForm.unitPrice = 0" 
                    input-debounce="0" 
                    @filter="filterInventory" 
                    @update:model-value="onProductSelected" 
                    outlined 
                    dense 
                    class="manual-modal-input-grey" 
                    placeholder="Search product..." 
                    :rules="[val => !!val || 'Product is required']" 
                    hide-bottom-space 
                  >
                    <template v-slot:no-option><q-item><q-item-section class="text-italic text-grey-6">No products found</q-item-section></q-item></template>
                  </q-select>
                </div>
                
                <div class="row q-col-gutter-md q-mb-md">
                  <div class="col-6">
                    <div class="text-caption text-weight-bold text-blue-grey-8 q-mb-xs" style="font-size: 13px;">Quantity</div>
                    <q-input v-model.number="manualForm.quantity" type="number" outlined dense class="manual-modal-input-grey" :rules="[val => val > 0 || 'Must be > 0']" hide-bottom-space />
                  </div>
                  <div class="col-6">
                    <div class="text-caption text-weight-bold text-blue-grey-8 q-mb-xs" style="font-size: 13px;">Unit Price (₱)</div>
                    <q-input v-model.number="manualForm.unitPrice" type="number" outlined dense class="manual-modal-input-grey" :rules="[val => val >= 0 || 'Invalid price']" hide-bottom-space />
                  </div>
                </div>

                <div class="q-pa-md q-mb-md" style="border-radius: 6px; border: 1px solid #e2e8f0; background: #fff;">
                  <div class="row items-center justify-between">
                    <div class="text-subtitle2 text-blue-grey-8 text-weight-bold">Estimated Total</div>
                    <div class="text-h6 text-weight-bolder text-red-9">₱{{ formatNumber(estimatedTotal) }}</div>
                  </div>
                </div>

                <div>
                  <q-btn type="submit" label="Record Sale" unelevated class="full-width bg-brand-red text-white text-weight-bold" style="border-radius: 8px; padding: 12px 0; font-size: 15px;" no-caps :loading="submitting" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

      </div>

    </div>

    <!-- Mobile Manual Sale Modal -->
    <q-dialog v-model="showMobileManualModal" position="bottom">
      <q-card style="width: 100%; border-radius: 20px 20px 0 0; padding-bottom: 24px;" class="bg-white overflow-hidden">
        
        <q-card-section class="row items-center justify-between q-py-md q-px-lg bg-gradient-red text-white">
          <div class="text-h6 text-weight-bolder tracking-tight">Add Manual Sale</div>
          <q-btn icon="close" flat round dense v-close-popup class="opacity-80 hover-opacity-100 text-white" size="sm" />
        </q-card-section>
        
        <q-card-section class="q-px-lg q-pt-lg">
          
          <div v-if="!selectedDate" class="bg-red-50 text-red-9 q-pa-md rounded-borders q-mb-md" style="border: 1px solid #fca5a5;">
            <div class="row items-center q-mb-xs">
              <q-icon name="warning" size="18px" class="q-mr-xs" />
              <span class="text-weight-bold" style="font-size: 14px;">Date Selection Required</span>
            </div>
            <div style="font-size: 13px;">You must select a specific date from the calendar to record a manual sale.</div>
          </div>

          <q-form v-else @submit.prevent="confirmManualSale">
            <div class="q-mb-md">
              <div class="text-caption text-weight-bold text-blue-grey-8 q-mb-xs" style="font-size: 13px;">
                Product Name <span class="text-red">*</span>
              </div>
              <q-select 
                v-model="manualForm.product" 
                :options="inventoryOptions" 
                option-value="inventory_id" 
                option-label="product_name" 
                :use-input="!manualForm.product" 
                clearable 
                @clear="manualForm.unitPrice = 0" 
                input-debounce="0" 
                @filter="filterInventory" 
                @update:model-value="onProductSelected" 
                outlined 
                dense 
                placeholder="Search product..." 
                :rules="[val => !!val || 'Product is required']"
                bg-color="white"
                class="manual-modal-input"
                behavior="menu"
                menu-anchor="bottom left"
                menu-self="top left"
              >
                <template v-slot:no-option>
                  <q-item><q-item-section class="text-italic text-grey-6">No products found</q-item-section></q-item>
                </template>
              </q-select>
            </div>

            <div class="row q-col-gutter-md q-mb-md">
              <div class="col-6">
                <div class="text-caption text-weight-bold text-blue-grey-8 q-mb-xs" style="font-size: 13px;">Quantity</div>
                <q-input 
                  v-model.number="manualForm.quantity" 
                  type="number" 
                  outlined 
                  dense 
                  class="manual-modal-input-grey"
                  :rules="[val => val > 0 || 'Must be > 0']"
                  hide-bottom-space
                />
              </div>
              <div class="col-6">
                <div class="text-caption text-weight-bold text-blue-grey-8 q-mb-xs" style="font-size: 13px;">Unit Price (₱)</div>
                <q-input 
                  v-model.number="manualForm.unitPrice" 
                  type="number" 
                  outlined 
                  dense 
                  class="manual-modal-input-grey"
                  :rules="[val => val >= 0 || 'Invalid price']"
                  hide-bottom-space
                />
              </div>
            </div>

            <div class="q-pa-md q-mb-md" style="border-radius: 6px; border: 1px solid #e2e8f0; background: #fff;">
              <div class="row items-center justify-between">
                <div class="text-subtitle2 text-blue-grey-8 text-weight-bold">Estimated Total</div>
                <div class="text-h6 text-weight-bolder text-red-9">₱{{ formatNumber(estimatedTotal) }}</div>
              </div>
            </div>

            <div>
              <q-btn type="submit" label="Record Sale" unelevated class="full-width bg-brand-red text-white text-weight-bold" style="border-radius: 8px; padding: 12px 0; font-size: 15px;" no-caps :loading="submitting" />
            </div>

          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useQuasar, date } from 'quasar'
import { api } from '@/boot/axios'

const $q = useQuasar()

const timeStamp = Date.now()
const selectedDate = ref(date.formatDate(timeStamp, 'YYYY/MM/DD'))
const showMobileManualModal = ref(false)

const displayDate = computed(() => {
  if (!selectedDate.value) return 'All Time'
  const d = new Date(selectedDate.value.replace(/\//g, '-'))
  return date.formatDate(d, 'MMM DD, YYYY')
})

const clearDate = () => {
  selectedDate.value = null
  fetchSalesData()
}

const setToday = () => {
  selectedDate.value = date.formatDate(Date.now(), 'YYYY/MM/DD')
  fetchSalesData()
}

const metrics = reactive({
  revenue: 0,
  growthRate: null,
  avgOrderValue: 0,
  cancellationRate: 0,
  bestSellingCategory: null
})
const transactions = ref([])
const submitting = ref(false)

const columns = computed(() => {
  if (!selectedDate.value) {
    return [
      { name: 'sale_date', label: 'Date', field: 'sale_date', align: 'left', sortable: true },
      { name: 'total_items', label: 'Products Sold', field: 'total_items', align: 'left', sortable: true },
      { name: 'daily_revenue', label: 'Revenue (₱)', field: 'daily_revenue', align: 'left', sortable: true }
    ]
  }
  return [
    { name: 'order_id', label: 'Order ID', field: 'order_id', align: 'left', sortable: true },
    { name: 'product', label: 'Product', field: 'product', align: 'left' },
    { name: 'quantity', label: 'Items', field: 'quantity', align: 'left', sortable: true },
    { name: 'total', label: 'Total (₱)', field: 'total', align: 'left', sortable: true },
    { name: 'status', label: 'Status', field: 'status', align: 'left' }
  ]
})

const getStatusColor = (status) => {
  const normalizedStatus = String(status).toLowerCase().replace(/\s+/g, '_')
  
  switch (normalizedStatus) {
    case 'placed': return 'blue-6'
    case 'preparing': return 'purple-5'
    case 'ready_for_pickup': return 'orange-6'
    case 'picked_up': return 'green-6'
    case 'cancelled': return 'red-6'
    case 'pending': return 'orange-8'
    default: return 'grey-6'
  }
}

const formatStatus = (status) => {
  if (!status) return ''
  return String(status).split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatNumber = (num) => {
  return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

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
      const payload = {
        inventory_id: manualForm.product.inventory_id,
        quantity: manualForm.quantity,
        unit_price: manualForm.unitPrice,
        total_amount: estimatedTotal.value,
        sale_date: selectedDate.value ? selectedDate.value.replace(/\//g, '-') : date.formatDate(Date.now(), 'YYYY-MM-DD')
      }
      await api.post('/vendor/sales/manual', payload)
      $q.notify({ type: 'positive', message: 'Manual sale recorded successfully.', position: 'top-right' })
      manualForm.product = null
      manualForm.quantity = 1
      manualForm.unitPrice = 0
      showMobileManualModal.value = false
      
      await fetchSalesData()
    } catch (error) {
      $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to record manual sale.', position: 'top-right' })
    } finally {
      submitting.value = false
    }
  })
}

const fetchSalesData = async () => {
  try {
    const requestParams = selectedDate.value 
      ? { start_date: selectedDate.value.replace(/\//g, '-'), end_date: selectedDate.value.replace(/\//g, '-') } 
      : {};

    const [metricsRes, transRes, invRes] = await Promise.all([
      api.get('/vendor/sales/metrics', { params: requestParams }),
      api.get('/vendor/sales/transactions', { params: requestParams }),
      api.get('/vendor/products')
    ])
    
    if (metricsRes.data) {
      metrics.revenue = metricsRes.data.revenue || 0
      metrics.growthRate = metricsRes.data.growth_rate || null 
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
  background: radial-gradient(circle, rgba(185, 28, 28, 0.4) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: 100px;
  right: -50px;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.3) 0%, transparent 70%); 
}

/* Typography Utilities */
.text-brand-red { color: #b91c1c !important; }
.bg-brand-red { background-color: #b91c1c !important; }
.bg-white-20 { background-color: rgba(255,255,255,0.15) !important; }
.hover-bg-white-20:hover { background-color: rgba(255,255,255,0.25) !important; }
.transition-ease { transition: all 0.2s ease; }
.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.opacity-80 { opacity: 0.8; }
.h-full { height: 100%; }
.shrink-none { flex-shrink: 0; }
.border-none { border: none !important; }
.border-radius-6 { border-radius: 6px; }
.bg-slate-50 { background-color: #f8fafc; }

/* Header Glass Icon Box */
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

/* Calendar Popover Styling */
.calendar-popover-card {
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
}
.calendar-popover-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}
.custom-flat-date {
  width: 100%;
}
.custom-flat-date :deep(.q-date__header) {
  display: none;
}
.border-top-solid {
  border-top: 1px solid #e2e8f0;
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

/* Compact Icon Box for Manual Entry */
.icon-compact-box {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Mobile Manual Modal Inputs */
.manual-modal-input :deep(.q-field__control) {
  border-radius: 6px;
  border: 1px solid #cbd5e1;
}
.manual-modal-input :deep(.q-field__control:before) {
  border: none;
}
.manual-modal-input-grey :deep(.q-field__control) {
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background-color: #f8fafc !important;
}
.manual-modal-input-grey :deep(.q-field__control:before) {
  border: none;
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
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.25);
  transition: all 0.2s ease;
}
.btn-premium:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(185, 28, 28, 0.35);
}

/* Utilities */
.border-grey-light { border: 1px solid rgba(226, 232, 240, 0.8); }
.border-slate-light { border: 1px solid #e2e8f0; }
.shadow-soft { box-shadow: 0 2px 8px rgba(15,23,42,0.06); }

/* Custom Premium Table Styling */
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7);
  backdrop-filter: blur(8px);
  font-weight: 700;
  color: #64748B; 
  text-transform: uppercase; 
  font-size: 10px;
  letter-spacing: 0.05em; 
  padding: 8px 16px; 
  border-bottom: 1px solid rgba(226, 232, 240, 0.8); 
}
:deep(.custom-premium-table tbody td) {
  padding: 8px 16px; 
  border-bottom: 1px solid rgba(226, 232, 240, 0.5); 
}

/* Empty State Styling */
.drop-shadow-icon { filter: drop-shadow(0 4px 6px rgba(15, 23, 42, 0.05)); opacity: 0.5; }

/* Mobile specific styling */
@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { padding: 16px 16px 32px 16px !important; }
  .desktop-only { display: none !important; }
}
</style>