<template>
  <div class="birthday-input" role="group" aria-label="Birthday">
    <!-- Three plain dropdowns, the way most sign-up forms ask for a birthday, so nobody has to page back through a calendar. -->
    <div
      ref="rowRef"
      class="birthday-row"
      :class="{ 'birthday-row--error': !!errorMessage }"
      @focusout="checkLeave"
    >
      <!-- Each dropdown shows its placeholder in place of a value until one is picked. -->
      <q-select
        v-model="month"
        :options="MONTHS"
        emit-value
        map-options
        outlined
        dense
        hide-bottom-space
        behavior="menu"
        dropdown-icon="keyboard_arrow_down"
        :display-value="month ? undefined : 'Birth Month'"
        popup-content-class="birthday-menu"
        class="birthday-month"
        :class="{ 'birthday-empty': !month }"
        @popup-show="openMenus++"
        @popup-hide="onMenuHide"
      />
      <q-select
        v-model="day"
        :options="dayOptions"
        outlined
        dense
        hide-bottom-space
        behavior="menu"
        dropdown-icon="keyboard_arrow_down"
        :display-value="day ? undefined : 'Birth Day'"
        popup-content-class="birthday-menu"
        class="birthday-day"
        :class="{ 'birthday-empty': !day }"
        @popup-show="openMenus++"
        @popup-hide="onMenuHide"
      />
      <q-select
        v-model="year"
        :options="YEARS"
        outlined
        dense
        hide-bottom-space
        behavior="menu"
        dropdown-icon="keyboard_arrow_down"
        :display-value="year ? undefined : 'Birth Year'"
        popup-content-class="birthday-menu"
        class="birthday-year"
        :class="{ 'birthday-empty': !year }"
        @popup-show="openMenus++"
        @popup-hide="onMenuHide"
      />
    </div>

    <div v-if="errorMessage" class="birthday-error" role="alert">{{
      errorMessage
    }}</div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useFormChild } from 'quasar'
import { isValidBirthday } from '@/utils/birthday'

const MONTHS = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December'
].map((label, i) => ({ label, value: i + 1 }))

// Newest first and never past this year, so recent birth years sit at the top of the list.
const THIS_YEAR = new Date().getFullYear()
const YEARS = Array.from({ length: THIS_YEAR - 1899 }, (_, i) => THIS_YEAR - i)

const props = defineProps({
  // The birthday as YYYY-MM-DD, or an empty string until all three parts are picked.
  modelValue: { type: String, default: '' },
  // Rules checked against the YYYY-MM-DD value, the same way Quasar field rules work.
  rules: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:modelValue', 'touched'])

const rowRef = ref(null)
const month = ref(null)
const day = ref(null)
const year = ref(null)
const touched = ref(false)
const errorMessage = ref('')
const openMenus = ref(0)

const pad = n => String(n).padStart(2, '0')

const setParts = value => {
  const [y, m, d] = value ? value.split('-').map(Number) : []
  year.value = y || null
  month.value = m || null
  day.value = d || null
}
setParts(props.modelValue)

// Day 0 of the next month is the last day of this one, and a leap year stands in until a year is picked so February 29 stays open.
const daysInMonth = computed(() =>
  new Date(year.value || 2000, month.value || 1, 0).getDate()
)
const dayOptions = computed(() =>
  Array.from({ length: daysInMonth.value }, (_, i) => i + 1)
)

// A day the new month or year does not have is cleared rather than quietly moved to another date.
watch(daysInMonth, max => {
  if (day.value > max) day.value = null
})

const value = computed(() =>
  year.value && month.value && day.value
    ? `${year.value}-${pad(month.value)}-${pad(day.value)}`
    : ''
)

// True once any part is picked but not all three, which the page would otherwise see as no birthday at all.
const partial = computed(
  () => !value.value && !!(month.value || day.value || year.value)
)

// A half-picked date or one outside 1900 to yesterday is never valid, whatever rules the page adds on top.
const validate = () => {
  const checks = [
    () => !partial.value || 'Choose a month, day and year.',
    v => !v || isValidBirthday(v) || 'Enter a valid birthday.',
    ...props.rules
  ]
  for (const rule of checks) {
    const result = rule(value.value)
    if (result !== true) {
      errorMessage.value = typeof result === 'string' ? result : ''
      return false
    }
  }
  errorMessage.value = ''
  return true
}

const resetValidation = () => {
  errorMessage.value = ''
  touched.value = false
}

// Lets the parent QForm check this field on submit like any other input.
useFormChild({ validate, resetValidation, requiresQForm: false })

const markTouched = () => {
  if (touched.value) return
  touched.value = true
  emit('touched')
}

watch(value, v => {
  if (v !== props.modelValue) emit('update:modelValue', v)
  if (v) markTouched()
  if (touched.value) validate()
})

// Only an outside change is copied in, since a half-picked date is sent up as an empty string and must not wipe the parts.
watch(
  () => props.modelValue,
  v => {
    if (v === value.value) return
    if (v) setParts(v)
    else if (value.value) setParts('')
  }
)

// The field counts as left once focus settles outside it with no dropdown open, which is when a missing date gets flagged.
const checkLeave = () => {
  setTimeout(() => {
    const active = document.activeElement
    if (
      !rowRef.value ||
      openMenus.value > 0 ||
      rowRef.value.contains(active) ||
      active?.closest?.('.birthday-menu')
    )
      return
    markTouched()
    validate()
  }, 150)
}

const onMenuHide = () => {
  openMenus.value = Math.max(0, openMenus.value - 1)
  checkLeave()
}
</script>

<style scoped>
/* Sized by its own width rather than the screen's, since the same field sits in the sign-up card and the profile dialog. */
.birthday-input {
  container-type: inline-size;
}

/* Columns follow each placeholder's length, so all three fit on one row wherever there is room. */
.birthday-row {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) minmax(0, 1.05fr);
  gap: 8px;
}

/* Too narrow for one row, Birth Month takes a full row with Birth Day and Birth Year side by side below it, so no placeholder is cut off. */
@container (max-width: 329px) {
  .birthday-row {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  }

  .birthday-month {
    grid-column: 1 / -1;
  }
}

/* Tighter insides and a slim chevron leave the room the placeholders need. */
.birthday-input .birthday-row :deep(.q-field__control) {
  padding: 0 4px 0 12px;
}

.birthday-input .birthday-row :deep(.q-field__append) {
  padding-left: 0;
}

.birthday-input .birthday-row :deep(.q-select__dropdown-icon) {
  font-size: 18px;
}

/* A picked value stays 14px even on phones, which is safe because a dropdown opens no keyboard for iOS to zoom into. */
.birthday-input .birthday-row :deep(.q-field__native) {
  min-width: 0;
  padding-left: 0;

  font-size: 14px;
}

/* Cut off with an ellipsis rather than spilling under the chevron if a very narrow screen runs out of room. */
.birthday-input .birthday-row :deep(.q-field__native > span) {
  display: block;
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;

  white-space: nowrap;
  text-overflow: ellipsis;
}

/* Placeholders stay 13px at every width, the size that lets all three fit in the 330px sign-up card. */
.birthday-input .birthday-empty :deep(.q-field__native) {
  font-size: 13px;

  color: var(--c-muted);
}

/* Taps land on this hidden input, and 16px keeps iOS from zooming the page when it takes focus. */
.birthday-input .birthday-row :deep(.q-select__focus-target) {
  font-size: 16px;
}

.birthday-row--error :deep(.q-field__control::before) {
  border-color: var(--c-danger);
}

.birthday-error {
  margin-top: 6px;

  font-size: var(--fs-xs);
  line-height: 1.4;

  color: var(--c-danger);
}
</style>
