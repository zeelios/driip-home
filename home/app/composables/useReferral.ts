/**
 * Composable for accessing and managing referral state.
 * Reads from the driip_referral cookie set by app.vue middleware.
 */

import {
  getReferralInfo,
  getSalesNameFromCode,
  isValidReferralCode,
  normalizeReferralCode,
  type ReferralMapping,
} from "~/utils/referral";

export interface UseReferralReturn {
  /** The raw referral code from cookie */
  referralCode: Ref<string | null>;
  /** Full staff name for Google Sheets */
  salesName: ComputedRef<string>;
  /** Staff info if valid, null otherwise */
  referralInfo: ComputedRef<ReferralMapping | null>;
  /** Whether a valid referral code exists */
  hasReferral: ComputedRef<boolean>;
  /** Whether the current code is valid */
  isValid: ComputedRef<boolean>;
  /** Set a new referral code (validates and saves to cookie) */
  setReferral: (code: string | null | undefined) => void;
  /** Clear the referral cookie */
  clearReferral: () => void;
}

export function useReferral(): UseReferralReturn {
  const referralCookie = useCookie<string | null>("driip_referral", {
    path: "/",
    sameSite: "lax",
    maxAge: 60 * 60 * 24 * 90, // 90 days
  });

  const referralCode = computed(() =>
    normalizeReferralCode(referralCookie.value)
  );

  const referralInfo = computed(() => getReferralInfo(referralCode.value));

  const salesName = computed(() =>
    getSalesNameFromCode(referralCode.value)
  );

  const hasReferral = computed(() => isValidReferralCode(referralCode.value));

  const isValid = computed(() => isValidReferralCode(referralCode.value));

  function setReferral(code: string | null | undefined): void {
    const normalized = normalizeReferralCode(code);

    if (normalized && isValidReferralCode(normalized)) {
      referralCookie.value = normalized;
    } else {
      // Invalid code - clear it
      referralCookie.value = null;
    }
  }

  function clearReferral(): void {
    referralCookie.value = null;
  }

  return {
    referralCode: referralCookie,
    salesName,
    referralInfo,
    hasReferral,
    isValid,
    setReferral,
    clearReferral,
  };
}
