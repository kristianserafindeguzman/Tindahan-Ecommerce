<template>
  <q-dialog 
    v-model="isOpen" 
    persistent 
    transition-show="scale" 
    transition-hide="scale" 
    @hide="resetToViewMode"
  >
    <q-card style="width: 900px; max-width: 95vw; max-height: 90vh; border-radius: 12px;" class="bg-white overflow-hidden shadow-10 column no-wrap modal-card">
      
      <!-- ================= UNIFIED RED HEADER ================= -->
      <q-card-section class="modal-header row items-center justify-between q-px-lg q-py-sm sticky-header z-top text-white header-gradient shrink-none">
        <div class="row items-center col no-wrap q-pr-sm">
          <q-icon :name="isEditMode ? 'inventory_2' : 'inventory'" size="20px" class="q-mr-sm shrink-none" />
          <div class="text-subtitle1 text-weight-bold ellipsis modal-title">
            {{ isEditMode ? 'Edit Product' : 'Product Details' }}
          </div>
        </div>
        
        <div class="row items-center q-gutter-x-sm col-auto header-actions no-wrap">
          
          <!-- DESKTOP ONLY: Interactive Toggle Switch Pill (Hidden on Mobile) -->
          <div 
            v-if="!isEditMode"
            class="gt-xs interactive-status-pill row items-center no-wrap"
            :class="isActive ? 'pill-active' : 'pill-archived'"
          >
            <q-toggle 
              v-model="isActive" 
              color="green-4" 
              dense
              dark
              size="xs"
              class="q-mr-xs status-toggle-control"
              @update:model-value="quickToggleStatus"
            />
            <span class="status-label">{{ isActive ? 'Available' : 'Archived' }}</span>
          </div>
          
          <!-- DESKTOP ONLY: Edit Product Button (Hidden on Mobile) -->
          <q-btn 
            v-if="!isEditMode"
            unelevated 
            icon="edit" 
            label="Edit Product" 
            class="gt-xs header-edit-btn text-weight-bold" 
            no-caps 
            @click="enterEditMode"
          />

          <!-- ALWAYS VISIBLE: Close Button -->
          <q-btn icon="close" flat round dense v-close-popup color="white" size="sm" class="opacity-80 hover-opacity-100 transition-ease" />
        </div>
      </q-card-section>

      <!-- ================= VIEW MODE ================= -->
      <div v-if="!isEditMode" class="col column no-wrap modal-scroll-body">
        
        <q-card-section class="col scroll q-pa-md q-pa-sm-lg">
          <div class="row q-col-gutter-lg">
            
            <!-- Left: Product Image -->
            <div class="col-12 col-md-5">
              <div class="image-showcase flex flex-center relative-position">
                <img v-if="product?.image_url" :src="product.image_url" class="showcase-img shadow-1" />
                <div v-else class="text-center opacity-50 bg-slate-50 border-slate-light full-width full-height flex flex-center rounded-borders q-pa-lg">
                  <div>
                    <q-icon name="image_not_supported" size="44px" color="blue-grey-3" class="q-mb-sm" />
                    <div class="text-body2 text-weight-medium text-blue-grey-4">No Image Uploaded</div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Right: Details & Graph -->
            <div class="col-12 col-md-7 flex column">
              
              <div class="q-mb-sm">
                <div class="category-badge-wrapper q-mb-xs">
                  <span class="category-badge">
                    <q-icon name="sell" size="12px" class="q-mr-xs text-red-7" />
                    {{ product?.category?.category_name || 'Uncategorized' }}
                  </span>
                </div>
                
                <div class="text-h5 text-weight-bold text-slate-800 q-mt-xs q-mb-xs leading-tight">
                  {{ product?.product_name || 'Unnamed Product' }}
                </div>
                
                <div class="row items-center q-mt-sm">
                  <div class="text-h6 text-weight-bold text-slate-900">
                    ₱{{ formatNumber(displayPrice) }}
                  </div>
                  <q-separator vertical class="q-mx-md" style="background-color: #e2e8f0; height: 18px;" />
                  <div class="text-body1 text-slate-500 font-medium">
                    {{ totalSales }} Products Sold
                  </div>
                </div>
              </div>

              <!-- Details Description -->
              <div class="q-mb-md q-mt-sm">
                <div class="text-body1 text-slate-700 font-normal leading-relaxed">
                  {{ product?.description || 'No description provided for this product.' }}
                </div>
              </div>

              <!-- Inventory Status -->
              <div class="q-mb-lg q-mt-sm">
                <div class="text-caption text-weight-bold text-slate-500 text-uppercase q-mb-sm">Inventory</div>
                
                <div v-if="!hasVariants" class="row items-center">
                  <q-icon name="inventory_2" color="blue-grey-4" size="20px" class="q-mr-sm" />
                  <span class="text-body1 text-weight-bold" :class="product?.stock_quantity > 0 ? 'text-slate-800' : 'text-red-6'">
                    {{ product?.stock_quantity || 0 }} <span class="text-weight-regular text-slate-500">products available</span>
                  </span>
                </div>
                
                <div v-else class="row q-gutter-xs">
                  <div v-for="v in product.variants" :key="v.size" class="bg-slate-50 border-slate-light rounded-borders q-px-md q-py-sm flex items-center shadow-soft">
                    <span class="text-body2 text-weight-bold text-slate-800 q-mr-md">{{ v.size }}</span>
                    <span class="text-body2 text-weight-medium" :class="v.quantity > 0 ? 'text-slate-500' : 'text-red-5'">{{ v.quantity }} left</span>
                  </div>
                </div>
              </div>

              <!-- Sales Performance Area Chart -->
              <div class="q-mt-auto">
                <div class="row items-center justify-between q-mb-sm">
                  <div class="text-caption text-weight-bold text-slate-500 text-uppercase">Sales Performance</div>
                  <div class="text-caption text-slate-500">{{ salesDateRange }}</div>
                </div>
                
                <div class="bg-slate-50 border-slate-light rounded-borders relative-position overflow-hidden" style="height: 160px; width: 100%;">
                  <div v-if="!hasSalesData" class="absolute-full flex flex-center z-top" style="background: rgba(248, 250, 252, 0.7); backdrop-filter: blur(1px);">
                    <div class="bg-white border-slate-light text-slate-600 q-px-md q-py-xs rounded-borders shadow-1 text-body2 text-weight-bold">
                      No sales data yet
                    </div>
                  </div>
                  
                  <apexchart 
                    v-if="renderChart"
                    type="area" 
                    height="100%" 
                    width="100%"
                    :options="dynamicChartOptions" 
                    :series="dynamicChartSeries"
                    :class="{ 'opacity-30 grayscale': !hasSalesData }" 
                  />
                </div>
              </div>
              
            </div>
          </div>
        </q-card-section>

        <!-- MOBILE ONLY: View Mode Action Footer -->
        <div class="lt-sm view-mode-footer row justify-between items-center border-top-light shrink-none bg-white z-top">
          <!-- Mobile Toggle Pill -->
          <div 
            class="interactive-status-pill mobile-action-pill row items-center justify-center no-wrap shadow-1 cursor-pointer"
            :class="isActive ? 'pill-active' : 'pill-archived'"
          >
            <q-toggle 
              v-model="isActive" 
              color="green-4" 
              dense
              dark
              size="sm"
              class="q-mr-xs"
              @update:model-value="quickToggleStatus"
            />
            <span class="status-label text-weight-bold">{{ isActive ? 'Available' : 'Archived' }}</span>
          </div>
          
          <!-- Mobile Edit Button -->
          <q-btn 
            unelevated 
            icon="edit" 
            label="Edit Product" 
            class="solid-edit-btn mobile-action-btn text-weight-bold shadow-1" 
            no-caps 
            @click="enterEditMode"
          />
        </div>

      </div>

      <!-- ================= EDIT MODE (FORM) ================= -->
      <q-form v-else @submit.prevent="submitForm" class="col column no-wrap full-width">
        
        <q-card-section class="col scroll q-pa-md q-pa-sm-lg">
          <div class="row q-col-gutter-lg edit-form-row">
            
            <!-- LEFT COLUMN: Form Inputs (Moves to bottom on Mobile) -->
            <div class="col-12 col-md-7 edit-form-inputs">
              <div class="text-subtitle1 text-weight-bold text-slate-800 q-mb-md row items-center">
                <q-icon name="sort" color="red-9" size="20px" class="q-mr-sm" /> Basic Information
              </div>
              
              <div class="q-mb-md">
                <div class="input-label q-mb-xs">Product Name</div>
                <q-input v-model="form.product_name" outlined dense placeholder="e.g., Oreo Vanilla" :rules="[val => !!val || 'Required']" hide-bottom-space class="custom-glass-input" />
              </div>

              <div class="q-mb-md">
                <div class="input-label q-mb-xs">Category</div>
                <q-select v-model="form.category_id" :options="categories" option-value="category_id" option-label="category_name" emit-value map-options outlined dense placeholder="Select a category" :rules="[val => !!val || 'Required']" hide-bottom-space class="custom-glass-input" />
              </div>

              <div class="q-mb-md">
                <div class="input-label q-mb-xs">Description</div>
                <q-input v-model="form.description" type="textarea" outlined dense placeholder="Provide a detailed description..." hide-bottom-space class="custom-glass-input" rows="3" />
              </div>

              <q-separator color="slate-200" class="q-my-lg" />

              <div class="row items-center justify-between q-mb-md">
                <div class="text-subtitle1 text-weight-bold text-slate-800 row items-center">
                  <q-icon name="local_offer" color="red-9" size="18px" class="q-mr-sm" /> Inventory & Pricing
                </div>
                <q-toggle v-model="hasVariants" label="Has Variants" color="red-9" size="sm" class="text-weight-bold text-slate-700 custom-toggle" />
              </div>

              <div v-if="!hasVariants" class="row q-col-gutter-md">
                <div class="col-12 col-sm-6 q-mb-sm q-mb-sm-none">
                  <div class="input-label q-mb-xs">Price (₱)</div>
                  <q-input v-model.number="form.price" type="number" outlined dense min="0" step="0.01" hide-bottom-space class="custom-glass-input" />
                </div>
                <div class="col-12 col-sm-6">
                  <div class="input-label q-mb-xs">Quantity</div>
                  <q-input v-model.number="form.stock_quantity" type="number" outlined dense min="0" hide-bottom-space class="custom-glass-input" />
                </div>
              </div>

              <q-slide-transition>
                <div v-if="hasVariants">
                  <div class="row text-caption text-weight-bold text-slate-500 q-mb-xs q-px-xs">
                    <div class="col-4">Size / Variant</div>
                    <div class="col-3">Price (₱)</div>
                    <div class="col-3">Quantity</div>
                    <div class="col-2 text-center">Remove</div>
                  </div>
                  <div v-for="(variant, index) in form.variants" :key="index" class="row q-col-gutter-sm items-start q-mb-sm">
                    <div class="col-4">
                      <q-input v-model="variant.size" outlined dense placeholder="e.g., Small" hide-bottom-space class="custom-glass-input" />
                    </div>
                    <div class="col-3">
                      <q-input v-model.number="variant.price" type="number" outlined dense placeholder="0.00" hide-bottom-space class="custom-glass-input" />
                    </div>
                    <div class="col-3">
                      <q-input v-model.number="variant.quantity" type="number" outlined dense placeholder="0" hide-bottom-space class="custom-glass-input" />
                    </div>
                    <div class="col-2 flex flex-center">
                      <q-btn icon="close" flat round color="red-5" size="sm" @click="removeVariant(index)" :disable="form.variants.length === 1" class="bg-red-50" />
                    </div>
                  </div>
                  <q-btn outline icon="add" label="Add Variant" color="blue-grey-6" size="sm" class="full-width q-mt-sm text-weight-bold btn-standard-action" no-caps @click="addVariant" />
                </div>
              </q-slide-transition>
            </div>

            <!-- RIGHT COLUMN: Image Edit (Moves to Top on Mobile) -->
            <div class="col-12 col-md-5 edit-form-image">
              <div class="text-subtitle1 text-weight-bold text-slate-800 q-mb-md row items-center">
                <q-icon name="image" color="red-9" size="20px" class="q-mr-sm" /> Product Image
              </div>
              
              <div class="image-upload-box relative-position" @click="showOptionMenu = true">
                <img v-if="imagePreview" :src="imagePreview" class="preview-img" />
                <div v-else class="flex column flex-center full-height w-full q-pa-md text-center cursor-pointer">
                  <q-icon name="add_photo_alternate" size="32px" color="blue-grey-3" class="q-mb-sm" />
                  <div class="text-body2 text-weight-bold text-slate-600">Upload Image</div>
                </div>
                <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleImageUpload" />
              </div>
              
              <q-slide-transition>
                <div v-if="imagePreview" class="row q-gutter-sm q-mt-md justify-center">
                  <q-btn outline color="slate-700" icon="crop" label="Crop" no-caps class="btn-sub-action text-weight-bold flex-1" @click="openCropperForEdit" />
                  <q-btn outline color="red-8" icon="delete_outline" label="Remove" no-caps class="btn-sub-action text-weight-bold flex-1" @click="removeEditImage" />
                </div>
              </q-slide-transition>
            </div>
          </div>
        </q-card-section>

        <!-- Edit Footer -->
        <q-card-section class="modal-footer-actions row justify-end items-center q-px-lg q-py-sm border-top-light shrink-none bg-white">
          <q-btn flat label="Cancel" color="slate-600" no-caps class="btn-form-action text-weight-bold q-mr-sm" @click="cancelEdit" />
          <q-btn type="submit" unelevated label="Save Changes" color="red-9" no-caps class="btn-form-action btn-save-action text-weight-bold shadow-1" :loading="saving">
            <template v-slot:loading>
              <q-spinner-dots class="on-left" /> Saving...
            </template>
          </q-btn>
        </q-card-section>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- [OPTION MENU, CAMERA, CROPPER] -->
  <q-dialog v-model="showOptionMenu">
    <q-card style="width: 320px; max-width: 90vw; border-radius: 8px;" class="bg-white q-pa-sm shadow-10">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-subtitle1 text-weight-bold text-slate-800">Add Photo</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup color="blue-grey-4" size="sm" />
      </q-card-section>
      <q-card-section class="q-pt-md q-pb-md">
        <div class="row q-col-gutter-sm flex-center">
          <div class="col-12">
            <q-btn unelevated color="red-9" icon="camera_alt" label="Take a Photo" class="full-width text-weight-bold text-white btn-standard-action" style="padding: 8px;" @click="openCameraViewfinder" />
          </div>
          <div class="col-12 text-center text-weight-bold text-slate-400 text-caption q-py-xs">Or</div>
          <div class="col-12">
            <q-btn outline color="slate-700" icon="cloud_upload" label="Upload from Device" class="full-width text-weight-bold btn-standard-action" style="padding: 8px;" @click="triggerDeviceUpload" />
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
  
  <q-dialog v-model="showCameraLens" persistent @show="startCamera" @hide="stopCamera">
    <q-card style="width: 650px; max-width: 95vw; border-radius: 8px;" class="bg-white overflow-hidden shadow-10">
      <q-card-section class="row items-center q-px-md q-py-sm border-bottom-light">
        <div class="text-subtitle1 text-weight-bold text-slate-800">Camera</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup color="blue-grey-4" size="sm" />
      </q-card-section>
      <q-card-section class="q-pa-md flex flex-center bg-black">
        <video ref="videoElement" autoplay playsinline class="camera-feed"></video>
      </q-card-section>
      <q-card-actions align="center" class="q-pa-sm bg-white border-top-light">
        <q-btn unelevated color="red-9" icon="camera" label="Capture Photo" class="text-white text-weight-bold q-px-lg btn-standard-action" no-caps @click="capturePhoto" />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <ImageCaptureModal v-model="showCropper" :initialImage="imagePreview" :aspectRatio="1" @captured="handleEditImageCaptured" />
</template>

<script setup>
import { ref, watch, onMounted, computed, nextTick } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar, date } from 'quasar'
import VueApexCharts from 'vue3-apexcharts'
import ImageCaptureModal from '@/components/modals/ImageCaptureModal.vue'

const props = defineProps({
  modelValue: Boolean,
  product: Object
})
const emit = defineEmits(['update:modelValue', 'refresh'])
const $q = useQuasar()

const isOpen = ref(props.modelValue)
const isEditMode = ref(false) 
const renderChart = ref(false) 

watch(() => props.modelValue, async (val) => {
  isOpen.value = val
  if (val && props.product) {
    populateForm()
    await nextTick()
    setTimeout(() => {
      renderChart.value = true
    }, 200)
  } else {
    renderChart.value = false
  }
})

watch(isOpen, (val) => emit('update:modelValue', val))

const categories = ref([])
const hasVariants = ref(false)
const isActive = ref(true)
const saving = ref(false)
const fileInput = ref(null)
const imagePreview = ref(null)

const totalSales = computed(() => props.product?.sales_count || 0)

const displayPrice = computed(() => {
  if (props.product?.variants && props.product.variants.length > 0) {
    const prices = props.product.variants.map(v => Number(v.price))
    return Math.min(...prices)
  }
  return props.product?.price || 0
})

const formatNumber = (num) => {
  return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const hasSalesData = computed(() => {
  return props.product?.sales_history && props.product.sales_history.length > 0
})

const dynamicChartSeries = computed(() => {
  if (!hasSalesData.value) {
    return [{ name: 'No Data', data: [15, 30, 20, 45, 25, 40, 50] }]
  }
  const dataPoints = props.product.sales_history.map(record => record.total_sold)
  return [{ name: 'Sales', data: dataPoints }]
})

const dynamicChartCategories = computed(() => {
  if (!hasSalesData.value) {
    return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] 
  }
  return props.product.sales_history.map(record => {
    const d = new Date(record.date)
    return date.formatDate(d, 'ddd') 
  })
})

const salesDateRange = computed(() => {
  if (!hasSalesData.value) return 'Recent 7 Days'
  const history = props.product.sales_history
  const first = new Date(history[0].date)
  const last = new Date(history[history.length - 1].date)
  return `${date.formatDate(first, 'MMM D')} - ${date.formatDate(last, 'MMM D')}`
})

const dynamicChartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'inherit',
    parentHeightOffset: 0
  },
  colors: ['#dc2626'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  xaxis: { 
    categories: dynamicChartCategories.value,
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#94a3b8', fontSize: '10px' } }
  },
  yaxis: {
    labels: { 
      style: { colors: '#94a3b8', fontSize: '10px' },
      formatter: (value) => Math.round(value)
    },
    tickAmount: 3,
    min: 0
  },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0, stops: [0, 90, 100] }
  },
  grid: { 
    borderColor: '#e2e8f0',
    strokeDashArray: 0,
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true } }
  }
}))

const form = ref({
  product_name: '',
  description: '',
  category_id: null,
  price: null,
  stock_quantity: null,
  product_picture: null,
  variants: [{ size: '', price: null, quantity: null }]
})

const enterEditMode = () => {
  populateForm() 
  isEditMode.value = true
}

const cancelEdit = () => {
  isEditMode.value = false
  populateForm() 
}

const resetToViewMode = () => {
  isEditMode.value = false
}

const toggleStatusDirectly = () => {
  isActive.value = !isActive.value
  quickToggleStatus(isActive.value)
}

const quickToggleStatus = async (val) => {
  try {
    const newStatus = val ? 'active' : 'archived'
    await api.patch(`/vendor/products/${props.product.inventory_id}`, { 
      status: newStatus,
      _method: 'PATCH'
    })
    $q.notify({ type: 'positive', message: `Product marked as ${val ? 'Available' : 'Archived'}` })
    emit('refresh')
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to update status' })
    isActive.value = !val 
  }
}

const populateForm = () => {
  const p = props.product
  if (!p) return
  
  form.value.product_name = p.product_name
  form.value.description = p.description || ''
  form.value.category_id = p.category_id
  isActive.value = p.status !== 'archived'
  
  if (p.variants && p.variants.length > 0) {
    hasVariants.value = true
    form.value.variants = JSON.parse(JSON.stringify(p.variants))
  } else {
    hasVariants.value = false
    form.value.price = p.price
    form.value.stock_quantity = p.stock_quantity
    form.value.variants = [{ size: '', price: null, quantity: null }]
  }

  imagePreview.value = p.image_url || null
  form.value.product_picture = null
}

const fetchCategories = async () => {
  try {
    const res = await api.get('/categories')
    categories.value = res.data
  } catch (err) {
    console.error(err)
  }
}

onMounted(() => {
  fetchCategories()
})

const showOptionMenu = ref(false)
const showCameraLens = ref(false)
const showCropper = ref(false)
const videoElement = ref(null)
let stream = null

const addVariant = () => form.value.variants.push({ size: '', price: null, quantity: null })
const removeVariant = (index) => { if (form.value.variants.length > 1) form.value.variants.splice(index, 1) }
const triggerFileInput = () => fileInput.value.click()
const handleImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) { form.value.product_picture = file; imagePreview.value = URL.createObjectURL(file) }
}
const openCropperForEdit = () => showCropper.value = true
const removeEditImage = () => { imagePreview.value = null; form.value.product_picture = null }
const handleEditImageCaptured = ({ file, dataUrl }) => {
  if (!file) return; form.value.product_picture = file; imagePreview.value = dataUrl || URL.createObjectURL(file);
}
const openCameraViewfinder = () => { showOptionMenu.value = false; showCameraLens.value = true }
const triggerDeviceUpload = () => { showOptionMenu.value = false; if (fileInput.value) fileInput.value.click() }
const startCamera = async () => {
  try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        if (videoElement.value) videoElement.value.srcObject = stream
  } catch (err) { console.error("Camera access denied", err) }
}
const stopCamera = () => { if (stream) { stream.getTracks().forEach(track => track.stop()); stream = null } }
const capturePhoto = () => {
  if (!videoElement.value) return
  const canvas = document.createElement('canvas')
  canvas.width = videoElement.value.videoWidth; canvas.height = videoElement.value.videoHeight
  const ctx = canvas.getContext('2d'); ctx.drawImage(videoElement.value, 0, 0, canvas.width, canvas.height)
  const dataUrl = canvas.toDataURL('image/png')
  canvas.toBlob((blob) => {
    const file = new File([blob], 'product_capture.png', { type: 'image/png' })
    form.value.product_picture = file; imagePreview.value = dataUrl; stopCamera(); showCameraLens.value = false
  }, 'image/png')
}

const submitForm = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('_method', 'PATCH')
    formData.append('product_name', form.value.product_name)
    formData.append('description', form.value.description || '')
    formData.append('category_id', form.value.category_id)
    formData.append('status', isActive.value ? 'active' : 'archived')
    if (form.value.product_picture) formData.append('product_picture', form.value.product_picture)
    if (hasVariants.value) formData.append('variants', JSON.stringify(form.value.variants))
    else { formData.append('variants', ''); formData.append('price', form.value.price); formData.append('stock_quantity', form.value.stock_quantity) }

    await api.post(`/vendor/products/${props.product.inventory_id}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    
    $q.notify({ type: 'positive', message: 'Product updated successfully' })
    isEditMode.value = false 
    emit('refresh')
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Failed to update product' })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* ================= CORE STYLES ================= */
.header-gradient { 
  background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); 
}

.text-slate-900 { color: #0f172a; }
.text-slate-800 { color: #1e293b; }
.text-slate-700 { color: #334155; }
.text-slate-600 { color: #475569; }
.text-slate-500 { color: #64748b; }
.text-slate-400 { color: #94a3b8; }
.bg-slate-50 { background-color: #f8fafc; }

.border-slate-light { border: 1px solid #e2e8f0; }
.border-bottom-light { border-bottom: 1px solid #e2e8f0; }
.border-top-light { border-top: 1px solid #e2e8f0; }

.z-top { z-index: 10; }
.shrink-none { flex-shrink: 0; }
.absolute-full { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }

.opacity-30 { opacity: 0.3; }
.opacity-80 { opacity: 0.8; }
.hover-opacity-100:hover { opacity: 1; transition: opacity 0.2s; }
.grayscale { filter: grayscale(100%); }

/* ================= HEADER BUTTONS (DESKTOP) ================= */
.interactive-status-pill {
  height: 32px;
  padding: 0 10px 0 6px;
  border-radius: 6px;
  transition: all 0.25s ease;
  user-select: none;
}
.pill-active {
  background-color: #15803d;
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.25);
}
.pill-archived {
  background-color: #475569;
  color: #f8fafc;
  border: 1px solid rgba(255, 255, 255, 0.15);
}
.status-label {
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.2px;
}

.header-edit-btn {
  background: #7f1d1d !important;
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.25) !important;
  height: 32px;
  font-size: 13px;
  padding: 0 12px;
  border-radius: 6px;
  transition: all 0.2s ease;
}
.header-edit-btn:hover {
  background: #6a1818 !important;
  border-color: rgba(255, 255, 255, 0.4) !important;
}

/* ================= CATEGORY BADGE ================= */
.category-badge-wrapper {
  display: inline-flex;
}
.category-badge {
  display: inline-flex;
  align-items: center;
  background-color: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
  border-radius: 4px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* ================= BUTTONS ================= */
.btn-standard-action {
  border-radius: 6px !important;
  font-size: 13px;
}
.btn-sub-action {
  height: 34px;
  border-radius: 6px !important;
  font-size: 13px;
}
.btn-form-action {
  height: 36px;
  border-radius: 6px !important;
  font-size: 13px;
  padding: 0 16px;
}
.btn-save-action {
  background-color: #b91c1c !important;
}
.btn-save-action:hover {
  background-color: #991b1b !important;
}

/* ================= VIEW MODE ================= */
.image-showcase { 
  border-radius: 8px; 
  aspect-ratio: 1; 
  padding: 0; 
  background: transparent; 
}
.showcase-img { 
  width: 100%; 
  height: 100%; 
  object-fit: contain; 
  border-radius: 8px; 
}
.sticky-header { position: sticky; top: 0; }

/* ================= EDIT INPUTS ================= */
.input-label { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 4px; }
.custom-glass-input :deep(.q-field__control) { background: #f8fafc; border-radius: 6px; transition: all 0.3s ease; height: 38px; }
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid #e2e8f0; }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; border-color: #cbd5e1; }
.custom-glass-input :deep(.q-field--focused .q-field__control) { background: #ffffff; box-shadow: 0 0 0 1px #b91c1c; border-color: #b91c1c; }
.custom-glass-input :deep(.q-field__native), .custom-glass-input :deep(input), .custom-glass-input :deep(textarea) { color: #1e293b !important; font-weight: 400; font-size: 13px; }

.custom-toggle :deep(.q-toggle__inner) { color: #16a34a; }

.image-upload-box { 
  border: 2px dashed #fca5a5; 
  border-radius: 8px; 
  background-color: #ffffff; 
  min-height: 240px; 
  cursor: pointer; 
}
.image-upload-box:hover { border-color: #ef4444; background-color: #fef2f2; }
.preview-img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; }

.hidden { display: none; }
.camera-feed { width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; background-color: #000; }

/* ================= MOBILE UI / UX (strictly below 600px) ================= */
@media (max-width: 599px) {
  
  /* Mobile Action Pill & Edit Button */
  .mobile-action-pill {
    height: 44px;
    border-radius: 8px;
    flex: 1;
    margin-right: 8px;
  }
  .mobile-action-btn {
    height: 44px;
    border-radius: 8px;
    font-size: 14px;
    flex: 1;
  }
  .solid-edit-btn {
    background-color: #b91c1c !important;
    color: #ffffff !important;
    transition: all 0.2s ease;
  }

  /* Form Re-ordering (Image on top) */
  .edit-form-row {
    display: flex;
    flex-direction: column;
  }
  .edit-form-image {
    order: -1;
    margin-bottom: 24px;
  }
  .edit-form-inputs {
    order: 2;
  }
  
  .image-upload-box {
    min-height: 160px !important;
  }
  .preview-img {
    height: 160px !important;
  }

  /* View Mode Action Bar Padding */
  .view-mode-footer {
    padding: 12px 16px !important;
  }

  /* View Mode Layout Tweaks */
  .image-showcase {
    aspect-ratio: auto;
    width: 100%;
    max-width: 250px;
    margin: 0 auto;
    height: auto;
    padding: 12px;
  }
  .showcase-img {
    height: auto;
    max-height: 200px;
  }

  .modal-footer-actions {
    display: flex;
    flex-direction: row;
    gap: 8px;
    padding: 12px 16px !important;
  }
  .modal-footer-actions .q-btn {
    flex: 1;
    margin: 0 !important;
    height: 44px;
  }
}
</style>