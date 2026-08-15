<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="width: 850px; max-width: 95vw; border-radius: 12px;" class="bg-white overflow-hidden shadow-4">
      
      <!-- ================= MODAL HEADER ================= -->
      <q-card-section class="row items-center q-px-xl q-py-md header-gradient text-white">
        <div class="text-h6 text-weight-bolder tracking-tight">Product Details</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup color="white" size="sm" class="opacity-80 hover-opacity-100" />
      </q-card-section>

      <!-- ================= FORM AREA ================= -->
      <q-card-section class="q-pt-xl q-px-xl q-pb-xl">
        <q-form @submit.prevent="submitForm">
          <div class="row q-col-gutter-xl">
            
            <!-- LEFT COLUMN: DETAILS -->
            <div class="col-12 col-md-7">
              
              <!-- Product Name -->
              <div class="q-mb-md">
                <div class="input-label q-mb-xs">Product Name</div>
                <q-input 
                  v-model="form.product_name" 
                  outlined 
                  dense 
                  placeholder="e.g., Classic T-Shirt"
                  :rules="[val => !!val || 'Product name is required']" 
                  hide-bottom-space 
                  class="custom-input" 
                />
              </div>

              <!-- Category -->
              <div class="q-mb-md">
                <div class="input-label q-mb-xs">Category</div>
                <q-select
                  v-model="form.category_id"
                  :options="categories"
                  option-value="category_id"
                  option-label="category_name"
                  emit-value
                  map-options
                  outlined
                  dense
                  placeholder="Select a category"
                  :rules="[val => !!val || 'Category is required']"
                  hide-bottom-space
                  class="custom-input"
                />
                <q-slide-transition>
                  <q-banner v-if="form.category_id && selectedEditCategoryGuide" class="bg-grey-1 text-grey-8 q-mt-sm rounded-borders" dense>
                    <template v-slot:avatar>
                      <q-icon name="info" color="grey-6" size="20px" />
                    </template>
                    <span class="text-caption">{{ selectedEditCategoryGuide }}</span>
                  </q-banner>
                </q-slide-transition>
              </div>

              <!-- Active Status Toggle -->
              <div class="q-mb-md q-mt-md">
                <q-toggle 
                  v-model="isActive" 
                  label="Available for Sale (Active)" 
                  color="green-6" 
                  class="text-weight-bold text-slate-700 custom-toggle" 
                />
              </div>

              <!-- Variants Checkbox -->
              <div class="q-mb-md">
                <q-checkbox 
                  v-model="hasVariants" 
                  label="This product has different sizes/variants" 
                  color="red-9" 
                  class="custom-checkbox text-weight-bold text-slate-700" 
                />
              </div>

              <!-- Single Size Pricing/Qty -->
              <div v-if="!hasVariants" class="row q-col-gutter-md q-mt-xs">
                <div class="col-6">
                  <div class="input-label q-mb-xs">Price (₱)</div>
                  <q-input v-model.number="form.price" type="number" outlined dense min="0" step="0.01" hide-bottom-space class="custom-input" />
                </div>
                <div class="col-6">
                  <div class="input-label q-mb-xs">Quantity</div>
                  <q-input v-model.number="form.stock_quantity" type="number" outlined dense min="0" hide-bottom-space class="custom-input" />
                </div>
              </div>

              <!-- Variants Pricing/Qty -->
              <q-slide-transition>
                <div v-if="hasVariants" class="variants-card q-mt-sm q-pa-md">
                  <div class="text-subtitle2 text-weight-bold text-slate-700 q-mb-md">Variants (Sizes)</div>
                  <div v-for="(variant, index) in form.variants" :key="index" class="row q-col-gutter-sm items-start q-mb-sm">
                    <div class="col-4">
                      <q-input v-model="variant.size" outlined dense placeholder="Size" :rules="[val => !!val || 'Required']" hide-bottom-space class="variant-input bg-white" />
                    </div>
                    <div class="col-3">
                      <q-input v-model.number="variant.price" type="number" outlined dense min="0" step="0.01" placeholder="Price" hide-bottom-space class="variant-input bg-white" />
                    </div>
                    <div class="col-3">
                      <q-input v-model.number="variant.quantity" type="number" outlined dense min="0" placeholder="Qty" hide-bottom-space class="variant-input bg-white" />
                    </div>
                    <div class="col-2 flex flex-center" style="padding-top: 4px;">
                      <q-btn icon="delete" flat round color="grey-5" size="sm" @click="removeVariant(index)" :disable="form.variants.length === 1" class="hover-red" />
                    </div>
                  </div>
                  <q-btn outline icon="add" label="Add Variant" size="sm" class="btn-add-variant q-mt-xs" no-caps @click="addVariant" />
                </div>
              </q-slide-transition>
            </div>

            <!-- RIGHT COLUMN: IMAGE -->
            <div class="col-12 col-md-5 flex column">
              <div class="input-label q-mb-xs">Product Image</div>
              
              <!-- Image Upload Zone -->
              <div class="image-upload-box flex-1 relative-position" @click="showOptionMenu = true">
                <img v-if="imagePreview" :src="imagePreview" class="preview-img" />
                <div v-else class="flex column flex-center full-height w-full q-pa-lg text-center cursor-pointer">
                  <q-icon name="cloud_upload" size="64px" color="blue-grey-3" class="q-mb-sm opacity-80" />
                  <div class="text-subtitle1 text-weight-bolder text-slate-700">Update Product Image</div>
                  <div class="text-caption text-slate-500">Click to add a photo</div>
                </div>
                <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleImageUpload" />
              </div>

              <!-- Action buttons for existing image -->
              <q-slide-transition>
                <div v-if="imagePreview" class="row q-gutter-sm q-mt-md justify-center">
                  <q-btn outline color="blue-grey-7" icon="crop" label="Crop Photo" no-caps class="btn-glass-outline text-weight-bold flex-1" @click="openCropperForEdit" />
                  <q-btn outline color="red-9" icon="delete" label="Remove" no-caps class="btn-glass-outline text-weight-bold flex-1" @click="removeEditImage" />
                </div>
              </q-slide-transition>
            </div>
          </div>

          <!-- FOOTER ACTIONS -->
          <div class="row justify-end q-mt-xl q-pt-md border-top-light">
            <q-btn flat label="Cancel" color="grey-8" no-caps v-close-popup class="text-weight-bold q-mr-md" />
            <q-btn type="submit" unelevated label="SAVE CHANGES" class="btn-save text-white text-weight-bold" no-caps :loading="saving" />
          </div>

        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>

  <!-- ================= OPTION MENU MODAL ================= -->
  <q-dialog v-model="showOptionMenu">
    <q-card style="width: 400px; max-width: 90vw; border-radius: 12px;" class="bg-white q-pa-sm shadow-4">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6 text-weight-bolder text-slate-700">Add Product Photo</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup color="grey-7" />
      </q-card-section>

      <q-card-section class="q-pt-lg q-pb-lg">
        <div class="row q-col-gutter-md flex-center">
          <div class="col-12">
            <q-btn unelevated icon="camera_alt" label="OPEN CAMERA" class="full-width text-weight-bold btn-save text-white" style="border-radius: 8px; padding: 12px;" @click="openCameraViewfinder" />
          </div>
          <div class="col-12 text-center text-weight-bold text-slate-400 q-py-sm">
            OR
          </div>
          <div class="col-12">
            <q-btn outline icon="cloud_upload" label="UPLOAD FROM DEVICE" class="full-width text-weight-bold btn-outline-dark" style="border-radius: 8px; padding: 12px; border-width: 2px;" @click="triggerDeviceUpload" />
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>

  <!-- ================= CAMERA VIEWFINDER MODAL ================= -->
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
        <q-btn unelevated icon="camera" label="CAPTURE PHOTO" class="btn-save text-white text-weight-bold q-px-xl" no-caps @click="capturePhoto" />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <!-- ================= CROPPER MODAL ================= -->
  <ImageCaptureModal v-model="showCropper" :initialImage="imagePreview" :aspectRatio="1" @captured="handleEditImageCaptured" />

</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import ImageCaptureModal from '@/components/modals/ImageCaptureModal.vue'

const props = defineProps({
  modelValue: Boolean,
  product: Object
})
const emit = defineEmits(['update:modelValue', 'refresh'])
const $q = useQuasar()

const isOpen = ref(props.modelValue)
watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val && props.product) {
    populateForm()
  }
})
watch(isOpen, (val) => emit('update:modelValue', val))

const categories = ref([])
const hasVariants = ref(false)
const isActive = ref(true)
const saving = ref(false)
const fileInput = ref(null)
const imagePreview = ref(null)

const form = ref({
  product_name: '',
  category_id: null,
  price: null,
  stock_quantity: null,
  product_picture: null,
  variants: [{ size: '', price: null, quantity: null }]
})

const showOptionMenu = ref(false)
const showCameraLens = ref(false)
const showCropper = ref(false)
const videoElement = ref(null)
let stream = null

const selectedEditCategoryGuide = computed(() => {
  if (!form.value.category_id && !form.value.category) return 'Select a category to see its description.'
  
  const matchedCategory = categories.value.find(c => 
    c.category_id === form.value.category_id || 
    c.category_name === form.value.category || 
    c.category_name === form.value.category?.label
  )
  
  return matchedCategory?.description || 'No description available for this category.'
})

const populateForm = () => {
  const p = props.product
  form.value.product_name = p.product_name
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

const addVariant = () => {
  form.value.variants.push({ size: '', price: null, quantity: null })
}

const removeVariant = (index) => {
  if (form.value.variants.length > 1) {
    form.value.variants.splice(index, 1)
  }
}

const triggerFileInput = () => {
  fileInput.value.click()
}

const handleImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.value.product_picture = file
    imagePreview.value = URL.createObjectURL(file)
  }
}

const openCropperForEdit = () => {
  showCropper.value = true
}

const removeEditImage = () => {
  imagePreview.value = null
  form.value.product_picture = null
}

const handleEditImageCaptured = ({ file, dataUrl }) => {
  if (!file) return;
  form.value.product_picture = file;
  imagePreview.value = dataUrl || URL.createObjectURL(file);
};

const openCameraViewfinder = () => {
  showOptionMenu.value = false
  showCameraLens.value = true
}

const triggerDeviceUpload = () => {
  showOptionMenu.value = false
  if (fileInput.value) fileInput.value.click()
}

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
    
    stopCamera()
    showCameraLens.value = false
  }, 'image/png')
}

const submitForm = async () => {
  saving.value = true
  try {
    // We have to simulate PATCH with FormData using POST and _method=PATCH
    const formData = new FormData()
    formData.append('_method', 'PATCH')
    formData.append('product_name', form.value.product_name)
    formData.append('category_id', form.value.category_id)
    formData.append('status', isActive.value ? 'active' : 'archived')
    
    if (form.value.product_picture) {
      formData.append('product_picture', form.value.product_picture)
    }

    if (hasVariants.value) {
      formData.append('variants', JSON.stringify(form.value.variants))
    } else {
      // Clear variants if unchecked
      formData.append('variants', '')
      formData.append('price', form.value.price)
      formData.append('stock_quantity', form.value.stock_quantity)
    }

    await api.post(`/vendor/products/${props.product.inventory_id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    $q.notify({ type: 'positive', message: 'Product updated successfully' })
    isOpen.value = false
    emit('refresh')
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Failed to update product' })
    console.error(err)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
/* Gradient Header Styling */
.header-gradient { background: linear-gradient(135deg, #B91C1C 0%, #7F1D1D 100%); }
.tracking-tight { letter-spacing: -0.02em; }
.opacity-80 { opacity: 0.8; transition: opacity 0.2s ease; }
.hover-opacity-100:hover { opacity: 1; }

/* Colors & Typography */
.text-brand-red { color: #cc3333; }
.text-slate-700 { color: #334155; }
.text-slate-500 { color: #64748b; }
.text-slate-400 { color: #94a3b8; }
.bg-slate-50 { background-color: #f8fafc; }
.bg-grey-1 { background-color: #f1f5f9; }
.input-label {
  font-size: 13px;
  font-weight: 700;
  color: #2F3C4D; 
}
.hover-underline:hover { text-decoration: underline; }

/* Standard Inputs - Fixed Native Input Color */
.custom-input :deep(.q-field__control) {
  border-radius: 8px;
  background-color: #ffffff;
}
.custom-input :deep(.q-field__control:before) { border: 1px solid #e2e8f0; }
.custom-input :deep(.q-field--focused .q-field__control) {
  box-shadow: 0 0 0 1px rgba(185, 28, 28, 0.15); 
  border-color: #B91C1C;
}
.custom-input :deep(.q-placeholder) { color: #94a3b8; }
.custom-input :deep(.q-field__native),
.custom-input :deep(input) { 
  color: #1e293b !important; 
  font-weight: 500; 
}

/* Checkbox & Toggle */
.custom-checkbox :deep(.q-checkbox__bg) {
  border: 1.5px solid #94a3b8;
  border-radius: 4px;
  width: 20px;
  height: 20px;
}
.custom-checkbox :deep(.q-checkbox__inner--truthy .q-checkbox__bg) {
  background: #cc3333;
  border-color: #cc3333;
}
.custom-toggle :deep(.q-toggle__inner) {
  color: #16a34a;
}

/* ================= VARIANTS SECTION ================= */
.variants-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.variant-input :deep(.q-field__control) {
  border-radius: 6px;
  height: 38px;
}
.variant-input :deep(.q-field__control:before) { border: 1px solid #e2e8f0; }
.variant-input :deep(.q-field--focused .q-field__control) {
  border-color: #3b82f6 !important;
  box-shadow: 0 0 0 1px #3b82f6 !important;
}
.variant-input :deep(.q-field--error .q-field__control) {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 1px #ef4444 !important;
}
.variant-input :deep(.q-field__native),
.variant-input :deep(input) { 
  color: #1e293b !important; 
  font-weight: 500; 
}
.btn-add-variant {
  border-radius: 6px;
  color: #475569 !important;
  border-color: #cbd5e1 !important;
  font-weight: 600;
  padding: 4px 12px;
}

/* ================= IMAGE UPLOAD BOX ================= */
.image-upload-box {
  border: 2px dashed #94a3b8;
  border-radius: 12px;
  background-color: #f1f5f9;
  min-height: 280px;
  overflow: hidden;
  transition: all 0.2s ease;
  cursor: pointer;
}
.image-upload-box:hover {
  border-color: #64748b;
  background-color: #e2e8f0;
}
.preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 10px;
}
.hidden { display: none; }

/* Buttons & Footer */
.btn-outline-dark {
  color: #334155 !important;
  border-color: #334155 !important;
}
.btn-glass-outline {
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid rgba(203, 213, 225, 0.8);
  transition: all 0.2s ease;
}
.btn-glass-outline:hover {
  background: #f8fafc !important;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
  transform: translateY(-1px);
}
.border-top-light { border-top: 1px solid #f1f5f9; }
.border-bottom-light { border-bottom: 1px solid #e2e8f0; }

.btn-save {
  background-color: #cc3333 !important; 
  border-radius: 8px;
  padding: 8px 28px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.btn-save:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(204, 51, 51, 0.3);
}

.w-full { width: 100%; }
.hover-red:hover { color: #cc3333 !important; }
.camera-feed {
  width: 100%;
  max-height: 400px;
  object-fit: cover;
  border-radius: 8px;
  background-color: #000;
}
</style>