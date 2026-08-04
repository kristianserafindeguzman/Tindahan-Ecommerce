<template>
  <q-dialog v-model="isOpen" persistent>
    <q-card style="width: 700px; max-width: 90vw;" class="premium-glass-card">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6 text-weight-bold">Product Details</div>
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
                <q-input v-model="form.product_name" outlined dense :rules="[val => !!val || 'Product name is required']" />
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
                  :rules="[val => !!val || 'Category is required']"
                />
              </div>

              <div class="q-mb-md">
                <q-toggle v-model="isActive" label="Available for Sale (Active)" color="green-6" />
              </div>

              <div class="q-mb-md">
                <q-checkbox v-model="hasVariants" label="This product has different sizes/variants" color="red-8" />
              </div>

              <!-- Single Size Pricing/Qty -->
              <div v-if="!hasVariants" class="row q-col-gutter-sm q-mb-md">
                <div class="col-6">
                  <div class="text-subtitle2 q-mb-xs">Price (₱)</div>
                  <q-input v-model.number="form.price" type="number" outlined dense min="0" step="0.01" />
                </div>
                <div class="col-6">
                  <div class="text-subtitle2 q-mb-xs">Quantity</div>
                  <q-input v-model.number="form.stock_quantity" type="number" outlined dense min="0" />
                </div>
              </div>

              <!-- Variants Pricing/Qty -->
              <div v-else class="q-mb-md">
                <div class="text-subtitle2 q-mb-xs">Variants (Sizes)</div>
                <div v-for="(variant, index) in form.variants" :key="index" class="row q-col-gutter-sm items-center q-mb-sm">
                  <div class="col-4">
                    <q-input v-model="variant.size" outlined dense placeholder="Size" :rules="[val => !!val || 'Required']" />
                  </div>
                  <div class="col-3">
                    <q-input v-model.number="variant.price" type="number" outlined dense min="0" step="0.01" placeholder="Price" />
                  </div>
                  <div class="col-3">
                    <q-input v-model.number="variant.quantity" type="number" outlined dense min="0" placeholder="Qty" />
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
              <div class="image-upload-box" @click="triggerFileInput">
                <img v-if="imagePreview" :src="imagePreview" class="preview-img" />
                <div v-else class="text-center text-grey-6">
                  <q-icon name="cloud_upload" size="48px" />
                  <div>Click to update</div>
                </div>
                <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleImageUpload" />
              </div>
            </div>
          </div>

          <div class="row justify-end q-mt-lg q-gutter-sm">
            <q-btn flat label="Cancel" color="grey-8" no-caps v-close-popup />
            <q-btn type="submit" unelevated label="Save Changes" color="red-8" class="btn-premium" :loading="saving" />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'

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
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
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
