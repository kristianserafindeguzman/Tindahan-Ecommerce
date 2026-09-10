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
      <div class="product-name">
        <template v-for="(part, i) in nameParts" :key="i">
          <mark v-if="part.match" class="highlight-mark">{{ part.text }}</mark>
          <template v-else>{{ part.text }}</template>
        </template>
      </div>
      <div class="product-price">₱{{ product.price.toFixed(2) }}</div>
      <div v-if="productMetaText" class="product-meta">
        <q-icon name="o_storefront" size="13px" class="product-meta-icon" />
        <span class="product-meta-text">{{ productMetaText }}</span>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { computed, ref } from 'vue'
import { splitHighlightParts } from '@/utils/textHighlight'
import { formatDistance } from '@/utils/distance'

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

const productMetaText = computed(() => {
  const parts = []
  if (props.product.distance_meters != null) parts.push(formatDistance(props.product.distance_meters))
  if (props.product.store) parts.push(props.product.store)
  return parts.join(' • ')
})

</script>

<style scoped>
.product-card {
  overflow: hidden;

  font-family: 'Roboto', Arial, sans-serif;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;

  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);

  transition: box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.2s;

  cursor: pointer;

}

.product-card:hover {
  border-color: var(--c-brand-tint-3);

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

  background: linear-gradient(145deg, var(--c-surface) 0%, var(--c-surface) 100%);
  color: var(--c-brand);

  transition: background 0.2s;
}

.product-card:hover .product-card-image {
  background: linear-gradient(145deg, var(--c-brand-tint) 0%, var(--c-brand-tint-2) 100%);
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

  border-radius: var(--r-pill);

  background: rgba(255, 255, 255, 0.92);
  color: var(--c-text-2);

  font-size: var(--fs-2xs);
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

  border-radius: var(--r-sm);

  background: rgba(17, 17, 17, 0.78);
  color: #ffffff;

  font-size: var(--fs-sm);
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

  background: var(--c-brand);
  color: #ffffff;

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.35);

  transition: background-color 0.15s, transform 0.15s;
}

.product-add-btn :deep(.q-icon) {
  font-size: 16px;
}

.product-add-btn:hover {
  background: var(--c-brand-deep);
  transform: scale(1.1);
}

.product-price {
  font-size: var(--fs-xl);
  font-weight: 700;
  line-height: 1.3;

  color: var(--c-brand);

  margin-bottom: 10px;
}

.product-name {
  font-size: var(--fs-lg);
  font-weight: 500;
  line-height: 1.35;

  color: var(--c-text-2);

  margin-bottom: 6px;

  min-height: calc(1.35em * 2);

  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.highlight-mark {
  background: var(--c-brand-tint);
  color: var(--c-brand-deep);
  font-weight: 700;

  border-radius: var(--r-xs);
}

.product-meta {
  display: flex;
  align-items: center;

  gap: 5px;
  min-width: 0;
  padding-top: 10px;

  border-top: 1px solid var(--c-hairline);

  font-size: var(--fs-sm);
  line-height: 1.3;

  color: var(--c-muted);
}

.product-meta-icon {
  flex-shrink: 0;
}

.product-meta-text {
  min-width: 0;

  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

/* Phones fit two cards to a 390px row, so this trims the card's chrome rather than its content. */
@media (max-width: 600px) {
  /* 5:4 rather than the desktop 1:1 trims about 34px per card without cropping tall bottles and packets the way 4:3 did. */
  .product-card-image {
    aspect-ratio: 5 / 4;
  }

  .product-card-body {
    padding: 10px;
  }

  /* Drops the two-line name reserve, which aligns prices across a desktop row but only adds dead space on a two-up phone row. */
  .product-name {
    min-height: 0;

    margin-bottom: 4px;
  }

  .product-price {
    margin-bottom: 8px;
  }

  .product-category-tag {
    top: 6px;
    left: 6px;

    padding: 2px 6px;

    letter-spacing: 0.02em;
  }

  .product-meta {
    gap: 4px;
    padding-top: 8px;
  }

  .product-add-btn {
    right: 6px;
    bottom: 6px;
  }
}
</style>
