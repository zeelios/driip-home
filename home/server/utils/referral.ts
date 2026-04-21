/**
 * Server-side referral code mapping for sales staff.
 * Duplicated from app/utils/referral.ts for server API usage.
 */

export interface ReferralMapping {
  code: string;
  name: string; // Full name for Google Sheets
  shortName: string; // Display name
}

/**
 * Map of referral codes to staff information.
 * Add new staff here as needed.
 */
export const REFERRAL_MAP: Record<string, ReferralMapping> = {
  kn: { code: "kn", name: "Kim Ngoc", shortName: "Kim Ngọc" },
  pa: { code: "pa", name: "Phuong Anh", shortName: "Phương Anh" },
  ze: { code: "ze", name: "Zeelios", shortName: "Zeelios" },
};

/**
 * Check if a referral code is valid.
 */
export function isValidReferralCode(code: string | null | undefined): boolean {
  if (!code) return false;
  const normalized = code.toLowerCase().trim();
  return normalized in REFERRAL_MAP;
}

/**
 * Get staff information from a referral code.
 * Returns null if code is invalid.
 */
export function getReferralInfo(
  code: string | null | undefined
): ReferralMapping | null {
  if (!code) return null;
  const normalized = code.toLowerCase().trim();
  return REFERRAL_MAP[normalized] ?? null;
}

/**
 * Get the full name for Google Sheets from a referral code.
 * Falls back to "Website" for invalid/missing codes.
 */
export function getSalesNameFromCode(code: string | null | undefined): string {
  const info = getReferralInfo(code);
  return info?.name ?? "Website";
}

/**
 * Normalize a referral code input.
 * Lowercases, trims whitespace.
 */
export function normalizeReferralCode(
  code: string | null | undefined
): string | null {
  if (!code) return null;
  const normalized = code.toLowerCase().trim();
  return normalized || null;
}
