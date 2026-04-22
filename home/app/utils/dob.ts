/**
 * Date of Birth validation utilities
 * Ensures users are at least MIN_AGE years old
 */

export const MIN_AGE = 16;

export interface DobValidationResult {
  valid: boolean;
  year: number | null;
  age: number | null;
  error: string | null;
}

/**
 * Parse DD/MM/YYYY format and validate age
 * Returns validation result with age check
 */
export function validateDob(
  dobString: string | null | undefined
): DobValidationResult {
  if (!dobString || dobString === "") {
    return { valid: true, year: null, age: null, error: null }; // Empty is valid (optional field)
  }

  // Parse DD/MM/YYYY
  const trimmed = dobString.trim();
  const parts = trimmed.split("/");
  if (parts.length !== 3) {
    return {
      valid: false,
      year: null,
      age: null,
      error: "Sai định dạng ngày sinh",
    };
  }

  const dayStr = parts[0];
  const monthStr = parts[1];
  const yearStr = parts[2];

  if (!dayStr || !monthStr || !yearStr) {
    return {
      valid: false,
      year: null,
      age: null,
      error: "Sai định dạng ngày sinh",
    };
  }

  const day = parseInt(dayStr, 10);
  const month = parseInt(monthStr, 10);
  const year = parseInt(yearStr, 10);

  // Validate date components
  if (
    isNaN(day) ||
    isNaN(month) ||
    isNaN(year) ||
    day < 1 ||
    day > 31 ||
    month < 1 ||
    month > 12 ||
    year < 1900 ||
    year > new Date().getFullYear()
  ) {
    return {
      valid: false,
      year: isNaN(year) ? null : year,
      age: null,
      error: "Ngày sinh không hợp lệ",
    };
  }

  // Calculate age
  const today = new Date();
  const birthDate = new Date(year, month - 1, day);
  let age = today.getFullYear() - birthDate.getFullYear();

  // Adjust age if birthday hasn't occurred this year
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (
    monthDiff < 0 ||
    (monthDiff === 0 && today.getDate() < birthDate.getDate())
  ) {
    age--;
  }

  // Check minimum age
  if (age < MIN_AGE) {
    return {
      valid: false,
      year,
      age,
      error: `Phải từ ${MIN_AGE} tuổi trở lên`,
    };
  }

  // Check if year is in the future (invalid)
  if (year > today.getFullYear()) {
    return {
      valid: false,
      year,
      age: null,
      error: "Năm sinh không hợp lệ",
    };
  }

  // Check if age is unreasonably high (likely data entry error)
  if (age > 100) {
    return {
      valid: false,
      year,
      age,
      error: "Vui lòng kiểm tra lại năm sinh",
    };
  }

  return { valid: true, year, age, error: null };
}

/**
 * Format DoB input while typing
 * Converts raw digits to DD/MM/YYYY format
 */
export function formatDobInput(input: string): string {
  const digits = input.replace(/\D/g, "").slice(0, 8);

  if (digits.length > 4) {
    return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
  } else if (digits.length > 2) {
    return `${digits.slice(0, 2)}/${digits.slice(2)}`;
  }

  return digits;
}
