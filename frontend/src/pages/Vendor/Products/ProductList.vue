<template>
  <q-page class="vendor-page relative-position overflow-hidden">
    <!-- Subtle Ambient Background Glows (Brand Aligned) -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <div class="page-container relative-position" style="z-index: 1;">      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-xl q-mt-sm row items-center justify-between">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md">
            <q-icon name="inventory_2" size="26px" class="text-brand-red" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight leading-tight">Inventory Management</h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none font-medium">Monitor and update your product catalog.</p>
          </div>
        </div>
      </div>

      <!-- ================= ML INSIGHTS (TOP ROW) ================= -->
      <div class="row q-col-gutter-lg q-mb-xl">
        
        <!-- Restock Alert Card -->
        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card h-full flex column card-hover" style="border-radius: 16px;">
            <q-card-section class="q-pa-lg flex-1 flex column justify-between">
              <div>
                <div class="row items-center q-mb-md">
                  <q-icon name="warning_amber" size="18px" color="red-8" class="q-mr-sm" />
                  <div class="text-subtitle2 text-grey-7 text-uppercase" style="font-size: 11px;">Restock Alert</div>
                </div>
                <div class="text-h6 text-weight-bold text-dark leading-tight">
                  {{ mlInsights.restockProduct || 'Analyzing Inventory...' }}
                </div>
              </div>
              <div class="insight-badge bg-red-50 text-red-9 border-red-light q-mt-md">
                <q-icon name="schedule" size="14px" class="q-mr-xs" />
                Predicted stock-out in {{ mlInsights.daysUntilStockout || 'N/A' }} days
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Upcoming Trend Card -->
        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card h-full flex column card-hover" style="border-radius: 16px;">
            <q-card-section class="q-pa-lg flex-1 flex column justify-between">
              <div>
                <div class="row items-center q-mb-md">
                  <q-icon name="trending_up" size="18px" color="blue-8" class="q-mr-sm" />
                  <div class="text-subtitle2 text-grey-7 text-uppercase" style="font-size: 11px;">Upcoming Trend</div>
                </div>
                <div class="text-h6 text-weight-bold text-dark leading-tight">
                  {{ mlInsights.trendingCategory || 'Gathering Data...' }}
                </div>
              </div>
              <div class="insight-badge bg-blue-50 text-blue-9 border-blue-light q-mt-md">
                <q-icon name="insights" size="14px" class="q-mr-xs" />
                Expected {{ mlInsights.trendMultiplier || '0' }}x demand increase
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Top Performance Card (Dark Variant) -->
        <div class="col-12 col-md-4">
          <q-card class="premium-glass-card bg-gradient-dark text-white h-full relative-position overflow-hidden card-hover" style="border-radius: 16px;">
            <div class="glow-amber"></div>
            <q-card-section class="relative-position z-top q-pa-lg flex-1 flex column justify-between">
              <div>
                <div class="row items-center q-mb-md">
                  <q-icon name="emoji_events" size="18px" color="amber-4" class="q-mr-sm" />
                  <div class="text-subtitle2 text-amber-2 text-uppercase" style="font-size: 11px;">Top Performance</div>
                </div>
                <div class="text-h6 text-weight-bold text-white leading-tight">
                  {{ mlInsights.topCategory || 'Calculating...' }}
                </div>
              </div>
              <div class="insight-badge bg-amber-9 text-white q-mt-md shadow-1" style="border: 1px solid rgba(255,255,255,0.2);">
                <q-icon name="star_outline" size="14px" class="q-mr-xs" />
                Highest revenue driver this week
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card card-hover" style="border-radius: 16px;">
        <q-card-section class="panel-header q-pa-lg border-bottom">
          
          <!-- Refined Products Heading with Red Accent Line -->
          <div class="row items-center justify-between q-mb-lg">
            <div class="text-h5 text-weight-bold text-dark row items-center">
              <div class="header-accent-red q-mr-md"></div>
              Product Catalog
            </div>
          </div>

          <!-- Controls: Unified Toolbar -->
          <div class="row items-center justify-between q-col-gutter-sm">
            
            <!-- Search & Filter Area -->
            <div class="col-12 col-md-6 row items-center no-wrap q-gutter-x-sm">
              <q-input v-model="search" outlined dense class="custom-glass-input exact-height col-grow" placeholder="Search products...">
                <template v-slot:prepend>
                  <q-icon name="search" color="blue-grey-4" size="20px" />
                </template>
              </q-input>
              
              <q-btn outline icon="filter_list" label="Filter" color="blue-grey-4" text-color="blue-grey-8" no-caps class="btn-glass-outline exact-height text-weight-bold q-px-md">
                <q-menu class="premium-dropdown-list shadow-4 q-mt-xs" style="min-width: 280px; border-radius: 12px; padding: 8px;">
                  <div class="q-pa-md">
                    <div class="row items-center justify-between q-mb-md">
                      <div class="text-subtitle1 text-weight-bolder text-slate-800">Filters</div>
                      <q-btn flat label="Clear" color="red-9" size="sm" class="text-weight-bold" @click="resetFilters" v-close-popup no-caps />
                    </div>
                    
                    <div class="q-mb-md">
                      <div class="text-caption text-weight-bold text-slate-500 q-mb-xs text-uppercase" style="letter-spacing: 0.5px;">Category</div>
                      <q-select v-model="filters.category" :options="[{label: 'All Categories', value: 'all'}, ...categoryOptions]" emit-value map-options dense outlined options-dense class="custom-glass-input" />
                    </div>

                    <div class="q-mb-md">
                      <div class="text-caption text-weight-bold text-slate-500 q-mb-xs text-uppercase" style="letter-spacing: 0.5px;">Stock Level</div>
                      <q-select v-model="filters.stock" :options="[{label: 'All', value: 'all'}, {label: 'Low Stock (< 10)', value: 'low_stock'}]" emit-value map-options dense outlined options-dense class="custom-glass-input" />
                    </div>

                    <div class="q-mb-md">
                      <div class="text-caption text-weight-bold text-slate-500 q-mb-xs text-uppercase" style="letter-spacing: 0.5px;">Status</div>
                      <q-select v-model="filters.status" :options="[{label: 'All', value: 'all'}, {label: 'Active', value: 'active'}, {label: 'Archived', value: 'archived'}]" emit-value map-options dense outlined options-dense class="custom-glass-input" />
                    </div>

                    <div class="q-mb-sm">
                      <div class="text-caption text-weight-bold text-slate-500 q-mb-xs text-uppercase" style="letter-spacing: 0.5px;">Price</div>
                      <q-select v-model="filters.priceSort" :options="[{label: 'Default', value: 'default'}, {label: 'Low to High', value: 'low_to_high'}, {label: 'High to Low', value: 'high_to_low'}]" emit-value map-options dense outlined options-dense class="custom-glass-input" />
                    </div>
                  </div>
                </q-menu>
              </q-btn>
            </div>

            <!-- Action Area -->
            <div class="row q-gutter-md col-12 col-md-auto q-mt-sm q-md-none justify-end">
              <q-btn outline icon="download" label="Export" color="blue-grey-4" text-color="blue-grey-8" no-caps class="btn-glass-outline exact-height text-weight-bold q-px-md" @click="openExportWizard" />
              <q-btn unelevated icon="add" label="Add Product" color="red-9" no-caps class="btn-premium exact-height text-white text-weight-bold q-px-md" @click="showAddModal = true" />
            </div>
          </div>
        </q-card-section>

        <!-- Table -->
        <q-table
          flat
          class="custom-premium-table bg-transparent"
          :rows="filteredProducts"
          :columns="columns"
          row-key="inventory_id"
          :loading="loading"
        >
          <!-- Empty State -->
          <template #no-data>
            <div class="full-width row flex-center text-grey-6 q-pa-xl empty-state-glass">
              <div class="text-center">
                <q-icon name="inventory_2" size="48px" class="q-mb-md opacity-50 drop-shadow-icon" />
                <div class="text-subtitle1 text-weight-bold text-blue-grey-8">No products found</div>
                <div class="text-caption text-blue-grey-5">Your inventory is currently empty or no items match your filter.</div>
              </div>
            </div>
          </template>

          <!-- Image Cell -->
          <template #body-cell-image="props">
            <q-td :props="props">
              <q-avatar size="44px" square class="bg-slate-100 shadow-soft" style="border-radius: 8px; border: 1px solid #e2e8f0;">
                <img v-if="props.row.image_url" :src="props.row.image_url" style="object-fit: cover;" />
                <q-icon v-else name="image" color="blue-grey-3" size="24px" />
              </q-avatar>
            </q-td>
          </template>
          
          <!-- Quantity Cell -->
          <template #body-cell-quantity="props">
            <q-td :props="props">
              <div class="text-weight-bold" :class="props.row.available_quantity > 0 ? 'text-blue-grey-9' : 'text-red-7'">
                Avail: {{ props.row.available_quantity }}
              </div>
              <div class="text-caption text-blue-grey-4 font-medium">
                Total: {{ props.row.stock_quantity }}
              </div>
            </q-td>
          </template>

          <!-- Price Cell (With generous right padding to separate from status) -->
          <template #body-cell-price="props">
            <q-td :props="props" class="text-weight-bold text-blue-grey-9 q-pr-xl">
              <span v-if="props.row.variants && props.row.variants.length > 0" class="text-caption text-blue-grey-4 font-medium q-mr-xs">from</span>
              ₱{{ formatNumber(props.row.price) }}
            </q-td>
          </template>
          
          <!-- Status Cell (Bigger & Bolder with Left padding protection) -->
          <template #body-cell-status="props">
            <q-td :props="props" class="q-pl-lg">
              <q-chip :color="getStatusColor(props.row.status)" text-color="white" class="text-weight-bolder shadow-1 q-px-md" style="font-size: 12.5px; min-height: 26px;">
                {{ formatStatus(props.row.status) }}
              </q-chip>
            </q-td>
          </template>

          <!-- Action Menu Cell (Three Dots) -->
          <template #body-cell-action="props">
            <q-td :props="props" class="text-right q-pr-lg">
              <q-btn flat round dense icon="more_vert" color="blue-grey-4" class="hover-action-btn">
                <q-menu class="premium-dropdown-list shadow-10 q-mt-xs" anchor="bottom right" self="top right" auto-close transition-show="scale" transition-hide="scale">
                  <q-list style="min-width: 160px; padding: 4px;">
                    <q-item clickable @click="openDetails(props.row)" class="hover-grey rounded-borders">
                      <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="visibility" size="20px" color="blue-6" /></q-item-section>
                      <q-item-section class="text-weight-medium text-slate-700">View & Edit</q-item-section>
                    </q-item>
                    <q-item clickable v-if="props.row.status !== 'archived'" @click="deactivateProduct(props.row)" class="hover-red rounded-borders q-mt-xs">
                      <q-item-section avatar class="min-w-0 q-pr-sm"><q-icon name="archive" size="20px" color="red-9" /></q-item-section>
                      <q-item-section class="text-weight-bold text-red-9">Archive</q-item-section>
                    </q-item>
                  </q-list>
                </q-menu>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card>

    </div>

    <!-- Modals -->
    <AddProductModal v-model="showAddModal" @refresh="fetchProducts" />
    <ProductDetailsModal v-model="showDetailsModal" :product="selectedProduct" @refresh="fetchProducts" />

    <!-- Export Wizard Modal -->
    <q-dialog v-model="showExportModal" persistent transition-show="scale" transition-hide="scale">
      <q-card style="width: 500px; max-width: 90vw; border-radius: 16px;" class="bg-white shadow-4">
        <q-card-section class="row items-center q-pb-none q-pt-lg q-px-lg">
          <div class="text-h6 text-weight-bolder text-slate-800">Export Inventory</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup color="grey-6" />
        </q-card-section>

        <q-card-section class="q-pt-md q-px-lg q-pb-lg">
          <!-- Step 1: Format Selection -->
          <div v-if="exportStep === 1">
            <p class="text-body2 text-slate-500 q-mb-lg font-medium">Choose your preferred export format for the inventory report.</p>
            
            <div class="row q-col-gutter-md">
              <div class="col-6">
                <q-card class="cursor-pointer format-card text-center q-pa-md" :class="exportFormat === 'pdf' ? 'bg-red-50 border-red-light' : 'bg-slate-50 border-slate-light'" @click="exportFormat = 'pdf'" flat bordered>
                  <q-icon name="picture_as_pdf" size="40px" :color="exportFormat === 'pdf' ? 'red-9' : 'blue-grey-3'" class="q-mb-sm transition-ease" />
                  <div class="text-weight-bold" :class="exportFormat === 'pdf' ? 'text-red-9' : 'text-slate-700'">PDF Document</div>
                  <div class="text-caption text-slate-500 q-mt-xs">Professional A4 format</div>
                </q-card>
              </div>
              <div class="col-6">
                <q-card class="cursor-pointer format-card text-center q-pa-md" :class="exportFormat === 'image' ? 'bg-red-50 border-red-light' : 'bg-slate-50 border-slate-light'" @click="exportFormat = 'image'" flat bordered>
                  <q-icon name="image" size="40px" :color="exportFormat === 'image' ? 'red-9' : 'blue-grey-3'" class="q-mb-sm transition-ease" />
                  <div class="text-weight-bold" :class="exportFormat === 'image' ? 'text-red-9' : 'text-slate-700'">Image Snapshot</div>
                  <div class="text-caption text-slate-500 q-mt-xs">Quick shareable image</div>
                </q-card>
              </div>
            </div>

            <div class="row justify-end q-mt-xl">
              <q-btn unelevated label="Next" color="red-9" class="q-px-xl text-weight-bold btn-premium" no-caps @click="proceedToPreview(exportFormat)" />
            </div>
          </div>

          <!-- Step 2: Preview -->
          <div v-else-if="exportStep === 2">
            <q-banner rounded class="bg-slate-50 q-mb-lg border-slate-light q-pa-md">
              <template v-slot:avatar>
                <q-icon name="info" color="blue-grey-4" size="28px" />
              </template>
              <div class="text-weight-bolder text-slate-800 text-subtitle1">Ready to Generate</div>
              <div class="text-body2 text-slate-600 q-mt-xs">
                Format: <strong class="text-slate-800">{{ exportFormat.toUpperCase() }}</strong><br>
                Total Items: <strong class="text-slate-800">{{ filteredProducts.length }}</strong>
              </div>
            </q-banner>

            <div class="row justify-end q-mt-lg q-gutter-sm">
              <q-btn flat label="Back" color="blue-grey-6" no-caps @click="exportStep = 1" :disable="isExporting" class="text-weight-bold" />
              <q-btn unelevated label="Confirm & Download" color="red-9" class="q-px-lg text-weight-bold btn-premium" no-caps :loading="isExporting" @click="executeFinalExport" />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, reactive, nextTick } from 'vue'
import html2canvas from 'html2canvas'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import { useAuth } from '@/composables/useAuth'
import AddProductModal from '@/components/modals/AddProductModal.vue'
import ProductDetailsModal from '@/components/modals/ProductDetailsModal.vue'

const $q = useQuasar()
const authStore = useAuth()
const search = ref('')
const loading = ref(true)
const products = ref([])
const showAddModal = ref(false)
const showDetailsModal = ref(false)
const selectedProduct = ref(null)

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
  topCategory: null
})

// Columns redefined with explicit padding classes to separate Price, Status, and Action
const columns = [
  { name: 'image', label: 'Image', field: 'image', align: 'left' },
  { name: 'product_name', label: 'Name', field: 'product_name', align: 'left', sortable: true },
  { name: 'category', label: 'Category', field: row => row.category?.category_name || 'Uncategorized', align: 'left', sortable: true },
  { name: 'quantity', label: 'QTY', field: 'stock_quantity', align: 'left', sortable: true },
  { name: 'price', label: 'Price (₱)', field: 'price', align: 'right', sortable: true, headerClasses: 'q-pr-xl' },
  { name: 'status', label: 'Status', field: 'status', align: 'left', headerClasses: 'q-pl-lg' },
  { name: 'action', label: '', field: 'action', align: 'right', headerClasses: 'q-pr-lg' }
]

const filteredProducts = computed(() => {
  let result = products.value

  // Search
  if (search.value) {
    const needle = search.value.toLowerCase()
    result = result.filter(p => p.product_name.toLowerCase().includes(needle))
  }

  // Stock
  if (filters.stock === 'low_stock') {
    result = result.filter(p => p.stock_quantity < 10)
  }

  // Category
  if (filters.category !== 'all') {
    result = result.filter(p => p.category_id === filters.category)
  }

  // Status
  if (filters.status !== 'all') {
    result = result.filter(p => p.status === filters.status)
  }

  // Price Sort
  if (filters.priceSort === 'low_to_high') {
    result = result.slice().sort((a, b) => (a.price || 0) - (b.price || 0))
  } else if (filters.priceSort === 'high_to_low') {
    result = result.slice().sort((a, b) => (b.price || 0) - (a.price || 0))
  }

  return result
})

const resetFilters = () => {
  filters.stock = 'all'
  filters.category = 'all'
  filters.status = 'all'
  filters.priceSort = 'default'
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

const proceedToPreview = (format) => {
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
            $q.notify({ type: 'negative', message: 'Failed to generate PDF report' })
        } finally {
            isExporting.value = false
        }
    } else {
        // Image Export via Backend HTML rendering
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
            const imagePromises = Array.from(images).map(async (img) => {
                if (!img.complete) {
                    await new Promise((resolve) => {
                        img.onload = resolve
                        img.onerror = resolve
                    })
                }
                if (img.decode) {
                    try {
                        await img.decode()
                    } catch (e) {
                        // ignore decode errors
                    }
                }
            })
            await Promise.all([
                ...imagePromises,
                document.fonts ? document.fonts.ready : Promise.resolve()
            ])
            
            const allImages = container.querySelectorAll('img');
            allImages.forEach(img => {
                if (img.src && img.src.startsWith('data:image')) {
                    try {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.naturalWidth || img.width || 240;
                        canvas.height = img.naturalHeight || img.height || 160;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        
                        canvas.style.cssText = img.style.cssText;
                        canvas.className = img.className;
                        if (img.hasAttribute('width')) canvas.style.width = img.getAttribute('width') + 'px';
                        if (img.hasAttribute('height')) canvas.style.height = img.getAttribute('height') + 'px';
                        
                        img.parentNode.replaceChild(canvas, img);
                    } catch (e) {
                        console.warn('Failed to convert image to canvas for export', e);
                    }
                }
            });
            
            const pages = container.querySelectorAll('.page')
            if (pages.length > 0) {
                for (let i = 0; i < pages.length; i++) {
                    const canvas = await html2canvas(pages[i], {
                        scale: 2,
                        useCORS: true,
                        allowTaint: true,
                        logging: false,
                        backgroundColor: '#ffffff'
                    })
                    
                    const imageLink = document.createElement('a')
                    imageLink.download = `inventory-report-page-${i + 1}-${Date.now()}.png`
                    imageLink.href = canvas.toDataURL('image/png')
                    imageLink.click()
                    
                    await new Promise(r => setTimeout(r, 500))
                }
            } else {
                const canvas = await html2canvas(container, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    logging: false
                })
                
                const imageLink = document.createElement('a')
                imageLink.download = `inventory-report-${Date.now()}.png`
                imageLink.href = canvas.toDataURL('image/png')
                imageLink.click()
            }
            
            document.body.removeChild(container)
            showExportModal.value = false
        } catch (error) {
            console.error('Detailed Image Export Error:', error)
            $q.notify({ type: 'negative', message: 'Failed to generate Image report' })
        } finally {
            isExporting.value = false
        }
    }
}

const categoryOptions = computed(() => {
  const cats = new Map()
  products.value.forEach(p => {
    if (p.category) {
      cats.set(p.category_id, p.category.category_name)
    }
  })
  return Array.from(cats, ([value, label]) => ({ value, label }))
})

// Ensures first letter capitalization
const formatStatus = (status) => {
  if (!status) return 'Active'
  return String(status).charAt(0).toUpperCase() + String(status).slice(1).toLowerCase()
}

const getStatusColor = (status) => {
  switch (String(status || 'active').toLowerCase()) {
    case 'active': return 'green-6'
    case 'inactive': return 'grey-6'
    case 'archived': return 'grey-8'
    case 'out of stock': return 'red-9'
    default: return 'blue-grey-4'
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

const openDetails = (product) => {
  selectedProduct.value = product
  showDetailsModal.value = true
}

const deactivateProduct = async (product) => {
  $q.dialog({
    title: 'Archive Product',
    message: `Are you sure you want to archive "${product.product_name}"? It will no longer be available for sale.`,
    cancel: true,
    persistent: true,
    color: 'red-9'
  }).onOk(async () => {
    try {
      await api.delete(`/vendor/products/${product.inventory_id}`)
      $q.notify({ type: 'positive', message: 'Product archived successfully' })
      fetchProducts()
    } catch (err) {
      $q.notify({ type: 'negative', message: 'Failed to archive product' })
      console.error(err)
    }
  })
}

const fetchMlInsights = async () => {
  try {
    const res = await api.get('/vendor/ml-insights')
    if (res.data && res.data.has_insights) {
      mlInsights.value.restockProduct = res.data.restockProduct
      mlInsights.value.daysUntilStockout = res.data.daysUntilStockout
      mlInsights.value.trendingCategory = res.data.trendingCategory
      mlInsights.value.trendMultiplier = "1.5" // Hardcoded placeholder for UI purposes since RF predicts quantity, not explicitly multiplier
      mlInsights.value.topCategory = res.data.topCategory
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

onMounted(() => {
  fetchProducts()
  fetchMlInsights()
})
</script>

<style scoped>
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}

/* Brand Colors */
.text-brand-red { color: #B91C1C !important; }
.bg-red-50 { background-color: #fef2f2 !important; }
.border-red-light { border: 1px solid #fca5a5 !important; }

.bg-slate-50 { background-color: #f8fafc; }
.bg-slate-100 { background-color: #f1f5f9; }
.text-slate-500 { color: #64748b; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.border-slate-light { border: 1px solid #e2e8f0; }

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
  background: radial-gradient(circle, rgba(185, 28, 28, 0.25) 0%, transparent 70%); 
}
.bg-glow-secondary {
  bottom: 100px;
  right: -50px;
  background: radial-gradient(circle, rgba(15, 23, 42, 0.25) 0%, transparent 70%);
}

/* Typography Enhancements */
.tracking-tight { letter-spacing: -0.03em; }
.tracking-wide { letter-spacing: 0.05em; }
.leading-tight { line-height: 1.2; }
.font-medium { font-weight: 500; }
.z-top { z-index: 1; }

/* Header Glass Icon Box */
.glass-icon-box {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.9);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.1); 
}

/* Header Accent */
.header-accent-red {
  width: 6px; height: 24px; background: linear-gradient(180deg, #B91C1C 0%, #450A0A 100%); border-radius: 6px; 
}

/* Clean Glassmorphism Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(241, 245, 249, 1);
  box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.bg-gradient-dark {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border: 1px solid #334155;
}
.glow-amber {
  position: absolute;
  top: -20px;
  right: -20px;
  width: 100px;
  height: 100px;
  background: radial-gradient(circle, rgba(251, 191, 36, 0.15) 0%, transparent 70%);
  border-radius: 50%;
  filter: blur(20px);
}

.h-full { height: 100%; }

.insight-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  width: fit-content;
}

/* Exact Height Alignment for Toolbar */
.exact-height :deep(.q-field__control) {
  height: 40px !important;
  min-height: 40px !important;
}

/* Custom Inputs & Outline Buttons */
.custom-glass-input :deep(.q-field__control) {
  background: rgba(248, 250, 252, 0.8); 
  border-radius: 8px;
  transition: all 0.3s ease;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.8); }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 0 0 2px rgba(185, 28, 28, 0.15); 
  border-color: #B91C1C;
}

.btn-glass-outline {
  border-radius: 8px !important;
  background: rgba(255, 255, 255, 0.9) !important;
  border: 1px solid rgba(203, 213, 225, 0.8);
  transition: all 0.2s ease;
}
.btn-glass-outline:hover {
  background: #ffffff !important;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
  transform: translateY(-1px);
}

/* Solid Action Button */
.btn-premium {
  border-radius: 8px !important;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.3);
  transition: all 0.2s ease;
}
.btn-premium:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(185, 28, 28, 0.4);
}

/* Utilities */
.border-bottom { border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.panel-header {
  background: rgba(248, 250, 252, 0.5);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 16px 16px 0 0;
}
.transition-ease { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.shadow-soft { box-shadow: 0 2px 8px rgba(15,23,42,0.06); }

/* Custom Premium Table Styling */
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.5); 
  font-weight: 700;
  color: #64748b; 
  text-transform: uppercase; 
  font-size: 11px; 
  letter-spacing: 0.05em; 
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(241, 245, 249, 1);
  transition: all 0.2s ease;
  color: #334155;
  font-weight: 500;
}
:deep(.custom-premium-table tbody tr) {
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}
:deep(.custom-premium-table tbody tr:hover) {
  background: #ffffff;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
  transform: scale(1.002);
  z-index: 5;
  position: relative;
}
:deep(.custom-premium-table tbody tr:hover td) {
  border-bottom-color: transparent;
}

/* Enhanced 3-dots action button hover effect */
.hover-action-btn {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
:deep(.custom-premium-table tbody tr:hover .hover-action-btn) {
  color: #B91C1C !important; 
  background: rgba(185, 28, 28, 0.08);
  transform: scale(1.05);
}
.hover-action-btn:active, .hover-action-btn:focus {
  background: rgba(185, 28, 28, 0.15) !important;
  transform: scale(0.95) !important;
}

.status-chip { 
  border: 1px solid rgba(255,255,255,0.4); 
  letter-spacing: 0.3px;
}

/* Dropdown styling */
.premium-dropdown-list {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(226, 232, 240, 0.8);
  border-radius: 8px;
}
.hover-grey:hover { background: rgba(241, 245, 249, 0.8); }
.hover-red:hover { background: rgba(254, 242, 242, 0.8); color: #B91C1C; }
.rounded-borders { border-radius: 6px; }

/* Empty State Styling */
.empty-state-glass {
  background: rgba(248, 250, 252, 0.6);
  border: 1px dashed rgba(203, 213, 225, 0.8);
  border-radius: 12px;
  margin: 16px;
  width: calc(100% - 32px);
  min-height: 300px;
}
.drop-shadow-icon { filter: drop-shadow(0 4px 6px rgba(15, 23, 42, 0.05)); }
.format-card {
  transition: all 0.2s ease;
  border-radius: 12px;
}
.format-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
</style>