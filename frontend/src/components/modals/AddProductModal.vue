<template>
  <q-dialog v-model="isOpen" persistent @hide="resetForm">
    <q-card style="width: 700px; max-width: 90vw;" class="premium-glass-card">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6 text-weight-bold">Add New Product</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section class="q-pt-md">
        <q-form @submit.prevent="submitForm">
          <div class="row q-col-gutter-md">
            <!-- Left Column: Details -->
            <div class="col-12 col-md-7">
              <div class="q-mb-md">
                <div class="text-subtitle2 q-mb-xs">Product Name</div>
                <q-input v-model="form.product_name" outlined dense placeholder="e.g., Classic T-Shirt" :rules="[val => !!val || 'Product name is required']" />
              </div>

              <div class="q-mb-md">
                <div class="text-subtitle2 q-mb-xs">Category</div>
                <q-select
                  v-model="form.category_id"
                  :options="categories"
                  option-value="category_id"
                  option-label="category_name"
                  emit-value
                  map-options
                  outlined
                  dense
                  placeholder="Select Category"
                  :rules="[val => !!val || 'Category is required']"
                />
                <q-banner v-if="selectedCategory?.description" class="bg-blue-1 text-blue-9 q-mt-sm rounded-borders" dense>
                  <template v-slot:avatar>
                    <q-icon name="info" color="blue-7" />
                  </template>
                  {{ selectedCategory.description }}
                </q-banner>
              </div>

              <div class="q-mb-md">
                <q-checkbox v-model="hasVariants" label="This product has different sizes/variants" color="red-8" />
              </div>

              <!-- Single Size Pricing/Qty -->
              <div v-if="!hasVariants" class="row q-col-gutter-sm q-mb-md">
                <div class="col-6">
                  <div class="text-subtitle2 q-mb-xs">Price (₱)</div>
                  <q-input v-model.number="form.price" type="number" outlined dense min="0" step="0.01" :rules="[val => val !== null && val >= 0 || 'Valid price required']" />
                </div>
                <div class="col-6">
                  <div class="text-subtitle2 q-mb-xs">Quantity</div>
                  <q-input v-model.number="form.stock_quantity" type="number" outlined dense min="0" :rules="[val => val !== null && val >= 0 || 'Valid quantity required']" />
                </div>
              </div>

              <!-- Variants Pricing/Qty -->
              <div v-else class="q-mb-md">
                <div class="text-subtitle2 q-mb-xs">Variants (Sizes)</div>
                <div v-for="(variant, index) in form.variants" :key="index" class="row q-col-gutter-sm items-center q-mb-sm">
                  <div class="col-4">
                    <q-input v-model="variant.size" outlined dense placeholder="Size (e.g. Small)" :rules="[val => !!val || 'Required']" />
                  </div>
                  <div class="col-3">
                    <q-input v-model.number="variant.price" type="number" outlined dense min="0" step="0.01" placeholder="Price" :rules="[val => val !== null && val >= 0 || 'Invalid']" />
                  </div>
                  <div class="col-3">
                    <q-input v-model.number="variant.quantity" type="number" outlined dense min="0" placeholder="Qty" :rules="[val => val !== null && val >= 0 || 'Invalid']" />
                  </div>
                  <div class="col-2 text-right">
                    <q-btn icon="delete" flat color="red-6" dense @click="removeVariant(index)" :disable="form.variants.length === 1" />
                  </div>
                </div>
                <q-btn outline label="Add Variant" icon="add" size="sm" color="grey-8" no-caps @click="addVariant" />
              </div>
            </div>

            <!-- Right Column: Image -->
            <div class="col-12 col-md-5">
              <div class="text-subtitle2 q-mb-xs">Product Image</div>
              <div class="image-upload-box" @click="showImageModal = true">
                <div v-if="imagePreview" class="full-width full-height relative-position">
                  <img :src="imagePreview" class="preview-img" />
                  <q-btn icon="close" color="negative" round size="sm" class="absolute-top-right q-ma-sm" @click.stop="removePhoto" />
                </div>
                <div v-else class="text-center text-grey-6">
                  <q-icon name="photo_camera" size="48px" />
                  <div class="q-mt-sm font-weight-bold">Add Photo</div>
                  <div class="text-caption">Take picture or upload</div>
                </div>
              </div>
            </div>
          </div>

          <div class="row justify-end q-mt-lg q-gutter-sm">
            <q-btn flat label="Cancel" color="grey-8" no-caps v-close-popup />
            <q-btn type="submit" unelevated label="Save Product" color="red-8" class="btn-premium" :loading="saving" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
    <ImageCaptureModal v-model="showImageModal" @captured="handleImageCaptured" />
  </q-dialog>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import ImageCaptureModal from './ImageCaptureModal.vue'

const props = defineProps({
  modelValue: Boolean
})
const emit = defineEmits(['update:modelValue', 'refresh'])
const $q = useQuasar()

const isOpen = ref(props.modelValue)
watch(() => props.modelValue, (val) => isOpen.value = val)
watch(isOpen, (val) => emit('update:modelValue', val))

const categories = ref([])
const hasVariants = ref(false)
const saving = ref(false)
const imagePreview = ref(null)
const showImageModal = ref(false)

const form = ref({
  product_name: '',
  category_id: null,
  price: null,
  stock_quantity: null,
  product_picture: null,
  variants: [
    { size: '', price: null, quantity: null }
  ]
})

const fetchCategories = async () => {
  try {
    const res = await api.get('/categories')
    categories.value = res.data
  } catch (err) {
    console.error(err)
  }
}

const selectedCategory = computed(() => {
  if (!form.value.category_id) return null
  return categories.value.find(c => c.category_id === form.value.category_id)
})

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

const handleImageCaptured = ({ file, dataUrl }) => {
  form.value.product_picture = file
  imagePreview.value = dataUrl
}

const removePhoto = () => {
  form.value.product_picture = null
  imagePreview.value = null
}

const resetForm = () => {
  form.value = {
    product_name: '',
    category_id: null,
    price: null,
    stock_quantity: null,
    product_picture: null,
    variants: [{ size: '', price: null, quantity: null }]
  }
  hasVariants.value = false
  imagePreview.value = null
}

const submitForm = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('product_name', form.value.product_name)
    formData.append('category_id', form.value.category_id)
    
    if (form.value.product_picture) {
      formData.append('product_picture', form.value.product_picture)
    }

    if (hasVariants.value) {
      formData.append('variants', JSON.stringify(form.value.variants))
    } else {
      formData.append('price', form.value.price)
      formData.append('stock_quantity', form.value.stock_quantity)
    }

    await api.post('/vendor/products', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    $q.notify({ type: 'positive', message: 'Product added successfully' })
    resetForm()
    isOpen.value = false
    emit('refresh')
  } catch (err) {
    $q.notify({ type: 'negative', message: 'Failed to save product' })
    console.error(err)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.premium-glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
}
.image-upload-box {
  border: 2px dashed #CBD5E1;
  border-radius: 12px;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  background: #F8FAFC;
  overflow: hidden;
  position: relative;
  transition: all 0.2s ease;
}
.image-upload-box:hover {
  border-color: #94A3B8;
  background: #F1F5F9;
}
.preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.hidden {
  display: none;
}
.btn-premium {
  border-radius: 8px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.3);
}
</style>
