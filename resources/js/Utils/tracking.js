// First-party click tracking for the public site. Sends small beacons to /t/e;
// the server ignores signed-in staff, bots and Do-Not-Track browsers.

const SHARE_HOSTS = /(^|\.)(facebook\.com|twitter\.com|x\.com|linkedin\.com|wa\.me|whatsapp\.com|t\.me|telegram\.me)$/i
const SHARE_PATHS = /share|sharer|intent|send|msg/i

const send = (name, label) => {
  try {
    const data = new FormData()
    data.append('name', name)
    if (label) data.append('label', String(label).slice(0, 255))
    data.append('path', window.location.pathname.slice(0, 255))
    if (!navigator.sendBeacon?.('/t/e', data)) {
      fetch('/t/e', { method: 'POST', body: data, keepalive: true, credentials: 'same-origin' }).catch(() => {})
    }
  } catch {
    // Tracking must never interfere with the click
  }
}

// Work out what kind of click this is from the link itself, so components don't need annotating.
// An explicit data-track="name" (optionally with data-track-label) always wins.
const classify = (el) => {
  if (el.dataset.track) {
    return [el.dataset.track, el.dataset.trackLabel || el.textContent.trim()]
  }

  const href = el.getAttribute('href') || ''
  if (/^(mailto|tel):/i.test(href)) return ['contact_click', href.replace(/^(mailto|tel):/i, '').split('?')[0]]

  let url
  try {
    url = new URL(href, window.location.href)
  } catch {
    return null
  }
  if (url.origin === window.location.origin) return null

  if (/hamzatforlagos\.com$/i.test(url.hostname) && /volunteer/i.test(url.pathname)) {
    return ['volunteer_click', el.textContent.trim() || 'Volunteer']
  }
  if (SHARE_HOSTS.test(url.hostname) && SHARE_PATHS.test(url.pathname + url.search)) {
    return ['share', url.hostname.replace(/^www\./, '')]
  }
  return ['outbound_click', url.hostname.replace(/^www\./, '') + url.pathname.replace(/\/$/, '')]
}

export function installClickTracking() {
  document.addEventListener('click', (event) => {
    if (window.location.pathname.startsWith('/admin')) return
    const el = event.target.closest?.('a[href], [data-track]')
    if (!el) return
    const result = classify(el)
    if (result) send(...result)
  }, { capture: true })
}
