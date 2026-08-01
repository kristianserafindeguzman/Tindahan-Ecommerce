<template>
  <q-dialog v-model="isOpen" persistent :maximized="state !== 'CHOOSING'">
    <q-card class="bg-dark text-white column" :class="state !== 'CHOOSING' ? 'full-width full-height' : ''" :style="state === 'CHOOSING' ? 'width: 350px; max-width: 80vw;' : ''">
      
      <!-- HEADER -->
      <q-card-section class="row items-center q-pb-sm bg-black">
        <div class="text-h6 text-weight-bold">Add Product Photo</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup @click="stopCamera" />
      </q-card-section>

      <!-- BODY -->
      <q-card-section class="col q-pa-none flex flex-center relative-position bg-grey-10">
        
        <!-- STATE: CHOOSING -->
        <div v-if="state === 'CHOOSING'" class="column items-center q-gutter-lg">
          <q-btn unelevated icon="camera_alt" label="Open Camera" color="red-8" size="lg" class="btn-premium" @click="startCamera" />
          <div class="text-grey-5 text-h6">OR</div>
          <q-btn outline icon="cloud_upload" label="Upload from Device" color="white" size="lg" @click="triggerFileInput" />
          <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleFileUpload" />
        </div>

        <!-- STATE: CAMERA LIVE -->
        <div v-if="state === 'CAMERA_LIVE'" class="full-width full-height relative-position">
          <video ref="videoEl" autoplay playsinline class="full-width full-height" style="object-fit: cover;"></video>
          <div class="absolute-bottom text-center q-pb-xl">
            <q-btn round color="white" text-color="dark" size="xl" icon="camera" @click="capturePhoto" class="shadow-4" />
          </div>
        </div>

        <!-- STATE: CROPPING -->
        <div v-show="state === 'CROPPING'" class="full-width full-height relative-position">
          <div class="cropper-container">
            <img ref="imageEl" :src="previewSrc" style="max-width: 100%; display: block;" />
          </div>
          <div class="absolute-bottom bg-black q-pa-md row justify-around">
            <q-btn flat icon="replay" label="Retake/Cancel" color="white" @click="resetToChoose" />
            <q-btn unelevated icon="check" label="Confirm Photo" color="red-8" @click="confirmCrop" />
          </div>
        </div>

      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'

const props = defineProps({
  modelValue: Boolean
})
const emit = defineEmits(['update:modelValue', 'captured'])

const isOpen = ref(props.modelValue)
watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val) {
    state.value = 'CHOOSING'
    previewSrc.value = null
  }
})
watch(isOpen, (val) => {
  emit('update:modelValue', val)
  if (!val) stopCamera()
})

// STATES: CHOOSING, CAMERA_LIVE, CROPPING
const state = ref('CHOOSING')

const fileInput = ref(null)
const videoEl = ref(null)
const imageEl = ref(null)
const previewSrc = ref(null)
let stream = null
let cropperInstance = null

// --- CAMERA LOGIC ---
const startCamera = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    state.value = 'CAMERA_LIVE'
    // Give DOM time to render video element
    setTimeout(() => {
      if (videoEl.value) {
        videoEl.value.srcObject = stream
      }
    }, 100)
  } catch (err) {
    console.error("Camera error:", err)
    alert("Could not access camera. Please check permissions or use device upload.")
  }
}

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
    stream = null
  }
}

const capturePhoto = () => {
  if (!videoEl.value) return
  const canvas = document.createElement('canvas')
  canvas.width = videoEl.value.videoWidth
  canvas.height = videoEl.value.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(videoEl.value, 0, 0, canvas.width, canvas.height)
  
  previewSrc.value = canvas.toDataURL('image/jpeg')
  stopCamera()
  
  state.value = 'CROPPING'
  initCropper()
}

// --- FILE UPLOAD LOGIC ---
const triggerFileInput = () => {
  if (fileInput.value) fileInput.value.click()
}

const handleFileUpload = (e) => {
  const file = e.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (evt) => {
      previewSrc.value = evt.target.result
      state.value = 'CROPPING'
      initCropper()
    }
    reader.readAsDataURL(file)
  }
}

// --- CROPPER LOGIC ---
const initCropper = () => {
  if (cropperInstance) {
    cropperInstance.destroy()
  }
  setTimeout(() => {
    if (imageEl.value) {
      cropperInstance = new Cropper(imageEl.value, {
        aspectRatio: 1, // 1:1 Square by default for products
        viewMode: 2,
        background: false,
      })
    }
  }, 100)
}

const confirmCrop = () => {
  if (!cropperInstance) return
  
  // Get the cropped canvas and convert to Blob
  cropperInstance.getCroppedCanvas({
    width: 800,
    height: 800,
    fillColor: '#fff',
  }).toBlob((blob) => {
    // Generate a file object for easy form appending
    const file = new File([blob], `product_${Date.now()}.jpg`, { type: 'image/jpeg' })
    // Also pass a data url for instant preview in the parent
    const dataUrl = cropperInstance.getCroppedCanvas().toDataURL('image/jpeg')
    
    emit('captured', { file, dataUrl })
    isOpen.value = false
    stopCamera()
  }, 'image/jpeg', 0.9)
}

const resetToChoose = () => {
  stopCamera()
  if (cropperInstance) {
    cropperInstance.destroy()
    cropperInstance = null
  }
  previewSrc.value = null
  state.value = 'CHOOSING'
}

onBeforeUnmount(() => {
  stopCamera()
  if (cropperInstance) cropperInstance.destroy()
})
</script>

<style scoped>
.hidden { display: none; }
.cropper-container {
  width: 100%;
  height: calc(100vh - 120px);
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-premium {
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.4);
}
</style>
