import assert from 'node:assert/strict'
import { test } from 'node:test'
import { computed } from 'vue'

async function createLanguage(storage) {
  globalThis.localStorage = storage
  const module = await import(
    '../src/composables/useConsumerLanguage.js?test=' + Math.random()
  )
  return module.useConsumerLanguage
}

const storage = (values = {}) => ({
  values,
  getItem(key) {
    return this.values[key] ?? null
  },
  setItem(key, value) {
    this.values[key] = value
  }
})

test('consumer language updates shared views, persists, and leaves vendor settings alone', async () => {
  const saved = storage({ vendor_lang: 'ph' })
  const useLanguage = await createLanguage(saved)
  const first = useLanguage()
  const second = useLanguage()
  const heading = computed(() => second.t('Products'))
  assert.equal(first.lang.value, 'en')
  assert.equal(heading.value, 'Products')
  first.setLanguage('fil')
  assert.equal(heading.value, 'Products')
  assert.equal(saved.values.consumer_lang, 'fil')
  assert.equal(saved.values.vendor_lang, 'ph')
  const restored = (await createLanguage(saved))()
  assert.equal(restored.lang.value, 'fil')
  restored.setLanguage('en')
  assert.equal(restored.t('Products'), 'Products')
  assert.equal(restored.t('View Order Details'), 'View Order Details')
})

test('invalid saved languages and selections fall back without breaking the current preference', async () => {
  const saved = storage({ consumer_lang: 'invalid' })
  const language = (await createLanguage(saved))()
  assert.equal(language.lang.value, 'en')
  language.setLanguage('fil')
  language.setLanguage('invalid')
  assert.equal(language.lang.value, 'fil')
  assert.equal(saved.values.consumer_lang, 'fil')
})

test('translations interpolate names and quantities, preserve unknown content, and translate schedules', async () => {
  const language = (await createLanguage(storage({ consumer_lang: 'fil' })))()
  assert.equal(
    language.t('{name} added to cart.', { name: 'Milk & Bread' }),
    'Na-add sa cart ang Milk & Bread.'
  )
  assert.equal(language.itemCount(0), '0 na item')
  assert.equal(language.itemCount(2), '2 na item')
  assert.equal(language.t('Seller product name'), 'Seller product name')
  assert.equal(
    language.t('Review your order before confirming.'),
    'Tingnan muna ang order mo bago i-confirm.'
  )
  assert.equal(language.t('View Order Details'), 'Tingnan ang Order Details')
  assert.equal(
    language.languages.find(option => option.value === 'fil').label,
    'Filipino'
  )
  assert.equal(language.t(null), '')
  assert.equal(
    language.storeStatus({ scheduleStatusText: 'Open until 8:00 PM' }),
    'Bukas hanggang 8:00 PM'
  )
  assert.equal(
    language.storeStatus({ scheduleStatusText: 'Closed till Monday 8:00 AM' }),
    'Sarado hanggang Lunes 8:00 AM'
  )
  assert.equal(
    language.storeStatus({ scheduleStatusText: 'Open today' }),
    'Bukas ngayong araw'
  )
  assert.equal(language.locale.value, 'fil-PH')
  language.setLanguage('en')
  assert.equal(language.itemCount(1), '1 item')
  assert.equal(language.itemCount(2), '2 items')
})

test('blocked browser storage still allows language switching', async () => {
  const language = (
    await createLanguage({
      getItem() {
        throw new Error('blocked')
      },
      setItem() {
        throw new Error('blocked')
      }
    })
  )()
  assert.equal(language.lang.value, 'en')
  language.setLanguage('fil')
  assert.equal(language.t('Products'), 'Products')
})

test('filter option labels change while values stay stable', async () => {
  const language = (await createLanguage(storage({ consumer_lang: 'fil' })))()
  const options = [
    { label: 'All Categories', value: 'All' },
    { label: 'Popular', value: 'popular' }
  ]
  assert.deepEqual(language.translateOptions(options), [
    { label: 'Lahat ng Categories', value: 'All' },
    { label: 'Popular', value: 'popular' }
  ])
  assert.equal(options[0].label, 'All Categories')
  assert.deepEqual(language.translateOptions(['Other']), [
    { label: 'Iba Pa', value: 'Other' }
  ])
})


test('inherited object property names never crash translation lookup', async () => {
  const language = (await createLanguage(storage()))()
  for (const locale of ['en', 'fil']) {
    language.setLanguage(locale)
    for (const name of ['constructor', '__proto__', 'toString', 'hasOwnProperty']) {
      assert.equal(language.t(name), name)
      assert.deepEqual(language.translateOptions([name]), [{ label: name, value: name }])
    }
  }
})

test('open stores without closing times show a complete status', async () => {
  const language = (await createLanguage(storage()))()
  assert.equal(language.storeStatus({ isOpen: true }), 'Open')
  language.setLanguage('fil')
  assert.equal(language.storeStatus({ isOpen: true, closesAt: null }), 'Bukas')
  assert.equal(language.storeStatus({ isOpen: true, closesAt: '8:00 PM' }), 'Bukas hanggang 8:00 PM')
  assert.equal(language.storeStatus({ isOpen: false }), 'Sarado ngayon')
})

test('store filters preserve seller names that match translation keys', async () => {
  const language = (await createLanguage(storage({ consumer_lang: 'fil' })))()
  const options = [
    { label: 'All Stores', value: 'All' },
    { label: 'Store', value: 'Store' },
    { label: 'Products', value: 'Products' }
  ]
  const original = structuredClone(options)
  assert.deepEqual(language.translateOptions(options, option => option.value === 'All'), [
    { label: 'Lahat ng Stores', value: 'All' },
    { label: 'Store', value: 'Store' },
    { label: 'Products', value: 'Products' }
  ])
  assert.deepEqual(options, original)
  assert.deepEqual(language.translateOptions(['Products'], () => false), [
    { label: 'Products', value: 'Products' }
  ])
})

test('birthday validation errors react to consumer language changes', async () => {
  const language = (await createLanguage(storage()))()
  const error = computed(() => language.t('Enter a valid birthday.'))
  assert.equal(error.value, 'Enter a valid birthday.')
  language.setLanguage('fil')
  assert.equal(error.value, 'Maglagay ng valid na birthday.')
  assert.equal(language.t('Choose a month, day and year.'), 'Pumili ng buwan, araw, at taon.')
  language.setLanguage('en')
  assert.equal(error.value, 'Enter a valid birthday.')
})
