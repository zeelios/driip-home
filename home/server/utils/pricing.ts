/**
 * SERVER PRICING - Re-export from unified client pricing
 * Single source of truth: ~/utils/pricing.ts
 */

export {
  // Driip Slide
  SLIDE_NORMAL_PRICE_PER_PAIR,
  SLIDE_DEAL_PRICE_ONE,
  SLIDE_DEAL_PRICE_MULTI,
  SLIDE_SHIPPING_FEE,
  type SlidePriceBreakdown,
  getSlideShippingFee,
  getSlideSubtotal,
  getSlideGrandTotal,
  getSlideCompareTotal,
  getSlidePriceBreakdown,
  // CK Underwear
  BASE_BOX_COMPARE_PRICE,
  EXTRA_PROMO_RATE,
  BOX_TIERS,
  SKU_DISPLAY_BOXES,
  type BoxTier,
  type CkPriceBreakdown,
  formatVnd,
  formatVndCurrency,
  getBoxTier,
  getTierTotal,
  getCompareTotal,
  getExtraPromoDiscountAmount,
  getFinalTotal,
  getTierUnitPrice,
  getFinalUnitPrice,
  getSkuDisplayPrice,
  getCkPriceBreakdown,
  // SKU detection
  isDriipSlideSku,
  isCkUnderwearSku,
} from "../../app/utils/pricing";
