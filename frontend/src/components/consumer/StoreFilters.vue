<template>
  <div class="store-filters">
    <div class="filters-panel-header">
      <div class="filters-panel-title-group">
        <span class="filters-panel-title">Filters</span>
      </div>
      <button type="button" class="filters-close-btn" aria-label="Close filters" @click="$emit('close')">
        <q-icon name="close" size="18px" />
      </button>
    </div>

    <div class="filter-group filter-group-row">
      <label class="filter-label filter-label-inline">Open Now</label>
      <q-toggle v-model="openNow" dense color="primary" />
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
  sortOptions: {
    type: Array,
    required: true
  }
})

defineEmits(['close', 'clear'])

const openNow = defineModel('openNow')
const sort = defineModel('sort')
</script>

<style scoped>
.store-filters {
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
  color: #666666;

  cursor: pointer;

  transition: background-color 0.15s;
}

.filters-close-btn:hover {
  background: #f4f4f4;
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

  font-size: 12.5px;
  font-weight: 600;

  color: #4a4a4a;
}

.filter-label-inline {
  margin-bottom: 0;

  font-size: 13px;
  color: #333333;
}

.store-filters :deep(.q-field--outlined .q-field__control) {
  border-radius: 10px;
}

/* Same red hover/focus fill as the page-level Sort/Filters controls, for consistency across every field. */
.store-filters :deep(.q-field--outlined .q-field__control:hover),
.store-filters :deep(.q-field--outlined.q-field--focused .q-field__control) {
  background: #fdecec;
}

.store-filters :deep(.q-field--outlined .q-field__control:hover):before {
  border-color: #bd2427;
}

.filters-divider {
  margin: 4px 0 16px;
}

.apply-filters-btn {
  width: 100%;
  height: 40px;

  border-radius: 6px;

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
  color: #8f1a1c;
  text-decoration: underline;
}
</style>
