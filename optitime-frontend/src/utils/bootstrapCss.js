import bootstrapLtrUrl from 'bootstrap/dist/css/bootstrap.min.css?url'
import bootstrapRtlUrl from 'bootstrap/dist/css/bootstrap.rtl.min.css?url'

const BOOTSTRAP_LINK_ID = 'bootstrap-css'

function ensureBootstrapLink() {
  const existing = document.getElementById(BOOTSTRAP_LINK_ID)
  if (existing instanceof HTMLLinkElement) return existing

  const link = document.createElement('link')
  link.id = BOOTSTRAP_LINK_ID
  link.rel = 'stylesheet'

  const head = document.head || document.getElementsByTagName('head')[0]
  const firstStylesheet = head.querySelector('link[rel="stylesheet"], style')
  if (firstStylesheet) head.insertBefore(link, firstStylesheet)
  else head.appendChild(link)

  return link
}

export function applyBootstrapCss(locale) {
  if (typeof document === 'undefined') return

  const link = ensureBootstrapLink()
  link.href = locale === 'en' ? bootstrapLtrUrl : bootstrapRtlUrl
}
