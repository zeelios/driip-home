interface OrderItemRow {
  id: string;
  name: string;
  sku: string;
  size_display: string | null;
  color: string | null;
  unit_price: number;
  discount_amount: number;
  status: string;
  returned_at: string | null;
  inventory_id: string | null;
  inventory: {
    id: string;
    warehouse_id: string;
    quantity_on_hand: number;
  } | null;
  shipment_id: string | null;
  shipment: {
    id: string;
    tracking_number: string | null;
    status: string;
    courier_code: string;
  } | null;
}