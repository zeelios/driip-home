/**
 * Auto-generated from backend Requests/DTO conventions.
 * Do not edit manually. Run backend/scripts/generate-ai-contracts.php
 */

export interface AdjustInventoryDto {
  variant_id: string;
  warehouse_id: string;
  quantity: number;
  reason: string;
}

export interface AssignStaffDto {
  user_id: string;
  role: string;
}

export interface BulkOrderDto {
  order_ids: unknown[];
}

export interface CancelOrderDto {
  reason: string;
}

export interface CountStockItemDto {
  quantity_counted: number;
  notes?: string | null;
}

export interface CreateBrandDto {
  name: string;
  slug?: string | null;
  description?: string | null;
  logo_url?: string | null;
  website?: string | null;
  country?: string | null;
  is_active?: boolean | null;
  sort_order?: number | null;
}

export interface CreateCampaignDto {
  name: string;
  type: string;
  multiplier?: number | null;
  bonus_points?: number | null;
  conditions?: unknown[];
  starts_at: string;
  ends_at?: string | null;
  is_active?: boolean;
}

export interface CreateCategoryDto {
  parent_id?: string | null;
  name: string;
  slug?: string | null;
  description?: string | null;
  image_url?: string | null;
  is_active?: boolean | null;
  sort_order?: number | null;
}

export interface CreateClaimDto {
  type: string;
  description: string;
  evidence_urls?: unknown[];
  order_item_id?: string | null;
}

export interface CreateCouponDto {
  code: string;
  name: string;
  description?: string | null;
  type: string;
  value: number;
  min_order_amount?: number | null;
  min_items?: number | null;
  max_discount_amount?: number | null;
  applies_to?: string | null;
  applies_to_ids?: unknown[];
  max_uses?: number | null;
  max_uses_per_customer?: number | null;
  is_public?: boolean | null;
  is_active?: boolean | null;
  starts_at?: string | null;
  expires_at?: string | null;
}

export interface CreateCustomerDto {
  first_name: string;
  last_name: string;
  email?: string | null;
  phone?: string | null;
  gender?: string | null;
  source?: string | null;
  notes?: string | null;
}

export interface CreateOrderDto {
  customer_id?: string | null;
  guest_name?: string | null;
  guest_email?: string | null;
  guest_phone?: string | null;
  payment_method?: string | null;
  items: unknown[];
  coupon_code?: string | null;
  loyalty_points_to_use?: number | null;
  warehouse_id?: string | null;
  shipping_name: string;
  shipping_phone: string;
  shipping_province: string;
  shipping_district?: string | null;
  shipping_ward?: string | null;
  shipping_address: string;
  shipping_zip?: string | null;
  notes?: string | null;
  source?: string | null;
  utm_source?: string | null;
  utm_medium?: string | null;
  utm_campaign?: string | null;
}

export interface CreateProductDto {
  name: string;
  slug?: string | null;
  brand_id?: string | null;
  category_id?: string | null;
  description?: string | null;
  short_description?: string | null;
  sku_base?: string | null;
  gender?: string | null;
  season?: string | null;
  tags?: unknown[];
  status?: string | null;
}

export interface CreatePurchaseOrderDto {
  supplier_id: string;
  warehouse_id: string;
  expected_arrival_at?: string | null;
  notes?: string | null;
  items: unknown[];
}

export interface CreateRemittanceDto {
  courier_code: string;
  remittance_reference?: string | null;
  period_from: string;
  period_to: string;
  total_cod_collected: number;
  total_fees_deducted: number;
  net_remittance: number;
  notes?: string | null;
}

export interface CreateReturnDto {
  claim_id?: string | null;
  return_items: unknown[];
  notes?: string | null;
}

export interface CreateSalaryDto {
  period: string;
  base_salary: number;
  allowances?: unknown[];
  bonuses?: unknown[];
  deductions?: unknown[];
  overtime_hours?: number | null;
  overtime_rate?: number | null;
  payment_method?: string | null;
  payment_reference?: string | null;
  paid_at?: string | null;
  notes?: string | null;
}

export interface CreateSaleEventDto {
  name: string;
  slug?: string | null;
  description?: string | null;
  type: string;
  status?: string | null;
  starts_at: string;
  ends_at?: string | null;
  max_orders_total?: number | null;
  is_public?: boolean | null;
  banner_url?: string | null;
}

export interface CreateShipmentDto {
  order_id: string;
  courier_code: string;
  cod_amount: number;
  weight_kg?: number | null;
}

export interface CreateStaffDto {
  name: string;
  email: string;
  password: string;
  phone?: string | null;
  department?: string | null;
  position?: string | null;
  hired_at?: string | null;
  notes?: string | null;
  roles?: unknown[];
}

export interface CreateStockCountDto {
  warehouse_id: string;
  type: string;
  scheduled_at?: string | null;
  notes?: string | null;
  variant_ids?: unknown[];
}

export interface CreateStockTransferDto {
  from_warehouse_id: string;
  to_warehouse_id: string;
  reason?: string | null;
  notes?: string | null;
  items: unknown[];
}

export interface CreateTaxConfigDto {
  name: string;
  rate: number;
  applies_to?: string | null;
  applies_to_ids?: unknown[];
  effective_from: string;
  effective_to?: string | null;
  is_active?: boolean | null;
}

export interface CreateTierDto {
  name: string;
  slug?: string | null;
  min_lifetime_points: number;
  discount_percent: number;
  free_shipping?: boolean;
  early_access?: boolean;
  birthday_multiplier?: number;
  perks?: unknown[];
  color?: string | null;
  sort_order?: number;
}

export interface CreateVariantDto {
  sku: string;
  barcode?: string | null;
  attribute_values: unknown[];
  compare_price: number;
  cost_price: number;
  selling_price: number;
  weight_grams?: number | null;
  status?: string | null;
}

export interface CreateWarehouseDto {
  code: string;
  name: string;
  type: string;
  address?: string | null;
  province?: string | null;
  district?: string | null;
  phone?: string | null;
  manager_id?: string | null;
  is_active?: boolean | null;
  notes?: string | null;
}

export interface EarnPointsDto {
  points: number;
  reference_type?: string | null;
  reference_id?: string | null;
  description?: string | null;
}

export interface GenerateTaxInvoiceDto {
  order_id: string;
  invoice_type?: string | null;
  buyer_name?: string | null;
  buyer_tax_code?: string | null;
  buyer_address?: string | null;
}

export interface ReceivePurchaseOrderDto {
  received_by: string;
  notes?: string | null;
  receipt_items: unknown[];
}

export interface ReconcileRemittanceDto {
  items: unknown[];
}

export interface RedeemPointsDto {
  points: number;
  reference_id?: string | null;
  description?: string | null;
}

export interface UpdateBrandDto {
  name?: string;
  slug?: string;
  description?: string | null;
  logo_url?: string | null;
  website?: string | null;
  country?: string | null;
  is_active?: boolean | null;
  sort_order?: number | null;
}

export interface UpdateCampaignDto {
  name?: string;
  type?: string;
  multiplier?: number | null;
  bonus_points?: number | null;
  conditions?: unknown[];
  starts_at?: string;
  ends_at?: string | null;
  is_active?: boolean;
}

export interface UpdateCategoryDto {
  parent_id?: string | null;
  name?: string;
  slug?: string;
  description?: string | null;
  image_url?: string | null;
  is_active?: boolean | null;
  sort_order?: number | null;
}

export interface UpdateClaimDto {
  status?: string;
  resolution?: string | null;
  resolution_notes?: string | null;
  refund_amount?: number | null;
  assigned_to?: string | null;
}

export interface UpdateCouponDto {
  code?: string;
  name?: string;
  description?: string | null;
  type?: string;
  value?: number;
  min_order_amount?: number | null;
  min_items?: number | null;
  max_discount_amount?: number | null;
  applies_to?: string | null;
  applies_to_ids?: unknown[];
  max_uses?: number | null;
  max_uses_per_customer?: number | null;
  is_public?: boolean | null;
  is_active?: boolean | null;
  starts_at?: string | null;
  expires_at?: string | null;
}

export interface UpdateCourierConfigDto {
  name?: string;
  api_endpoint?: string | null;
  api_key?: string | null;
  api_secret?: string | null;
  account_id?: string | null;
  pickup_hub_code?: string | null;
  pickup_address?: unknown[];
  webhook_secret?: string | null;
  is_active?: boolean;
  settings?: unknown[];
}

export interface UpdateCustomerDto {
  first_name?: string | null;
  last_name?: string | null;
  email?: string | null;
  phone?: string | null;
  gender?: string | null;
  source?: string | null;
  notes?: string | null;
}

export interface UpdateOrderDto {
  notes?: string | null;
  internal_notes?: string | null;
  assigned_to?: string | null;
  tags?: unknown[];
}

export interface UpdateProductDto {
  name?: string;
  slug?: string;
  brand_id?: string | null;
  category_id?: string | null;
  description?: string | null;
  short_description?: string | null;
  sku_base?: string | null;
  gender?: string | null;
  season?: string | null;
  tags?: unknown[];
  status?: string | null;
  is_featured?: boolean | null;
  published_at?: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
}

export interface UpdateReturnDto {
  status?: string;
  return_courier?: string | null;
  return_tracking?: string | null;
  total_refund?: number | null;
  refund_method?: string | null;
  refund_reference?: string | null;
  refunded_at?: string | null;
  received_at?: string | null;
  processed_by?: string | null;
  notes?: string | null;
}

export interface UpdateSaleEventDto {
  name?: string;
  slug?: string;
  description?: string | null;
  type?: string;
  status?: string;
  starts_at?: string;
  ends_at?: string | null;
  max_orders_total?: number | null;
  is_public?: boolean | null;
  banner_url?: string | null;
}

export interface UpdateSettingsDto {
  settings: unknown[];
}

export interface UpdateStaffDto {
  name?: string;
  email?: string;
  phone?: string | null;
  department?: string | null;
  position?: string | null;
  status?: string;
  hired_at?: string | null;
  notes?: string | null;
  roles?: unknown[];
}

export interface UpdateTaxConfigDto {
  name?: string;
  rate?: number;
  applies_to?: string;
  applies_to_ids?: unknown[];
  effective_from?: string;
  effective_to?: string | null;
  is_active?: boolean;
}

export interface UpdateTierDto {
  name?: string;
  slug?: string;
  min_lifetime_points?: number;
  discount_percent?: number;
  free_shipping?: boolean;
  early_access?: boolean;
  birthday_multiplier?: number;
  perks?: unknown[];
  color?: string | null;
  sort_order?: number;
}

export interface UpdateVariantDto {
  sku?: string;
  barcode?: string | null;
  attribute_values?: unknown[];
  compare_price?: number;
  cost_price?: number;
  selling_price?: number;
  weight_grams?: number | null;
  status?: string | null;
  sort_order?: number | null;
  reason?: string | null;
}

export interface UpdateWarehouseDto {
  name?: string;
  type?: string;
  address?: string | null;
  province?: string | null;
  phone?: string | null;
  manager_id?: string | null;
  is_active?: boolean;
  notes?: string | null;
}

export interface ValidateCouponDto {
  code: string;
  customer_id?: string | null;
  order_amount: number;
  item_count?: number | null;
}
