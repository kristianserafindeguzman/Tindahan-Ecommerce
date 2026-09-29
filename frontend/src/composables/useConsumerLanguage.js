import { computed, readonly, ref } from 'vue'
import messages from '../i18n/consumerMessages.js'

const STORAGE_KEY = 'consumer_lang'
const languages = Object.freeze([
  { value: 'en', label: 'English' },
  { value: 'fil', label: 'Filipino' }
])
const isSupported = value =>
  languages.some(language => language.value === value)
const readLanguage = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    return isSupported(saved) ? saved : 'en'
  } catch {
    return 'en'
  }
}
const language = ref(readLanguage())
const locale = computed(() => (language.value === 'fil' ? 'fil-PH' : 'en-PH'))

function setLanguage(value) {
  if (!isSupported(value)) return
  language.value = value
  try {
    localStorage.setItem(STORAGE_KEY, value)
  } catch {
    // Switching still works when browser storage is unavailable.
  }
}

function t(key, params = {}) {
  if (key == null) return ''
  const text =
    language.value === 'fil'
      ? (Object.hasOwn(messages, key) ? messages[key] : String(key))
      : String(key)
  return text.replace(/\{(\w+)\}/g, (match, name) =>
    Object.hasOwn(params, name) ? String(params[name]) : match
  )
}

function itemCount(count) {
  return t(count === 1 ? '{count} item' : '{count} items', { count })
}

function storeStatus(store) {
  const text = store.scheduleStatusText
  if (text) {
    if (text.startsWith('Open until '))
      return t('Open until {time}', { time: text.slice(11) })
    if (text.startsWith('Closed till ')) {
      const time = text
        .slice(12)
        .replace(
          /Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday/g,
          day => t(day)
        )
      return t('Closed till {time}', { time })
    }
    return t(text)
  }
  return store.isOpen
    ? (store.closesAt ? t('Open until {time}', { time: store.closesAt }) : t('Open'))
    : t('Closed now')
}

function translateOptions(options, shouldTranslate = () => true) {
  return options.map(option => {
    const translateLabel = shouldTranslate(option)
    return typeof option === 'string'
      ? { label: translateLabel ? t(option) : option, value: option }
      : { ...option, label: translateLabel ? t(option.label) : option.label }
  })
}

export function useConsumerLanguage() {
  return {
    lang: readonly(language),
    locale,
    languages,
    setLanguage,
    t,
    itemCount,
    storeStatus,
    translateOptions
  }
}
