<template>
  <div>
    <!-- Toolbar -->
    <div
      class="flex flex-col sm:flex-row items-start justify-between gap-3 mb-4 sm:mb-4.5"
    >
      <div class="flex flex-col sm:flex-row gap-2.5 flex-1 w-full">
        <ZInput
          v-model="search"
          placeholder="Tìm sản phẩm, SKU..."
          type="search"
          class="flex-1 w-full min-w-0"
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
          :options="statusOptions"
          placeholder="Tất cả trạng thái"
          class="w-full sm:w-auto sm:min-w-37.5"
          @change="onFilterChange"
        />
      </div>
      <ZButton
        size="sm"
        @click="showCreateModal = true"
        class="w-full sm:w-auto shrink-0"
      >
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
        Thêm sản phẩm
      </ZButton>
    </div>

    <!-- Error -->
    <div
      v-if="store.listState === 'error'"
      class="flex items-center justify-between gap-3 py-3 px-4 mb-3.5 bg-red-500/10 border border-red-500/30 rounded-lg text-sm text-red-500"
    >
      <span>{{ store.listError }}</span>
      <ZButton variant="ghost" size="sm" @click="store.fetchProducts()"
        >Thử lại</ZButton
      >
    </div>

    <!-- Table -->
    <ZTable
      :columns="columns"
      :rows="store.products"
      :loading="store.isListLoading"
      :skeleton-rows="12"
      row-key="id"
      empty-title="Không có sản phẩm"
      empty-description="Chưa có sản phẩm nào khớp với bộ lọc."
      :on-row-click="(row) => navigateTo(`/products/${(row as ProductRow).id}`)"
    >
      <template #cell-name="{ row }">
        <div class="flex flex-col gap-0.5">
          <p class="m-0 font-medium text-white/90 text-sm">
            {{ (row as ProductRow).name }}
          </p>
          <p class="m-0 text-[0.6875rem] text-white/50 font-mono">
            {{ (row as ProductRow).sku_base ?? "—" }}
          </p>
        </div>
      </template>
      <template #cell-brand="{ row }">
        <span class="text-white/40 text-sm">{{
          (row as ProductRow).brand?.name ?? "—"
        }}</span>
      </template>
      <template #cell-category="{ row }">
        <span class="text-white/40 text-sm">{{
          (row as ProductRow).category?.name ?? "—"
        }}</span>
      </template>
      <template #cell-status="{ row }">
        <ZBadge
          :variant="productStatusVariant((row as ProductRow).status) as BadgeVariant"
        >
          {{ productStatusLabel((row as ProductRow).status) }}
        </ZBadge>
      </template>
      <template #cell-variants="{ row }">
        {{ (row as ProductRow).variants?.length ?? 0 }}
      </template>
      <template #cell-is_featured="{ row }">
        <ZBadge v-if="(row as ProductRow).is_featured" variant="warning"
          >Nổi bật</ZBadge
        >
        <span v-else class="text-white/40 text-sm">—</span>
      </template>
      <template #cell-created_at="{ row }">
        <span class="text-[0.8125rem] text-white/50">{{
          formatDate((row as ProductRow).created_at)
        }}</span>
      </template>
      <template #cell-actions="{ row }">
        <ZButton
          variant="ghost"
          size="sm"
          icon-only
          @click.stop="openDeleteConfirm((row as ProductRow).id)"
          aria-label="Xóa sản phẩm"
        >
          <svg
            width="15"
            height="15"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <polyline points="3 6 5 6 21 6" />
            <path
              d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"
            />
          </svg>
        </ZButton>
      </template>
    </ZTable>

    <!-- Pagination -->
    <div class="flex items-center justify-between gap-3 pt-4 flex-wrap">
      <p class="m-0 text-[0.8125rem] text-white/40">
        {{ store.meta.total }} sản phẩm
      </p>
      <ZPagination
        :current-page="store.meta.current_page"
        :total-pages="store.meta.last_page"
        @change="onPageChange"
      />
    </div>

    <!-- Create modal -->
    <ZModal v-model="showCreateModal" title="Thêm sản phẩm mới" size="md">
      <div class="flex flex-col gap-4">
        <ZInput
          v-model="form.name"
          label="Tên sản phẩm *"
          placeholder="Áo thun unisex..."
          :error="formErrors.name"
        />
        <ZInput
          v-model="form.sku_base"
          label="SKU cơ sở"
          placeholder="SHIRT-001"
        />
        <ZSelect
          v-model="form.status"
          :options="statusOptions"
          label="Trạng thái"
          placeholder="Chọn trạng thái"
        />
        <ZSelect
          v-model="form.gender"
          :options="genderOptions"
          label="Giới tính"
          placeholder="Chọn giới tính"
        />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          :disabled="store.formPending"
          @click="closeCreateModal"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="store.formPending"
          @click="handleCreate"
          >Tạo sản phẩm</ZButton
        >
      </template>
    </ZModal>

    <!-- Delete confirm -->
    <ZConfirmDialog
      v-model="showDeleteConfirm"
      title="Xóa sản phẩm"
      message="Bạn có chắc muốn xóa sản phẩm này? Hành động này không thể hoàn tác."
      confirm-label="Xóa sản phẩm"
      :dangerous="true"
      :loading="store.deletePending"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { useProductsStore } from "~/stores/products";
import {
  formatDate,
  productStatusLabel,
  productStatusVariant,
  sanitizeString,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

interface ProductRow {
  id: string;
  name: string;
  sku_base: string | null;
  status: string;
  is_featured: boolean;
  brand?: { name: string } | null;
  category?: { name: string } | null;
  variants?: unknown[];
  created_at: string | null;
}

type BadgeVariant =
  | "default"
  | "success"
  | "warning"
  | "danger"
  | "info"
  | "neutral";

const store = useProductsStore();
const search = ref(store.filters.search);
const statusFilter = ref(store.filters.status);
const showCreateModal = ref(false);
const showDeleteConfirm = ref(false);
const deleteTargetId = ref<string | null>(null);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

const form = reactive({ name: "", sku_base: "", status: "draft", gender: "" });
const formErrors = reactive({ name: "" });

const columns: TableColumn[] = [
  { key: "name", label: "Sản phẩm", skeletonWidth: "200px" },
  { key: "brand", label: "Thương hiệu", skeletonWidth: "100px" },
  { key: "category", label: "Danh mục", skeletonWidth: "100px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "80px" },
  {
    key: "variants",
    label: "Biến thể",
    align: "center",
    width: "80px",
    skeletonWidth: "30px",
  },
  {
    key: "is_featured",
    label: "Nổi bật",
    align: "center",
    skeletonWidth: "60px",
  },
  { key: "created_at", label: "Ngày tạo", skeletonWidth: "100px" },
  { key: "actions", label: "", width: "50px", skeletonWidth: "30px" },
];

const statusOptions: SelectOption[] = [
  { value: "draft", label: "Nháp" },
  { value: "active", label: "Đang bán" },
  { value: "archived", label: "Lưu trữ" },
];

const genderOptions: SelectOption[] = [
  { value: "unisex", label: "Unisex" },
  { value: "male", label: "Nam" },
  { value: "female", label: "Nữ" },
];

function validateForm(): boolean {
  formErrors.name = sanitizeString(form.name) ? "" : "Tên sản phẩm là bắt buộc";
  return !formErrors.name;
}

async function handleCreate(): Promise<void> {
  if (!validateForm()) return;
  const id = await store.createProduct({
    name: form.name,
    sku_base: form.sku_base || null,
    status: form.status || "draft",
    gender: form.gender || null,
  });
  if (id) {
    closeCreateModal();
    await navigateTo(`/products/${id}`);
  }
}

function closeCreateModal(): void {
  showCreateModal.value = false;
  form.name = "";
  form.sku_base = "";
  form.status = "draft";
  form.gender = "";
  formErrors.name = "";
}

function openDeleteConfirm(id: string): void {
  deleteTargetId.value = id;
  showDeleteConfirm.value = true;
}

async function handleDelete(): Promise<void> {
  if (!deleteTargetId.value) return;
  const ok = await store.deleteProduct(deleteTargetId.value);
  if (ok) {
    showDeleteConfirm.value = false;
    deleteTargetId.value = null;
  }
}

function onSearchInput(): void {
  if (searchTimer) clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    store.setFilters({ search: search.value });
    store.fetchProducts();
  }, 350);
}

function onFilterChange(): void {
  store.setFilters({ status: statusFilter.value });
  store.fetchProducts();
}

function onPageChange(page: number): void {
  store.setPage(page);
  store.fetchProducts();
}

onMounted(() => {
  store.fetchProducts();
});
</script>
