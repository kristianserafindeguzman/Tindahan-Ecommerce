<template>
  <!-- PRODUCT DETAILS — view the product, switch it on or off, or edit it, in the vendor dialog shape. -->
  <q-dialog v-model="isOpen" persistent :maximized="$q.screen.xs" @hide="resetToViewMode">
    <q-card class="vp-dialog pm-dialog" :class="{ 'pm-dialog--full': $q.screen.xs }">

      <!-- VIEW MODE -->
      <template v-if="!isEditMode">
        <div class="vp-dialog-head pm-head">
          <span class="vp-dialog-icon"><q-icon name="o_inventory_2" size="22px" /></span>
          <div class="pm-head-text">
            <div class="vp-dialog-title">Product details</div>
            <div class="vp-dialog-text">{{ product?.category?.category_name || 'Uncategorized' }}</div>
          </div>
          <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" />
        </div>

        <div class="pm-body">
          <div class="pm-grid pm-grid--view">
            <div class="pm-photo-frame">
              <img v-if="product?.image_url" :src="product.image_url" :alt="product?.product_name || 'Product photo'" />
              <div v-else class="pm-no-photo">
                <q-icon name="o_image_not_supported" size="34px" />
                <span>No photo yet</span>
              </div>
            </div>

            <div class="pm-info">
              <div class="pm-info-head">
                <div class="pm-name">{{ product?.product_name || 'Unnamed product' }}</div>
                <div class="pm-price-row">
                  <span class="pm-price"><span v-if="productHasVariants" class="pm-from">from </span>₱{{ formatNumber(displayPrice) }}</span>
                  <span class="pm-dot" aria-hidden="true" />
                  <span class="pm-sold">{{ totalSales }} sold</span>
                </div>
              </div>

              <!-- Availability is a switch that saves straight away. -->
              <div class="pm-availability" :class="{ 'pm-availability--off': !isActive }">
                <div>
                  <div class="pm-availability-title">{{ isActive ? 'Available to customers' : 'Archived' }}</div>
                  <div class="pm-availability-text">
                    {{ isActive ? 'Customers can find and order this product.' : 'Hidden from your store until you turn it back on.' }}
                  </div>
                </div>
                <q-toggle v-model="isActive" color="positive" :aria-label="isActive ? 'Archive this product' : 'Make this product available'" @update:model-value="quickToggleStatus" />
              </div>

              <!-- Stock and description as labelled rows, split by thin lines. -->
              <dl class="pm-specs">
                <div class="pm-spec">
                  <dt>Stock</dt>
                  <dd>
                    <span v-if="!productHasVariants" class="pm-stock" :class="{ 'pm-stock--out': !(product?.stock_quantity > 0) }">
                      {{ product?.stock_quantity || 0 }} in stock
                    </span>
                    <!-- One aligned row per size: its name, what is left, and its price on the right. -->
                    <ul v-else class="pm-variant-list">
                      <li v-for="v in product.variants" :key="v.size" class="pm-variant-row" :class="{ 'pm-variant-row--out': !(v.quantity > 0) }">
                        <span class="pm-variant-size">{{ v.size }}</span>
                        <span class="pm-variant-qty">{{ v.quantity > 0 ? `${v.quantity} left` : 'Out of stock' }}</span>
                        <span class="pm-variant-price">₱{{ formatNumber(v.price) }}</span>
                      </li>
                    </ul>
                  </dd>
                </div>
                <div class="pm-spec">
                  <dt>Description</dt>
                  <dd class="pm-desc">{{ product?.description || 'No description yet.' }}</dd>
                </div>
              </dl>

              <!-- The sales chart fills the rest of the column, so its bottom lines up with the photo. -->
              <div class="pm-block pm-block--grow">
                <div class="pm-block-head">
                  <span class="pm-block-title">Sales</span>
                  <span class="pm-block-note">{{ salesDateRange }}</span>
                </div>
                <div class="pm-chart">
                  <div v-if="!hasSalesData" class="pm-chart-empty">No sales yet</div>
                  <VueApexCharts
                    v-if="renderChart"
                    type="area"
                    height="100%"
                    width="100%"
                    :options="dynamicChartOptions"
                    :series="dynamicChartSeries"
                    :class="{ 'pm-chart--muted': !hasSalesData }"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="vp-dialog-actions pm-actions">
          <q-btn v-close-popup outline no-caps color="primary" label="Close" class="vp-dialog-btn" />
          <q-btn unelevated no-caps color="primary" icon="o_edit" label="Edit Product" class="vp-dialog-btn" @click="enterEditMode" />
        </div>
      </template>

      <!-- EDIT MODE — the whole card is the form, so Save runs the field checks. -->
      <q-form v-else greedy class="pm-form" @submit.prevent="submitForm">
        <div class="vp-dialog-head pm-head">
          <span class="vp-dialog-icon"><q-icon name="o_edit" size="22px" /></span>
          <div class="pm-head-text">
            <div class="vp-dialog-title">Edit product</div>
            <div class="vp-dialog-text">{{ product?.product_name }}</div>
          </div>
          <q-btn flat round dense icon="o_close" class="vp-dialog-close" aria-label="Stop editing" :disable="saving" @click="cancelEdit" />
        </div>

        <div class="pm-body">
          <div class="pm-grid">
            <div class="pm-fields">
              <div>
                <label class="vp-field-label" for="pm-edit-name">Product name</label>
                <q-input
                  v-model="form.product_name"
                  for="pm-edit-name"
                  outlined
                  dense
                  hide-bottom-space
                  placeholder="e.g. Oreo Vanilla"
                  class="vp-input"
                  :rules="[val => !!(val && val.trim()) || 'Enter a product name.']"
                />
              </div>

              <div>
                <label class="vp-field-label">Category</label>
                <q-select
                  v-model="form.category_id"
                  :options="categories"
                  option-value="category_id"
                  option-label="category_name"
                  emit-value
                  map-options
                  outlined
                  dense
                  hide-bottom-space
                  behavior="menu"
                  placeholder="Choose a category"
                  class="vp-input"
                  :rules="[val => !!val || 'Choose a category.']"
                />
              </div>

              <div>
                <label class="vp-field-label" for="pm-edit-desc">Description <span class="vp-field-optional">(optional)</span></label>
                <q-input
                  v-model="form.description"
                  for="pm-edit-desc"
                  type="textarea"
                  outlined
                  autogrow
                  placeholder="Size, flavour, or anything that helps customers choose"
                  class="vp-input pm-textarea"
                />
              </div>

              <div class="pm-section">
                <div class="pm-section-head">
                  <span class="pm-section-title">Pricing and stock</span>
                  <q-toggle v-model="hasVariants" dense color="primary" label="Has sizes" class="pm-toggle" />
                </div>

                <div v-if="!hasVariants" class="pm-row">
                  <div>
                    <label class="vp-field-label" for="pm-edit-price">Price (₱)</label>
                    <q-input
                      v-model.number="form.price"
                      for="pm-edit-price"
                      type="number"
                      min="0"
                      step="0.01"
                      outlined
                      dense
                      hide-bottom-space
                      placeholder="0.00"
                      class="vp-input"
                      :rules="[val => (val !== null && val !== '' && val >= 0) || 'Enter a price.']"
                    />
                  </div>
                  <div>
                    <label class="vp-field-label" for="pm-edit-qty">Quantity</label>
                    <q-input
                      v-model.number="form.stock_quantity"
                      for="pm-edit-qty"
                      type="number"
                      min="0"
                      outlined
                      dense
                      hide-bottom-space
                      placeholder="0"
                      class="vp-input"
                      :rules="[val => (val !== null && val !== '' && val >= 0) || 'Enter a quantity.']"
                    />
                  </div>
                </div>

                <div v-else class="pm-variants">
                  <div class="pm-variant pm-variant--labels" aria-hidden="true">
                    <span>Size</span><span>Price (₱)</span><span>Quantity</span><span />
                  </div>
                  <div v-for="(variant, index) in form.variants" :key="index" class="pm-variant">
                    <q-input v-model="variant.size" outlined dense hide-bottom-space placeholder="e.g. Small" class="vp-input" :aria-label="`Size ${index + 1}`" :rules="[val => !!(val && String(val).trim()) || 'Required']" />
                    <q-input v-model.number="variant.price" type="number" min="0" step="0.01" outlined dense hide-bottom-space placeholder="0.00" class="vp-input" :aria-label="`Price for size ${index + 1}`" />
                    <q-input v-model.number="variant.quantity" type="number" min="0" outlined dense hide-bottom-space placeholder="0" class="vp-input" :aria-label="`Quantity for size ${index + 1}`" />
                    <q-btn flat round dense icon="o_close" class="pm-variant-remove" :disable="form.variants.length === 1" :aria-label="`Remove size ${index + 1}`" @click="removeVariant(index)" />
                  </div>
                  <q-btn outline no-caps color="primary" icon="add" label="Add size" class="vp-pill-btn pm-add-variant" @click="addVariant" />
                </div>
              </div>
            </div>

            <div class="pm-photo">
              <label class="vp-field-label">Product photo</label>
              <div v-if="imagePreview" class="pm-photo-frame">
                <img :src="imagePreview" alt="Product photo preview" />
              </div>
              <button v-else type="button" class="pm-dropzone" @click="showOptionMenu = true">
                <span class="pm-dropzone-icon"><q-icon name="o_add_photo_alternate" size="30px" /></span>
                <span class="pm-dropzone-title">Add a photo</span>
                <span class="pm-dropzone-text">Take one or upload from your device</span>
              </button>
              <div v-if="imagePreview" class="pm-photo-actions">
                <q-btn outline no-caps color="primary" icon="o_crop" label="Crop" class="vp-pill-btn" @click="openCropperForEdit" />
                <q-btn outline no-caps color="primary" icon="o_sync" label="Replace" class="vp-pill-btn" @click="showOptionMenu = true" />
                <q-btn flat no-caps icon="o_delete" label="Remove" class="pm-remove" @click="removeEditImage" />
              </div>
              <div class="pm-photo-tip">A square photo on a plain background looks best in your store.</div>
              <!-- Kept outside the photo box, so choosing a file doesn't reopen the photo menu. -->
              <input ref="fileInput" type="file" class="pm-hidden" accept="image/*" @change="handleImageUpload" />
            </div>
          </div>
        </div>

        <div class="vp-dialog-actions pm-actions">
          <q-btn outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" :disable="saving" @click="cancelEdit" />
          <q-btn type="submit" unelevated no-caps color="primary" label="Save Changes" class="vp-dialog-btn" :loading="saving" />
        </div>
      </q-form>
    </q-card>
  </q-dialog>

  <!-- PHOTO SOURCE — camera or a file. -->
  <q-dialog v-model="showOptionMenu">
    <q-card class="vp-dialog pm-source-dialog">
      <div class="vp-dialog-head">
        <span class="vp-dialog-icon"><q-icon name="o_add_a_photo" size="22px" /></span>
        <div>
          <div class="vp-dialog-title">Add product photo</div>
          <div class="vp-dialog-text">A clear photo on a plain background works best.</div>
        </div>
        <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" />
      </div>
      <div class="vp-dialog-body pm-sources">
        <button type="button" class="pm-source" @click="openCameraViewfinder">
          <span class="pm-source-icon"><q-icon name="o_photo_camera" size="22px" /></span>
          <span class="pm-source-body">
            <span class="pm-source-title">Take a photo</span>
            <span class="pm-source-text">Use your camera</span>
          </span>
          <q-icon name="o_chevron_right" size="20px" class="pm-source-arrow" />
        </button>
        <button type="button" class="pm-source" @click="triggerDeviceUpload">
          <span class="pm-source-icon"><q-icon name="o_upload_file" size="22px" /></span>
          <span class="pm-source-body">
            <span class="pm-source-title">Upload from device</span>
            <span class="pm-source-text">Choose an image file</span>
          </span>
          <q-icon name="o_chevron_right" size="20px" class="pm-source-arrow" />
        </button>
      </div>
    </q-card>
  </q-dialog>

  <!-- CAMERA -->
  <q-dialog v-model="showCameraLens" persistent :maximized="$q.screen.lt.md" @show="startCamera" @hide="stopCamera">
    <q-card class="vp-dialog vp-dialog--wide pm-camera" :class="{ 'pm-dialog--full': $q.screen.lt.md }">
      <div class="vp-dialog-head">
        <span class="vp-dialog-icon"><q-icon name="o_photo_camera" size="22px" /></span>
        <div>
          <div class="vp-dialog-title">Take product photo</div>
          <div class="vp-dialog-text">Center the product in the frame.</div>
        </div>
        <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" />
      </div>
      <div class="pm-camera-view">
        <video ref="videoElement" autoplay playsinline class="pm-camera-feed"></video>
      </div>
      <div class="vp-dialog-actions">
        <q-btn v-close-popup outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" />
        <q-btn unelevated no-caps color="primary" icon="o_camera" label="Capture" class="vp-dialog-btn" @click="capturePhoto" />
      </div>
    </q-card>
  </q-dialog>

  <!-- CROP — the same drag-and-zoom cropper as the profile photos, set to a square for products. -->
  <q-dialog v-model="showCropper" persistent>
    <q-card class="vp-dialog pm-crop-dialog">
      <div class="vp-dialog-head">
        <span class="vp-dialog-icon"><q-icon name="o_crop" size="22px" /></span>
        <div>
          <div class="vp-dialog-title">Crop product photo</div>
          <div class="vp-dialog-text">Drag the photo to move it, and zoom until the product fills the square.</div>
        </div>
        <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" />
      </div>
      <div class="vp-dialog-body">
        <PhotoCropper ref="cropperRef" :src="cropSrc" :aspect="1" :output-width="800" @ready="cropReady = true" />
      </div>
      <div class="vp-dialog-actions">
        <q-btn v-close-popup outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" />
        <q-btn unelevated no-caps color="primary" label="Apply Crop" class="vp-dialog-btn" :disable="!cropReady" @click="applyCrop" />
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch, onMounted, computed, nextTick } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar, date } from 'quasar'
import PhotoCropper from '@/components/shared/PhotoCropper.vue'
// Imported here like the dashboards do, since the chart component isn't registered for the whole app.
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  modelValue: Boolean,
  product: Object
})
const emit = defineEmits(['update:modelValue', 'refresh'])
const $q = useQuasar()

const isOpen = ref(props.modelValue)
const isEditMode = ref(false)
const renderChart = ref(false)

watch(() => props.modelValue, async val => {
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

watch(isOpen, val => emit('update:modelValue', val))

const categories = ref([])
const hasVariants = ref(false)
const isActive = ref(true)
const saving = ref(false)
const fileInput = ref(null)
const imagePreview = ref(null)

const totalSales = computed(() => props.product?.sales_count || 0)
const productHasVariants = computed(() => (props.product?.variants?.length || 0) > 0)

const displayPrice = computed(() => {
  if (productHasVariants.value) return Math.min(...props.product.variants.map(v => Number(v.price)))
  return props.product?.price || 0
})

const formatNumber = num => Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const hasSalesData = computed(() => props.product?.sales_history && props.product.sales_history.length > 0)

// Without sales, a faint sample curve sits behind the "No sales yet" label.
const dynamicChartSeries = computed(() => {
  if (!hasSalesData.value) return [{ name: 'No Data', data: [15, 30, 20, 45, 25, 40, 50] }]
  return [{ name: 'Sales', data: props.product.sales_history.map(record => record.total_sold) }]
})

const dynamicChartCategories = computed(() => {
  if (!hasSalesData.value) return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  return props.product.sales_history.map(record => date.formatDate(new Date(record.date), 'ddd'))
})

const salesDateRange = computed(() => {
  if (!hasSalesData.value) return 'Last 7 days'
  const history = props.product.sales_history
  return `${date.formatDate(new Date(history[0].date), 'MMM D')} – ${date.formatDate(new Date(history[history.length - 1].date), 'MMM D')}`
})

// ApexCharts needs plain hex colours, so the brand red is written out here.
const dynamicChartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    zoom: { enabled: false },
    fontFamily: 'inherit',
    parentHeightOffset: 0
  },
  colors: ['#bd2427'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2 },
  xaxis: {
    categories: dynamicChartCategories.value,
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#8992a2', fontSize: '10px' } }
  },
  yaxis: {
    labels: {
      style: { colors: '#8992a2', fontSize: '10px' },
      formatter: value => Math.round(value)
    },
    tickAmount: 3,
    min: 0
  },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.25, opacityTo: 0, stops: [0, 90, 100] }
  },
  grid: {
    borderColor: '#f0f0f0',
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

const quickToggleStatus = async val => {
  try {
    await api.patch(`/vendor/products/${props.product.inventory_id}`, {
      status: val ? 'active' : 'archived',
      _method: 'PATCH'
    })
    $q.notify({ type: 'positive', message: `Product marked as ${val ? 'available' : 'archived'}.` })
    emit('refresh')
  } catch {
    $q.notify({ type: 'negative', message: 'Failed to update the status.' })
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

onMounted(fetchCategories)

const showOptionMenu = ref(false)
const showCameraLens = ref(false)
const showCropper = ref(false)
const videoElement = ref(null)
let stream = null

const addVariant = () => form.value.variants.push({ size: '', price: null, quantity: null })
const removeVariant = index => { if (form.value.variants.length > 1) form.value.variants.splice(index, 1) }

const handleImageUpload = event => {
  const file = event.target.files[0]
  if (file) {
    form.value.product_picture = file
    imagePreview.value = URL.createObjectURL(file)
  }
}

const cropperRef = ref(null)
const cropSrc = ref('')
// Set once the cropper has drawn the photo, so Apply Crop can't run on an empty frame.
const cropReady = ref(false)

// A photo already saved on the server is copied into the browser first, because a picture from another address can't be cut out of a canvas.
const localCopy = async src => {
  if (/^(blob:|data:)/.test(src)) return src
  try {
    const res = await fetch(src)
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    return URL.createObjectURL(await res.blob())
  } catch (error) {
    console.error('Could not load the saved photo for cropping', error)
    $q.notify({ type: 'warning', message: 'This photo can’t be cropped here. Upload it again to crop it.' })
    return ''
  }
}

const openCropperForEdit = async () => {
  if (!imagePreview.value) return
  cropReady.value = false
  cropSrc.value = await localCopy(imagePreview.value)
  if (cropSrc.value) showCropper.value = true
}

// Replaces the photo with the framed square, the same way the profile photos are cropped.
const applyCrop = async () => {
  const blob = await cropperRef.value?.toBlob()
  if (!blob) return
  form.value.product_picture = new File([blob], `product_${Date.now()}.jpg`, { type: 'image/jpeg' })
  imagePreview.value = URL.createObjectURL(blob)
  showCropper.value = false
}
const removeEditImage = () => {
  imagePreview.value = null
  form.value.product_picture = null
}


const openCameraViewfinder = () => {
  showOptionMenu.value = false
  showCameraLens.value = true
}

// Clears the input first, so picking the same file again still counts as a change.
const triggerDeviceUpload = () => {
  showOptionMenu.value = false
  if (fileInput.value) {
    fileInput.value.value = ''
    fileInput.value.click()
  }
}

const startCamera = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    if (videoElement.value) videoElement.value.srcObject = stream
  } catch (err) {
    console.error('Camera access denied', err)
  }
}

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
    stream = null
  }
}

const capturePhoto = () => {
  if (!videoElement.value) return
  const canvas = document.createElement('canvas')
  canvas.width = videoElement.value.videoWidth
  canvas.height = videoElement.value.videoHeight
  canvas.getContext('2d').drawImage(videoElement.value, 0, 0, canvas.width, canvas.height)
  const dataUrl = canvas.toDataURL('image/png')
  canvas.toBlob(blob => {
    form.value.product_picture = new File([blob], 'product_capture.png', { type: 'image/png' })
    imagePreview.value = dataUrl
    stopCamera()
    showCameraLens.value = false
  }, 'image/png')
}

const submitForm = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('_method', 'PATCH')
    formData.append('product_name', form.value.product_name.trim())
    formData.append('description', form.value.description || '')
    formData.append('category_id', form.value.category_id)
    formData.append('status', isActive.value ? 'active' : 'archived')
    if (form.value.product_picture) formData.append('product_picture', form.value.product_picture)
    if (hasVariants.value) {
      formData.append('variants', JSON.stringify(form.value.variants))
    } else {
      formData.append('variants', '')
      formData.append('price', form.value.price)
      formData.append('stock_quantity', form.value.stock_quantity)
    }

    await api.post(`/vendor/products/${props.product.inventory_id}`, formData, { headers: { 'Content-Type': 'multipart/form-data' } })

    $q.notify({ type: 'positive', message: 'Product updated.' })
    isEditMode.value = false
    emit('refresh')
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to update the product.' })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.pm-dialog {
  display: flex;
  flex-direction: column;

  width: 960px;
  max-height: calc(100vh - 48px);
  overflow: hidden;
}

/* Quasar caps small dialogs at 560px, so the product dialog lifts that cap. */
.pm-dialog.pm-dialog {
  max-width: calc(100vw - 48px);
}

.pm-dialog.pm-dialog--full {
  width: 100%;
  max-width: 100%;
  height: 100%;
  max-height: 100%;

  border-radius: 0;
}

.pm-form {
  display: flex;
  flex-direction: column;
  flex: 1;

  min-height: 0;
}

.pm-head {
  flex-shrink: 0;

  padding-bottom: 16px;

  border-bottom: 1px solid var(--c-hairline);
}

.pm-head-text {
  min-width: 0;
}

.pm-head-text .vp-dialog-text {
  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.pm-body {
  flex: 1;

  min-height: 0;
  padding: 20px 24px;
  overflow-y: auto;
}

.pm-actions {
  flex-shrink: 0;

  border-top: 1px solid var(--c-hairline);
}

/* The photo takes the left half, large, with the details beside it. */
.pm-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
  align-items: start;

  gap: 28px;
}

/* Both columns share one height, so the photo and the chart end on the same line. */
.pm-grid--view {
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.1fr);
  align-items: stretch;
}

.pm-grid--view > .pm-photo-frame {
  align-self: stretch;
}

/* VIEW */

.pm-no-photo {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 8px;
  height: 100%;

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-muted);
}

.pm-info {
  display: flex;
  flex-direction: column;

  gap: 16px;
  min-width: 0;
}

.pm-name {
  font-size: var(--fs-2xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.pm-info-head {
  display: flex;
  flex-direction: column;

  gap: 4px;
}

.pm-price-row {
  display: flex;
  align-items: center;

  gap: 10px;
}

.pm-specs {
  margin: 0;

  border-top: 1px solid var(--c-hairline);
}

.pm-spec {
  display: grid;
  grid-template-columns: 96px minmax(0, 1fr);
  align-items: start;

  gap: 12px;
  padding: 12px 0;

  border-bottom: 1px solid var(--c-hairline);
}

.pm-spec dt {
  padding-top: 2px;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-muted);
}

.pm-spec dd {
  min-width: 0;
  margin: 0;
}

.pm-block--grow {
  display: flex;
  flex-direction: column;
  flex: 1;

  min-height: 0;
}

.pm-block--grow .pm-chart {
  flex: 1;

  height: auto;
  min-height: 140px;
}

.pm-price {
  font-size: var(--fs-xl);
  font-weight: 700;

  color: var(--c-brand);
}

.pm-from {
  font-size: var(--fs-xs);
  font-weight: 500;

  color: var(--c-muted);
}

.pm-dot {
  width: 4px;
  height: 4px;

  border-radius: 50%;

  background: var(--c-border-strong);
}

.pm-sold {
  font-size: var(--fs-sm);

  color: var(--c-text-3);
}

.pm-availability {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 12px;
  padding: 12px 14px;

  border: 1px solid var(--c-success-line);
  border-radius: var(--r-surface);

  background: var(--c-success-wash);
}

.pm-availability--off {
  border-color: var(--c-border);

  background: var(--c-surface-2);
}

.pm-availability-title {
  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-success);
}

.pm-availability--off .pm-availability-title {
  color: var(--c-text-3);
}

.pm-availability-text {
  margin-top: 2px;

  font-size: var(--fs-xs);

  color: var(--c-text-3);
}

.pm-block-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;

  gap: 8px;
}

.pm-block-title {
  display: block;

  margin-bottom: 6px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.pm-block-note {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.pm-desc {
  margin: 0;

  font-size: var(--fs-sm);
  line-height: 1.55;

  color: var(--c-text-2);
}

.pm-stock {
  display: inline-flex;
  align-items: center;

  gap: 8px;

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text);
}

.pm-stock .q-icon {
  color: var(--c-muted);
}

.pm-stock--out {
  color: var(--c-danger);
}

/* Sizes as a small bordered list, so every name, count and price lines up in its own column. */
.pm-variant-list {
  margin: 0;
  padding: 0;
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  list-style: none;
}

.pm-variant-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto minmax(72px, auto);
  align-items: center;

  gap: 12px;
  padding: 8px 12px;

  border-bottom: 1px solid var(--c-hairline);

  font-size: var(--fs-sm);
}

.pm-variant-row:last-child {
  border-bottom: none;
}

.pm-variant-size {
  overflow: hidden;

  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;
  text-transform: capitalize;

  color: var(--c-text);
}

.pm-variant-qty {
  font-size: var(--fs-xs);

  color: var(--c-text-3);
}

.pm-variant-price {
  font-weight: 700;
  text-align: right;

  color: var(--c-text);
}

.pm-variant-row--out .pm-variant-qty {
  font-weight: 600;

  color: var(--c-danger);
}

.pm-chart {
  position: relative;

  height: 150px;
  overflow: hidden;

  border: 1px solid var(--c-hairline);
  border-radius: var(--r-control);

  background: var(--c-surface-2);
}

.pm-chart-empty {
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 2;

  padding: 5px 12px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-pill);

  background: #ffffff;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-3);

  transform: translate(-50%, -50%);
}

.pm-chart--muted {
  opacity: 0.3;

  filter: grayscale(100%);
}

/* EDIT */

.pm-fields {
  display: flex;
  flex-direction: column;

  gap: 16px;
}

.pm-textarea :deep(textarea) {
  min-height: 72px;
}

/* Pricing sits in its own tinted panel, with a switch for sizes. */
.pm-section {
  display: flex;
  flex-direction: column;

  gap: 12px;
  padding: 16px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  background: var(--c-surface-2);
}

.pm-section :deep(.q-field__control) {
  background: #ffffff;
}

.pm-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 8px;
}

.pm-section-title {
  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.pm-toggle {
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-text-3);
}

.pm-row {
  display: grid;
  grid-template-columns: 1fr 1fr;

  gap: 12px;
}

.pm-variants {
  display: flex;
  flex-direction: column;

  gap: 8px;
}

.pm-variant {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr) minmax(0, 1fr) 32px;
  align-items: start;

  gap: 8px;
}

.pm-variant--labels span {
  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.pm-variant-remove {
  margin-top: 4px;

  color: var(--c-muted);
}

.pm-variant-remove:hover {
  color: var(--c-danger);
}

.pm-add-variant {
  align-self: flex-start;
}

/* PHOTO */

.pm-photo {
  display: flex;
  flex-direction: column;
  order: -1;
}

.pm-photo-tip {
  margin-top: 8px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.pm-photo-frame {
  width: 100%;
  aspect-ratio: 1;
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  background: var(--c-surface);
}

.pm-photo-frame img {
  width: 100%;
  height: 100%;

  object-fit: contain;
}

.pm-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 6px;
  width: 100%;
  aspect-ratio: 1;

  border: 1.5px dashed var(--c-border-strong);
  border-radius: var(--r-surface);

  background: var(--c-surface-2);

  font-family: inherit;

  cursor: pointer;

  transition: border-color 0.15s, background-color 0.15s;
}

.pm-dropzone:hover {
  border-color: var(--c-brand);

  background: var(--c-brand-tint);
}

.pm-dropzone:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

.pm-dropzone-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 64px;
  height: 64px;
  margin-bottom: 6px;

  border-radius: var(--r-surface);

  background: #ffffff;
  color: var(--c-brand);

  box-shadow: var(--sh-card);
}

.pm-dropzone-title {
  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.pm-dropzone-text {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.pm-photo-actions {
  display: flex;
  flex-wrap: wrap;

  gap: 8px;
  margin-top: 10px;
}

.pm-remove {
  height: 38px;

  border-radius: var(--r-control);

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-danger);
}

.pm-hidden {
  display: none;
}

/* PHOTO SOURCE */

.pm-source-dialog {
  width: 400px;
}

.pm-crop-dialog {
  width: 480px;
}

.pm-sources {
  gap: 10px;
  padding-bottom: 24px;
}

.pm-source {
  display: flex;
  align-items: center;

  gap: 12px;
  width: 100%;
  padding: 12px 14px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-surface);

  background: #ffffff;

  font-family: inherit;
  text-align: left;

  cursor: pointer;

  transition: border-color 0.15s, background-color 0.15s;
}

.pm-source:hover {
  border-color: var(--c-brand);

  background: var(--c-brand-tint);
}

.pm-source:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 2px;
}

.pm-source-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 40px;
  height: 40px;

  border-radius: var(--r-control);

  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.pm-source-body {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.pm-source-title {
  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.pm-source-text {
  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.pm-source-arrow {
  color: var(--c-muted);
}

/* CAMERA */

.pm-camera {
  display: flex;
  flex-direction: column;
}

.pm-camera-view {
  margin: 18px 24px 0;
  aspect-ratio: 4 / 3;
  max-height: 60vh;
  overflow: hidden;

  border-radius: var(--r-control);

  background: #000000;
}

.pm-camera.pm-dialog--full .pm-camera-view {
  flex: 1;

  aspect-ratio: auto;
  max-height: none;
  margin: 16px 0 0;

  border-radius: 0;
}

.pm-camera-feed {
  display: block;

  width: 100%;
  height: 100%;

  object-fit: cover;
}

/* Narrow dialogs stack the photo above the details. */
@media (max-width: 720px) {
  .pm-grid,
  .pm-grid--view {
    grid-template-columns: minmax(0, 1fr);
  }

  .pm-block--grow .pm-chart {
    flex: none;

    height: 150px;
  }

  .pm-photo {
    order: -1;
  }

  .pm-photo-frame,
  .pm-dropzone {
    aspect-ratio: auto;
    height: 200px;
  }
}

@media (max-width: 480px) {
  .pm-body {
    padding: 16px 18px;
  }

  .pm-row {
    grid-template-columns: 1fr;
  }

  .pm-variant {
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) minmax(0, 0.9fr) 28px;

    gap: 6px;
  }
}
</style>
