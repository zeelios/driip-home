const KEY = 'panel_theme'
export type Theme = 'dark' | 'light'

export function useTheme () {
  const theme = useState<Theme>('theme', () =>
    (import.meta.client ? (localStorage.getItem(KEY) as Theme) : null) ?? 'dark'
  )
  function apply (t: Theme) {
    if (!import.meta.client) return
    document.documentElement.classList.toggle('dark',  t === 'dark')
    document.documentElement.classList.toggle('light', t === 'light')
  }
  function toggle () {
    theme.value = theme.value === 'dark' ? 'light' : 'dark'
    localStorage.setItem(KEY, theme.value)
    apply(theme.value)
  }
  function init () { apply(theme.value) }
  return { theme, toggle, init }
}
