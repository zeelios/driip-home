export interface BoxTier {
  boxes: number;
  total: number;
}

export const BASE_BOX_COMPARE_PRICE = 2300000;
export const EXTRA_PROMO_RATE = 0.2;
export const BOX_TIERS: BoxTier[] = [
  { boxes: 1, total: 980000 },
  { boxes: 2, total: 1580000 },
  { boxes: 5, total: 3430000 },
];

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

export function getTierTotal(boxes: number): number {
  return getBoxTier(boxes).total;
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
