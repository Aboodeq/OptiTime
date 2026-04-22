function readErrorBag(error) {
  const bag = error?.data?.errors
  return bag && typeof bag === 'object' ? bag : {}
}

export function getApiFieldError(error, field) {
  const messages = readErrorBag(error)[field]
  return Array.isArray(messages) && typeof messages[0] === 'string' ? messages[0] : ''
}

export function getApiMessage(error) {
  if (typeof error?.data?.message === 'string' && error.data.message.trim()) {
    return error.data.message
  }

  if (typeof error?.message === 'string' && error.message.trim()) {
    return error.message
  }

  return ''
}
