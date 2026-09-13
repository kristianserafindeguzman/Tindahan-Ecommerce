import { ref } from 'vue'

const globalLang = ref(localStorage.getItem('vendor_lang') || 'en')

// Global fallback dictionary for order statuses
const globalStatusDict = {
  en: {
    placed: 'Placed',
    preparing: 'Preparing',
    ready_for_pickup: 'Ready for pickup',
    picked_up: 'Picked up',
    cancelled: 'Cancelled',
    completed: 'Completed'
  },
  ph: {
    placed: 'Na-order',
    preparing: 'Inihahanda',
    ready_for_pickup: 'Pwede nang kunin',
    picked_up: 'Nakuha na',
    cancelled: 'Kinansela',
    completed: 'Nakuha na'
  }
}

export function useLanguage(componentDict = {}) {
  
  const toggleLanguage = () => {
    globalLang.value = globalLang.value === 'en' ? 'ph' : 'en'
    localStorage.setItem('vendor_lang', globalLang.value)
  }

  const t = (key) => {
    if (!key) return ''

    // 1. Check the local page-level dictionary first
    if (componentDict[globalLang.value]?.[key]) {
      return componentDict[globalLang.value][key]
    }

    // 2. Normalize status strings (e.g. "Ready for pickup" -> "ready_for_pickup")
    const normalizedKey = String(key).toLowerCase().trim().replace(/[\s-]+/g, '_')

    // 3. Check the global status dictionary
    if (globalStatusDict[globalLang.value]?.[normalizedKey]) {
      return globalStatusDict[globalLang.value][normalizedKey]
    }

    return key
  }

  // Helper function to resolve the color class for badges
  const getStatusTone = (status) => {
    const s = String(status || '').toLowerCase().trim().replace(/[\s_-]+/g, '_')
    if (s.includes('ready')) return 'ready'
    if (s.includes('picked') || s.includes('complete')) return 'done'
    if (s.includes('cancel')) return 'cancelled'
    if (s.includes('prepar')) return 'preparing'
    return 'placed'
  }

  // Translation helper specifically for status badges
  const translateStatus = (status) => {
    if (!status) return ''
    const key = String(status).toLowerCase().trim().replace(/[\s-]+/g, '_')
    return globalStatusDict[globalLang.value]?.[key] || status
  }

  return { 
    lang: globalLang, 
    toggleLanguage, 
    t, 
    getStatusTone, 
    translateStatus 
  }
}