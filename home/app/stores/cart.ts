import { computed, ref, watch } from "vue";
import { defineStore } from "pinia";
import {
  BASE_BOX_COMPARE_PRICE,
  formatVndCurrency,
  getExtraPromoDiscountAmount,
  getFinalTotal,
  getTierTotal,
} from "~/utils/pricing";

export interface CartItem {
  id: string;
  sku: "ck-brief" | "ck-boxer";
  skuLabel: string;
  boxes: number;
  size: string;
  color: string;
  colorLabel: string;
  compareTotal: number; // original price (BASE × boxes)
  tierTotal: number; // after bundle/tier deal
  extraDiscount: number; // extra 20% off (DRIIP20)
  finalTotal: number; // tierTotal − extraDiscount
}

interface CartCookieItem
  extends Pick<
    CartItem,
    "id" | "sku" | "skuLabel" | "boxes" | "size" | "color" | "colorLabel"
  > {}

export const useCartStore = defineStore("cart", () => {
  const cartCookie = useCookie<CartCookieItem[] | null>("ck-cart", {
    path: "/",
    maxAge: 60 * 60 * 24 * 7,
  });

  const hydrateFromCookie = (): CartItem[] =>
    cartCookie.value?.map((entry) => ({
      ...entry,
      compareTotal: 0,
      tierTotal: 0,
      extraDiscount: 0,
      finalTotal: 0,
    })) ?? [];

  const items = ref<CartItem[]>(hydrateFromCookie());

  const totalBoxes = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.boxes, 0)
  );

  // ── Totals ────────────────────────────────────────────────────────
  const grandTierTotal = computed<number>(() => getTierTotal(totalBoxes.value));

  const grandFinalTotal = computed<number>(() =>
    getFinalTotal(totalBoxes.value)
  );

  const grandCompareTotal = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.compareTotal, 0)
  );

  const grandDiscount = computed<number>(
    () => grandCompareTotal.value - grandFinalTotal.value
  );

  const grandExtraDiscount = computed<number>(() =>
    getExtraPromoDiscountAmount(totalBoxes.value)
  );

  const grandTierDiscount = computed<number>(
    () => grandCompareTotal.value - grandTierTotal.value
  );

  const formattedGrandFinalTotal = computed<string>(() =>
    formatVndCurrency(grandFinalTotal.value)
  );

  const formattedGrandCompareTotal = computed<string>(() =>
    formatVndCurrency(grandCompareTotal.value)
  );

  const itemCount = computed<number>(() => items.value.length);

  const isEmpty = computed<boolean>(() => items.value.length === 0);

  function syncItemTotals(): void {
    const boxes = totalBoxes.value;
    const tierTotal = getTierTotal(boxes);
    const finalTotal = getFinalTotal(boxes);
    let allocatedTierTotal = 0;
    let allocatedFinalTotal = 0;

    items.value.forEach((item, index) => {
      const isLastItem = index === items.value.length - 1;
      const itemCompareTotal = BASE_BOX_COMPARE_PRICE * item.boxes;
      const itemTierTotal = isLastItem
        ? tierTotal - allocatedTierTotal
        : Math.round((tierTotal * item.boxes) / boxes);
      const itemFinalTotal = isLastItem
        ? finalTotal - allocatedFinalTotal
        : Math.round((finalTotal * item.boxes) / boxes);

      item.compareTotal = BASE_BOX_COMPARE_PRICE * item.boxes;
      item.tierTotal = itemTierTotal;
      item.finalTotal = itemFinalTotal;
      item.extraDiscount = item.tierTotal - item.finalTotal;
      allocatedTierTotal += itemTierTotal;
      allocatedFinalTotal += itemFinalTotal;
      item.compareTotal = itemCompareTotal;
    });
  }

  // ── Actions ───────────────────────────────────────────────────────
  function addItem(
    item: Omit<
      CartItem,
      "id" | "compareTotal" | "tierTotal" | "extraDiscount" | "finalTotal"
    >
  ): void {
    const existing = items.value.find(
      (i) => i.sku === item.sku && i.size === item.size && i.color === item.color
    );
    if (existing) {
      existing.boxes += item.boxes;
    } else {
      const id = `${item.sku}-${item.size}-${item.color}-${Date.now()}`;
      items.value.push({
        ...item,
        id,
        compareTotal: 0,
        tierTotal: 0,
        extraDiscount: 0,
        finalTotal: 0,
      });
    }
    syncItemTotals();
  }

  function removeItem(id: string): void {
    items.value = items.value.filter((item) => item.id !== id);
    syncItemTotals();
  }

  function updateItemBoxes(id: string, boxes: number): void {
    const item = items.value.find((i) => i.id === id);
    if (!item) return;
    item.boxes = boxes;
    syncItemTotals();
  }

  function clear(): void {
    items.value = [];
  }

  watch(
    () =>
      items.value.map(
        ({ id, sku, skuLabel, boxes, size, color, colorLabel }) => ({
          id,
          sku,
          skuLabel,
          boxes,
          size,
          color,
          colorLabel,
        })
      ),
    (snapshot) => {
      cartCookie.value = snapshot.length ? snapshot : null;
    },
    { deep: true }
  );

  return {
    items,
    grandTierTotal,
    grandFinalTotal,
    grandCompareTotal,
    grandDiscount,
    grandTierDiscount,
    grandExtraDiscount,
    formattedGrandFinalTotal,
    formattedGrandCompareTotal,
    itemCount,
    isEmpty,
    addItem,
    removeItem,
    updateItemBoxes,
    clear,
  };
});
