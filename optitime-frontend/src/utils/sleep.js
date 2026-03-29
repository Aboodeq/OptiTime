export function sleep(duration = 0) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, duration)
  })
}
