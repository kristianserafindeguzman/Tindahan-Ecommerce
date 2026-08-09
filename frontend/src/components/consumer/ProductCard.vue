<template>
  <q-card flat class="product-card" :class="{ 'product-card-oos': !product.inStock }" @click="$emit('view-product', product)">
    <div class="product-card-image">
      <img
        v-if="product.image && !imageFailed"
        :src="product.image"
        :alt="product.name"
        class="product-card-img"
        @error="imageFailed = true"
      />
      <q-icon v-else name="o_inventory_2" size="36px" />

      <span v-if="product.category" class="product-category-tag">{{ product.category }}</span>
      <span v-if="!product.inStock" class="product-oos-tag">Out of Stock</span>

      <q-btn
        v-if="product.inStock"
        round
        unelevated
        dense
        icon="o_add"
        aria-label="Add to cart"
        class="product-add-btn"
        @click.stop="$emit('add-to-cart', product)"
      />
    </div>
    <q-card-section class="product-card-body">
      <div class="product-price">₱{{ product.price.toFixed(2) }}</div>
      <div class="product-name">
        <template v-for="(part, i) in nameParts" :key="i">
          <mark v-if="part.match" class="highlight-mark">{{ part.text }}</mark>
          <template v-else>{{ part.text }}</template>
        </template>
      </div>
      <div class="product-meta">
        <q-icon name="o_storefront" size="13px" class="product-meta-icon" />
        <span class="product-meta-text">{{ product.store }}</span>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { computed, ref } from 'vue'
import { splitHighlightParts } from '@/utils/textHighlight'

const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  highlightQuery: {
    type: String,
    default: ''
  }
})

defineEmits(['add-to-cart', 'view-product'])

const nameParts = computed(() => splitHighlightParts(props.product.name, props.highlightQuery))
const imageFailed = ref(false)

const goToProduct = () => {
  router.push(`/consumer/product/${props.product.id}`)
}
</script>

<style scoped>
.product-card {
  overflow: hidden;

  font-family: 'Roboto', Arial, sans-serif;

  border-radius: 10px;
  border: 1px solid #e8e8e8;

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;

  cursor: pointer;
}

.product-card:hover {
  border-color: #f3c6c7;

  box-shadow: 0 10px 24px rgba(189, 36, 39, 0.14);
  transform: translateY(-3px);
}

.product-card-image {
  position: relative;

  display: flex;
  align-items: center;
  justify-content: center;

  aspect-ratio: 1 / 1;
  overflow: hidden;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  transition: background 0.2s;
}

.product-card:hover .product-card-image {
  background: linear-gradient(145deg, #fdecec 0%, #fbdbdc 100%);
}

.product-card-img {
  width: 100%;
  height: 100%;

  object-fit: cover;

  transition: transform 0.3s ease;
}

.product-card:hover .product-card-img {
  transform: scale(1.06);
}

.product-card-oos .product-card-img {
  filter: grayscale(40%);
  opacity: 0.55;
}

.product-category-tag {
  position: absolute;
  top: 8px;
  left: 8px;

  padding: 3px 8px;

  border-radius: 999px;

  background: rgba(255, 255, 255, 0.92);
  color: #4a4a4a;

  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;

  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.product-oos-tag {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);

  padding: 5px 12px;

  border-radius: 6px;

  background: rgba(17, 17, 17, 0.78);
  color: #ffffff;

  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.product-card-body {
  padding: 14px;
}

.product-add-btn {
  position: absolute;
  right: 8px;
  bottom: 8px;

  width: 28px;
  height: 28px;
  min-width: 28px;
  min-height: 28px;
  padding: 0;

  background: #bd2427;
  color: #ffffff;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.35);

  transition: background-color 0.15s, transform 0.15s;
}

.product-add-btn :deep(.q-icon) {
  font-size: 16px;
}

.product-add-btn:hover {
  background: #9c171b;
  transform: scale(1.1);
}

.product-price {
  font-size: 16px;
  font-weight: 700;
  line-height: 1.3;

  color: #bd2427;

  margin-bottom: 6px;
}

.product-name {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.35;

  color: #333333;

  margin-bottom: 12px;

  min-height: calc(1.35em * 2);

  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.highlight-mark {
  background: #fdecec;
  color: #9c171b;
  font-weight: 700;

  border-radius: 2px;
}

.product-meta {
  display: flex;
  align-items: center;

  gap: 5px;
  min-width: 0;
  padding-top: 10px;

  border-top: 1px solid #f4f4f4;

  font-size: 12px;
  line-height: 1.3;

  color: #8992a2;
}

.product-meta-icon {
  flex-shrink: 0;
}

.product-meta-text {
  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
