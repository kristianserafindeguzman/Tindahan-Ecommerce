<template>
  <q-page class="vendor-page relative-position" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= DESKTOP HEADER AREA ================= -->
      <div v-if="!$q.screen.lt.md" class="page-header q-mb-xl q-mt-sm row items-center justify-between">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md">
            <q-icon name="point_of_sale" size="26px" color="red-8" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">Sales Management</h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none">Monitor metrics, process manual entries, and view predictions.</p>
          </div>
        </div>

        <div class="q-mt-md q-mt-sm-none">
          <q-btn outline icon="calendar_today" color="dark" :label="displayDate" no-caps class="btn-glass-outline text-weight-bold q-px-lg">
            <q-popup-proxy cover transition-show="scale" transition-hide="scale">
              <q-date v-model="selectedDate" mask="YYYY/MM/DD" color="red-8" today-btn class="premium-glass-card">
                  <div class="row justify-between full-width border-top q-pt-sm" style="border-top: 1px solid rgba(0,0,0,0.05)">
                    <div class="q-gutter-x-sm">
                      <q-btn label="View All Time" color="grey-8" flat size="sm" class="text-weight-bold" @click="clearDate" v-close-popup />
                      <q-btn label="Go to Today" color="blue-8" flat size="sm" class="text-weight-bold" @click="setToday" v-close-popup />
                    </div>
                    <div class="q-gutter-x-sm">
                      <q-btn label="Cancel" color="grey-6" flat size="sm" v-close-popup />
                      <q-btn label="Apply" color="red-8" unelevated size="sm" class="text-weight-bold" @click="fetchSalesData" v-close-popup />
                    </div>
                  </div>
              </q-date>
            </q-popup-proxy>
          </q-btn>
        </div>
      </div>

      <!-- ================= MOBILE HEADER AREA ================= -->
      <div v-else class="page-header q-mb-lg q-mt-sm">
        <!-- Restored Original Mobile Header with Logo and Text -->
        <div class="row items-center q-mb-md">
          <div class="glass-icon-box q-mr-md" style="width: 44px; height: 44px;">
            <q-icon name="point_of_sale" size="22px" class="text-brand-red" />
          </div>
          <div>
            <h1 class="text-h5 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight leading-tight">Sales Management</h1>
            <p class="text-caption text-blue-grey-5 q-mt-xs q-mb-none font-medium">Monitor metrics and track revenue.</p>
          </div>
        </div>
        
        <!-- Clean, Standardized Date Picker matching the reference image -->
        <q-btn outline icon="calendar_today" text-color="blue-grey-8" :label="displayDate" no-caps class="full-width text-weight-bold" style="border-radius: 6px; background-color: #94a3b840; border-color: #94a3b880; height: 40px; font-size: 13px;">
          <q-popup-proxy cover transition-show="scale" transition-hide="scale">
            <q-date v-model="selectedDate" mask="YYYY/MM/DD" color="red-8" today-btn class="bg-white">
              <div class="row justify-between full-width border-top q-pt-sm" style="border-top: 1px solid rgba(0,0,0,0.05)">
                <div class="q-gutter-x-sm">
                  <q-btn label="All Time" color="grey-8" flat size="sm" class="text-weight-bold" @click="clearDate" v-close-popup />
                  <q-btn label="Today" color="blue-8" flat size="sm" class="text-weight-bold" @click="setToday" v-close-popup />
                </div>
                <div class="q-gutter-x-sm">
                  <q-btn label="Apply" color="red-8" unelevated size="sm" class="text-weight-bold" @click="fetchSalesData" v-close-popup />
                </div>
              </div>
            </q-date>
          </q-popup-proxy>
        </q-btn>
      </div>

      <div class="row q-col-gutter-lg q-col-gutter-md-xl">
        
        <!-- ================= LEFT COLUMN / MAIN CONTENT ================= -->
        <div class="col-12 col-md-8">
          
          <!-- Today's Revenue -->
          <q-card class="q-mb-lg text-white" :class="$q.screen.lt.md ? 'bg-brand-red q-pa-md shadow-2' : 'premium-glass-card q-pa-md bg-gradient-red'" :style="$q.screen.lt.md ? 'border-radius: 12px;' : ''">
            <q-card-section :class="{ 'q-pa-sm': $q.screen.lt.md }">
              <div class="text-white opacity-80 text-uppercase text-weight-bold q-mb-sm" :style="$q.screen.lt.md ? 'font-size: 11px; letter-spacing: 0.5px;' : ''">REVENUE FOR {{ displayDate.toUpperCase() }}</div>
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
                    {{ metrics.bestSellingCategory || 'Analyzing...' }}
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
                  <div class="text-h6 text-weight-bold text-white leading-tight ellipsis">{{ metrics.bestSellingCategory || 'Analyzing...' }}</div>
                </div>
                <q-avatar size="38px" color="amber-9" text-color="white" icon="emoji_events" />
              </q-card-section>
            </q-card>

          </div>

          <!-- Transactions Table Container / Header -->
          <div class="row items-center justify-between q-mb-md">
            <div class="row items-center">
              <div v-if="$q.screen.lt.md" style="width: 4px; height: 20px; background-color: #b91c1c; border-radius: 2px;" class="q-mr-sm"></div>
              <h2 class="text-h6 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight" :class="{ 'text-red-9': $q.screen.lt.md }" style="line-height: 1;">Sales Records</h2>
            </div>
            <!-- Standardized Manual Sale Button -->
            <q-btn v-if="$q.screen.lt.md" unelevated color="red-9" icon="add" label="Manual Sale" no-caps class="text-weight-bold shadow-1" style="border-radius: 6px; font-size: 12px; padding: 4px 12px;" @click="showMobileManualModal = true" />
          </div>

          <!-- Desktop Table -->
          <q-card v-if="!$q.screen.lt.md" class="premium-glass-card">
            <q-card-section class="panel-header q-pa-lg">
              <div class="text-h6 text-weight-bold text-dark row items-center">
                <div class="header-accent-red q-mr-md"></div>
                Sales for {{ displayDate }}
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
              <!-- Explicit Empty State for Desktop -->
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
          </q-card>

          <!-- MOBILE SALES RECORDS LIST -->
          <div v-if="$q.screen.lt.md" class="q-pb-xl">
            <!-- EXPLICIT RESTORED EMPTY STATE FEEDBACK FOR MOBILE -->
            <div v-if="transactions.length === 0" class="full-width text-center bg-white shadow-soft q-py-xl q-px-md border-slate-light" style="border-radius: 12px;">
              <q-icon name="receipt_long" size="48px" color="blue-grey-3" class="q-mb-md opacity-50 drop-shadow-icon" />
              <div class="text-subtitle1 text-weight-bold text-blue-grey-8">No sales recorded</div>
              <div class="text-caption text-blue-grey-5">There are no transactions for {{ displayDate }}.</div>
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
          <q-card class="premium-glass-card q-pa-sm manual-entry-card relative-position">
            
            <div v-if="!selectedDate" class="absolute-full flex flex-center z-top" style="background: rgba(255,255,255,0.85); backdrop-filter: blur(4px); border-radius: inherit;">
              <div class="text-center q-pa-lg">
                <q-icon name="edit_calendar" size="48px" color="blue-grey-4" class="q-mb-sm" />
                <div class="text-subtitle1 text-weight-bold text-dark leading-tight q-mb-xs">Select a Date</div>
                <div class="text-caption text-blue-grey-6">All Time date is selected. Please select a specific date from the calendar to manual entry sales.</div>
              </div>
            </div>
            
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
                  <q-select v-model="manualForm.product" :options="inventoryOptions" option-value="inventory_id" option-label="product_name" :use-input="!manualForm.product" clearable @clear="manualForm.unitPrice = 0" input-debounce="0" @filter="filterInventory" @update:model-value="onProductSelected" outlined dense class="custom-glass-input" placeholder="Search product..." :rules="[val => !!val || 'Product is required']" hide-bottom-space>
                    <template v-slot:no-option><q-item><q-item-section class="text-italic text-grey-6">No products found</q-item-section></q-item></template>
                  </q-select>
                </div>
                <div class="row q-col-gutter-md">
                  <div class="col-6 field-group">
                    <label class="input-label">Quantity</label>
                    <q-input v-model.number="manualForm.quantity" type="number" outlined dense class="custom-glass-input" :rules="[val => val > 0 || 'Must be > 0']" hide-bottom-space />
                  </div>
                  <div class="col-6 field-group">
                    <label class="input-label">Unit Price (₱)</label>
                    <q-input v-model.number="manualForm.unitPrice" type="number" outlined dense class="custom-glass-input" :rules="[val => val >= 0 || 'Invalid price']" hide-bottom-space />
                  </div>
                </div>
                <div class="bg-grey-2 rounded-borders q-pa-md q-mt-md shadow-1">
                  <div class="row items-center justify-between">
                    <div class="text-subtitle2 text-grey-8">Estimated Total</div>
                    <div class="text-h6 text-weight-bold text-red-8">₱{{ formatNumber(estimatedTotal) }}</div>
                  </div>
                </div>
                <div class="q-mt-sm">
                  <q-btn type="submit" label="Record Sale" unelevated class="btn-premium text-white full-width bg-red-8" size="lg" no-caps :loading="submitting" />
                </div>
              </q-form>
            </q-card-section>
          </q-card>
        </div>

      </div>

      <!-- ================= PREMIUM MOBILE BOTTOM NAVIGATION ================= -->
      <div v-if="$q.screen.lt.md" class="mobile-bottom-nav row justify-around items-center">
        <div class="nav-item-wrapper" @click="$router.push('/vendor/dashboard')">
          <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
            <q-icon name="home" size="26px" />
          </q-btn>
        </div>
        <div class="nav-item-wrapper" @click="$router.push('/vendor/orders/list')">
          <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
            <q-icon name="receipt_long" size="26px" />
          </q-btn>
        </div>
        <div class="nav-item-wrapper" @click="$router.push('/vendor/products/list')">
          <q-btn flat round class="mobile-nav-btn text-blue-grey-4">
            <q-icon name="inventory_2" size="26px" />
          </q-btn>
        </div>
        <div class="nav-item-wrapper" @click="$router.push('/vendor/sales')">
          <q-btn flat round class="mobile-nav-btn nav-active shadow-3">
            <q-icon name="analytics" size="24px" />
          </q-btn>
        </div>
      </div>

    </div>

    <!-- Mobile Manual Sale Modal - EXACTLY MATCHING DESIGN -->
    <q-dialog v-model="showMobileManualModal" position="bottom">
      <q-card style="width: 100%; border-radius: 20px 20px 0 0; padding-bottom: 24px;" class="bg-white overflow-hidden">
        
        <!-- Red Gradient Header for Mobile Modal -->
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

          <q-form v-else @submit.prevent="confirmManualSale" class="q-gutter-y-md">
            
            <!-- Product Name -->
            <div>
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

            <!-- Quantity & Unit Price Row -->
            <div class="row q-col-gutter-md">
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

            <!-- Estimated Total Box -->
            <div class="q-mt-lg q-pa-md" style="border-radius: 6px; border: 1px solid #e2e8f0; background: #fff;">
              <div class="row items-center justify-between">
                <div class="text-subtitle2 text-blue-grey-8 text-weight-bold">Estimated Total</div>
                <div class="text-h6 text-weight-bolder text-red-9">₱{{ formatNumber(estimatedTotal) }}</div>
              </div>
            </div>

            <!-- Record Sale Button -->
            <div class="q-mt-lg">
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

// Set default date to today, formatted properly for QDate mask
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
  growthRate: null, // Tied to real data, hidden otherwise
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

// Unified Status Colors mapping to Quasar brand colors
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

// Format status nicely for UI
const formatStatus = (status) => {
  if (!status) return ''
  return String(status).split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
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
      const payload = {
        inventory_id: manualForm.product.inventory_id,
        quantity: manualForm.quantity,
        unit_price: manualForm.unitPrice,
        total_amount: estimatedTotal.value,
        sale_date: selectedDate.value ? selectedDate.value.replace(/\//g, '-') : date.formatDate(Date.now(), 'YYYY-MM-DD')
      }
      console.log("MANUAL SALE PAYLOAD", payload)
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
.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.opacity-80 { opacity: 0.8; }
.h-full { height: 100%; }
.shrink-none { flex-shrink: 0; }
.border-none { border: none !important; }

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

/* Base Inputs & Form Elements (Desktop) */
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

/* Mobile Manual Modal Inputs (Matches Reference Image Exactly) */
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
.border-slate-light { border: 1px solid #e2e8f0; }
.shadow-soft { box-shadow: 0 2px 8px rgba(15,23,42,0.06); }
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

/* Mobile specific styling */
@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { padding: 16px 16px calc(90px + env(safe-area-inset-bottom)) 16px !important; }
  .desktop-only { display: none !important; }
  
  /* Mobile Bottom Navigation */
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