import type { StaffUserModel } from "~~/types/generated/backend-models.generated";

export interface LoginRequestDto {
  email: string;
  password: string;
  device?: string;
}

export interface LoginResponseDto {
  success: boolean;
  token: string;
  data: StaffUserModel | { data: StaffUserModel };
}

export interface LogoutResponseDto {
  success: boolean;
  message: string;
}

export type MeResponseDto = StaffUserModel | { data: StaffUserModel };

export interface ForgotPasswordRequestDto {
  email: string;
}

export interface ForgotPasswordResponseDto {
  success: boolean;
  message: string;
}

export interface ResetPasswordRequestDto {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}

export interface ResetPasswordResponseDto {
  success: boolean;
  message: string;
}
