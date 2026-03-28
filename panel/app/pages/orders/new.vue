<template>
  <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <!-- Page header -->
    <div class="mb-6">
      <!-- Back link -->
      <NuxtLink
        to="/orders"
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
        Đơn hàng
      </NuxtLink>

      <!-- Title row -->
      <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
        <h1 class="text-xl sm:text-2xl font-semibold text-white tracking-tight">
          Tạo đơn hàng mới
        </h1>
      </div>
    </div>

    <!-- Main content -->
    <form
      class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_400px]"
      @submit.prevent="handleCreate"
    >
      <!-- Left column -->
      <div class="flex flex-col gap-5">
        <OrderLineItemsSection />

        <!-- Payment section -->
        <div class="rounded-xl border border-white/8 bg-[#111111] p-6">
          <p
            class="m-0 mb-5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Thanh toán
          </p>
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
          <p
            class="m-0 mb-5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Ghi chú
          </p>
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
          <p
            class="m-0 mb-5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Khách hàng
          </p>

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
                  type="button"
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
          <p
            class="m-0 mb-5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Giao hàng
          </p>
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
              :searchable="true"
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
          <p
            class="m-0 mb-5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Giảm giá
          </p>
          <div class="flex gap-3">
            <ZInput
              v-model="form.coupon_code"
              placeholder="Nhập mã giảm giá"
              class="flex-1"
            />
            <ZButton
              variant="outline"
              size="sm"
              type="button"
              @click="applyCoupon"
            >
              Áp dụng
            </ZButton>
          </div>
        </div>

        <!-- Summary section -->
        <div class="rounded-xl border border-white/10 bg-[#141414] p-6">
          <p
            class="m-0 mb-5 text-[0.6875rem] font-bold tracking-[0.07em] uppercase text-white/50"
          >
            Tổng cộng
          </p>
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
        <div class="flex gap-3 sm:flex-row flex-col">
          <ZButton
            variant="outline"
            size="md"
            class="flex-1"
            type="button"
            @click="navigateTo('/orders')"
          >
            Hủy
          </ZButton>
          <ZButton
            variant="primary"
            size="md"
            class="flex-1"
            type="submit"
            :loading="formPending"
          >
            Tạo đơn hàng
          </ZButton>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { storeToRefs } from "pinia";
import { formatVnd } from "~/utils/format";
import { useOrderCreateStore } from "~/stores/order-create";
import OrderLineItemsSection from "~/components/orders/order-line-items-section.vue";

definePageMeta({ layout: "panel" });

const orderCreate = useOrderCreateStore();

const {
  selectedCustomer,
  selectedCustomerId,
  customerSearchLoading,
  formPending,
  form,
  formErrors,
  customerSelectOptions,
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
  onCustomerSearch,
  onCustomerSelect,
  clearCustomer,
  applyCoupon,
  handleCreate,
} = orderCreate;
</script>
