import { ref } from 'vue'

// Globally shared state across Admin, Vendor, and Consumer.
// Checks the new global key first, falls back to the old vendor key so no one's preference resets.
const globalLang = ref(localStorage.getItem('tindahan_lang') || localStorage.getItem('vendor_lang') || 'en')

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
  
  // NEW: Added setLanguage so AdminLayout can use it
  const setLanguage = (newLang) => {
    globalLang.value = newLang
    localStorage.setItem('tindahan_lang', newLang)
    // Keep vendor_lang updated just in case older components rely on it directly
    localStorage.setItem('vendor_lang', newLang) 
  }

  // Refactored to use the new setLanguage
  const toggleLanguage = () => {
    const nextLang = globalLang.value === 'en' ? 'ph' : 'en'
    setLanguage(nextLang)
  }

  const t = (key) => {
    if (!key) return ''

    // 1. Check the local page-level dictionary first
    if (componentDict[globalLang.value]?.[key]) {
      return componentDict[globalLang.value][key]
    }

    // NEW: Fallback to English if translation is missing in Filipino
    if (globalLang.value !== 'en' && componentDict['en']?.[key]) {
      return componentDict['en'][key]
    }

    // 2. Normalize status strings (e.g. "Ready for pickup" -> "ready_for_pickup")
    const normalizedKey = String(key).toLowerCase().trim().replace(/[\s-]+/g, '_')

    // 3. Check the global status dictionary
    if (globalStatusDict[globalLang.value]?.[normalizedKey]) {
      return globalStatusDict[globalLang.value][normalizedKey]
    }

    // Fallback to the exact key name if not found anywhere
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
    setLanguage, // Exported for Admin
    toggleLanguage, 
    t, 
    getStatusTone, 
    translateStatus 
  }
}