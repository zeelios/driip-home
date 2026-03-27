<template>
  <div>
    <!-- Toolbar -->
    <div class="flex items-start justify-between gap-3 mb-4.5 flex-wrap">
      <ZInput
        v-model="search"
        placeholder="Tìm mã, tên kho..."
        type="search"
        size="sm"
        class="flex-1 min-w-45 max-w-[320px]"
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
      <ZButton size="sm" @click="showCreateModal = true">
        <template #prefix>
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path d="M5 12h14M12 5v14" />
          </svg>
        </template>
        Thêm kho
      </ZButton>
    </div>

    <!-- Error -->
    <div
      v-if="listState === 'error'"
      class="flex items-center justify-between gap-3 py-3 px-4 mb-3.5 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
    >
      <span>{{ listError }}</span>
      <ZButton variant="ghost" size="sm" @click="fetchWarehouses"
        >Thử lại</ZButton
      >
    </div>

    <!-- Table -->
    <ZTable
      :columns="columns"
      :rows="warehouses"
      :loading="listState === 'loading'"
      :skeleton-rows="12"
      row-key="id"
      empty-title="Không có kho hàng"
      empty-description="Chưa có kho hàng nào. Tạo kho đầu tiên."
      :on-row-click="(row) => navigateTo(`/warehouse/${(row as WarehouseRow).id}`)"
    >
      <template #cell-code="{ row }">
        <span class="font-mono text-[0.8125rem] font-semibold">{{
          (row as WarehouseRow).code
        }}</span>
      </template>
      <template #cell-name="{ row }">
        <span class="font-medium text-white/90">{{
          (row as WarehouseRow).name
        }}</span>
      </template>
      <template #cell-type="{ row }">
        <ZBadge variant="info">{{
          warehouseTypeLabel((row as WarehouseRow).type)
        }}</ZBadge>
      </template>
      <template #cell-address="{ row }">
        <span class="text-[0.8125rem] text-white/60">{{
          (row as WarehouseRow).address ?? "—"
        }}</span>
      </template>
      <template #cell-manager="{ row }">
        {{ (row as WarehouseRow).manager?.name ?? "—" }}
      </template>
      <template #cell-is_active="{ row }">
        <ZBadge
          :variant="(row as WarehouseRow).is_active ? 'success' : 'neutral'"
        >
          {{ (row as WarehouseRow).is_active ? "Đang hoạt động" : "Tắt" }}
        </ZBadge>
      </template>
      <template #cell-created_at="{ row }">
        <span class="text-[0.8125rem] text-white/50">{{
          formatDate((row as WarehouseRow).created_at)
        }}</span>
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="flex items-center justify-between gap-3 pt-4 flex-wrap">
      <p class="m-0 text-[0.8125rem] text-white/40">
        {{ meta.total }} kho hàng
      </p>
      <ZPagination
        :current-page="meta.current_page"
        :total-pages="meta.last_page"
        @change="onPageChange"
      />
    </div>

    <!-- Create modal -->
    <ZModal v-model="showCreateModal" title="Thêm kho hàng mới" size="md">
      <div
        class="grid grid-cols-2 gap-4 [&>*:nth-child(2)]:col-span-full [&>*:nth-child(5)]:col-span-full [&>*:nth-child(8)]:col-span-full max-sm:grid-cols-1"
      >
        <ZInput
          v-model="form.code"
          label="Mã kho *"
          placeholder="WH-001"
          size="sm"
          :error="formErrors.code"
        />
        <ZInput
          v-model="form.name"
          label="Tên kho *"
          placeholder="Kho chính TP.HCM"
          size="sm"
          :error="formErrors.name"
        />
        <ZSelect
          v-model="form.type"
          :options="typeOptions"
          label="Loại kho *"
          size="sm"
          :error="formErrors.type"
        />
        <ZInput
          v-model="form.phone"
          label="Điện thoại"
          placeholder="0901234567"
          size="sm"
        />
        <ZInput
          v-model="form.address"
          label="Địa chỉ"
          placeholder="123 Đường ABC..."
          size="sm"
        />
        <ZInput
          v-model="form.province"
          label="Tỉnh/Thành phố"
          placeholder="TP.HCM"
          size="sm"
        />
        <ZInput
          v-model="form.district"
          label="Quận/Huyện"
          placeholder="Quận 1"
          size="sm"
        />
        <ZInput
          v-model="form.notes"
          label="Ghi chú"
          placeholder="Ghi chú về kho..."
          size="sm"
        />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          :disabled="formPending"
          @click="closeCreateModal"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="formPending"
          @click="handleCreate"
          >Tạo kho</ZButton
        >
      </template>
    </ZModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
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

interface WarehouseRow {
  id: string;
  code: string;
  name: string;
  type: string;
  address: string | null;
  province: string | null;
  district: string | null;
  phone: string | null;
  is_active: boolean;
  created_at: string | null;
  manager?: { name: string } | null;
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
const warehouses = ref<WarehouseRow[]>([]);
const meta = ref<ListMeta>({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
});
const search = ref("");
const currentPage = ref(1);

const showCreateModal = ref(false);
const formPending = ref(false);

const form = reactive({
  code: "",
  name: "",
  type: "",
  phone: "",
  address: "",
  province: "",
  district: "",
  notes: "",
});
const formErrors = reactive({
  code: "",
  name: "",
  type: "",
});

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const columns: TableColumn[] = [
  { key: "code", label: "Mã kho", skeletonWidth: "80px" },
  { key: "name", label: "Tên kho", skeletonWidth: "160px" },
  { key: "type", label: "Loại", skeletonWidth: "90px" },
  { key: "address", label: "Địa chỉ", skeletonWidth: "180px" },
  { key: "manager", label: "Quản lý", skeletonWidth: "120px" },
  { key: "is_active", label: "Trạng thái", skeletonWidth: "90px" },
  { key: "created_at", label: "Ngày tạo", skeletonWidth: "100px" },
];

const typeOptions: SelectOption[] = [
  { value: "main", label: "Kho chính" },
  { value: "branch", label: "Kho chi nhánh" },
  { value: "dropship", label: "Dropship" },
  { value: "return", label: "Kho trả hàng" },
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

async function fetchWarehouses(): Promise<void> {
  listState.value = "loading";
  listError.value = null;
  try {
    const params: Record<string, string> = {
      page: String(currentPage.value),
      per_page: "20",
    };
    if (search.value.trim()) params["search"] = search.value.trim();
    const q = new URLSearchParams(params).toString();
    const response = await api.get<{ data: WarehouseRow[]; meta: ListMeta }>(
      `/warehouses?${q}`
    );
    warehouses.value = response.data ?? [];
    meta.value = response.meta ?? {
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 20,
    };
    listState.value = "loaded";
  } catch (error) {
    listState.value = "error";
    listError.value = getErrorMessage(error, "Không thể tải danh sách kho.");
  }
}

function validateForm(): boolean {
  let valid = true;
  formErrors.code = sanitizeString(form.code) ? "" : "Mã kho là bắt buộc";
  formErrors.name = sanitizeString(form.name) ? "" : "Tên kho là bắt buộc";
  formErrors.type = form.type ? "" : "Loại kho là bắt buộc";
  if (formErrors.code || formErrors.name || formErrors.type) valid = false;
  return valid;
}

async function handleCreate(): Promise<void> {
  if (!validateForm()) return;
  formPending.value = true;
  try {
    await api.post("/warehouses", {
      code: sanitizeString(form.code).toUpperCase(),
      name: sanitizeString(form.name),
      type: form.type,
      phone: sanitizePhone(form.phone) || null,
      address: sanitizeString(form.address) || null,
      province: sanitizeString(form.province) || null,
      district: sanitizeString(form.district) || null,
      notes: sanitizeString(form.notes) || null,
    });
    toast.success("Đã tạo kho hàng");
    closeCreateModal();
    await fetchWarehouses();
  } catch (error) {
    toast.error("Tạo thất bại", getErrorMessage(error));
  } finally {
    formPending.value = false;
  }
}

function closeCreateModal(): void {
  showCreateModal.value = false;
  form.code = "";
  form.name = "";
  form.type = "";
  form.phone = "";
  form.address = "";
  form.province = "";
  form.district = "";
  form.notes = "";
  formErrors.code = "";
  formErrors.name = "";
  formErrors.type = "";
}

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    currentPage.value = 1;
    fetchWarehouses();
  }, 350);
}

function onPageChange(page: number): void {
  currentPage.value = page;
  fetchWarehouses();
}

onMounted(() => {
  fetchWarehouses();
});
</script>
