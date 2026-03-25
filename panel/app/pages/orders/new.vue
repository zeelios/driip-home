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
                  {{ formatVnd(item.price * item.quantity) }}
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
            <p class="text-sm text-white/50">Chưa có sản phẩm nào được chọn</p>
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
          <div
            class="mb-5 flex rounded-lg border border-white/10 bg-white/4 p-1"
          >
            <button
              :class="[
                'flex-1 rounded-md px-4 py-2.5 text-sm font-medium transition-all',
                customerType === 'existing'
                  ? 'bg-white text-[#0a0a0a] shadow-sm'
                  : 'text-white/60 hover:text-white',
              ]"
              @click="customerType = 'existing'"
            >
              Khách hàng có sẵn
            </button>
            <button
              :class="[
                'flex-1 rounded-md px-4 py-2.5 text-sm font-medium transition-all',
                customerType === 'guest'
                  ? 'bg-white text-[#0a0a0a] shadow-sm'
                  : 'text-white/60 hover:text-white',
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
              class="flex items-center justify-between rounded-lg border border-white/6 bg-white/4 p-4"
            >
              <div>
                <p class="font-medium text-white">
                  {{ selectedCustomer.first_name }}
                  {{ selectedCustomer.last_name }}
                </p>
                <p class="text-sm text-white/50">
                  {{ selectedCustomer.email || selectedCustomer.phone }}
                </p>
              </div>
              <button
                class="rounded-lg p-2 text-white/40 hover:bg-white/10 hover:text-white/70 transition-colors"
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
        <div class="rounded-xl border border-white/8 bg-[#111111] p-6">
          <p class="mb-5 text-sm font-semibold text-white">Giao hàng</p>
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
