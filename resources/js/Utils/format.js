// "press_release" -> "Press Release", "t-shirt" -> "T-Shirt"
export const categoryLabel = (value) =>
  (value || '').replace(/_/g, ' ').replace(/(^|[\s-])\w/g, (m) => m.toUpperCase())

// e.g. "9 October 2026"
export const longDate = (iso) =>
  iso ? new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : ''

export const fileSize = (bytes) => {
  if (!bytes) return '0 KB'
  const kb = bytes / 1024
  return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`
}

// Formats a price in its own currency, e.g. "₦2,500" or "$15"
export const money = (amount, currency = 'NGN') => {
  const value = Number(amount)
  if (Number.isNaN(value)) return ''
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: currency || 'NGN',
    maximumFractionDigits: value % 1 === 0 ? 0 : 2,
  }).format(value)
}
