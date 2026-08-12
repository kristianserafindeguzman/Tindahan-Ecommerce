<template>
  <q-page class="storefront-page">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <h1 class="page-title">My Cart</h1>
      <p v-if="items.length" class="page-subtitle">Choose a store to checkout.</p>

      <div v-if="loading" class="cart-loading">Loading your cart…</div>

      <div v-else-if="!items.length" class="cart-empty">
        <q-icon name="o_shopping_cart" size="40px" class="cart-empty-icon" />
        <p class="cart-empty-text">Your cart is empty.</p>
        <q-btn
          unelevated
          no-caps
          label="Browse Products"
          class="browse-btn"
          @click="router.push('/consumer/products')"
        />
      </div>

      <div v-else class="cart-layout">

        <div class="cart-main">
          <div
            v-for="group in groupedByStore"
            :key="group.storeId"
            class="store-card"
            :class="{ 'store-card-selected': selectedStoreId === group.storeId }"
          >

            <div class="store-card-header">
              <div class="store-card-header-info">
                <q-checkbox
                  :model-value="selectedStoreId === group.storeId"
                  :disable="!!selectedGroup && selectedStoreId !== group.storeId"
                  color="primary"
                  dense
                  class="store-card-checkbox"
                  @update:model-value="toggleStoreSelection(group.storeId)"
                />
                <q-icon name="o_storefront" size="15px" />
                <span>{{ group.store }}</span>
              </div>
            </div>

            <div v-for="item in group.items" class="cart-item" :key="item.cartId" :class="{ 'cart-item-oos': !item.inStock }">
              <div class="cart-item-image">
                <img v-if="item.image" :src="item.image" :alt="item.name" />
                <q-icon v-else name="o_inventory_2" size="22px" />
              </div>

              <div class="cart-item-info">
                <div class="cart-item-name">{{ item.name }}</div>
                <div v-if="!item.inStock" class="cart-item-oos-tag">Out of Stock</div>
                <div class="cart-item-price">₱{{ item.price.toFixed(2) }}</div>
              </div>

              <div class="quantity-stepper">
                <button
                  type="button"
                  class="stepper-btn"
                  :disabled="item.quantity <= 1"
                  @click="changeQuantity(item, item.quantity - 1)"
                >
                  <q-icon name="o_remove" size="14px" />
                </button>
                <span class="stepper-value">{{ item.quantity }}</span>
                <button
                  type="button"
                  class="stepper-btn"
                  :disabled="item.quantity >= item.availableQuantity"
                  @click="changeQuantity(item, item.quantity + 1)"
                >
                  <q-icon name="o_add" size="14px" />
                </button>
              </div>

              <div class="cart-item-line-total">₱{{ (item.price * item.quantity).toFixed(2) }}</div>

              <button type="button" class="remove-btn" aria-label="Remove item" @click="removeItem(item)">
                <q-icon name="o_delete" size="18px" />
              </button>
            </div>

            <div class="store-card-subtotal">
              <span>Subtotal</span>
              <strong>₱{{ group.subtotal.toFixed(2) }}</strong>
            </div>
          </div>
        </div>

        <aside class="cart-summary">
          <div class="summary-title">Order Summary</div>

          <p v-if="!selectedGroup" class="summary-empty-hint">Select a store from your cart to continue to checkout.</p>

          <template v-else>
            <div class="summary-row">
              <span>{{ selectedGroup.store }} ({{ selectedItemCount }})</span>
              <span>₱{{ selectedGroup.subtotal.toFixed(2) }}</span>
            </div>
            <q-separator class="summary-separator" />
            <div class="summary-row summary-total">
              <span>Total</span>
              <span>₱{{ selectedGroup.subtotal.toFixed(2) }}</span>
            </div>
          </template>

          <q-btn
            unelevated
            no-caps
            label="Proceed to Checkout"
            class="checkout-btn"
            :disable="!selectedGroup"
            @click="router.push({ path: '/consumer/checkout', query: { storeId: selectedGroup.storeId } })"
          />
          <p class="summary-pickup-note">You'll pay and pick up your order at the store.</p>
        </aside>

      </div>

    </div>

    <SiteFooter />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import { useCart } from '@/composables/useCart'

const $q = useQuasar()
const router = useRouter()

const { items, loading, fetchCart, updateQuantity, removeFromCart } = useCart()

onMounted(fetchCart)

// Only one store can be checked out from at a time — the checkout flow is per-store pickup, not a combined order.
const selectedStoreId = ref(null)

const toggleStoreSelection = (storeId) => {
  selectedStoreId.value = selectedStoreId.value === storeId ? null : storeId
}

// Grouped client-side, same "fetch flat, derive in the frontend" convention used elsewhere.
const groupedByStore = computed(() => {
  const groups = new Map()

  for (const item of items.value) {
    if (!groups.has(item.storeId)) {
      groups.set(item.storeId, { storeId: item.storeId, store: item.store, items: [], subtotal: 0 })
    }
    const group = groups.get(item.storeId)
    group.items.push(item)
    group.subtotal += item.price * item.quantity
  }

  return Array.from(groups.values())
})

// Drives the Order Summary — null whenever nothing is selected or the selected store's items are gone.
const selectedGroup = computed(() =>
  groupedByStore.value.find((group) => group.storeId === selectedStoreId.value) || null
)

const selectedItemCount = computed(() =>
  selectedGroup.value ? selectedGroup.value.items.reduce((sum, item) => sum + item.quantity, 0) : 0
)

const changeQuantity = async (item, newQuantity) => {
  if (newQuantity < 1 || newQuantity > item.availableQuantity) return
  try {
    await updateQuantity(item.cartId, newQuantity)
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to update quantity.' })
  }
}

const removeItem = async (item) => {
  try {
    await removeFromCart(item.cartId)
    $q.notify({ type: 'positive', message: `${item.name} removed from cart.` })
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to remove item.' })
  }
}
</script>

<style scoped>
.storefront-page {
  min-height: 100vh;

  display: flex;
  flex-direction: column;

  background: #ffffff;

  font-family: 'Roboto', Arial, sans-serif;
}

.page-content {
  flex: 1;

  width: 100%;
  max-width: 1000px;
  box-sizing: border-box;

  margin: 0 auto;

  padding: 24px;
}

.page-title {
  margin: 0 0 4px;

  font-size: 22px;
  font-weight: 700;
  line-height: 1.3;

  color: #111111;
}

.page-subtitle {
  margin: 0 0 20px;

  font-size: 13px;

  color: #767676;
}

/* LOADING / EMPTY */

.cart-loading {
  padding: 60px 0;

  text-align: center;

  color: #8992a2;

  font-size: 14px;
}

.cart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;

  padding: 60px 24px;

  text-align: center;
}

.cart-empty-icon {
  margin-bottom: 10px;

  color: #d8dce3;
}

.cart-empty-text {
  margin: 0 0 20px;

  font-size: 14px;

  color: #8992a2;
}

.browse-btn {
  height: 48px;
  padding: 0 24px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13px;
  font-weight: 500;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.browse-btn:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.browse-btn:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.browse-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

/* LAYOUT */

.cart-layout {
  display: grid;
  grid-template-columns: 1fr 280px;

  gap: 20px;

  align-items: start;
}

/* STORE CARDS — each store is its own card, matching the app's canonical card recipe. */

.store-card {
  margin-bottom: 14px;
  padding: 14px 16px;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);

  transition: border-color 0.15s, background-color 0.15s;
}

.store-card:last-child {
  margin-bottom: 0;
}

.store-card-selected {
  border-color: #f3c6c7;
  background: #fdf6f6;
}

.store-card-checkbox {
  margin: -6px 0;
}

.store-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 8px;
  padding-bottom: 8px;

  border-bottom: 1px solid #f4f4f4;
}

.store-card-header-info {
  display: flex;
  align-items: center;

  gap: 6px;

  font-size: 13.5px;
  font-weight: 700;

  color: #333333;
}

.store-card-header-info .q-icon {
  color: #bd2427;
}

.cart-item {
  display: grid;
  grid-template-columns: 46px 1fr auto auto auto;
  align-items: center;

  gap: 6px;
  padding: 8px 0;
}

.cart-item + .cart-item {
  border-top: 1px solid #f8f8f8;
}

.cart-item-oos {
  opacity: 0.6;
}

.cart-item-image {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 46px;
  height: 46px;

  border-radius: 8px;
  border: 1px solid #e8e8e8;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  overflow: hidden;
}

.cart-item-image img {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.cart-item-info {
  min-width: 0;
}

.cart-item-name {
  font-size: 13.5px;
  font-weight: 600;
  line-height: 1.35;

  color: #222222;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.cart-item-oos-tag {
  margin-top: 2px;

  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;

  color: #b91c1c;
}

.cart-item-price {
  margin-top: 2px;

  font-size: 12.5px;

  color: #8992a2;
}

.quantity-stepper {
  display: flex;
  align-items: center;

  border: 1px solid #d6d6da;
  border-radius: 8px;

  overflow: hidden;
}

.stepper-btn {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 32px;
  height: 32px;

  border: none;
  background: #ffffff;
  color: #333333;

  cursor: pointer;

  transition: background-color 0.15s;
}

.stepper-btn:hover:not(:disabled) {
  background: #fdecec;
  color: #bd2427;
}

.stepper-btn:disabled {
  color: #cccccc;
  cursor: default;
}

.stepper-value {
  min-width: 26px;

  text-align: center;

  font-size: 13px;
  font-weight: 600;

  color: #222222;
}

.cart-item-line-total {
  min-width: 70px;

  text-align: right;

  font-size: 13.5px;
  font-weight: 700;

  color: #bd2427;
}

.remove-btn {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 30px;
  height: 30px;

  border: none;
  border-radius: 6px;

  background: transparent;
  color: #9ca3af;

  cursor: pointer;

  transition: background-color 0.15s, color 0.15s;
}

.remove-btn:hover {
  background: #fdecec;
  color: #bd2427;
}

.store-card-subtotal {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-top: 6px;
  padding-top: 10px;

  border-top: 1px solid #f4f4f4;

  font-size: 12.5px;

  color: #666666;
}

.store-card-subtotal strong {
  font-size: 14px;
  font-weight: 700;

  color: #111111;
}

/* SUMMARY */

.cart-summary {
  position: sticky;
  top: 88px;

  padding: 18px;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.summary-title {
  margin-bottom: 12px;

  font-size: 14px;
  font-weight: 700;

  color: #111111;
}

.summary-empty-hint {
  margin: 0;

  font-size: 12.5px;
  line-height: 1.5;

  color: #8992a2;
}

.summary-row {
  display: flex;
  justify-content: space-between;

  margin-bottom: 8px;

  font-size: 13px;

  color: #555555;
}

.summary-separator {
  margin: 10px 0;
}

.summary-total {
  font-size: 15px;
  font-weight: 700;

  color: #111111;
}

.checkout-btn {
  width: 100%;
  height: 48px;
  margin-top: 14px;

  border-radius: 6px;

  background: #bd2427;
  color: #ffffff;

  font-size: 13.5px;
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.checkout-btn:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.checkout-btn:active {
  background: #8f1a1c;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.checkout-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.checkout-btn:disabled {
  background: #bd2427;
  opacity: 0.45;
}

.summary-pickup-note {
  margin: 10px 0 0;

  font-size: 11px;
  line-height: 1.4;
  text-align: center;

  color: #8992a2;
}

/* RESPONSIVE */

@media (max-width: 800px) {
  .cart-layout {
    grid-template-columns: 1fr;
  }

  .cart-summary {
    position: static;
  }
}

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }

  .store-card {
    padding: 12px 14px;
  }

  .cart-item {
    display: flex;
    flex-wrap: wrap;
    align-items: center;

    gap: 10px;
  }

  .cart-item-image {
    width: 44px;
    height: 44px;
  }

  .cart-item-info {
    flex: 1 1 auto;
    min-width: 120px;
  }
}
</style>
