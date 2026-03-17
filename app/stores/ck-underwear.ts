import { computed, ref } from "vue";
import { defineStore } from "pinia";
import { vietnamProvinces } from "~/data/vietnam-addresses";
import { useMetaEvents, type OrderData } from "~/composables/useMetaEvents";

export interface SkuOption {
  value: string;
  label: string;
  price: number;
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
  { value: "ck-brief", label: "CK BRIEF", price: 79 },
  { value: "ck-boxer", label: "CK BOXER", price: 95 },
];

const sizes: string[] = ["S", "M", "L", "XL", "2XL"];

const productSpecs = {
  vi: {
    brief: ["Viền lưng thấp", "Vải cotton modal", "Dây lưng chữ ký CK"],
    boxer: ["Ống chân dài", "Không xê dịch", "Túi định hình giải phẫu"],
  },
  en: {
    brief: [
      "Low-rise silhouette",
      "Modal-cotton blend",
      "Signature CK waistband",
    ],
    boxer: [
      "Extended leg coverage",
      "Anti-ride-up hem",
      "Contoured support",
    ],
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
  const {
    trackViewContent,
    trackAddToCart,
    trackPurchase,
    trackInitiateCheckout,
    trackLead,
    trackSubscribe,
  } = useMetaEvents();

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
    firstName: "",
    lastName: "",
    phone: "",
    province: "",
    district: "",
    ward: "",
    street: "",
    sku: "",
    size: "",
    color: "",
  });

  const selectedProvince = computed(
    () => vietnamProvinces.find((p) => p.name === order.value.province) ?? null
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

  const orderPrice = computed<number>(() => {
    const base =
      order.value.sku === "ck-brief"
        ? 79
        : order.value.sku === "ck-boxer"
          ? 95
          : 89;
    return Math.round(base * 0.8);
  });

  const skuLabel = computed<string>(
    () => skuOptions.find((sku) => sku.value === order.value.sku)?.label ?? ""
  );

  const colorLabel = computed<string>(
    () =>
      colorOptions.value.find((color) => color.value === order.value.color)
        ?.label ?? ""
  );

  const orderValidationMsg = computed<string>(() => {
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

  function onProvinceChange(): void {
    order.value.district = "";
  }

  function prefillOrder(sku: string): void {
    trackAddToCart(sku);
    order.value.sku = sku;
  }

  function trackHeroCTA(): void {
    trackInitiateCheckout();
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
      trackLead(access.value.email, access.value.phone);
      trackSubscribe(access.value.email, access.value.phone);
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
          email: access.value.email,
          province: order.value.province,
          district: order.value.district,
          ward: order.value.ward,
          street: order.value.street,
          sku: order.value.sku,
          size: order.value.size,
          color: order.value.color,
          coupon: couponCode,
          timestamp: new Date().toISOString(),
        },
      });

      const purchasePayload: OrderData = {
        firstName: order.value.firstName,
        lastName: order.value.lastName,
        phone: order.value.phone,
        email: access.value.email,
        city: order.value.province,
        district: order.value.district,
        ward: order.value.ward,
        street: order.value.street,
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

  return {
    access,
    accessState,
    activeSection,
    boxerColor,
    boxerColors,
    boxerSpecs,
    briefColor,
    briefSpecs,
    codeCopied,
    colorLabel,
    colorOptions,
    couponCode,
    onProvinceChange,
    order,
    orderPreviewColor,
    orderPrice,
    orderState,
    orderValidationMsg,
    prefillOrder,
    selectedProvince,
    setActiveSection,
    sizeGuideOpen,
    sizes,
    skuLabel,
    skuOptions,
    submitAccess,
    submitOrder,
    switchLang,
    trackHeroCTA,
    trackProductsViewed,
    viewContentFired,
    copyCode,
  };
});
