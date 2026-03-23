<template>
  <div>
    <!-- Loading -->
    <template v-if="store.isDetailLoading">
      <div class="detail-header-skeleton">
        <div style="display:flex;flex-direction:column;gap:0.5rem;flex:1">
          <ZSkeleton height="1.5rem" width="200px" />
          <ZSkeleton height="0.875rem" width="120px" />
        </div>
        <ZSkeleton height="2.25rem" width="100px" />
      </div>
      <div class="detail-grid">
        <div class="detail-card" v-for="i in 2" :key="i">
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
        <ZButton variant="outline" size="sm" @click="store.fetchProduct(id)">Thử lại</ZButton>
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else-if="store.currentProduct">
      <!-- Page header -->
      <div class="detail-page-header">
        <div class="detail-page-header__left">
          <NuxtLink to="/products" class="detail-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Sản phẩm
          </NuxtLink>
          <div>
            <h1 class="detail-page-title">{{ product.name }}</h1>
            <p class="product-sku">{{ product.sku_base ?? '—' }}</p>
          </div>
          <ZBadge :variant="productStatusVariant(product.status) as BadgeVariant">
            {{ productStatusLabel(product.status) }}
          </ZBadge>
          <ZBadge v-if="product.is_featured" variant="amber">Nổi bật</ZBadge>
        </div>
        <div class="detail-page-header__actions">
          <ZButton variant="outline" size="sm" @click="openEditModal">Chỉnh sửa</ZButton>
          <ZButton variant="danger" size="sm" @click="showDeleteConfirm = true">Xóa</ZButton>
        </div>
      </div>

      <!-- Detail grid -->
      <div class="detail-grid">
        <!-- Product info -->
        <div class="detail-card">
          <p class="detail-card__title">Thông tin sản phẩm</p>
          <dl class="detail-dl">
            <div class="detail-dl__row">
              <dt>Danh mục</dt>
              <dd>{{ (product.category as CategoryObj | null | undefined)?.name ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Thương hiệu</dt>
              <dd>{{ (product.brand as BrandObj | null | undefined)?.name ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Giới tính</dt>
              <dd>{{ genderDisplayLabel(product.gender) }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Mùa</dt>
              <dd>{{ product.season ?? '—' }}</dd>
            </div>
            <div class="detail-dl__row">
              <dt>Ngày tạo</dt>
              <dd>{{ formatDate(product.created_at) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Description -->
        <div class="detail-card detail-card--wide">
          <p class="detail-card__title">Mô tả</p>
          <p v-if="product.short_description" class="product-short-desc">
            {{ product.short_description }}
          </p>
          <p v-if="product.description" class="product-desc">{{ product.description }}</p>
          <p v-if="!product.description && !product.short_description" class="text-muted">Chưa có mô tả.</p>
        </div>
      </div>

      <!-- Variants table -->
      <div class="detail-section">
        <p class="detail-section__title">Biến thể ({{ product.variants?.length ?? 0 }})</p>
        <ZTable
          :columns="variantColumns"
          :rows="product.variants ?? []"
          row-key="id"
          empty-title="Chưa có biến thể"
          empty-description="Thêm biến thể cho sản phẩm này."
        >
          <template #cell-sku="{ row }">
            <span class="mono-id">{{ (row as VariantRow).sku }}</span>
          </template>
          <template #cell-attribute_values="{ row }">
            <span class="variant-attrs">
              {{ formatAttributeValues((row as VariantRow).attribute_values) }}
            </span>
          </template>
          <template #cell-selling_price="{ row }">
            {{ formatVnd((row as VariantRow).selling_price) }}
          </template>
          <template #cell-compare_price="{ row }">
            <span class="compare-price">{{ formatVnd((row as VariantRow).compare_price) }}</span>
          </template>
          <template #cell-status="{ row }">
            <ZBadge :variant="productStatusVariant((row as VariantRow).status) as BadgeVariant">
              {{ productStatusLabel((row as VariantRow).status) }}
            </ZBadge>
          </template>
        </ZTable>
      </div>
    </template>

    <!-- Edit modal -->
    <ZModal v-model="showEditModal" title="Chỉnh sửa sản phẩm" size="md">
      <div class="form-stack">
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
        <ZButton variant="outline" size="sm" :disabled="store.formPending" @click="showEditModal = false">Hủy</ZButton>
        <ZButton variant="primary" size="sm" :loading="store.formPending" @click="handleUpdate">Lưu thay đổi</ZButton>
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
  formatVnd, formatDate,
  productStatusLabel, productStatusVariant,
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

interface CategoryObj { name?: string }
interface BrandObj { name?: string }

type BadgeVariant = "default" | "success" | "warning" | "danger" | "info" | "neutral" | "amber";

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
  { key: "selling_price", label: "Giá bán", align: "right", skeletonWidth: "90px" },
  { key: "compare_price", label: "Giá so sánh", align: "right", skeletonWidth: "90px" },
  { key: "status", label: "Trạng thái", skeletonWidth: "70px" },
];

function genderDisplayLabel(gender: string | null | undefined): string {
  const map: Record<string, string> = { male: "Nam", female: "Nữ", unisex: "Unisex" };
  return (gender && map[gender]) ? map[gender] : "—";
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
  editErrors.name = sanitizeString(editForm.name) ? "" : "Tên sản phẩm là bắt buộc";
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

<style scoped>
.detail-page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.detail-page-header__left {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.detail-back {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  color: #888;
  text-decoration: none;
  transition: color 130ms;
}
.detail-back:hover { color: #1a1a18; }
.detail-page-header__actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.detail-page-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1a1a18;
  line-height: 1.2;
}
.product-sku {
  margin: 0;
  font-size: 0.6875rem;
  color: #999;
  font-family: ui-monospace, monospace;
}

.detail-header-skeleton {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.875rem;
  margin-bottom: 1.25rem;
}
@media (min-width: 768px) {
  .detail-grid { grid-template-columns: 1fr 1.5fr; }
}

.detail-card {
  background: #fff;
  border: 1px solid rgba(0,0,0,0.07);
  border-radius: 10px;
  padding: 1.125rem;
}
.detail-card--wide { }
.detail-card__title {
  margin: 0 0 0.875rem;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: #888;
}

.detail-dl { margin: 0; }
.detail-dl__row {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.3125rem 0;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  font-size: 0.875rem;
}
.detail-dl__row:last-child { border-bottom: none; }
.detail-dl__row dt { color: #888; flex-shrink: 0; }
.detail-dl__row dd { margin: 0; color: #1a1a18; text-align: right; word-break: break-word; }

.product-short-desc {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: #555;
  font-style: italic;
}
.product-desc {
  margin: 0;
  font-size: 0.875rem;
  color: #444;
  line-height: 1.6;
  white-space: pre-wrap;
}
.text-muted { color: #bbb; font-size: 0.875rem; }

.detail-section { margin-top: 0.25rem; }
.detail-section__title {
  margin: 0 0 0.75rem;
  font-size: 0.9375rem;
  font-weight: 650;
  color: #1a1a18;
}

.mono-id { font-family: ui-monospace, monospace; font-size: 0.8125rem; font-weight: 600; }
.variant-attrs { font-size: 0.875rem; color: #444; }
.compare-price { color: #999; text-decoration: line-through; font-size: 0.8125rem; }

.form-stack { display: flex; flex-direction: column; gap: 1rem; }
.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
</style>
