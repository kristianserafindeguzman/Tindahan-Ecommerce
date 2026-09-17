<template>
  <q-page class="vp-page">
    <div class="vp-container">

      <div class="vp-header">
        <div>
          <h1 class="vp-title">{{ t('title') }}</h1>
          <p class="vp-subtitle">{{ t('subtitle') }}</p>
        </div>
        <div class="vp-header-actions">
          <q-btn no-caps unelevated :loading="insightsRefreshing" class="pl-refresh-btn" @click="refreshInsights">
            <span class="pl-refresh-icon"><q-icon name="o_refresh" size="16px" /></span>
            <span class="pl-refresh-label">{{ t('refreshInsights') }}</span>
            <template #loading>
              <span class="pl-refresh-icon pl-refresh-icon--spin"><q-icon name="o_refresh" size="16px" /></span>
              <span class="pl-refresh-label">{{ t('refreshingInsights') }}</span>
            </template>
          </q-btn>
          <q-btn outline no-caps color="primary" icon="o_download" :label="t('exportBtn')" class="vp-pill-btn" @click="openExportWizard" />
          <q-btn unelevated no-caps color="primary" icon="add" :label="t('addBtn')" class="vp-primary-btn" @click="showAddModal = true" />
        </div>
      </div>

      <!-- Forecast insights from the demand model. -->
      <div class="vp-stats vp-stats--insights">
        <div v-for="card in insightCards" :key="card.key" class="vp-card vp-stat">
          <div class="vp-stat-top">
            <span class="vp-stat-label">{{ card.label }}</span>
            <span class="vp-stat-icon" :class="`vp-tone--${card.tone}`"><q-icon :name="card.icon" size="20px" /></span>
          </div>
          <div class="vp-stat-value vp-stat-value--text">{{ card.value }}</div>
          <div class="vp-stat-notes">
            <span v-for="note in card.notes" :key="note.text" class="vp-stat-note" :class="`vp-tone--${note.tone || card.tone}`">
              <q-icon :name="note.icon" size="14px" />
              {{ note.text }}
            </span>
          </div>
        </div>
      </div>

      <div class="vp-card">
        <div class="vp-toolbar">
          <div class="pl-search-row">
            <q-input
              v-model="search"
              outlined
              dense
              clearable
              clear-icon="o_close"
              hide-bottom-space
              :placeholder="t('searchPlaceholder')"
              class="vp-search"
            >
              <template #prepend>
                <q-icon name="o_search" size="18px" />
              </template>
            </q-input>

            <!-- A dropdown on wide screens and a bottom sheet on phones, the same as the order lists. -->
            <FilterSheet :count="activeFilterCount" :result-count="filteredProducts.length" :noun="t('product')" @clear="resetFilters">
              <div class="pl-filter-panel">
                <div>
                  <label class="vp-field-label">{{ t('filterCategory') }}</label>
                  <q-select v-model="filters.category" :options="categorySelectOptions" emit-value map-options outlined dense options-dense behavior="menu" class="vp-input" />
                </div>
                <div>
                  <label class="vp-field-label">{{ t('filterStock') }}</label>
                  <q-select v-model="filters.stock" :options="localizedStockOptions" emit-value map-options outlined dense options-dense behavior="menu" class="vp-input" />
                </div>
                <div>
                  <label class="vp-field-label">{{ t('filterPrice') }}</label>
                  <q-select v-model="filters.priceSort" :options="localizedPriceOptions" emit-value map-options outlined dense options-dense behavior="menu" class="vp-input" />
                </div>
              </div>
            </FilterSheet>
          </div>

          <div
            ref="chipRow"
            class="vp-chips vp-chips--scroll"
            :class="{ 'vp-chips--more-left': chipFade.left, 'vp-chips--more-right': chipFade.right }"
            role="tablist"
            aria-label="Filter products by status"
            @scroll.passive="updateChipFade"
          >
            <button
              v-for="filter in localizedStatusFilters"
              :key="filter.key"
              type="button"
              role="tab"
              class="vp-chip"
              :class="{ 'vp-chip--active': filters.status === filter.key }"
              :aria-selected="filters.status === filter.key"
              @click="filters.status = filter.key"
            >
              {{ filter.label }}
              <span class="vp-chip-count">{{ statusCount(filter.key) }}</span>
            </button>
          </div>
        </div>

        <SkeletonTable v-if="loading" :columns="SKELETON_COLUMNS" :list="$q.screen.lt.md" thumb />

        <div v-else-if="!filteredProducts.length" class="vp-empty">
          <div class="vp-empty-icon"><q-icon name="o_inventory_2" size="24px" /></div>
          <div class="vp-empty-title">{{ products.length ? t('noMatchTitle') : t('emptyTitle') }}</div>
          <div class="vp-empty-text">
            {{ products.length ? t('noMatchDesc') : t('emptyDesc') }}
          </div>
          <q-btn v-if="!products.length" unelevated no-caps color="primary" icon="add" :label="t('addBtn')" class="vp-primary-btn pl-empty-btn" @click="showAddModal = true" />
        </div>

        <!-- A table on wide screens; a row opens the product. -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table pl-table">
            <thead>
              <tr>
                <th>{{ t('colProduct') }}</th>
                <th class="col-cat">{{ t('colCategory') }}</th>
                <th class="col-stock">{{ t('colStock') }}</th>
                <th class="text-right col-price">{{ t('colPrice') }}</th>
                <th class="col-status">{{ t('colStatus') }}</th>
                <th class="col-act"><span class="vp-sr-only">Actions</span></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="product in pagedProducts"
                :key="product.inventory_id"
                class="vp-row"
                tabindex="0"
                @click="viewProduct(product)"
                @keydown.enter="viewProduct(product)"
              >
                <td>
                  <div class="vp-person">
                    <span class="pl-thumb">
                      <img v-if="product.image_url" :src="product.image_url" alt="" />
                      <q-icon v-else name="o_image" size="20px" />
                    </span>
                    <span class="vp-name">{{ product.product_name }}</span>
                  </div>
                </td>
                <td class="vp-muted pl-ellipsis">{{ product.category?.category_name || t('uncategorized') }}</td>
                <td>
                  <div class="pl-stock-row">
                    <span class="pl-stock" :class="stockClass(product)">{{ product.available_quantity }}</span>
                    <span class="pl-stock-total">{{ t('ofWord') }} {{ product.stock_quantity }}</span>
                  </div>
                </td>
                <td class="text-right vp-amount">
                  <span v-if="product.variants?.length" class="pl-from">{{ t('fromWord') }} </span>₱{{ formatNumber(product.price) }}
                </td>
                <td><span class="vp-status" :class="`vp-status--${productTone(product.status)}`">{{ formatStatus(product.status) }}</span></td>
                <td class="text-right" @click.stop @keydown.stop>
                  <q-btn flat round dense icon="o_more_vert" class="vp-open-btn" :aria-label="`Actions for ${product.product_name}`">
                    <q-menu anchor="bottom right" self="top right" auto-close>
                      <q-list class="vp-menu-list">
                        <q-item v-for="action in rowActions(product)" :key="action.label" clickable :class="{ 'vp-menu-item--danger': action.danger }" @click="action.run">
                          <q-item-section avatar><q-icon :name="action.icon" size="18px" /></q-item-section>
                          <q-item-section>{{ action.label }}</q-item-section>
                        </q-item>
                      </q-list>
                    </q-menu>
                  </q-btn>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- A tappable list on phones, with the actions behind the three dots. -->
        <div v-else class="vp-list">
          <div
            v-for="product in pagedProducts"
            :key="product.inventory_id"
            class="vp-list-item"
            role="button"
            tabindex="0"
            @click="viewProduct(product)"
            @keydown.enter.self="viewProduct(product)"
          >
            <span class="pl-thumb pl-thumb--lg">
              <img v-if="product.image_url" :src="product.image_url" alt="" />
              <q-icon v-else name="o_image" size="22px" />
            </span>
            <div class="vp-list-body">
              <span class="vp-name">{{ product.product_name }}</span>
              <div class="vp-list-meta">
                {{ product.category?.category_name || t('uncategorized') }} · 
                <span class="pl-stock-row pl-stock-row--inline">
                  <span :class="stockClass(product)">{{ product.available_quantity }}</span>
                  <span>{{ t('ofWord') }} {{ product.stock_quantity }} {{ t('leftWord') }}</span>
                </span>
              </div>
              <div class="pl-list-bottom">
                <span class="vp-amount">₱{{ formatNumber(product.price) }}</span>
                <span class="vp-status" :class="`vp-status--${productTone(product.status)}`">{{ formatStatus(product.status) }}</span>
              </div>
            </div>
            <q-btn flat round dense icon="o_more_vert" class="vp-open-btn" :aria-label="`Actions for ${product.product_name}`" @click.stop>
              <q-menu anchor="bottom right" self="top right" auto-close>
                <q-list class="vp-menu-list">
                  <q-item v-for="action in rowActions(product)" :key="action.label" clickable :class="{ 'vp-menu-item--danger': action.danger }" @click="action.run">
                    <q-item-section avatar><q-icon :name="action.icon" size="18px" /></q-item-section>
                    <q-item-section>{{ action.label }}</q-item-section>
                  </q-item>
                </q-list>
              </q-menu>
            </q-btn>
          </div>
        </div>

        <div v-if="!loading && pageCount > 1" class="vp-pager">
          <span>{{ t('showingWord') }} {{ rangeStart }}–{{ rangeEnd }} {{ t('ofWord') }} {{ filteredProducts.length }}</span>
          <div class="vp-pager-btns">
            <q-btn outline no-caps color="primary" icon="o_chevron_left" class="vp-pill-btn" aria-label="Previous page" :disable="page === 1" @click="page--" />
            <q-btn outline no-caps color="primary" icon="o_chevron_right" class="vp-pill-btn" aria-label="Next page" :disable="page === pageCount" @click="page++" />
          </div>
        </div>
      </div>

    </div>

    <AddProductModal v-model="showAddModal" @refresh="fetchProducts" />
    <ProductDetailsModal v-model="showDetailsModal" :product="selectedProduct" @refresh="fetchProducts" />

    <!-- Deactivate or delete, in the Log out dialog's layout: everything centred, the icon above the title, and a line above two equal buttons. -->
    <q-dialog v-model="confirm.open" persistent>
      <q-card class="vp-dialog pl-confirm">
        <div class="pl-confirm-body">
          <span class="vp-dialog-icon vp-dialog-icon--danger pl-confirm-icon">
            <q-icon :name="confirm.kind === 'delete' ? 'o_delete' : 'o_block'" size="24px" />
          </span>
          <div class="pl-confirm-title">{{ confirm.kind === 'delete' ? t('confirmDeleteTitle') : t('confirmDeactivateTitle') }}</div>
          <p class="pl-confirm-text">
            <template v-if="confirm.kind === 'delete'">
              <strong>{{ confirm.product?.product_name }}</strong> {{ t('confirmDeleteDesc') }}
            </template>
            <template v-else>
              {{ t('confirmDeactivateDesc1') }} <strong>{{ confirm.product?.product_name }}</strong> {{ t('confirmDeactivateDesc2') }}
            </template>
          </p>
        </div>
        <q-separator class="pl-confirm-sep" />
        <div class="pl-confirm-actions">
          <q-btn v-close-popup outline no-caps color="primary" :label="t('cancelBtn')" class="vp-dialog-btn" :disable="confirm.busy" />
          <q-btn
            unelevated
            no-caps
            color="primary"
            :label="confirm.kind === 'delete' ? t('confirmDeleteBtn') : t('confirmDeactivateBtn')"
            class="vp-dialog-btn"
            :loading="confirm.busy"
            @click="runConfirm"
          />
        </div>
      </q-card>
    </q-dialog>

    <q-dialog v-model="showExportModal" persistent>
      <q-card class="vp-dialog vp-dialog--wide">
        <div class="vp-dialog-head">
          <span class="vp-dialog-icon"><q-icon name="o_download" size="22px" /></span>
          <div>
            <div class="vp-dialog-title">{{ t('exportWizardTitle') }}</div>
            <div class="vp-dialog-text">
              {{ exportStep === 1 ? t('exportWizardDesc1') : t('exportWizardDesc2') }}
            </div>
          </div>
          <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" :disable="isExporting" />
        </div>

        <div class="vp-dialog-body">
          <div v-if="exportStep === 1" class="pl-format-grid" role="radiogroup" aria-label="Export format">
            <button
              v-for="format in localizedExportFormats"
              :key="format.value"
              type="button"
              role="radio"
              class="pl-format"
              :class="{ 'pl-format--active': exportFormat === format.value }"
              :aria-checked="exportFormat === format.value"
              @click="exportFormat = format.value"
            >
              <span class="pl-format-icon"><q-icon :name="format.icon" size="24px" /></span>
              <span class="pl-format-title">{{ format.title }}</span>
              <span class="pl-format-sub">{{ format.sub }}</span>
            </button>
          </div>

          <div v-else class="pl-summary">
            <div class="pl-summary-row"><span>{{ t('exportSummaryFormat') }}</span><strong>{{ exportFormat === 'pdf' ? t('formatPdfTitle') : t('formatImageTitle') }}</strong></div>
            <div class="pl-summary-row"><span>{{ t('exportSummaryProducts') }}</span><strong>{{ filteredProducts.length }}</strong></div>
          </div>
        </div>

        <div class="vp-dialog-actions">
          <template v-if="exportStep === 1">
            <q-btn v-close-popup outline no-caps color="primary" :label="t('cancelBtn')" class="vp-dialog-btn" />
            <q-btn unelevated no-caps color="primary" :label="t('nextBtn')" class="vp-dialog-btn" @click="proceedToPreview(exportFormat)" />
          </template>
          <template v-else>
            <q-btn outline no-caps color="primary" :label="t('backBtn')" class="vp-dialog-btn" :disable="isExporting" @click="exportStep = 1" />
            <q-btn unelevated no-caps color="primary" :label="t('downloadBtn')" class="vp-dialog-btn" :loading="isExporting" @click="executeFinalExport" />
          </template>
        </div>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, reactive, nextTick, watch } from 'vue'
import html2canvas from 'html2canvas'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import { useLanguage } from '@/composables/useLanguage'

import AddProductModal from '@/components/modals/AddProductModal.vue'
import ProductDetailsModal from '@/components/modals/ProductDetailsModal.vue'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'
import FilterSheet from '@/components/vendor/FilterSheet.vue'

const $q = useQuasar()

const productListDict = {
  en: {
    title: 'Product List',
    subtitle: 'Monitor and update your product catalog.',
    exportBtn: 'Export',
    addBtn: 'Add Product',
    refreshInsights: 'Refresh Insights',
    refreshingInsights: 'Refreshing…',
    insightRestockAlert: 'Restock alert',
    insightUpcomingTrend: 'Upcoming trend',
    insightTopPerformer: 'Top performer',
    insightAnalyzing: 'Analyzing inventory…',
    insightGathering: 'Gathering data…',
    insightCalculating: 'Calculating…',
    insightNotEnoughData: 'Not enough data yet',
    insightExpectedDemand: 'Expected {x}× demand',
    insightSeason: 'Season',
    insightStockOutPlural: 'Stock-out in about {x} days',
    insightStockOutSingular: 'Stock-out in about {x} day',
    insightNoStockOut: 'No stock-out predicted yet',
    insightHighestRevenue: 'Highest revenue this week',
    insightAwaitingData: 'Awaiting more data',
    insightNA: 'N/A',
    searchPlaceholder: 'Search products',
    filterCategory: 'Category',
    filterStock: 'Stock level',
    filterPrice: 'Sort by price',
    optAllCategories: 'All categories',
    optAllStock: 'All stock levels',
    optLowStock: 'Low stock (under 10)',
    optDefaultOrder: 'Default order',
    optLowToHigh: 'Low to high',
    optHighToLow: 'High to low',
    statusAll: 'All',
    statusActive: 'Active',
    statusDeactivated: 'Deactivated',
    statusArchived: 'Archived',
    product: 'product',
    noMatchTitle: 'No matching products',
    emptyTitle: 'No products yet',
    noMatchDesc: 'Try another search, status or filter.',
    emptyDesc: 'Add your first product so customers can order it.',
    colProduct: 'Product',
    colCategory: 'Category',
    colStock: 'Stock',
    colPrice: 'Price',
    colStatus: 'Status',
    uncategorized: 'Uncategorized',
    ofWord: 'of',
    leftWord: 'left',
    fromWord: 'from',
    showingWord: 'Showing',
    actionView: 'View',
    actionDeactivate: 'Deactivate',
    actionDelete: 'Delete',
    confirmDeleteTitle: 'Delete this product?',
    confirmDeactivateTitle: 'Deactivate this product?',
    confirmDeleteDesc: 'will be removed for good. This can\'t be undone.',
    confirmDeactivateDesc1: 'Customers won\'t be able to buy',
    confirmDeactivateDesc2: 'until you turn it back on.',
    cancelBtn: 'Cancel',
    confirmDeleteBtn: 'Delete Product',
    confirmDeactivateBtn: 'Deactivate',
    notifyProductDeleted: 'Product deleted.',
    notifyProductDeactivated: 'Product deactivated.',
    notifyFailedAction: 'Failed to {kind} the product.',
    exportWizardTitle: 'Export inventory',
    exportWizardDesc1: 'Choose a format for the inventory report.',
    exportWizardDesc2: 'Your report is ready to generate.',
    formatPdfTitle: 'PDF document',
    formatPdfSub: 'Printable A4 report',
    formatImageTitle: 'Image snapshot',
    formatImageSub: 'Quick shareable image',
    exportSummaryFormat: 'Format',
    exportSummaryProducts: 'Products',
    nextBtn: 'Next',
    backBtn: 'Back',
    downloadBtn: 'Download',
    notifyExportPdfFailed: 'Failed to generate the PDF report.',
    notifyExportImgFailed: 'Failed to generate the image report.'
  },
  ph: {
    title: 'Listahan ng Paninda',
    subtitle: 'Bantayan at i-update ang iyong mga paninda.',
    exportBtn: 'I-export',
    addBtn: 'Magdagdag',
    refreshInsights: 'I-refresh',
    refreshingInsights: 'Nire-refresh…',
    insightRestockAlert: 'Restock alert',
    insightUpcomingTrend: 'Bagong trend',
    insightTopPerformer: 'Mataas ang benta',
    insightAnalyzing: 'Sinusuri ang inventory…',
    insightGathering: 'Nangongolekta ng data…',
    insightCalculating: 'Kinakalkula…',
    insightNotEnoughData: 'Wala pang sapat na data',
    insightExpectedDemand: 'Inaasahang {x}× na demand',
    insightSeason: 'Panahon',
    insightStockOutPlural: 'Mauubos sa loob ng {x} araw',
    insightStockOutSingular: 'Mauubos sa loob ng {x} araw',
    insightNoStockOut: 'Walang hula na maubusan ng stock',
    insightHighestRevenue: 'Pinakamataas na kita ngayong linggo',
    insightAwaitingData: 'Naghahantay pa ng data',
    insightNA: 'N/A',
    searchPlaceholder: 'Hanapin ang paninda',
    filterCategory: 'Kategorya',
    filterStock: 'Dami ng Stock',
    filterPrice: 'I-sort sa presyo',
    optAllCategories: 'Lahat ng kategorya',
    optAllStock: 'Lahat ng dami ng stock',
    optLowStock: 'Paubos na (mababa sa 10)',
    optDefaultOrder: 'Default order',
    optLowToHigh: 'Mababa pataas',
    optHighToLow: 'Mataas pababa',
    statusAll: 'Lahat',
    statusActive: 'Active',
    statusDeactivated: 'Naka-deactivate',
    statusArchived: 'Naka-archive',
    product: 'paninda',
    noMatchTitle: 'Walang nahanap na paninda',
    emptyTitle: 'Wala pang paninda',
    noMatchDesc: 'Subukang ibahin ang search, status o filter.',
    emptyDesc: 'Ilagay ang iyong unang paninda para maka-order ang customers.',
    colProduct: 'Paninda',
    colCategory: 'Kategorya',
    colStock: 'Stock',
    colPrice: 'Presyo',
    colStatus: 'Status',
    uncategorized: 'Walang Kategorya',
    ofWord: 'mula sa',
    leftWord: 'na natitira',
    fromWord: 'mula',
    showingWord: 'Pinapakita',
    actionView: 'Tingnan',
    actionDeactivate: 'I-deactivate',
    actionDelete: 'Burahin',
    confirmDeleteTitle: 'Burahin ang panindang ito?',
    confirmDeactivateTitle: 'I-deactivate ang panindang ito?',
    confirmDeleteDesc: 'ay mabubura nang tuluyan. Hindi na ito maibabalik.',
    confirmDeactivateDesc1: 'Hindi na mabibili ang',
    confirmDeactivateDesc2: 'hangga\'t hindi mo binabalik.',
    cancelBtn: 'I-cancel',
    confirmDeleteBtn: 'Burahin',
    confirmDeactivateBtn: 'I-deactivate',
    notifyProductDeleted: 'Nabura na ang paninda.',
    notifyProductDeactivated: 'Na-deactivate na ang paninda.',
    notifyFailedAction: 'Failed ma-{kind} ang paninda.',
    exportWizardTitle: 'I-export ang inventory',
    exportWizardDesc1: 'Pumili ng format para sa report.',
    exportWizardDesc2: 'Handa nang i-generate ang report mo.',
    formatPdfTitle: 'PDF document',
    formatPdfSub: 'A4 report na pwede i-print',
    formatImageTitle: 'Image snapshot',
    formatImageSub: 'Mabilis ma-share na picture',
    exportSummaryFormat: 'Format',
    exportSummaryProducts: 'Mga Paninda',
    nextBtn: 'Next',
    backBtn: 'Bumalik',
    downloadBtn: 'I-download',
    notifyExportPdfFailed: 'Failed ma-generate ang PDF report.',
    notifyExportImgFailed: 'Failed ma-generate ang image report.'
  }
}

const { t, lang } = useLanguage(productListDict)

// Reactive filters/options so they switch instantly
const localizedStatusFilters = computed(() => [
  { key: 'all', label: t('statusAll') },
  { key: 'active', label: t('statusActive') },
  { key: 'deactivated', label: t('statusDeactivated') },
  { key: 'archived', label: t('statusArchived') }
])

const localizedStockOptions = computed(() => [
  { label: t('optAllStock'), value: 'all' },
  { label: t('optLowStock'), value: 'low_stock' }
])

const localizedPriceOptions = computed(() => [
  { label: t('optDefaultOrder'), value: 'default' },
  { label: t('optLowToHigh'), value: 'low_to_high' },
  { label: t('optHighToLow'), value: 'high_to_low' }
])

const localizedExportFormats = computed(() => [
  { value: 'pdf', icon: 'o_picture_as_pdf', title: t('formatPdfTitle'), sub: t('formatPdfSub') },
  { value: 'image', icon: 'o_image', title: t('formatImageTitle'), sub: t('formatImageSub') }
])

const PAGE_SIZE = 10

// The placeholder rows take the same columns as the table: photo and name, category, stock, price, status and the menu.
const SKELETON_COLUMNS = [
  { type: 'thumb' },
  { width: '18%', type: 'text' },
  { width: '13%', type: 'text' },
  { width: '14%', type: 'text', align: 'right' },
  { width: '14%', type: 'pill', size: 64 },
  { width: '7%', type: 'icon', align: 'right' }
]

const search = ref('')
const loading = ref(true)
const products = ref([])
const showAddModal = ref(false)
const showDetailsModal = ref(false)
const selectedProduct = ref(null)
const page = ref(1)

const filters = reactive({
  stock: 'all',
  category: 'all',
  status: 'all',
  priceSort: 'default'
})

const mlInsights = ref({
  restockProduct: null,
  daysUntilStockout: null,
  trendingCategory: null,
  trendMultiplier: null,
  topCategory: null,
  currentSeason: null,
  currentHoliday: null
})

const isNumber = value => value !== null && value !== '' && !Number.isNaN(Number(value))

// Picks an icon that actually matches the season/holiday text from the model,
// instead of always showing a sun regardless of what season it names.
const seasonIcon = (season, holiday) => {
  const text = `${season || ''} ${holiday || ''}`.toLowerCase()
  if (text.includes('rain') || text.includes('ulan') || text.includes('habagat')) return 'o_umbrella'
  if (text.includes('typhoon') || text.includes('bagyo') || text.includes('storm')) return 'o_thunderstorm'
  if (text.includes('school') || text.includes('eskwela') || text.includes('paaralan') || text.includes('klase')) return 'o_school'
  if (text.includes('christmas') || text.includes('pasko')) return 'o_ac_unit'
  if (text.includes('new year') || text.includes('bagong taon')) return 'o_celebration'
  if (text.includes('holy week') || text.includes('semana santa')) return 'o_church'
  if (text.includes('valentine')) return 'o_favorite'
  if (text.includes('harvest') || text.includes('ani')) return 'o_agriculture'
  if (text.includes('summer') || text.includes('tag-init') || text.includes('dry')) return 'o_wb_sunny'
  return 'o_calendar_month'
}

const insightCards = computed(() => {
  const ml = mlInsights.value
  const days = Number(ml.daysUntilStockout)
  const trendNotes = [{ 
    icon: 'o_insights', 
    text: isNumber(ml.trendMultiplier) ? t('insightExpectedDemand').replace('{x}', ml.trendMultiplier) : t('insightNotEnoughData') 
  }]
  if (ml.currentSeason) {
    trendNotes.push({ 
      icon: seasonIcon(ml.currentSeason, ml.currentHoliday), 
      tone: 'success', 
      text: `${t('insightSeason')}: ${ml.currentSeason}${ml.currentHoliday ? ` · ${ml.currentHoliday}` : ''}` 
    })
  }

  return [
    {
      key: 'restock',
      label: t('insightRestockAlert'),
      icon: 'o_warning_amber',
      tone: 'danger',
      value: ml.restockProduct || t('insightAnalyzing'),
      notes: [{ 
        icon: 'o_schedule', 
        text: isNumber(ml.daysUntilStockout) 
          ? (days === 1 ? t('insightStockOutSingular').replace('{x}', days) : t('insightStockOutPlural').replace('{x}', days)) 
          : t('insightNoStockOut') 
      }]
    },
    { 
      key: 'trend', 
      label: t('insightUpcomingTrend'), 
      icon: 'o_trending_up', 
      tone: 'info', 
      value: ml.trendingCategory || t('insightGathering'), 
      notes: trendNotes 
    },
    { 
      key: 'top', 
      label: t('insightTopPerformer'), 
      icon: 'o_emoji_events', 
      tone: 'wait', 
      value: ml.topCategory || t('insightCalculating'), 
      notes: [{ icon: 'o_star_outline', text: t('insightHighestRevenue') }] 
    }
  ]
})

const insightsRefreshing = ref(false)

const refreshInsights = async () => {
  insightsRefreshing.value = true
  try {
    // Trigger the shared forecast refresh
    await api.post('/vendor/demand-forecast/refresh')
    // Then re-fetch insights
    await fetchMlInsights()
  } catch (err) {
    console.error('Failed to refresh insights:', err)
  } finally {
    insightsRefreshing.value = false
  }
}

// Search, category and stock narrow the list first, so each status chip can count what it would show.
const baseProducts = computed(() => {
  const needle = (search.value || '').trim().toLowerCase()
  return products.value.filter(p =>
    (!needle || (p.product_name || '').toLowerCase().includes(needle)) &&
    (filters.stock !== 'low_stock' || p.stock_quantity < 10) &&
    (filters.category === 'all' || p.category_id === filters.category)
  )
})

const statusOf = product => String(product.status || 'active').toLowerCase()
const statusCount = key => baseProducts.value.filter(p => key === 'all' || statusOf(p) === key).length

// On phones the status chips scroll sideways; these flags fade whichever edge still has chips beyond it.
const chipRow = ref(null)
const chipFade = reactive({ left: false, right: false })

const updateChipFade = () => {
  const el = chipRow.value
  if (!el) return
  chipFade.left = el.scrollLeft > 2
  chipFade.right = el.scrollLeft + el.clientWidth < el.scrollWidth - 2
}

// The counts change the chips' widths, so the fade is rechecked whenever they do.
watch(() => localizedStatusFilters.value.map(f => statusCount(f.key)).join(), () => nextTick(updateChipFade))

onMounted(() => {
  nextTick(updateChipFade)
  window.addEventListener('resize', updateChipFade)
})

onBeforeUnmount(() => window.removeEventListener('resize', updateChipFade))

const filteredProducts = computed(() => {
  const result = baseProducts.value.filter(p => filters.status === 'all' || statusOf(p) === filters.status)
  if (filters.priceSort === 'low_to_high') return result.slice().sort((a, b) => (a.price || 0) - (b.price || 0))
  if (filters.priceSort === 'high_to_low') return result.slice().sort((a, b) => (b.price || 0) - (a.price || 0))
  return result
})

const pageCount = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / PAGE_SIZE)))
const pagedProducts = computed(() => filteredProducts.value.slice((page.value - 1) * PAGE_SIZE, page.value * PAGE_SIZE))
const rangeStart = computed(() => (page.value - 1) * PAGE_SIZE + 1)
const rangeEnd = computed(() => Math.min(page.value * PAGE_SIZE, filteredProducts.value.length))

// Any change to what is shown starts back on the first page.
watch(() => [search.value, filters.stock, filters.category, filters.status, filters.priceSort], () => { page.value = 1 })

// A refresh that removes rows keeps the page in range.
watch(pageCount, count => { if (page.value > count) page.value = count })

const activeFilterCount = computed(() => [filters.category !== 'all', filters.stock !== 'all', filters.priceSort !== 'default'].filter(Boolean).length)

const resetFilters = () => {
  filters.stock = 'all'
  filters.category = 'all'
  filters.priceSort = 'default'
}

const categoryOptions = computed(() => {
  const cats = new Map()
  products.value.forEach(p => {
    if (p.category) cats.set(p.category_id, p.category.category_name)
  })
  return Array.from(cats, ([value, label]) => ({ value, label }))
})

const categorySelectOptions = computed(() => [{ label: t('optAllCategories'), value: 'all' }, ...categoryOptions.value])

const formatStatus = status => {
  const s = String(status || 'active').toLowerCase()
  if (s === 'active') return t('statusActive')
  if (s === 'deactivated') return t('statusDeactivated')
  if (s === 'archived') return t('statusArchived')
  return s.charAt(0).toUpperCase() + s.slice(1)
}

const productTone = status => {
  switch (String(status || 'active').toLowerCase()) {
    case 'active': return 'success'
    case 'deactivated':
    case 'inactive': return 'danger'
    case 'out of stock': return 'warning'
    default: return 'neutral'
  }
}

const stockClass = product => {
  if (Number(product.available_quantity) <= 0) return 'pl-stock--out'
  if (Number(product.stock_quantity) < 10) return 'pl-stock--low'
  return ''
}

const formatNumber = num => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const fetchProducts = async () => {
  loading.value = true
  try {
    const res = await api.get('/vendor/products')
    products.value = res.data || []
  } catch (error) {
    console.error('Failed to load products', error)
  } finally {
    loading.value = false
  }
}

const viewProduct = product => {
  selectedProduct.value = product
  showDetailsModal.value = true
}

const confirm = reactive({ open: false, kind: 'delete', product: null, busy: false })

const askConfirm = (kind, product) => {
  Object.assign(confirm, { open: true, kind, product, busy: false })
}

const rowActions = product => [
  { label: t('actionView'), icon: 'o_visibility', run: () => viewProduct(product) },
  ...(product.status !== 'deactivated' ? [{ label: t('actionDeactivate'), icon: 'o_block', run: () => askConfirm('deactivate', product) }] : []),
  { label: t('actionDelete'), icon: 'o_delete', danger: true, run: () => askConfirm('delete', product) }
]

const runConfirm = async () => {
  const { kind, product } = confirm
  if (!product) return

  confirm.busy = true
  try {
    if (kind === 'delete') {
      await api.delete(`/vendor/products/${product.inventory_id}`)
      $q.notify({ type: 'positive', message: t('notifyProductDeleted') })
    } else {
      await api.patch(`/vendor/products/${product.inventory_id}/status`, { status: 'deactivated' })
      $q.notify({ type: 'positive', message: t('notifyProductDeactivated') })
    }
    confirm.open = false
    fetchProducts()
  } catch (err) {
    console.error(err)
    $q.notify({ type: 'negative', message: err.response?.data?.message || t('notifyFailedAction').replace('{kind}', kind) })
  } finally {
    confirm.busy = false
  }
}

const isExporting = ref(false)
const showExportModal = ref(false)
const exportStep = ref(1)
const exportFormat = ref('pdf')

const openExportWizard = () => {
  exportStep.value = 1
  exportFormat.value = 'pdf'
  showExportModal.value = true
}

const proceedToPreview = format => {
  exportFormat.value = format
  exportStep.value = 2
}

const executeFinalExport = async () => {
  if (exportFormat.value === 'pdf') {
    try {
      isExporting.value = true
      const response = await api.get('/vendor/inventory/export', { responseType: 'blob' })
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)

      const link = document.createElement('a')
      link.href = url
      link.download = `Tindahan-Inventory-Report-${Date.now()}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)

      setTimeout(() => window.URL.revokeObjectURL(url), 1000)
      showExportModal.value = false
    } catch (error) {
      console.error('PDF Export failed:', error)
      $q.notify({ type: 'negative', message: t('notifyExportPdfFailed') })
    } finally {
      isExporting.value = false
    }
  } else {
    try {
      isExporting.value = true

      const response = await api.get('/vendor/inventory/export-html')
      const htmlContent = response.data.html

      const container = document.createElement('div')
      container.innerHTML = htmlContent
      container.style.position = 'absolute'
      container.style.left = '-9999px'
      container.style.top = '0'
      container.style.width = '840px'
      document.body.appendChild(container)

      await nextTick()

      const images = container.querySelectorAll('img')
      const imagePromises = Array.from(images).map(async img => {
        if (!img.complete) {
          await new Promise(resolve => {
            img.onload = resolve
            img.onerror = resolve
          })
        }
        if (img.decode) {
          try {
            await img.decode()
          } catch {
            // A picture that can't be decoded is left as it is.
          }
        }
      })
      await Promise.all([...imagePromises, document.fonts ? document.fonts.ready : Promise.resolve()])

      // Embedded pictures are redrawn onto canvases so html2canvas captures them.
      container.querySelectorAll('img').forEach(img => {
        if (img.src && img.src.startsWith('data:image')) {
          try {
            const canvas = document.createElement('canvas')
            canvas.width = img.naturalWidth || img.width || 240
            canvas.height = img.naturalHeight || img.height || 160
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height)

            canvas.style.cssText = img.style.cssText
            canvas.className = img.className
            if (img.hasAttribute('width')) canvas.style.width = img.getAttribute('width') + 'px'
            if (img.hasAttribute('height')) canvas.style.height = img.getAttribute('height') + 'px'

            img.parentNode.replaceChild(canvas, img)
          } catch (e) {
            console.warn('Failed to convert image to canvas for export', e)
          }
        }
      })

      const pages = container.querySelectorAll('.page')
      if (pages.length > 0) {
        for (let i = 0; i < pages.length; i++) {
          const canvas = await html2canvas(pages[i], { scale: 2, useCORS: true, allowTaint: true, logging: false, backgroundColor: '#ffffff' })
          const imageLink = document.createElement('a')
          imageLink.download = `inventory-report-page-${i + 1}-${Date.now()}.png`
          imageLink.href = canvas.toDataURL('image/png')
          imageLink.click()
          await new Promise(r => setTimeout(r, 500))
        }
      } else {
        const canvas = await html2canvas(container, { scale: 2, useCORS: true, allowTaint: true, logging: false })
        const imageLink = document.createElement('a')
        imageLink.download = `inventory-report-${Date.now()}.png`
        imageLink.href = canvas.toDataURL('image/png')
        imageLink.click()
      }

      document.body.removeChild(container)
      showExportModal.value = false
    } catch (error) {
      console.error('Detailed Image Export Error:', error)
      $q.notify({ type: 'negative', message: t('notifyExportImgFailed') })
    } finally {
      isExporting.value = false
    }
  }
}

const fetchMlInsights = async () => {
  try {
    const res = await api.get('/vendor/ml-insights')
    if (res.data && res.data.has_insights) {
      mlInsights.value.restockProduct = res.data.restockProduct
      mlInsights.value.daysUntilStockout = res.data.daysUntilStockout
      mlInsights.value.trendingCategory = res.data.trendingCategory
      mlInsights.value.trendMultiplier = res.data.trendMultiplier ?? t('insightNA')
      mlInsights.value.topCategory = res.data.topCategory
      mlInsights.value.currentSeason = res.data.currentSeason ?? null
      mlInsights.value.currentHoliday = res.data.currentHoliday ?? null
    } else {
      mlInsights.value.restockProduct = t('insightAwaitingData')
      mlInsights.value.daysUntilStockout = t('insightNA')
      mlInsights.value.trendingCategory = t('insightAwaitingData')
      mlInsights.value.trendMultiplier = t('insightNA')
      mlInsights.value.topCategory = t('insightAwaitingData')
    }
  } catch (err) {
    console.error('Failed to load ML insights:', err)
  }
}

// A link from Categories carries ?category=, so the list opens already filtered to it.
const route = useRoute()

onMounted(() => {
  const linkedCategory = Number(route.query.category)
  if (Number.isFinite(linkedCategory) && linkedCategory > 0) filters.category = linkedCategory
  fetchProducts()
  fetchMlInsights()
})
</script>

<style scoped>
.pl-search-row {
  display: flex;
  flex: 1 1 360px;
  gap: 8px;
  max-width: 460px;
}

.pl-search-row .vp-search {
  max-width: none;
}

.pl-search-row > .q-btn {
  flex-shrink: 0;
  white-space: nowrap;
}

.pl-filter-panel {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* REFRESH INSIGHTS BUTTON */
.pl-refresh-btn {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  gap: 10px;
  height: 38px;
  margin-right: 6px;
  padding: 0 16px 0 6px;
  border: 1px solid var(--c-brand-tint-2, rgba(101, 16, 18, 0.16));
  border-radius: var(--r-pill, 9999px);
  background: var(--c-brand-tint, rgba(101, 16, 18, 0.05));
  color: var(--c-brand, #651012);
  font-size: var(--fs-sm, 13px);
  font-weight: 600;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.pl-refresh-btn:hover:not(.disabled) {
  background: var(--c-brand-tint-2, rgba(101, 16, 18, 0.1));
  border-color: var(--c-brand, #651012);
  box-shadow: 0 3px 10px rgba(101, 16, 18, 0.12);
  transform: translateY(-1px);
}

.pl-refresh-btn:active:not(.disabled) {
  transform: translateY(0);
}

.pl-refresh-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #ffffff;
  color: var(--c-brand, #651012);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.pl-refresh-icon--spin :deep(.q-icon) {
  animation: pl-spin 0.9s linear infinite;
}

.pl-refresh-label {
  white-space: nowrap;
}

@keyframes pl-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
  .pl-refresh-icon--spin :deep(.q-icon) {
    animation: none;
  }
}

/* STOCK CELL */
.pl-stock-row {
  display: inline-flex;
  align-items: baseline;
  gap: 4px;
}

.pl-stock-row--inline {
  display: inline-flex;
  align-items: baseline;
  gap: 4px;
}

.pl-stock {
  font-weight: 700;
  color: var(--c-text);
}

.pl-stock--low {
  color: var(--c-warning);
}

.pl-stock--out {
  color: var(--c-danger);
}

.pl-stock-total {
  font-size: var(--fs-xs);
  color: var(--c-muted);
}

.pl-from {
  font-size: var(--fs-xs);
  font-weight: 500;
  color: var(--c-muted);
}

.pl-table .col-cat { width: 18%; }
.pl-table .col-stock { width: 13%; }
.pl-table .col-price { width: 14%; }
.pl-table .col-status { width: 14%; }
.pl-table .col-act { width: 7%; }

/* CONFIRM MODAL */
.pl-confirm {
  width: 400px;
}

.pl-confirm-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 28px 28px 22px;
  text-align: center;
}

.pl-confirm-icon {
  margin-bottom: 14px;
}

.pl-confirm-title {
  font-size: 19px;
  font-weight: 700;
  line-height: 1.3;
  color: var(--c-text);
}

.pl-confirm-text {
  margin: 8px 0 0;
  font-size: var(--fs-sm);
  line-height: 1.6;
  color: var(--c-text-3);
}

.pl-confirm-text strong {
  color: var(--c-text);
}

.pl-confirm-sep {
  background: var(--c-hairline);
}

.pl-confirm-actions {
  display: flex;
  gap: 12px;
  padding: 18px 28px 24px;
}

.pl-confirm-actions .vp-dialog-btn {
  flex: 1;
  min-width: 0;
  height: 48px;
}

.pl-thumb {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 44px;
  height: 44px;
  overflow: hidden;
  border: 1px solid var(--c-hairline);
  border-radius: var(--r-control);
  background: var(--c-surface);
  color: var(--c-muted);
}

.pl-thumb img {
  width: 100%;
  height: 100%;
  border-radius: inherit;
  object-fit: cover;
}

.pl-thumb--lg {
  width: 56px;
  height: 56px;
}

.pl-ellipsis {
  overflow: hidden;
  text-overflow: ellipsis;
}

.pl-list-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 6px;
}

.pl-empty-btn {
  margin-top: 10px;
}

.pl-format-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.pl-format {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 18px 12px;
  border: 1.5px solid var(--c-border);
  border-radius: var(--r-surface);
  background: #ffffff;
  font-family: inherit;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.15s, background-color 0.15s;
}

.pl-format:hover {
  border-color: var(--c-border-strong);
}

.pl-format:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

.pl-format--active,
.pl-format--active:hover {
  border-color: var(--c-brand);
  background: var(--c-brand-tint);
}

.pl-format-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  margin-bottom: 2px;
  border-radius: var(--r-surface);
  background: var(--c-surface);
  color: var(--c-muted);
}

.pl-format--active .pl-format-icon {
  background: #ffffff;
  color: var(--c-brand);
}

.pl-format-title {
  font-size: var(--fs-sm);
  font-weight: 700;
  color: var(--c-text);
}

.pl-format-sub {
  font-size: var(--fs-xs);
  color: var(--c-muted);
}

.pl-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 14px 16px;
  border: 1px solid var(--c-border);
  border-radius: var(--r-control);
  background: var(--c-surface-2);
}

.pl-summary-row {
  display: flex;
  justify-content: space-between;
  font-size: var(--fs-sm);
  color: var(--c-text-3);
}

.pl-summary-row strong {
  color: var(--c-text);
}

@media (max-width: 600px) {
  .vp-header-actions {
    width: 100%;
  }

  .vp-header-actions .q-btn {
    flex: 1;
  }

  .pl-refresh-btn {
    flex: 1 1 100% !important;
    order: -1;
    justify-content: center;
    margin-right: 0;
    margin-bottom: 8px;
  }

  .pl-search-row {
    max-width: none;
  }
}
</style>