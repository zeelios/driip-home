// Persists dark/light preference in localStorage + syncs <html> class
const THEME_KEY = 'driip_theme'

export type Theme = 'dark' | 'light'

export function useTheme () {
  const theme = useState<Theme>('theme', () => {
    if (typeof window === 'undefined') return 'dark'
    return (localStorage.getItem(THEME_KEY) as Theme) || 'dark'
  })

  function apply (t: Theme) {
    if (typeof document === 'undefined') return
    document.documentElement.classList.toggle('dark', t === 'dark')
    document.documentElement.classList.toggle('light', t === 'light')
  }

  function toggle () {
    theme.value = theme.value === 'dark' ? 'light' : 'dark'
    localStorage.setItem(THEME_KEY, theme.value)
    apply(theme.value)
  }

  function init () {
    apply(theme.value)
  }

  return { theme, toggle, init }
}
