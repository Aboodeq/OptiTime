export function useLocalStorage(key) {
  function read(defaultValue = null) {
    if (typeof window === 'undefined') return defaultValue

    try {
      const value = window.localStorage.getItem(key)
      return value ? JSON.parse(value) : defaultValue
    } catch {
      return defaultValue
    }
  }

  function write(value) {
    if (typeof window === 'undefined') return

    try {
      if (value === null || value === undefined) {
        window.localStorage.removeItem(key)
        return
      }

      window.localStorage.setItem(key, JSON.stringify(value))
    } catch {
      // Ignore storage failures and keep the app functional.
    }
  }

  function remove() {
    if (typeof window === 'undefined') return

    try {
      window.localStorage.removeItem(key)
    } catch {
      // Ignore storage failures and keep the app functional.
    }
  }

  return { read, write, remove }
}
