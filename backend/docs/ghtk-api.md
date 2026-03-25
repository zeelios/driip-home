# GHTK API Documentation

## Overview
Giao Hàng Tiết Kiệm (GHTK) API for shipment management and COD remittance tracking.

## Base URL
```
Production: https://services.giaohangtietkiem.vn
```

## Authentication
API Token via Header:
```
Token: {your_api_token}
```

## Endpoints

### 1. Create Shipment
**POST** `/services/shipment/order`

**Request Body:**
```json
{
  "products": [
    {
      "name": "Product name",
      "weight": 0.5,
      "quantity": 1
    }
  ],
  "order": {
    "id": "ORDER-123",
    "pick_address": "123 Pick St",
    "pick_province": "Hà Nội",
    "pick_district": "Cầu Giấy",
    "pick_ward": "Dịch Vọng",
    "pick_tel": "0912345678",
    "pick_name": "Driip Store",
    "tel": "0987654321",
    "name": "Customer Name",
    "address": "456 Delivery St",
    "province": "Hồ Chí Minh",
    "district": "Quận 1",
    "ward": "Phường Bến Nghé",
    "hamlet": "Khác",
    "is_freeship": 0,
    "pick_money": 500000,
    "note": "Call before delivery",
    "value": 500000
  }
}
```

**Response:**
```json
{
  "success": true,
  "order": {
    "partner_id": "ORDER-123",
    "label": "S123456789.GHTK",
    "area": 1,
    "fee": 25000,
    "insurance_fee": 0,
    "estimated_pick_time": "2026-03-25T09:00:00+07:00",
    "estimated_deliver_time": "2026-03-26T18:00:00+07:00",
    "products": [...]
  }
}
```

### 2. Get Shipment Detail (Tracking)
**GET** `/services/shipment/v2/{tracking_number}`

**Response:**
```json
{
  "success": true,
  "order": {
    "label_id": "S123456789.GHTK",
    "partner_id": "ORDER-123",
    "status": 9,
    "status_text": "Đã giao hàng",
    "created": "2026-03-25T08:30:00+07:00",
    "modified": "2026-03-26T14:20:00+07:00",
    "pick_date": "2026-03-25",
    "deliver_date": "2026-03-26",
    "customer_fullname": "Customer Name",
    "customer_tel": "0987654321",
    "address": "456 Delivery St",
    "is_cod_collected": true,
    "product_weight": 0.5,
    "money_collection": 500000,
    "log": [
      {
        "status": 9,
        "status_text": "Đã giao hàng",
        "location": "Hồ Chí Minh",
        "modified": "2026-03-26T14:20:00+07:00"
      },
      {
        "status": 8,
        "status_text": "Đang giao hàng",
        "location": "Hồ Chí Minh",
        "modified": "2026-03-26T08:00:00+07:00"
      }
    ]
  }
}
```

### 3. Cancel Shipment
**POST** `/services/shipment/cancel/{tracking_number}`

**Response:**
```json
{
  "success": true,
  "message": "Hủy đơn hàng thành công"
}
```

### 4. List COD Remittances (Kho Nhận)
**GET** `/services/kho-nhan/list`

**Query Parameters:**
- `from`: Date from (YYYY-MM-DD)
- `to`: Date to (YYYY-MM-DD)
- `status`: 0=pending, 1=completed

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "KN123456",
      "date": "2026-03-26",
      "total_money": 15000000,
      "total_orders": 25,
      "status": 1
    }
  ]
}
```

### 5. Get COD Remittance Detail
**GET** `/services/kho-nhan/{remittance_id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "KN123456",
    "date": "2026-03-26",
    "total_money": 15000000,
    "total_orders": 25,
    "status": 1,
    "orders": [
      {
        "label_id": "S123456789.GHTK",
        "partner_id": "ORDER-123",
        "customer_fullname": "Customer Name",
        "money_collection": 500000,
        "ship_money": 25000,
        "total_money": 475000
      }
    ]
  }
}
```

## Status Codes

| Code | Meaning | Internal Status |
|------|---------|-----------------|
| -1 | Đã hủy | cancelled |
| 1 | Chưa tiếp nhận | created |
| 2 | Đã tiếp nhận | created |
| 3 | Đã lấy hàng | picked_up |
| 4 | Đã nhập kho | in_transit |
| 5 | Đã xuất kho | in_transit |
| 6 | Đang giao hàng | out_for_delivery |
| 7 | Giao không thành công | failed_delivery |
| 8 | Giao lại | out_for_delivery |
| 9 | Đã giao hàng | delivered |
| 10 | Đang hoàn hàng | returning |
| 11 | Đã hoàn hàng | returned |
| 12 | Chờ giao lại | pending |
| 13 | Đang chuyển hoàn | returning |
| 20 | Đã đối soát | reconciled |
| 21 | Đã đối soát giao hàng | reconciled |
| 22 | Đã đối soát hoàn hàng | reconciled |
| 31 | Kiện vấn đề | failed_delivery |

## Error Handling

**Common Error Responses:**
```json
{
  "success": false,
  "message": "Token không hợp lệ",
  "error_code": "INVALID_TOKEN"
}
```

```json
{
  "success": false,
  "message": "Mã đơn hàng không tồn tại",
  "error_code": "ORDER_NOT_FOUND"
}
```
