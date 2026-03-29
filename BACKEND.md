# Backend - Laravel API

Laravel 11 monolith API với Domain-Driven Design, phục vụ cả Panel và Home.

## Cấu Trúc

```
backend/
├── app/
│   ├── Domain/                 # Domain modules (DDD)
│   │   ├── Commission/         # Hoa hồng nhân viên
│   │   ├── Coupon/            # Mã giảm giá
│   │   ├── Customer/          # Khách hàng
│   │   ├── Fulfillment/       # Đóng gói, xuất kho
│   │   ├── Inventory/         # Tồn kho, PO, điều chỉnh
│   │   ├── Loyalty/           # Điểm thưởng
│   │   ├── Order/             # Đơn hàng, order items
│   │   ├── Payment/           # Thanh toán, cấu hình ngân hàng
│   │   ├── Product/           # Sản phẩm, variants, size
│   │   ├── Shipment/          # Vận chuyển, GHTK
│   │   ├── Staff/             # Nhân viên, lương
│   │   └── ...
│   └── Http/
│       └── Controllers/Api/V1/ # API Controllers
├── database/migrations/        # Migration (2026_01_01_xxx)
├── routes/api.php              # API Routes
└── config/                     # Config files
```

## Domain Modules

### 1. Order Domain
- **Models**: Order, OrderItem, OrderStatusHistory
- **Actions**: CreateOrderAction, ConfirmOrderAction, PackOrderAction, CancelOrderAction
- **Features**: Per-item tracking (size, inventory, shipment, status)

### 2. Inventory Domain
- **Models**: Inventory, PurchaseOrder, PurchaseOrderItem, PurchaseRequest
- **Actions**: CreatePurchaseOrderAction, ReceiveInventoryAction
- **Features**: Low stock alerts, PO workflow (draft → approved → ordered → partial → received)

### 3. Product Domain
- **Models**: Product, ProductVariant, Category, SizeOption, Brand
- **Features**: Variants với size/color, cost_price tracking

### 4. Customer Domain
- **Models**: Customer, CustomerAddress, LoyaltyAccount
- **Features**: Guest checkout, loyalty points

### 5. Shipment Domain
- **Models**: Shipment, CourierConfig, ShipmentDiscrepancy
- **Services**: GHTKService (sandbox/production mode)
- **Features**: COD reconciliation, label printing

### 6. Fulfillment Domain
- **Actions**: PickItemAction, PackOrderAction
- **Features**: Pick-then-pack workflow, staff assignment

## API Endpoints

### Auth
```
POST /auth/login
POST /auth/logout
POST /auth/refresh
GET  /auth/me
```

### Orders
```
GET    /orders                 # List with filters
POST   /orders                 # Create order
GET    /orders/{id}            # Show order
PATCH  /orders/{id}            # Update order
POST   /orders/{id}/confirm    # Confirm pending
POST   /orders/{id}/pack       # Mark as packed
POST   /orders/{id}/cancel     # Cancel order
GET    /orders/{id}/timeline   # Status history
```

### Inventory
```
GET    /inventory                    # List inventory
GET    /inventory/{variant}          # Show variant inventory
POST   /inventory/adjust             # Adjust stock
GET    /inventory/movements          # Stock movements

GET    /purchase-orders             # List POs
POST   /purchase-orders             # Create PO
GET    /purchase-orders/{id}        # Show PO
POST   /purchase-orders/{id}/approve # Approve PO
POST   /purchase-orders/{id}/receive # Receive items

GET    /purchase-requests            # Summary counts
GET    /purchase-requests/low-stock  # Low stock items
GET    /purchase-requests/unfulfillable  # Unfulfillable orders
GET    /purchase-requests/selected-items # Get selected items
```

### Fulfillment
```
GET    /fulfillment/items       # Items to pick
POST   /fulfillment/pick        # Pick item
POST   /fulfillment/pack        # Pack order
GET    /fulfillment/stats       # Stats
```

### Shipments
```
GET    /shipments               # List shipments
POST   /shipments/{id}/track    # Sync tracking
GET    /shipments/{id}/label    # Print label

POST   /ghtk/submit-order       # GHTK integration
POST   /ghtk/calculate-fee
```

### Customers
```
GET    /customers              # List customers
POST   /customers              # Create customer
GET    /customers/{id}          # Show customer
PATCH  /customers/{id}          # Update customer
GET    /customers/{id}/orders    # Customer orders
POST   /customers/{id}/interactions  # Log interaction
```

### Products
```
GET    /products               # List products
POST   /products               # Create product
GET    /products/{id}          # Show product
PATCH  /products/{id}          # Update product

GET    /product-variants       # List variants
POST   /product-variants       # Create variant

GET    /categories             # List categories
GET    /size-options           # List sizes
```

### Staff
```
GET    /staff                  # List staff
POST   /staff                  # Create staff
GET    /staff/{id}/commissions # Staff commissions
POST   /commissions/{order}/mark-paid
```

## Database Schema Highlights

### order_items (Per-item tracking)
```sql
- size_option_id       -> size_options
- inventory_id         -> inventory
- shipment_id          -> shipments
- status               (pending/picked/packed/shipped/returned)
- picked_at/packed_at/returned_at
```

### inventory
```sql
- product_id           -> products
- warehouse_id         -> warehouses
- quantity_on_hand     # Thực tế
- quantity_reserved    # Đang giữ cho orders
- quantity_available   # Có thể bán
- reorder_point        # Ngưỡng cảnh báo
```

### purchase_orders
```sql
- supplier_id          -> suppliers
- warehouse_id         -> warehouses
- status               (draft/approved/ordered/partial/received/cancelled)
- expected_arrival_at
- shipping_cost, other_costs, total_cost
```

## Key Features

1. **Per-item Order Tracking**: Mỗi sản phẩm trong đơn hàng có dòng riêng, track size, inventory, shipment
2. **Inventory Reservations**: Khi tạo order, tự động reserve inventory
3. **PO Workflow**: Draft → Approved → Ordered → Partial/Received
4. **GHTK Integration**: Sandbox mode, tính phí, in nhãn
5. **Commission System**: Tính hoa hồng theo sản phẩm/ngày
6. **Loyalty Points**: Tích/lũy điểm

## Development

```bash
# Setup
cp .env.example .env
composer install
php artisan migrate
php artisan db:seed

# Run
php artisan serve
# or
php artisan octane:start

# Queue worker (for shipments/notifications)
php artisan queue:work
```

## Testing

```bash
php artisan test
php artisan test --filter=OrderTest
```
