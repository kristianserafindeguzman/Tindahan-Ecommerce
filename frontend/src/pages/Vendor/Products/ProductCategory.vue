<template>
  <q-page class="vendor-page relative-position">
    <!-- Subtle Ambient Background Glows (Brand Aligned) -->
    <div class="bg-glow bg-glow-primary"></div>
    <div class="bg-glow bg-glow-secondary"></div>

    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= HEADER AREA ================= -->
      <div class="page-header q-mb-xl q-mt-sm row items-center justify-between">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md">
            <q-icon name="category" size="26px" class="text-brand-red" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">Product Categories</h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none">Organize and manage your store's inventory flow.</p>
          </div>
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card" style="border-radius: 16px;">
        <q-card-section class="q-pa-lg border-bottom row items-center justify-between q-col-gutter-y-md">
          
          <!-- Search -->
          <div class="col-12 col-md-5">
            <q-input v-model="search" outlined dense class="custom-glass-input" placeholder="Search categories...">
              <template v-slot:prepend>
                <q-icon name="search" color="blue-grey-4" />
              </template>
            </q-input>
          </div>

          <!-- Actions -->
          <div class="col-12 col-md-7 text-right flex justify-end q-gutter-md">
            <q-btn outline icon="download" label="Export" color="red-9" no-caps class="btn-glass-outline text-weight-bold q-px-md" :loading="isExporting" @click="exportCategories" />
            <q-btn unelevated icon="add" label="Add Category" color="red-9" no-caps class="btn-premium text-white text-weight-bold q-px-md" @click="showAddModal = true" />
          </div>
        </q-card-section>

        <!-- Table -->
        <q-table
          flat
          class="custom-premium-table bg-transparent"
          :rows="filteredCategories"
          :columns="columns"
          row-key="category_id"
          :loading="loading"
          :pagination="{ rowsPerPage: 10 }"
        >
          <!-- Empty State -->
          <template #no-data>
            <div class="full-width row flex-center q-pa-xl empty-state-glass">
              <div class="text-center z-top relative-position">
                <div class="empty-icon-wrapper q-mb-lg">
                  <q-icon name="style" size="56px" color="blue-grey-3" class="drop-shadow-icon" />
                  <div class="icon-pulse"></div>
                </div>
                <div class="text-h6 text-weight-bold text-blue-grey-8">No categories found</div>
                <div class="text-body2 text-blue-grey-5 q-mt-xs">You haven't assigned any products to a category yet.</div>
              </div>
            </div>
          </template>

          <!-- Category Name Formatter with Icon -->
          <template #body-cell-category_name="props">
            <q-td :props="props">
              <div class="row items-center no-wrap">
                <!-- Subtle Category Icon Box -->
                <div class="category-icon-box q-mr-sm">
                  <q-icon name="category" size="20px" color="blue-grey-5" />
                </div>
                <!-- Category Text -->
                <div>
                  <div class="text-weight-bold text-blue-grey-9 text-subtitle2 leading-tight">
                    {{ props.row.category_name }}
                  </div>
                  <div class="text-caption text-blue-grey-4 text-truncate" style="max-width: 250px;" v-if="props.row.description">
                    {{ props.row.description }}
                  </div>
                </div>
              </div>
            </q-td>
          </template>

          <!-- Item Count Formatter -->
          <template #body-cell-products_count="props">
            <q-td :props="props">
              <q-badge color="blue-grey-1" text-color="blue-grey-8" class="q-px-sm q-py-xs text-weight-bold rounded-borders border-slate-light">
                {{ props.row.products_count || 0 }} Items
              </q-badge>
            </q-td>
          </template>

          <!-- Action Buttons -->
          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <q-btn flat round dense icon="edit" color="blue-grey-4" class="hover-action-btn q-mr-sm transition-ease" @click.stop="openEditModal(props.row)">
                <q-tooltip class="bg-blue-grey-9 text-caption">Edit Category</q-tooltip>
              </q-btn>
              <q-btn flat round dense icon="delete" color="blue-grey-4" class="hover-action-btn-red transition-ease" @click.stop="openDeleteModal(props.row)">
                <q-tooltip class="bg-red-9 text-caption">Delete Category</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card>

      <!-- ================= ADD CATEGORY MODAL ================= -->
      <q-dialog v-model="showAddModal" persistent backdrop-filter="blur(4px)">
        <q-card class="premium-glass-card" style="width: 500px; max-width: 90vw;">
          <q-card-section class="row items-center border-bottom q-pa-md">
            <div class="text-h6 text-weight-bold text-blue-grey-9">Add New Category</div>
            <q-space />
            <q-btn icon="close" flat round dense color="blue-grey-4" v-close-popup class="hover-text-dark transition-ease" />
          </q-card-section>

          <q-card-section class="q-pa-lg">
            <q-form @submit.prevent="submitCategory" class="q-gutter-y-md">
              <div>
                <div class="text-subtitle2 text-weight-bold text-blue-grey-8 q-mb-xs">Category Name <span class="text-red-9">*</span></div>
                <q-input v-model="categoryForm.category_name" outlined dense class="custom-glass-input" placeholder="e.g. Fresh Produce" :rules="[val => !!val || 'Category name is required']" hide-bottom-space />
              </div>

              <div>
                <div class="text-subtitle2 text-weight-bold text-blue-grey-8 q-mb-xs">Description <span class="text-weight-regular text-blue-grey-4">(Optional)</span></div>
                <q-input v-model="categoryForm.description" type="textarea" outlined dense class="custom-glass-input" rows="3" placeholder="Briefly describe what belongs in this category..." />
              </div>

              <div class="row justify-end q-mt-xl q-gutter-sm">
                <q-btn flat label="Cancel" color="blue-grey-5" v-close-popup no-caps class="text-weight-bold transition-ease hover-text-dark" />
                <q-btn unelevated type="submit" label="Save Category" color="red-9" class="btn-premium text-white q-px-lg text-weight-bold" no-caps :loading="submitting" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

      <!-- ================= EDIT CATEGORY MODAL ================= -->
      <q-dialog v-model="showEditModal" persistent backdrop-filter="blur(4px)">
        <q-card class="premium-glass-card" style="width: 500px; max-width: 90vw; overflow: hidden;">
          
          <!-- Dark Red Gradient Header -->
          <q-card-section class="row items-center q-pa-md header-red-gradient text-white">
            <q-avatar size="40px" color="white" text-color="red-9" icon="edit_note" class="q-mr-sm shadow-1" />
            <div>
              <div class="text-h6 text-weight-bold leading-tight">Edit Category</div>
            </div>
            <q-space />
            <q-btn icon="close" flat round dense color="white" v-close-popup class="transition-ease" style="opacity: 0.8;" />
          </q-card-section>

          <q-card-section class="q-pa-lg">
            <q-form @submit.prevent="submitEditCategory" class="q-gutter-y-lg">
              
              <!-- Visually distinct locked field -->
              <div class="locked-field-container q-pa-md rounded-borders border-slate-light bg-slate-50">
                <div class="row items-center justify-between q-mb-sm">
                  <div class="text-subtitle2 text-weight-bold text-blue-grey-8">Category Name</div>
                  <q-chip size="sm" color="blue-grey-2" text-color="blue-grey-7" icon="lock" class="q-ma-none text-weight-medium">System Protected</q-chip>
                </div>
                <q-input v-model="editCategoryForm.category_name" outlined dense class="custom-glass-input disabled-glass text-weight-bold" disable readonly />
                <div class="text-caption text-blue-grey-5 q-mt-sm">
                  To maintain global catalog consistency, core category names cannot be modified.
                </div>
              </div>

              <div>
                <div class="text-subtitle2 text-weight-bold text-blue-grey-8 q-mb-xs">Category Description</div>
                <q-input v-model="editCategoryForm.description" type="textarea" outlined dense class="custom-glass-input" rows="4" placeholder="Append specific guidelines, examples, or notes for your staff..." />
              </div>

              <div class="row justify-end q-mt-md q-gutter-sm">
                <q-btn flat label="Cancel" color="blue-grey-5" v-close-popup no-caps class="text-weight-bold transition-ease hover-text-dark" />
                <q-btn unelevated type="submit" label="Save Changes" color="red-9" class="btn-premium text-white q-px-lg text-weight-bold" no-caps :loading="submitting" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

      <!-- ================= SMART DELETE CATEGORY MODAL ================= -->
      <q-dialog v-model="showDeleteModal" persistent backdrop-filter="blur(4px)">
        <q-card class="premium-glass-card" style="width: 450px; max-width: 90vw;">
          
          <!-- STATE 1: Cannot delete because category has products -->
          <template v-if="categoryToDelete && categoryToDelete.products_count > 0">
            <q-card-section class="q-pa-lg text-center">
              <div class="q-mb-md">
                <q-avatar size="64px" color="orange-1" text-color="orange-8" icon="warning" class="shadow-1" />
              </div>
              <h3 class="text-h6 text-weight-bold text-blue-grey-9 q-mt-none q-mb-sm">Cannot Delete Category</h3>
              <p class="text-body2 text-blue-grey-6 q-mb-none">
                The <strong>{{ categoryToDelete.category_name }}</strong> category currently contains 
                <q-badge color="orange-2" text-color="orange-9" class="text-weight-bold">{{ categoryToDelete.products_count }} products</q-badge>. 
              </p>
              <div class="bg-orange-50 text-orange-9 q-pa-md rounded-borders q-mt-md text-caption text-left border-orange-light">
                <q-icon name="info" size="16px" class="q-mr-xs" />
                <strong>Action Required:</strong> To delete this category, you must first reassign or remove all items currently linked to it.
              </div>
            </q-card-section>
            <q-card-actions align="center" class="q-pa-md border-top">
              <q-btn unelevated label="Understood" color="blue-grey-8" v-close-popup no-caps class="q-px-xl text-weight-bold btn-glass-outline" />
            </q-card-actions>
          </template>

          <!-- STATE 2: Category is empty, allow deletion -->
          <template v-else-if="categoryToDelete">
            <q-card-section class="q-pa-lg text-center">
              <div class="q-mb-md">
                <q-avatar size="64px" color="red-1" text-color="red-9" icon="delete_outline" class="shadow-1" />
              </div>
              <h3 class="text-h6 text-weight-bold text-blue-grey-9 q-mt-none q-mb-sm">Delete Category?</h3>
              <p class="text-body2 text-blue-grey-6 q-mb-none">
                Are you sure you want to permanently delete the <strong>{{ categoryToDelete.category_name }}</strong> category? This action cannot be undone.
              </p>
            </q-card-section>
            <q-card-actions align="between" class="q-pa-md border-top bg-slate-50">
              <q-btn flat label="Cancel" color="blue-grey-6" v-close-popup no-caps class="text-weight-bold" />
              <q-btn unelevated label="Yes, Delete it" color="red-9" @click="confirmDelete" no-caps class="text-weight-bold q-px-md shadow-2" :loading="submitting" />
            </q-card-actions>
          </template>

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
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const submitting = ref(false)
const isExporting = ref(false)

const categoryToDelete = ref(null)

const categoryForm = ref({
  category_name: '',
  description: ''
})

const editCategoryForm = ref({
  category_id: null,
  category_name: '',
  description: ''
})

const columns = [
  { name: 'category_name', label: 'Category Details', field: 'category_name', align: 'left', sortable: true },
  { name: 'products_count', label: 'Inventory Count', field: 'products_count', align: 'left', sortable: true },
  { name: 'action', label: 'Actions', field: 'action', align: 'right' }
]

const filteredCategories = computed(() => {
  if (!search.value) return categories.value
  const needle = search.value.toLowerCase()
  return categories.value.filter(c => c.category_name.toLowerCase().includes(needle))
})

const fetchCategories = async () => {
  try {
    loading.value = true
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
    $q.notify({ type: 'positive', message: 'Category added successfully', position: 'top-right' })
    showAddModal.value = false
    categoryForm.value = { category_name: '', description: '' }
    await fetchCategories()
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to add category', position: 'top-right' })
  } finally {
    submitting.value = false
  }
}

const exportCategories = async () => {
  try {
    isExporting.value = true
    const response = await api.get('/vendor/categories/export', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const dateStr = new Date().toISOString().split('T')[0]
    link.setAttribute('download', `Tindahan-Product-Categories-Report-${dateStr}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Export failed:', error)
    $q.notify({ type: 'negative', message: 'Failed to generate category report', position: 'top-right' })
  } finally {
    isExporting.value = false
  }
}

const openEditModal = (category) => {
  editCategoryForm.value = {
    category_id: category.category_id,
    category_name: category.category_name,
    description: category.description || ''
  }
  showEditModal.value = true
}

const submitEditCategory = async () => {
  submitting.value = true
  try {
    await api.patch(`/categories/${editCategoryForm.value.category_id}`, {
      description: editCategoryForm.value.description
    })
    $q.notify({ type: 'positive', message: 'Category updated successfully', position: 'top-right' })
    showEditModal.value = false
    await fetchCategories()
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to update category', position: 'top-right' })
  } finally {
    submitting.value = false
  }
}

const openDeleteModal = (category) => {
  categoryToDelete.value = category
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  submitting.value = true
  try {
    await api.delete(`/categories/${categoryToDelete.value.category_id}`)
    $q.notify({ type: 'positive', message: 'Category deleted successfully', position: 'top-right' })
    showDeleteModal.value = false
    categoryToDelete.value = null
    await fetchCategories()
  } catch (error) {
    $q.notify({ type: 'negative', message: 'Failed to delete category', position: 'top-right' })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchCategories()
})
</script>

<style scoped>
/* Core Page Styling */
.vendor-page {
  padding: 32px 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}
.page-container {
  max-width: 1200px;
  margin: 0 auto;
}

/* Strictly Dark Red Brand Colors */
.text-brand-red { color: #B91C1C !important; }

/* Subtle Ambient Glows (Fixed to Dark Red & Slate) */
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

.tracking-tight { letter-spacing: -0.02em; }
.leading-tight { line-height: 1.2; }
.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Beautiful Header Glass Icon Box */
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

/* Clean SaaS Cards */
.premium-glass-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(241, 245, 249, 1);
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(15, 23, 42, 0.04); 
}

/* Custom Inputs */
.custom-glass-input :deep(.q-field__control) {
  background: rgba(248, 250, 252, 0.8); 
  border-radius: 8px;
  transition: all 0.3s ease;
  height: 40px; /* Lock standard text inputs to 40px */
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.8); }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 0 0 2px rgba(185, 28, 28, 0.15); 
  border-color: #B91C1C;
}

/* FIX: Allow textareas to expand dynamically instead of being locked to 40px */
.custom-glass-input.q-textarea :deep(.q-field__control) {
  height: auto !important;
  min-height: 100px; /* Ensures the 'rows' attribute functions properly */
}

.disabled-glass :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.5) !important;
  cursor: not-allowed;
  color: #64748b;
}

/* Clean Outline Buttons */
.btn-glass-outline {
  border-radius: 8px !important;
  background: rgba(255, 255, 255, 0.9) !important;
  border: 1px solid currentColor;
  transition: all 0.2s ease;
}
.btn-glass-outline:hover {
  background: #ffffff !important;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
  transform: translateY(-2px);
}

/* Solid Action Button */
.btn-premium {
  border-radius: 8px !important;
  font-weight: 700;
  transition: all 0.2s ease;
}
.btn-premium:hover {
  transform: scale(1.02);
}

/* Utilities */
.bg-slate-50 { background-color: #f8fafc; }
.border-bottom { border-bottom: 1px solid rgba(226, 232, 240, 0.8); }
.border-top { border-top: 1px solid rgba(226, 232, 240, 0.8); }
.border-slate-light { border: 1px solid rgba(226, 232, 240, 0.8); }
.border-orange-light { border: 1px solid rgba(253, 186, 116, 0.5); }
.transition-ease { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.hover-text-dark:hover { color: #334155 !important; }

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
:deep(.custom-premium-table thead tr th:first-child) { border-top-left-radius: 12px; }
:deep(.custom-premium-table thead tr th:last-child) { border-top-right-radius: 12px; }

:deep(.custom-premium-table tbody td) {
  padding: 16px 20px; 
  border-bottom: 1px solid rgba(241, 245, 249, 1); 
  cursor: pointer;
  transition: all 0.2s ease;
}

/* Micro-Interactions */
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
:deep(.custom-premium-table tbody tr:hover .hover-action-btn) {
  color: #3b82f6 !important; 
  transform: translateY(-2px);
  background: rgba(59, 130, 246, 0.05);
}
:deep(.custom-premium-table tbody tr:hover .hover-action-btn-red) {
  color: #B91C1C !important; 
  transform: translateY(-2px);
  background: rgba(185, 28, 28, 0.05);
}

/* Empty State Styling */
.empty-state-glass {
  background: rgba(248, 250, 252, 0.6);
  border: 1px dashed rgba(203, 213, 225, 0.8);
  border-radius: 12px;
  margin: 16px;
  width: calc(100% - 32px);
}
.empty-icon-wrapper {
  position: relative;
  display: inline-flex;
  justify-content: center;
  align-items: center;
}
.drop-shadow-icon { filter: drop-shadow(0 4px 6px rgba(15, 23, 42, 0.05)); }
.icon-pulse {
  position: absolute;
  width: 60px;
  height: 60px;
  background: rgba(226, 232, 240, 0.5);
  border-radius: 50%;
  z-index: -1;
  animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

/* Dark Red Gradient for Modal Headers */
.header-red-gradient {
  background: linear-gradient(135deg, #B91C1C 0%, #450A0A 100%);
  border-bottom: 2px solid #7f1d1d;
}

/* Ensure the card clips the header's background properly */
.premium-glass-card {
  border-radius: 12px;
  overflow: hidden; 
}

.category-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(241, 245, 249, 0.8);
  border: 1px solid rgba(226, 232, 240, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

@keyframes pulse-ring {
  0% { transform: scale(0.8); opacity: 0; }
  50% { opacity: 1; }
  100% { transform: scale(1.5); opacity: 0; }
}
</style>