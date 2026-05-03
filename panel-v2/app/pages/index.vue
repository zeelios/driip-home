<template>
  <div class="dash">
    <!-- Page header -->
    <div class="page-header">
      <div>
        <p class="page-eyebrow">{{ greeting }}</p>
        <h1 class="page-title">Dashboard</h1>
      </div>
      <PBtn @click="store.fetch()" :loading="store.loading" variant="ghost" size="xs">
        Làm mới
      </PBtn>
    </div>

    <!-- Stat cards skeleton -->
    <div v-if="store.loading && !store.stats" class="stats-grid">
      <div v-for="i in 5" :key="i" class="skeleton stat-card-skeleton" />
    </div>

    <!-- Stat cards -->
    <div v-else class="stats-grid">
      <div class="stat-card">
        <p class="stat-card__label">Đơn hôm nay</p>
        <p class="stat-card__value">{{ store.stats?.orders_today ?? 0 }}</p>
        <div class="stat-card__icon stat-card__icon--blue">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
        </div>
      </div>

      <div class="stat-card">
        <p class="stat-card__label">Chờ xử lý</p>
        <p class="stat-card__value" :class="{ 'stat-card__value--warn': (store.stats?.orders_pending ?? 0) > 5 }">
          {{ store.stats?.orders_pending ?? 0 }}
        </p>
        <div class="stat-card__icon stat-card__icon--amber">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
      </div>

      <div class="stat-card">
        <p class="stat-card__label">Doanh thu hôm nay</p>
        <p class="stat-card__value stat-card__value--sm">{{ formatVND(store.stats?.revenue_today_cents ?? 0) }}</p>
        <div class="stat-card__icon stat-card__icon--green">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
      </div>

      <div class="stat-card">
        <p class="stat-card__label">Tổng đơn (lấy được)</p>
        <p class="stat-card__value">{{ store.stats?.orders_total ?? 0 }}</p>
        <div class="stat-card__icon stat-card__icon--muted">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
      </div>

      <NuxtLink to="/inventory" class="stat-card stat-card--link"
        :class="{ 'stat-card--danger': (store.stats?.low_stock_count ?? 0) > 0 }">
        <p class="stat-card__label">Sắp hết hàng</p>
        <p class="stat-card__value" :class="{ 'stat-card__value--danger': (store.stats?.low_stock_count ?? 0) > 0 }">
          {{ store.stats?.low_stock_count ?? 0 }}
        </p>
        <div class="stat-card__icon" :class="(store.stats?.low_stock_count ?? 0) > 0 ? 'stat-card__icon--red' : 'stat-card__icon--muted'">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
      </NuxtLink>
    </div>

    <!-- Charts row -->
    <div class="charts-row">
      <!-- Orders by status donut -->
      <div class="chart-card">
        <p class="chart-card__title">Trạng thái đơn hàng</p>
        <div class="chart-card__canvas-wrap">
          <canvas ref="donutCanvas" />
        </div>
      </div>

      <!-- Recent orders bar (last 7 days) -->
      <div class="chart-card chart-card--wide">
        <p class="chart-card__title">Đơn hàng — 7 ngày gần nhất</p>
        <div class="chart-card__canvas-wrap">
          <canvas ref="lineCanvas" />
        </div>
      </div>
    </div>

    <!-- Recent orders table -->
    <div class="section-card">
      <div class="section-card__header">
        <p class="section-card__title">Đơn hàng gần đây</p>
        <NuxtLink to="/orders" class="section-card__link">Xem tất cả →</NuxtLink>
      </div>

      <div v-if="store.loading" class="table-skeleton">
        <div v-for="i in 5" :key="i" class="skeleton table-row-skeleton" />
      </div>

      <div v-else-if="store.recentOrders.length === 0" class="empty-state">
        Chưa có đơn hàng nào.
      </div>

      <div v-else class="order-table-wrap">
        <table class="order-table">
          <thead>
            <tr>
              <th>ID</th>
              <th class="hide-xs">Ngày</th>
              <th>Trạng thái</th>
              <th class="hide-xs">Ưu tiên</th>
              <th class="text-right">Tổng</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in store.recentOrders" :key="order.id"
              class="order-row"
              @click="navigateTo(`/orders/${order.id}`)">
              <td class="order-id">#{{ order.id?.slice(0,8).toUpperCase() }}</td>
              <td class="hide-xs order-date">{{ formatDate(order.created_at) }}</td>
              <td><PBadge :status="order.status" /></td>
              <td class="hide-xs"><PBadge :status="order.priority ?? 'normal'" /></td>
              <td class="text-right order-total">{{ formatVND(order.grand_total_cents ?? 0) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Low stock alerts -->
    <div v-if="store.lowStockItems.length > 0" class="section-card section-card--danger">
      <div class="section-card__header">
        <p class="section-card__title">⚠ Sắp hết hàng</p>
        <NuxtLink to="/inventory" class="section-card__link">Quản lý kho →</NuxtLink>
      </div>
      <div class="low-stock-list">
        <div v-for="item in store.lowStockItems" :key="item.id" class="low-stock-row">
          <span class="low-stock-name">{{ item.product_name ?? item.product_id }}</span>
          <span class="low-stock-qty" :class="{ 'low-stock-qty--red': item.quantity <= 5 }">
            {{ item.quantity }} còn lại
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const store = useDashboardStore()

onMounted(() => store.fetch())

// ── Charts ───────────────────────────────────────────────────────────
const donutCanvas = ref<HTMLCanvasElement | null>(null)
const lineCanvas  = ref<HTMLCanvasElement | null>(null)

const getTextColor  = () => getComputedStyle(document.documentElement).getPropertyValue('--text-mute').trim()
const getBorderColor= () => getComputedStyle(document.documentElement).getPropertyValue('--border').trim()

function donutConfig () {
  const orders = store.recentOrders
  const counts = {
    pending: orders.filter(o => o.status === 'pending').length,
    confirmed: orders.filter(o => o.status === 'confirmed').length,
    shipped: orders.filter(o => o.status === 'shipped').length,
    delivered: orders.filter(o => o.status === 'delivered').length,
    cancelled: orders.filter(o => o.status === 'cancelled').length,
  }
  return {
    type: 'doughnut' as const,
    data: {
      labels: ['Chờ', 'Xác nhận', 'Đang giao', 'Đã giao', 'Hủy'],
      datasets: [{
        data: Object.values(counts),
        backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#22c55e', '#ef4444'],
        borderWidth: 0, hoverOffset: 4,
      }],
    },
    options: {
      cutout: '70%', responsive: true, maintainAspectRatio: true,
      plugins: {
        legend: { position: 'bottom' as const, labels: { color: getTextColor(), font: { size: 11 }, padding: 12, boxWidth: 10 } },
        tooltip: { callbacks: { label: (ctx: any) => ` ${ctx.label}: ${ctx.raw}` } },
      },
    },
  }
}

function lineConfig () {
  // Build last-7-days buckets from recentOrders
  const days: string[] = []
  const counts: number[] = []
  for (let i = 6; i >= 0; i--) {
    const d = new Date(); d.setDate(d.getDate() - i)
    days.push(d.toLocaleDateString('vi-VN', { weekday: 'short', day: 'numeric' }))
    const ds = d.toDateString()
    counts.push(store.recentOrders.filter(o => new Date(o.created_at).toDateString() === ds).length)
  }
  return {
    type: 'line' as const,
    data: {
      labels: days,
      datasets: [{
        label: 'Đơn hàng',
        data: counts,
        borderColor: '#ffffff',
        backgroundColor: 'rgba(255,255,255,0.06)',
        pointBackgroundColor: '#ffffff',
        pointRadius: 3, borderWidth: 1.5, tension: 0.35, fill: true,
      }],
    },
    options: {
      responsive: true, maintainAspectRatio: true,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx: any) => ` ${ctx.raw} đơn` } },
      },
      scales: {
        x: { ticks: { color: getTextColor(), font: { size: 10 } }, grid: { color: getBorderColor() } },
        y: { ticks: { color: getTextColor(), font: { size: 10 }, stepSize: 1 }, grid: { color: getBorderColor() }, beginAtZero: true },
      },
    },
  }
}

const donut = useChart(donutCanvas, donutConfig)
const line  = useChart(lineCanvas,  lineConfig)

watch(() => store.recentOrders, () => { donut.update(); line.update() }, { deep: true })

// ── Helpers ──────────────────────────────────────────────────────────
const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Chào buổi sáng'
  if (h < 18) return 'Chào buổi chiều'
  return 'Chào buổi tối'
})

function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}
function formatDate (iso: string) {
  return new Date(iso).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
}
</script>

<style scoped>
.dash { display: flex; flex-direction: column; gap: 1.25rem; }

/* ── Page header ─────────────────────────────────────────────────────── */
.page-header {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
  margin-bottom: 0.25rem;
}
.page-eyebrow {
  font-size: 0.68rem; font-weight: 600; letter-spacing: 0.12em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.2rem;
}
.page-title {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.75rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.04em; color: var(--text);
}

/* ── Stat cards ──────────────────────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}
@media (min-width: 640px)  { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1024px) { .stats-grid { grid-template-columns: repeat(5, 1fr); } }

.stat-card {
  position: relative;
  background: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.75rem; padding: 1rem;
  overflow: hidden;
}
.stat-card--link { text-decoration: none; color: inherit; transition: border-color 0.15s; }
.stat-card--link:hover { border-color: var(--border-hi); }
.stat-card--danger { border-color: rgba(239,68,68,0.35); }

.stat-card__label {
  font-size: 0.68rem; font-weight: 600; letter-spacing: 0.08em;
  text-transform: uppercase; color: var(--text-mute); margin-bottom: 0.5rem;
}
.stat-card__value {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 2rem; font-weight: 700; line-height: 1; color: var(--text);
}
.stat-card__value--sm   { font-size: 1.3rem; }
.stat-card__value--warn { color: var(--status-pending-t); }
.stat-card__value--danger { color: var(--status-danger-t); }

.stat-card__icon {
  position: absolute; top: 0.875rem; right: 0.875rem;
  width: 2rem; height: 2rem; border-radius: 0.5rem;
  display: flex; align-items: center; justify-content: center;
  opacity: 0.8;
}
.stat-card__icon svg { width: 1rem; height: 1rem; }
.stat-card__icon--blue  { background: var(--status-info);    color: var(--status-info-t); }
.stat-card__icon--amber { background: var(--status-pending);  color: var(--status-pending-t); }
.stat-card__icon--green { background: var(--status-ok);       color: var(--status-ok-t); }
.stat-card__icon--red   { background: var(--status-danger);   color: var(--status-danger-t); }
.stat-card__icon--muted { background: var(--status-muted);    color: var(--text-sub); }

.stat-card-skeleton { height: 90px; }

/* ── Charts ──────────────────────────────────────────────────────────── */
.charts-row {
  display: grid; grid-template-columns: 1fr;
  gap: 0.75rem;
}
@media (min-width: 768px) { .charts-row { grid-template-columns: 200px 1fr; } }

.chart-card {
  background: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.75rem; padding: 1rem;
}
.chart-card--wide {}
.chart-card__title {
  font-size: 0.72rem; font-weight: 600; letter-spacing: 0.06em;
  text-transform: uppercase; color: var(--text-sub); margin-bottom: 0.75rem;
}
.chart-card__canvas-wrap { position: relative; width: 100%; }

/* ── Section cards ───────────────────────────────────────────────────── */
.section-card {
  background: var(--bg-card); border: 1px solid var(--border);
  border-radius: 0.75rem; overflow: hidden;
}
.section-card--danger { border-color: rgba(239,68,68,0.3); }
.section-card__header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--border);
}
.section-card__title {
  font-size: 0.78rem; font-weight: 600; color: var(--text);
}
.section-card__link {
  font-size: 0.72rem; color: var(--text-mute); text-decoration: none;
  transition: color 0.12s;
}
.section-card__link:hover { color: var(--text-sub); }

/* ── Order table ─────────────────────────────────────────────────────── */
.order-table-wrap { overflow-x: auto; }
.order-table {
  width: 100%; border-collapse: collapse; font-size: 0.8rem;
}
.order-table th {
  padding: 0.5rem 1rem; text-align: left;
  font-size: 0.65rem; font-weight: 600; letter-spacing: 0.08em;
  text-transform: uppercase; color: var(--text-mute);
  border-bottom: 1px solid var(--border);
}
.order-table td { padding: 0.625rem 1rem; border-bottom: 1px solid var(--border); }
.order-table tr:last-child td { border-bottom: none; }
.order-row { cursor: pointer; transition: background 0.12s; }
.order-row:hover { background: var(--bg-hover); }

.order-id { font-family: monospace; font-size: 0.75rem; color: var(--text-sub); }
.order-date { color: var(--text-mute); }
.order-total { font-weight: 600; }
.text-right { text-align: right; }

.hide-xs { display: none; }
@media (min-width: 480px) { .hide-xs { display: table-cell; } }

/* ── Low stock ───────────────────────────────────────────────────────── */
.low-stock-list { padding: 0.25rem 0; }
.low-stock-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.5rem 1rem; border-bottom: 1px solid var(--border);
  font-size: 0.8rem;
}
.low-stock-row:last-child { border-bottom: none; }
.low-stock-name  { color: var(--text); }
.low-stock-qty   { color: var(--status-pending-t); font-weight: 600; }
.low-stock-qty--red { color: var(--status-danger-t); }

/* ── Misc ────────────────────────────────────────────────────────────── */
.table-skeleton { padding: 0.5rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
.table-row-skeleton { height: 36px; }
.empty-state { padding: 2rem; text-align: center; font-size: 0.875rem; color: var(--text-mute); }
</style>
