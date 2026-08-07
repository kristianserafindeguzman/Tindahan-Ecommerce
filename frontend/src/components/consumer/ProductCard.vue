<template>
  <q-card flat class="product-card">
    <div class="product-card-image">
      <q-icon name="inventory_2" size="32px" />
      <q-btn
        round
        unelevated
        dense
        icon="add"
        aria-label="Add to cart"
        class="product-add-btn"
        @click="$emit('add-to-cart', product)"
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
        <q-icon name="location_on" size="13px" class="product-meta-icon" />
        <span class="product-meta-text">{{ product.store }}</span>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { computed } from 'vue'
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

defineEmits(['add-to-cart'])

const nameParts = computed(() => splitHighlightParts(props.product.name, props.highlightQuery))
</script>

<style scoped>
.product-card {
  overflow: hidden;

  border-radius: 8px;
  border: 1px solid #f0f0f0;

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

  height: 110px;
  overflow: hidden;

  background: linear-gradient(145deg, #f7f7f8 0%, #ececee 100%);
  color: #bd2427;

  transition: background 0.2s;
}

.product-card:hover .product-card-image {
  background: linear-gradient(145deg, #fdecec 0%, #fbdbdc 100%);
}

.product-card-body {
  padding: 12px;
}

.product-add-btn {
  position: absolute;
  right: 6px;
  bottom: 6px;

  width: 26px;
  height: 26px;
  min-width: 26px;
  min-height: 26px;
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
  transform: scale(1.08);
}

.product-price {
  font-size: 15px;
  font-weight: 700;
  line-height: 1.3;

  color: #111111;

  margin-bottom: 4px;
}

.product-name {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.35;

  color: #333333;

  margin-bottom: 8px;

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

  gap: 4px;
  min-width: 0;

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
