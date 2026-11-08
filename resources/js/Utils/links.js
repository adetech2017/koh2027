// A link is "external" when it points to another website (absolute http(s) URL on a different host).
// Relative links (/events, #section), mailto: and tel: count as internal.
export function isExternalUrl(href) {
  if (!href || !/^https?:\/\//i.test(href)) return false
  try {
    return new URL(href).host !== window.location.host
  } catch {
    return false
  }
}

// Editor-made links store an explicit target: "_blank" (new tab) or "_self" (same tab).
// Links without one (pasted URLs, older content) open external sites in a new tab.
export function applyLinkTargets(html) {
  const tpl = document.createElement('template')
  tpl.innerHTML = html
  tpl.content.querySelectorAll('a[href]').forEach((a) => {
    let target = a.getAttribute('target')
    if (target !== '_blank' && target !== '_self') {
      target = isExternalUrl(a.getAttribute('href')) ? '_blank' : '_self'
    }
    if (target === '_blank') {
      a.setAttribute('target', '_blank')
      a.setAttribute('rel', 'noopener noreferrer')
    } else {
      a.removeAttribute('target')
    }
  })
  return tpl.innerHTML
}
