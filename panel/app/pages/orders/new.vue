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
          class="w-full rounded-lg border border-neutral-200 bg-white px-4 py-3 pr-10 text-sm text-neutral-900 placeholder-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
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
          class="flex items-center gap-1.5 text-sm text-neutral-400 hover:text-neutral-600 transition-colors"
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
        <h1 class="text-2xl font-semibold text-neutral-900 tracking-tight">
          Tạo đơn hàng mới
        </h1>
      </div>
    </div>

    <!-- Main content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_400px]">
      <!-- Left column -->
      <div class="flex flex-col gap-5">
        <!-- Products section -->
        <div
          class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm"
        >
          <p class="mb-5 text-sm font-semibold text-neutral-900">Sản phẩm</p>
          <div class="mb-5">
            <ZInput
              v-model="productSearch"
              placeholder="Tìm sản phẩm theo tên hoặc SKU..."
              @input="onProductSearch"
            />
          </div>
          <div v-if="selectedItems.length > 0" class="flex flex-col gap-3">
            <div
              v-for="(item, index) in selectedItems"
              :key="index"
              class="flex items-center justify-between rounded-lg border border-neutral-100 bg-neutral-50 p-4 transition-all hover:border-neutral-200"
            >
              <div class="flex items-center gap-3">
                <img
                  :src="item.image || '/placeholder.png'"
                  :alt="item.name"
                  class="h-12 w-12 rounded-lg object-cover border border-neutral-200"
                />
                <div>
                  <p class="font-medium text-neutral-900">{{ item.name }}</p>
                  <p class="text-sm text-neutral-500">{{ item.variant }}</p>
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
                <span
                  class="min-w-24 text-right font-semibold text-neutral-900"
                >
                  {{ formatVnd(item.price * item.quantity) }}
                </span>
                <button
                  class="rounded-lg p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 transition-colors"
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
              class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100"
            >
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="text-neutral-400"
              >
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 3v18M15 3v18M3 9h18M3 15h18" />
              </svg>
            </div>
            <p class="text-sm text-neutral-500">
              Chưa có sản phẩm nào được chọn
            </p>
          </div>
        </div>

        <!-- Payment section -->
        <div
          class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm"
        >
          <p class="mb-5 text-sm font-semibold text-neutral-900">Thanh toán</p>
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
        </div>

        <!-- Notes section -->
        <div
          class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm"
        >
          <p class="mb-5 text-sm font-semibold text-neutral-900">Ghi chú</p>
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
        <div
          class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm"
        >
          <p class="mb-5 text-sm font-semibold text-neutral-900">Khách hàng</p>
          <div
            class="mb-5 flex rounded-lg border border-neutral-200 bg-neutral-50 p-1"
          >
            <button
              :class="[
                'flex-1 rounded-md px-4 py-2.5 text-sm font-medium transition-all',
                customerType === 'existing'
                  ? 'bg-neutral-900 text-white shadow-sm'
                  : 'text-neutral-600 hover:text-neutral-900',
              ]"
              @click="customerType = 'existing'"
            >
              Khách hàng có sẵn
            </button>
            <button
              :class="[
                'flex-1 rounded-md px-4 py-2.5 text-sm font-medium transition-all',
                customerType === 'guest'
                  ? 'bg-neutral-900 text-white shadow-sm'
                  : 'text-neutral-600 hover:text-neutral-900',
              ]"
              @click="customerType = 'guest'"
            >
              Khách vãng lai
            </button>
          </div>
          <div v-if="customerType === 'existing'" class="flex flex-col gap-4">
            <ZInput
              v-model="customerSearch"
              placeholder="Tìm khách hàng theo tên, email, SĐT..."
              @input="onCustomerSearch"
            />
            <div
              v-if="selectedCustomer"
              class="flex items-center justify-between rounded-lg border border-neutral-100 bg-neutral-50 p-4"
            >
              <div>
                <p class="font-medium text-neutral-900">
                  {{ selectedCustomer.first_name }}
                  {{ selectedCustomer.last_name }}
                </p>
                <p class="text-sm text-neutral-500">
                  {{ selectedCustomer.email || selectedCustomer.phone }}
                </p>
              </div>
              <button
                class="rounded-lg p-2 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700 transition-colors"
                @click="selectedCustomer = null"
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
          <div v-else class="flex flex-col gap-4">
            <ZInput
              v-model="form.guest_name"
              label="Tên khách hàng *"
              placeholder="Nguyễn Văn A"
              :error="formErrors.guest_name"
            />
            <ZInput
              v-model="form.guest_phone"
              label="Số điện thoại *"
              placeholder="0901234567"
              :error="formErrors.guest_phone"
            />
            <ZInput
              v-model="form.guest_email"
              label="Email"
              placeholder="khach@email.com"
            />
          </div>
        </div>

        <!-- Shipping section -->
        <div
          class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm"
        >
          <p class="mb-5 text-sm font-semibold text-neutral-900">Giao hàng</p>
          <div class="flex flex-col gap-4">
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
              v-model="form.shipping_city"
              label="Tỉnh/Thành phố"
              placeholder="TP. Hồ Chí Minh"
            />
          </div>
        </div>

        <!-- Discount section -->
        <div
          class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm"
        >
          <p class="mb-5 text-sm font-semibold text-neutral-900">Giảm giá</p>
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
        <div
          class="rounded-xl border border-neutral-900 bg-neutral-900 p-6 shadow-lg"
        >
          <p class="mb-5 text-sm font-semibold text-white">Tổng cộng</p>
          <div class="flex flex-col gap-3">
            <div class="flex justify-between text-sm">
              <span class="text-neutral-400">Tạm tính</span>
              <span class="text-white">{{ formatVnd(subtotal) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-neutral-400">Giảm giá</span>
              <span class="text-emerald-400">-{{ formatVnd(discount) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-neutral-400">Phí vận chuyển</span>
              <span class="text-white">{{ formatVnd(shippingFee) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-neutral-400">Thuế (10%)</span>
              <span class="text-white">{{ formatVnd(tax) }}</span>
            </div>
            <div
              class="mt-3 flex justify-between border-t border-neutral-700 pt-4"
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

definePageMeta({ layout: "panel" });

interface SelectedItem {
  id: number;
  name: string;
  variant: string;
  image: string | null;
  price: number;
  quantity: number;
}

interface Customer {
  id: number;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
}

const productSearch = ref("");
const globalSearch = ref("");
const customerSearch = ref("");
const customerType = ref<"existing" | "guest">("existing");
const selectedItems = ref<SelectedItem[]>([]);
const selectedCustomer = ref<Customer | null>(null);
const formPending = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);

const form = ref({
  guest_name: "",
  guest_phone: "",
  guest_email: "",
  shipping_address: "",
  shipping_ward: "",
  shipping_district: "",
  shipping_city: "",
  payment_method: "",
  payment_status: "pending",
  coupon_code: "",
  notes: "",
  internal_notes: "",
});

const formErrors = ref({
  guest_name: "",
  guest_phone: "",
  shipping_address: "",
});

const paymentMethodOptions: SelectOption[] = [
  { value: "cod", label: "Thanh toán khi nhận hàng (COD)" },
  { value: "bank_transfer", label: "Chuyển khoản ngân hàng" },
  { value: "momo", label: "Ví MoMo" },
  { value: "vnpay", label: "VNPay" },
];

const paymentStatusOptions: SelectOption[] = [
  { value: "pending", label: "Chờ thanh toán" },
  { value: "paid", label: "Đã thanh toán" },
];

const subtotal = computed(() =>
  selectedItems.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
);
const discount = ref(0);
const shippingFee = ref(30000);
const tax = computed(() => (subtotal.value - discount.value) * 0.1);
const total = computed(
  () => subtotal.value - discount.value + shippingFee.value + tax.value
);

function onProductSearch(): void {
  // TODO: Implement product search
}

function onSearchFocus(): void {
  searchInput.value?.select();
}

function onCustomerSearch(): void {
  // TODO: Implement customer search
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
  formErrors.value = { guest_name: "", guest_phone: "", shipping_address: "" };

  if (customerType.value === "guest") {
    if (!form.value.guest_name.trim()) {
      formErrors.value.guest_name = "Vui lòng nhập tên khách hàng";
      valid = false;
    }
    if (!form.value.guest_phone.trim()) {
      formErrors.value.guest_phone = "Vui lòng nhập số điện thoại";
      valid = false;
    }
  } else if (!selectedCustomer.value) {
    // TODO: Show error for missing customer selection
    valid = false;
  }

  if (!form.value.shipping_address.trim()) {
    formErrors.value.shipping_address = "Vui lòng nhập địa chỉ giao hàng";
    valid = false;
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
    // TODO: Implement order creation API call
    await navigateTo("/orders");
  } finally {
    formPending.value = false;
  }
}
</script>

<style scoped>
.order-create-grid {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 24px;
}

.order-create-left,
.order-create-right {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.product-search {
  margin-bottom: 16px;
}

.selected-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.selected-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  background: var(--color-neutral-50);
  border-radius: 8px;
}

.selected-item__info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.selected-item__image {
  width: 48px;
  height: 48px;
  object-fit: cover;
  border-radius: 6px;
}

.selected-item__name {
  font-weight: 500;
  font-size: 14px;
}

.selected-item__variant {
  font-size: 12px;
  color: var(--color-neutral-500);
}

.selected-item__controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.selected-item__qty {
  width: 64px;
}

.selected-item__price {
  font-weight: 600;
  min-width: 100px;
  text-align: right;
}

.selected-item__remove,
.selected-customer__remove {
  padding: 4px;
  color: var(--color-neutral-400);
  cursor: pointer;
  transition: color 0.15s;
}

.selected-item__remove:hover,
.selected-customer__remove:hover {
  color: var(--color-red-500);
}

.empty-products {
  padding: 40px;
  text-align: center;
  color: var(--color-neutral-400);
  background: var(--color-neutral-50);
  border-radius: 8px;
}

.customer-type-toggle {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.toggle-btn {
  flex: 1;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 500;
  border: 1px solid var(--color-neutral-200);
  background: var(--color-neutral-50);
  color: var(--color-neutral-600);
  cursor: pointer;
  transition: all 0.15s;
  border-radius: 6px;
}

.toggle-btn:first-child {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.toggle-btn:last-child {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
  margin-left: -1px;
}

.toggle-btn.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}

.toggle-btn:hover:not(.active) {
  background: var(--color-neutral-100);
}

.form-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.selected-customer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  background: var(--color-neutral-50);
  border: 1px solid var(--color-neutral-200);
  border-radius: 8px;
}

.selected-customer__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.selected-customer__name {
  font-weight: 500;
  font-size: 14px;
  margin: 0;
}

.selected-customer__contact {
  font-size: 12px;
  color: var(--color-neutral-500);
  margin: 0;
}

.order-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px;
  background: var(--color-neutral-50);
  border-radius: 8px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
}

.summary-row--total {
  font-weight: 600;
  font-size: 16px;
  padding-top: 8px;
  border-top: 1px solid var(--color-neutral-200);
}

.coupon-input {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

@media (max-width: 1024px) {
  .order-create-grid {
    grid-template-columns: 1fr;
  }
}
</style>
