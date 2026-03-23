<template>
  <div>
    <!-- Toolbar -->
    <div class="page-toolbar">
      <ZInput
        v-model="search"
        placeholder="Tìm SKU, sản phẩm..."
        type="search"
        class="page-toolbar__search"
        @input="onSearchInput"
      >
        <template #prefix>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </template>
      </ZInput>
      <ZButton variant="outline" size="sm" @click="showAdjustModal = true">
        Điều chỉnh tồn kho
      </ZButton>
    </div>

    <!-- Error -->
    <div v-if="listState === 'error'" class="page-error-bar">
      <span>{{ listError }}</span>
      <ZButton variant="ghost" size="sm" @click="fetchInventory">Thử lại</ZButton>
    </div>

    <!-- Table -->
    <ZTable
      :columns="columns"
      :rows="inventory"
      :loading="listState === 'loading'"
      :skeleton-rows="12"
      row-key="id"
      empty-title="Không có dữ liệu tồn kho"
      empty-description="Chưa có dữ liệu tồn kho nào."
    >
      <template #cell-sku="{ row }">
        <span class="mono-id">{{ (row as InventoryRow).variant?.sku ?? '—' }}</span>
      </template>
      <template #cell-product="{ row }">
        <span class="cell-product">{{ (row as InventoryRow).variant?.product?.name ?? '—' }}</span>
      </template>
      <template #cell-warehouse="{ row }">
        {{ (row as InventoryRow).warehouse?.name ?? '—' }}
      </template>
      <template #cell-quantity_available="{ row }">
        <span
          class="cell-qty"
          :class="{ 'cell-qty--low': (row as InventoryRow).quantity_available <= ((row as InventoryRow).reorder_point ?? 5) }"
        >
          {{ (row as InventoryRow).quantity_available }}
        </span>
      </template>
      <template #cell-quantity_on_hand="{ row }">
        {{ (row as InventoryRow).quantity_on_hand }}
      </template>
      <template #cell-quantity_reserved="{ row }">
        {{ (row as InventoryRow).quantity_reserved }}
      </template>
      <template #cell-reorder_point="{ row }">
        {{ (row as InventoryRow).reorder_point ?? '—' }}
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="page-footer">
      <p class="page-footer__count">{{ meta.total }} mục tồn kho</p>
      <ZPagination
        :current-page="meta.current_page"
        :total-pages="meta.last_page"
        @change="onPageChange"
      />
    </div>

    <!-- Adjust modal -->
    <ZModal v-model="showAdjustModal" title="Điều chỉnh tồn kho" size="sm">
      <div class="form-stack">
        <ZInput
          v-model="adjustForm.variant_id"
          label="Mã biến thể (ID) *"
          placeholder="uuid của variant"
          :error="adjustErrors.variant_id"
        />
        <ZInput
          v-model="adjustForm.warehouse_id"
          label="Mã kho (ID) *"
          placeholder="uuid của warehouse"
          :error="adjustErrors.warehouse_id"
        />
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
          placeholder="Nhập hàng, hàng lỗi..."
          :error="adjustErrors.reason"
        />
      </div>
      <template #footer>
        <ZButton variant="outline" size="sm" :disabled="adjustPending" @click="showAdjustModal = false">Hủy</ZButton>
        <ZButton variant="primary" size="sm" :loading="adjustPending" @click="handleAdjust">Xác nhận</ZButton>
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage, sanitizeString } from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";

definePageMeta({ layout: "panel" });

interface InventoryRow {
  id: string;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  reorder_point: number | null;
  variant?: { sku: string; product?: { name: string } | null } | null;
  warehouse?: { name: string } | null;
}

interface ListMeta { current_page: number; last_page: number; total: number; per_page: number; }

const api = useApi();
const toast = useToast();

const listState = ref<"idle" | "loading" | "loaded" | "error">("idle");
const listError = ref<string | null>(null);
const inventory = ref<InventoryRow[]>([]);
const meta = ref<ListMeta>({ current_page: 1, last_page: 1, total: 0, per_page: 20 });
const search = ref("");
const currentPage = ref(1);

const showAdjustModal = ref(false);
const adjustPending = ref(false);
const adjustForm = reactive({ variant_id: "", warehouse_id: "", quantity: "", reason: "" });
const adjustErrors = reactive({ variant_id: "", warehouse_id: "", quantity: "", reason: "" });

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const columns: TableColumn[] = [
  { key: "sku", label: "SKU", skeletonWidth: "100px" },
  { key: "product", label: "Sản phẩm", skeletonWidth: "160px" },
  { key: "warehouse", label: "Kho", skeletonWidth: "100px" },
  { key: "quantity_available", label: "Có thể bán", align: "right", skeletonWidth: "60px" },
  { key: "quantity_on_hand", label: "Tồn kho", align: "right", skeletonWidth: "60px" },
  { key: "quantity_reserved", label: "Đặt trước", align: "right", skeletonWidth: "60px" },
  { key: "reorder_point", label: "Điểm đặt lại", align: "right", skeletonWidth: "60px" },
];

async function fetchInventory(): Promise<void> {
  listState.value = "loading";
  listError.value = null;
  try {
    const params: Record<string, string> = { page: String(currentPage.value), per_page: "20" };
    if (search.value.trim()) params["search"] = search.value.trim();
    const q = new URLSearchParams(params).toString();
    const response = await api.get<{ data: InventoryRow[]; meta: ListMeta }>(`/inventory?${q}`);
    inventory.value = response.data ?? [];
    meta.value = response.meta ?? { current_page: 1, last_page: 1, total: 0, per_page: 20 };
    listState.value = "loaded";
  } catch (error) {
    listState.value = "error";
    listError.value = getErrorMessage(error, "Không thể tải dữ liệu tồn kho.");
  }
}

function validateAdjust(): boolean {
  let valid = true;
  adjustErrors.variant_id = sanitizeString(adjustForm.variant_id) ? "" : "Bắt buộc";
  adjustErrors.warehouse_id = sanitizeString(adjustForm.warehouse_id) ? "" : "Bắt buộc";
  adjustErrors.quantity = adjustForm.quantity !== "" && Number.isFinite(Number(adjustForm.quantity)) ? "" : "Số lượng không hợp lệ";
  adjustErrors.reason = sanitizeString(adjustForm.reason) ? "" : "Lý do là bắt buộc";
  if (adjustErrors.variant_id || adjustErrors.warehouse_id || adjustErrors.quantity || adjustErrors.reason) valid = false;
  return valid;
}

async function handleAdjust(): Promise<void> {
  if (!validateAdjust()) return;
  adjustPending.value = true;
  try {
    await api.post("/inventory/adjust", {
      variant_id: sanitizeString(adjustForm.variant_id),
      warehouse_id: sanitizeString(adjustForm.warehouse_id),
      quantity: Number(adjustForm.quantity),
      reason: sanitizeString(adjustForm.reason),
    });
    toast.success("Đã điều chỉnh tồn kho");
    showAdjustModal.value = false;
    adjustForm.variant_id = ""; adjustForm.warehouse_id = "";
    adjustForm.quantity = ""; adjustForm.reason = "";
    await fetchInventory();
  } catch (error) {
    toast.error("Điều chỉnh thất bại", getErrorMessage(error));
  } finally {
    adjustPending.value = false;
  }
}

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { currentPage.value = 1; fetchInventory(); }, 350);
}

function onPageChange(page: number): void {
  currentPage.value = page;
  fetchInventory();
}

onMounted(() => { fetchInventory(); });
</script>

<style scoped>
.page-toolbar {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 0.75rem; margin-bottom: 1.125rem; flex-wrap: wrap;
}
.page-toolbar__search { flex: 1; min-width: 180px; max-width: 320px; }
.page-error-bar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 0.75rem; padding: 0.75rem 1rem; margin-bottom: 0.875rem;
  background: #fff5f5; border: 1px solid #fecaca; border-radius: 8px;
  font-size: 0.875rem; color: #b91c1c;
}
.page-footer {
  display: flex; align-items: center; justify-content: space-between;
  gap: 0.75rem; padding-top: 1rem; flex-wrap: wrap;
}
.page-footer__count { margin: 0; font-size: 0.8125rem; color: #888; }
.mono-id { font-family: ui-monospace, monospace; font-size: 0.8125rem; font-weight: 600; }
.cell-product { font-weight: 500; color: #1a1a18; }
.cell-qty { font-weight: 600; color: #1a1a18; }
.cell-qty--low { color: #dc2626; }
.form-stack { display: flex; flex-direction: column; gap: 1rem; }
</style>
