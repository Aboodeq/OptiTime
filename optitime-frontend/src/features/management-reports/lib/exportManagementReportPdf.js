import html2canvas from 'html2canvas'
import { jsPDF } from 'jspdf'

/**
 * @param {string} raw
 * @returns {string}
 */
export function sanitizeReportFilenamePart(raw) {
  const s = String(raw ?? 'report').trim() || 'report'
  return s.replace(/[<>:"/\\|?*\u0000-\u001F]+/g, '-').replace(/\s+/g, '-').slice(0, 96)
}

/**
 * Rasterize a DOM subtree and save multi-page A4 PDF (JPEG).
 * @param {HTMLElement} element
 * @param {string} filenameBase — sanitized slug fragment (semester label or id)
 */
export async function downloadDomAsPdf(element, filenameBase) {
  if (!element || typeof document === 'undefined') {
    throw new Error('export: missing element')
  }

  const canvas = await html2canvas(element, {
    scale: Math.min(2, window.devicePixelRatio > 1 ? 2 : 1.5),
    useCORS: true,
    logging: false,
    backgroundColor: '#ffffff',
    windowWidth: element.scrollWidth,
    windowHeight: element.scrollHeight,
    scrollX: 0,
    scrollY: -window.scrollY,
    onclone(clonedDoc) {
      clonedDoc.querySelectorAll('.management-reports-pdf-exclude').forEach((node) => node.remove())
      clonedDoc.querySelectorAll('.app-autocomplete-field__menu').forEach((node) => node.remove())
    },
  })

  const imgData = canvas.toDataURL('image/jpeg', 0.92)
  const pdf = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
    compress: true,
  })

  const pageWidth = pdf.internal.pageSize.getWidth()
  const pageHeight = pdf.internal.pageSize.getHeight()
  const marginX = 10
  const imgWidth = pageWidth - 2 * marginX

  const imgHeight = (canvas.height * imgWidth) / canvas.width
  let heightLeft = imgHeight
  let position = marginX

  pdf.addImage(imgData, 'JPEG', marginX, position, imgWidth, imgHeight)
  heightLeft -= pageHeight

  while (heightLeft > 0) {
    position = heightLeft - imgHeight
    pdf.addPage()
    pdf.addImage(imgData, 'JPEG', marginX, position, imgWidth, imgHeight)
    heightLeft -= pageHeight
  }

  const safe = sanitizeReportFilenamePart(filenameBase)
  const day = new Date().toISOString().slice(0, 10)
  pdf.save(`optitime-management-report-${safe}-${day}.pdf`)
}
