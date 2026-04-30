use serde::{Deserialize, Serialize};

// ── Fee Estimation ────────────────────────────────────────────────────────────

#[derive(Debug, Serialize)]
pub struct GhtkFeeRequest {
    pub pick_address: String,
    pub pick_province: String,
    pub pick_district: String,
    pub address: String,
    pub province: String,
    pub district: String,
    pub weight: i32, // grams
    pub value: i64,  // declared value in VND (for insurance)
    pub transport: GhtkTransport,
    pub deliver_option: GhtkDeliverOption,
}

#[derive(Debug, Serialize, Deserialize, Clone, Copy, PartialEq, Eq)]
#[serde(rename_all = "lowercase")]
pub enum GhtkTransport {
    Road,
    Fly,
}

#[derive(Debug, Serialize, Deserialize, Clone, Copy, PartialEq, Eq)]
#[serde(rename_all = "lowercase")]
pub enum GhtkDeliverOption {
    Xteam,
    None,
}

#[derive(Debug, Deserialize)]
pub struct GhtkFeeResponse {
    pub success: bool,
    pub message: String,
    pub fee: Option<GhtkFeeData>,
}

#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct GhtkFeeData {
    pub name: String,
    pub fee: i64, // total shipping fee (VND)
    pub insurance_fee: i64,
    pub include_vat: String,
    pub cost_id: String,
    pub delivery_type: String,
    pub ship_fee_only: Option<i64>,
}

// ── Create Order ──────────────────────────────────────────────────────────────

#[derive(Debug, Serialize)]
pub struct GhtkOrderRequest {
    pub products: Vec<GhtkProduct>,
    pub order: GhtkOrderPayload,
}

#[derive(Debug, Serialize)]
pub struct GhtkProduct {
    pub name: String,
    pub weight: f64, // kg
    pub quantity: i32,
    pub product_code: String,
}

#[derive(Debug, Serialize)]
pub struct GhtkOrderPayload {
    pub id: String, // your internal order ID (used as label)
    pub pick_name: String,
    pub pick_address: String,
    pub pick_province: String,
    pub pick_district: String,
    pub pick_tel: String,
    pub name: String, // recipient name
    pub address: String,
    pub province: String,
    pub district: String,
    pub tel: String,
    pub email: Option<String>,
    pub hamlet: String,         // "Khác" for misc
    pub is_freeship: i32,       // 1 = free ship (customer pre-paid), 0 = COD collects shipping
    pub pick_money: i64,        // COD amount (0 if already paid)
    pub value: i64,             // declared insurance value
    pub transport: String,      // "road" | "fly"
    pub deliver_option: String, // "xteam" | "none"
    pub note: Option<String>,
    pub tags: Vec<GhtkTag>,
}

#[derive(Debug, Serialize)]
pub struct GhtkTag {
    pub id: i32,
    pub weight: f64,
}

#[derive(Debug, Deserialize)]
pub struct GhtkOrderResponse {
    pub success: bool,
    pub message: String,
    pub order: Option<GhtkOrderData>,
}

#[derive(Debug, Deserialize, Serialize)]
pub struct GhtkOrderData {
    pub label_id: String, // GHTK's order ID — store as ghtk_order_id
    pub tracking_id: Option<String>,
    pub estimated_pick_time: Option<String>,
    pub estimated_deliver_time: Option<String>,
    pub status_id: Option<i32>,
    pub fee: Option<i64>,
    pub insurance_fee: Option<i64>,
}

// ── Cancel Order ──────────────────────────────────────────────────────────────

#[derive(Debug, Deserialize)]
pub struct GhtkCancelResponse {
    pub success: bool,
    pub message: String,
}

// ── Webhook payload ───────────────────────────────────────────────────────────

/// GHTK pushes this when shipment status changes.
#[derive(Debug, Deserialize, Serialize, Clone)]
pub struct GhtkWebhookPayload {
    pub partner_id: String, // your order ID echoed back
    pub label_id: Option<String>,
    pub status_id: i32,
    pub action_time: String, // "YYYY-MM-DD HH:MM:SS"
    pub reason_code: Option<String>,
    pub reason: Option<String>,
    pub weight: Option<f64>,
    pub fee: Option<i64>,
    pub ship_money: Option<i64>,
}

/// GHTK status_id mappings (for reference / display)
pub fn ghtk_status_label(status_id: i32) -> &'static str {
    match status_id {
        -1 => "Hủy đơn",
        1 => "Chờ lấy hàng",
        2 => "Đã lấy hàng",
        3 => "Đã nhận tại kho",
        4 => "Đang vận chuyển",
        5 => "Đang giao hàng",
        6 => "Đã giao hàng",
        7 => "Không thể giao",
        8 => "Đang trả hàng",
        9 => "Đã trả hàng",
        10 => "Huỷ — Đang trả hàng",
        11 => "Huỷ — Đã trả hàng",
        12 => "Chờ giao lại",
        13 => "Giao lại thành công",
        14 => "Thành công — Giao 1 phần",
        20 => "Chờ xác nhận",
        103 => "Chờ lấy hàng",
        127 => "COD đã thanh toán",
        128 => "COD chờ thanh toán",
        _ => "Trạng thái không xác định",
    }
}

/// Map GHTK status_id to our internal shipment status string
pub fn ghtk_status_to_internal(status_id: i32) -> &'static str {
    match status_id {
        -1 | 10 | 11 => "cancelled",
        1 | 20 | 103 => "picking",
        2..=4 => "delivering",
        5 | 12 => "delivering",
        6 | 13 | 14 => "delivered",
        7..=9 => "returned",
        _ => "delivering",
    }
}
