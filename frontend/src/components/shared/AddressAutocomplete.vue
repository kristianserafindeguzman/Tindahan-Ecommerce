<template>
  <!-- The parent's class stays on this wrapper, so its scoped :deep() rules still reach the input. Suggestions float over the neighbouring map instead of pushing the layout, and mousedown.prevent keeps the input focused so a pick is not lost to blur. -->
  <div class="address-autocomplete" :class="$attrs.class" :style="$attrs.style">
    <q-input
      v-bind="{ ...$attrs, class: undefined, style: undefined }"
      :model-value="modelValue"
      autocomplete="off"
      @update:model-value="onInput"
      @keydown.enter="onEnter"
      @keydown.down="moveActive($event, 1)"
      @keydown.up="moveActive($event, -1)"
      @blur="closeSuggestions"
    />

    <div v-if="open" class="address-suggestions" :class="{ 'address-suggestions-above': above }" @mousedown.prevent>
      <div
        v-for="(suggestion, i) in suggestions"
        :key="suggestion.address"
        class="address-suggestion"
        :class="{ 'address-suggestion-active': activeIndex === i }"
        @click="pick(suggestion)"
      >
        <q-icon name="o_location_on" size="16px" class="address-suggestion-icon" />
        <span class="address-suggestion-text">
          <template v-for="(part, pi) in splitHighlightParts(suggestion.address, modelValue)" :key="pi">
            <mark v-if="part.match" class="address-suggestion-highlight">{{ part.text }}</mark>
            <template v-else>{{ part.text }}</template>
          </template>
        </span>
      </div>
      <div v-if="!suggestions.length" class="address-suggestions-note">
        {{ searching ? translate('Finding places...') : translate("Can't find it? Pin your spot on the map, then edit the address if needed.") }}
      </div>
      <!-- The map only knows streets and areas for most addresses, so the pin usually lands close rather than exact. -->
      <div v-else class="address-suggestions-note address-suggestions-hint">
        {{ translate('Pin not on your exact spot? Tap the map to move it.') }}
      </div>
    </div>
  </div>
</template>

<script>
// Everything else the parent passes (label, rules, textarea options) goes to the q-input, not the wrapper.
export default { inheritAttrs: false }
</script>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { searchAddresses } from '@/utils/geolocation'
import { calculateDistanceMeters } from '@/utils/distance'
import { splitHighlightParts } from '@/utils/textHighlight'

const props = defineProps({
  modelValue: { type: String, default: '' },
  // Opens the list upward, for an input that sits below its map.
  above: { type: Boolean, default: false },
  translate: { type: Function, default: text => text },
  // A pin the parent already holds, such as a saved store location, which typing then leaves in place and searches rank near.
  pinned: { type: Object, default: null }
})

// pin carries a location for the parent to move its map to and store: the best match for typed text, or a picked suggestion whose address has already replaced the text.
const emit = defineEmits(['update:modelValue', 'pin', 'enter'])

// A one-part query such as "Sitio Mahayahay" names no town to check its match against, so the match is pinned only within this range of the search's centre.
const LONE_NAME_PIN_RANGE_M = 50000
// The free public Photon server sometimes stops answering, and fetch has no timeout of its own, so a search gives up after this long.
const SEARCH_TIMEOUT_MS = 8000
// How long a confirm waits for a search still running on just-typed text before going ahead with the current pin.
const SETTLE_WAIT_MS = 3000

const hasPinned = props.pinned?.latitude != null && props.pinned?.longitude != null

const suggestions = ref([])
const open = ref(false)
const searching = ref(false)
const activeIndex = ref(-1)
let searchTimer = null
let searchController = null
// The text waiting to be searched and its search once started, so Enter can finish it first; searchSeq is bumped by every cancel.
let queuedQuery = null
let pendingSearch = null
let searchSeq = 0
// Set while the box holds text the user typed, which the map then leaves alone and only re-pins.
let typed = false
// Set once the user places the pin themselves (map tap, Your Location, a picked suggestion or a saved pin); until then it follows the typed address.
let pinChosenByUser = hasPinned
// Where searches rank results from, which is never a pin that typing placed, so one bad partial match cannot drag later searches after it.
let searchBias = hasPinned ? props.pinned : null

const cancelSearch = () => {
  clearTimeout(searchTimer)
  searchController?.abort()
  searchController = null
  queuedQuery = null
  pendingSearch = null
  searchSeq++
}

const startSearch = () => {
  clearTimeout(searchTimer)
  if (!pendingSearch && queuedQuery) pendingSearch = runSearch(queuedQuery)
  return pendingSearch
}

const closeSuggestions = () => {
  cancelSearch()
  open.value = false
  searching.value = false
  suggestions.value = []
  activeIndex.value = -1
}

const shouldAutoPin = (query, result) => {
  if (pinChosenByUser) return false
  if (query.includes(',') || !searchBias) return true
  const meters = calculateDistanceMeters(searchBias.latitude, searchBias.longitude, result.latitude, result.longitude)
  return meters == null || meters <= LONE_NAME_PIN_RANGE_M
}

const runSearch = async (query) => {
  const controller = new AbortController()
  searchController = controller
  const timeout = setTimeout(() => controller.abort(new Error('Address search timed out')), SEARCH_TIMEOUT_MS)
  try {
    const results = await searchAddresses(query, searchBias, controller.signal)
    suggestions.value = results
    // The pin follows the typed address to its best match, leaving the text as typed, until the user places it themselves.
    if (results.length && shouldAutoPin(query, results[0])) emit('pin', results[0])
  } catch (error) {
    // Cancelled for newer typing, a pick or a blur, which own the list now; a timeout keeps searchController and falls through as a failure.
    if (searchController !== controller) return
    console.warn('Address search failed:', error.message)
    suggestions.value = []
  } finally {
    clearTimeout(timeout)
  }
  searching.value = false
}

// A pin the user placed survives typing, so adding a unit number to a map-picked address still saves where the pin is.
const onInput = (value) => {
  const text = value ?? ''
  emit('update:modelValue', text)
  typed = true
  cancelSearch()
  activeIndex.value = -1

  const query = text.trim()
  if (query.length < 3) {
    // Cutting the text back this far is starting over, so the next address typed moves the pin again.
    pinChosenByUser = false
    closeSuggestions()
    return
  }

  // The previous list stays up while the next one loads, so the dropdown does not flicker on every keystroke.
  searching.value = true
  open.value = true
  queuedQuery = query
  searchTimer = setTimeout(startSearch, 300)
}

// Only takes the arrow keys while there is a list, so a textarea keeps them for moving between lines.
const moveActive = (event, delta) => {
  const len = suggestions.value.length
  if (!open.value || !len) return
  event.preventDefault()
  activeIndex.value = activeIndex.value < 0
    ? (delta > 0 ? 0 : len - 1)
    : (activeIndex.value + delta + len) % len
}

const pick = (suggestion) => {
  emit('update:modelValue', suggestion.address)
  typed = false
  pinChosenByUser = true
  searchBias = suggestion
  closeSuggestions()
  emit('pin', suggestion)
}

// Enter picks the highlighted suggestion when there is one, and is otherwise the parent's, such as for confirming the typed address.
const onEnter = async (event) => {
  const suggestion = open.value && suggestions.value[activeIndex.value]
  if (suggestion) {
    event.preventDefault()
    pick(suggestion)
    return
  }
  if (!(await settle())) return
  emit('enter', event)
}

// A confirm straight after typing would use the pin from an earlier, shorter search, so this lets the waiting search finish and move the pin first.
// Resolves false when more typing, a pick or a blur overtakes it, and drops a search still out after SETTLE_WAIT_MS so its pin cannot land after the confirm.
const settle = async () => {
  if (pinChosenByUser || !searching.value || !queuedQuery) return true
  const seq = searchSeq
  const finished = await Promise.race([
    startSearch().then(() => true),
    new Promise((resolve) => setTimeout(() => resolve(false), SETTLE_WAIT_MS))
  ])
  if (seq !== searchSeq) return false
  if (!finished) closeSuggestions()
  return true
}

// The parent forwards its map's location-selected events here, and should put the map's address in the box only when this returns true, which it does unless the box holds typed text.
const mapSelected = (location) => {
  searchBias = location
  // The device fix taken when a map opens is only a starting guess, so it neither holds the pin nor closes suggestions mid-typing.
  if (!location.auto) {
    pinChosenByUser = true
    closeSuggestions()
  }
  if (typed && props.modelValue.trim()) return false
  typed = false
  return true
}

onBeforeUnmount(cancelSearch)

defineExpose({ mapSelected, settle })
</script>

<style scoped>
.address-autocomplete {
  position: relative;
}

/* The header's .search-suggestions surface. VendorLocationMap isolates Leaflet's own z-indexes, so the list only has to clear ordinary positioned neighbours. */
.address-suggestions {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 10;

  max-height: 260px;
  overflow-y: auto;
  padding: 4px 0;

  border-radius: var(--r-lg);
  border: 1px solid var(--c-border);

  background: #ffffff;

  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18);
}

.address-suggestions-above {
  top: auto;
  bottom: calc(100% + 6px);
}

.address-suggestion {
  display: flex;
  align-items: center;

  gap: 10px;
  min-width: 0;
  padding: 9px 14px;

  font-size: var(--fs-sm);
  color: var(--c-text-2);

  cursor: pointer;

  transition: background-color 0.1s;
}

.address-suggestion:hover,
.address-suggestion.address-suggestion-active {
  background: var(--c-brand-tint);
}

.address-suggestion-icon {
  flex-shrink: 0;

  color: var(--c-muted);
}

/* Addresses run long and the town is what tells two apart, so they get two lines before the ellipsis. */
.address-suggestion-text {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;

  overflow: hidden;
  min-width: 0;

  line-height: 1.35;
}

.address-suggestion-highlight {
  background: transparent;
  color: var(--c-brand);
  font-weight: 700;
}

.address-suggestions-note {
  padding: 14px;

  font-size: var(--fs-sm);
  text-align: center;

  color: var(--c-muted);
}

.address-suggestions-hint {
  margin-top: 4px;
  padding: 10px 14px 8px;

  border-top: 1px solid var(--c-hairline);

  text-align: left;
}
</style>
