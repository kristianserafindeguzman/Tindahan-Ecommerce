<template>
  <q-page class="vp-page">
    <div class="vp-container">

      <div class="vp-header">
        <div>
          <h1 class="vp-title">{{ t('title') }}</h1>
          <p class="vp-subtitle">{{ t('subtitle') }}</p>
        </div>
        <div class="vp-header-actions">
          <!-- Picking a day loads it straight away; All time and Today are one tap. -->
          <q-btn outline no-caps color="primary" icon="o_calendar_month" :label="displayDate" class="vp-pill-btn sr-date-btn">
            <q-popup-proxy ref="datePopup" anchor="bottom right" self="top right" :offset="[0, 8]">
              <div class="sr-calendar">
                <q-date v-model="selectedDate" mask="YYYY/MM/DD" color="primary" flat minimal class="sr-date" @update:model-value="closeDatePopup" />
                <div class="sr-calendar-foot">
                  <q-btn v-close-popup flat dense no-caps :label="t('btnAllTime')" class="sr-quick" :class="{ 'sr-quick--on': !selectedDate }" @click="clearDate" />
                  <q-btn v-close-popup flat dense no-caps :label="t('btnToday')" class="sr-quick" :class="{ 'sr-quick--on': isToday }" @click="setToday" />
                </div>
              </div>
            </q-popup-proxy>
          </q-btn>
          <q-btn v-if="$q.screen.lt.lg" unelevated no-caps color="primary" icon="add" :label="t('btnRecordSale')" class="vp-primary-btn" @click="showMobileManualModal = true" />
        </div>
      </div>

      <div class="sr-grid">
        <div class="sr-main">

          <!-- REVENUE — the total, what it came from, and a small bar chart of the records behind it. -->
          <section class="sr-hero">
            <div class="sr-hero-main">
              <div class="sr-hero-top">
                <span class="sr-eyebrow"><q-icon name="o_payments" size="16px" /> {{ t('eyebrowRevenue') }} · {{ displayDate }}</span>
                <span v-if="metrics.growthRate" class="sr-growth"><q-icon name="trending_up" size="16px" /> +{{ metrics.growthRate }}% {{ t('vsYesterday') }}</span>
              </div>
              <div class="sr-hero-value">₱{{ formatNumber(metrics.revenue) }}</div>
              <div class="sr-hero-facts">
                <span class="sr-fact">
                  <q-icon name="o_receipt_long" size="16px" />
                  {{ recordCount }} {{ shownDate ? t('orderWord') : t('dayWord') }}{{ recordCount === 1 ? '' : 's' }}
                </span>
                <span class="sr-fact">
                  <q-icon name="o_shopping_basket" size="16px" />
                  {{ itemsSold }} {{ itemsSold === 1 ? t('itemSold') : t('itemsSold') }}
                </span>
                <span class="sr-fact">
                  <q-icon name="o_sell" size="16px" />
                  ₱{{ formatNumber(metrics.avgOrderValue) }} {{ t('perOrder') }}
                </span>
              </div>
            </div>

            <div v-if="heroBars.length" class="sr-hero-chart">
              <div class="sr-bars" role="img" :aria-label="shownDate ? t('ariaBarsDay') : t('ariaBarsAllTime')">
                <span v-for="bar in heroBars" :key="bar.key" class="sr-bar" :style="{ height: `${bar.height}%` }" :title="bar.title" />
              </div>
              <div class="sr-bars-label">{{ shownDate ? t('labelBarsDay') : t('labelBarsAllTime') }}</div>
            </div>
            <q-icon v-else name="o_insights" class="sr-hero-art" aria-hidden="true" />
          </section>

          <div class="vp-stats sr-stats">
            <div class="vp-card vp-stat">
              <div class="vp-stat-top">
                <span class="vp-stat-label">{{ t('statAvgOrderValue') }}</span>
                <span class="vp-stat-icon vp-tone--info"><q-icon name="o_receipt_long" size="20px" /></span>
              </div>
              <div class="vp-stat-value">₱{{ formatNumber(metrics.avgOrderValue) }}</div>
            </div>
            <div class="vp-card vp-stat">
              <div class="vp-stat-top">
                <span class="vp-stat-label">{{ t('statCancelRate') }}</span>
                <span class="vp-stat-icon vp-tone--danger"><q-icon name="o_remove_shopping_cart" size="20px" /></span>
              </div>
              <div class="vp-stat-value">{{ metrics.cancellationRate }}%</div>
            </div>
            <div class="vp-card vp-stat vp-stat--wide">
              <div class="vp-stat-top">
                <span class="vp-stat-label">{{ t('statBestSeller') }}</span>
                <span class="vp-stat-icon vp-tone--wait"><q-icon name="o_emoji_events" size="20px" /></span>
              </div>
              <div class="vp-stat-value vp-stat-value--text">{{ metrics.bestSellingCategory || t('noDataYet') }}</div>
            </div>
          </div>

          <!-- SALES RECORDS -->
          <div class="vp-card">
            <div class="sr-card-head">
              <div>
                <div class="sr-card-title">{{ t('salesRecordsTitle') }}</div>
                <div class="sr-card-sub">{{ selectedDate ? t('ordersOn') + ' ' + displayDate : t('dailyTotalsAllTime') }}</div>
              </div>
              <q-skeleton v-if="loading" type="rect" width="30px" height="20px" class="sr-count-sk" />
              <span v-else class="sr-count">{{ transactions.length }}</span>
            </div>

            <!-- Placeholder rows in the columns of the view being loaded: one day's orders, or All time's daily totals. -->
            <SkeletonTable
              v-if="loading"
              :columns="selectedDate ? DAY_SKELETON : ALL_TIME_SKELETON"
              :rows="5"
              :list="$q.screen.lt.md"
              :lead="false"
              :pill="!!selectedDate"
              class="sr-skeleton"
            />

            <div v-else-if="!transactions.length" class="vp-empty">
              <div class="vp-empty-icon"><q-icon name="o_query_stats" size="24px" /></div>
              <div class="vp-empty-title">{{ t('emptySalesTitle') }}</div>
              <div class="vp-empty-text">{{ t('emptySalesText1') }} {{ displayDate }}. {{ t('emptySalesText2') }}</div>
            </div>

            <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
              <table v-if="shownDate" class="vp-table sr-table">
                <thead>
                  <tr>
                    <th class="col-order">{{ t('colOrder') }}</th>
                    <th>{{ t('colProduct') }}</th>
                    <th class="col-items">{{ t('colItems') }}</th>
                    <th class="text-right col-total">{{ t('colTotal') }}</th>
                    <th class="col-status">{{ t('colStatus') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, index) in pagedRows" :key="`${row.order_id}-${index}`">
                    <td><span class="vp-id">#{{ row.order_id }}</span></td>
                    <td class="sr-product">{{ row.product }}</td>
                    <td class="vp-muted">{{ row.quantity }}</td>
                    <td class="text-right vp-amount">₱{{ formatNumber(row.total) }}</td>
                    <td><OrderStatusBadge :status="row.status" /></td>
                  </tr>
                </tbody>
              </table>

              <table v-else class="vp-table sr-table">
                <thead>
                  <tr>
                    <th>{{ t('colDate') }}</th>
                    <th class="col-sold">{{ t('colProductsSold') }}</th>
                    <th class="text-right col-revenue">{{ t('colRevenue') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in pagedRows" :key="row.sale_date">
                    <td class="sr-product">{{ row.sale_date }}</td>
                    <td class="vp-muted">{{ row.total_items }}</td>
                    <td class="text-right vp-amount">₱{{ formatNumber(row.daily_revenue) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="vp-list">
              <div v-for="(row, index) in pagedRows" :key="`${row.order_id || row.sale_date}-${index}`" class="sr-list-item">
                <div class="vp-list-body">
                  <span class="vp-name">{{ shownDate ? `${t('colOrder')} #${row.order_id}` : row.sale_date }}</span>
                  <div class="vp-list-meta">{{ shownDate ? `${row.product} · ${t('qtyWord')} ${row.quantity}` : `${row.total_items} ${t('itemsSoldWord')}` }}</div>
                </div>
                <div class="vp-list-side">
                  <span class="vp-amount">₱{{ formatNumber(shownDate ? row.total : row.daily_revenue) }}</span>
                  <OrderStatusBadge v-if="shownDate" :status="row.status" />
                </div>
              </div>
            </div>

            <div v-if="!loading && transactions.length > PAGE_SIZES[0]" class="vp-pager">
              <span>{{ t('showingWord') }} {{ rangeStart }}–{{ rangeEnd }} {{ t('ofWord') }} {{ transactions.length }}</span>
              <div class="sr-pager-right">
                <label class="sr-page-size">
                  {{ t('rowsWord') }}
                  <q-select v-model="pageSize" :options="PAGE_SIZES" dense outlined options-dense behavior="menu" class="vp-input sr-page-select" aria-label="Rows per page" />
                </label>
              </div>
              <div class="vp-pager-btns">
                <q-btn outline no-caps color="primary" icon="o_chevron_left" class="vp-pill-btn" aria-label="Previous page" :disable="page === 1" @click="page--" />
                <q-btn outline no-caps color="primary" icon="o_chevron_right" class="vp-pill-btn" aria-label="Next page" :disable="page === pageCount" @click="page++" />
              </div>
            </div>
          </div>
        </div>

        <!-- RECORD A SALE — beside the records on wide screens, in a sheet on smaller ones. -->
        <aside v-if="!$q.screen.lt.lg" class="vp-card sr-entry">
          <div class="sr-entry-head">
            <span class="vp-stat-icon vp-tone--brand"><q-icon name="o_add_shopping_cart" size="20px" /></span>
            <div>
              <div class="sr-card-title">{{ t('recordSaleTitle') }}</div>
              <div class="sr-card-sub">{{ t('recordSaleSub') }} {{ displayDate }}.</div>
            </div>
          </div>

          <div v-if="!selectedDate" class="sr-locked">
            <div class="vp-empty-icon"><q-icon name="o_edit_calendar" size="24px" /></div>
            <div class="vp-empty-title">{{ t('pickDayTitle') }}</div>
            <div class="vp-empty-text">{{ t('pickDayDesc') }}</div>
          </div>

          <q-form v-else ref="asideForm" class="sr-form" @submit.prevent="askToRecord">
            <div>
              <label class="vp-field-label">{{ t('formProduct') }} <span class="sr-req">*</span></label>
              <q-select
                v-model="manualForm.product"
                :options="inventoryOptions"
                option-value="inventory_id"
                option-label="product_name"
                :use-input="!manualForm.product"
                clearable
                input-debounce="0"
                behavior="menu"
                outlined
                dense
                :placeholder="t('searchProduct')"
                hide-bottom-space
                class="vp-input"
                :rules="[val => !!val || t('ruleProduct')]"
                @clear="manualForm.unitPrice = 0"
                @filter="filterInventory"
                @update:model-value="onProductSelected"
              >
                <template #no-option>
                  <q-item><q-item-section class="sr-no-option">{{ t('noProductsFound') }}</q-item-section></q-item>
                </template>
              </q-select>
            </div>
            <div class="sr-form-row">
              <div>
                <label class="vp-field-label">{{ t('formQuantity') }}</label>
                <q-input v-model.number="manualForm.quantity" type="number" min="1" :max="maxQuantity" outlined dense hide-bottom-space class="vp-input" :rules="quantityRules" />
              </div>
              <div>
                <label class="vp-field-label">{{ t('formUnitPrice') }}</label>
                <q-input v-model.number="manualForm.unitPrice" type="number" min="0" :max="MAX_PRICE" step="0.01" outlined dense hide-bottom-space class="vp-input" :rules="priceRules" />
              </div>
            </div>
            <div class="sr-estimate">
              <span>{{ t('estimatedTotal') }}</span>
              <strong>{{ estimateText }}</strong>
            </div>
            <q-btn type="submit" unelevated no-caps color="primary" :label="t('btnRecordSale')" class="vp-primary-btn sr-submit" :loading="submitting" />
          </q-form>
        </aside>
      </div>
    </div>

    <!-- Below desktop, where the entry form isn't beside the records, Record a Sale opens as a centred dialog at every width. -->
    <q-dialog v-model="showMobileManualModal">
      <q-card class="sr-dialog">
        <div class="sr-entry-head sr-dialog-head">
          <span class="vp-stat-icon vp-tone--brand"><q-icon name="o_add_shopping_cart" size="20px" /></span>
          <div>
            <div class="sr-card-title">{{ t('recordSaleTitle') }}</div>
            <div class="sr-card-sub">{{ t('recordSaleSub') }} {{ displayDate }}.</div>
          </div>
          <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" />
        </div>

        <div v-if="!selectedDate" class="sr-locked">
          <div class="vp-empty-icon"><q-icon name="o_edit_calendar" size="24px" /></div>
          <div class="vp-empty-title">{{ t('pickDayTitle') }}</div>
          <div class="vp-empty-text">{{ t('pickDayDesc') }}</div>
        </div>

        <q-form v-else ref="sheetForm" class="sr-form" @submit.prevent="askToRecord">
          <div>
            <label class="vp-field-label">{{ t('formProduct') }} <span class="sr-req">*</span></label>
            <q-select
              v-model="manualForm.product"
              :options="inventoryOptions"
              option-value="inventory_id"
              option-label="product_name"
              :use-input="!manualForm.product"
              clearable
              input-debounce="0"
              behavior="menu"
              outlined
              dense
              :placeholder="t('searchProduct')"
              hide-bottom-space
              class="vp-input"
              :rules="[val => !!val || t('ruleProduct')]"
              @clear="manualForm.unitPrice = 0"
              @filter="filterInventory"
              @update:model-value="onProductSelected"
            >
              <template #no-option>
                <q-item><q-item-section class="sr-no-option">{{ t('noProductsFound') }}</q-item-section></q-item>
              </template>
            </q-select>
          </div>
          <div class="sr-form-row">
            <div>
              <label class="vp-field-label">{{ t('formQuantity') }}</label>
              <q-input v-model.number="manualForm.quantity" type="number" min="1" :max="maxQuantity" outlined dense hide-bottom-space class="vp-input" :rules="quantityRules" />
            </div>
            <div>
              <label class="vp-field-label">{{ t('formUnitPrice') }}</label>
              <q-input v-model.number="manualForm.unitPrice" type="number" min="0" :max="MAX_PRICE" step="0.01" outlined dense hide-bottom-space class="vp-input" :rules="priceRules" />
            </div>
          </div>
          <div class="sr-estimate">
            <span>{{ t('estimatedTotal') }}</span>
            <strong>{{ estimateText }}</strong>
          </div>
          <q-btn type="submit" unelevated no-caps color="primary" :label="t('btnRecordSale')" class="vp-primary-btn sr-submit" :loading="submitting" />
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="confirmOpen" persistent>
      <q-card class="vp-dialog">
        <div class="vp-dialog-head">
          <span class="vp-dialog-icon"><q-icon name="o_point_of_sale" size="22px" /></span>
          <div>
            <div class="vp-dialog-title">{{ t('confirmSaleTitle') }}</div>
            <div class="vp-dialog-text">
              <strong>{{ manualForm.quantity }} × {{ manualForm.product?.product_name }}</strong> {{ t('confirmSaleFor') }} <strong>{{ estimateText }}</strong>
              {{ t('confirmSaleOn') }} {{ displayDate }}. {{ t('confirmSaleDesc') }}
            </div>
          </div>
        </div>
        <div class="vp-dialog-actions">
          <q-btn v-close-popup outline no-caps color="primary" :label="t('btnCancel')" class="vp-dialog-btn" :disable="submitting" />
          <q-btn unelevated no-caps color="primary" :label="t('btnRecordSale')" class="vp-dialog-btn" :loading="submitting" @click="recordSale" />
        </div>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useQuasar, date } from 'quasar'
import { api } from '@/boot/axios'
import OrderStatusBadge from '@/components/vendor/OrderStatusBadge.vue'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'
import { useLanguage } from '@/composables/useLanguage'

const $q = useQuasar()

// Language Dictionary for this page
const vendorSalesDict = {
  en: {
    title: 'Sales Reports',
    subtitle: 'Track revenue, record walk-in sales and review each day.',
    btnAllTime: 'All time',
    btnToday: 'Today',
    btnRecordSale: 'Record Sale',
    eyebrowRevenue: 'Revenue',
    vsYesterday: 'vs yesterday',
    orderWord: 'order',
    dayWord: 'day',
    itemSold: 'item',
    itemsSold: 'items',
    perOrder: 'per order',
    ariaBarsDay: 'Total of each order on this day',
    ariaBarsAllTime: 'Revenue for each of the last 14 days',
    labelBarsDay: 'Each order on this day',
    labelBarsAllTime: 'Last 14 days',
    statAvgOrderValue: 'Avg order value',
    statCancelRate: 'Cancellation rate',
    statBestSeller: 'Best seller',
    noDataYet: 'No data yet',
    salesRecordsTitle: 'Sales Records',
    ordersOn: 'Orders on',
    dailyTotalsAllTime: 'Daily totals for all time',
    emptySalesTitle: 'No sales found',
    emptySalesText1: 'Nothing was recorded for',
    emptySalesText2: 'Orders and walk-in sales will show up here.',
    colOrder: 'Order',
    colProduct: 'Product',
    colItems: 'Items',
    colTotal: 'Total',
    colStatus: 'Status',
    colDate: 'Date',
    colProductsSold: 'Products sold',
    colRevenue: 'Revenue',
    qtyWord: 'Qty',
    itemsSoldWord: 'items sold',
    showingWord: 'Showing',
    ofWord: 'of',
    rowsWord: 'Rows',
    recordSaleTitle: 'Record a Sale',
    recordSaleSub: 'Add a walk-in sale for',
    pickDayTitle: 'Pick a day first',
    pickDayDesc: "Choose a specific date from the calendar to record a sale. All time can't take new entries.",
    formProduct: 'Product',
    searchProduct: 'Search product',
    ruleProduct: 'Choose a product.',
    noProductsFound: 'No products found',
    formQuantity: 'Quantity',
    formUnitPrice: 'Unit price (₱)',
    estimatedTotal: 'Estimated total',
    ruleQuantityWhole: 'Enter a whole number above 0.',
    ruleQuantityStock: 'Only {max} in stock.',
    rulePriceValid: 'Enter a valid price.',
    rulePriceMax: 'Enter a price up to ₱1,000,000.',
    confirmSaleTitle: 'Record this sale?',
    confirmSaleFor: 'for',
    confirmSaleOn: 'on',
    confirmSaleDesc: 'This updates your revenue and stock.',
    btnCancel: 'Cancel',
    notifySaleRecorded: 'Sale recorded.',
    notifySaleFailed: 'Failed to record the sale.',
    notifyAllTimeString: 'All Time'
  },
  ph: {
    title: 'Sales Reports',
    subtitle: 'I-track ang kita, mag-record ng walk-in sales at silipin ang benta araw-araw.',
    btnAllTime: 'Buong panahon',
    btnToday: 'Ngayon',
    btnRecordSale: 'I-record ang Benta',
    eyebrowRevenue: 'Kita',
    vsYesterday: 'kumpara kahapon',
    orderWord: 'order',
    dayWord: 'araw',
    itemSold: 'paninda',
    itemsSold: 'mga paninda',
    perOrder: 'kada order',
    ariaBarsDay: 'Kabuuan ng bawat order sa araw na ito',
    ariaBarsAllTime: 'Kita sa bawat araw ng nakalipas na dalawang linggo',
    labelBarsDay: 'Bawat order ngayong araw',
    labelBarsAllTime: 'Nakalipas na 14 araw',
    statAvgOrderValue: 'Average na halaga ng order',
    statCancelRate: 'Cancellation rate',
    statBestSeller: 'Pinakamabenta',
    noDataYet: 'Wala pang data',
    salesRecordsTitle: 'Records ng Benta',
    ordersOn: 'Mga order noong',
    dailyTotalsAllTime: 'Araw-araw na kabuuan sa buong panahon',
    emptySalesTitle: 'Walang nahanap na benta',
    emptySalesText1: 'Walang nai-record noong',
    emptySalesText2: 'Dito lalabas ang mga order at walk-in sales.',
    colOrder: 'Order',
    colProduct: 'Paninda',
    colItems: 'Dami',
    colTotal: 'Kabuuan',
    colStatus: 'Status',
    colDate: 'Petsa',
    colProductsSold: 'Naibentang paninda',
    colRevenue: 'Kita',
    qtyWord: 'Qty',
    itemsSoldWord: 'panindang naibenta',
    showingWord: 'Pinapakita ang',
    ofWord: 'mula sa',
    rowsWord: 'Bilang',
    recordSaleTitle: 'Mag-record ng Benta',
    recordSaleSub: 'Magdagdag ng walk-in sale para sa',
    pickDayTitle: 'Pumili muna ng araw',
    pickDayDesc: "Pumili ng partikular na petsa sa kalendaryo. Hindi pwedeng magdagdag ng benta sa 'Buong panahon'.",
    formProduct: 'Paninda',
    searchProduct: 'Hanapin ang paninda',
    ruleProduct: 'Pumili ng paninda.',
    noProductsFound: 'Walang nahanap na paninda',
    formQuantity: 'Dami',
    formUnitPrice: 'Presyo (₱)',
    estimatedTotal: 'Estimated na kabuuan',
    ruleQuantityWhole: 'Maglagay ng buong numero na higit sa 0.',
    ruleQuantityStock: 'Hanggang {max} na lang ang stock.',
    rulePriceValid: 'Maglagay ng tamang presyo.',
    rulePriceMax: 'Maglagay ng presyo hanggang ₱1,000,000.',
    confirmSaleTitle: 'I-record ang bentang ito?',
    confirmSaleFor: 'sa halagang',
    confirmSaleOn: 'ngayong',
    confirmSaleDesc: 'Maa-update nito ang iyong kita at bilang ng stock.',
    btnCancel: 'I-cancel',
    notifySaleRecorded: 'Nai-record na ang benta.',
    notifySaleFailed: 'Failed mai-record ang benta.',
    notifyAllTimeString: 'Buong Panahon'
  }
}

const { t } = useLanguage(vendorSalesDict)

const PAGE_SIZES = [10, 25, 50]

// The placeholder rows take the same columns as the two records tables.
const DAY_SKELETON = [
  { width: '14%', type: 'pill', size: 56 },
  { type: 'text' },
  { width: '11%', type: 'text' },
  { width: '16%', type: 'text', align: 'right' },
  { width: '20%', type: 'pill', size: 96 }
]
const ALL_TIME_SKELETON = [
  { type: 'text' },
  { width: '30%', type: 'text' },
  { width: '30%', type: 'text', align: 'right' }
]
const todayKey = () => date.formatDate(Date.now(), 'YYYY/MM/DD')

const selectedDate = ref(todayKey())
const showMobileManualModal = ref(false)
const confirmOpen = ref(false)
const datePopup = ref(null)
const asideForm = ref(null)
const sheetForm = ref(null)
const loading = ref(true)
const page = ref(1)
const pageSize = ref(PAGE_SIZES[0])
// The date the loaded rows belong to, so switching dates never reads old rows in the new layout while the new ones load.
const shownDate = ref(null)

const displayDate = computed(() => {
  if (!selectedDate.value) return t('notifyAllTimeString')
  return date.formatDate(new Date(selectedDate.value.replace(/\//g, '-')), 'MMM DD, YYYY')
})

const isToday = computed(() => selectedDate.value === todayKey())

const closeDatePopup = () => datePopup.value?.hide()
const clearDate = () => { selectedDate.value = null }
const setToday = () => { selectedDate.value = todayKey() }

const metrics = reactive({
  revenue: 0,
  growthRate: null,
  avgOrderValue: 0,
  cancellationRate: 0,
  bestSellingCategory: null
})
const transactions = ref([])
const submitting = ref(false)

const pageCount = computed(() => Math.max(1, Math.ceil(transactions.value.length / pageSize.value)))
const pagedRows = computed(() => transactions.value.slice((page.value - 1) * pageSize.value, page.value * pageSize.value))
const rangeStart = computed(() => (page.value - 1) * pageSize.value + 1)
const rangeEnd = computed(() => Math.min(page.value * pageSize.value, transactions.value.length))

watch(pageSize, () => { page.value = 1 })

const formatNumber = num => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

// A day's rows are order items, so orders are counted once each; All time counts the days.
const recordCount = computed(() => (shownDate.value ? new Set(transactions.value.map(row => row.order_id)).size : transactions.value.length))

const itemsSold = computed(() => transactions.value.reduce((sum, row) => sum + Number((shownDate.value ? row.quantity : row.total_items) || 0), 0))

// The hero's bars: each record's total on one day, or the last 14 days' revenue for All time, scaled to the largest.
const heroBars = computed(() => {
  const rows = shownDate.value
    ? [...transactions.value].sort((a, b) => Number(a.order_id) - Number(b.order_id)).slice(-16)
    : [...transactions.value].sort((a, b) => new Date(a.sale_date) - new Date(b.sale_date)).slice(-14)
  const values = rows.map(row => Number((shownDate.value ? row.total : row.daily_revenue) || 0))
  const max = Math.max(0, ...values)
  if (!max) return []
  return rows.map((row, i) => ({
    key: `${row.order_id || row.sale_date}-${i}`,
    height: Math.max(6, Math.round((values[i] / max) * 100)),
    title: `${shownDate.value ? `${t('colOrder')} #${row.order_id}` : row.sale_date}: ₱${formatNumber(values[i])}`
  }))
})

const manualForm = reactive({
  product: null,
  quantity: 1,
  unitPrice: 0
})

const estimatedTotal = computed(() => (manualForm.quantity || 0) * (manualForm.unitPrice || 0))

// Limits that keep a sale realistic: no more than the chosen product has in stock, and a price up to one million.
const MAX_PRICE = 1000000
const maxQuantity = computed(() => {
  const product = manualForm.product
  const stock = Number(product?.available_quantity ?? product?.stock_quantity)
  return product && Number.isFinite(stock) ? stock : 9999
})

const quantityRules = computed(() => [
  val => (Number.isInteger(Number(val)) && Number(val) > 0) || t('ruleQuantityWhole'),
  val => Number(val) <= maxQuantity.value || t('ruleQuantityStock').replace('{max}', maxQuantity.value)
])

const priceRules = computed(() => [
  val => (val !== '' && val !== null && Number(val) >= 0) || t('rulePriceValid'),
  val => Number(val) <= MAX_PRICE || t('rulePriceMax')
])

// The total only shows once both fields pass their checks, so an out-of-range entry never prints a meaningless figure.
const estimateText = computed(() => {
  const valid = [...quantityRules.value.map(rule => rule(manualForm.quantity)), ...priceRules.value.map(rule => rule(manualForm.unitPrice))].every(result => result === true)
  return valid ? `₱${formatNumber(estimatedTotal.value)}` : '—'
})

const inventoryData = ref([])
const inventoryOptions = ref([])

const filterInventory = (val, update) => {
  update(() => {
    const needle = val.toLowerCase()
    inventoryOptions.value = needle ? inventoryData.value.filter(v => v.product_name.toLowerCase().includes(needle)) : inventoryData.value
  })
}

const onProductSelected = val => {
  manualForm.unitPrice = val ? val.price || 0 : 0
}

// The form's own rules run first, then the sale is confirmed before it is saved.
const askToRecord = () => {
  confirmOpen.value = true
}

const recordSale = async () => {
  submitting.value = true
  try {
    await api.post('/vendor/sales/manual', {
      inventory_id: manualForm.product.inventory_id,
      quantity: manualForm.quantity,
      unit_price: manualForm.unitPrice,
      total_amount: estimatedTotal.value,
      sale_date: selectedDate.value ? selectedDate.value.replace(/\//g, '-') : date.formatDate(Date.now(), 'YYYY-MM-DD')
    })
    $q.notify({ type: 'positive', message: t('notifySaleRecorded'), position: 'top-right' })
    confirmOpen.value = false
    showMobileManualModal.value = false
    manualForm.product = null
    manualForm.quantity = 1
    manualForm.unitPrice = 0
    // Clearing the form shouldn't flag the empty product field as an error.
    nextTick(() => {
      asideForm.value?.resetValidation()
      sheetForm.value?.resetValidation()
    })
    await fetchSalesData()
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || t('notifySaleFailed'), position: 'top-right' })
  } finally {
    submitting.value = false
  }
}

let lastRequest = 0

const fetchSalesData = async () => {
  const requestId = ++lastRequest
  const requestedDate = selectedDate.value
  loading.value = true
  try {
    const day = requestedDate ? requestedDate.replace(/\//g, '-') : null
    const requestParams = day ? { start_date: day, end_date: day } : {}

    const [metricsRes, transRes, invRes] = await Promise.all([
      api.get('/vendor/sales/metrics', { params: requestParams }),
      api.get('/vendor/sales/transactions', { params: requestParams }),
      api.get('/vendor/products')
    ])

    // A slower answer for a date that is no longer chosen is dropped.
    if (requestId !== lastRequest) return

    if (metricsRes.data) {
      metrics.revenue = metricsRes.data.revenue || 0
      metrics.growthRate = metricsRes.data.growth_rate || null
      metrics.avgOrderValue = metricsRes.data.avg_order_value || 0
      metrics.cancellationRate = metricsRes.data.cancellation_rate || 0
      metrics.bestSellingCategory = metricsRes.data.best_selling_category || null
    }

    transactions.value = transRes.data || []
    inventoryData.value = invRes.data || []
    shownDate.value = requestedDate
    page.value = 1
  } catch (error) {
    console.error('Failed to load sales data', error)
  } finally {
    if (requestId === lastRequest) loading.value = false
  }
}

watch(selectedDate, fetchSalesData)

onMounted(fetchSalesData)
</script>

<style scoped>
.sr-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 330px;
  align-items: start;

  gap: var(--sp-gap);
}

.sr-main {
  display: flex;
  flex-direction: column;

  gap: 16px;
  min-width: 0;
}

.sr-stats {
  margin-bottom: 0;
}

.sr-hero {
  position: relative;

  display: flex;
  align-items: flex-end;
  justify-content: space-between;

  gap: 24px;
  padding: 22px 24px 24px;
  overflow: hidden;

  border-radius: var(--r-surface);

  background:
    radial-gradient(rgba(255, 255, 255, 0.09) 1px, transparent 1px) 0 0 / 16px 16px,
    linear-gradient(145deg, var(--c-brand) 0%, var(--c-brand-deep) 60%, var(--c-brand-active) 100%);
  color: #ffffff;

  box-shadow: var(--sh-header);
}

.sr-hero-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;

  gap: 8px;
}

.sr-eyebrow {
  display: inline-flex;
  align-items: center;

  gap: 6px;
  padding: 5px 12px;

  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.14);

  font-size: var(--fs-xs);
  font-weight: 600;
}

.sr-growth {
  display: inline-flex;
  align-items: center;

  gap: 4px;
  padding: 5px 10px;

  border-radius: var(--r-pill);

  background: #ffffff;

  font-size: var(--fs-xs);
  font-weight: 700;

  color: var(--c-success);
}

.sr-hero-value {
  margin-top: 16px;

  font-family: 'Poppins', 'Roboto', Arial, sans-serif;
  font-size: 40px;
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -0.01em;
}

.sr-hero-main {
  position: relative;
  z-index: 1;

  flex: 1;
  min-width: 0;
}

/* Quick facts under the total, as soft white pills. */
.sr-hero-facts {
  display: flex;
  flex-wrap: wrap;

  gap: 8px;
  margin-top: 16px;
}

.sr-fact {
  display: inline-flex;
  align-items: center;

  gap: 6px;
  padding: 5px 12px;

  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.12);

  font-size: var(--fs-xs);
  font-weight: 600;
  white-space: nowrap;
}

/* A small gold bar chart of the records behind the total. */
.sr-hero-chart {
  position: relative;
  z-index: 1;
  flex-shrink: 0;

  width: 280px;
  max-width: 42%;
}

.sr-bars {
  display: flex;
  align-items: flex-end;

  gap: 4px;
  height: 104px;
  padding: 10px 10px 0;

  border: 1px solid rgba(255, 255, 255, 0.16);
  border-radius: var(--r-control);

  background: rgba(0, 0, 0, 0.14);
}

.sr-bar {
  flex: 1;

  min-width: 4px;

  border-radius: 3px 3px 0 0;

  background: linear-gradient(180deg, #fde68a 0%, #f59e0b 100%);

  opacity: 0.92;

  transition: opacity 0.15s;
}

.sr-bar:hover {
  opacity: 1;
}

.sr-bars-label {
  margin-top: 6px;

  font-size: var(--fs-2xs);
  font-weight: 600;
  text-align: right;

  color: rgba(255, 255, 255, 0.72);
}

.sr-hero-art {
  position: absolute;
  right: 28px;
  bottom: -14px;

  font-size: 150px;

  color: rgba(255, 255, 255, 0.1);

  pointer-events: none;
}

.sr-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;
  padding: 16px 20px;

  border-bottom: 1px solid var(--c-hairline);
}

.sr-card-title {
  font-size: var(--fs-lg);
  font-weight: 700;

  color: var(--c-text);
}

.sr-card-sub {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.sr-count {
  padding: 2px 10px;

  border-radius: var(--r-pill);

  background: var(--c-surface);

  font-size: var(--fs-2xs);
  font-weight: 700;

  color: var(--c-text-3);
}

.sr-count-sk {
  border-radius: var(--r-pill);
}

/* The placeholder rows are as tall as the records table's, so nothing jumps when the real rows arrive. */
.sr-skeleton :deep(.sk-table td) {
  padding-block: 14px;
}

.sr-product {
  overflow: hidden;

  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text);
}

.sr-table {
  min-width: 560px;
}

/* Larger type than the other tables, since this is where the money gets read. */
.sr-table {
  font-size: var(--fs-md);
}

.sr-table th {
  font-size: var(--fs-xs);
}

.sr-table td {
  padding-block: 14px;
}

.sr-table .vp-amount {
  font-size: var(--fs-lg);
}

.sr-table .vp-id,
.sr-table .vp-status {
  font-size: var(--fs-sm);
}

.sr-list-item .vp-name {
  font-size: var(--fs-md);
}

.sr-list-item .vp-list-meta {
  font-size: var(--fs-sm);
}

.sr-list-item .vp-amount {
  font-size: var(--fs-lg);
}

.sr-pager-right {
  display: flex;
  align-items: center;

  margin-left: auto;
}

.sr-page-size {
  display: flex;
  align-items: center;

  gap: 8px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.sr-page-select {
  width: 76px;
}

.sr-table .col-order { width: 14%; }
.sr-table .col-items { width: 11%; }
.sr-table .col-total { width: 16%; }
.sr-table .col-status { width: 20%; }
.sr-table .col-sold { width: 30%; }
.sr-table .col-revenue { width: 30%; }

.sr-list-item {
  display: flex;
  align-items: center;

  gap: 12px;
  padding: 12px 8px;

  border-bottom: 1px solid var(--c-hairline);
}

.sr-list-item:last-child {
  border-bottom: none;
}

.sr-entry {
  position: sticky;
  top: 24px;

  overflow: hidden;
}

.sr-entry-head {
  position: relative;

  display: flex;
  align-items: center;

  gap: 12px;
  padding: 18px 20px;

  border-bottom: 1px solid var(--c-hairline);
}

.sr-locked {
  display: flex;
  flex-direction: column;
  align-items: center;

  gap: 6px;
  padding: 36px 20px;

  text-align: center;
}

.sr-form {
  display: flex;
  flex-direction: column;

  gap: 14px;
  padding: 18px 20px 20px;
}

.sr-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;

  gap: 12px;
}

.sr-req {
  color: var(--c-brand);
}

.sr-no-option {
  font-style: italic;

  color: var(--c-muted);
}

.sr-estimate {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 8px;
  padding: 12px 14px;

  border-radius: var(--r-control);

  background: var(--c-brand-tint);

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text-2);
}

.sr-estimate strong {
  font-size: var(--fs-xl);

  color: var(--c-brand);
}

/* A long total wraps inside the box instead of running past its edge. */
.sr-estimate {
  flex-wrap: wrap;
}

.sr-estimate span {
  white-space: nowrap;
}

.sr-estimate strong {
  flex: 1 1 auto;

  min-width: 0;

  text-align: right;
  overflow-wrap: anywhere;
}

.sr-submit {
  width: 100%;
  height: 44px;
}

.sr-calendar {
  width: 300px;
  max-width: calc(100vw - 32px);

  background: #ffffff;
}

.sr-date {
  width: 100%;
}

.sr-calendar-foot {
  display: flex;

  gap: 6px;
  padding: 8px 10px;

  border-top: 1px solid var(--c-hairline);

  background: var(--c-surface-2);
}

.sr-quick {
  padding: 4px 12px;

  border-radius: var(--r-pill);

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-3);
}

.sr-quick--on {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

/* Record a Sale below desktop: a centred card, rounded all round like the page's other dialogs. */
.sr-dialog {
  width: 440px;
  max-width: calc(100vw - 32px);

  border-radius: var(--r-surface) !important;

  box-shadow: var(--sh-pop);
}

/* Leaves room for the close button in the corner. */
.sr-dialog-head {
  padding-right: 52px;
}

/* Below desktop the records take the full width and the entry form moves to a dialog. */
@media (max-width: 1439px) {
  .sr-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 600px) {
  .sr-hero {
    padding: 18px 18px 22px;
  }

  .sr-hero-value {
    font-size: 32px;
  }

  .sr-hero {
    flex-direction: column;
    align-items: stretch;
  }

  .sr-hero-chart {
    width: 100%;
    max-width: none;
  }

  .sr-bars {
    height: 80px;
  }

  .sr-hero-art {
    display: none;
  }

  .sr-card-head {
    padding: 14px 16px;
  }

  .vp-header-actions {
    width: 100%;
  }

  .vp-header-actions .q-btn {
    flex: 1;
  }
}
</style>