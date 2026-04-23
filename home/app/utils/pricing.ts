// ═════════════════════════════════════════════════════════════════════════════
// DRIIP SLIDE PRICING
// ═════════════════════════════════════════════════════════════════════════════

export const SLIDE_NORMAL_PRICE_PER_PAIR = 480000; // regular / compare price
export const SLIDE_DEAL_PRICE_ONE = 349000; // 1 pair price
export const SLIDE_DEAL_PRICE_MULTI = 286000; // 2+ pairs price per pair
export const SLIDE_SHIPPING_FEE = 35000; // shipping for 1 pair, free for 2+

export interface SlidePriceBreakdown {
  pairs: number;
  subtotal: number; // product price only
  shippingFee: number;
  grandTotal: number; // subtotal + shipping
  compareTotal: number; // original price for comparison
  savings: number; // compareTotal - subtotal
  shippingNote: string;
}

/**
 * Calculate shipping fee for Driip Slide
 * Free for 2+ pairs, 35,000đ for 1 pair
 */
export function getSlideShippingFee(totalPairs: number): number {
  if (totalPairs <= 0) return 0;
  if (totalPairs >= 2) return 0;
  return SLIDE_SHIPPING_FEE;
}

/**
 * Calculate product subtotal (without shipping)
 */
export function getSlideSubtotal(totalPairs: number): number {
  if (totalPairs <= 0) return 0;
  if (totalPairs === 1) return SLIDE_DEAL_PRICE_ONE;
  return totalPairs * SLIDE_DEAL_PRICE_MULTI;
}

/**
 * Calculate grand total including shipping
 */
export function getSlideGrandTotal(totalPairs: number): number {
  return getSlideSubtotal(totalPairs) + getSlideShippingFee(totalPairs);
}

/**
 * Calculate compare/at price (original price before discount)
 */
export function getSlideCompareTotal(totalPairs: number): number {
  return SLIDE_NORMAL_PRICE_PER_PAIR * totalPairs;
}

/**
 * Get complete price breakdown for Driip Slide
 */
export function getSlidePriceBreakdown(
  totalPairs: number
): SlidePriceBreakdown {
  const subtotal = getSlideSubtotal(totalPairs);
  const shippingFee = getSlideShippingFee(totalPairs);
  const compareTotal = getSlideCompareTotal(totalPairs);

  return {
    pairs: totalPairs,
    subtotal,
    shippingFee,
    grandTotal: subtotal + shippingFee,
    compareTotal,
    savings: compareTotal - subtotal,
    shippingNote:
      shippingFee === 0
        ? "Freeship (đơn từ 2 đôi)"
        : `+${formatVnd(shippingFee)} phí ship (miễn phí từ 2 đôi)`,
  };
}

// ═════════════════════════════════════════════════════════════════════════════
// CK UNDERWEAR PRICING
// ═════════════════════════════════════════════════════════════════════════════

export interface BoxTier {
  boxes: number;
  total: number;
}

export const BASE_BOX_COMPARE_PRICE = 2300000;
export const EXTRA_PROMO_RATE = 0.2;
export const BOX_TIERS: BoxTier[] = [
  { boxes: 1, total: 980000 },
  { boxes: 2, total: 1580000 },
  { boxes: 3, total: 2160000 },
  { boxes: 4, total: 2920000 },
  { boxes: 5, total: 3430000 },
];

export const SKU_DISPLAY_BOXES = 5;

const priceFormatter = new Intl.NumberFormat("vi-VN");

export function formatVnd(amount: number): string {
  return priceFormatter.format(Math.round(amount));
}

export function formatVndCurrency(amount: number): string {
  return `${formatVnd(amount)}đ`;
}

export function getBoxTier(boxes: number): BoxTier {
  const tier = BOX_TIERS.find((item) => item.boxes === boxes);
  return tier ?? BOX_TIERS[0]!;
}

function getTierPriceByBundle(boxes: number): number {
  if (boxes <= 0) return 0;

  const bestPrices = Array.from(
    { length: boxes + 1 },
    () => Number.POSITIVE_INFINITY
  );
  bestPrices[0] = 0;

  for (let currentBoxes = 1; currentBoxes <= boxes; currentBoxes += 1) {
    for (const tier of BOX_TIERS) {
      if (tier.boxes > currentBoxes) continue;

      const previousPrice = bestPrices[currentBoxes - tier.boxes]!;
      if (!Number.isFinite(previousPrice)) continue;

      const nextPrice = previousPrice + tier.total;
      const currentBest = bestPrices[currentBoxes]!;
      if (nextPrice < currentBest) {
        bestPrices[currentBoxes] = nextPrice;
      }
    }
  }

  const total = bestPrices[boxes]!;
  return Number.isFinite(total) ? total : 0;
}

export function getTierTotal(boxes: number): number {
  return getTierPriceByBundle(boxes);
}

export function getCompareTotal(boxes: number): number {
  return BASE_BOX_COMPARE_PRICE * boxes;
}

export function getExtraPromoDiscountAmount(boxes: number): number {
  return Math.round(getTierTotal(boxes) * EXTRA_PROMO_RATE);
}

export function getFinalTotal(boxes: number): number {
  return getTierTotal(boxes) - getExtraPromoDiscountAmount(boxes);
}

export function getTierUnitPrice(boxes: number): number {
  return Math.round(getTierTotal(boxes) / boxes);
}

export function getFinalUnitPrice(boxes: number): number {
  return Math.round(getFinalTotal(boxes) / boxes);
}

export function getSkuDisplayPrice(): number {
  return getFinalUnitPrice(SKU_DISPLAY_BOXES);
}

export interface CkPriceBreakdown {
  boxes: number;
  tierTotal: number; // before extra promo
  extraDiscount: number; // 20% off
  finalTotal: number; // after extra promo
  compareTotal: number; // original price
  savings: number; // total savings
  unitPrice: number; // per box price
}

/**
 * Get complete price breakdown for CK Underwear
 */
export function getCkPriceBreakdown(boxes: number): CkPriceBreakdown {
  const tierTotal = getTierTotal(boxes);
  const extraDiscount = getExtraPromoDiscountAmount(boxes);
  const finalTotal = tierTotal - extraDiscount;
  const compareTotal = getCompareTotal(boxes);

  return {
    boxes,
    tierTotal,
    extraDiscount,
    finalTotal,
    compareTotal,
    savings: compareTotal - finalTotal,
    unitPrice: boxes > 0 ? Math.round(finalTotal / boxes) : 0,
  };
}

// ═════════════════════════════════════════════════════════════════════════════
// ORDER TYPE DETECTION
// ═════════════════════════════════════════════════════════════════════════════

export function isDriipSlideSku(sku: string): boolean {
  return sku?.startsWith("driip-slide") ?? false;
}

export function isCkUnderwearSku(sku: string): boolean {
  return sku?.startsWith("ck-") ?? false;
}
