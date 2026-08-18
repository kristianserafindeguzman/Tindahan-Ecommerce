<template>
  <!-- 1. MAIN FORM MODAL (Bubukas lang pag pinindot ang Add Product) -->
  <q-dialog v-model="isOpen" persistent @hide="resetForm">
    <q-card style="width: 850px; max-width: 95vw; border-radius: 12px;" class="bg-white overflow-hidden shadow-4">
      
      <!-- Modal Header -->
      <q-card-section class="row items-center q-px-xl q-py-md header-gradient text-white">
        <div class="text-h6 text-weight-bolder tracking-tight">Add New Product</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup color="white" size="sm" class="opacity-80 hover-opacity-100" />
      </q-card-section>

      <!-- Form Area -->
      <q-card-section class="q-pt-xl q-px-xl q-pb-xl">
        <q-form @submit.prevent="submitForm">
          <div class="row q-col-gutter-xl">
            
            <!-- LEFT COLUMN: DETAILS -->
            <div class="col-12 col-md-7">
              <div class="q-mb-md">
                <div class="input-label q-mb-xs">Product Name</div>
                <q-input v-model="form.product_name" outlined dense placeholder="e.g., Classic T-Shirt" :rules="[val => !!val || 'Product name is required']" hide-bottom-space class="custom-input" />
              </div>

              <div class="q-mb-lg">
                <div class="input-label q-mb-xs">Category</div>
                <q-select v-model="form.category_id" :options="categories" option-value="category_id" option-label="category_name" emit-value map-options outlined dense :rules="[val => !!val || 'Category is required']" hide-bottom-space class="custom-input" placeholder="Select a category" />
                <div v-if="form.category_id" class="text-caption text-blue-grey-6 q-mt-sm">
                    <q-icon name="info" class="q-mr-xs" />
                    {{ selectedAddCategoryGuide }}
                </div>
              </div>

              <div class="q-mb-md">
                <div class="input-label q-mb-xs">Description (Optional)</div>
                <q-input v-model="form.description" type="textarea" outlined dense placeholder="e.g., Description of the product" hide-bottom-space class="custom-input" />
              </div>

              <div class="q-mb-md q-mt-md">
                <q-checkbox v-model="hasVariants" label="This product has different sizes/variants" color="red-9" class="custom-checkbox text-weight-bold text-slate-700" />
              </div>

              <div v-if="!hasVariants" class="row q-col-gutter-md q-mt-xs">
                <div class="col-6">
                  <div class="input-label q-mb-xs">Price (₱)</div>
                  <q-input v-model.number="form.price" type="number" outlined dense min="0" step="0.01" :rules="[val => val !== null && val >= 0 || 'Valid price required']" hide-bottom-space class="custom-input" />
                </div>
                <div class="col-6">
                  <div class="input-label q-mb-xs">Quantity</div>
                  <q-input v-model.number="form.stock_quantity" type="number" outlined dense min="0" :rules="[val => val !== null && val >= 0 || 'Valid quantity required']" hide-bottom-space class="custom-input" />
                </div>
              </div>

              <q-slide-transition>
                <div v-if="hasVariants" class="variants-card q-mt-sm q-pa-md">
                  <div class="text-subtitle2 text-weight-bold text-slate-700 q-mb-md">Variants (Sizes)</div>
                  <div v-for="(variant, index) in form.variants" :key="index" class="row q-col-gutter-sm items-start q-mb-sm">
                    <div class="col-4">
                      <q-input v-model="variant.size" outlined dense placeholder="Size (e.g. Small)" :rules="[val => !!val || 'Required']" class="variant-input" />
                    </div>
                    <div class="col-3">
                      <q-input v-model.number="variant.price" type="number" outlined dense min="0" step="0.01" placeholder="Price" hide-bottom-space class="variant-input" />
                    </div>
                    <div class="col-3">
                      <q-input v-model.number="variant.quantity" type="number" outlined dense min="0" placeholder="Qty" hide-bottom-space class="variant-input" />
                    </div>
                    <div class="col-2 flex flex-center" style="padding-top: 4px;">
                      <q-btn icon="delete" flat round color="grey-5" size="sm" @click="removeVariant(index)" :disable="form.variants.length === 1" class="hover-red" />
                    </div>
                  </div>
                  <q-btn outline icon="add" label="Add Variant" size="sm" class="btn-add-variant q-mt-xs" no-caps @click="addVariant" />
                </div>
              </q-slide-transition>
            </div>

            <!-- RIGHT COLUMN: IMAGE UPLOAD ZONE -->
            <div class="col-12 col-md-5 flex column">
              <div class="input-label q-mb-xs">Product Image</div>
              
              <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="onFileSelected" />

              <div v-if="imagePreview" class="q-mb-md">
                <q-img :src="imagePreview" ratio="1" class="rounded-borders shadow-2" />
                <div class="row q-gutter-sm q-mt-sm justify-center">
                  <q-btn outline color="negative" icon="delete" label="Remove" @click="removePhoto" />
                  <q-btn outline color="primary" icon="crop" label="Crop Photo" @click="openCropperForAdd" />
                </div>
              </div>

              <div v-else class="image-upload-box flex-1 relative-position">
                <!-- Ito ang mag-tritrigger sa pagpili kung Camera o Browse -->
                <div class="flex column flex-center full-height w-full q-pa-lg text-center cursor-pointer hover-bg-light" @click="showOptionMenu = true">
                  <q-icon name="cloud_upload" size="64px" color="blue-grey-3" class="q-mb-sm opacity-80" />
                  <div class="text-subtitle1 text-weight-bolder text-slate-700">Upload Product Image</div>
                  <div class="text-caption text-slate-500">Click to add a photo</div>
                </div>
              </div>
            </div>
            
          </div>

          <!-- FOOTER ACTIONS -->
          <div class="row justify-end q-mt-xl q-pt-md border-top-light">
            <q-btn flat label="Cancel" color="grey-8" no-caps v-close-popup class="text-weight-bold q-mr-md" />
            <q-btn type="submit" unelevated label="SAVE PRODUCT" class="btn-save text-white text-weight-bold" no-caps :loading="saving" />
          </div>

        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>

  <!-- 2. OPTION MENU MODAL (Yung puting pop-up) -->
  <q-dialog v-model="showOptionMenu">
    <q-card style="width: 400px; max-width: 90vw; border-radius: 12px;" class="bg-white q-pa-sm">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6 text-weight-bolder text-slate-700">Add Product Photo</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pt-lg q-pb-lg">
        <div class="row q-col-gutter-md flex-center">
          <div class="col-12">
            <!-- Pag pinindot 'to, bubukas diretso yung camera -->
            <q-btn unelevated icon="camera_alt" label="OPEN CAMERA" class="full-width text-weight-bold btn-save text-white" style="border-radius: 8px; padding: 12px;" @click="openCameraViewfinder" />
          </div>
          <div class="col-12 text-center text-weight-bold text-slate-400 q-py-sm">
            OR
          </div>
          <div class="col-12">
            <!-- Pag pinindot 'to, bubukas folder mo -->
            <q-btn outline icon="cloud_upload" label="UPLOAD FROM DEVICE" class="full-width text-weight-bold btn-outline-dark" style="border-radius: 8px; padding: 12px; border-width: 2px;" @click="triggerDeviceUpload" />
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>

  <!-- 3. CAMERA VIEWFINDER MODAL (Diretso na dito. WALA NANG DARK MENU) -->
  <q-dialog v-model="showCameraLens" persistent @show="startCamera" @hide="stopCamera">
    <q-card style="width: 650px; max-width: 95vw; border-radius: 12px;" class="bg-white overflow-hidden shadow-4">
      <q-card-section class="row items-center q-px-lg q-py-md bg-slate-50 border-bottom-light">
        <div class="text-subtitle1 text-weight-bolder text-slate-700">Take Product Photo</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup color="grey-7" class="hover-red" />
      </q-card-section>

      <q-card-section class="q-pa-lg flex flex-center bg-black">
        <video ref="videoElement" autoplay playsinline class="camera-feed"></video>
      </q-card-section>

      <q-card-actions align="center" class="q-pa-md bg-white">
        <q-btn unelevated icon="camera" label="CAPTURE PHOTO" class="btn-save text-white text-weight-bold" no-caps @click="capturePhoto" />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <!-- CROPPER MODAL -->
  <ImageCaptureModal v-model="showCropper" :initialImage="imagePreview" :aspectRatio="1" @captured="handleAddImageCaptured" />

</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import ImageCaptureModal from '@/components/modals/ImageCaptureModal.vue'

// Modal Controllers
const props = defineProps({ modelValue: Boolean })
const emit = defineEmits(['update:modelValue', 'refresh'])
const $q = useQuasar()

// State ng Main Form Modal
const isOpen = ref(props.modelValue)
watch(() => props.modelValue, (val) => isOpen.value = val)
watch(isOpen, (val) => emit('update:modelValue', val))

// State ng magkasunod na pop-ups (Option tapos Camera)
const showOptionMenu = ref(false)
const showCameraLens = ref(false)
const showCropper = ref(false)

// Form Variables
const categories = ref([])
const hasVariants = ref(false)
const saving = ref(false)
const imagePreview = ref(null)
const fileInput = ref(null)
const videoElement = ref(null)
let stream = null

const selectedAddCategoryGuide = computed(() => {
  if (!form.value.category_id && !form.value.category) return 'Select a category to see its description.'
  
  const matchedCategory = categories.value.find(c => 
    c.category_id === form.value.category_id || 
    c.category_name === form.value.category || 
    c.category_name === form.value.category?.label
  )
  
  return matchedCategory?.description || 'No description available for this category.'
})

const form = ref({
  product_name: '',
  description: '',
  category_id: null,
  price: null,
  stock_quantity: null,
  product_picture: null,
  variants: [{ size: '', price: null, quantity: null }]
})

// === FUNCTIONS PARA SA OPTIONS AT CAMERA === //

const openCameraViewfinder = () => {
  showOptionMenu.value = false // Isasara yung puting menu
  showCameraLens.value = true  // Bubuksan rekta yung camera feed
}

const triggerDeviceUpload = () => {
  showOptionMenu.value = false
  if (fileInput.value) fileInput.value.click()
}

// Camera Hardware Controls
const startCamera = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    if (videoElement.value) {
      videoElement.value.srcObject = stream
    }
  } catch (err) {
    console.error("Camera access denied or unavailable", err)
  }
}

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
    stream = null
  }
}

// Para i-capture yung photo galing sa video feed
const capturePhoto = () => {
  if (!videoElement.value) return
  const canvas = document.createElement('canvas')
  canvas.width = videoElement.value.videoWidth
  canvas.height = videoElement.value.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(videoElement.value, 0, 0, canvas.width, canvas.height)
  
  const dataUrl = canvas.toDataURL('image/png')
  canvas.toBlob((blob) => {
    const file = new File([blob], 'product_capture.png', { type: 'image/png' })
    form.value.product_picture = file
    imagePreview.value = dataUrl
    
    // Isara na yung camera lens
    stopCamera()
    showCameraLens.value = false
  }, 'image/png')
}

// Para sa regular file upload (Browse)
const onFileSelected = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.value.product_picture = file
    imagePreview.value = URL.createObjectURL(file)
  }
}

const removePhoto = () => {
  form.value.product_picture = null
  imagePreview.value = null
  if (fileInput.value) fileInput.value.value = ''
}

const openCropperForAdd = () => {
  showCropper.value = true
}

const handleAddImageCaptured = ({ file, dataUrl }) => {
  if (!file) return;
  form.value.product_picture = file;
  imagePreview.value = dataUrl || URL.createObjectURL(file);
}

// === FORM API FUNCTIONS === //
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

const addVariant = () => { form.value.variants.push({ size: '', price: null, quantity: null }) }
const removeVariant = (index) => { if (form.value.variants.length > 1) form.value.variants.splice(index, 1) }

const resetForm = () => {
  form.value = { product_name: '', description: '', category_id: null, price: null, stock_quantity: null, product_picture: null, variants: [{ size: '', price: null, quantity: null }] }
  hasVariants.value = false
  removePhoto()
}

const submitForm = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('product_name', form.value.product_name)
    if (form.value.description) formData.append('description', form.value.description)
    formData.append('category_id', form.value.category_id)
    if (form.value.product_picture) formData.append('product_picture', form.value.product_picture)
    if (hasVariants.value) {
      formData.append('variants', JSON.stringify(form.value.variants))
    } else {
      formData.append('price', form.value.price)
      formData.append('stock_quantity', form.value.stock_quantity)
    }

    await api.post('/vendor/products', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    $q.notify({ type: 'positive', message: 'Product added successfully' })
    resetForm()
    isOpen.value = false
    emit('refresh')
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Failed to save product' })
  } finally {
    saving.value = false
  }
}

onBeforeUnmount(() => {
  stopCamera()
})
</script>

<style scoped>
.header-gradient { background: linear-gradient(135deg, #B91C1C 0%, #7F1D1D 100%); }
.tracking-tight { letter-spacing: -0.02em; }
.opacity-80 { opacity: 0.8; transition: opacity 0.2s ease; }
.hover-opacity-100:hover { opacity: 1; }

.text-brand-red { color: #cc3333; }
.text-slate-700 { color: #334155; }
.text-slate-500 { color: #64748b; }
.text-slate-400 { color: #94a3b8; }
.input-label { font-size: 13px; font-weight: 700; color: #2F3C4D; }
.hover-underline:hover { text-decoration: underline; }

.custom-input :deep(.q-field__control) { border-radius: 8px; background-color: #ffffff; }
.custom-input :deep(.q-field__control:before) { border: 1px solid #e2e8f0; }
.custom-input :deep(.q-field--focused .q-field__control) { box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.3); border-color: #3b82f6; }
.custom-input :deep(.q-placeholder) { color: #94a3b8; }

.custom-checkbox :deep(.q-checkbox__bg) { border: 1.5px solid #94a3b8; border-radius: 4px; width: 20px; height: 20px; }
.custom-checkbox :deep(.q-checkbox__inner--truthy .q-checkbox__bg) { background: #cc3333; border-color: #cc3333; }

.variants-card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
.variant-input :deep(.q-field__control) { border-radius: 6px; height: 38px; }
.variant-input :deep(.q-field__control:before) { border: 1px solid #e2e8f0; }
.variant-input :deep(.q-field--focused .q-field__control) { border-color: #3b82f6 !important; box-shadow: 0 0 0 1px #3b82f6 !important; }
.btn-add-variant { border-radius: 6px; color: #475569 !important; border-color: #cbd5e1 !important; font-weight: 600; padding: 4px 12px; }

.image-upload-box { border: 2px dashed #94a3b8; border-radius: 12px; background-color: #f1f5f9; min-height: 280px; overflow: hidden; transition: all 0.2s ease; }
.image-upload-box:hover { border-color: #64748b; }
.hover-bg-light:hover { background-color: #e2e8f0; }
.preview-img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }
.hidden { display: none; }

.btn-outline-dark { color: #334155 !important; border-color: #334155 !important; }
.border-top-light { border-top: 1px solid #f1f5f9; }
.btn-save { background-color: #cc3333 !important; border-radius: 8px; padding: 8px 28px; transition: transform 0.2s ease, box-shadow 0.2s ease; }
.btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(204, 51, 51, 0.3); }
.w-full { width: 100%; }
.hover-red:hover { color: #cc3333 !important; }
.border-bottom-light { border-bottom: 1px solid #e2e8f0; }
.bg-slate-50 { background-color: #f8fafc; }
.camera-feed { width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; background-color: #000; }
</style>