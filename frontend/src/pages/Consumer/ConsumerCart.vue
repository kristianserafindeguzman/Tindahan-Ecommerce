<template>
  <q-page class="storefront-page" :style="showCheckoutBar ? { paddingBottom: checkoutBarHeight + 'px' } : null">

    <SiteHeader />

    <!-- MAIN CONTENT -->
    <div class="page-content">

      <h1 class="page-title">My Cart</h1>
      <p v-if="items.length" class="page-subtitle">Choose a store to checkout.</p>

      <div v-if="loading" class="cart-loading">
        <q-spinner size="32px" />
        <p class="cart-loading-text">Loading your cart…</p>
      </div>

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

      <template v-else>
      <div class="cart-layout">

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

              <div class="stepper-wrapper">
                <div class="quantity-stepper">
                  <!-- Named per item: a screen reader hitting six identical "Decrease"
                       buttons in a cart cannot tell which row it is on. -->
                  <q-btn
                    flat
                    dense
                    :ripple="false"
                    icon="o_remove"
                    class="stepper-btn"
                    :disable="item.quantity <= 1"
                    :aria-label="`Decrease quantity of ${item.name}`"
                    @click="changeQuantity(item, item.quantity - 1)"
                  />
                  <span class="stepper-value">{{ item.quantity }}</span>
                  <q-btn
                    flat
                    dense
                    :ripple="false"
                    icon="o_add"
                    class="stepper-btn"
                    :disable="item.quantity >= item.availableQuantity"
                    :aria-label="`Increase quantity of ${item.name}`"
                    @click="changeQuantity(item, item.quantity + 1)"
                  />
                </div>
                <div v-if="item.quantity >= item.availableQuantity" class="stepper-limit">
                  Max ({{ item.availableQuantity }} limit)
                </div>
              </div>

              <div class="cart-item-line-total">₱{{ (item.price * item.quantity).toFixed(2) }}</div>

              <q-btn
                flat
                dense
                :ripple="false"
                icon="o_delete"
                class="remove-btn"
                :aria-label="`Remove ${item.name} from cart`"
                @click="removeItem(item)"
              />
            </div>

            <div class="store-card-subtotal">
              <span>Subtotal</span>
              <strong>₱{{ group.subtotal.toFixed(2) }}</strong>
            </div>
          </div>
        </div>

        <aside v-if="!$q.screen.lt.md" class="cart-summary">
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

      <!-- Mobile/tablet: sticky checkout bar replaces the Order Summary sidebar. -->
      <div v-if="showCheckoutBar" ref="checkoutBarEl" class="cart-checkout-bar">
        <div class="cart-checkout-bar-top">
          <div class="cart-checkout-bar-info">
            <div class="cart-checkout-bar-title">{{ selectedGroup ? 'Total' : 'Select a store to checkout' }}</div>
            <div class="cart-checkout-bar-subtitle">
              <template v-if="selectedGroup">{{ selectedItemCount }} item{{ selectedItemCount === 1 ? '' : 's' }} · {{ selectedGroup.store }}</template>
              <template v-else>Choose a store above to view your total.</template>
            </div>
          </div>
          <div class="cart-checkout-bar-price">₱{{ (selectedGroup ? selectedGroup.subtotal : 0).toFixed(2) }}</div>
        </div>

        <q-btn
          unelevated
          no-caps
          label="Proceed to Checkout"
          class="checkout-btn"
          :disable="!selectedGroup"
          @click="router.push({ path: '/consumer/checkout', query: { storeId: selectedGroup.storeId } })"
        />
      </div>
      </template>

    </div>

    <SiteFooter />

  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import SiteHeader from '@/components/consumer/SiteHeader.vue'
import SiteFooter from '@/components/consumer/SiteFooter.vue'
import { useCart } from '@/composables/useCart'

const $q = useQuasar()
const router = useRouter()

const { items, loading, fetchCart, updateQuantity, removeFromCart } = useCart()

onMounted(fetchCart)

// Mobile/tablet: sticky checkout bar replaces the Order Summary sidebar.
const showCheckoutBar = computed(() => $q.screen.lt.md && items.value.length > 0)

// Reserves exactly the bar's measured height (not a guessed px value) as bottom padding, so SiteFooter sits flush against it with no gap or overlap.
const checkoutBarEl = ref(null)
const checkoutBarHeight = ref(0)
let checkoutBarObserver = null

watch(checkoutBarEl, (el) => {
  checkoutBarObserver?.disconnect()
  checkoutBarObserver = null

  if (!el) {
    checkoutBarHeight.value = 0
    return
  }

  checkoutBarHeight.value = el.offsetHeight
  checkoutBarObserver = new ResizeObserver(() => {
    checkoutBarHeight.value = el.offsetHeight
  })
  checkoutBarObserver.observe(el)
})

onBeforeUnmount(() => checkoutBarObserver?.disconnect())

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

  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.page-subtitle {
  margin: 0 0 20px;

  font-size: var(--fs-sm);

  color: var(--c-subtle);
}

/* LOADING / EMPTY */

.cart-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;

  gap: 16px;
  padding: 60px 0;

  color: var(--c-brand);
}

.cart-loading-text {
  margin: 0;

  color: var(--c-muted);

  font-size: var(--fs-md);
}

.cart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;

  padding: 60px 24px;

  text-align: center;

  animation: cart-fade-up 0.5s ease both;
}

/* PAGE ENTRANCE — page load only (fresh DOM each navigation), opacity/transform only so it never shifts layout. */
@keyframes cart-fade-up {
  from { opacity: 0; transform: translateY(14px); }
  to { opacity: 1; transform: translateY(0); }
}

.page-title,
.cart-layout {
  animation: cart-fade-up 0.5s ease both;
}

.cart-layout { animation-delay: 0.06s; }

@media (prefers-reduced-motion: reduce) {
  .cart-empty,
  .page-title,
  .cart-layout {
    animation: none;
  }
}

.cart-empty-icon {
  margin-bottom: 10px;

  color: var(--c-border);
}

.cart-empty-text {
  margin: 0 0 20px;

  font-size: var(--fs-md);

  color: var(--c-muted);
}

.browse-btn {
  height: 48px;
  padding: 0 24px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 500;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.browse-btn:hover {
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.browse-btn:active {
  background: var(--c-brand-active);

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

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);

  transition: border-color 0.15s, background-color 0.15s;
}

.store-card:last-child {
  margin-bottom: 0;
}

.store-card-selected {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);
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

  border-bottom: 1px solid var(--c-hairline);
}

.store-card-header-info {
  display: flex;
  align-items: center;

  gap: 6px;

  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text-2);
}

.store-card-header-info .q-icon {
  color: var(--c-brand);
}

.cart-item {
  display: grid;
  grid-template-columns: 46px 1fr auto auto auto;
  align-items: center;

  gap: 6px;
  padding: 8px 0;
}

.cart-item + .cart-item {
  border-top: 1px solid var(--c-surface);
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

  border-radius: var(--r-md);
  border: 1px solid var(--c-border);

  background: linear-gradient(145deg, var(--c-surface) 0%, var(--c-surface) 100%);
  color: var(--c-brand);

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
  font-size: var(--fs-md);
  font-weight: 600;
  line-height: 1.35;

  color: var(--c-text);

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.cart-item-oos-tag {
  margin-top: 2px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  text-transform: uppercase;

  color: var(--c-danger);
}

.cart-item-price {
  margin-top: 2px;

  font-size: var(--fs-sm);

  color: var(--c-muted);
}

.quantity-stepper {
  display: flex;
  align-items: center;

  border: 1px solid var(--c-border-strong);
  border-radius: var(--r-md);

  overflow: hidden;
}

/* QBtn ships its own min-width, padding and border-radius; these pin it back to the
   32px square the stepper strip is built around. */
.stepper-btn {
  width: 32px;
  height: 32px;
  min-width: 32px;
  min-height: 32px;
  padding: 0;

  border-radius: 0;

  background: #ffffff;
  color: var(--c-text-2);
}

.stepper-btn :deep(.q-icon) {
  font-size: 14px;
}

.stepper-btn:hover:not(.disabled) {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

/* QBtn marks a disabled button with a plain .disabled class (not :disabled, and not
   .q-btn--disable), and dims the whole thing to 0.7 opacity. This restores the greyed
   glyph the strip used instead, so the button reads disabled without the wash. */
.stepper-btn.disabled {
  color: var(--c-border-strong);
  opacity: 1 !important;
}

.stepper-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stepper-limit {
  max-width: 90px;
  margin-top: 4px;

  font-size: var(--fs-2xs);
  line-height: 1.1;
  text-align: center;

  color: var(--c-danger);
}

.stepper-value {
  min-width: 26px;

  text-align: center;

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text);
}

.cart-item-line-total {
  min-width: 70px;

  text-align: right;

  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-brand);
}

.remove-btn {
  width: 30px;
  height: 30px;
  min-width: 30px;
  min-height: 30px;
  padding: 0;

  border-radius: var(--r-sm);

  color: var(--c-muted);
}

.remove-btn :deep(.q-icon) {
  font-size: 18px;
}

.remove-btn:hover {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

.store-card-subtotal {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-top: 6px;
  padding-top: 10px;

  border-top: 1px solid var(--c-hairline);

  font-size: var(--fs-sm);

  color: var(--c-muted);
}

.store-card-subtotal strong {
  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

/* SUMMARY */

.cart-summary {
  position: sticky;
  top: 88px;

  padding: 18px;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.summary-title {
  margin-bottom: 12px;

  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.summary-empty-hint {
  margin: 0;

  font-size: var(--fs-sm);
  line-height: 1.5;

  color: var(--c-muted);
}

.summary-row {
  display: flex;
  justify-content: space-between;

  margin-bottom: 8px;

  font-size: var(--fs-sm);

  color: var(--c-text-3);
}

.summary-separator {
  margin: 10px 0;
}

.summary-total {
  font-size: var(--fs-lg);
  font-weight: 700;

  color: var(--c-text);
}

.checkout-btn {
  width: 100%;
  height: 48px;
  margin-top: 14px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-md);
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.checkout-btn:hover {
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.checkout-btn:active {
  background: var(--c-brand-active);

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.checkout-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.checkout-btn:disabled {
  background: var(--c-brand);
  opacity: 0.45;
}

.summary-pickup-note {
  margin: 10px 0 0;

  font-size: var(--fs-2xs);
  line-height: 1.4;
  text-align: center;

  color: var(--c-muted);
}

/* MOBILE STICKY CHECKOUT BAR — replaces the Order Summary sidebar below 600px. */

.cart-checkout-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 100;

  /* Same padding/border/shadow recipe as .order-actions-fixed (ConsumerOrderDetails.vue), this app's one fixed-bottom-bar convention. */
  padding: 12px 16px calc(12px + env(safe-area-inset-bottom, 0px));

  background: #ffffff;
  border-top: 1px solid var(--c-hairline);
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.06);
}

.cart-checkout-bar-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.cart-checkout-bar-info {
  min-width: 0;
}

.cart-checkout-bar-title {
  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.cart-checkout-bar-subtitle {
  margin-top: 2px;

  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-muted);
}

.cart-checkout-bar-price {
  flex-shrink: 0;

  /* Matches .order-ref-total-amount / .receipt-total-amount — this app's one "grand total" size. */
  font-size: var(--fs-xl);
  font-weight: 700;

  color: var(--c-brand);
}

/* .checkout-btn already carries margin-top: 14px, so no override is needed here. */

/* RESPONSIVE */

@media (max-width: 800px) {
  .cart-layout {
    grid-template-columns: 1fr;
  }
}

/* max-width: 1023px (not 1024px) matches $q.screen.lt.md exactly, since Quasar's md tier starts AT 1024px. */
@media (max-width: 1023px) {
  .cart-item {
    display: grid;
    grid-template-columns: 44px 1fr auto auto;
    align-items: center;

    gap: 10px;
  }

  .cart-item-image {
    width: 44px;
    height: 44px;
  }

  .cart-item-info {
    min-width: 0;
  }

  .cart-item-price {
    font-weight: 700;
    color: var(--c-brand);
  }

  /* Redundant next to the per-unit price now shown in red, so the line-item math stays desktop-only. */
  .cart-item-line-total {
    display: none;
  }

  /* Hidden on mobile/tablet — the sticky checkout bar is the page's one focal action, footer links just add extra scroll past it. */
  :deep(.site-footer) {
    display: none;
  }
}

@media (max-width: 600px) {
  .page-content {
    padding: 16px;
  }

  .store-card {
    padding: 12px 14px;
  }
}
</style>



