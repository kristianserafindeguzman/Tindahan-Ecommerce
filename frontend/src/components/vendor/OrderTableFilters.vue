<template>
  <!-- Order number, date and total ranges, plus the sort for phones, which have no column headings; a dropdown on wide screens and a bottom sheet on phones. -->
  <FilterSheet title="Filter orders" :count="count" :result-count="resultCount" noun="order" @clear="resetOrderFilters(filters)">
    <div class="of-panel">
      <div class="of-field">
        <label class="vp-field-label">Order ID</label>
        <div class="of-range">
          <q-input v-model.number="filters.idFrom" type="number" min="1" outlined dense hide-bottom-space placeholder="From #" class="vp-input" aria-label="Order ID from" />
          <span class="of-dash" aria-hidden="true">–</span>
          <q-input v-model.number="filters.idTo" type="number" min="1" outlined dense hide-bottom-space placeholder="To #" class="vp-input" aria-label="Order ID to" />
        </div>
        <div v-if="flipped(filters.idFrom, filters.idTo)" class="of-warn">The first number is higher than the second.</div>
      </div>

      <div class="of-field">
        <label class="vp-field-label">Date placed</label>
        <div class="of-range">
          <q-input v-model="filters.dateFrom" type="date" outlined dense hide-bottom-space class="vp-input" aria-label="From date" />
          <span class="of-dash" aria-hidden="true">–</span>
          <q-input v-model="filters.dateTo" type="date" outlined dense hide-bottom-space class="vp-input" aria-label="To date" />
        </div>
        <div class="of-presets">
          <button
            v-for="preset in PRESETS"
            :key="preset.key"
            type="button"
            class="vp-chip of-preset"
            :class="{ 'vp-chip--active': isPreset(preset) }"
            @click="applyPreset(preset)"
          >
            {{ preset.label }}
          </button>
        </div>
        <div v-if="flipped(filters.dateFrom, filters.dateTo, true)" class="of-warn">The start date is after the end date.</div>
      </div>

      <div class="of-field">
        <label class="vp-field-label">Total (₱)</label>
        <div class="of-range">
          <q-input v-model.number="filters.minTotal" type="number" min="0" outlined dense hide-bottom-space placeholder="Min" class="vp-input" aria-label="Minimum total" />
          <span class="of-dash" aria-hidden="true">–</span>
          <q-input v-model.number="filters.maxTotal" type="number" min="0" outlined dense hide-bottom-space placeholder="Max" class="vp-input" aria-label="Maximum total" />
        </div>
        <div v-if="flipped(filters.minTotal, filters.maxTotal)" class="of-warn">The minimum is higher than the maximum.</div>
      </div>

      <div class="of-field">
        <label class="vp-field-label">Sort by</label>
        <q-select v-model="filters.sort" :options="SORT_OPTIONS" emit-value map-options outlined dense options-dense behavior="menu" class="vp-input" />
      </div>
    </div>
  </FilterSheet>
</template>

<script setup>
import { computed } from 'vue'
import FilterSheet from '@/components/vendor/FilterSheet.vue'
import { SORT_OPTIONS, activeFilterCount, resetOrderFilters, dayKey } from '@/utils/orderFilters'

const filters = defineModel({ type: Object, required: true })

defineProps({
  // How many orders the filters leave, for the phone sheet's "Show 5 orders" button.
  resultCount: { type: Number, default: null }
})

const count = computed(() => activeFilterCount(filters.value))

const PRESETS = [
  { key: 'today', label: 'Today' },
  { key: 'week', label: 'Last 7 days' },
  { key: 'month', label: 'This month' }
]

const presetRange = key => {
  const today = new Date()
  const start = new Date(today)
  if (key === 'week') start.setDate(today.getDate() - 6)
  if (key === 'month') start.setDate(1)
  return { dateFrom: dayKey(start), dateTo: dayKey(today) }
}

const applyPreset = preset => Object.assign(filters.value, presetRange(preset.key))

const isPreset = preset => {
  const range = presetRange(preset.key)
  return filters.value.dateFrom === range.dateFrom && filters.value.dateTo === range.dateTo
}

// True when both ends are set the wrong way round, so the panel can say why nothing matches.
const flipped = (from, to, isDate = false) => {
  if (from === null || from === '' || to === null || to === '') return false
  return isDate ? from > to : Number(from) > Number(to)
}
</script>

<style scoped>
.of-panel {
  display: flex;
  flex-direction: column;

  gap: 14px;
}

.of-range {
  display: flex;
  align-items: center;

  gap: 6px;
}

.of-range .q-field {
  flex: 1;

  min-width: 0;
}

.of-dash {
  color: var(--c-muted);
}

.of-presets {
  display: flex;
  flex-wrap: wrap;

  gap: 6px;
  margin-top: 8px;
}

.of-preset {
  height: 28px;
  padding: 0 10px;
}

.of-warn {
  margin-top: 6px;

  font-size: var(--fs-xs);

  color: var(--c-danger);
}
</style>
