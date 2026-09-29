// Filtering and sorting shared by the Order List and Customer Orders tables: order number, date and total ranges, plus the sort.
const pad = n => String(n).padStart(2, '0')

const has = value => value !== null && value !== undefined && value !== ''

// A date as "2026-08-15" in local time, the form the date inputs use.
export const dayKey = value => {
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? '' : `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

export const SORT_OPTIONS = [
  { label: 'Newest first', value: 'date_desc' },
  { label: 'Oldest first', value: 'date_asc' },
  { label: 'Highest total', value: 'total_desc' },
  { label: 'Lowest total', value: 'total_asc' },
  { label: 'Order ID, high to low', value: 'id_desc' },
  { label: 'Order ID, low to high', value: 'id_asc' }
]

export const emptyOrderFilters = () => ({
  idFrom: null,
  idTo: null,
  dateFrom: '',
  dateTo: '',
  minTotal: null,
  maxTotal: null,
  sort: 'date_desc'
})

// Clears the ranges but keeps the chosen sort.
export const resetOrderFilters = filters => Object.assign(filters, { ...emptyOrderFilters(), sort: filters.sort })

export const activeFilterCount = filters => [
  has(filters.idFrom) || has(filters.idTo),
  has(filters.dateFrom) || has(filters.dateTo),
  has(filters.minTotal) || has(filters.maxTotal)
].filter(Boolean).length

export const applyOrderFilters = (orders, filters) => {
  const list = orders.filter(order => {
    const id = Number(order.order_id)
    const total = Number(order.total_amount || 0)
    const day = order.created_at ? dayKey(order.created_at) : ''
    if (has(filters.idFrom) && id < Number(filters.idFrom)) return false
    if (has(filters.idTo) && id > Number(filters.idTo)) return false
    if (has(filters.dateFrom) && (!day || day < filters.dateFrom)) return false
    if (has(filters.dateTo) && (!day || day > filters.dateTo)) return false
    if (has(filters.minTotal) && total < Number(filters.minTotal)) return false
    if (has(filters.maxTotal) && total > Number(filters.maxTotal)) return false
    return true
  })

  const [field, direction] = (filters.sort || 'date_desc').split('_')
  const valueOf = order => {
    if (field === 'id') return Number(order.order_id)
    if (field === 'total') return Number(order.total_amount || 0)
    return new Date(order.created_at).getTime() || 0
  }
  return list.sort((a, b) => (valueOf(a) - valueOf(b)) * (direction === 'asc' ? 1 : -1))
}

// Clicking a heading sorts by it, high to low first, and a second click flips the direction.
export const toggleSort = (sort, field) => {
  const [current, direction] = (sort || '').split('_')
  if (current === field) return `${field}_${direction === 'asc' ? 'desc' : 'asc'}`
  return `${field}_desc`
}

export const sortDirection = (sort, field) => {
  const [current, direction] = (sort || '').split('_')
  return current === field ? direction : null
}

const formatDay = key => new Date(`${key}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
const peso = value => `₱${Number(value).toLocaleString('en-US', { maximumFractionDigits: 2 })}`
const rangeLabel = (from, to, show) => {
  if (has(from) && has(to)) return `${show(from)} – ${show(to)}`
  return has(from) ? `from ${show(from)}` : `up to ${show(to)}`
}
const capitalise = text => text.charAt(0).toUpperCase() + text.slice(1)

// Short labels for the filters in use, shown as removable chips under the toolbar.
export const describeFilters = filters => {
  const chips = []
  if (has(filters.idFrom) || has(filters.idTo)) chips.push({ key: 'id', label: `Order ${rangeLabel(filters.idFrom, filters.idTo, v => `#${v}`)}` })
  if (has(filters.dateFrom) || has(filters.dateTo)) chips.push({ key: 'date', label: capitalise(rangeLabel(filters.dateFrom, filters.dateTo, formatDay)) })
  if (has(filters.minTotal) || has(filters.maxTotal)) chips.push({ key: 'total', label: `Total ${rangeLabel(filters.minTotal, filters.maxTotal, peso)}` })
  return chips
}

export const clearFilter = (filters, key) => {
  if (key === 'id') Object.assign(filters, { idFrom: null, idTo: null })
  if (key === 'date') Object.assign(filters, { dateFrom: '', dateTo: '' })
  if (key === 'total') Object.assign(filters, { minTotal: null, maxTotal: null })
}
