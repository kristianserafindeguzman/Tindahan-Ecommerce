<template>
  <div class="product-filters" :class="{ 'product-filters-sheet': isSheet }">
    <div class="filters-panel-header">
      <div class="filters-panel-title-group">
        <span class="filters-panel-title">Filters</span>
      </div>
      <q-btn flat dense round :ripple="false" icon="o_close" class="filters-close-btn" aria-label="Close filters" @click="$emit('close')" />
    </div>

    <div class="filters-scroll">
      <div class="filter-group">
        <label class="filter-label">Categories</label>
        <q-select
          v-model="category"
          :options="categoryOptions"
          dense
          outlined
          emit-value
          map-options
          hide-bottom-space
          behavior="menu"
        />
      </div>

      <div class="filter-group">
        <label class="filter-label">Price Range</label>
        <div class="price-range-row">
          <q-input v-model.number="priceMin" type="number" dense outlined hide-bottom-space placeholder="Min" />
          <span class="price-range-sep">–</span>
          <q-input v-model.number="priceMax" type="number" dense outlined hide-bottom-space placeholder="Max" />
        </div>
      </div>

      <div v-if="!hideStore" class="filter-group">
        <label class="filter-label">Store</label>
        <q-select
          v-model="store"
          :options="storeOptions"
          dense
          outlined
          emit-value
          map-options
          hide-bottom-space
          behavior="menu"
        />
      </div>

      <div class="filter-group filter-group-row">
        <label class="filter-label filter-label-inline">In Stock Only</label>
        <q-toggle v-model="inStock" dense color="primary" />
      </div>

      <div class="filter-group">
        <label class="filter-label">Sort by</label>
        <q-select
          v-model="sort"
          :options="sortOptions"
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
        label="Apply Filters"
        unelevated
        no-caps
        class="apply-filters-btn"
        @click="$emit('close')"
      />

      <div class="clear-filters-row">
        <span class="clear-filters-link" @click="$emit('clear')">Clear all filters</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  categoryOptions: {
    type: Array,
    required: true
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

const category = defineModel('category')
const store = defineModel('store')
const priceMin = defineModel('priceMin')
const priceMax = defineModel('priceMax')
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

/* QBtn ships min-width and padding of its own; the panel's close control is a bare
   glyph, so those are zeroed here. */
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

.price-range-row {
  display: flex;
  align-items: center;

  gap: 8px;
}

.price-range-sep {
  color: var(--c-muted);
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
</style>

