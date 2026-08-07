// Splits text into { text, match } parts around the first case-insensitive occurrence of query.
// Rendered as plain text nodes (no v-html), so there's no HTML-injection risk from either input.
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
