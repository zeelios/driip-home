<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="detailState === 'loading'">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <ZSkeleton variant="circle" width="3.5rem" height="3.5rem" />
          <div class="flex flex-col gap-1.5 flex-1">
            <ZSkeleton height="1.25rem" width="200px" />
            <ZSkeleton height="0.875rem" width="120px" />
          </div>
        </div>
        <ZSkeleton height="2.25rem" width="140px" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-6">
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-for="i in 3"
          :key="i"
        >
          <ZSkeleton height="0.75rem" width="40%" class="mb-2" />
          <ZSkeleton height="1rem" width="80%" class="mb-1" />
          <ZSkeleton height="1rem" width="65%" class="mb-1" />
          <ZSkeleton height="1rem" width="70%" />
        </div>
      </div>
    </template>

    <!-- Error -->
    <ZEmptyState
      v-else-if="detailState === 'error'"
      title="Không thể tải thông tin tồn kho"
      :description="detailError ?? ''"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="fetchInventory"
          >Thử lại</ZButton
        >
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="inventory">
      <!-- Page header -->
      <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div class="flex items-center flex-wrap gap-3">
          <NuxtLink
            to="/inventory"
            class="inline-flex items-center gap-1 text-[0.8125rem] text-white/50 no-underline transition-colors duration-130 hover:text-white/80"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <polyline points="15 18 9 12 15 6" />
            </svg>
            Tồn kho
          </NuxtLink>
          <div class="flex items-center gap-3">
            <div
              class="w-12 h-12 rounded-full bg-[#111111] border border-white/10 text-white/70 text-xs font-bold flex items-center justify-center shrink-0"
            >
              INV
            </div>
            <div>
              <h1 class="m-0 text-lg font-bold text-white/95">
                {{ inventory.variant?.product?.name ?? "Sản phẩm" }}
              </h1>
              <p class="m-0 text-sm text-white/40 font-mono">
                {{ inventory.variant?.sku ?? "—" }}
              </p>
            </div>
          </div>
        </div>
        <div class="flex gap-2 flex-wrap">
          <ZButton variant="outline" size="sm" @click="showAdjustModal = true">
            Điều chỉnh tồn kho
          </ZButton>
        </div>
      </div>

      <!-- Stock stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Tồn thực tế
          </p>
          <p class="m-0 text-2xl font-bold text-white/95">
            {{ inventory.quantity_on_hand }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Đặt trước
          </p>
          <p class="m-0 text-2xl font-bold text-white/95">
            {{ inventory.quantity_reserved }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Có thể bán
          </p>
          <p
            class="m-0 text-2xl font-bold"
            :class="isLowStock ? 'text-red-500' : 'text-white/95'"
          >
            {{ inventory.quantity_available }}
          </p>
        </div>
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
          <p
            class="m-0 mb-1 text-[0.6875rem] font-bold uppercase tracking-[0.07em] text-white/50"
          >
            Điểm đặt lại
          </p>
          <p class="m-0 text-2xl font-bold text-white/95">
            {{ inventory.reorder_point ?? "—" }}
          </p>
        </div>
      </div>

      <!-- Detail grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-6">
        <!-- Product info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin sản phẩm
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">SKU</dt>
              <dd
                class="m-0 text-white/85 text-right break-words font-mono text-[0.8125rem] font-semibold"
              >
                {{ inventory.variant?.sku ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Sản phẩm</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ inventory.variant?.product?.name ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Barcode</dt>
              <dd
                class="m-0 text-white/85 text-right break-words font-mono text-[0.8125rem] font-semibold"
              >
                {{ inventory.variant?.barcode ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Trạng thái</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                <ZBadge
                  :variant="(productStatusVariant(inventory.variant?.status ?? '') as any)"
                >
                  {{ productStatusLabel(inventory.variant?.status ?? "") }}
                </ZBadge>
              </dd>
            </div>
          </dl>
        </div>

        <!-- Stock info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin tồn kho
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Kho</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ inventory.warehouse?.name ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Tồn thực tế</dt>
              <dd class="m-0 text-white/85 text-right break-words font-bold">
                {{ inventory.quantity_on_hand }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Đặt trước</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ inventory.quantity_reserved }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Có thể bán</dt>
              <dd
                class="m-0 text-white/85 text-right break-words font-bold"
                :class="isLowStock ? 'text-red-500' : ''"
              >
                {{ inventory.quantity_available }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Đang về</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ inventory.quantity_incoming }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Pricing -->
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-if="inventory.variant"
        >
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Giá
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Giá so sánh</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ formatVnd(inventory.variant.compare_price) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Giá vốn</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ formatVnd(inventory.variant.cost_price) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Giá bán</dt>
              <dd class="m-0 text-white/85 text-right break-words font-bold">
                {{ formatVnd(inventory.variant.selling_price) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Giá sale</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{
                  inventory.variant.sale_price
                    ? formatVnd(inventory.variant.sale_price)
                    : "—"
                }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Transactions section -->
      <div class="my-6 pb-3 border-b border-white/10">
        <h2 class="m-0 text-base font-semibold text-white/90">
          Lịch sử giao dịch
        </h2>
      </div>

      <!-- Transactions Error -->
      <div
        v-if="transactionState === 'error'"
        class="flex items-center justify-between gap-3 py-3 px-4 mb-3.5 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
      >
        <span>{{ transactionError }}</span>
        <ZButton variant="ghost" size="sm" @click="fetchTransactions"
          >Thử lại</ZButton
        >
      </div>

      <!-- Transactions Table -->
      <ZTable
        :columns="transactionColumns"
        :rows="transactions"
        :loading="transactionState === 'loading'"
        :skeleton-rows="10"
        row-key="id"
        empty-title="Chưa có giao dịch"
        empty-description="Chưa có lịch sử giao dịch nào cho mục tồn kho này."
      >
        <template #cell-type="{ row }">
          <ZBadge
            :variant="(transactionTypeVariant((row as InventoryTransaction).type) as any)"
          >
            {{ transactionTypeLabel((row as InventoryTransaction).type) }}
          </ZBadge>
        </template>
        <template #cell-quantity="{ row }">
          <span
            :class="(row as InventoryTransaction).quantity > 0 ? 'text-green-500' : (row as InventoryTransaction).quantity < 0 ? 'text-red-500' : ''"
          >
            {{ (row as InventoryTransaction).quantity > 0 ? "+" : ""
            }}{{ (row as InventoryTransaction).quantity }}
          </span>
        </template>
        <template #cell-quantity_before="{ row }">{{
          (row as InventoryTransaction).quantity_before
        }}</template>
        <template #cell-quantity_after="{ row }">{{
          (row as InventoryTransaction).quantity_after
        }}</template>
        <template #cell-unit_cost="{ row }">
          <span class="font-semibold">{{
            (row as InventoryTransaction).unit_cost
              ? formatVnd((row as InventoryTransaction).unit_cost)
              : "—"
          }}</span>
        </template>
        <template #cell-reference="{ row }">
          <span class="text-[0.8125rem] text-white/60">{{
            formatReference(row as InventoryTransaction)
          }}</span>
        </template>
        <template #cell-created_at="{ row }">
          <span class="text-[0.8125rem] text-white/50">{{
            formatDatetime((row as InventoryTransaction).created_at)
          }}</span>
        </template>
      </ZTable>

      <!-- Transactions Pagination -->
      <div
        class="flex items-center justify-between gap-3 pt-4 flex-wrap"
        v-if="transactionState === 'loaded' && transactions.length > 0"
      >
        <p class="m-0 text-[0.8125rem] text-white/40">
          {{ transactionMeta.total }} giao dịch
        </p>
        <ZPagination
          :current-page="transactionMeta.current_page"
          :total-pages="transactionMeta.last_page"
          @change="onTransactionPageChange"
        />
      </div>
    </template>

    <!-- Adjust modal -->
    <ZModal v-model="showAdjustModal" title="Điều chỉnh tồn kho" size="sm">
      <div class="flex flex-col gap-4">
        <div class="bg-white/[0.03] border border-white/[0.08] rounded-lg p-3">
          <p class="m-0 mb-1 text-[0.6875rem] text-white/50">Sản phẩm</p>
          <p class="m-0 font-medium text-white/90">
            {{ inventory?.variant?.product?.name ?? "—" }}
          </p>
        </div>
        <div class="bg-white/[0.03] border border-white/[0.08] rounded-lg p-3">
          <p class="m-0 mb-1 text-[0.6875rem] text-white/50">SKU</p>
          <p class="m-0 font-medium text-white/90 font-mono text-[0.8125rem]">
            {{ inventory?.variant?.sku ?? "—" }}
          </p>
        </div>
        <ZInput
          v-model="adjustForm.quantity"
          label="Số lượng điều chỉnh *"
          type="number"
          placeholder="+10 hoặc -5"
          :error="adjustErrors.quantity"
        />
        <ZInput
          v-model="adjustForm.reason"
          label="Lý do *"
          placeholder="Nhập hàng, hàng lỗi, kiểm kê..."
          :error="adjustErrors.reason"
        />
        <ZInput
          v-model="adjustForm.lot_number"
          label="Số lô"
          placeholder="Lô nhập hàng..."
        />
        <ZInput
          v-model="adjustForm.unit_cost"
          label="Giá vốn"
          type="number"
          placeholder="Giá vốn mỗi đơn vị..."
        />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          :disabled="adjustPending"
          @click="showAdjustModal = false"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="adjustPending"
          @click="handleAdjust"
          >Xác nhận</ZButton
        >
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import {
  formatVnd,
  formatDatetime,
  getErrorMessage,
  sanitizeString,
  productStatusLabel,
  productStatusVariant,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";

definePageMeta({ layout: "panel" });

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

interface Product {
  name: string;
}

interface Variant {
  id?: string;
  sku: string;
  barcode?: string | null;
  status?: string;
  compare_price: number;
  cost_price: number;
  selling_price: number;
  sale_price: number | null;
  product?: Product | null;
}

interface Warehouse {
  name: string;
}

interface Inventory {
  id: string;
  product_variant_id: string;
  warehouse_id: string;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  quantity_incoming: number;
  reorder_point: number | null;
  reorder_quantity: number | null;
  variant?: Variant | null;
  warehouse?: Warehouse | null;
}

interface InventoryTransaction {
  id: string;
  product_variant_id: string;
  warehouse_id: string;
  type: string;
  quantity: number;
  quantity_before: number;
  quantity_after: number;
  unit_cost: number | null;
  lot_number: string | null;
  reference_type: string | null;
  reference_id: string | null;
  notes: string | null;
  created_by: string | null;
  created_at: string;
}

interface ListMeta {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

const route = useRoute();
const api = useApi();
const toast = useToast();

const id = route.params.id as string;

// Detail states
const detailState = ref<"idle" | "loading" | "loaded" | "error">("idle");
const detailError = ref<string | null>(null);
const inventory = ref<Inventory | null>(null);

// Transaction states
const transactionState = ref<"idle" | "loading" | "loaded" | "error">("idle");
const transactionError = ref<string | null>(null);
const transactions = ref<InventoryTransaction[]>([]);
const transactionMeta = ref<ListMeta>({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
});
const transactionPage = ref(1);

// Adjust modal
const showAdjustModal = ref(false);
const adjustPending = ref(false);
const adjustForm = reactive({
  quantity: "",
  reason: "",
  lot_number: "",
  unit_cost: "",
});
const adjustErrors = reactive({
  quantity: "",
  reason: "",
});

const isLowStock = computed(() => {
  if (!inventory.value) return false;
  return (
    inventory.value.quantity_available <= (inventory.value.reorder_point ?? 5)
  );
});

const transactionColumns: TableColumn[] = [
  { key: "type", label: "Loại", skeletonWidth: "80px" },
  {
    key: "quantity",
    label: "Thay đổi",
    align: "center",
    skeletonWidth: "60px",
  },
  {
    key: "quantity_before",
    label: "Trước",
    align: "right",
    skeletonWidth: "50px",
  },
  {
    key: "quantity_after",
    label: "Sau",
    align: "right",
    skeletonWidth: "50px",
  },
  { key: "unit_cost", label: "Giá vốn", align: "right", skeletonWidth: "80px" },
  { key: "reference", label: "Tham chiếu", skeletonWidth: "120px" },
  { key: "created_at", label: "Thời gian", skeletonWidth: "130px" },
];

function transactionTypeLabel(type: string): string {
  const map: Record<string, string> = {
    adjustment: "Điều chỉnh",
    purchase: "Nhập hàng",
    sale: "Bán hàng",
    return: "Trả hàng",
    transfer_in: "Nhận chuyển",
    transfer_out: "Xuất chuyển",
    stock_count: "Kiểm kê",
  };
  return map[type] ?? type;
}

function transactionTypeVariant(type: string): string {
  const map: Record<string, string> = {
    adjustment: "neutral",
    purchase: "success",
    sale: "info",
    return: "warning",
    transfer_in: "success",
    transfer_out: "amber",
    stock_count: "info",
  };
  return map[type] ?? "default";
}

function formatReference(tx: InventoryTransaction): string {
  if (!tx.reference_type || !tx.reference_id) return "—";
  const shortId = tx.reference_id.slice(-8);
  return `${tx.reference_type}: ${shortId}`;
}

async function fetchInventory(): Promise<void> {
  detailState.value = "loading";
  detailError.value = null;
  try {
    const response = await api.get<{ data: Inventory }>(`/inventory/${id}`);
    inventory.value = response.data ?? null;
    detailState.value = "loaded";
  } catch (error) {
    detailState.value = "error";
    detailError.value = getErrorMessage(
      error,
      "Không thể tải thông tin tồn kho."
    );
  }
}

async function fetchTransactions(): Promise<void> {
  transactionState.value = "loading";
  transactionError.value = null;
  try {
    const params: Record<string, string> = {
      page: String(transactionPage.value),
      per_page: "20",
      product_variant_id: inventory.value?.product_variant_id ?? "",
      warehouse_id: inventory.value?.warehouse_id ?? "",
    };
    const q = new URLSearchParams(params).toString();
    const response = await api.get<{
      data: InventoryTransaction[];
      meta: ListMeta;
    }>(`/inventory-transactions?${q}`);
    transactions.value = response.data ?? [];
    transactionMeta.value = response.meta ?? {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 20,
    };
    transactionState.value = "loaded";
  } catch (error) {
    transactionState.value = "error";
    transactionError.value = getErrorMessage(
      error,
      "Không thể tải lịch sử giao dịch."
    );
  }
}

function validateAdjust(): boolean {
  let valid = true;
  adjustErrors.quantity =
    adjustForm.quantity !== "" &&
    Number.isFinite(Number(adjustForm.quantity)) &&
    Number(adjustForm.quantity) !== 0
      ? ""
      : "Số lượng không hợp lệ";
  adjustErrors.reason = sanitizeString(adjustForm.reason)
    ? ""
    : "Lý do là bắt buộc";
  if (adjustErrors.quantity || adjustErrors.reason) valid = false;
  return valid;
}

async function handleAdjust(): Promise<void> {
  if (!validateAdjust()) return;
  if (!inventory.value) return;
  adjustPending.value = true;
  try {
    await api.post("/inventory/adjust", {
      variant_id: inventory.value.product_variant_id,
      warehouse_id: inventory.value.warehouse_id,
      quantity: Number(adjustForm.quantity),
      reason: sanitizeString(adjustForm.reason),
      lot_number: sanitizeString(adjustForm.lot_number) || null,
      unit_cost: adjustForm.unit_cost ? Number(adjustForm.unit_cost) : null,
    });
    toast.success("Đã điều chỉnh tồn kho");
    showAdjustModal.value = false;
    adjustForm.quantity = "";
    adjustForm.reason = "";
    adjustForm.lot_number = "";
    adjustForm.unit_cost = "";
    await fetchInventory();
    await fetchTransactions();
  } catch (error) {
    toast.error("Điều chỉnh thất bại", getErrorMessage(error));
  } finally {
    adjustPending.value = false;
  }
}

function onTransactionPageChange(page: number): void {
  transactionPage.value = page;
  fetchTransactions();
}

onMounted(async () => {
  await fetchInventory();
  if (inventory.value) {
    await fetchTransactions();
  }
});
</script>
