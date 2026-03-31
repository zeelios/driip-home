export interface BoxTier {
  boxes: number;
  total: number;
}

export const BASE_BOX_COMPARE_PRICE = 2300000;
export const EXTRA_PROMO_RATE = 0.2;
export const BOX_TIERS: BoxTier[] = [
  { boxes: 1, total: 980000 },
  { boxes: 2, total: 1580000 },
  { boxes: 3, total: 2100000 },
  { boxes: 4, total: 2550000 },
  { boxes: 5, total: 3000000 },
];

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
