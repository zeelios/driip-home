<template>
  <div class="flex min-h-screen bg-[#0a0a0a] w-full overflow-x-hidden">
    <!-- Mobile overlay -->
    <ClientOnly>
      <Transition name="overlay">
        <div
          v-if="sidebarOpen"
          class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden"
          @click="sidebarOpen = false"
        />
      </Transition>
    </ClientOnly>

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 w-60 flex flex-col bg-[#111110] transition-transform duration-220 ease-out max-w-[85vw]"
      :class="
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      "
    >
      <!-- Logo -->
      <div
        class="flex items-center justify-between px-4 py-5 border-b border-white/8"
      >
        <div class="flex items-center gap-2.5">
          <div
            class="w-7.5 h-7.5 bg-white flex items-center justify-center text-sm font-extrabold text-[#111110] rounded-md"
          >
            D
          </div>
          <span class="text-base font-bold text-white tracking-tight"
            >driip<span class="text-white">.</span></span
          >
        </div>
        <button
          class="flex items-center justify-center w-8 h-8 bg-transparent text-white/50 hover:bg-white/10 hover:text-white rounded-md transition-colors duration-150 lg:hidden"
          aria-label="Đóng menu"
          @click="sidebarOpen = false"
        >
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav
        class="flex-1 overflow-y-auto py-3 scrollbar-none"
        aria-label="Menu chính"
      >
        <div v-for="group in navigation" :key="group.label" class="py-2">
          <p
            class="px-4 py-1 text-[0.6875rem] font-semibold tracking-widest uppercase text-white/30"
          >
            {{ group.label }}
          </p>
          <ul class="m-0 p-0 list-none">
            <li v-for="item in group.items" :key="item.to">
              <NuxtLink
                :to="item.to"
                class="flex items-center gap-2.5 mx-2 my-px px-2.5 py-2.5 rounded-lg text-sm font-medium text-white/55 no-underline transition-colors duration-150 hover:bg-white/8 hover:text-white"
                :class="
                  isActive(item.to)
                    ? 'bg-white/10 text-white border-l-2 border-white'
                    : ''
                "
                @click="sidebarOpen = false"
              >
                <span
                  class="flex items-center shrink-0"
                  :class="isActive(item.to) ? 'opacity-100' : 'opacity-70'"
                  v-html="item.icon"
                />
                <span>{{ item.label }}</span>
              </NuxtLink>
            </li>
          </ul>
        </div>
      </nav>

      <!-- User footer -->
      <div class="p-3 border-t border-white/8 flex items-center gap-2">
        <div class="flex items-center gap-2.5 flex-1 min-w-0">
          <div
            class="w-8 h-8 rounded-full bg-white/15 text-white text-xs font-bold flex items-center justify-center shrink-0"
          >
            {{ userInitials }}
          </div>
          <div class="min-w-0">
            <p
              class="m-0 text-sm font-semibold text-white/90 whitespace-nowrap overflow-hidden text-ellipsis"
            >
              {{ auth.user?.name }}
            </p>
            <p
              class="m-0 text-xs text-white/40 whitespace-nowrap overflow-hidden text-ellipsis"
            >
              {{ userRole }}
            </p>
          </div>
        </div>
        <button
          class="flex items-center justify-center w-8 h-8 bg-transparent text-white/40 hover:bg-white/10 hover:text-white/80 rounded-md shrink-0 transition-colors duration-150 disabled:opacity-40 disabled:cursor-wait"
          :disabled="auth.logoutPending"
          aria-label="Đăng xuất"
          @click="signOut"
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"
            />
          </svg>
        </button>
      </div>
    </aside>

    <!-- Main content area -->
    <div
      class="flex flex-col flex-1 min-w-0 ml-0 lg:ml-60 transition-[margin-left] duration-220 ease-out"
    >
      <!-- Topbar -->
      <header
        class="sticky top-0 z-20 flex items-center gap-4 px-5 h-14 bg-[#0a0a0a]/92 backdrop-blur-md border-b border-white/8"
      >
        <button
          class="flex items-center justify-center w-9 h-9 bg-transparent text-white/60 hover:bg-white/10 hover:text-white rounded-lg transition-colors duration-150 lg:hidden"
          aria-label="Mở menu"
          @click="sidebarOpen = true"
        >
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M3 12h18M3 6h18M3 18h18" />
          </svg>
        </button>

        <div class="flex-1">
          <span class="text-base font-semibold text-white/90">{{
            currentPageTitle
          }}</span>
        </div>

        <div class="flex items-center gap-3">
          <div class="hidden lg:flex items-center gap-2">
            <div
              class="w-8 h-8 rounded-full bg-white/10 text-white text-xs font-bold flex items-center justify-center"
            >
              {{ userInitials }}
            </div>
            <div class="flex flex-col">
              <p class="m-0 text-sm font-semibold text-white/90 leading-tight">
                {{ auth.user?.name }}
              </p>
              <p class="m-0 text-xs text-white/40 leading-tight">
                {{ userRole }}
              </p>
            </div>
          </div>
          <!-- <button
            class="hidden lg:flex items-center gap-1.5 px-3 py-2 border border-white/15 bg-transparent text-white/70 text-sm font-medium hover:bg-white/10 hover:text-white hover:border-white/25 rounded-lg transition-colors duration-150 disabled:opacity-40 disabled:cursor-wait"
            :disabled="auth.logoutPending"
            @click="signOut"
          >
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"
              />
            </svg>
            Đăng xuất
          </button> -->
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-4 sm:p-5 lg:p-6 min-w-0">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from "~/stores/auth";

const auth = useAuthStore();
const route = useRoute();
const sidebarOpen = ref(false);

const ICON_DASHBOARD = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>`;
const ICON_ORDERS = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>`;
const ICON_CUSTOMERS = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`;
const ICON_PRODUCTS = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>`;
const ICON_INVENTORY = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z"/><path d="m3 9 2.45-4.9A2 2 0 0 1 7.24 3h9.52a2 2 0 0 1 1.8 1.1L21 9"/><path d="M12 3v6"/></svg>`;
const ICON_STAFF = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>`;
const ICON_COUPONS = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>`;
const ICON_FULFILLMENT = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>`;
const ICON_PURCHASE = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>`;
const ICON_PO_CREATE = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>`;
const ICON_SETTINGS = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>`;

const userInitials = computed(() => {
  const name = auth.user?.name ?? "";
  return name
    .split(" ")
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");
});

const isActive = (path: string) =>
  route.path === path || route.path.startsWith(path + "/");

const signOut = async () => {
  await auth.logout();
};

const currentPageTitle = computed(() => {
  for (const group of navigation) {
    for (const item of group.items) {
      if (isActive(item.to)) return item.label;
    }
  }
  return "Dashboard";
});

const navigation = [
  {
    label: "Tổng quan",
    items: [{ to: "/dashboard", label: "Dashboard", icon: ICON_DASHBOARD }],
  },
  {
    label: "Quản lý",
    items: [
      { to: "/orders", label: "Đơn hàng", icon: ICON_ORDERS },
      { to: "/fulfillment", label: "Xuất hàng", icon: ICON_FULFILLMENT },
      { to: "/purchase-orders/new", label: "Tạo đơn PO", icon: ICON_PO_CREATE },
      { to: "/customers", label: "Khách hàng", icon: ICON_CUSTOMERS },
      { to: "/products", label: "Sản phẩm", icon: ICON_PRODUCTS },
      { to: "/inventory", label: "Kho hàng", icon: ICON_INVENTORY },
      { to: "/staff", label: "Nhân viên", icon: ICON_STAFF },
      { to: "/coupons", label: "Mã giảm giá", icon: ICON_COUPONS },
    ],
  },
  {
    label: "Hệ thống",
    items: [{ to: "/settings", label: "Cài đặt", icon: ICON_SETTINGS }],
  },
];

const userRole = computed(() => {
  const roles = auth.user?.roles ?? [];
  const map: Record<string, string> = {
    "super-admin": "Quản trị viên",
    admin: "Quản trị viên",
    manager: "Quản lý",
    "warehouse-staff": "Nhân viên kho",
    "sales-staff": "Nhân viên bán hàng",
  };
  return map[roles[0] ?? ""] ?? roles[0] ?? "Nhân viên";
});
</script>
