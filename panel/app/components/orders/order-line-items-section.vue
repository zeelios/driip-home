<template>
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

    <div
      v-if="selectedProduct"
      class="mb-5 rounded-lg border border-white/8 bg-white/4 p-4"
    >
      <div class="mb-4 flex items-start justify-between gap-3">
        <div>
          <p class="font-medium text-white">{{ selectedProduct.name }}</p>
          <p class="text-sm text-white/50">
            {{ selectedProduct.sku_base || "SKU chính" }}
          </p>
        </div>
        <ZButton
          variant="ghost"
          size="sm"
          type="button"
          @click="clearProductDraft"
        >
          Huỷ
        </ZButton>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <OrderSizeSelect
          v-if="selectedProductHasSizeOptions"
          v-model="selectedSizeOptionId"
          :options="selectedProductSizeOptions"
          label="Chọn size"
          placeholder="Chọn size phù hợp"
          @update:modelValue="onProductSizeSelect"
        />

        <OrderVariantSelect
          v-if="
            selectedProductHasVariantOptions &&
            (!selectedProductHasSizeOptions || selectedSizeOptionId)
          "
          v-model="selectedProductOptionId"
          :options="selectedProductVariantOptions"
          label="Tuỳ chọn / biến thể"
          placeholder="Chọn tuỳ chọn phù hợp"
          :disabled="selectedProductHasSizeOptions && !selectedSizeOptionId"
          @update:modelValue="onProductVariantSelect"
        />
      </div>

      <p v-if="formErrors.items" class="mt-3 text-sm text-red-400">
        {{ formErrors.items }}
      </p>

      <div class="mt-4 flex justify-end">
        <ZButton
          variant="primary"
          size="sm"
          type="button"
          :disabled="selectedProductHasSizeOptions && !selectedSizeOptionId"
          @click="commitSelectedProduct"
        >
          Thêm vào đơn
        </ZButton>
      </div>
    </div>

    <div v-if="selectedItems.length > 0" class="flex flex-col gap-3">
      <OrderLineItemCard
        v-for="(item, index) in selectedItems"
        :key="`${item.product_variant_id}-${index}`"
        :item="item"
        :index="index"
        @remove="removeItem"
        @update-quantity="updateQuantity"
      />
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
      <p class="text-sm text-neutral-400">Chưa có sản phẩm nào được chọn</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { useOrderCreateStore } from "~/stores/order-create";
import OrderLineItemCard from "~/components/orders/order-line-item-card.vue";
import OrderSizeSelect from "~/components/orders/order-size-select.vue";
import OrderVariantSelect from "~/components/orders/order-variant-select.vue";

const orderCreate = useOrderCreateStore();
const {
  selectedProduct,
  selectedProductId,
  selectedProductOptionId,
  selectedSizeOptionId,
  selectedProductSizeOptions,
  selectedProductVariantOptions,
  selectedProductHasSizeOptions,
  selectedProductHasVariantOptions,
  selectedItems,
  productSelectOptions,
  productSearchLoading,
  formErrors,
} = storeToRefs(orderCreate);

const {
  clearProductDraft,
  commitSelectedProduct,
  onProductSelect,
  onProductSearch,
  onProductSizeSelect,
  onProductVariantSelect,
  removeItem,
  updateQuantity,
} = orderCreate;
</script>
