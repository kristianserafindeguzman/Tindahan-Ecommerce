<template>
  <q-page class="vendor-page relative-position" :class="{ 'mobile-page-padding': $q.screen.lt.md }">
    <!-- Subtle Ambient Background Glows -->
    <div class="bg-glow bg-glow-primary desktop-only"></div>
    <div class="bg-glow bg-glow-secondary desktop-only"></div>

    <div class="page-container relative-position" style="z-index: 1;">
      
      <!-- ================= DESKTOP HEADER AREA ================= -->
      <div v-if="!$q.screen.lt.md" class="page-header q-mb-xl q-mt-sm row items-center justify-between">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md">
            <q-icon name="category" size="26px" class="text-brand-red" />
          </div>
          <div>
            <h1 class="text-h4 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight">Product Categories</h1>
            <p class="text-body1 text-blue-grey-5 q-mt-xs q-mb-none font-medium">Organize and manage your store's inventory flow.</p>
          </div>
        </div>
      </div>

      <!-- ================= MOBILE HEADER AREA ================= -->
      <div v-else class="page-header q-mb-lg q-mt-sm row items-center justify-between">
        <div class="row items-center">
          <div class="glass-icon-box q-mr-md" style="width: 44px; height: 44px;">
            <q-icon name="category" size="22px" class="text-brand-red" />
          </div>
          <div>
            <h1 class="text-h5 text-weight-bolder text-blue-grey-9 q-ma-none tracking-tight leading-tight">Product Categories</h1>
            <p class="text-caption text-blue-grey-5 q-mt-xs q-mb-none font-medium">Manage inventory flow.</p>
          </div>
        </div>
      </div>

      <!-- ================= CONTROLS & TABLE ================= -->
      <q-card class="premium-glass-card" :class="{ 'border-none shadow-none bg-transparent': $q.screen.lt.md }" style="border-radius: 16px;">
        
        <q-card-section class="border-bottom row items-center justify-between q-col-gutter-y-md" :class="$q.screen.lt.md ? 'q-pa-none q-mb-md border-none' : 'q-pa-lg'">
          
          <!-- Search -->
          <div class="col-12 col-md-5">
            <q-input v-model="search" outlined dense class="custom-glass-input" placeholder="Search categories..." hide-bottom-space>
              <template v-slot:prepend>
                <q-icon name="search" color="blue-grey-4" />
              </template>
            </q-input>
          </div>

          <!-- Actions -->
          <div class="col-12 col-md-7 flex justify-end" :class="$q.screen.lt.md ? 'row no-wrap q-gutter-x-sm' : 'q-gutter-md'">
            <q-btn outline icon="download" label="Export" color="red-9" text-color="red-9" no-caps class="btn-export-red text-weight-bold" :class="$q.screen.lt.md ? 'col' : 'q-px-md'" :loading="isExporting" @click="exportCategories" />
            <q-btn unelevated icon="add" label="Add Category" color="red-9" no-caps class="btn-premium text-white text-weight-bold" :class="$q.screen.lt.md ? 'col' : 'q-px-md'" @click="showAddModal = true" />
          </div>
        </q-card-section>

        <!-- Table with Skeleton Loader -->
        <q-table
          flat
          class="custom-premium-table bg-transparent"
          :rows="loading ? skeletonRows : filteredCategories"
          :columns="columns"
          row-key="category_id"
          :pagination="{ rowsPerPage: 10 }"
          :grid="$q.screen.lt.md"
        >
          <!-- Empty State -->
          <template #no-data>
            <div v-if="!loading" class="full-width row flex-center empty-state-glass" :class="$q.screen.lt.md ? 'q-pa-lg q-mt-md' : 'q-pa-xl'">
              <div class="text-center z-top relative-position">
                <div class="empty-icon-wrapper q-mb-lg">
                  <q-icon name="style" :size="$q.screen.lt.md ? '48px' : '56px'" color="blue-grey-3" class="drop-shadow-icon" />
                </div>
                <div class="text-weight-bold text-blue-grey-8" :class="$q.screen.lt.md ? 'text-subtitle1' : 'text-h6'">No categories found</div>
                <div class="text-blue-grey-5 q-mt-xs" :class="$q.screen.lt.md ? 'text-caption' : 'text-body2'">You haven't added or assigned any categories yet.</div>
              </div>
            </div>
          </template>

          <!-- DESKTOP FORMATTERS -->
          <template #body-cell-category_name="props">
            <q-td :props="props">
              <div v-if="loading" class="row items-center no-wrap">
                <q-skeleton type="rect" width="38px" height="38px" style="border-radius: 10px;" class="q-mr-sm" />
                <div class="col">
                  <q-skeleton type="text" width="130px" height="20px" />
                  <q-skeleton type="text" width="180px" height="14px" class="q-mt-xs" />
                </div>
              </div>
              <div v-else class="row items-center no-wrap">
                <div 
                  class="category-icon-circle q-mr-sm" 
                  :style="`background-color: ${getCategoryIconMeta(props.row.category_name).bg}; border-color: ${getCategoryIconMeta(props.row.category_name).border};`"
                >
                  <q-icon 
                    :name="getCategoryIconMeta(props.row.category_name).icon" 
                    size="18px" 
                    :style="`color: ${getCategoryIconMeta(props.row.category_name).color};`" 
                  />
                </div>
                <div>
                  <div class="text-weight-bold text-blue-grey-9 text-subtitle2 leading-tight">
                    {{ props.row.category_name }}
                  </div>
                  <div class="text-caption text-blue-grey-5 text-truncate" style="max-width: 280px;" v-if="props.row.description">
                    {{ props.row.description }}
                  </div>
                  <div class="text-caption text-blue-grey-4 text-italic" v-else>
                    No description added
                  </div>
                </div>
              </div>
            </q-td>
          </template>

          <template #body-cell-products_count="props">
            <q-td :props="props">
              <q-skeleton v-if="loading" type="rect" width="70px" height="24px" style="border-radius: 6px;" />
              <q-badge v-else color="slate-100" text-color="blue-grey-8" class="q-px-sm q-py-xs text-weight-bold rounded-borders border-slate-light count-badge">
                <q-icon name="inventory_2" size="12px" class="q-mr-xs text-blue-grey-5" />
                {{ props.row.products_count || 0 }} Items
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-action="props">
            <q-td :props="props" class="text-right">
              <div v-if="loading" class="row justify-end q-gutter-x-sm">
                <q-skeleton type="QBtn" size="sm" />
                <q-skeleton type="QBtn" size="sm" />
              </div>
              <div v-else>
                <q-btn flat round dense icon="edit" color="blue-grey-4" class="hover-action-btn q-mr-sm transition-ease" @click.stop="openEditModal(props.row)">
                  <q-tooltip class="bg-blue-grey-9 text-caption">Edit Category</q-tooltip>
                </q-btn>
                <q-btn flat round dense icon="delete" color="blue-grey-4" class="hover-action-btn-red transition-ease" @click.stop="openDeleteModal(props.row)">
                  <q-tooltip class="bg-red-9 text-caption">Delete Category</q-tooltip>
                </q-btn>
              </div>
            </q-td>
          </template>

          <!-- MOBILE GRID FORMATTER (Non-floating clean integrated layout) -->
          <template v-if="$q.screen.lt.md" v-slot:item="props">
            <div class="col-12 q-mb-md">
              <!-- Mobile Skeleton Card -->
              <q-card v-if="loading" flat bordered class="bg-white rounded-borders q-pa-md border-slate-light">
                <div class="row items-center no-wrap q-mb-md">
                  <q-skeleton type="rect" width="44px" height="44px" style="border-radius: 12px;" class="q-mr-md flex-shrink-0" />
                  <div class="col">
                    <q-skeleton type="text" width="60%" height="20px" />
                    <q-skeleton type="text" width="40%" height="14px" class="q-mt-xs" />
                  </div>
                </div>
                <q-separator class="q-my-sm" color="grey-2" />
                <div class="row items-center justify-between q-pt-xs">
                  <q-skeleton type="rect" width="70px" height="22px" style="border-radius: 6px;" />
                  <div class="row q-gutter-x-sm">
                    <q-skeleton type="rect" width="55px" height="28px" style="border-radius: 6px;" />
                    <q-skeleton type="rect" width="55px" height="28px" style="border-radius: 6px;" />
                  </div>
                </div>
              </q-card>

              <!-- Mobile Loaded Card -->
              <q-card v-else flat bordered class="bg-white rounded-borders border-slate-light overflow-hidden">
                <q-card-section class="q-pa-md">
                  <div class="row items-center no-wrap q-mb-xs">
                    <div 
                      class="category-icon-circle q-mr-md" 
                      style="width: 44px; height: 44px;"
                      :style="`background-color: ${getCategoryIconMeta(props.row.category_name).bg}; border-color: ${getCategoryIconMeta(props.row.category_name).border};`"
                    >
                      <q-icon 
                        :name="getCategoryIconMeta(props.row.category_name).icon" 
                        size="22px" 
                        :style="`color: ${getCategoryIconMeta(props.row.category_name).color};`" 
                      />
                    </div>
                    <div class="col ellipsis">
                      <div class="text-weight-bolder text-slate-800 text-subtitle1 ellipsis leading-tight">{{ props.row.category_name }}</div>
                      <!-- Removed "enrolled" keyword -->
                      <div class="text-caption text-blue-grey-5 q-mt-xs">{{ props.row.products_count || 0 }} products</div>
                    </div>
                  </div>
                  
                  <div v-if="props.row.description" class="text-caption text-slate-600 q-mt-sm bg-slate-50 q-pa-sm rounded-borders border-slate-light">
                    {{ props.row.description }}
                  </div>
                </q-card-section>

                <!-- Clean Non-Floating Footer System -->
                <q-card-section class="q-pa-sm q-px-md bg-slate-50 border-top row items-center justify-between no-wrap">
                  <div class="row items-center text-caption text-weight-bold text-slate-700 font-monospace">
                    <q-icon name="inventory_2" size="14px" class="q-mr-xs text-blue-grey-5" />
                    {{ props.row.products_count || 0 }} Items
                  </div>
                  
                  <div class="row q-gutter-x-sm no-wrap">
                    <q-btn 
                      outline 
                      dense 
                      icon="edit" 
                      color="blue-grey-6" 
                      text-color="slate-800" 
                      size="sm" 
                      class="q-px-sm rounded-borders bg-white border-slate-light text-weight-bold" 
                      style="height: 28px;"
                      @click.stop="openEditModal(props.row)" 
                      label="Edit" 
                      no-caps 
                    />
                    <q-btn 
                      outline 
                      dense 
                      icon="delete" 
                      color="red-8" 
                      size="sm" 
                      class="q-px-sm rounded-borders bg-white border-red-light text-weight-bold" 
                      style="height: 28px;"
                      @click.stop="openDeleteModal(props.row)" 
                      label="Delete" 
                      no-caps 
                    />
                  </div>
                </q-card-section>
              </q-card>
            </div>
          </template>

        </q-table>
      </q-card>

      <!-- ================= ADD CATEGORY MODAL ================= -->
      <q-dialog v-model="showAddModal" persistent backdrop-filter="blur(4px)" :position="$q.screen.lt.md ? 'bottom' : 'standard'">
        <q-card class="premium-glass-card overflow-hidden" :style="$q.screen.lt.md ? 'width: 100%; border-radius: 24px 24px 0 0; padding-bottom: env(safe-area-inset-bottom);' : 'width: 500px; max-width: 90vw; border-radius: 16px;'">
          <q-card-section class="row items-center q-pa-md header-red-gradient text-white">
            <q-avatar size="36px" color="white" text-color="red-9" icon="library_add" class="q-mr-sm shadow-1" />
            <div>
              <div class="text-h6 text-weight-bold leading-tight">Add New Category</div>
            </div>
            <q-space />
            <q-btn icon="close" flat round dense color="white" v-close-popup class="transition-ease" style="opacity: 0.8;" size="sm" />
          </q-card-section>

          <q-card-section :class="$q.screen.lt.md ? 'q-px-lg q-pb-lg q-pt-md' : 'q-pa-lg'">
            <q-form @submit.prevent="submitCategory" class="q-gutter-y-md">
              <div>
                <div class="text-subtitle2 text-weight-bold text-blue-grey-8 q-mb-xs">Category Name <span class="text-red-9">*</span></div>
                <q-input v-model="categoryForm.category_name" outlined dense class="custom-glass-input" placeholder="e.g. Beverages, Cooking Essentials, Snacks & Sweets" :rules="[val => !!val || 'Category name is required']" hide-bottom-space />
              </div>

              <div>
                <div class="text-subtitle2 text-weight-bold text-blue-grey-8 q-mb-xs">Description <span class="text-weight-regular text-blue-grey-4">(Optional)</span></div>
                <q-input v-model="categoryForm.description" type="textarea" outlined dense class="custom-glass-input" rows="3" placeholder="Briefly describe what belongs in this category..." />
              </div>

              <div class="row justify-end q-mt-lg" :class="$q.screen.lt.md ? 'column q-gutter-y-sm' : 'q-gutter-x-sm'">
                <q-btn v-if="!$q.screen.lt.md" flat label="Cancel" color="blue-grey-5" v-close-popup no-caps class="text-weight-bold transition-ease hover-text-dark" />
                <q-btn unelevated type="submit" label="Save Category" color="red-9" class="btn-premium text-white text-weight-bold" :class="$q.screen.lt.md ? 'full-width q-py-sm' : 'q-px-lg'" no-caps :loading="submitting" />
                <q-btn v-if="$q.screen.lt.md" flat label="Cancel" color="blue-grey-6" v-close-popup no-caps class="full-width text-weight-bold transition-ease bg-slate-50" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

      <!-- ================= EDIT CATEGORY MODAL ================= -->
      <q-dialog v-model="showEditModal" persistent backdrop-filter="blur(4px)" :position="$q.screen.lt.md ? 'bottom' : 'standard'">
        <q-card class="premium-glass-card overflow-hidden border-none" :style="$q.screen.lt.md ? 'width: 100%; border-radius: 24px 24px 0 0; padding-bottom: env(safe-area-inset-bottom);' : 'width: 500px; max-width: 90vw; border-radius: 16px;'">
          <q-card-section class="row items-center q-pa-md header-red-gradient text-white">
            <q-avatar size="36px" color="white" text-color="red-9" icon="edit_note" class="q-mr-sm shadow-1" />
            <div>
              <div class="text-h6 text-weight-bold leading-tight">Edit Category</div>
            </div>
            <q-space />
            <q-btn icon="close" flat round dense color="white" v-close-popup class="transition-ease" style="opacity: 0.8;" size="sm" />
          </q-card-section>

          <q-card-section :class="$q.screen.lt.md ? 'q-px-lg q-pb-lg q-pt-md' : 'q-pa-lg'">
            <q-form @submit.prevent="submitEditCategory" class="q-gutter-y-md">
              <div class="locked-field-container q-pa-md rounded-borders border-slate-light bg-slate-50">
                <div class="row items-center justify-between q-mb-sm">
                  <div class="text-subtitle2 text-weight-bold text-blue-grey-8">Category Name</div>
                  <q-chip size="sm" color="blue-grey-2" text-color="blue-grey-7" icon="lock" class="q-ma-none text-weight-medium">System Protected</q-chip>
                </div>
                
                <div class="row items-center no-wrap">
                  <div 
                    class="category-icon-circle q-mr-sm flex-shrink-0" 
                    style="width: 38px; height: 38px;"
                    :style="`background-color: ${getCategoryIconMeta(editCategoryForm.category_name).bg}; border-color: ${getCategoryIconMeta(editCategoryForm.category_name).border};`"
                  >
                    <q-icon 
                      :name="getCategoryIconMeta(editCategoryForm.category_name).icon" 
                      size="18px" 
                      :style="`color: ${getCategoryIconMeta(editCategoryForm.category_name).color};`" 
                    />
                  </div>
                  <q-input v-model="editCategoryForm.category_name" outlined dense class="custom-glass-input disabled-glass text-weight-bold col" disable readonly />
                </div>

                <div class="text-caption text-blue-grey-5 q-mt-xs leading-tight">
                  To maintain catalog consistency, core category names cannot be renamed.
                </div>
              </div>

              <div>
                <div class="text-subtitle2 text-weight-bold text-blue-grey-8 q-mb-xs">Category Description</div>
                <q-input v-model="editCategoryForm.description" type="textarea" outlined dense class="custom-glass-input" rows="3" placeholder="Add specific guidelines, notes, or examples for this category..." />
              </div>

              <div class="row justify-end q-mt-lg" :class="$q.screen.lt.md ? 'column q-gutter-y-sm' : 'q-gutter-x-sm'">
                <q-btn v-if="!$q.screen.lt.md" flat label="Cancel" color="blue-grey-5" v-close-popup no-caps class="text-weight-bold transition-ease hover-text-dark" />
                <q-btn unelevated type="submit" label="Save Changes" color="red-9" class="btn-premium text-white text-weight-bold" :class="$q.screen.lt.md ? 'full-width q-py-sm' : 'q-px-lg'" no-caps :loading="submitting" />
                <q-btn v-if="$q.screen.lt.md" flat label="Cancel" color="blue-grey-6" v-close-popup no-caps class="full-width text-weight-bold transition-ease bg-slate-50" />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </q-dialog>

      <!-- ================= RED THEMED DELETE MODAL ================= -->
      <q-dialog v-model="showDeleteModal" persistent backdrop-filter="blur(6px)" transition-show="scale" transition-hide="scale">
        <q-card class="delete-modal-card overflow-hidden" style="width: 440px; max-width: 92vw; border-radius: 18px;">
          
          <!-- STATE 1: Blocked from delete due to active products -->
          <template v-if="categoryToDelete && categoryToDelete.products_count > 0">
            <q-card-section class="q-pa-lg text-center">
              <div class="delete-icon-circle bg-red-50 border-red-light q-mx-auto q-mb-md flex flex-center">
                <q-icon name="error_outline" size="34px" color="red-9" />
              </div>
              <h3 class="text-h6 text-weight-bolder text-slate-800 q-mt-none q-mb-xs leading-tight">Cannot Delete Category</h3>
              
              <p class="text-body2 text-slate-600 q-mt-sm q-mb-md leading-normal">
                <strong>"{{ categoryToDelete.category_name }}"</strong> currently links to 
                <span class="text-weight-bold text-red-9 font-monospace">{{ categoryToDelete.products_count }} item(s)</span>.
              </p>

              <div class="delete-blocked-notice text-left q-pa-sm rounded-borders row items-start no-wrap q-mt-xs">
                <q-icon name="info" size="18px" color="red-8" class="q-mr-xs q-mt-xs flex-shrink-0" />
                <div class="text-caption text-slate-700 leading-tight">
                  Please reassign or delete the products under this category before removing it from your catalog.
                </div>
              </div>
            </q-card-section>

            <q-separator color="grey-2" />

            <q-card-actions align="center" class="q-pa-md bg-slate-50">
              <q-btn unelevated label="Got it" color="dark" text-color="white" v-close-popup no-caps class="full-width text-weight-bold btn-premium q-py-sm shadow-1" />
            </q-card-actions>
          </template>

          <!-- STATE 2: Empty category, deletion allowed -->
          <template v-else-if="categoryToDelete">
            <q-card-section class="q-pa-lg text-center">
              <div class="delete-icon-circle bg-red-50 border-red-light q-mx-auto q-mb-md flex flex-center">
                <q-icon name="delete_outline" size="34px" color="red-9" />
              </div>
              <h3 class="text-h6 text-weight-bolder text-slate-800 q-mt-none q-mb-xs leading-tight">Delete Category?</h3>
              
              <p class="text-body2 text-slate-600 q-mt-sm q-mb-none leading-normal">
                Are you sure you want to permanently remove 
                <strong class="text-slate-900">"{{ categoryToDelete.category_name }}"</strong>? 
                This action is immediate and cannot be undone.
              </p>
            </q-card-section>

            <q-separator color="grey-2" />

            <q-card-actions align="between" class="q-pa-md bg-slate-50" :class="$q.screen.lt.md ? 'column q-gutter-y-sm' : 'row no-wrap'">
              <q-btn flat label="Cancel" color="slate-600" v-close-popup no-caps class="text-weight-bold" :class="$q.screen.lt.md ? 'full-width' : 'q-px-md'" />
              <q-btn unelevated label="Yes, Delete Category" color="red-9" @click="confirmDelete" no-caps class="text-weight-bold btn-premium shadow-1" :class="$q.screen.lt.md ? 'full-width q-py-sm' : 'q-px-lg'" :loading="submitting" />
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

const skeletonRows = Array.from({ length: 6 }, (_, index) => ({
  category_id: `skeleton-${index}`
}))

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

// Exact Category Icons matching the user reference photo with appropriate treat/snack icon:
const getCategoryIconMeta = (name) => {
  const clean = (name || '').toLowerCase()

  if (clean.includes('beverage') || clean.includes('drink') || clean.includes('juice') || clean.includes('water') || clean.includes('soda')) {
    return { icon: 'local_drink', bg: '#eff6ff', border: '#bfdbfe', color: '#2563eb' }
  }
  if (clean.includes('cooking') || clean.includes('essential') || clean.includes('condiment') || clean.includes('oil') || clean.includes('spice') || clean.includes('sauce')) {
    return { icon: 'restaurant', bg: '#fef3c7', border: '#fde68a', color: '#d97706' }
  }
  if (clean.includes('laundry') || clean.includes('cleaning') || clean.includes('clean') || clean.includes('detergent')) {
    return { icon: 'local_laundry_service', bg: '#ecfdf5', border: '#a7f3d0', color: '#059669' }
  }
  if (clean.includes('personal') || clean.includes('care') || clean.includes('hygiene') || clean.includes('soap') || clean.includes('shampoo')) {
    return { icon: 'spa', bg: '#fdf2f8', border: '#fbcfe8', color: '#db2777' }
  }
  // Snacks & Sweets redesigned to 'cake' / treat icon
  if (clean.includes('snack') || clean.includes('sweet') || clean.includes('candy') || clean.includes('biscuit') || clean.includes('chip') || clean.includes('treat')) {
    return { icon: 'cake', bg: '#fff7ed', border: '#fed7aa', color: '#ea580c' }
  }
  if (clean.includes('other') || clean.includes('misc') || clean.includes('general')) {
    return { icon: 'widgets', bg: '#fff1f2', border: '#fecdd3', color: '#e11d48' }
  }

  return { icon: 'category', bg: '#f1f5f9', border: '#e2e8f0', color: '#64748b' }
}

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

/* Subtle Ambient Glows */
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

/* Header Glass Icon Box */
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

.border-none {
  border: none !important;
}

/* Custom Inputs */
.custom-glass-input :deep(.q-field__control) {
  background: rgba(248, 250, 252, 0.8); 
  border-radius: 8px;
  transition: all 0.3s ease;
  height: 40px;
}
.custom-glass-input :deep(.q-field__control:before) { border: 1px solid rgba(226, 232, 240, 0.8); }
.custom-glass-input :deep(.q-field__control:hover) { background: #ffffff; }
.custom-glass-input :deep(.q-field--focused .q-field__control) {
  background: #ffffff;
  box-shadow: 0 0 0 2px rgba(185, 28, 28, 0.15); 
  border-color: #B91C1C;
}

.custom-glass-input.q-textarea :deep(.q-field__control) {
  height: auto !important;
  min-height: 80px;
}

.disabled-glass :deep(.q-field__control) {
  background: rgba(241, 245, 249, 0.5) !important;
  cursor: not-allowed;
  color: #64748b;
}

/* Red Outlined Export Button */
.btn-export-red {
  border-radius: 8px !important;
  background-color: #ffffff !important;
  border: 1px solid #b91c1c !important;
  color: #b91c1c !important;
  transition: all 0.2s ease;
}
.btn-export-red:hover {
  background-color: #fef2f2 !important;
  border-color: #991b1b !important;
  color: #991b1b !important;
  box-shadow: 0 2px 8px rgba(185, 28, 28, 0.15);
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
.border-red-light { border: 1px solid rgba(254, 202, 202, 0.6); }
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

.count-badge {
  background-color: #f1f5f9;
  border-radius: 6px;
  font-size: 12px;
}

/* Empty State Styling */
.empty-state-glass {
  background: rgba(248, 250, 252, 0.6);
  border: 1px dashed rgba(203, 213, 225, 0.8);
  border-radius: 12px;
  width: calc(100% - 32px);
}
.empty-icon-wrapper {
  position: relative;
  display: inline-flex;
  justify-content: center;
  align-items: center;
}
.drop-shadow-icon { filter: drop-shadow(0 4px 6px rgba(15, 23, 42, 0.05)); }

/* Dark Red Gradient for Modal Headers */
.header-red-gradient {
  background: linear-gradient(135deg, #B91C1C 0%, #450A0A 100%);
  border-bottom: 2px solid #7f1d1d;
}

/* Circular Category Icon Pill */
.category-icon-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1.5px solid transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

/* Delete Modal Styling */
.delete-modal-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 15px 35px rgba(15, 23, 42, 0.12) !important;
}

.delete-icon-circle {
  width: 58px;
  height: 58px;
  border-radius: 50%;
}

.delete-blocked-notice {
  background-color: #fef2f2;
  border: 1px solid #fca5a5;
}

.shrink-none {
  flex-shrink: 0;
}

@media (max-width: 767px) {
  .vendor-page.mobile-page-padding { padding: 16px 16px calc(90px + env(safe-area-inset-bottom)) 16px !important; }
  .desktop-only { display: none !important; }
}
</style>