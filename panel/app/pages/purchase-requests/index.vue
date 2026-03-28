<template>
  <div>
    <!-- Summary Cards - Mobile: 2 columns, Desktop: 4 columns -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 mb-4">
      <div class="bg-[#111111] border border-amber-500/20 rounded-lg p-2.5 sm:p-3">
        <p class="text-[11px] sm:text-xs text-white/50 mb-0.5 sm:mb-1">Tồn kho thấp</p>
        <p class="text-lg sm:text-xl font-bold text-amber-500">{{ summary.low_stock_count }}</p>
      </div>
      <div class="bg-[#111111] border border-red-500/20 rounded-lg p-2.5 sm:p-3">
        <p class="text-[11px] sm:text-xs text-white/50 mb-0.5 sm:mb-1">Đơn thiếu hàng</p>
        <p class="text-lg sm:text-xl font-bold text-red-500">{{ summary.unfulfillable_count }}</p>
      </div>
      <div class="bg-[#111111] border border-white/8 rounded-lg p-2.5 sm:p-3">
        <p class="text-[11px] sm:text-xs text-white/50 mb-0.5 sm:mb-1">Tổng cần mua</p>
        <p class="text-lg sm:text-xl font-bold text-white/90">{{ summary.total_items_needing_purchase }}</p>
      </div>
      <div class="bg-[#111111] border border-white/8 rounded-lg p-2.5 sm:p-3">
        <p class="text-[11px] sm:text-xs text-white/50 mb-0.5 sm:mb-1">Chi phí ước tính</p>
        <p class="text-lg sm:text-xl font-bold text-white/90">{{ formatVndCompact(summary.estimated_cost) }}</p>
      </div>
    </div>

    <!-- Mobile-Optimized Tabs -->
    <div class="flex items-center gap-1 mb-4 border-b border-white/10 overflow-x-auto scrollbar-none">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="px-3 sm:px-4 py-2 text-sm font-medium transition-colors relative whitespace-nowrap"
        :class="activeTab === tab.key ? 'text-white' : 'text-white/50 hover:text-white/70'"
        @click="activeTab = tab.key"
      >
        {{ tab.label }}
        <span v-if="tab.key === 'low-stock' && summary.low_stock_count > 0" class="ml-1 text-xs text-amber-500">
          ({{ summary.low_stock_count }})
        </span>
        <span v-if="tab.key === 'unfulfillable' && summary.unfulfillable_count > 0" class="ml-1 text-xs text-red-500">
          ({{ summary.unfulfillable_count }})
        </span>
        <div
          v-if="activeTab === tab.key"
          class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-500 rounded-t"
        ></div>
      </button>
    </div>

    <!-- Search & Actions -->
    <div class="flex flex-col sm:flex-row gap-2 mb-4">
      <ZInput
        v-model="search"
        placeholder="Tìm SKU, tên sản phẩm..."
        type="search"
        size="sm"
        class="w-full"
        @input="onSearchInput"
      >
        <template #prefix>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
        </template>
      </ZInput>
      <ZButton 
        size="sm" 
        variant="primary" 
        @click="showCreatePOModal = true" 
        :disabled="selectedItems.length === 0"
        class="w-full sm:w-auto shrink-0"
      >
        Tạo PO ({{ selectedItems.length }})
      </ZButton>
    </div>

    <!-- Low Stock Tab - Mobile Cards -->
    <div v-if="activeTab === 'low-stock'" class="block sm:hidden space-y-2">
      <div 
        v-for="item in lowStockItems" 
        :key="item.id" 
        class="bg-[#111111] border border-white/8 rounded-lg p-3"
        :class="{ 'border-amber-500/30 bg-amber-500/5': isSelected(item) }"
        @click="toggleSelectLowStock(item)"
      >
        <div class="flex items-start gap-2 mb-2">
          <div class="w-5 h-5 rounded border border-white/20 flex items-center justify-center shrink-0 mt-0.5" :class="{ 'bg-amber-500 border-amber-500': isSelected(item) }">
            <svg v-if="isSelected(item)" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-white">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white/85 truncate">{{ item.product_name }}</p>
            <p class="text-xs text-white/50 font-mono">{{ item.sku }}</p>
          </div>
          <span class="text-xs text-white/70 bg-white/10 px-2 py-0.5 rounded whitespace-nowrap">
            {{ item.warehouse_name }}
          </span>
        </div>
        
        <div class="grid grid-cols-3 gap-2 text-center py-2 border-y border-white/5">
          <div>
            <p class="text-[10px] text-white/40">Có sẵn</p>
            <p class="text-sm font-semibold" :class="item.quantity_available <= 0 ? 'text-red-500' : 'text-amber-500'">
              {{ item.quantity_available }}
            </p>
          </div>
          <div>
            <p class="text-[10px] text-white/40">Điểm đặt</p>
            <p class="text-sm text-white/70">{{ item.reorder_point }}</p>
          </div>
          <div>
            <p class="text-[10px] text-white/40">Đề xuất</p>
            <p class="text-sm font-semibold text-white/90">{{ item.suggested_quantity }}</p>
          </div>
        </div>
        
        <div class="flex items-center justify-between pt-2">
          <span class="text-xs text-white/50">{{ item.supplier?.sku ?? 'Chưa có NCC' }}</span>
          <span class="text-xs text-white/70">{{ formatVnd(item.unit_cost) }}/sp</span>
        </div>
      </div>
      
      <!-- Mobile Load More -->
      <div v-if="lowStockMeta.current_page < lowStockMeta.last_page" class="flex justify-center mt-3">
        <ZButton size="sm" variant="outline" @click="loadMoreLowStock" :loading="lowStockLoading" class="w-full">
          Tải thêm
        </ZButton>
      </div>
      <p class="text-center text-xs text-white/40 mt-2">{{ lowStockItems.length }} / {{ lowStockMeta.total }} mục</p>
    </div>

    <!-- Low Stock Tab - Desktop Table -->
    <div v-if="activeTab === 'low-stock'" class="hidden sm:block">
      <ZTable
        :columns="lowStockColumns"
        :rows="lowStockItems"
        :loading="lowStockLoading"
        :skeleton-rows="10"
        row-key="id"
        empty-title="Không có sản phẩm tồn kho thấp"
        @selection-change="onLowStockSelectionChange"
      >
        <template #cell-sku="{ row }">
          <span class="font-mono text-sm text-white/70">{{ (row as LowStockItem).sku }}</span>
        </template>
        <template #cell-product="{ row }">
          <span class="font-medium text-white/85">{{ (row as LowStockItem).product_name }}</span>
        </template>
        <template #cell-stock="{ row }">
          <div class="flex flex-col text-xs">
            <span :class="(row as LowStockItem).quantity_available <= 0 ? 'text-red-500' : 'text-amber-500'">
              Có sẵn: {{ (row as LowStockItem).quantity_available }}
            </span>
            <span class="text-white/40">Tồn kho: {{ (row as LowStockItem).quantity_on_hand }}</span>
          </div>
        </template>
        <template #cell-reorder="{ row }">
          <span class="text-sm text-white/70">{{ (row as LowStockItem).reorder_point }}</span>
        </template>
        <template #cell-suggested="{ row }">
          <span class="text-sm font-semibold text-white/90">{{ (row as LowStockItem).suggested_quantity }}</span>
        </template>
        <template #cell-cost="{ row }">
          <span class="text-sm text-white/70">{{ formatVnd((row as LowStockItem).unit_cost) }}</span>
        </template>
        <template #cell-supplier="{ row }">
          <span class="text-xs text-white/50">{{ (row as LowStockItem).supplier?.sku ?? '—' }}</span>
        </template>
      </ZTable>
      <div class="flex items-center justify-between gap-3 pt-4 flex-wrap">
        <p class="m-0 text-[0.8125rem] text-white/40">{{ lowStockMeta.total }} mục</p>
        <ZPagination :current-page="lowStockMeta.current_page" :total-pages="lowStockMeta.last_page" @change="onLowStockPageChange" />
      </div>
    </div>

    <!-- Unfulfillable Tab - Mobile Cards -->
    <div v-if="activeTab === 'unfulfillable'" class="block sm:hidden space-y-2">
      <div 
        v-for="item in unfulfillableItems" 
        :key="item.id" 
        class="bg-[#111111] border border-white/8 rounded-lg p-3"
        :class="{ 'border-red-500/30 bg-red-500/5': isSelectedUnfulfillable(item) }"
        @click="toggleSelectUnfulfillable(item)"
      >
        <div class="flex items-start gap-2 mb-2">
          <div class="w-5 h-5 rounded border border-white/20 flex items-center justify-center shrink-0 mt-0.5" :class="{ 'bg-red-500 border-red-500': isSelectedUnfulfillable(item) }">
            <svg v-if="isSelectedUnfulfillable(item)" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="text-white">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <NuxtLink 
              :to="`/orders/${item.order_id}`" 
              class="font-medium text-white/90 text-sm hover:text-blue-400 block"
              @click.stop
            >
              {{ item.order_number }}
            </NuxtLink>
            <p class="text-xs text-white/50">{{ formatDatetime(item.order_date) }}</p>
          </div>
        </div>
        
        <div class="flex items-center gap-2 mb-2">
          <div class="flex-1 min-w-0">
            <p class="text-sm text-white/85 truncate">{{ item.product_name }}</p>
            <p class="text-xs text-white/50 font-mono">{{ item.sku }}</p>
          </div>
          <span v-if="item.size_display" class="text-xs text-white/70 bg-white/10 px-2 py-0.5 rounded">{{ item.size_display }}</span>
        </div>
        
        <div class="flex items-center justify-between pt-2 border-t border-white/5">
          <div class="text-xs text-white/50">
            <p>{{ item.customer_name }}</p>
            <p>{{ formatVnd(item.unit_cost) }}/sp</p>
          </div>
          <span class="text-xs text-white/50">{{ item.supplier?.sku ?? 'Chưa có NCC' }}</span>
        </div>
      </div>
      
      <!-- Mobile Load More -->
      <div v-if="unfulfillableMeta.current_page < unfulfillableMeta.last_page" class="flex justify-center mt-3">
        <ZButton size="sm" variant="outline" @click="loadMoreUnfulfillable" :loading="unfulfillableLoading" class="w-full">
          Tải thêm
        </ZButton>
      </div>
      <p class="text-center text-xs text-white/40 mt-2">{{ unfulfillableItems.length }} / {{ unfulfillableMeta.total }} mục</p>
    </div>

    <!-- Unfulfillable Tab - Desktop Table -->
    <div v-if="activeTab === 'unfulfillable'" class="hidden sm:block">
      <ZTable
        :columns="unfulfillableColumns"
        :rows="unfulfillableItems"
        :loading="unfulfillableLoading"
        :skeleton-rows="10"
        row-key="id"
        empty-title="Không có đơn hàng thiếu hàng"
        @selection-change="onUnfulfillableSelectionChange"
      >
        <template #cell-order="{ row }">
          <NuxtLink :to="`/orders/${(row as UnfulfillableItem).order_id}`" class="font-medium text-white/90 hover:text-blue-400">
            {{ (row as UnfulfillableItem).order_number }}
          </NuxtLink>
        </template>
        <template #cell-product="{ row }">
          <div class="flex flex-col">
            <span class="font-medium text-white/85">{{ (row as UnfulfillableItem).product_name }}</span>
            <span class="text-xs font-mono text-white/50">{{ (row as UnfulfillableItem).sku }}</span>
          </div>
        </template>
        <template #cell-size="{ row }">
          <span class="text-sm text-white/70">{{ (row as UnfulfillableItem).size_display ?? '—' }}</span>
        </template>
        <template #cell-customer="{ row }">
          <span class="text-sm text-white/70">{{ (row as UnfulfillableItem).customer_name }}</span>
        </template>
        <template #cell-order-date="{ row }">
          <span class="text-sm text-white/50">{{ formatDatetime((row as UnfulfillableItem).order_date) }}</span>
        </template>
        <template #cell-cost="{ row }">
          <span class="text-sm text-white/70">{{ formatVnd((row as UnfulfillableItem).unit_cost) }}</span>
        </template>
      </ZTable>
      <div class="flex items-center justify-between gap-3 pt-4 flex-wrap">
        <p class="m-0 text-[0.8125rem] text-white/40">{{ unfulfillableMeta.total }} mục</p>
        <ZPagination :current-page="unfulfillableMeta.current_page" :total-pages="unfulfillableMeta.last_page" @change="onUnfulfillablePageChange" />
      </div>
    </div>

    <!-- By Supplier Tab - Mobile Cards -->
    <div v-if="activeTab === 'by-supplier'" class="block sm:hidden space-y-3">
      <div v-if="supplierGroups.length === 0" class="text-center py-8 text-white/50">
        Không có nhóm nhà cung cấp nào
      </div>
      <div 
        v-for="group in supplierGroups" 
        :key="group.supplier_id" 
        class="bg-[#111111] border border-white/8 rounded-lg overflow-hidden"
      >
        <div class="p-3 border-b border-white/5 bg-white/5">
          <div class="flex items-center justify-between">
            <div>
              <p class="font-medium text-white/90 text-sm">NCC: {{ group.supplier_id.substring(0, 8) }}...</p>
              <p class="text-xs text-white/50">{{ group.items.length }} sản phẩm</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-semibold text-white/90">{{ formatVndCompact(group.total_cost) }}</p>
            </div>
          </div>
        </div>
        <div class="p-2 space-y-1">
          <div 
            v-for="item in group.items.slice(0, 3)" 
            :key="item.product_id" 
            class="flex justify-between items-center py-1.5 px-2 text-sm"
          >
            <span class="text-white/70 truncate flex-1 mr-2">{{ item.sku }}</span>
            <span class="text-white/50 shrink-0">{{ item.quantity }} x {{ formatVndCompact(item.unit_cost) }}</span>
          </div>
          <div v-if="group.items.length > 3" class="text-center py-1">
            <span class="text-xs text-white/40">+{{ group.items.length - 3 }} sản phẩm khác</span>
          </div>
        </div>
        <div class="p-2 border-t border-white/5">
          <ZButton size="sm" variant="outline" @click="createPOForSupplier(group)" class="w-full">
            Tạo PO cho nhà cung cấp này
          </ZButton>
        </div>
      </div>
    </div>

    <!-- By Supplier Tab - Desktop -->
    <div v-if="activeTab === 'by-supplier'" class="hidden sm:block">
      <div v-if="supplierGroups.length === 0" class="text-center py-8 text-white/50">
        Không có nhóm nhà cung cấp nào
      </div>
      <div v-for="group in supplierGroups" :key="group.supplier_id" class="mb-4 bg-[#111111] border border-white/8 rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <p class="font-medium text-white/90">Nhà cung cấp: {{ group.supplier_id.substring(0, 8) }}...</p>
            <p class="text-xs text-white/50">{{ group.items.length }} sản phẩm · Tổng: {{ formatVnd(group.total_cost) }}</p>
          </div>
          <ZButton size="sm" variant="outline" @click="createPOForSupplier(group)">
            Tạo PO
          </ZButton>
        </div>
        <div class="space-y-1">
          <div v-for="item in group.items" :key="item.product_id" class="flex justify-between py-1 text-sm border-b border-white/5 last:border-0">
            <span class="text-white/70">{{ item.sku }} - {{ item.name }}</span>
            <span class="text-white/50">{{ item.quantity }} x {{ formatVnd(item.unit_cost) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Create PO Modal - Mobile Optimized -->
    <ZModal v-model="showCreatePOModal" title="Tạo đơn đặt hàng (PO)" size="md">
      <div class="flex flex-col gap-4">
        <p class="text-sm text-white/70">Tạo PO cho {{ selectedItems.length }} sản phẩm đã chọn</p>
        <div class="bg-white/5 rounded-lg p-3 max-h-60 overflow-y-auto">
          <div v-for="item in selectedItems.slice(0, 10)" :key="item.id" class="flex justify-between py-1 text-sm">
            <span class="text-white/70 truncate flex-1 mr-2">{{ (item as any).sku || (item as any).product_name }}</span>
            <span class="text-white/50 shrink-0">{{ (item as any).suggested_quantity || (item as any).quantity_needed || 1 }}</span>
          </div>
          <div v-if="selectedItems.length > 10" class="text-center py-2">
            <span class="text-xs text-white/40">+{{ selectedItems.length - 10 }} sản phẩm khác</span>
          </div>
        </div>
      </div>
      <template #footer>
        <ZButton variant="outline" size="sm" @click="showCreatePOModal = false" class="flex-1 sm:flex-none">Hủy</ZButton>
        <ZButton variant="primary" size="sm" :loading="createPOLoading" @click="handleCreatePO" class="flex-1 sm:flex-none">
          Tạo PO
        </ZButton>
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch, computed } from "vue";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage, formatVnd, formatDatetime } from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";

definePageMeta({ layout: "panel" });

interface LowStockItem {
  id: string;
  product_id: string;
  sku: string;
  product_name: string;
  warehouse_id: string;
  warehouse_name: string;
  quantity_available: number;
  quantity_on_hand: number;
  reorder_point: number;
  suggested_quantity: number;
  unit_cost: number;
  supplier: { id: string; sku: string; lead_time_days: number } | null;
}

interface UnfulfillableItem {
  id: string;
  order_id: string;
  order_number: string;
  product_id: string;
  sku: string;
  product_name: string;
  size_display: string | null;
  quantity_needed: number;
  order_date: string;
  customer_name: string;
  unit_price: number;
  unit_cost: number;
  supplier: { id: string; sku: string; lead_time_days: number } | null;
}

interface SupplierGroup {
  supplier_id: string;
  items: {
    type: string;
    product_id: string;
    sku: string;
    name: string;
    quantity: number;
    unit_cost: number;
    total_cost: number;
    order_item_id?: string;
    order_number?: string;
    customer_name?: string;
  }[];
  total_cost: number;
}

interface ListMeta {
  current_page: number;
  last_page: number;
  total: number;
}

const api = useApi();
const toast = useToast();

const tabs = [
  { key: "low-stock", label: "Tồn kho thấp" },
  { key: "unfulfillable", label: "Đơn thiếu hàng" },
  { key: "by-supplier", label: "Theo NCC" },
];
const activeTab = ref("low-stock");
const search = ref("");

const summary = reactive({
  low_stock_count: 0,
  unfulfillable_count: 0,
  total_items_needing_purchase: 0,
  estimated_cost: 0,
});

// Low stock state
const lowStockItems = ref<LowStockItem[]>([]);
const lowStockLoading = ref(false);
const lowStockMeta = ref<ListMeta>({ current_page: 1, last_page: 1, total: 0 });
const lowStockPage = ref(1);
const selectedLowStock = ref<LowStockItem[]>([]);

// Unfulfillable state
const unfulfillableItems = ref<UnfulfillableItem[]>([]);
const unfulfillableLoading = ref(false);
const unfulfillableMeta = ref<ListMeta>({ current_page: 1, last_page: 1, total: 0 });
const unfulfillablePage = ref(1);
const selectedUnfulfillable = ref<UnfulfillableItem[]>([]);

// Supplier groups
const supplierGroups = ref<SupplierGroup[]>([]);

// Modal
const showCreatePOModal = ref(false);
const createPOLoading = ref(false);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const lowStockColumns: TableColumn[] = [
  { key: "selection", type: "selection", width: "40px" },
  { key: "sku", label: "SKU", width: "100px" },
  { key: "product", label: "Sản phẩm" },
  { key: "stock", label: "Tồn kho", width: "100px" },
  { key: "reorder", label: "Điểm đặt lại", width: "100px", align: "right" },
  { key: "suggested", label: "Đề xuất mua", width: "100px", align: "right" },
  { key: "cost", label: "Đơn giá", width: "120px", align: "right" },
  { key: "supplier", label: "NCC", width: "100px" },
];

const unfulfillableColumns: TableColumn[] = [
  { key: "selection", type: "selection", width: "40px" },
  { key: "order", label: "Đơn hàng", width: "120px" },
  { key: "product", label: "Sản phẩm" },
  { key: "size", label: "Size", width: "60px" },
  { key: "customer", label: "Khách hàng", width: "140px" },
  { key: "order-date", label: "Ngày đặt", width: "120px" },
  { key: "cost", label: "Giá vốn", width: "120px", align: "right" },
];

const selectedItems = computed(() => {
  if (activeTab.value === "low-stock") return selectedLowStock.value;
  if (activeTab.value === "unfulfillable") return selectedUnfulfillable.value;
  return [];
});

function formatVndCompact(amount: number): string {
  if (amount >= 1_000_000_000) return (amount / 1_000_000_000).toFixed(1) + 'T';
  if (amount >= 1_000_000) return (amount / 1_000_000).toFixed(1) + 'tr';
  if (amount >= 1_000) return (amount / 1_000).toFixed(1) + 'k';
  return formatVnd(amount);
}

function isSelected(item: LowStockItem): boolean {
  return selectedLowStock.value.some((i) => i.id === item.id);
}

function isSelectedUnfulfillable(item: UnfulfillableItem): boolean {
  return selectedUnfulfillable.value.some((i) => i.id === item.id);
}

function toggleSelectLowStock(item: LowStockItem): void {
  const index = selectedLowStock.value.findIndex((i) => i.id === item.id);
  if (index === -1) {
    selectedLowStock.value.push(item);
  } else {
    selectedLowStock.value.splice(index, 1);
  }
}

function toggleSelectUnfulfillable(item: UnfulfillableItem): void {
  const index = selectedUnfulfillable.value.findIndex((i) => i.id === item.id);
  if (index === -1) {
    selectedUnfulfillable.value.push(item);
  } else {
    selectedUnfulfillable.value.splice(index, 1);
  }
}

async function fetchSummary(): Promise<void> {
  try {
    const response = await api.get<{ data: typeof summary }>("/purchase-requests");
    Object.assign(summary, response.data);
  } catch (error) {
    console.error("Failed to fetch summary", error);
  }
}

async function fetchLowStock(): Promise<void> {
  lowStockLoading.value = true;
  try {
    const params = new URLSearchParams({
      page: String(lowStockPage.value),
      per_page: "20",
    });
    if (search.value) params.set("search", search.value);

    const response = await api.get<{ data: LowStockItem[]; meta: ListMeta }>(`/purchase-requests/low-stock?${params}`);
    lowStockItems.value = response.data ?? [];
    lowStockMeta.value = response.meta ?? { current_page: 1, last_page: 1, total: 0 };
  } catch (error) {
    toast.error("Lỗi tải dữ liệu", getErrorMessage(error));
  } finally {
    lowStockLoading.value = false;
  }
}

async function loadMoreLowStock(): Promise<void> {
  if (lowStockPage.value >= lowStockMeta.value.last_page) return;
  lowStockPage.value++;
  lowStockLoading.value = true;
  try {
    const params = new URLSearchParams({
      page: String(lowStockPage.value),
      per_page: "20",
    });
    if (search.value) params.set("search", search.value);

    const response = await api.get<{ data: LowStockItem[]; meta: ListMeta }>(`/purchase-requests/low-stock?${params}`);
    lowStockItems.value.push(...(response.data ?? []));
    lowStockMeta.value = response.meta ?? { current_page: 1, last_page: 1, total: 0 };
  } catch (error) {
    toast.error("Lỗi tải dữ liệu", getErrorMessage(error));
  } finally {
    lowStockLoading.value = false;
  }
}

async function fetchUnfulfillable(): Promise<void> {
  unfulfillableLoading.value = true;
  try {
    const params = new URLSearchParams({
      page: String(unfulfillablePage.value),
      per_page: "20",
    });
    if (search.value) params.set("search", search.value);

    const response = await api.get<{ data: UnfulfillableItem[]; meta: ListMeta }>(`/purchase-requests/unfulfillable?${params}`);
    unfulfillableItems.value = response.data ?? [];
    unfulfillableMeta.value = response.meta ?? { current_page: 1, last_page: 1, total: 0 };
  } catch (error) {
    toast.error("Lỗi tải dữ liệu", getErrorMessage(error));
  } finally {
    unfulfillableLoading.value = false;
  }
}

async function loadMoreUnfulfillable(): Promise<void> {
  if (unfulfillablePage.value >= unfulfillableMeta.value.last_page) return;
  unfulfillablePage.value++;
  unfulfillableLoading.value = true;
  try {
    const params = new URLSearchParams({
      page: String(unfulfillablePage.value),
      per_page: "20",
    });
    if (search.value) params.set("search", search.value);

    const response = await api.get<{ data: UnfulfillableItem[]; meta: ListMeta }>(`/purchase-requests/unfulfillable?${params}`);
    unfulfillableItems.value.push(...(response.data ?? []));
    unfulfillableMeta.value = response.meta ?? { current_page: 1, last_page: 1, total: 0 };
  } catch (error) {
    toast.error("Lỗi tải dữ liệu", getErrorMessage(error));
  } finally {
    unfulfillableLoading.value = false;
  }
}

async function fetchSupplierGroups(): Promise<void> {
  try {
    const response = await api.get<{ data: SupplierGroup[] }>("/purchase-requests/by-supplier");
    supplierGroups.value = response.data ?? [];
  } catch (error) {
    console.error("Failed to fetch supplier groups", error);
  }
}

function onLowStockSelectionChange(selection: LowStockItem[]): void {
  selectedLowStock.value = selection;
}

function onUnfulfillableSelectionChange(selection: UnfulfillableItem[]): void {
  selectedUnfulfillable.value = selection;
}

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    lowStockPage.value = 1;
    unfulfillablePage.value = 1;
    fetchLowStock();
    fetchUnfulfillable();
  }, 350);
}

function onLowStockPageChange(page: number): void {
  lowStockPage.value = page;
  fetchLowStock();
}

function onUnfulfillablePageChange(page: number): void {
  unfulfillablePage.value = page;
  fetchUnfulfillable();
}

async function handleCreatePO(): Promise<void> {
  createPOLoading.value = true;
  try {
    const items = selectedItems.value.map((item) => ({
      product_id: (item as any).product_id,
      quantity: (item as any).suggested_quantity || (item as any).quantity_needed || 1,
      supplier_id: (item as any).supplier?.id,
    }));

    await api.post("/purchase-requests/create-po", { items });
    toast.success("Đã tạo đơn đặt hàng");
    showCreatePOModal.value = false;
    selectedLowStock.value = [];
    selectedUnfulfillable.value = [];
    await fetchSummary();
  } catch (error) {
    toast.error("Tạo PO thất bại", getErrorMessage(error));
  } finally {
    createPOLoading.value = false;
  }
}

async function createPOForSupplier(group: SupplierGroup): Promise<void> {
  try {
    const items = group.items.map((item) => ({
      product_id: item.product_id,
      quantity: item.quantity,
      supplier_id: group.supplier_id,
    }));

    await api.post("/purchase-requests/create-po", { items });
    toast.success("Đã tạo PO cho nhà cung cấp");
    await fetchSummary();
  } catch (error) {
    toast.error("Tạo PO thất bại", getErrorMessage(error));
  }
}

watch(activeTab, (tab) => {
  if (tab === "low-stock") fetchLowStock();
  if (tab === "unfulfillable") fetchUnfulfillable();
  if (tab === "by-supplier") fetchSupplierGroups();
});

onMounted(() => {
  fetchSummary();
  fetchLowStock();
});
</script>
