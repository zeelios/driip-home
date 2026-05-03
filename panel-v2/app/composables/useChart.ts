// Thin Chart.js wrapper — registers only what we use to keep bundle small
import type { ChartConfiguration, ChartType } from 'chart.js'

let registered = false

async function ensureChartJs () {
  if (registered) return
  const {
    Chart, LineElement, PointElement, LinearScale, CategoryScale,
    ArcElement, DoughnutController, LineController,
    Tooltip, Legend, Filler,
  } = await import('chart.js')
  Chart.register(
    LineElement, PointElement, LinearScale, CategoryScale,
    ArcElement, DoughnutController, LineController,
    Tooltip, Legend, Filler,
  )
  registered = true
}

export function useChart (
  canvasRef: Ref<HTMLCanvasElement | null>,
  getConfig: () => ChartConfiguration<any>,
) {
  let instance: import('chart.js').Chart | null = null

  async function mount () {
    if (!canvasRef.value) return
    await ensureChartJs()
    const { Chart } = await import('chart.js')
    instance?.destroy()
    instance = new Chart(canvasRef.value, getConfig())
  }

  async function update () {
    if (!instance) { await mount(); return }
    const cfg = getConfig()
    instance.data    = cfg.data
    instance.options = cfg.options ?? {}
    instance.update('none')
  }

  onMounted(mount)
  onUnmounted(() => instance?.destroy())

  return { mount, update }
}
