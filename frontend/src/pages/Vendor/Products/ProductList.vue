<template>
  <q-page class="vp-page">
    <div class="vp-container">

      <div class="vp-header">
        <div>
          <h1 class="vp-title">Product List</h1>
          <p class="vp-subtitle">Monitor and update your product catalog.</p>
        </div>
        <div class="vp-header-actions">
          <q-btn outline no-caps color="primary" icon="o_download" label="Export" class="vp-pill-btn" @click="openExportWizard" />
          <q-btn unelevated no-caps color="primary" icon="add" label="Add Product" class="vp-primary-btn" @click="showAddModal = true" />
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
              placeholder="Search products"
              class="vp-search"
            >
              <template #prepend>
                <q-icon name="o_search" size="18px" />
              </template>
            </q-input>

            <!-- A dropdown on wide screens and a bottom sheet on phones, the same as the order lists. -->
            <FilterSheet :count="activeFilterCount" :result-count="filteredProducts.length" noun="product" @clear="resetFilters">
              <div class="pl-filter-panel">
                <div>
                  <label class="vp-field-label">Category</label>
                  <q-select v-model="filters.category" :options="categorySelectOptions" emit-value map-options outlined dense options-dense behavior="menu" class="vp-input" />
                </div>
                <div>
                  <label class="vp-field-label">Stock level</label>
                  <q-select v-model="filters.stock" :options="STOCK_OPTIONS" emit-value map-options outlined dense options-dense behavior="menu" class="vp-input" />
                </div>
                <div>
                  <label class="vp-field-label">Sort by price</label>
                  <q-select v-model="filters.priceSort" :options="PRICE_OPTIONS" emit-value map-options outlined dense options-dense behavior="menu" class="vp-input" />
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
              v-for="filter in STATUS_FILTERS"
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
          <div class="vp-empty-title">{{ products.length ? 'No matching products' : 'No products yet' }}</div>
          <div class="vp-empty-text">
            {{ products.length ? 'Try another search, status or filter.' : 'Add your first product so customers can order it.' }}
          </div>
          <q-btn v-if="!products.length" unelevated no-caps color="primary" icon="add" label="Add Product" class="vp-primary-btn pl-empty-btn" @click="showAddModal = true" />
        </div>

        <!-- A table on wide screens; a row opens the product. -->
        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table pl-table">
            <thead>
              <tr>
                <th>Product</th>
                <th class="col-cat">Category</th>
                <th class="col-stock">Stock</th>
                <th class="text-right col-price">Price</th>
                <th class="col-status">Status</th>
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
                <td class="vp-muted pl-ellipsis">{{ product.category?.category_name || 'Uncategorized' }}</td>
                <td>
                  <span class="pl-stock" :class="stockClass(product)">{{ product.available_quantity }}</span>
                  <span class="pl-stock-total"> of {{ product.stock_quantity }}</span>
                </td>
                <td class="text-right vp-amount">
                  <span v-if="product.variants?.length" class="pl-from">from </span>₱{{ formatNumber(product.price) }}
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
                {{ product.category?.category_name || 'Uncategorized' }} · <span :class="stockClass(product)">{{ product.available_quantity }}</span> of {{ product.stock_quantity }} left
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
          <span>Showing {{ rangeStart }}–{{ rangeEnd }} of {{ filteredProducts.length }}</span>
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
          <div class="pl-confirm-title">{{ confirm.kind === 'delete' ? 'Delete this product?' : 'Deactivate this product?' }}</div>
          <p class="pl-confirm-text">
            <template v-if="confirm.kind === 'delete'">
              <strong>{{ confirm.product?.product_name }}</strong> will be removed for good. This can't be undone.
            </template>
            <template v-else>
              Customers won't be able to buy <strong>{{ confirm.product?.product_name }}</strong> until you turn it back on.
            </template>
          </p>
        </div>
        <q-separator class="pl-confirm-sep" />
        <div class="pl-confirm-actions">
          <q-btn v-close-popup outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" :disable="confirm.busy" />
          <q-btn
            unelevated
            no-caps
            color="primary"
            :label="confirm.kind === 'delete' ? 'Delete Product' : 'Deactivate'"
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
            <div class="vp-dialog-title">Export inventory</div>
            <div class="vp-dialog-text">
              {{ exportStep === 1 ? 'Choose a format for the inventory report.' : 'Your report is ready to generate.' }}
            </div>
          </div>
          <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" :disable="isExporting" />
        </div>

        <div class="vp-dialog-body">
          <div v-if="exportStep === 1" class="pl-format-grid" role="radiogroup" aria-label="Export format">
            <button
              v-for="format in EXPORT_FORMATS"
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
            <div class="pl-summary-row"><span>Format</span><strong>{{ exportFormat === 'pdf' ? 'PDF document' : 'Image snapshot' }}</strong></div>
            <div class="pl-summary-row"><span>Products</span><strong>{{ filteredProducts.length }}</strong></div>
          </div>
        </div>

        <div class="vp-dialog-actions">
          <template v-if="exportStep === 1">
            <q-btn v-close-popup outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" />
            <q-btn unelevated no-caps color="primary" label="Next" class="vp-dialog-btn" @click="proceedToPreview(exportFormat)" />
          </template>
          <template v-else>
            <q-btn outline no-caps color="primary" label="Back" class="vp-dialog-btn" :disable="isExporting" @click="exportStep = 1" />
            <q-btn unelevated no-caps color="primary" label="Download" class="vp-dialog-btn" :loading="isExporting" @click="executeFinalExport" />
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

import AddProductModal from '@/components/modals/AddProductModal.vue'
import ProductDetailsModal from '@/components/modals/ProductDetailsModal.vue'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'
import FilterSheet from '@/components/vendor/FilterSheet.vue'

const $q = useQuasar()

const STATUS_FILTERS = [
  { key: 'all', label: 'All' },
  { key: 'active', label: 'Active' },
  { key: 'deactivated', label: 'Deactivated' },
  { key: 'archived', label: 'Archived' }
]
const STOCK_OPTIONS = [
  { label: 'All stock levels', value: 'all' },
  { label: 'Low stock (under 10)', value: 'low_stock' }
]
const PRICE_OPTIONS = [
  { label: 'Default order', value: 'default' },
  { label: 'Low to high', value: 'low_to_high' },
  { label: 'High to low', value: 'high_to_low' }
]
const EXPORT_FORMATS = [
  { value: 'pdf', icon: 'o_picture_as_pdf', title: 'PDF document', sub: 'Printable A4 report' },
  { value: 'image', icon: 'o_image', title: 'Image snapshot', sub: 'Quick shareable image' }
]
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

const insightCards = computed(() => {
  const ml = mlInsights.value
  const days = Number(ml.daysUntilStockout)
  const trendNotes = [{ icon: 'o_insights', text: isNumber(ml.trendMultiplier) ? `Expected ${ml.trendMultiplier}× demand` : 'Not enough data yet' }]
  if (ml.currentSeason) {
    trendNotes.push({ icon: 'o_wb_sunny', tone: 'success', text: `Season: ${ml.currentSeason}${ml.currentHoliday ? ` · ${ml.currentHoliday}` : ''}` })
  }

  return [
    {
      key: 'restock',
      label: 'Restock alert',
      icon: 'o_warning_amber',
      tone: 'danger',
      value: ml.restockProduct || 'Analyzing inventory…',
      notes: [{ icon: 'o_schedule', text: isNumber(ml.daysUntilStockout) ? `Stock-out in about ${days} day${days === 1 ? '' : 's'}` : 'No stock-out predicted yet' }]
    },
    { key: 'trend', label: 'Upcoming trend', icon: 'o_trending_up', tone: 'info', value: ml.trendingCategory || 'Gathering data…', notes: trendNotes },
    { key: 'top', label: 'Top performer', icon: 'o_emoji_events', tone: 'wait', value: ml.topCategory || 'Calculating…', notes: [{ icon: 'o_star_outline', text: 'Highest revenue this week' }] }
  ]
})

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
watch(() => STATUS_FILTERS.map(f => statusCount(f.key)).join(), () => nextTick(updateChipFade))

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

const categorySelectOptions = computed(() => [{ label: 'All categories', value: 'all' }, ...categoryOptions.value])

const formatStatus = status => {
  if (!status) return 'Active'
  return String(status).charAt(0).toUpperCase() + String(status).slice(1).toLowerCase()
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
  { label: 'View', icon: 'o_visibility', run: () => viewProduct(product) },
  ...(product.status !== 'deactivated' ? [{ label: 'Deactivate', icon: 'o_block', run: () => askConfirm('deactivate', product) }] : []),
  { label: 'Delete', icon: 'o_delete', danger: true, run: () => askConfirm('delete', product) }
]

const runConfirm = async () => {
  const { kind, product } = confirm
  if (!product) return

  confirm.busy = true
  try {
    if (kind === 'delete') {
      await api.delete(`/vendor/products/${product.inventory_id}`)
      $q.notify({ type: 'positive', message: 'Product deleted.' })
    } else {
      await api.patch(`/vendor/products/${product.inventory_id}/status`, { status: 'deactivated' })
      $q.notify({ type: 'positive', message: 'Product deactivated.' })
    }
    confirm.open = false
    fetchProducts()
  } catch (err) {
    console.error(err)
    $q.notify({ type: 'negative', message: err.response?.data?.message || `Failed to ${kind} the product.` })
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
      $q.notify({ type: 'negative', message: 'Failed to generate the PDF report.' })
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
      $q.notify({ type: 'negative', message: 'Failed to generate the image report.' })
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
      mlInsights.value.trendMultiplier = res.data.trendMultiplier ?? 'N/A'
      mlInsights.value.topCategory = res.data.topCategory
      mlInsights.value.currentSeason = res.data.currentSeason ?? null
      mlInsights.value.currentHoliday = res.data.currentHoliday ?? null
    } else {
      mlInsights.value.restockProduct = 'Awaiting more data'
      mlInsights.value.daysUntilStockout = 'N/A'
      mlInsights.value.trendingCategory = 'Awaiting more data'
      mlInsights.value.trendMultiplier = 'N/A'
      mlInsights.value.topCategory = 'Awaiting more data'
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

/* CONFIRM — deactivate and delete share the Log out dialog's layout, keeping their icon and adding a line above the buttons. */
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

/* Cancel and the action share the row equally, as Cancel and Log out do. */
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

/* The photo fills its rounded frame, so its own corners come out rounded instead of sitting square inside. */
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

  .pl-search-row {
    max-width: none;
  }
}
</style>
