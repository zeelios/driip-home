<template>
  <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <!-- Search input -->
    <div class="mb-6">
      <div class="relative">
        <input
          ref="searchInput"
          v-model="globalSearch"
          type="text"
          placeholder="Tìm kiếm đơn hàng, sản phẩm, khách hàng..."
          class="w-full rounded-lg border border-white/12 bg-white/4 px-4 py-3 pr-10 text-sm text-white placeholder-white/35 focus:border-white/40 focus:outline-none focus:ring-1 focus:ring-white/20"
          @focus="onSearchFocus"
        />
        <div
          class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
        >
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="text-neutral-400"
          >
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Page header -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <NuxtLink
          to="/orders"
          class="flex items-center gap-1.5 text-sm text-white/50 hover:text-white/80 transition-colors"
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
          Đơn hàng
        </NuxtLink>
        <h1 class="text-2xl font-semibold text-white tracking-tight">
          Tạo đơn hàng mới
        </h1>
      </div>
    </div>

    <!-- Main content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_400px]">
      <!-- Left column -->
      <div class="flex flex-col gap-5">
        <!-- Products section -->
        <div class="rounded-xl border border-white/8 bg-[#111111] p-6">
          <p class="mb-5 text-sm font-semibold text-white">Sản phẩm</p>
          <div class="mb-5">
            <ZSelect
              v-model="selectedProductId"
              :options="productSelectOptions"
              label="Tìm sản phẩm"
              placeholder="Nhập tên hoặc SKU sản phẩm..."
              :searchable="true"
              :async="true"
              :loading="productSearchLoading"
              @search="onProductSearch"
              @update:model-value="onProductSelect"
            />
          </div>
          <div v-if="selectedItems.length > 0" class="flex flex-col gap-3">
            <div
              v-for="(item, index) in selectedItems"
              :key="item.product_variant_id"
              class="flex items-center justify-between rounded-lg border border-white/6 bg-white/4 p-4 transition-all hover:border-white/10"
            >
              <div class="flex items-center gap-3">
                <img
                  :src="item.image || '/placeholder.png'"
                  :alt="item.name"
                  class="h-12 w-12 rounded-lg object-cover border border-white/10"
                />
                <div>
                  <p class="font-medium text-white">{{ item.name }}</p>
                  <p class="text-sm text-white/50">{{ item.variant }}</p>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <ZInput
                  :model-value="item.quantity"
                  type="number"
                  min="1"
                  class="w-20"
                  @update:modelValue="updateQuantity(index, Number($event))"
                />
                <span class="min-w-24 text-right font-semibold text-white">
                  {{ formatVnd(item.unit_price * item.quantity) }}
                </span>
                <button
                  class="rounded-lg p-2 text-white/40 hover:bg-white/10 hover:text-white/70 transition-colors"
                  @click="removeItem(index)"
                >
                  <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <div v-else class="py-12 text-center">
            <div
              class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white/6"
            >
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="text-white/35"
              >
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 3v18M15 3v18M3 9h18M3 15h18" />
              </svg>
            </div>
            <p class="text-sm text-neutral-400">
              Chưa có sản phẩm nào được chọn
            </p>
          </div>
        </div>

        <!-- Payment section -->
        <div class="rounded-xl border border-white/8 bg-[#111111] p-6">
          <p class="mb-5 text-sm font-semibold text-white">Thanh toán</p>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <ZSelect
              v-model="form.payment_method"
              :options="paymentMethodOptions"
              label="Phương thức thanh toán"
              placeholder="Chọn phương thức"
            />
            <ZSelect
              v-model="form.payment_status"
              :options="paymentStatusOptions"
              label="Trạng thái thanh toán"
              placeholder="Chọn trạng thái"
            />
          </div>
          <!-- Deposit amount for partial payment -->
          <div v-if="form.payment_status === 'partial'" class="mt-4">
            <ZInput
              v-model="form.deposit_amount"
              type="number"
              label="Số tiền đặt cọc *"
              placeholder="Nhập số tiền đặt cọc"
              :error="formErrors.deposit_amount"
            >
              <template #suffix>VND</template>
            </ZInput>
            <p class="mt-1 text-xs text-white/50">
              Còn lại:
              {{
                formatVnd(Math.max(0, total - Number(form.deposit_amount || 0)))
              }}
            </p>
          </div>
        </div>

        <!-- Notes section -->
        <div class="rounded-xl border border-white/8 bg-[#111111] p-6">
          <p class="mb-5 text-sm font-semibold text-white">Ghi chú</p>
          <div class="flex flex-col gap-4">
            <ZTextarea
              v-model="form.notes"
              label="Ghi chú khách hàng"
              placeholder="Ghi chú từ khách hàng..."
              :rows="3"
            />
            <ZTextarea
              v-model="form.internal_notes"
              label="Ghi chú nội bộ"
              placeholder="Ghi chú nội bộ cho nhân viên..."
              :rows="3"
            />
          </div>
        </div>
      </div>

      <!-- Right column -->
      <div class="flex flex-col gap-5">
        <!-- Customer section -->
        <div class="rounded-xl border border-white/8 bg-[#111111] p-6">
          <p class="mb-5 text-sm font-semibold text-white">Khách hàng</p>

          <!-- Unified customer search with create option -->
          <div class="relative flex flex-col gap-4">
            <ZSelect
              v-model="selectedCustomerId"
              :options="customerSelectOptions"
              label="Tìm hoặc tạo khách hàng"
              placeholder="Nhập tên, email, hoặc SĐT để tìm..."
              :searchable="true"
              :async="true"
              :loading="customerSearchLoading"
              :show-create-option="true"
              create-option-label="+ Tạo khách hàng mới"
              @search="onCustomerSearch"
              @update:model-value="onCustomerSelect"
              @create="showCreateModal = true"
            />

            <!-- Selected customer info -->
            <div
              v-if="selectedCustomer"
              class="p-3 rounded-lg bg-white/5 border border-white/8"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-full bg-[#8B4513]/20 flex items-center justify-center text-[#C4A77D] font-medium"
                >
                  {{ selectedCustomer.first_name.charAt(0)
                  }}{{ selectedCustomer.last_name.charAt(0) }}
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-white truncate">
                    {{ selectedCustomer.first_name }}
                    {{ selectedCustomer.last_name }}
                  </p>
                  <p class="text-xs text-white/50">
                    <span v-if="selectedCustomer.phone">{{
                      selectedCustomer.phone
                    }}</span>
                    <span
                      v-if="selectedCustomer.phone && selectedCustomer.email"
                    >
                      ·
                    </span>
                    <span v-if="selectedCustomer.email">{{
                      selectedCustomer.email
                    }}</span>
                  </p>
                </div>
                <button
                  class="p-1.5 rounded-lg text-white/40 hover:text-white/70 hover:bg-white/10 transition-colors"
                  @click="clearCustomer"
                >
                  <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Quick create button when no search -->
            <button
              v-if="!selectedCustomer && !customerSearchLoading"
              type="button"
              class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg border border-white/10 bg-white/4 text-sm text-white/70 hover:text-white hover:bg-white/8 transition-colors"
              @click="showCreateModal = true"
            >
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <line x1="12" y1="5" x2="12" y2="19" />
                <line x1="5" y1="12" x2="19" y2="12" />
              </svg>
              Tạo khách hàng mới
            </button>
          </div>

          <!-- Create Customer Modal -->
          <CreateCustomerModal
            v-model="showCreateModal"
            @submit="handleCustomerCreate"
            @use-existing="handleUseExisting"
            @cancel="showCreateModal = false"
          />
        </div>

        <!-- Shipping section -->
        <div class="rounded-xl border border-white/8 bg-[#111111] p-6">
          <p class="mb-5 text-sm font-semibold text-white">Giao hàng</p>
          <div class="flex flex-col gap-4">
            <ZInput
              v-model="form.shipping_name"
              label="Tên người nhận *"
              placeholder="Nguyễn Văn A"
              :error="formErrors.shipping_name"
            />
            <ZInput
              v-model="form.shipping_phone"
              label="SĐT người nhận *"
              placeholder="0901234567"
              :error="formErrors.shipping_phone"
            />
            <ZInput
              v-model="form.shipping_address"
              label="Địa chỉ *"
              placeholder="123 Đường ABC"
              :error="formErrors.shipping_address"
            />
            <div class="grid grid-cols-2 gap-4">
              <ZInput
                v-model="form.shipping_ward"
                label="Phường/Xã"
                placeholder="Phường 1"
              />
              <ZInput
                v-model="form.shipping_district"
                label="Quận/Huyện"
                placeholder="Quận 1"
              />
            </div>
            <ZInput
              v-model="form.shipping_province"
              label="Tỉnh/Thành phố *"
              placeholder="TP. Hồ Chí Minh"
              :error="formErrors.shipping_province"
            />
            <ZInput
              v-model="form.shipping_zip"
              label="Mã bưu điện"
              placeholder="700000"
            />
          </div>
        </div>

        <!-- Discount section -->
        <div class="rounded-xl border border-white/8 bg-[#111111] p-6">
          <p class="mb-5 text-sm font-semibold text-white">Giảm giá</p>
          <div class="flex gap-3">
            <ZInput
              v-model="form.coupon_code"
              placeholder="Nhập mã giảm giá"
              class="flex-1"
            />
            <ZButton variant="outline" size="sm" @click="applyCoupon">
              Áp dụng
            </ZButton>
          </div>
        </div>

        <!-- Summary section -->
        <div class="rounded-xl border border-white/10 bg-[#141414] p-6">
          <p class="mb-5 text-sm font-semibold text-white">Tổng cộng</p>
          <div class="flex flex-col gap-3">
            <div class="flex justify-between text-sm">
              <span class="text-white/50">Tạm tính</span>
              <span class="text-white">{{ formatVnd(subtotal) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-white/50">Giảm giá</span>
              <span class="text-white/70">-{{ formatVnd(discount) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-white/50">Phí vận chuyển</span>
              <span class="text-white">{{ formatVnd(shippingFee) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-white/50">Thuế (10%)</span>
              <span class="text-white">{{ formatVnd(tax) }}</span>
            </div>
            <div
              class="mt-3 flex justify-between border-t border-white/10 pt-4"
            >
              <span class="text-base font-semibold text-white">Tổng cộng</span>
              <span class="text-lg font-bold text-white">{{
                formatVnd(total)
              }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <ZButton
            variant="outline"
            size="md"
            class="flex-1"
            @click="navigateTo('/orders')"
          >
            Hủy
          </ZButton>
          <ZButton
            variant="primary"
            size="md"
            class="flex-1"
            :loading="formPending"
            @click="handleCreate"
          >
            Tạo đơn hàng
          </ZButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatVnd } from "~/utils/format";
import type { SelectOption } from "~/components/z/Select.vue";
import type { CustomerModel } from "~~/types/generated/backend-models.generated";
import { useCustomersStore } from "~/stores/customers";
import { useProductsStore } from "~/stores/products";

definePageMeta({ layout: "panel" });

interface SelectedItem {
  product_variant_id: string;
  name: string;
  variant: string;
  image: string | null;
  unit_price: number;
  quantity: number;
}

interface ProductVariantSearchResult {
  id: string;
  product_id: string;
  sku: string;
  name: string;
  variant_name: string | null;
  unit_price: number;
  stock_quantity: number;
  image: string | null;
}

const api = useApi();
const customersStore = useCustomersStore();
const productsStore = useProductsStore();

const productSearch = ref("");
const globalSearch = ref("");
const showCreateModal = ref(false);
const selectedCustomer = ref<CustomerModel | null>(null);
const selectedCustomerId = ref<string | number | null>(null);
const customerSearchLoading = ref(false);
const customerResults = ref<CustomerModel[]>([]);
const selectedItems = ref<SelectedItem[]>([]);
const formPending = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);

// Product search state
const selectedProductId = ref<string | number | null>(null);
const productResults = ref<ProductVariantSearchResult[]>([]);
const productSearchLoading = ref(false);
let productSearchTimer: ReturnType<typeof setTimeout> | null = null;

// Debounced customer search
let customerSearchTimer: ReturnType<typeof setTimeout> | null = null;

const customerSelectOptions = computed(() => {
  return customerResults.value.map((c) => ({
    value: c.id,
    label: `${c.first_name} ${c.last_name} - ${
      c.phone || c.email || "Không có liên hệ"
    }`,
  }));
});

const productSelectOptions = computed(() => {
  return productResults.value.map((v) => ({
    value: v.id,
    label: `${v.name}${v.variant_name ? ` - ${v.variant_name}` : ""} (${
      v.sku
    }) - ${formatVnd(v.unit_price)}`,
  }));
});

function onProductSelect(variantId: string | number): void {
  const variant = productResults.value.find((v) => v.id === String(variantId));
  if (!variant) return;

  // Check if already in cart
  const existingIndex = selectedItems.value.findIndex(
    (item) => item.product_variant_id === variant.id
  );

  if (existingIndex >= 0) {
    // Increment quantity if already exists
    const existingItem = selectedItems.value[existingIndex];
    if (existingItem) {
      existingItem.quantity += 1;
    }
  } else {
    // Add new item
    selectedItems.value.push({
      product_variant_id: variant.id,
      name: variant.name,
      variant: variant.variant_name || "Mặc định",
      image: variant.image,
      unit_price: variant.unit_price,
      quantity: 1,
    });
  }

  // Clear search
  productSearch.value = "";
  productResults.value = [];
}

function onSearchFocus(): void {
  searchInput.value?.select();
}

function onProductSearch(query: string): void {
  if (productSearchTimer) clearTimeout(productSearchTimer);

  if (!query.trim()) {
    productResults.value = [];
    return;
  }

  productSearchLoading.value = true;
  productSearchTimer = setTimeout(async () => {
    productResults.value = await productsStore.searchProductsUnified(query, 10);
    productSearchLoading.value = false;
  }, 200);
}

const form = ref({
  customer_id: "",
  guest_name: "",
  guest_phone: "",
  guest_email: "",
  shipping_name: "",
  shipping_phone: "",
  shipping_address: "",
  shipping_ward: "",
  shipping_district: "",
  shipping_province: "",
  shipping_zip: "",
  payment_method: "",
  payment_status: "pending",
  deposit_amount: "",
  coupon_code: "",
  notes: "",
  internal_notes: "",
  source: "admin",
});

const formErrors = ref({
  customer: "",
  shipping_address: "",
  shipping_name: "",
  shipping_phone: "",
  shipping_province: "",
  deposit_amount: "",
});

const paymentMethodOptions: SelectOption[] = [
  { value: "cod", label: "Thanh toán khi nhận hàng (COD)" },
  { value: "bank_transfer", label: "Chuyển khoản ngân hàng" },
  { value: "momo", label: "Ví MoMo" },
  { value: "vnpay", label: "VNPay" },
];

const paymentStatusOptions: SelectOption[] = [
  { value: "pending", label: "Chờ thanh toán" },
  { value: "partial", label: "Đã đặt cọc" },
  { value: "paid", label: "Đã thanh toán" },
];

const subtotal = computed(() =>
  selectedItems.value.reduce(
    (sum, item) => sum + item.unit_price * item.quantity,
    0
  )
);
const discount = ref(0);
const shippingFee = ref(30000);
const tax = computed(() => (subtotal.value - discount.value) * 0.1);
const total = computed(
  () => subtotal.value - discount.value + shippingFee.value + tax.value
);

function onCustomerSearch(query: string): void {
  if (customerSearchTimer) clearTimeout(customerSearchTimer);

  if (!query.trim()) {
    customerResults.value = [];
    return;
  }

  customerSearchLoading.value = true;
  customerSearchTimer = setTimeout(async () => {
    customerResults.value = await customersStore.searchCustomersUnified(
      query,
      10
    );
    customerSearchLoading.value = false;
  }, 200);
}

function onCustomerSelect(value: string | number): void {
  const customerId = String(value);
  const customer = customerResults.value.find((c) => c.id === customerId);

  if (customer) {
    selectedCustomer.value = customer;
    form.value.customer_id = customerId;
    // Auto-fill shipping info from customer
    form.value.shipping_name = `${customer.first_name} ${customer.last_name}`;
    form.value.shipping_phone = customer.phone || "";
    form.value.guest_email = customer.email || "";
  }
}

function clearCustomer(): void {
  selectedCustomer.value = null;
  selectedCustomerId.value = null;
  form.value.customer_id = "";
  form.value.shipping_name = "";
  form.value.shipping_phone = "";
  form.value.guest_email = "";
}

async function handleCustomerCreate(data: {
  customer: Partial<CustomerModel>;
  resolution: "none" | "overwrite" | "unlink";
}): Promise<void> {
  const result = await customersStore.createCustomerWithResolution(
    data.customer,
    data.resolution
  );

  if (result.success) {
    showCreateModal.value = false;

    // If a customer was created/updated, select it
    if (result.action === "created" || result.action === "unlinked") {
      // Refresh search and select the new customer
      // For now, we'll manually set the customer data
      const newCustomer = {
        id: "new-customer-id", // This would come from API
        ...data.customer,
      } as CustomerModel;

      selectedCustomer.value = newCustomer;
      selectedCustomerId.value = newCustomer.id;
      form.value.customer_id = newCustomer.id;
      form.value.shipping_name = `${newCustomer.first_name} ${newCustomer.last_name}`;
      form.value.shipping_phone = newCustomer.phone || "";
      form.value.guest_email = newCustomer.email || "";
    }
  }
}

function handleUseExisting(customer: CustomerModel): void {
  selectedCustomer.value = customer;
  selectedCustomerId.value = customer.id;
  form.value.customer_id = customer.id;
  form.value.shipping_name = `${customer.first_name} ${customer.last_name}`;
  form.value.shipping_phone = customer.phone || "";
  form.value.guest_email = customer.email || "";
  showCreateModal.value = false;
}

function removeItem(index: number): void {
  selectedItems.value.splice(index, 1);
}

function updateQuantity(index: number, quantity: number): void {
  const item = selectedItems.value[index];
  if (!item || quantity <= 0) return;

  item.quantity = quantity;
}

function applyCoupon(): void {
  // TODO: Implement coupon validation
}

function validateForm(): boolean {
  let valid = true;
  formErrors.value = {
    customer: "",
    shipping_address: "",
    shipping_name: "",
    shipping_phone: "",
    shipping_province: "",
    deposit_amount: "",
  };

  // Validate customer selection
  if (!selectedCustomer.value && !form.value.customer_id) {
    formErrors.value.customer = "Vui lòng chọn hoặc tạo khách hàng";
    valid = false;
  }

  if (!form.value.shipping_address.trim()) {
    formErrors.value.shipping_address = "Vui lòng nhập địa chỉ giao hàng";
    valid = false;
  }
  if (!form.value.shipping_name.trim()) {
    formErrors.value.shipping_name = "Vui lòng nhập tên người nhận";
    valid = false;
  }
  if (!form.value.shipping_phone.trim()) {
    formErrors.value.shipping_phone = "Vui lòng nhập SĐT người nhận";
    valid = false;
  }
  if (!form.value.shipping_province.trim()) {
    formErrors.value.shipping_province = "Vui lòng nhập tỉnh/thành phố";
    valid = false;
  }

  if (form.value.payment_status === "partial") {
    const deposit = Number(form.value.deposit_amount);
    if (!deposit || deposit <= 0) {
      formErrors.value.deposit_amount = "Vui lòng nhập số tiền đặt cọc";
      valid = false;
    } else if (deposit >= total.value) {
      formErrors.value.deposit_amount =
        "Số tiền đặt cọc phải nhỏ hơn tổng đơn hàng";
      valid = false;
    }
  }

  if (selectedItems.value.length === 0) {
    // TODO: Show error for no items selected
    valid = false;
  }

  return valid;
}

async function handleCreate(): Promise<void> {
  if (!validateForm()) return;
  formPending.value = true;
  try {
    // Build items array for API
    const items = selectedItems.value.map((item) => ({
      product_variant_id: item.product_variant_id,
      quantity: item.quantity,
      unit_price: item.unit_price,
    }));

    // Build order payload matching CreateOrderRequest
    const payload: Record<string, unknown> = {
      items,
      shipping_name: form.value.shipping_name,
      shipping_phone: form.value.shipping_phone,
      shipping_address: form.value.shipping_address,
      shipping_province: form.value.shipping_province,
      shipping_district: form.value.shipping_district || null,
      shipping_ward: form.value.shipping_ward || null,
      shipping_zip: form.value.shipping_zip || null,
      payment_method: form.value.payment_method || null,
      notes: form.value.notes || null,
      internal_notes: form.value.internal_notes || null,
      coupon_code: form.value.coupon_code || null,
      source: form.value.source || "admin",
    };

    // Add customer identification
    if (form.value.customer_id) {
      payload.customer_id = form.value.customer_id;
    } else {
      payload.guest_name = form.value.guest_name || form.value.shipping_name;
      payload.guest_phone = form.value.guest_phone || form.value.shipping_phone;
      payload.guest_email = form.value.guest_email || null;
    }

    await api.post("/orders", payload);
    navigateTo("/orders");
  } catch (error) {
    // Error is handled by useApi composable
  } finally {
    formPending.value = false;
  }
}
</script>
