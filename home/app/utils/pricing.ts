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
