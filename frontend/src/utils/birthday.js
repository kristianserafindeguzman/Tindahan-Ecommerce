const pad = value => String(value).padStart(2, '0')

// Reject dates that JavaScript would silently roll into another month.
function parseBirthday(value) {
  if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return null
  const [year, month, day] = value.split('-').map(Number)
  const date = new Date(year, month - 1, day)
  if (
    date.getFullYear() !== year ||
    date.getMonth() !== month - 1 ||
    date.getDate() !== day
  ) return null
  return date
}

// Local yesterday, matching the backend's before-today rule.
export const latestBirthday = () => {
  const date = new Date()
  date.setDate(date.getDate() - 1)
  return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
}

// Only real YYYY-MM-DD dates from 1900 to yesterday are allowed.
export const isValidBirthday = value =>
  !!parseBirthday(value) && value >= '1900-01-01' && value <= latestBirthday()

// Never display a different calendar date for malformed input.
export const formatBirthday = (value, locale = 'en-PH') => {
  const date = parseBirthday(value)
  if (!date) return ''
  return date.toLocaleDateString(locale, {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
