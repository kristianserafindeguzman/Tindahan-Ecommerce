// Yesterday in the phone's own time zone as YYYY-MM-DD, the latest birthday allowed, matching the backend's before-today rule.
export const latestBirthday = () => {
  const d = new Date()
  d.setDate(d.getDate() - 1)
  return d.toLocaleDateString('en-CA')
}

// YYYY-MM-DD strings sort by date, so a plain comparison checks the 1900-to-yesterday range.
export const isValidBirthday = value =>
  !!value && value >= '1900-01-01' && value <= latestBirthday()

// Shows a YYYY-MM-DD birthday as a written date such as June 15, 1995.
export const formatBirthday = value => {
  if (!value) return ''
  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, month - 1, day).toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
