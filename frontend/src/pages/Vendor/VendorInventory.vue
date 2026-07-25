<template>
  <q-page class="vendor-page">
    <div class="page-container">

      <div class="page-header">
        <h1>Inventory Management</h1>
        <p class="page-subtitle">Add and manage products in your store</p>
      </div>

      <div class="toolbar">
        <q-btn
          label="Add Product"
          no-caps
          unelevated
          icon="add"
          class="add-btn"
          @click="showAddModal = true"
        />
      </div>

      <!-- TABLE -->
      <div class="table-card">
        <q-table
          flat
          :rows="inventory"
          :columns="columns"
          row-key="inventory_id"
          :loading="loading"
          no-data-label="No products in inventory"
          class="data-table"
        >
          <template #body-cell-product_picture="props">
            <q-td :props="props">
              <q-avatar size="40px" square class="product-avatar">
                <img :src="getImageUrl(props.row.product_picture)" alt="Product" v-if="props.row.product_picture" />
                <q-icon name="inventory_2" size="24px" color="grey-5" v-else />
              </q-avatar>
            </q-td>
          </template>

          <template #body-cell-price="props">
            <q-td :props="props">
              ₱{{ Number(props.row.price).toFixed(2) }}
            </q-td>
          </template>

          <template #body-cell-status="props">
            <q-td :props="props">
              <q-chip
                :color="props.row.status === 'active' ? 'positive' : 'grey'"
                text-color="white"
                dense
                size="12px"
              >
                {{ props.row.status }}
              </q-chip>
            </q-td>
          </template>
        </q-table>
      </div>
    </div>

    <!-- ADD PRODUCT MODAL -->
    <q-dialog v-model="showAddModal">
      <q-card class="add-dialog">
        <q-card-section>
          <div class="modal-title">Add New Product</div>
        </q-card-section>

        <q-card-section>
          <q-form ref="addProductForm" @submit.prevent="handleAddProduct">
            
            <div class="field-group">
              <label class="input-label">Product Name</label>
              <q-input
                v-model="form.product_name"
                outlined
                dense
                placeholder="e.g. Coca-Cola 1L"
                :rules="[val => !!val || 'Product name is required']"
              />
            </div>

            <div class="row q-col-gutter-md">
              <div class="col-6">
                <div class="field-group">
                  <label class="input-label">Price (₱)</label>
                  <q-input
                    v-model.number="form.price"
                    type="number"
                    step="0.01"
                    outlined
                    dense
                    :rules="[val => val >= 0 || 'Price must be positive']"
                  />
                </div>
              </div>
              <div class="col-6">
                <div class="field-group">
                  <label class="input-label">Stock Quantity</label>
                  <q-input
                    v-model.number="form.stock_quantity"
                    type="number"
                    outlined
                    dense
                    :rules="[val => val >= 0 || 'Stock must be positive']"
                  />
                </div>
              </div>
            </div>

            <div class="field-group">
              <label class="input-label">Category</label>
              <q-select
                v-model="form.category_id"
                :options="categoryOptions"
                option-value="id"
                option-label="name"
                emit-value
                map-options
                outlined
                dense
                :rules="[val => !!val || 'Category is required']"
              />
            </div>

            <div class="field-group">
              <label class="input-label">Product Picture</label>
              <q-file
                v-model="form.product_picture"
                outlined
                dense
                accept="image/*"
                hint="Upload a clear photo of the product"
              >
                <template #prepend>
                  <q-icon name="attach_file" />
                </template>
              </q-file>
            </div>

            <div v-if="submitError" class="error-message q-mt-sm">
              {{ submitError }}
            </div>

            <div class="modal-actions q-mt-lg">
              <q-btn label="Cancel" flat no-caps @click="showAddModal = false" />
              <q-btn type="submit" label="Save Product" unelevated color="primary" no-caps :loading="submitLoading" />
            </div>

          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { api } from '@/boot/axios'

const loading = ref(false)
const inventory = ref([])
const showAddModal = ref(false)
const submitLoading = ref(false)
const submitError = ref('')

const form = reactive({
  product_name: '',
  price: 0,
  stock_quantity: 0,
  category_id: null,
  product_picture: null,
})

// Hardcoded for now. In a real app, you'd fetch these from /api/categories
const categoryOptions = [
  { id: 1, name: 'Beverages' },
  { id: 2, name: 'Snacks' },
  { id: 3, name: 'Canned Goods' },
  { id: 4, name: 'Toiletries' },
  { id: 5, name: 'Household Items' },
]

const columns = [
  { name: 'product_picture', label: 'Image', field: 'product_picture', align: 'left' },
  { name: 'product_name', label: 'Product Name', field: 'product_name', align: 'left', sortable: true },
  { name: 'price', label: 'Price', field: 'price', align: 'left', sortable: true },
  { name: 'stock_quantity', label: 'Stock', field: 'stock_quantity', align: 'left', sortable: true },
  { name: 'category_id', label: 'Category ID', field: 'category_id', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
]

const fetchInventory = async () => {
  loading.value = true
  try {
    const res = await api.get('/vendor/inventory')
    inventory.value = res.data
  } catch {
    inventory.value = []
  } finally {
    loading.value = false
  }
}

const handleAddProduct = async () => {
  submitError.value = ''
  submitLoading.value = true

  try {
    const formData = new FormData()
    formData.append('product_name', form.product_name)
    formData.append('price', form.price)
    formData.append('stock_quantity', form.stock_quantity)
    formData.append('category_id', form.category_id)
    
    if (form.product_picture) {
      formData.append('product_picture', form.product_picture)
    }

    const res = await api.post('/vendor/inventory', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    inventory.value.push(res.data.item)
    showAddModal.value = false
    
    // Reset form
    form.product_name = ''
    form.price = 0
    form.stock_quantity = 0
    form.category_id = null
    form.product_picture = null
  } catch (error) {
    submitError.value = error.response?.data?.message || 'Failed to add product'
  } finally {
    submitLoading.value = false
  }
}

const getImageUrl = (path) => {
  // Replace this with your actual backend URL in production
  return `http://127.0.0.1:8000/storage/${path}`
}

onMounted(() => {
  fetchInventory()
})
</script>

<style scoped>
.vendor-page {
  background: #f4f5f7;
  font-family: 'Roboto', Arial, sans-serif;
  min-height: 100vh;
}

.page-container {
  max-width: 960px;
  margin: 0 auto;
  padding: 32px 28px;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #111111;
}

.page-subtitle {
  margin: 4px 0 0;
  font-size: 13px;
  color: #8992a2;
}

.toolbar {
  margin-bottom: 16px;
  display: flex;
  justify-content: flex-end;
}

.add-btn {
  background: #bd2427;
  color: white;
}

.table-card {
  background: #ffffff;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.product-avatar {
  background: #f4f5f7;
  border-radius: 6px;
}

.product-avatar img {
  object-fit: cover;
}

.add-dialog {
  width: 500px;
  max-width: 90vw;
  border-radius: 10px;
}

.modal-title {
  font-size: 18px;
  font-weight: 700;
  color: #222222;
}

.field-group {
  margin-bottom: 16px;
}

.input-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #333333;
  margin-bottom: 6px;
}

.error-message {
  color: #ef4444;
  font-size: 13px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>
