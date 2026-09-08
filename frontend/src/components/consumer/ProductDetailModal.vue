<template>
  <q-dialog v-model="isOpen" :position="isSheet ? 'bottom' : 'standard'" @hide="resetLocalState">
    <q-card v-if="product" flat class="detail-card" :class="{ 'detail-card-sheet': isSheet }">

      <div v-if="isSheet" class="detail-drag-handle" />

      <q-btn icon="o_close" flat round dense class="close-btn" v-close-popup />

      <div class="detail-scroll">

        <div class="detail-grid">

          <div class="detail-image-wrap">
            <img
              v-if="product.image && !imageFailed"
              :src="product.image"
              :alt="product.name"
              class="detail-image"
              @error="imageFailed = true"
            />
            <q-icon v-else name="o_inventory_2" size="48px" />

            <span v-if="product.category" class="detail-category-tag">{{ product.category }}</span>
            <span v-if="!product.inStock" class="detail-oos-tag">Out of Stock</span>
          </div>

          <div class="detail-info">
            <h2 class="detail-name">{{ product.name }}</h2>

            <div class="price-stock-row">
              <div class="detail-price">₱{{ displayPrice.toFixed(2) }}</div>

              <div class="detail-stock" :class="{ 'detail-stock-oos': !product.inStock }">
                {{ product.inStock ? 'In Stock' : 'Out of Stock' }}
              </div>
            </div>

            <p v-if="product.description" class="detail-description">{{ product.description }}</p>

            <!-- VARIANTS -->
            <div v-if="hasVariants" class="variants-section">
              <div class="variants-label">Available Sizes</div>
              <div class="variants-list">
                <q-btn
                  v-for="(variant, i) in product.variants"
                  :key="i"
                  flat
                  dense
                  no-caps
                  :ripple="false"
                  class="variant-chip"
                  :class="{
                    'variant-chip-oos': !variant.quantity,
                    'variant-chip-selected': selectedVariantIndex === i
                  }"
                  :disable="!variant.quantity"
                  :label="variant.size || variant.name"
                  :aria-pressed="selectedVariantIndex === i"
                  @click="selectVariant(i)"
                />
              </div>
            </div>

            <!-- Desktop: in-flow with the rest of the product info. -->
            <div v-if="!isSheet" class="cart-action-row">
              <div v-if="product.inStock" class="quantity-row">
                <span class="quantity-label">Quantity</span>
                <div class="stepper-wrapper">
                  <div class="quantity-stepper">
                    <q-btn flat dense :ripple="false" icon="o_remove" class="stepper-btn" :disable="quantity <= 1" aria-label="Decrease quantity" @click="quantity--" />
                    <span class="stepper-value">{{ quantity }}</span>
                    <q-btn flat dense :ripple="false" icon="o_add" class="stepper-btn" :disable="quantity >= maxQuantity" aria-label="Increase quantity" @click="quantity++" />
                  </div>
                </div>
              </div>

              <q-btn
                unelevated
                no-caps
                icon="o_shopping_cart"
                :disable="!product.inStock"
                :loading="adding"
                label="Add to Cart"
                class="add-to-cart-btn"
                @click="handleAddToCart"
              />
            </div>
          </div>

        </div>

        <!-- STORE INFO -->
        <div v-if="store" class="store-section">
          <div class="store-section-label">About this Store</div>

          <div class="store-row" v-close-popup @click="router.push(`/consumer/stores/${store.slug || store.id}`)">
            <div class="store-row-info">
              <div class="store-row-name">{{ store.name }}</div>
              <div class="store-row-status" :class="{ 'store-row-status-closed': !store.isOpen }">
                {{ store.scheduleStatusText || (store.isOpen ? `Open until ${store.closesAt}` : 'Closed now') }}
              </div>
              <div v-if="storeAddressText" class="store-row-address">
                <q-icon name="o_location_on" size="12px" />
                <span class="store-row-address-text">{{ storeAddressText }}</span>
              </div>
            </div>
            <div class="store-row-link">
              View Store
              <q-icon name="o_chevron_right" size="16px" />
            </div>
          </div>
        </div>

        <!-- Mobile: sticky at the bottom of the sheet's own scroll viewport, so content scrolls underneath it instead of pushing it away. -->
        <div v-if="isSheet" class="cart-action-row cart-action-row-sheet">
          <div v-if="product.inStock" class="quantity-row">
            <div class="stepper-wrapper">
              <div class="quantity-stepper">
                <q-btn flat dense :ripple="false" icon="o_remove" class="stepper-btn" :disable="quantity <= 1" aria-label="Decrease quantity" @click="quantity--" />
                <span class="stepper-value">{{ quantity }}</span>
                <q-btn flat dense :ripple="false" icon="o_add" class="stepper-btn" :disable="quantity >= maxQuantity" aria-label="Increase quantity" @click="quantity++" />
              </div>
            </div>
          </div>

          <q-btn
            unelevated
            no-caps
            icon="o_shopping_cart"
            :disable="!product.inStock"
            :loading="adding"
            label="Add to Cart"
            class="add-to-cart-btn"
            @click="handleAddToCart"
          />
        </div>

      </div>

    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useRouter } from 'vue-router'
import { useStores } from '@/composables/useStores'
import { formatDistance } from '@/utils/distance'
import { useCart } from '@/composables/useCart'

const props = defineProps({
  modelValue: Boolean,
  product: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

const $q = useQuasar()
const router = useRouter()
const { stores, fetchStores } = useStores()
const { addToCart } = useCart()
const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

// Mobile only — tablet keeps the centered dialog.
const isSheet = computed(() => $q.screen.lt.sm)

const isOpen = ref(props.modelValue)
watch(() => props.modelValue, (val) => {
  isOpen.value = val
  if (val && !stores.value.length) fetchStores()
})
watch(isOpen, (val) => emit('update:modelValue', val))

const store = computed(() =>
  props.product ? stores.value.find((s) => s.id === props.product.storeId) || null : null
)

const storeAddressText = computed(() => {
  if (!store.value) return ''
  const dist = store.value.distance_meters != null ? formatDistance(store.value.distance_meters) : ''
  if (store.value.address && dist) return `${store.value.address} (${dist})`
  return store.value.address || dist
})

const hasVariants = computed(() => Array.isArray(props.product?.variants) && props.product.variants.length > 0)

const imageFailed = ref(false)
const quantity = ref(1)
const adding = ref(false)
const selectedVariantIndex = ref(null)

const selectedVariant = computed(() =>
  hasVariants.value && selectedVariantIndex.value !== null
    ? props.product.variants[selectedVariantIndex.value]
    : null
)

const displayPrice = computed(() =>
  selectedVariant.value ? Number(selectedVariant.value.price) : props.product.price
)

// ?? not || : a sold-out product reports availableQuantity 0, which || would fall
// through to the 99 meant only for products that omit the field entirely.
const maxQuantity = computed(() =>
  selectedVariant.value ? selectedVariant.value.quantity : (props.product.availableQuantity ?? 99)
)

const selectVariant = (i) => {
  selectedVariantIndex.value = selectedVariantIndex.value === i ? null : i
  quantity.value = 1
}

const resetLocalState = () => {
  imageFailed.value = false
  quantity.value = 1
  selectedVariantIndex.value = null
}

const handleAddToCart = async () => {
  if (!props.product) return

  if (!isLoggedIn.value) {
    isOpen.value = false
    router.push('/login')
    return
  }

  adding.value = true
  try {
    await addToCart(props.product.id, quantity.value)
    $q.notify({ type: 'positive', message: `${props.product.name} added to cart.` })
  } catch (error) {
    $q.notify({ type: 'negative', message: error.response?.data?.message || 'Failed to add to cart.' })
  } finally {
    adding.value = false
  }
}
</script>

<style scoped>
.detail-card {
  position: relative;

  width: 760px;
  max-width: 92vw;

  border-radius: var(--r-xl);

  font-family: 'Roboto', Arial, sans-serif;

  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
}

.close-btn {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 1;

  color: var(--c-muted);

  background: rgba(255, 255, 255, 0.9);

  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
}

.detail-scroll {
  max-height: 85vh;

  overflow-y: auto;

  padding: 28px;
}

/* PRODUCT GRID — same card/image treatment as ProductCard.vue. */

.detail-grid {
  display: grid;
  grid-template-columns: 300px 1fr;

  gap: 28px;
}

.detail-image-wrap {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;

  aspect-ratio: 1 / 1;
  overflow: hidden;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: linear-gradient(145deg, var(--c-surface) 0%, var(--c-surface) 100%);
  color: var(--c-brand);
}

.detail-image {
  width: 100%;
  height: 100%;

  object-fit: cover;
}

.detail-category-tag {
  position: absolute;
  top: 12px;
  left: 12px;

  padding: 4px 10px;

  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.92);
  color: var(--c-text-2);

  font-size: var(--fs-2xs);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;

  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.detail-oos-tag {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);

  padding: 6px 14px;

  border-radius: var(--r-sm);

  background: rgba(17, 17, 17, 0.78);
  color: #ffffff;

  font-size: var(--fs-xs);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

/* INFO COLUMN */

.detail-info {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.detail-name {
  margin: 0 0 14px;

  font-size: var(--fs-3xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-text);
}

.price-stock-row {
  display: flex;
  align-items: center;

  gap: 12px;
  margin-bottom: 18px;
}

.detail-price {
  font-size: var(--fs-4xl);
  font-weight: 700;
  line-height: 1.2;

  color: var(--c-brand);
}

.detail-stock {
  display: inline-flex;

  padding: 4px 10px;

  border-radius: var(--r-pill);

  background: var(--c-success-tint);
  color: var(--c-success);

  font-size: var(--fs-xs);
  font-weight: 700;
}

.detail-stock-oos {
  background: var(--c-danger-tint);
  color: var(--c-danger);
}

.detail-description {
  margin: 0 0 18px;

  font-size: var(--fs-md);
  line-height: 1.5;

  color: var(--c-subtle);
}

/* VARIANTS */

.variants-section {
  margin-bottom: 18px;
}

.variants-label {
  margin-bottom: 8px;

  font-size: var(--fs-2xs);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;

  color: var(--c-muted);
}

.variants-list {
  display: flex;
  flex-wrap: wrap;

  gap: 8px;
}

/* QBtn ships min-width, its own padding and a rectangle radius; these restore the
   pill the chip row is built from. Quasar marks a disabled button with .disabled
   (not :disabled), so the hover guards below key off that. */
.variant-chip {
  padding: 5px 14px;
  min-height: auto;

  border-radius: var(--r-pill);
  border: 1px solid var(--c-brand-tint-3);

  background: var(--c-brand-tint);
  color: var(--c-brand-deep);

  font-family: inherit;
  font-size: var(--fs-sm);
  font-weight: 600;

  cursor: pointer;

  transition: background-color 0.15s, border-color 0.15s, color 0.15s;
}

.variant-chip:hover:not(.disabled) {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint-2);
}

.variant-chip-selected,
.variant-chip-selected:hover:not(.disabled) {
  border-color: var(--c-brand);

  background: var(--c-brand);
  color: #ffffff;
}

.variant-chip-oos {
  border-color: var(--c-border);

  background: var(--c-surface);
  color: var(--c-muted);

  text-decoration: line-through;

  cursor: default;
}

/* QUANTITY */

.quantity-row {
  display: flex;
  align-items: center;

  gap: 16px;
  margin-bottom: 20px;
}

.quantity-label {
  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text-2);
}

.quantity-stepper {
  display: flex;
  align-items: center;

  border: 1px solid var(--c-border-strong);
  border-radius: var(--r-md);

  overflow: hidden;
}

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
  font-size: 16px;
}

.stepper-btn:hover:not(.disabled) {
  background: var(--c-brand-tint);
  color: var(--c-brand);
}

/* Quasar dims a disabled QBtn to 0.7 opacity; the greyed glyph reads better here. */
.stepper-btn.disabled {
  color: var(--c-border-strong);
  opacity: 1 !important;
}

.stepper-value {
  min-width: 34px;

  text-align: center;

  font-size: var(--fs-md);
  font-weight: 600;

  color: var(--c-text);
}

/* ADD TO CART — same brand button treatment as .login-button. */

.add-to-cart-btn {
  width: 100%;
  height: 46px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-sm);
  font-weight: 500;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.add-to-cart-btn:hover {
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.add-to-cart-btn:active {
  background: var(--c-brand-active);

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.add-to-cart-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.add-to-cart-btn:disabled {
  background: var(--c-brand);
  opacity: 0.45;
}

/* STORE INFO SECTION — same border-top + label separation used by ProductCard's .product-meta. */

.store-section {
  margin-top: 22px;
  padding-top: 18px;

  border-top: 1px solid var(--c-hairline);
}

.store-section-label {
  margin-bottom: 10px;

  font-size: var(--fs-sm);
  font-weight: 700;

  color: var(--c-text);
}

.store-row {
  display: flex;
  align-items: center;

  gap: 12px;
  padding: 16px;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  cursor: pointer;

  transition: border-color 0.15s, background-color 0.15s, box-shadow 0.2s, transform 0.15s;
}

.store-row:hover {
  border-color: var(--c-brand-tint-3);
  background: var(--c-brand-tint);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.12);
  transform: translateY(-2px);
}

.store-row-info {
  flex: 1;
  min-width: 0;
}

.store-row-name {
  font-size: var(--fs-md);
  font-weight: 700;

  color: var(--c-text);
}

.store-row-status {
  margin-top: 1px;

  font-size: var(--fs-xs);
  font-weight: 500;

  color: var(--c-success);
}

.store-row-status-closed {
  color: var(--c-brand);
}

.store-row-address {
  display: flex;
  align-items: center;

  gap: 4px;
  min-width: 0;
  margin-top: 3px;

  font-size: var(--fs-xs);

  color: var(--c-muted);
}

.store-row-address .q-icon {
  flex-shrink: 0;
}

.store-row-address-text {
  min-width: 0;

  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

.store-row-link {
  display: flex;
  align-items: center;
  flex-shrink: 0;

  gap: 1px;

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-brand);
}

/* RESPONSIVE — sheet styling is driven by the .detail-card-sheet class (same isSheet check as the dialog's position), not a separate media query, so the two can't disagree at the breakpoint. */

.detail-card-sheet {
  display: flex;
  flex-direction: column;

  width: 100%;
  max-width: 100%;
  max-height: 92vh;

  border-radius: 16px 16px 0 0;
}

.detail-card-sheet .detail-drag-handle {
  position: sticky;
  top: 0;
  z-index: 1;

  width: 36px;
  height: 4px;
  margin: 10px auto 0;

  border-radius: var(--r-pill);

  background: var(--c-border-strong);
}

.detail-card-sheet .close-btn {
  top: 20px;
}

.detail-card-sheet .detail-grid {
  grid-template-columns: 1fr;
}

.detail-card-sheet .detail-scroll {
  flex: 1;
  min-height: 0;
  max-height: none;

  padding: 20px;
}

.cart-action-row-sheet {
  position: sticky;
  bottom: -20px;
  z-index: 2;

  display: flex;
  align-items: center;

  gap: 12px;
  margin: 0 -20px -20px;
  padding: 14px 20px 16px;

  background: #ffffff;
  border-top: 1px solid var(--c-hairline);
}

.cart-action-row-sheet .quantity-row {
  flex-shrink: 0;

  margin-bottom: 0;
}

.cart-action-row-sheet .add-to-cart-btn {
  width: auto;

  flex: 1;
}
</style>


