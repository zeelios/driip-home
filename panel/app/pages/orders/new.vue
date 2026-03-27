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
          <div v-if="selectedProductHasOptions" class="mb-5">
            <ZSelect
              v-model="selectedProductOptionId"
              :options="selectedProductVariantOptions"
              label="Chọn tuỳ chọn / biến thể"
              placeholder="Chọn đúng biến thể của sản phẩm"
              @update:model-value="onProductVariantSelect"
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

          <div class="relative flex flex-col gap-4">
            <ZSelect
              v-model="selectedCustomerId"
              :options="customerSelectOptions"
              label="Tìm khách hàng"
              placeholder="Nhập tên, email, hoặc SĐT..."
              :searchable="true"
              :async="true"
              :loading="customerSearchLoading"
              @search="onCustomerSearch"
              @update:model-value="onCustomerSelect"
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
          </div>
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
            <ZSelect
              v-model="form.shipping_province"
              :options="provinceOptions"
              label="Tỉnh/Thành phố *"
              placeholder="Chọn tỉnh/thành phố"
              :error="formErrors.shipping_province"
            />
            <ZInput
              v-model="form.shipping_zip"
              label="Mã bưu điện"
              placeholder="700000"
              disabled
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
import { ref } from "vue";
import { storeToRefs } from "pinia";
import { formatVnd } from "~/utils/format";
import { useOrderCreateStore } from "~/stores/order-create";

definePageMeta({ layout: "panel" });

const orderCreate = useOrderCreateStore();

const {
  selectedCustomer,
  selectedCustomerId,
  customerSearchLoading,
  selectedItems,
  formPending,
  selectedProductId,
  selectedProductOptionId,
  productSearchLoading,
  form,
  formErrors,
  customerSelectOptions,
  productSelectOptions,
  selectedProductVariantOptions,
  selectedProductHasOptions,
  provinceOptions,
  paymentMethodOptions,
  paymentStatusOptions,
  discount,
  shippingFee,
  subtotal,
  tax,
  total,
} = storeToRefs(orderCreate);

const {
  onSearchFocus: focusSearchInput,
  onProductSelect,
  onProductVariantSelect,
  onProductSearch,
  onCustomerSearch,
  onCustomerSelect,
  clearCustomer,
  removeItem,
  updateQuantity,
  applyCoupon,
  handleCreate,
} = orderCreate;

const globalSearch = ref("");
const searchInput = ref<HTMLInputElement | null>(null);
function onSearchFocus(): void {
  focusSearchInput(searchInput.value);
}
</script>
