<template>
  <div class="mt-3 p-3 rounded-lg bg-[#8B4513]/10 border border-[#8B4513]/20">
    <!-- Conflict header -->
    <div class="flex items-start gap-2 mb-3">
      <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        class="text-[#C4A77D] mt-0.5"
      >
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
      <div class="flex-1">
        <p class="text-sm font-medium text-white/90">
          Số điện thoại đã tồn tại
        </p>
        <p v-if="existingCustomer" class="text-xs text-white/60 mt-1">
          Thuộc về:
          <span class="text-white/90 font-medium">
            {{ existingCustomer.first_name }} {{ existingCustomer.last_name }}
          </span>
          <span
            v-if="existingCustomer.customer_code"
            class="text-[#C4A77D]/80 ml-1"
          >
            ({{ existingCustomer.customer_code }})
          </span>
        </p>
        <p v-if="(loyaltyPoints ?? 0) > 0" class="text-xs text-white/50 mt-1">
          <svg
            width="12"
            height="12"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="inline mr-1"
          >
            <polygon
              points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"
            />
          </svg>
          Điểm tích lũy:
          <strong class="text-[#C4A77D]">{{
            formatNumber(loyaltyPoints ?? 0)
          }}</strong>
          điểm
          <span class="text-white/40">(không chuyển)</span>
        </p>
        <p v-if="hasOrders" class="text-xs text-white/50 mt-1">
          <svg
            width="12"
            height="12"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="inline mr-1"
          >
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <line x1="9" y1="9" x2="15" y2="9" />
            <line x1="9" y1="15" x2="15" y2="15" />
          </svg>
          Có đơn hàng trong lịch sử
        </p>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="flex flex-col gap-2">
      <button
        type="button"
        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-white bg-[#6F4E37] hover:bg-[#5D4037] rounded-lg transition-colors"
        @click="$emit('use-existing')"
      >
        <svg
          width="14"
          height="14"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        >
          <polyline points="20 6 9 17 4 12" />
        </svg>
        Sử dụng khách hàng này
      </button>

      <div class="grid grid-cols-2 gap-2">
        <button
          type="button"
          class="flex items-center justify-center gap-1.5 px-3 py-2 text-sm text-white/80 bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg transition-colors"
          @click="$emit('overwrite')"
        >
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"
            />
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
          </svg>
          Cập nhật
        </button>
        <button
          type="button"
          class="flex items-center justify-center gap-1.5 px-3 py-2 text-sm text-white/80 bg-white/5 hover:bg-white/10 border border-white/10 rounded-lg transition-colors"
          @click="$emit('create-unlink')"
        >
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Tạo mới
        </button>
      </div>
    </div>

    <!-- Tooltips -->
    <div class="mt-3 pt-3 border-t border-white/10 space-y-1">
      <p class="text-[11px] text-white/40 flex items-start gap-1.5">
        <span class="text-[#C4A77D]/60 font-medium">Cập nhật:</span>
        <span
          >Ghi đè thông tin khách hàng cũ (SĐT giữ nguyên, lịch sử & điểm giữ
          nguyên)</span
        >
      </p>
      <p class="text-[11px] text-white/40 flex items-start gap-1.5">
        <span class="text-[#C4A77D]/60 font-medium">Tạo mới:</span>
        <span
          >Tạo khách hàng mới, xóa SĐT khỏi khách hàng cũ (điểm không
          chuyển)</span
        >
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { CustomerModel } from "~~/types/generated/backend-models.generated";

defineProps<{
  existingCustomer: CustomerModel | null;
  hasOrders?: boolean;
  loyaltyPoints?: number;
}>();

defineEmits<{
  "use-existing": [];
  overwrite: [];
  "create-unlink": [];
}>();

function formatNumber(num: number): string {
  return new Intl.NumberFormat("vi-VN").format(num);
}
</script>
