<template>
  <q-page class="vendor-page">
    <div class="page-container">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-lg row items-center justify-between">
        <div>
          <h1 class="text-h4 text-weight-bold q-ma-none">Product Categories</h1>
          <p class="text-subtitle1 text-grey-7 q-mt-sm q-mb-none">Organize your inventory with categories.</p>
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card">
        <q-card-section class="q-pa-md border-bottom row items-center justify-between">
          
          <div class="row q-gutter-sm items-center">
            <q-input v-model="search" outlined dense class="custom-glass-input" placeholder="Search categories..." style="width: 300px;">
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>

          <div class="row q-gutter-sm">
            <q-btn outline icon="download" label="Export" color="dark" no-caps class="btn-3d-outline" />
            <q-btn unelevated icon="add" label="Add Category" color="red-8" no-caps class="btn-premium text-white" @click="showAddModal = true" />
          </div>
        </q-card-section>

        <!-- Table -->
        <q-table
          flat
          class="custom-premium-table"
          :rows="filteredCategories"
          :columns="columns"
          row-key="category_id"
          :loading="loading"
        >
          <template #no-data>
            <div class="full-width row flex-center text-grey-6 q-pa-xl empty-state-glass">
              <div class="text-center">
                <q-icon name="category" size="48px" class="q-mb-md opacity-50" />
                <div class="text-subtitle1 text-weight-medium">No categories found</div>
                <div class="text-caption">You haven't assigned any products to a category yet.</div>
              </div>
            </div>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <div class="row justify-end q-gutter-sm">
                <q-btn flat round dense icon="edit" color="amber-7" />
                <q-btn flat round dense icon="delete" color="red-6" />
              </div>
            </q-td>
          </template>
        </q-table>
      </q-card>

      <!-- ================= ADD CATEGORY MODAL ================= -->
      <q-dialog v-model="showAddModal" persistent>
        <q-card class="premium-glass-card" style="width: 500px; max-width: 90vw;">
          <q-card-section class="row items-center q-pb-none">
            <div class="text-h6 text-weight-bold">Add New Category</div>
            <q-space />
            <q-btn icon="close" flat round dense v-close-popup />
          </q-card-section>

          <q-card-section class="q-pt-md">
            <q-form @submit.prevent="submitCategory" class="q-gutter-md">
              <div>
                <div class="text-subtitle2 text-weight-bold text-dark q-mb-xs">Category Name</div>
                <q-input v-model="categoryForm.category_name" outlined dense class="custom-glass-input" placeholder="e.g. Canned Goods" :rules="[val => !!val || 'Name is required']" />
              </div>

              <div>
                <div class="text-subtitle2 text-weight-bold text-dark q-mb-xs">Description</div>
                <q-input v-model="categoryForm.description" type="textarea" outlined dense class="custom-glass-input" rows="3" placeholder="Brief description of the category..." />
              </div>

              <div class="row justify-end q-mt-lg q-gutter-sm">
                <q-btn flat label="Cancel" color="grey-7" v-close-popup no-caps />
                <q-btn unelevated type="submit" label="Save Category" color="red-8" class="btn-premium text-white q-px-md" no-caps :loading="submitting" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const search = ref('')
const loading = ref(true)
const categories = ref([])
const showAddModal = ref(false)
const submitting = ref(false)

const categoryForm = ref({
  category_name: '',
  description: ''
})

const columns = [
  { name: 'category_name', label: 'Category Name', field: 'category_name', align: 'left', sortable: true },
  { name: 'products_count', label: 'Number of Items', field: 'products_count', align: 'left', sortable: true },
  { name: 'action', label: '', field: 'action', align: 'right' }
]

const filteredCategories = computed(() => {
  if (!search.value) return categories.value
  const needle = search.value.toLowerCase()
  return categories.value.filter(c => c.category_name.toLowerCase().includes(needle))
})

const fetchCategories = async () => {
  try {
    const res = await api.get('/vendor/products/categories')
    categories.value = res.data || []
  } catch (error) {
    console.error('Failed to load categories', error)
  } finally {
    loading.value = false
  }
}

const submitCategory = async () => {
  submitting.value = true
  try {
    await api.post('/categories', {
      category_name: categoryForm.value.category_name,
      description: categoryForm.value.description
    })
    $q.notify({ type: 'positive', message: 'Category added successfully' })
    showAddModal.value = false
    categoryForm.value = { category_name: '', description: '' }
    // Note: since this adds to global categories, the vendor's local category list might not update unless they add a product to it. But we fetch anyway.
    await fetchCategories()
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to add category' })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchCategories()
})
</script>

<style scoped>
.vendor-page {
  padding: 24px;
  background: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1400px;
  margin: 0 auto;
}
.premium-glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.8);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}
.btn-premium {
  border-radius: 8px !important;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(185, 28, 28, 0.3);
  transition: all 0.2s ease;
}
.btn-premium:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(185, 28, 28, 0.4);
}
.btn-3d-outline {
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid #E2E8F0;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  transition: all 0.2s ease;
}
.btn-3d-outline:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
  background: #F8FAFC !important;
}
.custom-glass-input :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.6);
  border-radius: 8px;
}
.border-bottom {
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table thead tr th) {
  background: rgba(248, 250, 252, 0.7); backdrop-filter: blur(8px); font-weight: 700;
  color: #64748B; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}
:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; border-bottom: 1px solid rgba(226, 232, 240, 0.5);
}
.empty-state-glass {
  background: rgba(248, 250, 252, 0.5);
  border: 1px dashed #E2E8F0;
  border-radius: 12px;
}
</style>
