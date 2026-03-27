<template>
  <div>
    <!-- Loading -->
    <template v-if="store.isDetailLoading">
      <div class="flex items-center justify-between mb-6">
        <div class="flex flex-col gap-2 flex-1">
          <ZSkeleton height="1.5rem" width="200px" />
          <ZSkeleton height="0.875rem" width="120px" />
        </div>
        <ZSkeleton height="2.25rem" width="100px" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-[1fr_1.5fr] gap-3.5 mb-5">
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
      v-else-if="store.detailState === 'error'"
      title="Không thể tải sản phẩm"
      :description="store.detailError ?? ''"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="store.fetchProduct(id)"
          >Thử lại</ZButton
        >
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="store.currentProduct">
      <!-- Page header -->
      <div class="mb-5">
        <!-- Back link -->
        <NuxtLink
          to="/products"
          class="inline-flex items-center gap-1 text-[0.8125rem] text-white/50 no-underline transition-colors duration-130 hover:text-white/80 mb-4"
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
          Sản phẩm
        </NuxtLink>

        <!-- Product info and actions row -->
        <div
          class="flex flex-col sm:flex-row items-start justify-between gap-4"
        >
          <div class="flex items-center gap-3 flex-wrap">
            <div>
              <h1 class="m-0 text-lg font-bold text-white/95 leading-tight">
                {{ product.name }}
              </h1>
              <p class="m-0 text-[0.6875rem] text-white/40 font-mono">
                {{ product.sku_base ?? "—" }}
              </p>
            </div>
            <ZBadge
              :variant="productStatusVariant(product.status) as BadgeVariant"
            >
              {{ productStatusLabel(product.status) }}
            </ZBadge>
            <ZBadge v-if="product.is_featured" variant="warning"
              >Nổi bật</ZBadge
            >
          </div>
          <div class="flex gap-2 flex-wrap w-full sm:w-auto justify-end">
            <ZButton variant="outline" size="sm" @click="openEditModal"
              >Chỉnh sửa</ZButton
            >
            <ZButton
              variant="danger"
              size="sm"
              @click="showDeleteConfirm = true"
              >Xóa</ZButton
            >
          </div>
        </div>
      </div>

      <!-- Detail grid -->
      <div class="grid grid-cols-1 md:grid-cols-[1fr_1.5fr] gap-3.5 mb-5">
        <!-- Product info -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thông tin sản phẩm
          </p>
          <dl class="m-0">
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Danh mục</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{
                  (product.category as CategoryObj | null | undefined)?.name ??
                  "—"
                }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Thương hiệu</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{
                  (product.brand as BrandObj | null | undefined)?.name ?? "—"
                }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Giới tính</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ genderDisplayLabel(product.gender) }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Mùa</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ product.season ?? "—" }}
              </dd>
            </div>
            <div
              class="flex justify-between gap-3 py-1.25 border-b border-white/6 text-sm last:border-b-0"
            >
              <dt class="text-white/50 shrink-0">Ngày tạo</dt>
              <dd class="m-0 text-white/85 text-right wrap-break-word">
                {{ formatDate(product.created_at) }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Description -->
        <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4.5">
          <p
            class="m-0 mb-3.5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Mô tả
          </p>
          <p
            v-if="product.short_description"
            class="m-0 mb-3 text-sm text-white/60 italic"
          >
            {{ product.short_description }}
          </p>
          <p
            v-if="product.description"
            class="m-0 text-sm text-white/70 leading-relaxed whitespace-pre-wrap"
          >
            {{ product.description }}
          </p>
          <p
            v-if="!product.description && !product.short_description"
            class="m-0 text-sm text-white/40"
          >
            Chưa có mô tả.
          </p>
        </div>
      </div>

      <!-- Variants table -->
      <div class="mt-6">
        <div class="flex items-center justify-between mb-4">
          <p
            class="m-0 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Biến thể ({{ product.variants?.length ?? 0 }})
          </p>
        </div>
        <ZTable
          :columns="variantColumns"
          :rows="product.variants ?? []"
          row-key="id"
          empty-title="Chưa có biến thể"
          empty-description="Thêm biến thể cho sản phẩm này."
        >
          <template #cell-sku="{ row }">
            <span class="font-mono text-[0.8125rem] font-semibold">{{
              (row as VariantRow).sku
            }}</span>
          </template>
          <template #cell-attribute_values="{ row }">
            <span class="text-sm text-white/60">
              {{ formatAttributeValues((row as VariantRow).attribute_values) }}
            </span>
          </template>
          <template #cell-selling_price="{ row }">
            {{ formatVnd((row as VariantRow).selling_price) }}
          </template>
          <template #cell-compare_price="{ row }">
            <span class="text-white/40 line-through text-[0.8125rem]">{{
              formatVnd((row as VariantRow).compare_price)
            }}</span>
          </template>
          <template #cell-status="{ row }">
            <ZBadge
              :variant="productStatusVariant((row as VariantRow).status) as BadgeVariant"
            >
              {{ productStatusLabel((row as VariantRow).status) }}
            </ZBadge>
          </template>
        </ZTable>
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="showEditModal" title="Chỉnh sửa sản phẩm" size="md">
      <div class="flex flex-col gap-4">
        <ZInput
          v-model="editForm.name"
          label="Tên sản phẩm *"
          placeholder="Tên sản phẩm"
          :error="editErrors.name"
        />
        <ZSelect
          v-model="editForm.status"
          :options="statusOptions"
          label="Trạng thái"
        />
        <ZInput
          v-model="editForm.short_description"
          label="Mô tả ngắn"
          placeholder="Mô tả ngắn hiển thị trên trang sản phẩm"
        />
      </div>
      <template #footer>
        <ZButton
          variant="outline"
          size="sm"
          :disabled="store.formPending"
          @click="showEditModal = false"
          >Hủy</ZButton
        >
        <ZButton
          variant="primary"
          size="sm"
          :loading="store.formPending"
          @click="handleUpdate"
          >Lưu thay đổi</ZButton
        >
      </template>
    </ZModal>

    <!-- Delete confirm -->
    <ZConfirmDialog
      v-model="showDeleteConfirm"
      title="Xóa sản phẩm"
      message="Bạn có chắc muốn xóa sản phẩm này? Tất cả biến thể sẽ bị xóa và không thể khôi phục."
      confirm-label="Xóa sản phẩm"
      :dangerous="true"
      :loading="store.deletePending"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useProductsStore } from "~/stores/products";
import {
  formatVnd,
  formatDate,
  productStatusLabel,
  productStatusVariant,
  sanitizeString,
} from "~/utils/format";
import type { TableColumn } from "~/components/z/Table.vue";
import type { SelectOption } from "~/components/z/Select.vue";

definePageMeta({ layout: "panel" });

interface VariantRow {
  id: string;
  sku: string;
  attribute_values: unknown[];
  selling_price: number;
  compare_price: number;
  status: string;
}

interface CategoryObj {
  name?: string;
}
interface BrandObj {
  name?: string;
}

type BadgeVariant =
  | "default"
  | "success"
  | "warning"
  | "danger"
  | "info"
  | "neutral";

const route = useRoute();
const id = route.params.id as string;
const store = useProductsStore();

const showEditModal = ref(false);
const showDeleteConfirm = ref(false);

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const product = computed(() => store.currentProduct!);

const editForm = reactive({ name: "", status: "draft", short_description: "" });
const editErrors = reactive({ name: "" });

const statusOptions: SelectOption[] = [
  { value: "draft", label: "Nháp" },
  { value: "active", label: "Đang bán" },
  { value: "archived", label: "Lưu trữ" },
];

const variantColumns: TableColumn[] = [
  { key: "sku", label: "SKU", width: "140px", skeletonWidth: "100px" },
  { key: "attribute_values", label: "Thuộc tính", skeletonWidth: "120px" },
  {
    key: "selling_price",
    label: "Giá bán",
    align: "right",
    skeletonWidth: "90px",
  },
  {
    key: "compare_price",
    label: "Giá so sánh",
    align: "right",
    skeletonWidth: "90px",
  },
  { key: "status", label: "Trạng thái", skeletonWidth: "70px" },
];

function genderDisplayLabel(gender: string | null | undefined): string {
  const map: Record<string, string> = {
    male: "Nam",
    female: "Nữ",
    unisex: "Unisex",
  };
  return gender && map[gender] ? map[gender] : "—";
}

function formatAttributeValues(values: unknown[]): string {
  if (!Array.isArray(values) || !values.length) return "—";
  return values
    .map((v) => {
      if (typeof v === "object" && v !== null) {
        const obj = v as Record<string, unknown>;
        return String(obj["value"] ?? obj["name"] ?? "");
      }
      return String(v);
    })
    .filter(Boolean)
    .join(" / ");
}

function openEditModal(): void {
  const p = store.currentProduct;
  if (!p) return;
  editForm.name = p.name;
  editForm.status = p.status;
  editForm.short_description = p.short_description ?? "";
  editErrors.name = "";
  showEditModal.value = true;
}

async function handleUpdate(): Promise<void> {
  editErrors.name = sanitizeString(editForm.name)
    ? ""
    : "Tên sản phẩm là bắt buộc";
  if (editErrors.name) return;

  const ok = await store.updateProduct(id, {
    name: editForm.name,
    status: editForm.status,
    short_description: editForm.short_description || null,
  });
  if (ok) showEditModal.value = false;
}

async function handleDelete(): Promise<void> {
  const ok = await store.deleteProduct(id);
  if (ok) await navigateTo("/products");
}

onMounted(() => {
  store.fetchProduct(id);
});
</script>
