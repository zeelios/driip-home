import { computed, ref } from "vue";
import { defineStore } from "pinia";
import {
  EXTRA_PROMO_RATE,
  formatVndCurrency,
  getCompareTotal,
  getExtraPromoDiscountAmount,
  getFinalTotal,
  getTierTotal,
} from "~/composables/usePricing";

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

export const useCartStore = defineStore("cart", () => {
  const items = ref<CartItem[]>([]);

  // ── Totals ────────────────────────────────────────────────────────
  const grandTierTotal = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.tierTotal, 0)
  );

  const grandFinalTotal = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.finalTotal, 0)
  );

  const grandCompareTotal = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.compareTotal, 0)
  );

  const grandDiscount = computed<number>(
    () => grandCompareTotal.value - grandFinalTotal.value
  );

  const grandExtraDiscount = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.extraDiscount, 0)
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

  // ── Actions ───────────────────────────────────────────────────────
  function addItem(
    item: Omit<
      CartItem,
      "id" | "compareTotal" | "tierTotal" | "extraDiscount" | "finalTotal"
    >
  ): void {
    const id = `${item.sku}-${item.size}-${item.color}-${Date.now()}`;
    items.value.push({
      ...item,
      id,
      compareTotal: getCompareTotal(item.boxes),
      tierTotal: getTierTotal(item.boxes),
      extraDiscount: getExtraPromoDiscountAmount(item.boxes),
      finalTotal: getFinalTotal(item.boxes),
    });
  }

  function removeItem(id: string): void {
    items.value = items.value.filter((item) => item.id !== id);
  }

  function updateItemBoxes(id: string, boxes: number): void {
    const item = items.value.find((i) => i.id === id);
    if (!item) return;
    item.boxes = boxes;
    item.compareTotal = getCompareTotal(boxes);
    item.tierTotal = getTierTotal(boxes);
    item.extraDiscount = getExtraPromoDiscountAmount(boxes);
    item.finalTotal = getFinalTotal(boxes);
  }

  function clear(): void {
    items.value = [];
  }

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
