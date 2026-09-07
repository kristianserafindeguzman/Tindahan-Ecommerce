<template>
  <!--
    Placeholder that occupies the same box as the card it stands in for, so content
    arriving does not shift the grid.

    Geometry is derived from the same type and radius tokens the real cards use
    rather than restated in pixels. The previous version hardcoded heights computed
    from 16px/13px/12.5px type; once the type scale moved those numbers were silently
    wrong, and the skeleton no longer matched the card. calc() off the tokens means
    it cannot drift again.
  -->
  <q-card flat bordered class="cskel">
    <q-skeleton square :animation="anim" :class="['cskel__image', `cskel__image--${variant}`]" />

    <q-card-section class="cskel__body">
      <template v-if="variant === 'product'">
        <!-- name sits above price, matching ProductCard -->
        <div class="cskel__name">
          <q-skeleton type="text" :animation="anim" class="cskel__line" />
          <q-skeleton type="text" :animation="anim" class="cskel__line cskel__line--short" />
        </div>
        <q-skeleton type="text" :animation="anim" class="cskel__price" />
      </template>

      <template v-else>
        <q-skeleton type="text" :animation="anim" class="cskel__storename" />
        <q-skeleton type="text" :animation="anim" class="cskel__hours" />
      </template>

      <div class="cskel__meta">
        <q-skeleton type="text" :animation="anim" class="cskel__metabar" />
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { computed } from 'vue'

defineProps({
  /** 'product' mirrors ProductCard; 'store' mirrors StoreCard (4:3). */
  variant: {
    type: String,
    default: 'product',
    validator: (v) => ['product', 'store'].includes(v)
  }
})

// QSkeleton animates by default and has no reduced-motion handling of its own, so
// the preference is read here and passed as the prop rather than fought in CSS.
const prefersReduced =
  typeof window !== 'undefined' &&
  window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true

const anim = computed(() => (prefersReduced ? 'none' : 'wave'))
</script>

<style scoped>
/* Same shell as ProductCard/StoreCard so the outline does not change on load. */
.cskel {
  overflow: hidden;

  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);

  background: #ffffff;
  box-shadow: var(--sh-card);
}

.cskel__image {
  width: 100%;
  height: auto;
}

.cskel__image--product {
  aspect-ratio: 1 / 1;
}

.cskel__image--store {
  aspect-ratio: 4 / 3;
}

.cskel__body {
  padding: 14px;
}

/* --- product body: mirrors .product-name (2 reserved lines) then .product-price --- */
.cskel__name {
  display: flex;
  flex-direction: column;
  justify-content: space-between;

  height: calc(var(--fs-lg) * 1.35 * 2);
  margin-bottom: 6px;
}

.cskel__line {
  width: 100%;
  height: calc(var(--fs-lg) * 0.72);
}

.cskel__line--short {
  width: 62%;
}

.cskel__price {
  width: 42%;
  height: calc(var(--fs-xl) * 1.3);

  margin-bottom: 10px;
}

/* --- store body: mirrors .store-card-name then .store-card-hours --- */
.cskel__storename {
  width: 72%;
  height: calc(var(--fs-xl) * 1.3);

  margin-bottom: 6px;
}

.cskel__hours {
  width: 52%;
  height: calc(var(--fs-md) * 1.3);

  margin-bottom: 12px;
}

/* --- shared meta row, under its own hairline --- */
.cskel__meta {
  padding-top: 10px;

  border-top: 1px solid var(--c-hairline);
}

.cskel__metabar {
  width: 68%;
  height: calc(var(--fs-xs) * 1.3);
}

/* ProductCard drops to 5:4 and tighter padding on phones; the placeholder has to
   track it or the grid jumps when the real cards arrive. */
@media (max-width: 600px) {
  .cskel__image--product {
    aspect-ratio: 5 / 4;
  }

  .cskel__body {
    padding: 10px;
  }

  .cskel__name {
    height: calc(var(--fs-lg) * 1.35);
    margin-bottom: 4px;
  }

  .cskel__price {
    margin-bottom: 8px;
  }

  .cskel__meta {
    padding-top: 8px;
  }
}
</style>

