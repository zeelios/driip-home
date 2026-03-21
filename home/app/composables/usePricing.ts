import {
  BOX_TIERS,
  BASE_BOX_COMPARE_PRICE,
  EXTRA_PROMO_RATE,
  formatVnd,
  formatVndCurrency,
  getBoxTier,
  getCompareTotal,
  getExtraPromoDiscountAmount,
  getFinalTotal,
  getFinalUnitPrice,
  getSkuDisplayPrice,
  getTierTotal,
  getTierUnitPrice,
} from "~/utils/pricing";

export function usePricing() {
  return {
    BOX_TIERS,
    BASE_BOX_COMPARE_PRICE,
    EXTRA_PROMO_RATE,
    formatVnd,
    formatVndCurrency,
    getBoxTier,
    getCompareTotal,
    getExtraPromoDiscountAmount,
    getFinalTotal,
    getFinalUnitPrice,
    getSkuDisplayPrice,
    getTierTotal,
    getTierUnitPrice,
  };
}
