import type { PublicStaffUserModel } from "../generated/backend-models.generated";

export interface LoginRequestDto {
  email: string;
  password: string;
}

export interface LoginResponseDto {
  success: boolean;
  data: PublicStaffUserModel | { data: PublicStaffUserModel };
}

export interface LogoutResponseDto {
  success: boolean;
  message: string;
}

export type MeResponseDto =
  | PublicStaffUserModel
  | { data: PublicStaffUserModel };

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
