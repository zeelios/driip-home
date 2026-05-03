<!--
  Mobile: fixed bottom tab bar (5 primary items) + hamburger → full drawer
  Desktop: fixed left sidebar (var(--sidebar-w) wide)
-->
<template>
  <!-- Desktop sidebar -->
  <aside class="sidebar">
    <div class="sidebar__logo">
      <span class="sidebar__brand">driip-</span>
      <span class="sidebar__sub">Panel</span>
    </div>

    <nav class="sidebar__nav">
      <NuxtLink v-for="item in navItems" :key="item.to" :to="item.to"
        class="sidebar__link"
        :class="{ 'sidebar__link--active': isActive(item.to) }">
        <component :is="item.icon" class="sidebar__icon" />
        <span>{{ item.label }}</span>
        <span v-if="item.badge" class="sidebar__badge">{{ item.badge }}</span>
      </NuxtLink>
    </nav>

    <div class="sidebar__footer">
      <div class="sidebar__user">
        <div class="sidebar__avatar">{{ auth.staff?.name?.[0]?.toUpperCase() ?? '?' }}</div>
        <div class="sidebar__user-info">
          <p class="sidebar__user-name">{{ auth.staff?.name }}</p>
          <p class="sidebar__user-role">{{ auth.staff?.role }}</p>
        </div>
      </div>
      <div class="sidebar__actions">
        <button @click="theme.toggle()" class="sidebar__action-btn" :title="theme.theme.value === 'dark' ? 'Light mode' : 'Dark mode'">
          <SunIcon v-if="theme.theme.value === 'dark'" />
          <MoonIcon v-else />
        </button>
        <button @click="auth.logout()" class="sidebar__action-btn" title="Đăng xuất">
          <LogoutIcon />
        </button>
      </div>
    </div>
  </aside>

  <!-- Mobile bottom bar -->
  <nav class="mobile-bar">
    <NuxtLink v-for="item in mobileItems" :key="item.to" :to="item.to"
      class="mobile-bar__item"
      :class="{ 'mobile-bar__item--active': isActive(item.to) }">
      <component :is="item.icon" class="mobile-bar__icon" />
      <span class="mobile-bar__label">{{ item.label }}</span>
    </NuxtLink>
    <button class="mobile-bar__item" @click="drawerOpen = true">
      <MenuIcon class="mobile-bar__icon" />
      <span class="mobile-bar__label">Thêm</span>
    </button>
  </nav>

  <!-- Mobile drawer -->
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="drawerOpen" class="drawer-overlay" @click="drawerOpen = false" />
    </Transition>
    <Transition name="drawer">
      <div v-if="drawerOpen" class="drawer">
        <div class="drawer__header">
          <span class="sidebar__brand">driip- Panel</span>
          <button @click="drawerOpen = false" class="drawer__close">
            <XIcon />
          </button>
        </div>
        <nav class="drawer__nav">
          <NuxtLink v-for="item in navItems" :key="item.to" :to="item.to"
            class="drawer__link"
            :class="{ 'drawer__link--active': isActive(item.to) }"
            @click="drawerOpen = false">
            <component :is="item.icon" class="sidebar__icon" />
            {{ item.label }}
          </NuxtLink>
        </nav>
        <div class="drawer__footer">
          <div class="sidebar__user">
            <div class="sidebar__avatar">{{ auth.staff?.name?.[0]?.toUpperCase() ?? '?' }}</div>
            <div class="sidebar__user-info">
              <p class="sidebar__user-name">{{ auth.staff?.name }}</p>
              <p class="sidebar__user-role">{{ auth.staff?.role }}</p>
            </div>
          </div>
          <div class="sidebar__actions">
            <button @click="theme.toggle()" class="sidebar__action-btn">
              <SunIcon v-if="theme.theme.value === 'dark'" />
              <MoonIcon v-else />
            </button>
            <button @click="auth.logout()" class="sidebar__action-btn">
              <LogoutIcon />
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
const auth   = useAuthStore()
const theme  = useTheme()
const route  = useRoute()
const drawerOpen = ref(false)

// Close drawer on navigation
watch(() => route.path, () => { drawerOpen.value = false })

function isActive (to: string) {
  if (to === '/') return route.path === '/'
  return route.path.startsWith(to)
}

// ── Icon components (inline SVG for zero-dependency) ─────────────────
const DashIcon   = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M3 7h18M3 12h18M3 17h18' })]) })
const OrderIcon  = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' })]) })
const UserIcon   = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' })]) })
const ProdIcon   = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' })]) })
const InvIcon    = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' })]) })
const WhsIcon    = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })]) })
const ShipIcon   = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4' })]) })
const StaffIcon  = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' })]) })
const SunIcon    = defineComponent({ render: () => h('svg', { style: 'width:1rem;height:1rem', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('circle', { cx: '12', cy: '12', r: '5', 'stroke-width': '1.5' }), h('path', { 'stroke-linecap': 'round', 'stroke-width': '1.5', d: 'M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42' })]) })
const MoonIcon   = defineComponent({ render: () => h('svg', { style: 'width:1rem;height:1rem', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z' })]) })
const LogoutIcon = defineComponent({ render: () => h('svg', { style: 'width:1rem;height:1rem', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' })]) })
const MenuIcon   = defineComponent({ render: () => h('svg', { class: 'nav-icon', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M4 6h16M4 12h16M4 18h16' })]) })
const XIcon      = defineComponent({ render: () => h('svg', { style: 'width:1.25rem;height:1.25rem', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '1.5', d: 'M6 18L18 6M6 6l12 12' })]) })

const navItems = [
  { to: '/',               label: 'Dashboard',     icon: DashIcon  },
  { to: '/orders',         label: 'Đơn hàng',      icon: OrderIcon },
  { to: '/customers',      label: 'Khách hàng',    icon: UserIcon  },
  { to: '/products',       label: 'Sản phẩm',      icon: ProdIcon  },
  { to: '/inventory',      label: 'Kho hàng',      icon: InvIcon   },
  { to: '/warehouses',     label: 'Kho vật lý',    icon: WhsIcon   },
  { to: '/fulfillment',    label: 'Vận chuyển',     icon: ShipIcon  },
  { to: '/staff',          label: 'Nhân viên',     icon: StaffIcon },
  { to: '/purchase-orders',label: 'Nhập hàng',     icon: InvIcon   },
]
const mobileItems = navItems.slice(0, 4)
</script>

<style scoped>
/* ── Desktop sidebar ─────────────────────────────────────────────────── */
.sidebar {
  display: none;
  position: fixed; top: 0; left: 0; bottom: 0;
  width: var(--sidebar-w);
  background: var(--bg-raised);
  border-right: 1px solid var(--border);
  flex-direction: column;
  z-index: 20;
  overflow-y: auto;
}
@media (min-width: 768px) { .sidebar { display: flex; } }

.sidebar__logo {
  display: flex; align-items: baseline; gap: 0.4rem;
  padding: 1.25rem 1rem 1rem;
  border-bottom: 1px solid var(--border);
}
.sidebar__brand {
  font-family: "Barlow Condensed", ui-sans-serif, sans-serif;
  font-size: 1.1rem; font-weight: 700; letter-spacing: 0.12em;
  text-transform: uppercase; color: var(--text);
}
.sidebar__sub {
  font-size: 0.6rem; font-weight: 600; letter-spacing: 0.15em;
  text-transform: uppercase; color: var(--text-mute);
}

.sidebar__nav { flex: 1; padding: 0.75rem 0.5rem; display: flex; flex-direction: column; gap: 0.125rem; }

.sidebar__link {
  display: flex; align-items: center; gap: 0.625rem;
  padding: 0.5rem 0.625rem; border-radius: 0.375rem;
  font-size: 0.8rem; color: var(--text-sub); text-decoration: none;
  transition: background 0.12s, color 0.12s; position: relative;
}
.sidebar__link:hover { background: var(--bg-hover); color: var(--text); }
.sidebar__link--active { background: var(--bg-hover); color: var(--text); font-weight: 600; }
.sidebar__link--active::before {
  content: ''; position: absolute; left: 0; top: 25%; bottom: 25%;
  width: 2px; border-radius: 999px; background: var(--accent);
}
.sidebar__icon { width: 1rem; height: 1rem; flex-shrink: 0; }
.sidebar__badge {
  margin-left: auto; font-size: 0.6rem; font-weight: 700;
  padding: 0.1rem 0.4rem; background: var(--status-pending);
  color: var(--status-pending-t); border-radius: 999px;
}

.sidebar__footer {
  border-top: 1px solid var(--border);
  padding: 0.75rem 0.625rem;
  display: flex; flex-direction: column; gap: 0.625rem;
}
.sidebar__user { display: flex; align-items: center; gap: 0.625rem; min-width: 0; }
.sidebar__avatar {
  width: 2rem; height: 2rem; border-radius: 0.375rem;
  background: var(--bg-hover); border: 1px solid var(--border-hi);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.8rem; font-weight: 700; color: var(--text); flex-shrink: 0;
}
.sidebar__user-info { min-width: 0; }
.sidebar__user-name {
  font-size: 0.78rem; font-weight: 600; color: var(--text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.sidebar__user-role {
  font-size: 0.65rem; color: var(--text-mute); text-transform: uppercase; letter-spacing: 0.06em;
}
.sidebar__actions { display: flex; gap: 0.375rem; }
.sidebar__action-btn {
  width: 1.75rem; height: 1.75rem; border-radius: 0.375rem;
  background: none; border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: var(--text-sub); transition: color 0.12s, background 0.12s;
}
.sidebar__action-btn:hover { color: var(--text); background: var(--bg-hover); }

/* ── Mobile bottom bar ───────────────────────────────────────────────── */
.mobile-bar {
  position: fixed; bottom: 0; left: 0; right: 0;
  background: var(--bg-raised); border-top: 1px solid var(--border);
  display: flex; z-index: 40;
  padding-bottom: env(safe-area-inset-bottom, 0);
}
@media (min-width: 768px) { .mobile-bar { display: none; } }

.mobile-bar__item {
  flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 0.2rem; padding: 0.5rem 0;
  background: none; border: none; cursor: pointer; text-decoration: none;
  color: var(--text-mute); transition: color 0.12s;
  -webkit-tap-highlight-color: transparent;
}
.mobile-bar__item--active { color: var(--text); }
.mobile-bar__icon { width: 1.25rem; height: 1.25rem; }
.mobile-bar__label { font-size: 0.6rem; font-weight: 500; letter-spacing: 0.04em; }

/* ── Mobile drawer ───────────────────────────────────────────────────── */
.drawer-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 50;
}
.drawer {
  position: fixed; top: 0; left: 0; bottom: 0; width: min(80vw, 280px);
  background: var(--bg-raised); border-right: 1px solid var(--border);
  display: flex; flex-direction: column; z-index: 60; overflow-y: auto;
}
.drawer__header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem; border-bottom: 1px solid var(--border);
}
.drawer__close {
  background: none; border: none; cursor: pointer; color: var(--text-sub); padding: 0.25rem;
}
.drawer__nav {
  flex: 1; padding: 0.75rem 0.5rem; display: flex; flex-direction: column; gap: 0.125rem;
}
.drawer__link {
  display: flex; align-items: center; gap: 0.625rem;
  padding: 0.625rem 0.75rem; border-radius: 0.375rem;
  font-size: 0.875rem; color: var(--text-sub); text-decoration: none;
  transition: background 0.12s, color 0.12s;
}
.drawer__link:hover, .drawer__link--active {
  background: var(--bg-hover); color: var(--text); font-weight: 600;
}
.drawer__footer { border-top: 1px solid var(--border); padding: 0.75rem; }

/* Transitions */
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s; }
.overlay-enter-from,  .overlay-leave-to      { opacity: 0; }
.drawer-enter-active, .drawer-leave-active   { transition: transform 0.25s cubic-bezier(0.32,0.72,0,1); }
.drawer-enter-from,   .drawer-leave-to       { transform: translateX(-100%); }
</style>
