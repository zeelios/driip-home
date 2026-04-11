<template>
  <div>
    <!-- Stats Cards - Mobile: 2 columns, Desktop: 4 columns -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 mb-4">
      <div class="bg-[#111111] border border-white/8 rounded-lg p-2.5 sm:p-3">
        <p class="text-[11px] sm:text-xs text-white/50 mb-0.5 sm:mb-1">
          Chờ xử lý
        </p>
        <p class="text-lg sm:text-xl font-bold text-white/90">
          {{ stats.pending }}
        </p>
      </div>
      <div class="bg-[#111111] border border-white/8 rounded-lg p-2.5 sm:p-3">
        <p class="text-[11px] sm:text-xs text-white/50 mb-0.5 sm:mb-1">
          Đã pick
        </p>
        <p class="text-lg sm:text-xl font-bold text-amber-500">
          {{ stats.picked }}
        </p>
      </div>
      <div class="bg-[#111111] border border-white/8 rounded-lg p-2.5 sm:p-3">
        <p class="text-[11px] sm:text-xs text-white/50 mb-0.5 sm:mb-1">
          Đã đóng gói
        </p>
        <p class="text-lg sm:text-xl font-bold text-blue-500">
          {{ stats.packed_today }}
        </p>
      </div>
      <div class="bg-[#111111] border border-white/8 rounded-lg p-2.5 sm:p-3">
        <p class="text-[11px] sm:text-xs text-white/50 mb-0.5 sm:mb-1">
          Đã giao
        </p>
        <p class="text-lg sm:text-xl font-bold text-green-500">
          {{ stats.shipped_today }}
        </p>
      </div>
    </div>

    <!-- Mobile-First Toolbar -->
    <div class="flex flex-col gap-2 mb-4">
      <!-- Search & Filter Row -->
      <div class="flex flex-col sm:flex-row gap-2">
        <ZInput
          v-model="search"
          placeholder="Tìm đơn hàng, SKU..."
          type="search"
          size="sm"
          class="w-full"
          @input="onSearchInput"
        >
          <template #prefix>
            <svg
              width="15"
              height="15"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
          </template>
        </ZInput>
        <ZSelect
          v-model="statusFilter"
          size="sm"
          class="w-full sm:w-36 shrink-0"
          :options="statusOptions"
        />
      </div>

      <!-- Export Button (Mobile: Full width) -->
      <ZButton
        size="sm"
        variant="outline"
        :disabled="selectedItems.length === 0"
        class="w-full sm:w-auto"
        @click="showExportModal = true"
      >
        <template #prefix>
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
          </svg>
        </template>
        Xuất {{ selectedItems.length > 0 ? `(${selectedItems.length})` : "" }}
      </ZButton>
    </div>

    <!-- Mobile-Optimized Bulk Actions Bar -->
    <div
      v-if="selectedItems.length > 0"
      class="sticky top-14 z-10 mb-3 p-2.5 bg-blue-500/10 border border-blue-500/30 rounded-lg backdrop-blur-sm"
    >
      <div class="flex items-center justify-between gap-2">
        <span class="text-sm text-white/80 font-medium"
          >{{ selectedItems.length }} mục</span
        >
        <div class="flex items-center gap-2">
          <ZButton
            size="sm"
            variant="outline"
            :loading="pickPending"
            class="px-3 py-1.5 min-h-9"
            @click="handlePick"
          >
            <span class="hidden sm:inline">Đánh dấu đã pick</span>
            <span class="sm:hidden">Pick</span>
          </ZButton>
          <ZButton
            size="sm"
            variant="primary"
            class="px-3 py-1.5 min-h-9"
            @click="showPackModal = true"
          >
            <span class="hidden sm:inline">Đóng gói</span>
            <span class="sm:hidden">Pack</span>
          </ZButton>
        </div>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="listState === 'error'"
      class="flex items-center justify-between gap-3 py-3 px-4 mb-3 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
    >
      <span>{{ listError }}</span>
      <ZButton variant="ghost" size="sm" @click="fetchItems()">Thử lại</ZButton>
    </div>

    <!-- Mobile: Card-based layout | Desktop: Table -->
    <div class="block sm:hidden space-y-2">
      <!-- Mobile Cards -->
      <div
        v-for="item in items"
        :key="item.id"
        class="bg-[#111111] border border-white/8 rounded-lg p-3"
        :class="{ 'border-blue-500/30 bg-blue-500/5': isSelected(item) }"
        @click="toggleSelect(item)"
      >
        <div class="flex items-start justify-between gap-2 mb-2">
          <div class="flex-1 min-w-0">
            <NuxtLink
              :to="`/orders/${item.order?.id}`"
              class="font-medium text-white/90 text-sm hover:text-blue-400 block truncate"
              @click.stop
            >
              {{ item.order?.order_number ?? "—" }}
            </NuxtLink>
            <span class="text-xs text-white/40">{{
              formatDatetime(item.order?.created_at)
            }}</span>
          </div>
          <ZBadge :variant="statusVariant(item.status)" size="xs">
            {{ statusLabelShort(item.status) }}
          </ZBadge>
        </div>

        <div class="flex items-center gap-2 mb-2">
          <div
            class="w-4 h-4 rounded border border-white/20 flex items-center justify-center shrink-0"
            :class="{ 'bg-blue-500 border-blue-500': isSelected(item) }"
          >
            <svg
              v-if="isSelected(item)"
              width="10"
              height="10"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="3"
              class="text-white"
            >
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm text-white/85 truncate">{{ item.name }}</p>
            <p class="text-xs text-white/50 font-mono">{{ item.sku }}</p>
          </div>
          <span
            v-if="item.size_display"
            class="text-xs text-white/70 bg-white/10 px-2 py-0.5 rounded"
            >{{ item.size_display }}</span
          >
        </div>

        <div
          class="flex items-center justify-between pt-2 border-t border-white/5"
        >
          <div class="text-xs text-white/50">
            {{ item.inventory?.warehouse?.name ?? "—" }}
          </div>
          <div class="flex items-center gap-1">
            <ZButton
              v-if="item.status === 'pending'"
              size="sm"
              variant="outline"
              class="min-h-9 px-2.5"
              @click.stop="pickSingle(item)"
            >
              Pick
            </ZButton>
            <ZButton
              v-if="item.status === 'picked'"
              size="sm"
              variant="primary"
              class="min-h-9 px-2.5"
              @click.stop="packSingle(item)"
            >
              Pack
            </ZButton>
            <ZButton
              v-if="item.shipment?.tracking_number"
              size="sm"
              variant="ghost"
              class="min-h-8 px-2"
              @click.stop="printLabel(item.shipment.tracking_number)"
            >
              In
            </ZButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop Table -->
    <div class="hidden sm:block">
      <ZTable
        :columns="columns"
        :rows="items"
        :loading="listState === 'loading'"
        :skeleton-rows="10"
        row-key="id"
        empty-title="Không có đơn hàng cần xử lý"
        empty-description="Tất cả đơn hàng đã được xử lý."
        @selection-change="onSelectionChange"
      >
        <template #cell-order="{ row }">
          <div class="flex flex-col">
            <NuxtLink
              :to="`/orders/${(row as FulfillmentItem).order?.id}`"
              class="font-medium text-white/90 hover:text-blue-400"
            >
              {{ (row as FulfillmentItem).order?.order_number ?? "—" }}
            </NuxtLink>
            <span class="text-xs text-white/40">{{
              formatDatetime((row as FulfillmentItem).order?.created_at)
            }}</span>
          </div>
        </template>
        <template #cell-product="{ row }">
          <div class="flex flex-col">
            <span class="font-medium text-white/85">{{
              (row as FulfillmentItem).name
            }}</span>
            <span class="text-xs text-white/50 font-mono">{{
              (row as FulfillmentItem).sku
            }}</span>
          </div>
        </template>
        <template #cell-size="{ row }">
          <span class="text-sm text-white/70">{{
            (row as FulfillmentItem).size_display ?? "—"
          }}</span>
        </template>
        <template #cell-status="{ row }">
          <ZBadge
            :variant="statusVariant((row as FulfillmentItem).status)"
            size="sm"
          >
            {{ statusLabel((row as FulfillmentItem).status) }}
          </ZBadge>
        </template>
        <template #cell-warehouse="{ row }">
          <span class="text-sm text-white/70">{{
            (row as FulfillmentItem).inventory?.warehouse?.name ?? "—"
          }}</span>
        </template>
        <template #cell-shipping="{ row }">
          <div class="flex flex-col text-xs">
            <span class="text-white/70">{{
              (row as FulfillmentItem).order?.shipping_name
            }}</span>
            <span class="text-white/40">{{
              (row as FulfillmentItem).order?.shipping_province
            }}</span>
          </div>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-1">
            <ZButton
              v-if="(row as FulfillmentItem).status === 'pending'"
              size="sm"
              variant="ghost"
              @click="pickSingle(row as FulfillmentItem)"
            >
              Pick
            </ZButton>
            <ZButton
              v-if="(row as FulfillmentItem).status === 'picked'"
              size="sm"
              variant="primary"
              @click="packSingle(row as FulfillmentItem)"
            >
              Pack
            </ZButton>
            <ZButton
              v-if="(row as FulfillmentItem).shipment"
              size="sm"
              variant="ghost"
              @click="
                printLabel((row as FulfillmentItem).shipment?.tracking_number)
              "
            >
              In tem
            </ZButton>
          </div>
        </template>
      </ZTable>
    </div>

    <!-- Mobile Pagination (Load More) -->
    <div class="block sm:hidden mt-4">
      <div
        v-if="meta.current_page < meta.last_page"
        class="flex justify-center"
      >
        <ZButton
          size="sm"
          variant="outline"
          :loading="listState === 'loading'"
          class="w-full"
          @click="loadMore"
        >
          Tải thêm
        </ZButton>
      </div>
      <p class="text-center text-xs text-white/40 mt-2">
        {{ items.length }} / {{ meta.total }} mục
      </p>
    </div>

    <!-- Pack Modal -->
    <ZModal v-model="showPackModal" title="Đóng gói sản phẩm" size="sm">
      <div class="flex flex-col gap-4">
        <ZSelect
          v-model="packCourier"
          label="Chọn đơn vị vận chuyển"
          :options="courierOptions"
        />
        <div class="bg-white/5 rounded-lg p-3 max-h-40 overflow-y-auto">
          <div
            v-for="item in selectedItems"
            :key="item.id"
            class="flex justify-between py-1 text-sm"
          >
            <span class="text-white/70 truncate flex-1 mr-2">{{
              item.sku
            }}</span>
            <span class="text-white/50 shrink-0">{{
              item.order?.order_number
            }}</span>
          </div>
        </div>
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          class="flex-1 sm:flex-none"
          @click="showPackModal = false"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="packPending"
          class="flex-1 sm:flex-none"
          @click="handlePack"
        >
          Xác nhận
        </ZButton>
      </template>
    </ZModal>

    <!-- Export Modal -->
    <ZModal v-model="showExportModal" title="Xuất danh sách đóng gói" size="sm">
      <div class="flex flex-col gap-4">
        <p class="text-sm text-white/70">
          Xuất {{ selectedItems.length }} mục đã chọn
        </p>
        <ZSelect
          v-model="exportFormat"
          label="Định dạng"
          :options="exportFormatOptions"
        />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          class="flex-1 sm:flex-none"
          @click="showExportModal = false"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="exportPending"
          class="flex-1 sm:flex-none"
          @click="handleExport"
        >
          Xuất
        </ZButton>
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from "vue";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage, formatDatetime } from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";

definePageMeta({ layout: "panel" });

interface OrderInfo {
  id: string;
  order_number: string;
  created_at: string;
  shipping_name: string;
  shipping_province: string;
}

interface InventoryInfo {
  warehouse?: { name: string } | null;
}

interface ShipmentInfo {
  tracking_number: string | null;
}

interface FulfillmentItem {
  id: string;
  sku: string;
  name: string;
  size_display: string | null;
  status: string;
  order?: OrderInfo | null;
  inventory?: InventoryInfo | null;
  shipment?: ShipmentInfo | null;
}

interface ListMeta {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

const api = useApi();
const toast = useToast();

const listState = ref<"idle" | "loading" | "loaded" | "error">("idle");
const listError = ref<string | null>(null);
const items = ref<FulfillmentItem[]>([]);
const meta = ref<ListMeta>({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
});
const search = ref("");
const statusFilter = ref("");
const currentPage = ref(1);
const selectedItems = ref<FulfillmentItem[]>([]);

const stats = reactive({
  pending: 0,
  picked: 0,
  packed_today: 0,
  shipped_today: 0,
});

const showPackModal = ref(false);
const showExportModal = ref(false);
const packCourier = ref("");
const exportFormat = ref("csv");
const pickPending = ref(false);
const packPending = ref(false);
const exportPending = ref(false);

const courierOptions = [
  { value: "", label: "Tự chọn sau" },
  { value: "ghtk", label: "GHTK" },
  { value: "ghn", label: "GHN" },
  { value: "viettel_post", label: "Viettel Post" },
];

const statusOptions = [
  { value: "", label: "Tất cả trạng thái" },
  { value: "pending", label: "Chờ xử lý" },
  { value: "picked", label: "Đã pick" },
  { value: "packed", label: "Đã đóng gói" },
];

const exportFormatOptions = [
  { value: "csv", label: "CSV" },
  { value: "pdf", label: "PDF" },
];

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const columns: TableColumn[] = [
  { key: "selection", type: "selection", width: "40px" },
  { key: "order", label: "Đơn hàng", skeletonWidth: "120px" },
  { key: "product", label: "Sản phẩm", skeletonWidth: "160px" },
  { key: "size", label: "Size", width: "60px" },
  { key: "status", label: "Trạng thái", width: "100px" },
  { key: "warehouse", label: "Kho", width: "100px" },
  { key: "shipping", label: "Giao hàng", width: "140px" },
  { key: "actions", label: "", width: "100px", align: "right" },
];

async function fetchItems(): Promise<void> {
  listState.value = "loading";
  listError.value = null;
  try {
    const params: Record<string, string> = {
      page: String(currentPage.value),
      per_page: "20",
    };
    if (search.value.trim()) params["search"] = search.value.trim();
    if (statusFilter.value) params["filter[status]"] = statusFilter.value;

    const q = new URLSearchParams(params).toString();
    const response = await api.get<{ data: FulfillmentItem[]; meta: ListMeta }>(
      `/fulfillment/items?${q}`
    );
    items.value = response.data ?? [];
    meta.value = response.meta ?? {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 20,
    };
    listState.value = "loaded";
  } catch (error) {
    listState.value = "error";
    listError.value = getErrorMessage(error, "Không thể tải dữ liệu.");
  }
}

async function loadMore(): Promise<void> {
  if (currentPage.value >= meta.value.last_page) return;
  currentPage.value++;
  await fetchMoreItems();
}

async function fetchMoreItems(): Promise<void> {
  listState.value = "loading";
  try {
    const params: Record<string, string> = {
      page: String(currentPage.value),
      per_page: "20",
    };
    if (search.value.trim()) params["search"] = search.value.trim();
    if (statusFilter.value) params["filter[status]"] = statusFilter.value;

    const q = new URLSearchParams(params).toString();
    const response = await api.get<{ data: FulfillmentItem[]; meta: ListMeta }>(
      `/fulfillment/items?${q}`
    );
    items.value.push(...(response.data ?? []));
    meta.value = response.meta ?? {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 20,
    };
    listState.value = "loaded";
  } catch (error) {
    listState.value = "error";
    listError.value = getErrorMessage(error, "Không thể tải dữ liệu.");
  }
}

async function fetchStats(): Promise<void> {
  try {
    const response = await api.get<{ data: typeof stats }>(
      "/fulfillment/stats"
    );
    Object.assign(stats, response.data);
  } catch (error) {
    console.error("Failed to fetch stats", error);
  }
}

function isFulfillmentItem(value: unknown): value is FulfillmentItem {
  if (!value || typeof value !== "object") {
    return false;
  }

  const candidate = value as Partial<FulfillmentItem>;

  return (
    typeof candidate.id === "string" &&
    typeof candidate.sku === "string" &&
    typeof candidate.name === "string" &&
    typeof candidate.status === "string"
  );
}

function onSelectionChange(selection: unknown[]): void {
  selectedItems.value = selection.filter(isFulfillmentItem);
}

function isSelected(item: FulfillmentItem): boolean {
  return selectedItems.value.some((i) => i.id === item.id);
}

function toggleSelect(item: FulfillmentItem): void {
  const index = selectedItems.value.findIndex((i) => i.id === item.id);
  if (index === -1) {
    selectedItems.value.push(item);
  } else {
    selectedItems.value.splice(index, 1);
  }
}

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentPage.value = 1;
    fetchItems();
  }, 350);
}

function statusVariant(
  status: string
): "default" | "success" | "warning" | "danger" | "info" | "neutral" | "amber" {
  const map: Record<
    string,
    "default" | "success" | "warning" | "danger" | "info" | "neutral" | "amber"
  > = {
    pending: "warning",
    picked: "info",
    packed: "default",
    shipped: "success",
    delivered: "success",
    cancelled: "neutral",
  };
  return map[status] ?? "neutral";
}

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    pending: "Chờ xử lý",
    picked: "Đã pick",
    packed: "Đã đóng gói",
    shipped: "Đã giao",
    delivered: "Đã nhận",
    cancelled: "Đã hủy",
  };
  return map[status] ?? status;
}

function statusLabelShort(status: string): string {
  const map: Record<string, string> = {
    pending: "Chờ",
    picked: "Pick",
    packed: "Pack",
    shipped: "Giao",
    delivered: "Xong",
    cancelled: "Hủy",
  };
  return map[status] ?? status;
}

async function pickSingle(item: FulfillmentItem): Promise<void> {
  await executePick([item.id]);
}

async function packSingle(item: FulfillmentItem): Promise<void> {
  selectedItems.value = [item];
  showPackModal.value = true;
}

async function handlePick(): Promise<void> {
  const ids = selectedItems.value.map((i) => i.id);
  await executePick(ids);
}

async function executePick(ids: string[]): Promise<void> {
  pickPending.value = true;
  try {
    const response = await api.post<{
      success: boolean;
      data: { picked_count: number };
    }>("/fulfillment/pick", {
      item_ids: ids,
    });
    if (response.success) {
      toast.success(`Đã pick ${response.data.picked_count} sản phẩm`);
      selectedItems.value = [];
      await Promise.all([fetchItems(), fetchStats()]);
    }
  } catch (error) {
    toast.error("Pick thất bại", getErrorMessage(error));
  } finally {
    pickPending.value = false;
  }
}

async function handlePack(): Promise<void> {
  packPending.value = true;
  try {
    const ids = selectedItems.value.map((i) => i.id);
    const response = await api.post<{
      success: boolean;
      data: { packed_count: number; labels: { tracking_number: string }[] };
    }>("/fulfillment/pack", {
      item_ids: ids,
      courier_code: packCourier.value || undefined,
    });
    if (response.success) {
      const labelCount = response.data.labels?.length ?? 0;
      toast.success(
        `Đã đóng gói ${response.data.packed_count} sản phẩm, tạo ${labelCount} vận đơn`
      );
      // Save selected courier to cookie for next session
      if (packCourier.value) {
        setCookie("last_selected_courier", packCourier.value, 30);
      }
      showPackModal.value = false;
      selectedItems.value = [];
      packCourier.value = "";
      await Promise.all([fetchItems(), fetchStats()]);
    }
  } catch (error) {
    toast.error("Đóng gói thất bại", getErrorMessage(error));
  } finally {
    packPending.value = false;
  }
}

async function handleExport(): Promise<void> {
  exportPending.value = true;
  try {
    const ids = selectedItems.value.map((i) => i.id);
    await api.post("/fulfillment/export", {
      item_ids: ids,
      format: exportFormat.value,
    });
    toast.success("Đã gửi yêu cầu xuất file");
    showExportModal.value = false;
  } catch (error) {
    toast.error("Xuất thất bại", getErrorMessage(error));
  } finally {
    exportPending.value = false;
  }
}

// Cookie helper functions for persisting last selected courier
function setCookie(name: string, value: string, days: number): void {
  if (typeof document === "undefined") return;
  const expires = new Date(Date.now() + days * 864e5).toUTCString();
  document.cookie = `${name}=${encodeURIComponent(
    value
  )}; expires=${expires}; path=/; SameSite=Lax`;
}

function getCookie(name: string): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(new RegExp("(^| )" + name + "=([^;]+)"));
  return match?.[2] ? decodeURIComponent(match[2]) : null;
}

function printLabel(trackingNumber: string | null | undefined): void {
  if (!trackingNumber) {
    toast.error("Không có mã vận đơn");
    return;
  }
  window.open(`/api/v1/panel/ghtk/orders/${trackingNumber}/label`, "_blank");
}

watch(showPackModal, (open) => {
  if (open) {
    // Load last selected courier from cookie when modal opens
    const lastCourier = getCookie("last_selected_courier");
    if (lastCourier && courierOptions.some((o) => o.value === lastCourier)) {
      packCourier.value = lastCourier;
    }
  }
});

watch(statusFilter, () => {
  currentPage.value = 1;
  fetchItems();
});

onMounted(() => {
  fetchItems();
  fetchStats();
});
</script>
