/**
 * Auto-generated from backend model PHPDoc property annotations.
 * Source: backend/app/Models and backend/app/Domain/[domain]/Models
 */

export interface RoleModel {
  id?: string;
  name?: string;
  guard_name?: string;
  [key: string]: unknown;
}

export interface PermissionModel {
  id?: string;
  name?: string;
  guard_name?: string;
  [key: string]: unknown;
}

export interface CouponModel {
  id: string;
  code: string;
  name: string;
  description: string | null;
  type: string;
  value: number;
  min_order_amount: number | null;
  min_items: number | null;
  max_discount_amount: number | null;
  applies_to: string;
  applies_to_ids: unknown[];
  max_uses: number | null;
  max_uses_per_customer: number;
  used_count: number;
  is_public: boolean;
  is_active: boolean;
  starts_at: string | null;
  expires_at: string | null;
  created_by: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  usages?: CouponUsageModel[];
}

export interface CouponUsageModel {
  id: string;
  coupon_id: string;
  customer_id: string | null;
  order_id: string;
  discount_amount: number;
  used_at: string;

  // relations
  coupon?: CouponModel | null;
  customer?: CustomerModel | null;
}

export interface CustomerModel {
  id: string;
  customer_code: string;
  first_name: string;
  last_name: string;
  email: string | null;
  phone: string | null;
  phone_verified_at: string | null;
  gender: string | null;
  date_of_birth: string | null;
  avatar: string | null;
  source: string | null;
  referrer_id: string | null;
  referral_code: string | null;
  tags: unknown[];
  is_blocked: boolean;
  blocked_reason: string | null;
  total_orders: number;
  total_spent: number;
  last_ordered_at: string | null;
  notes: string | null;
  zalo_id: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  addresses?: CustomerAddressModel[];
  interactions?: CustomerInteractionModel[];
  referrer?: CustomerModel | null;
  referrals?: CustomerModel[];
  loyaltyAccount?: LoyaltyAccountModel | null;
}

export interface CustomerAddressModel {
  id: string;
  customer_id: string;
  label: string | null;
  recipient_name: string | null;
  phone: string | null;
  province: string | null;
  district: string | null;
  ward: string | null;
  address: string | null;
  zip_code: string | null;
  is_default: boolean;

  // relations
  customer?: CustomerModel | null;
}

export interface CustomerInteractionModel {
  id: string;
  customer_id: string;
  type: string;
  channel: string | null;
  summary: string | null;
  outcome: string | null;
  follow_up_at: string | null;
  created_by: string | null;
  created_at: string;

  // relations
  customer?: CustomerModel | null;
  createdBy?: StaffUserModel | null;
}

export interface InventoryModel {
  id: string;
  product_variant_id: string;
  warehouse_id: string;
  quantity_on_hand: number;
  quantity_reserved: number;
  quantity_available: number;
  quantity_incoming: number;
  reorder_point: number | null;
  reorder_quantity: number | null;
  last_counted_at: string | null;
  updated_at: string | null;

  // relations
  variant?: ProductVariantModel | null;
  warehouse?: WarehouseModel | null;
}

export interface InventoryTransactionModel {
  id: string;
  product_variant_id: string;
  warehouse_id: string;
  type: string;
  quantity: number;
  quantity_before: number;
  quantity_after: number;
  unit_cost: number | null;
  lot_number: string | null;
  reference_type: string | null;
  reference_id: string | null;
  notes: string | null;
  created_by: string | null;
  created_at: string;

  // relations
  variant?: ProductVariantModel | null;
  warehouse?: WarehouseModel | null;
  createdByUser?: StaffUserModel | null;
}

export interface PurchaseOrderModel {
  id: string;
  po_number: string;
  supplier_id: string;
  warehouse_id: string;
  status: string;
  expected_arrival_at: string | null;
  received_at: string | null;
  shipping_cost: number;
  other_costs: number;
  total_cost: number;
  notes: string | null;
  created_by: string;
  approved_by: string | null;
  approved_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  supplier?: SupplierModel | null;
  warehouse?: WarehouseModel | null;
  items?: PurchaseOrderItemModel[];
  receipts?: PurchaseOrderReceiptModel[];
  createdBy?: StaffUserModel | null;
  approvedBy?: StaffUserModel | null;
}

export interface PurchaseOrderItemModel {
  id: string;
  purchase_order_id: string;
  product_variant_id: string;
  sku: string;
  quantity_ordered: number;
  quantity_received: number;
  unit_cost: number;
  total_cost: number;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  purchaseOrder?: PurchaseOrderModel | null;
  variant?: ProductVariantModel | null;
}

export interface PurchaseOrderReceiptModel {
  id: string;
  purchase_order_id: string;
  receipt_number: string;
  received_by: string;
  received_at: string;
  notes: string | null;
  attachments: unknown[];
  receipt_items: unknown[];
  created_at: string | null;
  updated_at: string | null;

  // relations
  purchaseOrder?: PurchaseOrderModel | null;
  receivedByUser?: StaffUserModel | null;
}

export interface StockCountModel {
  id: string;
  count_number: string;
  warehouse_id: string;
  type: string;
  status: string;
  scheduled_at: string | null;
  started_at: string | null;
  completed_at: string | null;
  approved_by: string | null;
  approved_at: string | null;
  total_variance_qty: number | null;
  total_variance_value: number | null;
  notes: string | null;
  created_by: string;
  created_at: string | null;
  updated_at: string | null;

  // relations
  warehouse?: WarehouseModel | null;
  items?: StockCountItemModel[];
  createdBy?: StaffUserModel | null;
  approvedBy?: StaffUserModel | null;
}

export interface StockCountItemModel {
  id: string;
  stock_count_id: string;
  product_variant_id: string;
  quantity_expected: number;
  quantity_counted: number | null;
  variance: number | null;
  variance_value: number | null;
  notes: string | null;
  counted_by: string | null;
  counted_at: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  stockCount?: StockCountModel | null;
  variant?: ProductVariantModel | null;
  countedBy?: StaffUserModel | null;
}

export interface StockTransferModel {
  id: string;
  transfer_number: string;
  from_warehouse_id: string;
  to_warehouse_id: string;
  status: string;
  reason: string | null;
  requested_by: string;
  approved_by: string | null;
  dispatched_at: string | null;
  received_at: string | null;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  fromWarehouse?: WarehouseModel | null;
  toWarehouse?: WarehouseModel | null;
  items?: StockTransferItemModel[];
  requestedBy?: StaffUserModel | null;
  approvedBy?: StaffUserModel | null;
}

export interface StockTransferItemModel {
  id: string;
  stock_transfer_id: string;
  product_variant_id: string;
  quantity_requested: number;
  quantity_dispatched: number;
  quantity_received: number;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  transfer?: StockTransferModel | null;
  variant?: ProductVariantModel | null;
}

export interface SupplierModel {
  id: string;
  code: string;
  name: string;
  contact_name: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  province: string | null;
  country: string | null;
  payment_terms: string | null;
  notes: string | null;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  purchaseOrders?: PurchaseOrderModel[];
}

export interface LoyaltyAccountModel {
  id: string;
  customer_id: string;
  tier_id: string | null;
  points_balance: number;
  lifetime_points: number;
  lifetime_spending: number;
  tier_achieved_at: string | null;
  tier_expires_at: string | null;

  // relations
  customer?: CustomerModel | null;
  tier?: LoyaltyTierModel | null;
  transactions?: LoyaltyTransactionModel[];
}

export interface LoyaltyCampaignModel {
  id: string;
  name: string;
  type: string;
  multiplier: string;
  bonus_points: number;
  conditions: unknown[];
  starts_at: string | null;
  ends_at: string | null;
  is_active: boolean;
  created_by: string | null;
}

export interface LoyaltyTierModel {
  id: string;
  name: string;
  slug: string;
  min_lifetime_points: number;
  discount_percent: string;
  free_shipping: boolean;
  early_access: boolean;
  birthday_multiplier: string;
  perks: unknown[];
  color: string | null;
  sort_order: number;

  // relations
  accounts?: LoyaltyAccountModel[];
}

export interface LoyaltyTransactionModel {
  id: string;
  loyalty_account_id: string;
  type: string;
  points: number;
  balance_after: number;
  reference_type: string | null;
  reference_id: string | null;
  description: string | null;
  expires_at: string | null;
  created_by: string | null;
  created_at: string;

  // relations
  account?: LoyaltyAccountModel | null;
  createdByUser?: StaffUserModel | null;
}

export interface NotificationLogModel {
  id: string;
  channel: string;
  recipient: string;
  template_id: string | null;
  subject: string | null;
  payload: unknown[];
  status: string;
  attempts: number;
  sent_at: string | null;
  failed_at: string | null;
  error: string | null;
  notifiable_type: string | null;
  notifiable_id: string | null;
  created_at: string | null;

  // relations
  template?: NotificationTemplateModel | null;
}

export interface NotificationTemplateModel {
  id: string;
  slug: string;
  name: string;
  channel: string;
  subject: string | null;
  body_html: string;
  variables: unknown[];
  locale: string;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
}

export interface OrderModel {
  id: string;
  order_number: string;
  customer_id: string | null;
  guest_name: string | null;
  guest_email: string | null;
  guest_phone: string | null;
  status: string;
  payment_status: string;
  payment_method: string | null;
  payment_reference: string | null;
  paid_at: string | null;
  subtotal: number;
  coupon_id: string | null;
  coupon_code: string | null;
  coupon_discount: number;
  loyalty_points_used: number;
  loyalty_discount: number;
  shipping_fee: number;
  vat_rate: number;
  vat_amount: number;
  total_before_tax: number;
  total_after_tax: number;
  tax_code: string | null;
  cost_total: number;
  shipping_name: string;
  shipping_phone: string;
  shipping_province: string;
  shipping_district: string | null;
  shipping_ward: string | null;
  shipping_address: string;
  shipping_zip: string | null;
  notes: string | null;
  internal_notes: string | null;
  tags: unknown[];
  source: string | null;
  utm_source: string | null;
  utm_medium: string | null;
  utm_campaign: string | null;
  warehouse_id: string | null;
  assigned_to: string | null;
  packed_by: string | null;
  packed_at: string | null;
  confirmed_at: string | null;
  delivered_at: string | null;
  cancelled_at: string | null;
  cancellation_reason: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  customer?: CustomerModel | null;
  items?: OrderItemModel[];
  statusHistory?: OrderStatusHistoryModel[];
  claims?: OrderClaimModel[];
  returns?: OrderReturnModel[];
  shipments?: ShipmentModel[];
  warehouse?: WarehouseModel | null;
  assignedTo?: StaffUserModel | null;
  coupon?: CouponModel | null;
  taxInvoice?: TaxInvoiceModel | null;
}

export interface OrderClaimModel {
  id: string;
  claim_number: string;
  order_id: string;
  order_item_id: string | null;
  type: string;
  status: string;
  description: string;
  evidence_urls: unknown[];
  resolution: string | null;
  resolution_notes: string | null;
  refund_amount: number | null;
  assigned_to: string | null;
  created_by_customer: boolean;
  created_by: string | null;
  resolved_at: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  order?: OrderModel | null;
  orderItem?: OrderItemModel | null;
  assignedTo?: StaffUserModel | null;
  createdByUser?: StaffUserModel | null;
}

export interface OrderItemModel {
  id: string;
  order_id: string;
  product_variant_id: string | null;
  sku: string;
  name: string;
  size: string | null;
  color: string | null;
  unit_price: number;
  cost_price: number;
  quantity: number;
  quantity_returned: number;
  discount_amount: number;
  total_price: number;

  // relations
  order?: OrderModel | null;
  variant?: ProductVariantModel | null;
}

export interface OrderReturnModel {
  id: string;
  return_number: string;
  order_id: string;
  claim_id: string | null;
  status: string;
  return_items: unknown[];
  return_courier: string | null;
  return_tracking: string | null;
  total_refund: number | null;
  refund_method: string | null;
  refund_reference: string | null;
  refunded_at: string | null;
  received_at: string | null;
  processed_by: string | null;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  order?: OrderModel | null;
  claim?: OrderClaimModel | null;
  processedBy?: StaffUserModel | null;
}

export interface OrderStatusHistoryModel {
  id: string;
  order_id: string;
  from_status: string | null;
  to_status: string;
  note: string | null;
  is_customer_visible: boolean;
  created_by: string | null;
  created_at: string | null;

  // relations
  order?: OrderModel | null;
  createdByUser?: StaffUserModel | null;
}

export interface BrandModel {
  id: string;
  name: string;
  slug: string;
  logo: string | null;
  description: string | null;
  is_active: boolean;
  sort_order: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  products?: ProductModel[];
}

export interface CategoryModel {
  id: string;
  parent_id: string | null;
  name: string;
  slug: string;
  description: string | null;
  image: string | null;
  sort_order: number;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  parent?: CategoryModel | null;
  children?: CategoryModel[];
  products?: ProductModel[];
}

export interface ProductModel {
  id: string;
  brand_id: string | null;
  category_id: string | null;
  name: string;
  slug: string;
  description: string | null;
  short_description: string | null;
  sku_base: string | null;
  gender: string | null;
  season: string | null;
  tags: unknown[];
  status: string;
  is_featured: boolean;
  published_at: string | null;
  meta_title: string | null;
  meta_description: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  brand?: BrandModel | null;
  category?: CategoryModel | null;
  variants?: ProductVariantModel[];
}

export interface ProductAttributeModel {
  id: string;
  name: string;
  sort_order: number;
  created_at: string | null;
  updated_at: string | null;

  // relations
  values?: ProductAttributeValueModel[];
}

export interface ProductAttributeValueModel {
  id: string;
  attribute_id: string;
  value: string;
  color_hex: string | null;
  sort_order: number;
  created_at: string | null;
  updated_at: string | null;

  // relations
  attribute?: ProductAttributeModel | null;
}

export interface ProductPriceHistoryModel {
  id: string;
  product_variant_id: string;
  compare_price: number;
  cost_price: number;
  selling_price: number;
  changed_by: string | null;
  reason: string | null;
  changed_at: string;

  // relations
  variant?: ProductVariantModel | null;
  changedBy?: StaffUserModel | null;
}

export interface ProductVariantModel {
  id: string;
  product_id: string;
  sku: string;
  barcode: string | null;
  attribute_values: unknown[];
  compare_price: number;
  cost_price: number;
  selling_price: number;
  sale_price: number | null;
  sale_event_id: string | null;
  weight_grams: number;
  status: string;
  sort_order: number;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  product?: ProductModel | null;
  inventory?: InventoryModel[];
  priceHistory?: ProductPriceHistoryModel[];
}

export interface SaleEventModel {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  type: string;
  status: string;
  starts_at: string;
  ends_at: string | null;
  max_orders_total: number | null;
  is_public: boolean;
  banner_url: string | null;
  created_by: string;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  items?: SaleEventItemModel[];
  createdBy?: StaffUserModel | null;
}

export interface SaleEventItemModel {
  id: string;
  sale_event_id: string;
  product_variant_id: string;
  sale_price: number;
  compare_price: number | null;
  max_qty_per_customer: number | null;
  max_qty_total: number | null;
  sold_count: number;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;

  // relations
  saleEvent?: SaleEventModel | null;
  variant?: ProductVariantModel | null;
}

export interface WaitlistEntryModel {
  id: string;
  product_id: string;
  product_variant_id: string | null;
  customer_id: string | null;
  email: string | null;
  phone: string | null;
  source: string | null;
  notified_at: string | null;
  created_at: string | null;
}

export interface SettingModel {
  id: string;
  group: string;
  key: string;
  value: string | null;
  type: string;
  label: string | null;
  updated_at: string | null;
  updated_by: string | null;
}

export interface CourierCODRemittanceModel {
  id: string;
  courier_code: string;
  remittance_reference: string | null;
  period_from: string;
  period_to: string;
  total_cod_collected: number;
  total_fees_deducted: number;
  net_remittance: number;
  status: string;
  received_at: string | null;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  items?: CourierCODRemittanceItemModel[];
}

export interface CourierCODRemittanceItemModel {
  id: string;
  remittance_id: string;
  shipment_id: string;
  order_id: string;
  cod_amount: number;
  shipping_fee: number;
  other_fees: number;
  net_amount: number;
  created_at: string | null;
  updated_at: string | null;

  // relations
  remittance?: CourierCODRemittanceModel | null;
  shipment?: ShipmentModel | null;
  order?: OrderModel | null;
}

export interface CourierConfigModel {
  id: string;
  courier_code: string;
  name: string;
  api_endpoint: string | null;
  api_key: string | null;
  api_secret: string | null;
  account_id: string | null;
  pickup_hub_code: string | null;
  pickup_address: unknown[] | null;
  webhook_secret: string | null;
  is_active: boolean;
  settings: unknown[];
  created_at: string | null;
  updated_at: string | null;
}

export interface ShipmentModel {
  id: string;
  order_id: string;
  courier_code: string;
  tracking_number: string | null;
  internal_reference: string | null;
  status: string;
  label_url: string | null;
  cod_amount: number;
  cod_collected: boolean;
  shipping_fee_quoted: number | null;
  shipping_fee_actual: number | null;
  weight_kg: string | null;
  estimated_delivery_at: string | null;
  delivered_at: string | null;
  failed_attempts: number;
  courier_request: unknown[] | null;
  courier_response: unknown[] | null;
  created_by: string;
  created_at: string | null;
  updated_at: string | null;

  // relations
  order?: OrderModel | null;
  createdBy?: StaffUserModel | null;
  trackingEvents?: ShipmentTrackingEventModel[];
}

export interface ShipmentTrackingEventModel {
  id: string;
  shipment_id: string;
  status: string;
  courier_status_code: string | null;
  message: string;
  location: string | null;
  occurred_at: string;
  synced_at: string;
  raw_data: unknown[] | null;

  // relations
  shipment?: ShipmentModel | null;
}

export interface SalaryRecordModel {
  id: string;
  user_id: string;
  period: string;
  base_salary: number;
  allowances: unknown[];
  overtime_hours: string;
  overtime_rate: number;
  bonuses: unknown[];
  deductions: unknown[];
  total_gross: number;
  total_net: number;
  paid_at: string | null;
  payment_method: string | null;
  payment_reference: string | null;
  notes: string | null;
  created_by: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  user?: StaffUserModel | null;
  createdByUser?: StaffUserModel | null;
}

export interface StaffProfileModel {
  id: string;
  user_id: string;
  id_card_number: string | null;
  id_card_issued_at: string | null;
  id_card_issued_by: string | null;
  date_of_birth: string | null;
  gender: string | null;
  address: string | null;
  province: string | null;
  bank_name: string | null;
  bank_account_number: string | null;
  bank_account_name: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  user?: StaffUserModel | null;
}

export interface StaffUserModel {
  id: string;
  employee_code: string | null;
  name: string;
  email: string;
  phone: string | null;
  password: string;
  avatar: string | null;
  department: string | null;
  position: string | null;
  status: string;
  hired_at: string | null;
  terminated_at: string | null;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;

  // relations
  profile?: StaffProfileModel | null;
  salaryRecords?: SalaryRecordModel[];
  roles?: RoleModel[];
  permissions?: PermissionModel[];
}

export type PublicStaffUserModel = Pick<
  StaffUserModel,
  | "id"
  | "employee_code"
  | "name"
  | "email"
  | "phone"
  | "department"
  | "position"
  | "status"
  | "avatar"
  | "hired_at"
  | "created_at"
> & {
  roles?: string[];
};

export interface TaxConfigModel {
  id: string;
  name: string;
  rate: string;
  applies_to: string | null;
  applies_to_ids: unknown[];
  effective_from: string | null;
  effective_to: string | null;
  is_active: boolean;
  created_at: string;
}

export interface TaxInvoiceModel {
  id: string;
  order_id: string;
  invoice_number: string;
  invoice_type: string;
  buyer_name: string | null;
  buyer_tax_code: string | null;
  buyer_address: string | null;
  issued_at: string | null;
  file_url: string | null;
  created_by: string | null;
  created_at: string;

  // relations
  createdBy?: StaffUserModel | null;
}

export interface WarehouseModel {
  id: string;
  code: string;
  name: string;
  type: string;
  address: string | null;
  province: string | null;
  district: string | null;
  phone: string | null;
  manager_id: string | null;
  is_active: boolean;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  manager?: StaffUserModel | null;
  staffAssignments?: WarehouseStaffModel[];
  inventory?: InventoryModel[];
}

export interface WarehouseStaffModel {
  id: string;
  warehouse_id: string;
  user_id: string;
  role: string;
  assigned_at: string;
  unassigned_at: string | null;
  created_at: string | null;
  updated_at: string | null;

  // relations
  warehouse?: WarehouseModel | null;
  user?: StaffUserModel | null;
}

export interface AppUserModel extends StaffUserModel {}
