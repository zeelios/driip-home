// composables/useGuestOrder.ts
// Persist guest order public_token in a long-lived cookie so users can track orders after closing the browser.

const GUEST_ORDER_KEY = 'driip_guest_order_token'

export function useGuestOrder () {
  function setToken (token: string) {
    const cookie = useCookie(GUEST_ORDER_KEY, { maxAge: 60 * 60 * 24 * 30 }) // 30 days
    cookie.value = token
  }

  function getToken (): string | null | undefined {
    const cookie = useCookie(GUEST_ORDER_KEY)
    return cookie.value
  }

  function clearToken () {
    const cookie = useCookie(GUEST_ORDER_KEY, { maxAge: 0 })
    cookie.value = null
  }

  return {
    setToken,
    getToken,
    clearToken,
  }
}
