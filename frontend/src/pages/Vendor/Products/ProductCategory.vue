<template>
  <q-page class="vp-page">
    <div class="vp-container">

      <div class="vp-header">
        <div>
          <h1 class="vp-title">Categories</h1>
          <p class="vp-subtitle">Organize your products so customers can find them.</p>
        </div>
        <div class="vp-header-actions">
          <q-btn outline no-caps color="primary" icon="o_download" label="Export" class="vp-pill-btn" :loading="isExporting" @click="exportCategories" />
          <q-btn unelevated no-caps color="primary" icon="add" label="Add Category" class="vp-primary-btn" @click="openAddModal" />
        </div>
      </div>

      <div class="vp-card">
        <div class="vp-toolbar">
          <q-input
            v-model="search"
            outlined
            dense
            clearable
            clear-icon="o_close"
            hide-bottom-space
            placeholder="Search categories"
            class="vp-search"
          >
            <template #prepend>
              <q-icon name="o_search" size="18px" />
            </template>
          </q-input>
          <span v-if="!loading" class="cat-total">{{ filteredCategories.length }} {{ filteredCategories.length === 1 ? 'category' : 'categories' }}</span>
        </div>

        <SkeletonTable v-if="loading" :columns="SKELETON_COLUMNS" :rows="5" :list="$q.screen.lt.md" thumb />

        <div v-else-if="!filteredCategories.length" class="vp-empty">
          <div class="vp-empty-icon"><q-icon name="o_style" size="24px" /></div>
          <div class="vp-empty-title">{{ categories.length ? 'No matching categories' : 'No categories yet' }}</div>
          <div class="vp-empty-text">
            {{ categories.length ? 'Try another name.' : 'Add a category to start grouping your products.' }}
          </div>
        </div>

        <div v-else-if="!$q.screen.lt.md" class="vp-table-wrap">
          <table class="vp-table cat-table">
            <thead>
              <tr>
                <th>Category</th>
                <th class="col-count">Products</th>
                <th class="text-right col-act">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="category in filteredCategories" :key="category.category_id">
                <td>
                  <div class="vp-person">
                    <span class="cat-icon" :class="`cat-tone--${categoryKind(category.category_name).tone}`">
                      <q-icon :name="categoryKind(category.category_name).icon" size="18px" />
                    </span>
                    <div class="cat-text">
                      <div class="vp-name">{{ category.category_name }}</div>
                      <div class="cat-desc" :class="{ 'cat-desc--empty': !category.description }">
                        {{ category.description || 'No description added' }}
                      </div>
                    </div>
                  </div>
                </td>
                <td><span class="cat-count"><q-icon name="o_inventory_2" size="14px" /> {{ countLabel(category) }}</span></td>
                <td class="text-right">
                  <q-btn flat round dense icon="o_edit" class="cat-action" :aria-label="`Edit ${category.category_name}`" @click="openEditModal(category)">
                    <q-tooltip>Edit</q-tooltip>
                  </q-btn>
                  <q-btn flat round dense icon="o_delete" class="cat-action cat-action--danger" :aria-label="`Delete ${category.category_name}`" @click="openDeleteModal(category)">
                    <q-tooltip>Delete</q-tooltip>
                  </q-btn>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="vp-list">
          <div v-for="category in filteredCategories" :key="category.category_id" class="cat-item">
            <span class="cat-icon cat-icon--lg" :class="`cat-tone--${categoryKind(category.category_name).tone}`">
              <q-icon :name="categoryKind(category.category_name).icon" size="20px" />
            </span>
            <div class="vp-list-body">
              <span class="vp-name">{{ category.category_name }}</span>
              <div class="vp-list-meta">{{ countLabel(category) }}</div>
              <div v-if="category.description" class="cat-desc cat-desc--wrap">{{ category.description }}</div>
            </div>
            <div class="cat-item-actions">
              <q-btn flat round dense icon="o_edit" class="cat-action" :aria-label="`Edit ${category.category_name}`" @click="openEditModal(category)" />
              <q-btn flat round dense icon="o_delete" class="cat-action cat-action--danger" :aria-label="`Delete ${category.category_name}`" @click="openDeleteModal(category)" />
            </div>
          </div>
        </div>
      </div>

    </div>

    <q-dialog v-model="showAddModal" persistent>
      <q-card class="vp-dialog vp-dialog--wide">
        <q-form @submit.prevent="submitCategory">
          <div class="vp-dialog-head">
            <span class="vp-dialog-icon"><q-icon name="o_library_add" size="22px" /></span>
            <div>
              <div class="vp-dialog-title">Add category</div>
              <div class="vp-dialog-text">Group related products under one name.</div>
            </div>
          </div>
          <div class="vp-dialog-body">
            <div>
              <label class="vp-field-label" for="cat-add-name">Category name</label>
              <q-input
                v-model="categoryForm.category_name"
                for="cat-add-name"
                outlined
                dense
                autofocus
                placeholder="e.g. Beverages, Snacks & Sweets"
                class="vp-input"
                :rules="[val => !!(val && val.trim()) || 'Enter a category name.']"
              />
            </div>
            <div>
              <label class="vp-field-label" for="cat-add-desc">Description <span class="vp-field-optional">(optional)</span></label>
              <q-input
                v-model="categoryForm.description"
                for="cat-add-desc"
                type="textarea"
                outlined
                autogrow
                placeholder="What belongs in this category?"
                class="vp-input cat-textarea"
              />
            </div>
          </div>
          <div class="vp-dialog-actions">
            <q-btn v-close-popup outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" :disable="submitting" />
            <q-btn type="submit" unelevated no-caps color="primary" label="Save Category" class="vp-dialog-btn" :loading="submitting" />
          </div>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="showEditModal" persistent>
      <q-card class="vp-dialog vp-dialog--wide">
        <q-form @submit.prevent="submitEditCategory">
          <div class="vp-dialog-head">
            <span class="vp-dialog-icon"><q-icon name="o_edit_note" size="22px" /></span>
            <div>
              <div class="vp-dialog-title">Edit category</div>
              <div class="vp-dialog-text">Update the description shown with this category.</div>
            </div>
          </div>
          <div class="vp-dialog-body">
            <div>
              <label class="vp-field-label">Category name</label>
              <div class="cat-locked">
                <span class="cat-icon" :class="`cat-tone--${categoryKind(editCategoryForm.category_name).tone}`">
                  <q-icon :name="categoryKind(editCategoryForm.category_name).icon" size="18px" />
                </span>
                <span class="cat-locked-name">{{ editCategoryForm.category_name }}</span>
                <q-icon name="o_lock" size="16px" class="cat-locked-icon" />
              </div>
              <div class="cat-hint">Names can't be changed, to keep the catalog consistent.</div>
            </div>
            <div>
              <label class="vp-field-label" for="cat-edit-desc">Description</label>
              <q-input
                v-model="editCategoryForm.description"
                for="cat-edit-desc"
                type="textarea"
                outlined
                autogrow
                placeholder="Add notes or examples for this category"
                class="vp-input cat-textarea"
              />
            </div>
          </div>
          <div class="vp-dialog-actions">
            <q-btn v-close-popup outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" :disable="submitting" />
            <q-btn type="submit" unelevated no-caps color="primary" label="Save Changes" class="vp-dialog-btn" :loading="submitting" />
          </div>
        </q-form>
      </q-card>
    </q-dialog>

    <q-dialog v-model="showDeleteModal" persistent>
      <q-card v-if="categoryToDelete" class="vp-dialog vp-dialog--wide">
        <!-- A category that still has products can't be deleted, so the dialog shows what's in it and where to go to move them. -->
        <template v-if="categoryToDelete.products_count > 0">
          <div class="vp-dialog-head">
            <span class="vp-dialog-icon cat-icon--warn"><q-icon name="o_inventory_2" size="22px" /></span>
            <div>
              <div class="vp-dialog-title">Move its products first</div>
              <div class="vp-dialog-text">
                <strong>{{ categoryToDelete.category_name }}</strong> still has {{ countLabel(categoryToDelete) }}.
                A category can only be deleted once it's empty.
              </div>
            </div>
            <q-btn v-close-popup flat round dense icon="o_close" class="vp-dialog-close" aria-label="Close" />
          </div>

          <div class="vp-dialog-body">
            <div class="cat-preview">
              <div class="cat-preview-label">In this category</div>
              <div v-if="blockedLoading" class="cat-preview-list">
                <div v-for="n in Math.min(3, categoryToDelete.products_count)" :key="n" class="cat-preview-item">
                  <q-skeleton type="rect" width="32px" height="32px" class="cat-preview-skeleton" />
                  <q-skeleton type="text" width="55%" />
                </div>
              </div>
              <ul v-else class="cat-preview-list">
                <li v-for="product in blockedProducts" :key="product.inventory_id" class="cat-preview-item">
                  <span class="cat-preview-img">
                    <img v-if="product.image_url" :src="product.image_url" alt="" />
                    <q-icon v-else name="o_image" size="16px" />
                  </span>
                  <span class="cat-preview-name">{{ product.product_name }}</span>
                </li>
                <li v-if="categoryToDelete.products_count > blockedProducts.length" class="cat-preview-more">
                  and {{ categoryToDelete.products_count - blockedProducts.length }} more
                </li>
              </ul>
            </div>
            <div class="cat-tip">
              <q-icon name="o_lightbulb" size="16px" />
              <span>Open each product and pick another category, or delete the ones you no longer sell. Then come back to delete this category.</span>
            </div>
          </div>

          <div class="vp-dialog-actions">
            <q-btn v-close-popup outline no-caps color="primary" label="Close" class="vp-dialog-btn" />
            <q-btn unelevated no-caps color="primary" icon-right="o_arrow_forward" label="View Products" class="vp-dialog-btn" @click="viewCategoryProducts" />
          </div>
        </template>

        <!-- An empty category can go, after one clear confirmation that shows exactly which one. -->
        <template v-else>
          <div class="vp-dialog-head">
            <span class="vp-dialog-icon vp-dialog-icon--danger"><q-icon name="o_delete" size="22px" /></span>
            <div>
              <div class="vp-dialog-title">Delete this category?</div>
              <div class="vp-dialog-text">It has no products, so nothing else changes. This can't be undone.</div>
            </div>
          </div>
          <div class="vp-dialog-body">
            <div class="cat-locked">
              <span class="cat-icon" :class="`cat-tone--${categoryKind(categoryToDelete.category_name).tone}`">
                <q-icon :name="categoryKind(categoryToDelete.category_name).icon" size="18px" />
              </span>
              <span class="cat-locked-name">{{ categoryToDelete.category_name }}</span>
              <span class="cat-count">0 products</span>
            </div>
          </div>
          <div class="vp-dialog-actions">
            <q-btn v-close-popup outline no-caps color="primary" label="Cancel" class="vp-dialog-btn" :disable="submitting" />
            <q-btn unelevated no-caps color="primary" icon="o_delete" label="Delete Category" class="vp-dialog-btn" :loading="submitting" @click="confirmDelete" />
          </div>
        </template>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/boot/axios'
import { useQuasar } from 'quasar'
import SkeletonTable from '@/components/vendor/SkeletonTable.vue'
import { categoryStyle } from '@/composables/useCategories'

const $q = useQuasar()
const router = useRouter()

// The placeholder rows take the same columns as the table: icon and name, product count, and the two actions.
const SKELETON_COLUMNS = [
  { type: 'thumb', lines: 2 },
  { width: '18%', type: 'pill', size: 92 },
  { width: '14%', type: 'actions', align: 'right' }
]
const PREVIEW_LIMIT = 5

const blockedProducts = ref([])
const blockedLoading = ref(false)
const search = ref('')
const loading = ref(true)
const categories = ref([])
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const submitting = ref(false)
const isExporting = ref(false)

const categoryToDelete = ref(null)

const categoryForm = ref({ category_name: '', description: '' })
const editCategoryForm = ref({ category_id: null, category_name: '', description: '' })

// The same icon and colour the consumer's category cards show for each category, from the shared list in useCategories.
const categoryKind = name => categoryStyle((name || '').trim())

const countLabel = category => {
  const count = Number(category.products_count || 0)
  return `${count} product${count === 1 ? '' : 's'}`
}

// "Others" is the catch-all, so it always sits last; everything else reads A to Z.
const isOthers = category => /^others?$/i.test((category.category_name || '').trim())

const sortedCategories = computed(() =>
  [...categories.value].sort((a, b) =>
    (isOthers(a) - isOthers(b)) || (a.category_name || '').localeCompare(b.category_name || '', undefined, { sensitivity: 'base' })
  )
)

const filteredCategories = computed(() => {
  const needle = (search.value || '').trim().toLowerCase()
  if (!needle) return sortedCategories.value
  return sortedCategories.value.filter(c => (c.category_name || '').toLowerCase().includes(needle))
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

const openAddModal = () => {
  categoryForm.value = { category_name: '', description: '' }
  showAddModal.value = true
}

const submitCategory = async () => {
  submitting.value = true
  try {
    await api.post('/categories', {
      category_name: categoryForm.value.category_name.trim(),
      description: categoryForm.value.description
    })
    $q.notify({ type: 'positive', message: 'Category added.', position: 'top-right' })
    showAddModal.value = false
    categoryForm.value = { category_name: '', description: '' }
    await fetchCategories()
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to add the category.', position: 'top-right' })
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
    link.setAttribute('download', `Tindahan-Product-Categories-Report-${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    setTimeout(() => window.URL.revokeObjectURL(url), 1000)
  } catch (error) {
    console.error('Export failed:', error)
    $q.notify({ type: 'negative', message: 'Failed to generate the category report.', position: 'top-right' })
  } finally {
    isExporting.value = false
  }
}

const openEditModal = category => {
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
    $q.notify({ type: 'positive', message: 'Category updated.', position: 'top-right' })
    showEditModal.value = false
    await fetchCategories()
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to update the category.', position: 'top-right' })
  } finally {
    submitting.value = false
  }
}

// A category with products opens with a preview of them, loaded as the dialog appears.
const openDeleteModal = async category => {
  categoryToDelete.value = category
  blockedProducts.value = []
  showDeleteModal.value = true
  if (Number(category.products_count) > 0) {
    blockedLoading.value = true
    // A slow answer after another category's dialog has opened is dropped, so the preview never lists the wrong products.
    const stillOpen = () => categoryToDelete.value?.category_id === category.category_id
    try {
      const res = await api.get('/vendor/products')
      if (stillOpen()) blockedProducts.value = (res.data || []).filter(p => Number(p.category_id) === Number(category.category_id)).slice(0, PREVIEW_LIMIT)
    } catch (error) {
      console.error('Failed to load the category\'s products', error)
    } finally {
      if (stillOpen()) blockedLoading.value = false
    }
  }
}

// Opens the product list already filtered to this category, where its products can be moved.
const viewCategoryProducts = () => {
  const id = categoryToDelete.value?.category_id
  showDeleteModal.value = false
  router.push({ path: '/vendor/products/list', query: { category: id } })
}

const confirmDelete = async () => {
  const name = categoryToDelete.value.category_name
  submitting.value = true
  try {
    await api.delete(`/categories/${categoryToDelete.value.category_id}`)
    $q.notify({ type: 'positive', icon: 'o_check_circle', message: `“${name}” was deleted.`, position: 'top-right' })
    showDeleteModal.value = false
    categoryToDelete.value = null
    await fetchCategories()
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || `Couldn't delete “${name}”. Please try again.`, position: 'top-right' })
  } finally {
    submitting.value = false
  }
}

onMounted(fetchCategories)
</script>

<style scoped>
.cat-total {
  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-muted);
}

.cat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 38px;
  height: 38px;

  border-radius: var(--r-surface);
}

.cat-icon--lg {
  width: 44px;
  height: 44px;
}

/* The consumer category cards' tones (CategoryCard.vue), so a category is the same colour on both sides. */
.cat-tone--brand {
  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
  color: var(--c-brand);
}

.cat-tone--blue {
  background: linear-gradient(145deg, #e8f2fd 0%, #d6e8fa 100%);
  color: #1668ab;
}

.cat-tone--amber {
  background: linear-gradient(145deg, #fdf3e3 0%, #fae8cd 100%);
  color: #b06a10;
}

.cat-tone--orange {
  background: linear-gradient(145deg, #fdeee6 0%, #fadfd0 100%);
  color: #c1521c;
}

.cat-tone--rose {
  background: linear-gradient(145deg, #fdeaf2 0%, #f9d8e6 100%);
  color: #b3215f;
}

.cat-tone--teal {
  background: linear-gradient(145deg, #e2f5f2 0%, #cbeae5 100%);
  color: #0f766e;
}

.cat-text {
  min-width: 0;
}

.cat-desc {
  max-width: 520px;
  margin-top: 2px;
  overflow: hidden;

  font-size: var(--fs-xs);
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text-3);
}

.cat-desc--empty {
  font-style: italic;

  color: var(--c-muted);
}

.cat-desc--wrap {
  display: -webkit-box;
  margin-top: 6px;

  white-space: normal;

  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.cat-count {
  display: inline-flex;
  align-items: center;

  gap: 5px;
  padding: 3px 10px;

  border-radius: var(--r-pill);

  background: var(--c-surface);

  font-size: var(--fs-xs);
  font-weight: 700;

  color: var(--c-text-3);
}

.cat-count .q-icon {
  color: var(--c-muted);
}

.cat-action {
  color: var(--c-muted);
}

.cat-action:hover {
  color: var(--c-text);
}

.cat-action--danger:hover {
  color: var(--c-danger);
}

.cat-table .col-count { width: 18%; }
.cat-table .col-act { width: 14%; }

.cat-item {
  display: flex;
  align-items: flex-start;

  gap: 12px;
  padding: 12px 8px;

  border-bottom: 1px solid var(--c-hairline);
}

.cat-item:last-child {
  border-bottom: none;
}

.cat-item .vp-name {
  display: block;

  font-size: var(--fs-sm);
}

.cat-item-actions {
  display: flex;
  flex-shrink: 0;

  gap: 2px;
}

.cat-locked {
  display: flex;
  align-items: center;

  gap: 10px;
  padding: 8px 12px 8px 8px;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);

  background: var(--c-surface-2);
}

.cat-locked-name {
  flex: 1;
  min-width: 0;
  overflow: hidden;

  font-size: var(--fs-sm);
  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text-2);
}

.cat-locked-icon {
  color: var(--c-muted);
}

.cat-hint {
  margin-top: 6px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.cat-textarea :deep(textarea) {
  min-height: 72px;
}

/* DELETE — the warning tile, the preview of what's inside, and a short tip. */

.cat-icon--warn {
  background: var(--c-warning-tint);
  color: var(--c-warning);
}

.cat-preview {
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-control);
}

.cat-preview-label {
  padding: 8px 12px;

  border-bottom: 1px solid var(--c-hairline);

  background: var(--c-surface-2);

  font-size: var(--fs-2xs);
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;

  color: var(--c-muted);
}

.cat-preview-list {
  margin: 0;
  padding: 4px 12px;

  list-style: none;
}

.cat-preview-item {
  display: flex;
  align-items: center;

  gap: 10px;
  padding: 8px 0;

  border-bottom: 1px solid var(--c-hairline);
}

.cat-preview-item:last-child {
  border-bottom: none;
}

.cat-preview-img {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 32px;
  height: 32px;
  overflow: hidden;

  border-radius: var(--r-control);

  background: var(--c-surface);
  color: var(--c-muted);
}

.cat-preview-img img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.cat-preview-skeleton {
  border-radius: var(--r-control);
}

.cat-preview-name {
  min-width: 0;
  overflow: hidden;

  font-size: var(--fs-sm);
  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;

  color: var(--c-text);
}

.cat-preview-more {
  padding: 8px 0 6px;

  font-size: var(--fs-xs);
  font-weight: 600;

  color: var(--c-muted);
}

.cat-tip {
  display: flex;
  align-items: flex-start;

  gap: 8px;
  padding: 10px 12px;

  border-radius: var(--r-control);

  background: var(--c-info-wash);

  font-size: var(--fs-xs);
  line-height: 1.5;

  color: var(--c-text-3);
}

.cat-tip .q-icon {
  flex-shrink: 0;
  margin-top: 1px;

  color: var(--c-info);
}

@media (max-width: 600px) {
  .vp-header-actions {
    width: 100%;
  }

  .vp-header-actions .q-btn {
    flex: 1;
  }
}
</style>
