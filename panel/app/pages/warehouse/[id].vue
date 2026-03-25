<template>
  <div>
    <!-- Loading skeleton -->
    <template v-if="detailState === 'loading'">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <ZSkeleton variant="circle" width="3.5rem" height="3.5rem" />
          <div class="flex flex-col gap-1.5 flex-1">
            <ZSkeleton height="1.25rem" width="160px" />
            <ZSkeleton height="0.875rem" width="100px" />
          </div>
        </div>
        <ZSkeleton height="2.25rem" width="120px" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-6">
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-for="i in 2"
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
      title="Không thể tải thông tin kho"
      :description="detailError ?? ''"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="fetchWarehouse"
          >Thử lại</ZButton
        >
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="warehouse">
      <!-- Page header -->
      <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">
        <div class="flex items-center flex-wrap gap-3">
          <NuxtLink
            to="/warehouse"
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
            Kho hàng
          </NuxtLink>
          <div class="flex items-center gap-3">
            <div
              class="w-12 h-12 rounded-full bg-[#f0efed] text-[#333] text-sm font-bold flex items-center justify-center shrink-0"
            >
              WH
            </div>
            <div>
              <h1 class="m-0 text-lg font-bold text-white/95">
                {{ warehouse.name }}
              </h1>
              <p class="m-0 text-[0.6875rem] text-white/40 font-mono">
                {{ warehouse.code }}
              </p>
            </div>
          </div>
          <ZBadge :variant="warehouse.is_active ? 'success' : 'neutral'">
            {{ warehouse.is_active ? "Đang hoạt động" : "Tắt" }}
          </ZBadge>
        </div>
        <div class="flex gap-2 flex-wrap">
          <ZButton variant="outline" size="sm" @click="openEditModal"
            >Chỉnh sửa</ZButton
          >
          <ZButton
            v-if="warehouse.is_active"
            variant="ghost"
            size="sm"
            :loading="togglePending"
            @click="showDeactivateConfirm = true"
          >
            Tắt kho
          </ZButton>
          <ZButton
            v-else
            variant="outline"
            size="sm"
            :loading="togglePending"
            @click="handleActivate"
          >
            Bật kho
          </ZButton>
        </div>
      </div>

      <!-- Detail grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-6">
        <!-- Basic info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin cơ bản
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Mã kho</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouse.code }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Loại kho</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouseTypeLabel(warehouse.type) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Điện thoại</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouse.phone ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Ngày tạo</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ formatDate(warehouse.created_at) }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Address info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Địa chỉ
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Địa chỉ</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouse.address ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Quận/Huyện</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouse.district ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Tỉnh/Thành</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouse.province ?? "—" }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Manager info -->
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-if="warehouse.manager"
        >
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Quản lý kho
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Họ tên</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouse.manager.name }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Email</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouse.manager.email ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-[0.3125rem] border-b border-white/[0.06] text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Điện thoại</dt>
              <dd class="m-0 text-white/85 text-right break-words">
                {{ warehouse.manager.phone ?? "—" }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Notes -->
        <div
          class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5"
          v-if="warehouse.notes"
        >
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Ghi chú
          </p>
          <p class="m-0 text-sm text-white/65 leading-relaxed">
            {{ warehouse.notes }}
          </p>
        </div>
      </div>

      <!-- Inventory section -->
      <div class="my-6 pb-3 border-b border-white/10">
        <h2 class="m-0 text-base font-semibold text-white/90">
          Tồn kho tại kho
        </h2>
      </div>

      <!-- Inventory Error -->
      <div
        v-if="inventoryState === 'error'"
        class="flex items-center justify-between gap-3 py-3 px-4 mb-3.5 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
      >
        <span>{{ inventoryError }}</span>
        <ZButton variant="ghost" size="sm" @click="fetchInventory"
          >Thử lại</ZButton
        >
      </div>

      <!-- Inventory Table -->
      <ZTable
        :columns="inventoryColumns"
        :rows="inventory"
        :loading="inventoryState === 'loading'"
        :skeleton-rows="8"
        row-key="id"
        empty-title="Không có tồn kho"
        empty-description="Chưa có sản phẩm nào trong kho này."
      >
        <template #cell-sku="{ row }">
          <span class="font-mono text-[0.8125rem] font-semibold">{{
            (row as InventoryRow).variant?.sku ?? "—"
          }}</span>
        </template>
        <template #cell-product="{ row }">
          <span class="font-medium text-white/90">{{
            (row as InventoryRow).variant?.product?.name ?? "—"
          }}</span>
        </template>
        <template #cell-quantity_available="{ row }">
          <span
            class="font-semibold"
            :class="(row as InventoryRow).quantity_available <= ((row as InventoryRow).reorder_point ?? 5) ? 'text-red-500' : 'text-white/90'"
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
      </ZTable>

      <!-- Inventory Pagination -->
      <div
        class="flex items-center justify-between gap-3 pt-4 flex-wrap"
        v-if="inventoryState === 'loaded' && inventory.length > 0"
      >
        <p class="m-0 text-[0.8125rem] text-white/40">
          {{ inventoryMeta.total }} mục tồn kho
        </p>
        <ZPagination
          :current-page="inventoryMeta.current_page"
          :total-pages="inventoryMeta.last_page"
          @change="onInventoryPageChange"
        />
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="showEditModal" title="Chỉnh sửa kho hàng" size="md">
      <div
        class="grid grid-cols-2 gap-4 [&>*:first-child]:col-span-full [&>*:nth-child(4)]:col-span-full [&>*:nth-child(7)]:col-span-full max-sm:grid-cols-1"
      >
        <ZInput
          v-model="editForm.name"
          label="Tên kho *"
          placeholder="Kho chính TP.HCM"
          :error="editErrors.name"
        />
        <ZSelect
          v-model="editForm.type"
          :options="typeOptions"
          label="Loại kho *"
          :error="editErrors.type"
        />
        <ZInput
          v-model="editForm.phone"
          label="Điện thoại"
          placeholder="0901234567"
        />
        <ZInput
          v-model="editForm.address"
          label="Địa chỉ"
          placeholder="123 Đường ABC..."
        />
        <ZInput
          v-model="editForm.province"
          label="Tỉnh/Thành phố"
          placeholder="TP.HCM"
        />
        <ZInput
          v-model="editForm.district"
          label="Quận/Huyện"
          placeholder="Quận 1"
        />
        <ZInput
          v-model="editForm.notes"
          label="Ghi chú"
          placeholder="Ghi chú về kho..."
        />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          :disabled="editPending"
          @click="showEditModal = false"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="editPending"
          @click="handleUpdate"
          >Lưu thay đổi</ZButton
        >
      </template>
    </ZModal>

    <!-- Deactivate confirm -->
    <ZConfirmDialog
      v-model="showDeactivateConfirm"
      title="Tắt kho hàng"
      message="Bạn có chắc muốn tắt kho hàng này? Sản phẩm sẽ không thể xuất hàng từ kho này."
      confirm-label="Tắt kho"
      :dangerous="true"
      :loading="togglePending"
      @confirm="handleDeactivate"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import {
  formatDate,
  getErrorMessage,
  sanitizeString,
  sanitizePhone,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

interface Manager {
  id?: string;
  name: string;
  email?: string | null;
  phone?: string | null;
}

interface Warehouse {
  id: string;
  code: string;
  name: string;
  type: string;
  address: string | null;
  province: string | null;
  district: string | null;
  phone: string | null;
  is_active: boolean;
  notes: string | null;
  created_at: string | null;
  manager?: Manager | null;
}

interface InventoryVariant {
  sku: string;
  product?: { name: string } | null;
}

interface InventoryRow {
  id: string;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  reorder_point: number | null;
  variant?: InventoryVariant | null;
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
const warehouse = ref<Warehouse | null>(null);

// Inventory states
const inventoryState = ref<"idle" | "loading" | "loaded" | "error">("idle");
const inventoryError = ref<string | null>(null);
const inventory = ref<InventoryRow[]>([]);
const inventoryMeta = ref<ListMeta>({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
});
const inventoryPage = ref(1);

// Modals
const showEditModal = ref(false);
const showDeactivateConfirm = ref(false);
const editPending = ref(false);
const togglePending = ref(false);

const editForm = reactive({
  name: "",
  type: "",
  phone: "",
  address: "",
  province: "",
  district: "",
  notes: "",
});
const editErrors = reactive({
  name: "",
  type: "",
});

const typeOptions: SelectOption[] = [
  { value: "main", label: "Kho chính" },
  { value: "branch", label: "Kho chi nhánh" },
  { value: "dropship", label: "Dropship" },
  { value: "return", label: "Kho trả hàng" },
];

const inventoryColumns: TableColumn[] = [
  { key: "sku", label: "SKU", skeletonWidth: "100px" },
  { key: "product", label: "Sản phẩm", skeletonWidth: "160px" },
  {
    key: "quantity_available",
    label: "Có thể bán",
    align: "right",
    skeletonWidth: "60px",
  },
  {
    key: "quantity_on_hand",
    label: "Tồn kho",
    align: "right",
    skeletonWidth: "60px",
  },
  {
    key: "quantity_reserved",
    label: "Đặt trước",
    align: "right",
    skeletonWidth: "60px",
  },
];

function warehouseTypeLabel(type: string): string {
  const map: Record<string, string> = {
    main: "Kho chính",
    branch: "Chi nhánh",
    dropship: "Dropship",
    return: "Kho trả",
  };
  return map[type] ?? type;
}

async function fetchWarehouse(): Promise<void> {
  detailState.value = "loading";
  detailError.value = null;
  try {
    const response = await api.get<{ data: Warehouse }>(`/warehouses/${id}`);
    warehouse.value = response.data ?? null;
    detailState.value = "loaded";
  } catch (error) {
    detailState.value = "error";
    detailError.value = getErrorMessage(error, "Không thể tải thông tin kho.");
  }
}

async function fetchInventory(): Promise<void> {
  inventoryState.value = "loading";
  inventoryError.value = null;
  try {
    const params: Record<string, string> = {
      page: String(inventoryPage.value),
      per_page: "20",
      warehouse_id: id,
    };
    const q = new URLSearchParams(params).toString();
    const response = await api.get<{ data: InventoryRow[]; meta: ListMeta }>(
      `/inventory?${q}`
    );
    inventory.value = response.data ?? [];
    inventoryMeta.value = response.meta ?? {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 20,
    };
    inventoryState.value = "loaded";
  } catch (error) {
    inventoryState.value = "error";
    inventoryError.value = getErrorMessage(
      error,
      "Không thể tải dữ liệu tồn kho."
    );
  }
}

function openEditModal(): void {
  if (!warehouse.value) return;
  editForm.name = warehouse.value.name;
  editForm.type = warehouse.value.type;
  editForm.phone = warehouse.value.phone ?? "";
  editForm.address = warehouse.value.address ?? "";
  editForm.province = warehouse.value.province ?? "";
  editForm.district = warehouse.value.district ?? "";
  editForm.notes = warehouse.value.notes ?? "";
  editErrors.name = "";
  editErrors.type = "";
  showEditModal.value = true;
}

function validateEditForm(): boolean {
  let valid = true;
  editErrors.name = sanitizeString(editForm.name) ? "" : "Tên kho là bắt buộc";
  editErrors.type = editForm.type ? "" : "Loại kho là bắt buộc";
  if (editErrors.name || editErrors.type) valid = false;
  return valid;
}

async function handleUpdate(): Promise<void> {
  if (!validateEditForm()) return;
  editPending.value = true;
  try {
    await api.patch(`/warehouses/${id}`, {
      name: sanitizeString(editForm.name),
      type: editForm.type,
      phone: sanitizePhone(editForm.phone) || null,
      address: sanitizeString(editForm.address) || null,
      province: sanitizeString(editForm.province) || null,
      district: sanitizeString(editForm.district) || null,
      notes: sanitizeString(editForm.notes) || null,
    });
    toast.success("Đã cập nhật kho hàng");
    showEditModal.value = false;
    await fetchWarehouse();
  } catch (error) {
    toast.error("Cập nhật thất bại", getErrorMessage(error));
  } finally {
    editPending.value = false;
  }
}

async function handleDeactivate(): Promise<void> {
  togglePending.value = true;
  try {
    await api.patch(`/warehouses/${id}`, { is_active: false });
    toast.success("Đã tắt kho hàng");
    showDeactivateConfirm.value = false;
    await fetchWarehouse();
  } catch (error) {
    toast.error("Thao tác thất bại", getErrorMessage(error));
  } finally {
    togglePending.value = false;
  }
}

async function handleActivate(): Promise<void> {
  togglePending.value = true;
  try {
    await api.patch(`/warehouses/${id}`, { is_active: true });
    toast.success("Đã bật kho hàng");
    await fetchWarehouse();
  } catch (error) {
    toast.error("Thao tác thất bại", getErrorMessage(error));
  } finally {
    togglePending.value = false;
  }
}

function onInventoryPageChange(page: number): void {
  inventoryPage.value = page;
  fetchInventory();
}

onMounted(() => {
  fetchWarehouse();
  fetchInventory();
});
</script>
