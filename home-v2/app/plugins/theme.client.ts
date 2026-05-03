// Apply saved theme on first client paint to avoid flash
export default defineNuxtPlugin(() => {
  const { init } = useTheme()
  init()
})
