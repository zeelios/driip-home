<template>
  <div>
    <!-- Loading State -->
    <template v-if="isLoading">
      <div class="flex items-center justify-between gap-4 mb-6">
        <ZSkeleton height="1.5rem" width="200px" />
        <ZSkeleton height="2.5rem" width="120px" />
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
          <ZSkeleton height="300px" />
        </div>
        <div class="space-y-4">
          <ZSkeleton height="200px" />
          <ZSkeleton height="100px" />
        </div>
      </div>
    </template>

    <!-- Error State -->
    <ZEmptyState
      v-else-if="error"
      title="Không thể tải dữ liệu"
      :description="error"
      :icon="ERROR_ICON"
    >
      <template #action>
        <ZButton variant="outline" size="sm" @click="fetchSelectedItems">
          Thử lại
        </ZButton>
      </template>
    </ZEmptyState>

    <!-- Content -->
    <template v-else>
      <!-- Header -->
      <div
        class="flex flex-col sm:flex-row items-start justify-between gap-4 mb-6"
      >
        <div>
          <div class="flex items-center gap-2 mb-2">
            <NuxtLink
              :to="items.length > 0 ? '/purchase-requests' : '/purchase-orders'"
              class="inline-flex items-center gap-1 text-sm text-white/50 hover:text-white/80 transition-colors"
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
              {{ items.length > 0 ? "Yêu cầu mua hàng" : "Đơn đặt hàng" }}
            </NuxtLink>
            <span class="text-white/30">/</span>
            <span class="text-sm text-white/70">Tạo đơn đặt hàng</span>
          </div>
          <h1 class="text-xl font-bold text-white/95">Tạo đơn đặt hàng mới</h1>
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
          <ZButton
            variant="outline"
            size="sm"
            @click="
              navigateTo(
                items.length > 0 ? '/purchase-requests' : '/purchase-orders'
              )
            "
            class="flex-1 sm:flex-none"
          >
            Hủy
          </ZButton>
          <ZButton
            variant="primary"
            size="sm"
            :loading="isCreating"
            :disabled="!canSubmit"
            @click="handleCreatePO"
            class="flex-1 sm:flex-none"
          >
            <template #prefix>
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path d="M12 5v14M5 12h14" />
              </svg>
            </template>
            Tạo đơn đặt hàng
          </ZButton>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Left Column: Items Table -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Items Table -->
          <div
            class="bg-[#111111] border border-white/8 rounded-[10px] overflow-hidden"
          >
            <div class="p-4 border-b border-white/8">
              <h2 class="text-sm font-semibold text-white/90">
                Sản phẩm đặt hàng ({{ items.length }})
              </h2>
            </div>

            <ZTable
              :columns="itemColumns"
              :rows="items"
              :loading="false"
              row-key="id"
              empty-title="Chưa có sản phẩm"
              empty-description="Thêm sản phẩm để tạo đơn đặt hàng"
            >
              <template #cell-product="{ row }">
                <div class="flex flex-col gap-0.5">
                  <span class="font-medium text-white/85">{{
                    (row as POItem).product_name
                  }}</span>
                  <span class="text-xs text-white/50 font-mono">{{
                    (row as POItem).sku
                  }}</span>
                </div>
              </template>

              <template #cell-size="{ row }">
                <span class="text-sm text-white/70">{{
                  (row as POItem).size_display ?? "-"
                }}</span>
              </template>

              <template #cell-quity_needed="{ row }">
                <span class="text-sm text-white/70">{{
                  (row as POItem).quantity_needed
                }}</span>
              </template>

              <template #cell-quity_to_order="{ row }">
                <ZInput
                  v-model="(row as POItem).quantity_to_order"
                  type="number"
                  size="sm"
                  class="w-20"
                  :min="1"
                />
              </template>

              <template #cell-unit_cost="{ row }">
                <ZInput
                  v-model="(row as POItem).unit_cost"
                  type="number"
                  size="sm"
                  class="w-28"
                  :min="0"
                  :step="1000"
                />
              </template>

              <template #cell-total="{ row }">
                <span class="text-sm font-medium text-white/90">
                  {{
                    formatVnd(
                      ((row as POItem).quantity_to_order ?? 0) *
                        ((row as POItem).unit_cost ?? 0)
                    )
                  }}
                </span>
              </template>

              <template #cell-actions="{ row }">
                <ZButton
                  size="sm"
                  variant="danger"
                  @click="removeItemByRow(row as POItem)"
                >
                  <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <polyline points="3 6 5 6 21 6" />
                    <path
                      d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                    />
                  </svg>
                </ZButton>
              </template>
            </ZTable>

            <!-- Add Product Section -->
            <div class="p-4 border-t border-white/8 bg-white/2">
              <!-- Product Select -->
              <div class="mb-4">
                <ZSelect
                  v-model="selectedProduct"
                  :options="productOptions"
                  placeholder="Tìm sản phẩm theo tên hoặc SKU..."
                  :searchable="true"
                  :async="true"
                  :loading="isSearchingProduct"
                  size="md"
                  class="w-full"
                  @search="onProductSearch"
                  @update:model-value="onProductSelect"
                />
              </div>

              <!-- Selected Product Draft Card -->
              <div
                v-if="selectedProductData"
                class="rounded-lg border border-white/8 bg-white/4 p-4"
              >
                <div class="mb-4 flex items-start justify-between gap-3">
                  <div>
                    <p class="font-medium text-white">
                      {{ selectedProductData.name }}
                    </p>
                    <p class="text-sm text-white/50 font-mono">
                      {{ selectedProductData.sku_base || "-" }}
                    </p>
                  </div>
                  <ZButton
                    variant="ghost"
                    size="sm"
                    type="button"
                    @click="clearProductDraft"
                  >
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                  </ZButton>
                </div>

                <div class="grid grid-cols-1 gap-4">
                  <ZInput
                    v-model="newItem.quantity"
                    type="number"
                    label="Số lượng cần mua"
                    size="md"
                    :min="1"
                  />
                </div>

                <div class="mt-4 flex justify-end">
                  <ZButton
                    variant="primary"
                    size="sm"
                    :disabled="newItem.quantity < 1"
                    @click="addProduct"
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
                    Thêm vào đơn
                  </ZButton>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: PO Info -->
        <div class="space-y-4">
          <!-- PO Info Card -->
          <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
            <h2 class="text-sm font-semibold text-white/90 mb-4">
              Thông tin đơn hàng
            </h2>

            <div class="space-y-3">
              <!-- Supplier (Optional) -->
              <div>
                <label class="block text-xs text-white/50 mb-1.5"
                  >Nhà cung cấp (tùy chọn)</label
                >
                <ZSelect
                  v-model="poForm.supplier_id"
                  :options="supplierOptions"
                  placeholder="Chọn nhà cung cấp"
                  size="sm"
                  class="w-full"
                />
              </div>

              <!-- Warehouse (Required) -->
              <div>
                <label class="block text-xs text-white/50 mb-1.5">
                  Kho nhận hàng <span class="text-amber-500">*</span>
                </label>
                <ZSelect
                  v-model="poForm.warehouse_id"
                  :options="warehouseOptions"
                  placeholder="Chọn kho"
                  size="sm"
                  class="w-full"
                />
              </div>

              <!-- Expected Arrival -->
              <div>
                <label class="block text-xs text-white/50 mb-1.5"
                  >Ngày dự kiến nhận</label
                >
                <ZDatePicker v-model="poForm.expected_arrival_at" />
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-xs text-white/50 mb-1.5"
                  >Ghi chú</label
                >
                <ZInput
                  v-model="poForm.notes"
                  type="textarea"
                  placeholder="Ghi chú về đơn đặt hàng..."
                  size="sm"
                  class="w-full"
                />
              </div>
            </div>
          </div>

          <!-- Summary -->
          <div class="bg-[#111111] border border-white/8 rounded-[10px] p-4">
            <h2 class="text-sm font-semibold text-white/90 mb-4">Tổng kết</h2>

            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-white/60">Tổng số lượng</span>
                <span class="font-medium text-white/90">{{
                  totalQuantity
                }}</span>
              </div>

              <div class="flex items-center justify-between text-sm">
                <span class="text-white/60">Phí vận chuyển (ước tính)</span>
                <ZInput
                  v-model="poForm.shipping_cost"
                  type="number"
                  size="sm"
                  class="w-28 text-right"
                  :min="0"
                  :step="1000"
                />
              </div>

              <div class="flex items-center justify-between text-sm">
                <span class="text-white/60">Chi phí khác (ước tính)</span>
                <ZInput
                  v-model="poForm.other_costs"
                  type="number"
                  size="sm"
                  class="w-28 text-right"
                  :min="0"
                  :step="1000"
                />
              </div>

              <div
                class="border-t border-white/8 pt-3 flex items-center justify-between text-base font-semibold"
              >
                <span class="text-white/80">Tổng chi phí ước tính</span>
                <span class="text-amber-500">{{
                  formatVnd(poForm.shipping_cost + poForm.other_costs)
                }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from "vue";
import type { TableColumn } from "~/components/z/Table.vue";
import { usePendingPOStore, type PendingPOItem } from "~/stores/pending-po";
import { useProductsStore } from "~/stores/products";
import type { ProductSearchResult } from "~/stores/products";

definePageMeta({ layout: "panel" });

interface POItem {
  id: string;
  type: string;
  product_id: string;
  sku: string;
  product_name: string;
  size_option_id: string | null;
  size_display: string | null;
  color: string | null;
  quantity_needed: number;
  quantity_to_order: number;
  unit_cost?: number;
  warehouse_id?: string;
  warehouse_name?: string;
  order_id?: string;
  order_number?: string;
}

interface Supplier {
  id: string;
  name: string;
}

interface Warehouse {
  id: string;
  name: string;
}

// Product search result type matches ProductSearchResult from products store
type ProductResult = ProductSearchResult;

const ERROR_ICON = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

const api = useApi();
const toast = useToast();
const route = useRoute();
const productsStore = useProductsStore();

// Loading states
const isLoading = ref(false);
const isCreating = ref(false);
const error = ref<string | null>(null);
const isSearchingProduct = ref(false);

// Data
const items = ref<POItem[]>([]);
const suppliers = ref<Supplier[]>([]);
const warehouses = ref<Warehouse[]>([]);
const products = ref<ProductResult[]>([]);

// Form
const poForm = reactive({
  supplier_id: "",
  warehouse_id: "",
  expected_arrival_at: "",
  notes: "",
  shipping_cost: 0,
  other_costs: 0,
});

// Product selection
const selectedProduct = ref<string | null>(null);
const selectedProductData = ref<ProductResult | null>(null);
const searchTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const newItem = reactive({
  quantity: 1,
});

// Table columns
const itemColumns: TableColumn[] = [
  { key: "product", label: "Sản phẩm" },
  { key: "size", label: "Size", width: "70px" },
  { key: "quantity_needed", label: "SL cần", width: "70px", align: "center" },
  { key: "quantity_to_order", label: "SL đặt", width: "90px", align: "center" },
  { key: "actions", label: "", width: "50px", align: "center" },
];

// Options
const supplierOptions = computed(() =>
  suppliers.value.map((s) => ({ value: s.id, label: s.name }))
);

const warehouseOptions = computed(() =>
  warehouses.value.map((w) => ({ value: w.id, label: w.name }))
);

const productOptions = computed(() =>
  products.value.map((p) => ({
    value: p.id,
    label: p.name ? `${p.name} (${p.sku_base || "-"})` : p.sku_base || p.id,
  }))
);

// Computed
const totalQuantity = computed(() =>
  items.value.reduce((sum, item) => sum + item.quantity_to_order, 0)
);

const canSubmit = computed(() => {
  return (
    items.value.length > 0 &&
    poForm.warehouse_id &&
    items.value.every((i) => i.quantity_to_order > 0)
  );
});

const canAddProduct = computed(() => {
  return selectedProduct.value && newItem.quantity > 0;
});

// Fetch selected items from Pinia store
async function fetchSelectedItems(): Promise<void> {
  const pendingPOStore = usePendingPOStore();
  pendingPOStore.restoreFromStorage();

  if (pendingPOStore.hasItems) {
    items.value = pendingPOStore.items.map((item: PendingPOItem) => ({
      ...item,
      type: pendingPOStore.source || "manual",
    }));
    isLoading.value = false;
    error.value = null;
    return;
  }

  isLoading.value = false;
  error.value = null;
}

async function fetchSuppliers(): Promise<void> {
  try {
    const response = await api.get<{ data: Supplier[] }>("/suppliers");
    suppliers.value = response.data ?? [];
  } catch {
    // Silent fail
  }
}

async function fetchWarehouses(): Promise<void> {
  try {
    const response = await api.get<{ data: Warehouse[] }>("/warehouses");
    warehouses.value = response.data ?? [];
  } catch {
    // Silent fail
  }
}

async function searchProducts(query: string): Promise<void> {
  if (!query || query.length < 2) return;

  isSearchingProduct.value = true;
  try {
    const results = await productsStore.searchProductsUnified(query, 10);
    products.value = results;
  } catch {
    products.value = [];
  } finally {
    isSearchingProduct.value = false;
  }
}

function onProductSearch(query: string): void {
  if (searchTimer.value) clearTimeout(searchTimer.value);
  searchTimer.value = setTimeout(() => {
    if (query.length >= 2) {
      searchProducts(query);
    } else {
      products.value = [];
    }
  }, 300);
}

function onProductSelect(productId: string | number | null): void {
  if (!productId) {
    selectedProductData.value = null;
    return;
  }
  const product = products.value.find((p) => p.id === productId);
  if (product) {
    selectedProductData.value = product;
  }
}

function clearProductDraft(): void {
  selectedProduct.value = null;
  selectedProductData.value = null;
  products.value = [];
  newItem.quantity = 1;
}

function removeItem(index: number): void {
  items.value.splice(index, 1);
}

function removeItemByRow(row: POItem): void {
  const index = items.value.findIndex((item) => item.id === row.id);
  if (index !== -1) {
    removeItem(index);
  }
}

function addProduct(): void {
  if (!selectedProduct.value) return;

  const product = products.value.find((p) => p.id === selectedProduct.value);
  if (!product) return;

  items.value.push({
    id: `new-${Date.now()}`,
    type: "manual",
    product_id: product.id,
    sku: product.sku_base || "-",
    product_name: product.name,
    size_option_id: null,
    size_display: null,
    color: null,
    quantity_needed: newItem.quantity,
    quantity_to_order: newItem.quantity,
  });

  clearProductDraft();
  toast.success("Đã thêm sản phẩm");
}

async function handleCreatePO(): Promise<void> {
  if (!canSubmit.value) {
    toast.error("Vui lòng điền đầy đủ thông tin");
    return;
  }

  isCreating.value = true;

  try {
    const poItems = items.value.map((item) => ({
      product_variant_id: item.product_id,
      quantity_ordered: item.quantity_to_order,
      notes: null,
    }));

    const response = await api.post<{ data: { id: string } }>(
      "/purchase-orders",
      {
        supplier_id: poForm.supplier_id || null,
        warehouse_id: poForm.warehouse_id,
        expected_arrival_at: poForm.expected_arrival_at || null,
        notes: poForm.notes || null,
        shipping_cost: poForm.shipping_cost,
        other_costs: poForm.other_costs,
        items: poItems,
      }
    );

    toast.success("Đã tạo đơn đặt hàng");

    const pendingPOStore = usePendingPOStore();
    pendingPOStore.clearItems();
    pendingPOStore.clearStorage();

    navigateTo(`/purchase-orders/${response.data.id}`);
  } catch (err) {
    toast.error("Tạo đơn thất bại", getErrorMessage(err));
  } finally {
    isCreating.value = false;
  }
}

onMounted(() => {
  fetchSelectedItems();
  fetchSuppliers();
  fetchWarehouses();
});
</script>
