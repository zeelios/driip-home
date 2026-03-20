import { computed, ref, watch } from "vue";
import { defineStore } from "pinia";
import { useMetaEvents, type OrderData } from "~/composables/useMetaEvents";
import {
  compactMetaObject,
  META_ORDER_PROFILE_COOKIE_KEY,
  type MetaOrderProfileCookie,
} from "~/utils/meta-conversions";
import {
  BOX_TIERS,
  EXTRA_PROMO_RATE,
  formatVnd,
  formatVndCurrency,
  getCompareTotal,
  getExtraPromoDiscountAmount,
  getFinalTotal,
  getFinalUnitPrice,
  getTierTotal,
  getTierUnitPrice,
} from "~/composables/usePricing";
import { getProvinceZipCode } from "~/data/vietnam-addresses";

export interface SkuOption {
  value: string;
  label: string;
  price: number;
}

export interface BoxOption {
  boxes: number;
  total: number;
  unitPrice: number;
  finalTotal: number;
  finalUnitPrice: number;
}

export interface ColorOption {
  value: string;
  label: string;
  swatches: string[];
}

export interface BoxerColor {
  value: string;
  bg: string;
}

export type FormState = "idle" | "loading" | "success" | "error";

const boxerColors: BoxerColor[] = [
  { value: "Black", bg: "#111" },
  { value: "Gray", bg: "#888" },
  { value: "White", bg: "#f0f0f0" },
];

const skuOptions: SkuOption[] = [
  { value: "ck-brief", label: "Brief", price: getTierTotal(1) },
  { value: "ck-boxer", label: "Boxer", price: getTierTotal(1) },
];

const sizes: string[] = ["S", "M", "L", "XL", "2XL"];

const productSpecs = {
  vi: {
    brief: [
      "Dáng lưng thấp, thoải mái suốt ngày",
      "Chất liệu cotton modal mềm mại",
      "Dây lưng CK đặc trưng, co giãn tốt",
    ],
    boxer: [
      "Ống chân dài",
      "Hạn chế xê dịch",
      "Thiết kế hỗ trợ theo đường cong cơ thể",
    ],
  },
  en: {
    brief: [
      "Low-rise silhouette",
      "Modal-cotton blend",
      "Signature CK waistband",
    ],
    boxer: ["Extended leg coverage", "Anti-ride-up hem", "Contoured support"],
  },
} as const;

const colorToImage: Record<string, string> = {
  "3x-black": "Black",
  "3x-white": "White",
  "3x-grey": "Gray",
  mix: "Black",
};

const idleDelayMs = 3000;
const couponCode = "DRIIP20";

export const useCkUnderwearStore = defineStore("ck-underwear", () => {
  const { t, locale, setLocale } = useI18n();
  const { trackViewContent, trackPurchase, trackLead, trackSubscribe } =
    useMetaEvents();

  const orderProfileCookie = useCookie<MetaOrderProfileCookie | null>(
    META_ORDER_PROFILE_COOKIE_KEY,
    {
      maxAge: 60 * 60 * 24 * 90,
      path: "/",
      sameSite: "lax",
    }
  );

  const boxerColor = ref<string>("Black");
  const briefColor = ref<string>("Black");
  const codeCopied = ref<boolean>(false);
  const accessState = ref<FormState>("idle");
  const orderState = ref<FormState>("idle");
  const activeSection = ref<string | null>(null);
  const viewContentFired = ref<boolean>(false);
  const sizeGuideOpen = ref<boolean>(false);

  const access = ref({
    name: "",
    email: "",
    phone: "",
  });

  const order = ref({
    firstName: orderProfileCookie.value?.firstName ?? "",
    lastName: orderProfileCookie.value?.lastName ?? "",
    phone: orderProfileCookie.value?.phone ?? "",
    email: orderProfileCookie.value?.email ?? "",
    province: orderProfileCookie.value?.province ?? "",
    fullAddress: orderProfileCookie.value?.fullAddress ?? "",
    zipCode: orderProfileCookie.value?.zipCode ?? "",
    dob: "",
    gender: "" as "" | "male" | "female",
    boxes: 1,
    sku: "",
    size: "",
    color: "",
  });

  watch(
    order,
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

  watch(
    () => order.value.province,
    (province) => {
      const normalized = province.trim();
      const nextZip = normalized ? getProvinceZipCode(normalized) ?? "" : "";
      if (order.value.zipCode === nextZip) return;
      order.value.zipCode = nextZip;
    }
  );

  const boxOptions = computed<BoxOption[]>(() =>
    BOX_TIERS.map((tier) => ({
      boxes: tier.boxes,
      total: tier.total,
      unitPrice: getTierUnitPrice(tier.boxes),
      finalTotal: getFinalTotal(tier.boxes),
      finalUnitPrice: getFinalUnitPrice(tier.boxes),
    }))
  );

  const colorOptions = computed<ColorOption[]>(() => [
    {
      value: "3x-black",
      label: t("ck.order.colors.black"),
      swatches: ["#111", "#111", "#111"],
    },
    {
      value: "3x-white",
      label: t("ck.order.colors.white"),
      swatches: ["#f0f0f0", "#f0f0f0", "#f0f0f0"],
    },
    {
      value: "3x-grey",
      label: t("ck.order.colors.grey"),
      swatches: ["#888", "#888", "#888"],
    },
    {
      value: "mix",
      label: t("ck.order.colors.mix"),
      swatches: ["#111", "#888", "#f0f0f0"],
    },
  ]);

  const orderPreviewColor = computed<string>(
    () => colorToImage[order.value.color] ?? "Black"
  );

  const compareTotal = computed<number>(() =>
    getCompareTotal(order.value.boxes)
  );
  const tierTotal = computed<number>(() => getTierTotal(order.value.boxes));
  const tierUnitPrice = computed<number>(() =>
    getTierUnitPrice(order.value.boxes)
  );
  const extraDiscountAmount = computed<number>(() =>
    getExtraPromoDiscountAmount(order.value.boxes)
  );
  const orderPrice = computed<number>(() => getFinalTotal(order.value.boxes));
  const finalUnitPrice = computed<number>(() =>
    getFinalUnitPrice(order.value.boxes)
  );

  const formattedCompareTotal = computed<string>(() =>
    formatVndCurrency(compareTotal.value)
  );
  const formattedTierTotal = computed<string>(() =>
    formatVndCurrency(tierTotal.value)
  );
  const formattedTierUnitPrice = computed<string>(() =>
    formatVndCurrency(tierUnitPrice.value)
  );
  const formattedExtraDiscountAmount = computed<string>(() =>
    formatVndCurrency(extraDiscountAmount.value)
  );
  const formattedOrderPrice = computed<string>(() =>
    formatVndCurrency(orderPrice.value)
  );
  const formattedFinalUnitPrice = computed<string>(() =>
    formatVndCurrency(finalUnitPrice.value)
  );
  const formattedSkuPrice = computed<Record<string, string>>(() => ({
    "ck-brief": formatVndCurrency(getFinalUnitPrice(5)),
    "ck-boxer": formatVndCurrency(getFinalUnitPrice(5)),
  }));

  const skuLabel = computed<string>(
    () => skuOptions.find((sku) => sku.value === order.value.sku)?.label ?? ""
  );

  const colorLabel = computed<string>(
    () =>
      colorOptions.value.find((color) => color.value === order.value.color)
        ?.label ?? ""
  );

  const phoneValidationMsg = computed<string>(() => {
    const phone = order.value.phone.trim();
    if (!phone) return "";

    if (!/^\+?\d+$/.test(phone)) {
      return locale.value === "vi"
        ? "Số điện thoại chỉ được chứa số và dấu + ở đầu."
        : "Phone number can only contain digits and an optional leading +.";
    }

    if (phone.includes("+") && !phone.startsWith("+")) {
      return locale.value === "vi"
        ? "Dấu + chỉ được đặt ở đầu số điện thoại."
        : "The + sign is only allowed at the beginning of the phone number.";
    }

    const digits = phone.startsWith("+") ? phone.slice(1) : phone;
    const maxLength = phone.startsWith("+")
      ? 12
      : phone.startsWith("0")
      ? 11
      : 12;

    if (digits.length > maxLength) {
      return locale.value === "vi"
        ? `Số điện thoại tối đa ${maxLength} chữ số.`
        : `Phone number can be at most ${maxLength} digits.`;
    }

    return "";
  });

  const orderValidationMsg = computed<string>(() => {
    if (phoneValidationMsg.value) return phoneValidationMsg.value;
    if (!order.value.province) return t("ck.order.validate.province");
    if (!order.value.sku) return t("ck.order.validate.sku");
    if (!order.value.size) return t("ck.order.validate.size");
    if (!order.value.color) return t("ck.order.validate.color");
    return "";
  });

  const briefSpecs = computed<string[]>(() => [
    ...productSpecs[locale.value === "vi" ? "vi" : "en"].brief,
  ]);

  const boxerSpecs = computed<string[]>(() => [
    ...productSpecs[locale.value === "vi" ? "vi" : "en"].boxer,
  ]);

  function switchLang(): void {
    setLocale(locale.value === "vi" ? "en" : "vi");
  }

  function setActiveSection(section: string | null): void {
    activeSection.value = section;
  }

  function onProvinceChange(): void {}

  function normalizePhoneInput(input: string): void {
    let sanitized = input.replace(/[^\d+]/g, "");
    if (sanitized.includes("+")) {
      sanitized = sanitized.startsWith("+")
        ? `+${sanitized.slice(1).replace(/\+/g, "")}`
        : sanitized.replace(/\+/g, "");
    }

    const digits = sanitized.startsWith("+") ? sanitized.slice(1) : sanitized;
    const maxLength = sanitized.startsWith("+")
      ? 12
      : sanitized.startsWith("0")
      ? 11
      : 12;
    const trimmedDigits = digits.slice(0, maxLength);

    order.value.phone = sanitized.startsWith("+")
      ? `+${trimmedDigits}`
      : trimmedDigits;
  }

  function prefillOrder(sku: string): void {
    order.value.sku = sku;
  }

  function trackProductsViewed(): void {
    if (viewContentFired.value) return;
    viewContentFired.value = true;
    trackViewContent();
  }

  async function copyCode(): Promise<void> {
    if (!import.meta.client) return;
    await navigator.clipboard.writeText(couponCode);
    codeCopied.value = true;
    window.setTimeout(() => {
      codeCopied.value = false;
    }, 2000);
  }

  async function submitAccess(): Promise<void> {
    accessState.value = "loading";
    try {
      await $fetch("/api/subscribe", {
        method: "POST",
        body: {
          name: access.value.name,
          email: access.value.email,
          phone: access.value.phone,
          coupon: couponCode,
          timestamp: new Date().toISOString(),
        },
      });
      trackLead(access.value.email, access.value.phone, access.value.name);
      trackSubscribe(access.value.email, access.value.phone, access.value.name);
      accessState.value = "success";
    } catch {
      accessState.value = "error";
      window.setTimeout(() => {
        accessState.value = "idle";
      }, idleDelayMs);
    }
  }

  async function submitOrder(): Promise<void> {
    if (orderValidationMsg.value) return;

    orderState.value = "loading";
    try {
      await $fetch("/api/order", {
        method: "POST",
        body: {
          firstName: order.value.firstName,
          lastName: order.value.lastName,
          phone: order.value.phone,
          email: order.value.email, // using order's email instead of access's
          province: order.value.province,
          fullAddress: order.value.fullAddress,
          boxes: order.value.boxes,
          compareTotal: compareTotal.value,
          tierTotal: tierTotal.value,
          finalTotal: orderPrice.value,
          sku: order.value.sku,
          size: order.value.size,
          color: order.value.color,
          zipCode: order.value.zipCode,
          dob: order.value.dob || undefined,
          gender: order.value.gender || undefined,
          coupon: couponCode,
          timestamp: new Date().toISOString(),
        },
      });

      const purchasePayload: OrderData = {
        firstName: order.value.firstName,
        lastName: order.value.lastName,
        phone: order.value.phone,
        email: order.value.email,
        city: order.value.province,
        state: order.value.province,
        country: "VN",
        street: order.value.fullAddress,
        sku: order.value.sku,
        value: orderPrice.value,
      };

      trackPurchase(purchasePayload);
      orderState.value = "success";
    } catch {
      orderState.value = "error";
      window.setTimeout(() => {
        orderState.value = "idle";
      }, idleDelayMs);
    }
  }

  function nextBriefImage(): void {
    const idx = boxerColors.findIndex((c) => c.value === briefColor.value);
    const nextIdx = (idx + 1) % boxerColors.length;
    briefColor.value = boxerColors[nextIdx]!.value;
  }

  function prevBriefImage(): void {
    const idx = boxerColors.findIndex((c) => c.value === briefColor.value);
    const prevIdx = (idx - 1 + boxerColors.length) % boxerColors.length;
    briefColor.value = boxerColors[prevIdx]!.value;
  }

  function nextBoxerImage(): void {
    const idx = boxerColors.findIndex((c) => c.value === boxerColor.value);
    const nextIdx = (idx + 1) % boxerColors.length;
    boxerColor.value = boxerColors[nextIdx]!.value;
  }

  function prevBoxerImage(): void {
    const idx = boxerColors.findIndex((c) => c.value === boxerColor.value);
    const prevIdx = (idx - 1 + boxerColors.length) % boxerColors.length;
    boxerColor.value = boxerColors[prevIdx]!.value;
  }

  return {
    access,
    accessState,
    activeSection,
    boxerColor,
    boxerColors,
    boxerSpecs,
    boxOptions,
    briefColor,
    briefSpecs,
    codeCopied,
    colorLabel,
    colorOptions,
    compareTotal,
    couponCode,
    extraDiscountAmount,
    extraPromoRate: EXTRA_PROMO_RATE,
    finalUnitPrice,
    formatVnd,
    formatVndCurrency,
    formattedCompareTotal,
    formattedExtraDiscountAmount,
    formattedFinalUnitPrice,
    formattedOrderPrice,
    formattedSkuPrice,
    formattedTierTotal,
    formattedTierUnitPrice,
    normalizePhoneInput,
    onProvinceChange,
    order,
    orderPreviewColor,
    orderPrice,
    orderState,
    phoneValidationMsg,
    orderValidationMsg,
    prefillOrder,
    setActiveSection,
    sizeGuideOpen,
    sizes,
    skuLabel,
    skuOptions,
    submitAccess,
    submitOrder,
    switchLang,
    tierTotal,
    tierUnitPrice,
    trackProductsViewed,
    viewContentFired,
    copyCode,
    nextBriefImage,
    prevBriefImage,
    nextBoxerImage,
    prevBoxerImage,
  };
});
