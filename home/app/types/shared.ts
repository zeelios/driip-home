// Shared types across product stores to avoid duplicate imports

export type FormState = "idle" | "loading" | "success" | "error";

export interface BaseCartItem {
  id: string;
  sku: string;
  quantity: number;
  price: number;
}

export interface OrderInfo {
  firstName: string;
  lastName: string;
  phone: string;
  email: string;
  province: string;
  fullAddress: string;
}
