import { computed, onScopeDispose, ref, watch } from "vue";
import { defineStore } from "pinia";
import { useApi } from "~/composables/useApi";
import { useToast } from "~/composables/useToast";
import { getErrorMessage, formatVnd } from "~/utils/format";
import type { SelectOption } from "~/components/z/Select.vue";
import type { CustomerModel } from "~~/types/generated/backend-models.generated";
import { useCustomersStore } from "~/stores/customers";
import { useProductsStore } from "~/stores/products";
import { getProvinceZipCode, vietnamProvinces } from "~/data/vietnam-addresses";

interface SelectedItem {
  product_variant_id: string;
  name: string;
  variant: string;
  image: string | null;
  unit_price: number;
  quantity: number;
}

function resolveSearchPrice(
  item:
    | ProductSearchResult
    | ProductSearchVariantOption
    | {
        compare_price?: number;
        cost_price?: number;
        selling_price?: number;
        sale_price?: number;
        effective_price?: number;
      }
): number {
  const rawPrice =
    item.effective_price ??
    item.sale_price ??
    item.selling_price ??
    item.compare_price ??
    item.cost_price ??
    0;

  return Number(rawPrice) || 0;
}

interface ProductSearchVariantOption {
  id: string;
  name: string;
  sku: string | null;
  compare_price?: number;
  cost_price?: number;
  selling_price?: number;
  sale_price?: number;
  effective_price?: number;
  pricing?: {
    compare_price?: number;
    cost_price?: number;
    selling_price?: number;
    sale_price?: number;
    effective_price?: number;
    currency?: string;
  };
}

interface ProductSearchResult {
  id: string;
  name: string;
  slug: string;
  sku_base: string | null;
  compare_price?: number;
  cost_price?: number;
  selling_price?: number;
  sale_price?: number;
  effective_price?: number;
  pricing?: {
    compare_price?: number;
    cost_price?: number;
    selling_price?: number;
    sale_price?: number;
    effective_price?: number;
    currency?: string;
  };
  variant_options: ProductSearchVariantOption[];
}

interface CreateOrderForm {
  customer_id: string;
  guest_name: string;
  guest_phone: string;
  guest_email: string;
  shipping_name: string;
  shipping_phone: string;
  shipping_address: string;
  shipping_ward: string;
  shipping_district: string;
  shipping_province: string;
  shipping_zip: string;
  payment_method: string;
  payment_status: string;
  deposit_amount: string;
  coupon_code: string;
  notes: string;
  internal_notes: string;
  source: string;
}

interface CreateOrderErrors {
  customer: string;
  shipping_address: string;
  shipping_name: string;
  shipping_phone: string;
  shipping_province: string;
  deposit_amount: string;
}

const DEFAULT_FORM: CreateOrderForm = {
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
};

const DEFAULT_ERRORS: CreateOrderErrors = {
  customer: "",
  shipping_address: "",
  shipping_name: "",
  shipping_phone: "",
  shipping_province: "",
  deposit_amount: "",
};

export const useOrderCreateStore = defineStore("order-create", () => {
  const api = useApi();
  const toast = useToast();
  const customersStore = useCustomersStore();
  const productsStore = useProductsStore();

  const selectedCustomer = ref<CustomerModel | null>(null);
  const selectedCustomerId = ref<string | number | null>(null);
  const customerSearchLoading = ref(false);
  const customerResults = ref<CustomerModel[]>([]);
  const selectedItems = ref<SelectedItem[]>([]);
  const formPending = ref(false);

  const selectedProduct = ref<ProductSearchResult | null>(null);
  const selectedProductOptionId = ref<string | number | null>(null);
  const selectedProductOption = ref<ProductSearchVariantOption | null>(null);
  const selectedProductId = ref<string | number | null>(null);
  const productResults = ref<ProductSearchResult[]>([]);
  const productSearchLoading = ref(false);

  const form = ref<CreateOrderForm>({ ...DEFAULT_FORM });
  const formErrors = ref<CreateOrderErrors>({ ...DEFAULT_ERRORS });
  const discount = ref(0);
  const shippingFee = ref(30000);
  const paymentMethodOptions = ref<SelectOption[]>([
    { value: "cod", label: "Thanh toán khi nhận hàng (COD)" },
    { value: "bank_transfer", label: "Chuyển khoản ngân hàng" },
    { value: "momo", label: "Ví MoMo" },
    { value: "vnpay", label: "VNPay" },
  ]);
  const paymentStatusOptions = ref<SelectOption[]>([
    { value: "pending", label: "Chờ thanh toán" },
    { value: "partial", label: "Đã đặt cọc" },
    { value: "paid", label: "Đã thanh toán" },
  ]);

  let productSearchTimer: ReturnType<typeof setTimeout> | null = null;
  let customerSearchTimer: ReturnType<typeof setTimeout> | null = null;

  const customerSelectOptions = computed<SelectOption[]>(() => {
    return customerResults.value.map((customer) => ({
      value: customer.id,
      label: `${customer.first_name} ${customer.last_name} - ${
        customer.phone || customer.email || "Không có liên hệ"
      }`,
    }));
  });

  const productSelectOptions = computed<SelectOption[]>(() => {
    return productResults.value.map((variant) => ({
      value: variant.id,
      label: `${variant.name} (${variant.sku_base || "—"}) - ${formatVnd(
        resolveSearchPrice(variant)
      )}`,
    }));
  });

  const selectedProductVariantOptions = computed<SelectOption[]>(() => {
    return (selectedProduct.value?.variant_options ?? []).map((option) => ({
      value: option.id,
      label: `${option.name}${
        option.sku ? ` (${option.sku})` : ""
      } - ${formatVnd(resolveSearchPrice(option))}`,
    }));
  });

  const selectedProductHasOptions = computed(
    () => selectedProductVariantOptions.value.length > 0
  );

  const provinceOptions = computed<SelectOption[]>(() =>
    vietnamProvinces.map((province) => ({
      value: province.name,
      label: province.name,
    }))
  );

  const subtotal = computed(() =>
    selectedItems.value.reduce(
      (sum, item) => sum + item.unit_price * item.quantity,
      0
    )
  );

  const tax = computed(() => (subtotal.value - discount.value) * 0.1);
  const total = computed(
    () => subtotal.value - discount.value + shippingFee.value + tax.value
  );

  function resetForm(): void {
    form.value = { ...DEFAULT_FORM };
    formErrors.value = { ...DEFAULT_ERRORS };
    selectedCustomer.value = null;
    selectedCustomerId.value = null;
    selectedItems.value = [];
    selectedProduct.value = null;
    selectedProductOption.value = null;
    selectedProductId.value = null;
    selectedProductOptionId.value = null;
    customerResults.value = [];
    productResults.value = [];
    customerSearchLoading.value = false;
    productSearchLoading.value = false;
    discount.value = 0;
    shippingFee.value = 30000;
  }

  function clearCustomer(): void {
    selectedCustomer.value = null;
    selectedCustomerId.value = null;
    form.value.customer_id = "";
    form.value.shipping_name = "";
    form.value.shipping_phone = "";
    form.value.guest_email = "";
  }

  function onSearchFocus(input: HTMLInputElement | null): void {
    input?.select();
  }

  function addSelectedProductToOrder(
    product: ProductSearchResult,
    variant: ProductSearchVariantOption | null
  ): void {
    const unitPrice = resolveSearchPrice(variant ?? product);
    const lineName = product.name;
    const lineVariant = variant ? variant.name : product.sku_base || "Mặc định";
    const lineImage = null;

    const existingIndex = selectedItems.value.findIndex(
      (item) => item.product_variant_id === (variant?.id ?? product.id)
    );

    if (existingIndex >= 0) {
      const existingItem = selectedItems.value[existingIndex];

      if (existingItem) {
        existingItem.quantity += 1;
      }
    } else {
      selectedItems.value.push({
        product_variant_id: variant?.id ?? product.id,
        name: lineName,
        variant: lineVariant,
        image: lineImage,
        unit_price: unitPrice,
        quantity: 1,
      });
    }

    selectedProduct.value = null;
    selectedProductOption.value = null;
    selectedProductId.value = null;
    selectedProductOptionId.value = null;
  }

  function onProductSelect(productId: string | number): void {
    const product = productResults.value.find(
      (item) => item.id === String(productId)
    );

    if (!product) {
      return;
    }

    selectedProduct.value = product;
    selectedProductOption.value = null;
    selectedProductOptionId.value = null;

    if (!product.variant_options.length) {
      addSelectedProductToOrder(product, null);
    }
  }

  function onProductVariantSelect(variantId: string | number): void {
    const product = selectedProduct.value;

    if (!product) {
      return;
    }

    const variant = product.variant_options.find(
      (item) => item.id === String(variantId)
    );

    if (!variant) {
      return;
    }

    selectedProductOptionId.value = String(variantId);
    selectedProductOption.value = variant;
    addSelectedProductToOrder(product, variant);
  }

  function removeItem(index: number): void {
    selectedItems.value.splice(index, 1);
  }

  function updateQuantity(index: number, quantity: number): void {
    const item = selectedItems.value[index];

    if (!item || quantity <= 0) {
      return;
    }

    item.quantity = quantity;
  }

  function applyCoupon(): void {
    // TODO: Implement coupon validation
  }

  function onCustomerSearch(query: string): void {
    if (customerSearchTimer) {
      clearTimeout(customerSearchTimer);
    }

    if (!query.trim()) {
      customerResults.value = [];
      customerSearchLoading.value = false;
      return;
    }

    customerSearchLoading.value = true;
    customerSearchTimer = setTimeout(async () => {
      try {
        customerResults.value = await customersStore.searchCustomersUnified(
          query,
          10
        );
      } finally {
        customerSearchLoading.value = false;
      }
    }, 200);
  }

  function onCustomerSelect(value: string | number): void {
    const customerId = String(value);
    const customer = customerResults.value.find(
      (item) => item.id === customerId
    );

    if (!customer) {
      return;
    }

    selectedCustomer.value = customer;
    selectedCustomerId.value = customerId;
    form.value.customer_id = customerId;
    form.value.shipping_name = `${customer.first_name} ${customer.last_name}`;
    form.value.shipping_phone = customer.phone || "";
    form.value.guest_email = customer.email || "";
  }

  function validateForm(): boolean {
    let valid = true;
    formErrors.value = { ...DEFAULT_ERRORS };

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
      valid = false;
    }

    return valid;
  }

  async function handleCreate(): Promise<boolean> {
    if (!validateForm()) {
      return false;
    }

    formPending.value = true;

    try {
      const items = selectedItems.value.map((item) => ({
        product_variant_id: item.product_variant_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
      }));

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

      if (form.value.customer_id) {
        payload.customer_id = form.value.customer_id;
      } else {
        payload.guest_name = form.value.guest_name || form.value.shipping_name;
        payload.guest_phone =
          form.value.guest_phone || form.value.shipping_phone;
        payload.guest_email = form.value.guest_email || null;
      }

      await api.post("/orders", payload);
      resetForm();
      toast.success("Đã tạo đơn hàng", "Đơn hàng đã được lưu thành công.");
      await navigateTo("/orders");
      return true;
    } catch (error) {
      toast.error("Không thể tạo đơn hàng", getErrorMessage(error));
      return false;
    } finally {
      formPending.value = false;
    }
  }

  function onProductSearch(query: string): void {
    if (productSearchTimer) {
      clearTimeout(productSearchTimer);
    }

    if (!query.trim()) {
      productResults.value = [];
      productSearchLoading.value = false;
      return;
    }

    productSearchLoading.value = true;
    productSearchTimer = setTimeout(async () => {
      try {
        productResults.value = await productsStore.searchProductsUnified(
          query,
          10
        );
      } finally {
        productSearchLoading.value = false;
      }
    }, 200);
  }

  watch(
    () => form.value.shipping_province,
    (province) => {
      const normalizedProvince = String(province ?? "").trim();
      const nextZip = normalizedProvince
        ? getProvinceZipCode(normalizedProvince) ?? ""
        : "";

      if (form.value.shipping_zip === nextZip) {
        return;
      }

      form.value.shipping_zip = nextZip;
    },
    { immediate: true }
  );

  onScopeDispose(() => {
    if (productSearchTimer) {
      clearTimeout(productSearchTimer);
    }

    if (customerSearchTimer) {
      clearTimeout(customerSearchTimer);
    }
  });

  return {
    selectedCustomer,
    selectedCustomerId,
    customerSearchLoading,
    customerResults,
    selectedItems,
    formPending,
    selectedProduct,
    selectedProductId,
    selectedProductOptionId,
    selectedProductOption,
    productResults,
    productSearchLoading,
    form,
    formErrors,
    discount,
    shippingFee,
    customerSelectOptions,
    productSelectOptions,
    selectedProductVariantOptions,
    selectedProductHasOptions,
    provinceOptions,
    paymentMethodOptions,
    paymentStatusOptions,
    subtotal,
    tax,
    total,
    onSearchFocus,
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
    validateForm,
    resetForm,
  };
});
