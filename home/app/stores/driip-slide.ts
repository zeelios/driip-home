import { computed, ref, watch } from "vue";
import { defineStore } from "pinia";
import { useMetaEvents, type OrderData } from "~/composables/useMetaEvents";
import {
  compactMetaObject,
  META_ORDER_PROFILE_COOKIE_KEY,
  type MetaOrderProfileCookie,
} from "~/utils/meta-conversions";

// Use PRODUCT_CONFIG for pricing

// Available options with grouped sizes
const COLORS = [
  {
    value: "hot-pink",
    label: "Hot Pink",
    labelVi: "Hồng Nóng",
    sizes: ["36-37", "38-39", "40-41"],
  },
  {
    value: "cyan-blue",
    label: "Cyan Blue",
    labelVi: "Xanh Cyan",
    sizes: ["40-41", "42-43", "44-45"],
  },
];

import type { FormState, BaseCartItem } from "~/types/shared";

// Product configuration for Driip Slide
const PRODUCT_CONFIG = {
  name: "Driip Slide",
  line: "driip-slide",
  baseSku: "driip-slide",
  priceOne: 286000,
  priceTwo: 500000,
} as const;

interface CartItem extends BaseCartItem {
  color: string;
  colorLabel: string;
  colorLabelVi: string;
  size: string;
}

interface CartCookieItem
  extends Pick<
    CartItem,
    "id" | "sku" | "color" | "colorLabel" | "colorLabelVi" | "size" | "quantity"
  > {}

const VIETNAM_PHONE_REGEX = /^0\d{9}$/;

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

export const useDriipSlideStore = defineStore("driip-slide", () => {
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

  const cartCookie = useCookie<CartCookieItem[] | null>("driip-slide-cart", {
    path: "/",
    maxAge: 60 * 60 * 24 * 7,
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

  // Track InitiateCheckout when entering checkout step
  watch(currentStep, (newStep, oldStep) => {
    if (newStep === 2 && oldStep === 1) {
      trackInitiateCheckout(grandTotal.value);
    }
  });

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
        // Add sku for backward compatibility with old cookie data
        sku: entry.sku || `${PRODUCT_CONFIG.baseSku}-${entry.color}`,
        price: calculatePrice(entry.quantity),
      })) ?? []
    );
  }

  function calculatePrice(quantity: number): number {
    if (quantity >= 2) {
      const setsOfTwo = Math.floor(quantity / 2);
      const remainder = quantity % 2;
      return (
        setsOfTwo * PRODUCT_CONFIG.priceTwo +
        remainder * PRODUCT_CONFIG.priceOne
      );
    }
    return quantity * PRODUCT_CONFIG.priceOne;
  }

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
        gender: order.value.gender.trim(),
      }) as MetaOrderProfileCookie;

      orderProfileCookie.value = Object.keys(profile).length ? profile : null;
    },
    { deep: true, immediate: true }
  );

  // Sync cart cookie
  watch(
    () =>
      items.value.map(
        ({ id, sku, color, colorLabel, colorLabelVi, size, quantity }) => ({
          id,
          sku,
          color,
          colorLabel,
          colorLabelVi,
          size,
          quantity,
        })
      ),
    (snapshot) => {
      cartCookie.value = snapshot.length ? snapshot : null;
    },
    { deep: true }
  );

  // Computed
  const colorOptions = computed(() =>
    COLORS.map((c) => ({
      value: c.value,
      label: locale.value === "vi" ? c.labelVi : c.label,
      sizes: c.sizes,
    }))
  );

  const availableSizes = computed(() => {
    const color = COLORS.find((c) => c.value === draft.value.color);
    return color?.sizes ?? [];
  });

  const draftValid = computed(
    () => draft.value.color !== "" && draft.value.size !== ""
  );

  const totalPairs = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0)
  );

  const grandTotal = computed(() => calculatePrice(totalPairs.value));

  const formattedGrandTotal = computed(() =>
    new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
      maximumFractionDigits: 0,
    }).format(grandTotal.value)
  );

  const isEmpty = computed(() => items.value.length === 0);

  const itemCount = computed(() => items.value.length);

  const phoneValidationMsg = computed<string>(() => {
    const phone = order.value.phone.trim();
    if (!phone) return "";

    if (!VIETNAM_PHONE_REGEX.test(phone)) {
      return locale.value === "vi"
        ? "Số điện thoại Việt Nam phải đủ 10 số và bắt đầu bằng 0."
        : "Vietnam phone numbers must contain 10 digits and start with 0.";
    }

    return "";
  });

  const orderValidationMsg = computed<string>(() => {
    if (phoneValidationMsg.value) return phoneValidationMsg.value;
    if (!order.value.firstName.trim())
      return locale.value === "vi"
        ? "Vui lòng nhập tên."
        : "Please enter your first name.";
    if (!order.value.lastName.trim())
      return locale.value === "vi"
        ? "Vui lòng nhập họ."
        : "Please enter your last name.";
    if (!order.value.province)
      return locale.value === "vi"
        ? "Vui lòng chọn tỉnh/thành."
        : "Please select a province.";
    if (!order.value.fullAddress.trim())
      return locale.value === "vi"
        ? "Vui lòng nhập địa chỉ."
        : "Please enter your address.";
    return "";
  });

  const step2Valid = computed(
    () =>
      !!order.value.firstName.trim() &&
      !!order.value.lastName.trim() &&
      !!order.value.phone.trim() &&
      !phoneValidationMsg.value &&
      !!order.value.province &&
      !!order.value.fullAddress.trim()
  );

  // Actions
  function setDraftColor(color: string): void {
    draft.value.color = color;
    draft.value.size = "";
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
    // Always use English labels for data/Google Sheets, Vietnamese only for display
    const colorLabel = colorOption.label; // English: Hot Pink / Cyan Blue
    const colorLabelVi = colorOption.labelVi; // Vietnamese: Hồng Nóng / Xanh Cyan

    const existing = items.value.find(
      (item) =>
        item.color === draft.value.color && item.size === draft.value.size
    );

    let finalQty: number;
    if (existing) {
      existing.quantity += draft.value.quantity;
      existing.price = calculatePrice(existing.quantity);
      finalQty = existing.quantity;
    } else {
      const id = `${draft.value.color}-${draft.value.size}-${Date.now()}`;
      const sku = `${PRODUCT_CONFIG.baseSku}-${colorOption.value}`;
      items.value.push({
        id,
        sku,
        color: draft.value.color,
        colorLabel, // Always English for data consistency
        colorLabelVi,
        size: draft.value.size,
        quantity: draft.value.quantity,
        price: calculatePrice(draft.value.quantity),
      });
      finalQty = draft.value.quantity;
    }

    // Reset draft
    draft.value.size = "";

    // Track AddToCart event with SKU
    const itemSku = `${PRODUCT_CONFIG.baseSku}-${colorOption.value}`;
    trackAddToCart(itemSku, calculatePrice(finalQty));
  }

  function removeItem(id: string): void {
    items.value = items.value.filter((item) => item.id !== id);
  }

  function updateQuantity(id: string, quantity: number): void {
    const item = items.value.find((i) => i.id === id);
    if (!item) return;
    item.quantity = quantity;
    item.price = calculatePrice(quantity);
  }

  function increaseQuantity(id: string): void {
    const item = items.value.find((i) => i.id === id);
    if (!item) return;
    updateQuantity(id, item.quantity + 1);
  }

  function decreaseQuantity(id: string): void {
    const item = items.value.find((i) => i.id === id);
    if (!item || item.quantity <= 1) return;
    updateQuantity(id, item.quantity - 1);
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
    trackViewContent("driip-slide");
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
            color: item.colorLabel, // English: Hot Pink / Cyan Blue
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
      const firstItemSku = items.value[0]?.sku ?? "driip-slide";

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
    // Reset order form but keep phone/province for convenience
    order.value = {
      firstName: "",
      lastName: order.value.lastName,
      phone: order.value.phone,
      email: order.value.email,
      province: order.value.province,
      fullAddress: "",
      zipCode: "",
      dob: order.value.dob,
      gender: order.value.gender,
    };
    viewContentFired.value = false;
  }

  return {
    order,
    orderState,
    currentStep,
    draft,
    items,
    colorOptions,
    availableSizes,
    draftValid,
    totalPairs,
    grandTotal,
    formattedGrandTotal,
    isEmpty,
    itemCount,
    phoneValidationMsg,
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
    // Individual price exports for template convenience
    PRICE_ONE_PAIR: PRODUCT_CONFIG.priceOne,
    PRICE_TWO_PAIR: PRODUCT_CONFIG.priceTwo,
  };
});
