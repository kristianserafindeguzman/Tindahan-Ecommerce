<template>
  <div class="product-filters">
    <div class="filters-panel-header">
      <div class="filters-panel-title-group">
        <div class="filters-panel-icon"><q-icon name="tune" size="16px" /></div>
        <span class="filters-panel-title">Filters</span>
      </div>
      <button type="button" class="filters-close-btn" aria-label="Close filters" @click="$emit('close')">
        <q-icon name="close" size="18px" />
      </button>
    </div>

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

    <div class="filter-group">
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

    <div class="filter-group">
      <label class="filter-label">Availability</label>
      <q-checkbox v-model="inStock" label="In Stock Only" dense />
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
</template>

<script setup>
defineProps({
  categoryOptions: {
    type: Array,
    required: true
  },
  storeOptions: {
    type: Array,
    required: true
  },
  sortOptions: {
    type: Array,
    required: true
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

.filters-panel-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  width: 28px;
  height: 28px;

  border-radius: 8px;

  background: linear-gradient(145deg, #fdecec 0%, #fbdbdc 100%);
  color: #bd2427;
}

.filters-panel-title {
  font-size: 16px;
  font-weight: 700;

  color: #111111;
}

.filters-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 28px;
  height: 28px;

  border: none;
  border-radius: 50%;

  background: transparent;
  color: #767676;

  cursor: pointer;

  transition: background-color 0.15s;
}

.filters-close-btn:hover {
  background: #f4f4f4;
}

.filter-group {
  margin-bottom: 16px;
}

.filter-label {
  display: block;

  margin-bottom: 6px;

  font-size: 12.5px;
  font-weight: 600;

  color: #4a4a4a;
}

.price-range-row {
  display: flex;
  align-items: center;

  gap: 8px;
}

.price-range-sep {
  color: #9ca3af;
}

.product-filters :deep(.q-field--outlined .q-field__control) {
  border-radius: 8px;
}

.filters-divider {
  margin: 4px 0 16px;
}

.apply-filters-btn {
  width: 100%;
  height: 40px;

  border-radius: 8px;

  background: #bd2427;
  color: #ffffff;

  font-size: 14px;
  font-weight: 600;

  box-shadow: 0 2px 8px rgba(189, 36, 39, 0.25);

  transition: background-color 0.15s, box-shadow 0.2s, transform 0.2s;
}

.apply-filters-btn:hover {
  background: #a91e21;

  box-shadow: 0 6px 16px rgba(189, 36, 39, 0.32);

  transform: translateY(-1px);
}

.apply-filters-btn:active {
  background: #8f1a1c;

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
  font-size: 13px;
  font-weight: 500;

  color: #bd2427;

  cursor: pointer;
  transition: color 0.15s;
}

.clear-filters-link:hover {
  color: #8f1c1e;
  text-decoration: underline;
}
</style>
