<template>
  <div class="product-filters" :class="{ 'product-filters-sheet': isSheet }">
    <div class="filters-panel-header">
      <div class="filters-panel-title-group">
        <span class="filters-panel-title">{{ t('Filters') }}</span>
      </div>
      <q-btn flat dense round :ripple="false" icon="o_close" class="filters-close-btn" :aria-label="t('Close filters')" @click="$emit('close')" />
    </div>

    <div class="filters-scroll">
      <div class="filter-group">
        <q-input
          v-model="search"
          dense
          outlined
          clearable
          hide-bottom-space
          :placeholder="t('Search...')"
          :aria-label="t('Search products and stores')"
        >
          <template #prepend><q-icon name="o_search" /></template>
        </q-input>
      </div>
      <!-- Opt-in: only pages that pass distanceOptions show it, so the Products page is unchanged. -->
      <div v-if="distanceOptions.length" class="filter-group">
        <label class="filter-label">{{ t('Distance') }}</label>
        <q-select
          v-model="maxDistance"
          :options="translateOptions(distanceOptions)"
          :disable="distanceDisabled"
          dense
          outlined
          emit-value
          map-options
          hide-bottom-space
          behavior="menu"
        />
        <p v-if="distanceDisabled" class="filter-hint">{{ t('Set your location to filter by distance.') }}</p>
      </div>

      <div v-if="!hideCategory" class="filter-group">
        <label class="filter-label">{{ t('Categories') }}</label>
        <q-select
          v-model="category"
          :options="translateOptions(categoryOptions)"
          dense
          outlined
          emit-value
          map-options
          hide-bottom-space
          behavior="menu"
        />
      </div>

      <div class="filter-group">
        <label class="filter-label">{{ t('Price Range') }}</label>
        <div class="price-range-row">
          <q-input v-model.number="priceMin" type="number" dense outlined hide-bottom-space :placeholder="t('Min')" />
          <span class="price-range-sep">–</span>
          <q-input v-model.number="priceMax" type="number" dense outlined hide-bottom-space :placeholder="t('Max')" />
        </div>
      </div>

      <div v-if="!hideStore" class="filter-group">
        <label class="filter-label">{{ t('Store') }}</label>
        <q-select
          v-model="store"
          :options="translateOptions(storeOptions, option => option.value === 'All')"
          dense
          outlined
          emit-value
          map-options
          hide-bottom-space
          behavior="menu"
        />
      </div>

      <div v-if="!hideInStock" class="filter-group filter-group-row">
        <label class="filter-label filter-label-inline">{{ t('In Stock Only') }}</label>
        <q-toggle v-model="inStock" dense color="primary" />
      </div>

      <div class="filter-group">
        <label class="filter-label">{{ t('Sort by') }}</label>
        <q-select
          v-model="sort"
          :options="translateOptions(sortOptions)"
          dense
          outlined
          emit-value
          map-options
          hide-bottom-space
          behavior="menu"
        />
      </div>
    </div>

    <div class="filters-footer">
      <q-separator class="filters-divider" />

      <q-btn
        :label="t('Apply Filters')"
        unelevated
        no-caps
        class="apply-filters-btn"
        @click="$emit('close')"
      />

      <div class="clear-filters-row">
        <button type="button" class="clear-filters-link" @click="$emit('clear')">{{ t('Clear all filters') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useConsumerLanguage } from '@/composables/useConsumerLanguage'

const { t, translateOptions } = useConsumerLanguage()

defineProps({
  categoryOptions: {
    type: Array,
    default: () => []
  },
  // Options in metres; empty hides the Distance filter entirely.
  distanceOptions: {
    type: Array,
    default: () => []
  },
  // No location set means no distances to compare against.
  distanceDisabled: {
    type: Boolean,
    default: false
  },
  hideCategory: {
    type: Boolean,
    default: false
  },
  hideInStock: {
    type: Boolean,
    default: false
  },
  storeOptions: {
    type: Array,
    default: () => []
  },
  sortOptions: {
    type: Array,
    required: true
  },
  hideStore: {
    type: Boolean,
    default: false
  },
  isSheet: {
    type: Boolean,
    default: false
  }
})

defineEmits(['close', 'clear'])

const search = defineModel('search', { default: '' })
const maxDistance = defineModel('maxDistance')
const category = defineModel('category')
const store = defineModel('store')
const normalizePrice = value =>
  value == null || String(value).trim() === '' || !Number.isFinite(Number(value))
    ? null
    : Number(value)
const priceMin = defineModel('priceMin', { set: normalizePrice })
const priceMax = defineModel('priceMax', { set: normalizePrice })
const inStock = defineModel('inStock')
const sort = defineModel('sort')
</script>

<style scoped>
.product-filters {
  padding: 18px;
}

/* Sheet mode splits the panel into a fixed header, a scrollable field list, and a fixed footer (Apply/Clear). */
.product-filters-sheet {
  display: flex;
  flex-direction: column;

  flex: 1;
  min-height: 0;

  padding: 18px 0 0;
}

.product-filters-sheet .filters-panel-header {
  flex-shrink: 0;

  padding: 0 18px;
}

.product-filters-sheet .filters-scroll {
  flex: 1;
  min-height: 0;

  overflow-y: auto;

  padding: 0 18px;
}

.product-filters-sheet .filters-footer {
  flex-shrink: 0;

  padding: 4px 18px 18px;
}

.filters-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-bottom: 16px;
}

.filters-panel-title-group {
  display: flex;
  align-items: center;

  gap: 10px;
}

.filters-panel-title {
  font-size: var(--fs-xl);
  font-weight: 700;

  color: var(--c-text);
}

/* Zeroes QBtn's own min-width and padding, since the panel's close control is a bare glyph. */
.filters-close-btn :deep(.q-icon) {
  font-size: 18px;
}

.filters-close-btn {
  min-width: auto;
  min-height: auto;

  display: flex;
  align-items: center;
  justify-content: center;

  width: 28px;
  height: 28px;

  border: none;
  border-radius: 50%;

  background: transparent;
  color: var(--c-muted);

  cursor: pointer;

  transition: background-color 0.15s;
}

.filters-close-btn:hover {
  background: var(--c-hairline);
}

/* In the phone bottom sheet the close button grows to a 44px thumb-sized target. */
:global(.q-dialog__inner--bottom .filters-close-btn) {
  width: 44px;
  height: 44px;
}

:global(.q-dialog__inner--bottom .filters-close-btn .q-icon) {
  font-size: 26px;
}

/* Touch screens get the same thumb-sized close button in the side panel too, e.g. a tablet held sideways. */
@media (pointer: coarse) {
  .filters-close-btn {
    width: 44px;
    height: 44px;
  }

  .filters-close-btn :deep(.q-icon) {
    font-size: 26px;
  }
}

.filter-group {
  margin-bottom: 16px;
}

.filter-group-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.filter-label {
  display: block;

  margin-bottom: 6px;

  font-size: var(--fs-sm);
  font-weight: 600;

  color: var(--c-text-2);
}

.filter-label-inline {
  margin-bottom: 0;

  font-size: var(--fs-sm);
  color: var(--c-text-2);
}

.filter-hint {
  margin: 6px 0 0;

  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-muted);
}

.price-range-row {
  display: flex;
  align-items: center;

  gap: 8px;
}

.price-range-sep {
  color: var(--c-muted);
  flex-shrink: 0;
}

.price-range-row :deep(.q-field) {
  flex: 1;
  min-width: 0;
}

.product-filters :deep(.q-field--outlined .q-field__control) {
  border-radius: var(--r-lg);
}

/* Same red hover/focus fill as the page-level Sort/Filters controls, for consistency across every field. */
.product-filters :deep(.q-field--outlined .q-field__control:hover),
.product-filters :deep(.q-field--outlined.q-field--focused .q-field__control) {
  background: var(--c-brand-tint);
}

.product-filters :deep(.q-field--outlined .q-field__control:hover):before {
  border-color: var(--c-brand);
}

.filters-divider {
  margin: 4px 0 16px;
}

.apply-filters-btn {
  width: 100%;
  height: 40px;

  border-radius: var(--r-sm);

  background: var(--c-brand);
  color: #ffffff;

  font-size: var(--fs-md);
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.apply-filters-btn:hover {
  background: var(--c-brand-hover);

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.apply-filters-btn:active {
  background: var(--c-brand-active);

  box-shadow: 0 2px 6px rgba(189, 36, 39, 0.28);

  transform: translateY(0);
}

.apply-filters-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 3px rgba(189, 36, 39, 0.3);
}

.clear-filters-row {
  margin-top: 12px;

  text-align: center;
}

.clear-filters-link {
  padding: 0;
  border: none;
  background: transparent;
  font-family: inherit;
  font-size: var(--fs-sm);
  font-weight: 500;

  color: var(--c-brand);

  cursor: pointer;
  transition: color 0.15s;
}

.clear-filters-link:hover {
  color: var(--c-brand-active);
  text-decoration: underline;
}

.clear-filters-link:focus-visible {
  outline: 2px solid var(--c-brand);
  outline-offset: 3px;
}

.product-filters-sheet .filters-footer {
  padding-bottom: calc(18px + env(safe-area-inset-bottom, 0px));
}
</style>
