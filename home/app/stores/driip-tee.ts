import { computed, ref, watch } from "vue";
import { defineStore } from "pinia";
import { useMetaEvents, type OrderData } from "~/composables/useMetaEvents";
import {
  compactMetaObject,
  META_ORDER_PROFILE_COOKIE_KEY,
  type MetaOrderProfileCookie,
} from "~/utils/meta-conversions";

// Product configuration for Driip Tee
const PRODUCT_CONFIG = {
  name: "Driip Tee",
  line: "driip-tee",
  baseSku: "driip-tee",
  price: 890000, // Fixed price - no discounts
} as const;

// Fixed price philosophy - no tiers, no sales
const FIXED_PRICE = PRODUCT_CONFIG.price;

import type { FormState, BaseCartItem } from "~/types/shared";

interface CartItem extends BaseCartItem {
  color: string;
  colorLabel: string;
  size: string;
}

interface CartCookieItem
  extends Pick<
    CartItem,
    "id" | "sku" | "color" | "colorLabel" | "size" | "quantity"
  > {}

const VIETNAM_PHONE_REGEX = /^0\d{9}$/;

// Available options
const COLORS = [
  { value: "black", label: "Midnight Black", labelVi: "Đen Huyền Bí" },
  { value: "white", label: "Pure White", labelVi: "Trắng Tinh Khiết" },
] as const;

const SIZES = ["S", "M", "L", "XL"] as const;

function normalizeVietnamPhoneInput(input: string): string {
  const sanitized = input.replace(/[^\d+]/g, "");
  if (!sanitized) return "";

  if (sanitized.startsWith("+84")) {
    return `0${sanitized.slice(3).replace(/\D/g, "").slice(0, 9)}`;
  }

  if (sanitized.startsWith("84")) {
    return `0${sanitized.slice(2).replace(/\D/g, "").slice(0, 9)}`;
  }

  const digits = sanitized.replace(/\D/g, "");
  if (!digits) return "";

  if (digits.startsWith("0")) {
    return digits.slice(0, 10);
  }

  return `0${digits.slice(0, 9)}`;
}

export const useDriipTeeStore = defineStore("driip-tee", () => {
  const { t, locale } = useI18n();
  const {
    trackViewContent,
    trackPurchase,
    trackAddToCart,
    trackInitiateCheckout,
  } = useMetaEvents();

  const orderProfileCookie = useCookie<MetaOrderProfileCookie | null>(
    META_ORDER_PROFILE_COOKIE_KEY,
    {
      maxAge: 60 * 60 * 24 * 90,
      path: "/",
      sameSite: "lax",
    }
  );

  const cartCookie = useCookie<CartCookieItem[] | null>("driip-tee-cart", {
    maxAge: 60 * 60 * 24 * 7,
    path: "/",
    sameSite: "lax",
  });

  // Order form state
  const order = ref({
    firstName: orderProfileCookie.value?.firstName ?? "",
    lastName: orderProfileCookie.value?.lastName ?? "",
    phone: orderProfileCookie.value?.phone ?? "",
    email: orderProfileCookie.value?.email ?? "",
    province: orderProfileCookie.value?.province ?? "",
    fullAddress: orderProfileCookie.value?.fullAddress ?? "",
    zipCode: orderProfileCookie.value?.zipCode ?? "",
    dob: orderProfileCookie.value?.dob ?? "",
    gender: (orderProfileCookie.value?.gender ?? "") as "" | "male" | "female",
  });

  const orderState = ref<FormState>("idle");
  const currentStep = ref(1);
  const viewContentFired = ref(false);

  // Product selection draft
  const draft = ref({
    color: "",
    size: "",
    quantity: 1,
  });

  // Cart items
  const items = ref<CartItem[]>(hydrateFromCookie());

  function hydrateFromCookie(): CartItem[] {
    return (
      cartCookie.value?.map((entry) => ({
        ...entry,
        sku: entry.sku || `${PRODUCT_CONFIG.baseSku}-${entry.color}`,
        price: FIXED_PRICE,
      })) ?? []
    );
  }

  // Computed
  const colorOptions = computed(() =>
    COLORS.map((c) => ({
      value: c.value,
      label: locale.value === "vi" ? c.labelVi : c.label,
      colorClass: c.value === "black" ? "bg-neutral-900" : "bg-white",
    }))
  );

  const sizeOptions = computed(() => SIZES);

  const draftValid = computed(
    () => draft.value.color && draft.value.size && draft.value.quantity > 0
  );

  const isEmpty = computed(() => items.value.length === 0);

  const totalItems = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0)
  );

  const grandTotal = computed(() => totalItems.value * FIXED_PRICE);

  const formattedGrandTotal = computed(() =>
    new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
      maximumFractionDigits: 0,
    }).format(grandTotal.value)
  );

  const orderValidationMsg = computed(() => {
    if (items.value.length === 0) return t("tee.order.errorEmptyCart");
    if (!order.value.firstName.trim()) return t("tee.order.errorFirstName");
    if (!order.value.lastName.trim()) return t("tee.order.errorLastName");
    if (!VIETNAM_PHONE_REGEX.test(order.value.phone))
      return t("tee.order.errorPhone");
    if (!order.value.province.trim()) return t("tee.order.errorProvince");
    if (!order.value.fullAddress.trim()) return t("tee.order.errorAddress");
    return "";
  });

  const step2Valid = computed(
    () =>
      order.value.firstName.trim() &&
      order.value.lastName.trim() &&
      VIETNAM_PHONE_REGEX.test(order.value.phone) &&
      order.value.province.trim() &&
      order.value.fullAddress.trim()
  );

  // Alias for template convenience
  const canAddToCart = computed(() => draftValid.value);
  const currentItemTotal = computed(() => draft.value.quantity * FIXED_PRICE);

  // Sync order profile cookie
  watch(
    () => order,
    () => {
      const profile = compactMetaObject({
        firstName: order.value.firstName.trim(),
        lastName: order.value.lastName.trim(),
        phone: order.value.phone.trim(),
        email: order.value.email.trim(),
        province: order.value.province.trim(),
        fullAddress: order.value.fullAddress.trim(),
        zipCode: order.value.zipCode.trim(),
        dob: order.value.dob.trim(),
        gender: order.value.gender,
      });

      orderProfileCookie.value = Object.keys(profile).length ? profile : null;
    },
    { deep: true, immediate: true }
  );

  // Sync cart cookie
  watch(
    () =>
      items.value.map(({ id, sku, color, colorLabel, size, quantity }) => ({
        id,
        sku,
        color,
        colorLabel,
        size,
        quantity,
      })),
    (snapshot) => {
      cartCookie.value = snapshot.length ? snapshot : null;
    },
    { deep: true }
  );

  // Track InitiateCheckout when entering checkout step
  watch(currentStep, (newStep, oldStep) => {
    if (newStep === 2 && oldStep === 1) {
      trackInitiateCheckout(grandTotal.value);
    }
  });

  // Actions
  function setDraftColor(color: string): void {
    draft.value.color = color;
  }

  function setDraftSize(size: string): void {
    draft.value.size = size;
  }

  function setDraftQuantity(qty: number): void {
    draft.value.quantity = qty;
  }

  function addToCart(): void {
    if (!draftValid.value) return;

    const colorOption = COLORS.find((c) => c.value === draft.value.color)!;
    const colorLabel = colorOption.label; // English for data

    const existing = items.value.find(
      (item) =>
        item.color === draft.value.color && item.size === draft.value.size
    );

    let finalQty: number;
    if (existing) {
      existing.quantity += draft.value.quantity;
      finalQty = existing.quantity;
    } else {
      const id = `${draft.value.color}-${draft.value.size}-${Date.now()}`;
      const sku = `${PRODUCT_CONFIG.baseSku}-${colorOption.value}`;
      items.value.push({
        id,
        sku,
        color: draft.value.color,
        colorLabel,
        size: draft.value.size,
        quantity: draft.value.quantity,
        price: FIXED_PRICE,
      });
      finalQty = draft.value.quantity;
    }

    // Reset draft
    draft.value.size = "";

    // Track AddToCart event
    const itemSku = `${PRODUCT_CONFIG.baseSku}-${colorOption.value}`;
    trackAddToCart(itemSku, FIXED_PRICE * finalQty);
  }

  function removeItem(id: string): void {
    items.value = items.value.filter((item) => item.id !== id);
  }

  function updateQuantity(id: string, quantity: number): void {
    const item = items.value.find((i) => i.id === id);
    if (!item) return;
    item.quantity = quantity;
  }

  function increaseQuantity(id: string): void {
    const item = items.value.find((i) => i.id === id);
    if (!item) return;
    item.quantity += 1;
  }

  function decreaseQuantity(id: string): void {
    const item = items.value.find((i) => i.id === id);
    if (!item || item.quantity <= 1) return;
    item.quantity -= 1;
  }

  function clearCart(): void {
    items.value = [];
  }

  function normalizePhoneInput(input: string): void {
    order.value.phone = normalizeVietnamPhoneInput(input);
  }

  function trackProductsViewed(): void {
    if (viewContentFired.value) return;
    viewContentFired.value = true;
    trackViewContent(PRODUCT_CONFIG.line);
  }

  async function submitOrder(): Promise<void> {
    if (orderValidationMsg.value || items.value.length === 0) return;

    orderState.value = "loading";
    try {
      await $fetch("/api/order", {
        method: "POST",
        body: {
          firstName: order.value.firstName,
          lastName: order.value.lastName,
          phone: order.value.phone,
          email: order.value.email,
          province: order.value.province,
          fullAddress: order.value.fullAddress,
          cartItems: items.value.map((item) => ({
            productName: PRODUCT_CONFIG.name,
            sku: item.sku,
            color: item.colorLabel,
            size: item.size,
            quantity: item.quantity,
            price: item.price,
          })),
          total: grandTotal.value,
          dob: order.value.dob || undefined,
          gender: order.value.gender || undefined,
          productLine: PRODUCT_CONFIG.line,
          timestamp: new Date().toISOString(),
        },
      });

      // Get first item SKU for Meta purchase tracking
      const firstItemSku = items.value[0]?.sku ?? PRODUCT_CONFIG.baseSku;

      const purchasePayload: OrderData = {
        first_name: order.value.firstName,
        last_name: order.value.lastName,
        phone: order.value.phone,
        email: order.value.email,
        city: order.value.province,
        state: order.value.province,
        country: "VN",
        street: order.value.fullAddress,
        value: grandTotal.value,
        sku: firstItemSku,
      };

      trackPurchase(purchasePayload);
      orderState.value = "success";
      clearCart();
    } catch {
      orderState.value = "error";
      setTimeout(() => {
        orderState.value = "idle";
      }, 3000);
    }
  }

  function resetOrder(): void {
    orderState.value = "idle";
    currentStep.value = 1;
    clearCart();
    order.value = {
      firstName: order.value.firstName,
      lastName: order.value.lastName,
      phone: order.value.phone,
      email: "",
      province: order.value.province,
      fullAddress: "",
      zipCode: "",
      dob: "",
      gender: "",
    };
  }

  return {
    order,
    orderState,
    currentStep,
    draft,
    items,
    colorOptions,
    sizeOptions,
    draftValid,
    canAddToCart,
    currentItemTotal,
    isEmpty,
    totalItems,
    grandTotal,
    formattedGrandTotal,
    orderValidationMsg,
    step2Valid,
    setDraftColor,
    setDraftSize,
    setDraftQuantity,
    addToCart,
    removeItem,
    updateQuantity,
    increaseQuantity,
    decreaseQuantity,
    clearCart,
    normalizePhoneInput,
    submitOrder,
    resetOrder,
    trackProductsViewed,
    PRODUCT_CONFIG,
    COLORS,
    SIZES,
  };
});
