// Splits text around the first case-insensitive match of the query into plain-text parts, so there is no injection risk.
export function splitHighlightParts(text, query) {
  const value = text || ''
  const q = (query || '').trim()

  if (!q) return [{ text: value, match: false }]

  const index = value.toLowerCase().indexOf(q.toLowerCase())
  if (index === -1) return [{ text: value, match: false }]

  const parts = []
  if (index > 0) parts.push({ text: value.slice(0, index), match: false })
  parts.push({ text: value.slice(index, index + q.length), match: true })
  if (index + q.length < value.length) parts.push({ text: value.slice(index + q.length), match: false })

  return parts
}
