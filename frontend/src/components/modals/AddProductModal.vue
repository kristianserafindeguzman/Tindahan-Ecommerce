<template>
  <!-- ADD PRODUCT — the vendor dialog shape: details on the left, the photo on the right. -->
  <q-dialog v-model="isOpen" persistent :maximized="$q.screen.xs" @hide="resetForm">
    <q-card class="vp-dialog pm-dialog" :class="{ 'pm-dialog--full': $q.screen.xs }">
      <!-- The whole card is the form, so Save runs the field checks before sending. -->
      <q-form greedy class="pm-form" @submit.prevent="submitForm">
        <div class="vp-dialog-head pm-head">
          <span class="vp-dialog-icon"><q-icon name="o_add_box" size="22px" /></span>
          <div>
            <div class="vp-dialog-title">Add product</div>
            <div class="vp-dialog-text">Fill in what customers will see in your store.</div>
          </div>
          <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" :disable="saving" />
        </div>

        <div class="pm-body">
          <div class="pm-grid">
            <div class="pm-fields">
              <div>
                <label class="vp-field-label" for="pm-add-name">Product name</label>
                <q-input
                  v-model="form.product_name"
                  for="pm-add-name"
                  outlined
                  dense
                  hide-bottom-space
                  placeholder="e.g. Lucky Me Pancit Canton"
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
                <div v-if="form.category_id" class="pm-hint">
                  <q-icon name="o_info" size="14px" />
                  <span>{{ selectedAddCategoryGuide }}</span>
                </div>
              </div>

              <div>
                <label class="vp-field-label" for="pm-add-desc">Description <span class="vp-field-optional">(optional)</span></label>
                <q-input
                  v-model="form.description"
                  for="pm-add-desc"
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
                    <label class="vp-field-label" for="pm-add-price">Price (₱)</label>
                    <q-input
                      v-model.number="form.price"
                      for="pm-add-price"
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
                    <label class="vp-field-label" for="pm-add-qty">Quantity</label>
                    <q-input
                      v-model.number="form.stock_quantity"
                      for="pm-add-qty"
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
                <q-btn outline no-caps color="primary" icon="o_crop" label="Crop" class="vp-pill-btn" @click="openCropperForAdd" />
                <q-btn outline no-caps color="primary" icon="o_sync" label="Replace" class="vp-pill-btn" @click="showOptionMenu = true" />
                <q-btn flat no-caps icon="o_delete" label="Remove" class="pm-remove" @click="removePhoto" />
              </div>
              <div class="pm-photo-tip">A square photo on a plain background looks best in your store.</div>
              <input ref="fileInput" type="file" class="pm-hidden" accept="image/*" @change="onFileSelected" />
            </div>
          </div>
        </div>

        <div class="vp-dialog-actions pm-actions">
          <q-btn v-close-popup outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" :disable="saving" />
          <q-btn type="submit" unelevated no-caps color="primary" label="Save Product" class="vp-dialog-btn" :loading="saving" />
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
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import PhotoCropper from '@/components/shared/PhotoCropper.vue'

const props = defineProps({ modelValue: Boolean })
const emit = defineEmits(['update:modelValue', 'refresh'])
const $q = useQuasar()

const isOpen = ref(props.modelValue)
watch(() => props.modelValue, val => { isOpen.value = val })
watch(isOpen, val => emit('update:modelValue', val))

const showOptionMenu = ref(false)
const showCameraLens = ref(false)
const showCropper = ref(false)

const categories = ref([])
const hasVariants = ref(false)
const saving = ref(false)
const imagePreview = ref(null)
const fileInput = ref(null)
const videoElement = ref(null)
let stream = null

const form = ref({
  product_name: '',
  description: '',
  category_id: null,
  price: null,
  stock_quantity: null,
  product_picture: null,
  variants: [{ size: '', price: null, quantity: null }]
})

const selectedAddCategoryGuide = computed(() => {
  if (!form.value.category_id && !form.value.category) return 'Select a category to see its description.'

  const matchedCategory = categories.value.find(c =>
    c.category_id === form.value.category_id ||
    c.category_name === form.value.category ||
    c.category_name === form.value.category?.label
  )

  return matchedCategory?.description || 'No description available for this category.'
})

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
    console.error('Camera access denied or unavailable', err)
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

const onFileSelected = event => {
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

const cropperRef = ref(null)
const cropSrc = ref('')
// Set once the cropper has drawn the photo, so Apply Crop can't run on an empty frame.
const cropReady = ref(false)

const openCropperForAdd = () => {
  if (!imagePreview.value) return
  cropReady.value = false
  cropSrc.value = imagePreview.value
  showCropper.value = true
}

// Replaces the photo with the framed square, the same way the profile photos are cropped.
const applyCrop = async () => {
  const blob = await cropperRef.value?.toBlob()
  if (!blob) return
  form.value.product_picture = new File([blob], `product_${Date.now()}.jpg`, { type: 'image/jpeg' })
  imagePreview.value = URL.createObjectURL(blob)
  showCropper.value = false
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

const addVariant = () => { form.value.variants.push({ size: '', price: null, quantity: null }) }
const removeVariant = index => { if (form.value.variants.length > 1) form.value.variants.splice(index, 1) }

const resetForm = () => {
  form.value = { product_name: '', description: '', category_id: null, price: null, stock_quantity: null, product_picture: null, variants: [{ size: '', price: null, quantity: null }] }
  hasVariants.value = false
  removePhoto()
}

const submitForm = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('product_name', form.value.product_name.trim())
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
    $q.notify({ type: 'positive', message: 'Product added.' })
    resetForm()
    isOpen.value = false
    emit('refresh')
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Failed to save the product.' })
  } finally {
    saving.value = false
  }
}

onBeforeUnmount(stopCamera)
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

.pm-fields {
  display: flex;
  flex-direction: column;

  gap: 16px;
}

.pm-hint {
  display: flex;
  align-items: flex-start;

  gap: 6px;
  margin-top: 6px;

  font-size: var(--fs-xs);
  line-height: 1.45;

  color: var(--c-muted);
}

.pm-hint .q-icon {
  flex-shrink: 0;
  margin-top: 1px;
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
  .pm-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .pm-photo {
    order: -1;
  }

  .pm-dropzone,
  .pm-photo-frame {
    aspect-ratio: auto;
    height: 190px;
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
