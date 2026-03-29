# Panel - Admin Dashboard (Nuxt 3)

Giao diện quản trị cho staff, quản lý đơn hàng, tồn kho, nhân viên, vận chuyển.

## Cấu Trúc

```
panel/
├── app/
│   ├── components/
│   │   ├── z/              # Custom UI components
│   │   │   ├── Button.vue
│   │   │   ├── Table.vue
│   │   │   ├── Input.vue
│   │   │   ├── Select.vue
│   │   │   ├── Checkbox.vue
│   │   │   ├── Modal.vue
│   │   │   ├── Skeleton.vue
│   │   │   └── ...
│   │   └── charts/         # Chart components
│   ├── pages/              # File-based routing
│   │   ├── index.vue       # Dashboard
│   │   ├── orders/
│   │   │   ├── index.vue   # List orders
│   │   │   └── [id].vue    # Order detail
│   │   ├── fulfillment/
│   │   │   └── index.vue   # Pick & Pack
│   │   ├── inventory/
│   │   ├── purchase-requests/
│   │   │   └── index.vue   # Yêu cầu mua hàng
│   │   ├── purchase-orders/
│   │   │   └── new.vue     # Tạo PO
│   │   ├── products/
│   │   ├── customers/
│   │   ├── staff/
│   │   └── ...
│   ├── layouts/
│   │   └── panel.vue       # Main layout với sidebar
│   ├── stores/             # Pinia stores
│   ├── composables/        # Shared logic
│   └── utils/              # Helpers
├── nuxt.config.ts
└── package.json
```

## Design System (Z Components)

### Colors
- Background: `#111111`
- Card: `#111111` border `white/8`
- Text Primary: `white/95`
- Text Secondary: `white/60`
- Accent: `blue-500`
- Warning: `amber-500`
- Danger: `red-500`
- Success: `green-500`

### Components

#### ZButton
```vue
<ZButton variant="primary" size="sm">Tạo mới</ZButton>
<ZButton variant="outline" size="xs">Hủy</ZButton>
<ZButton variant="danger" :loading="isLoading">Xóa</ZButton>
```
- Variants: primary, outline, danger, ghost
- Sizes: xs, sm, md

#### ZTable
```vue
<ZTable
  :columns="columns"
  :rows="items"
  :loading="loading"
  row-key="id"
  v-model:selection="selectedItems"
/>
```
- Columns config với width, align, type: "selection"
- Slots: cell-{key}, header
- Pagination support

#### ZInput
```vue
<ZInput v-model="search" type="search" size="sm" />
<ZInput v-model="quantity" type="number" :min="1" />
```
- Types: text, number, email, password, search, date
- Slots: prefix, suffix

#### ZSelect
```vue
<ZSelect
  v-model="selected"
  :options="options"
  placeholder="Chọn..."
  searchable
  @search="onSearch"
/>
```
- Options: [{ value, label }]
- Searchable with async loading

#### ZCheckbox
```vue
<ZCheckbox v-model="checked" size="md" />
```
- Used in ZTable selection column

## Pages

### Dashboard (`/`)
- Tổng quan KPIs
- Đơn hàng hôm nay
- Doanh thu
- Biểu đồ

### Orders (`/orders`)
- **List**: Bảng đơn hàng với filter, sort
- **Detail** (`/orders/[id]`): 
  - Thông tin khách hàng
  - Danh sách items (per-item tracking)
  - Status timeline
  - Actions: Confirm, Pack, Cancel

### Fulfillment (`/fulfillment`)
- Tab: "Cần lấy hàng" + "Đã lấy/Cần đóng"
- Pick items → Pack với courier selection
- GHTK integration: Tính phí, tạo vận đơn, in nhãn

### Inventory (`/inventory`)
- Danh sách tồn kho
- Điều chỉnh stock
- Lịch sử movements

### Purchase Requests (`/purchase-requests`)
- Summary cards: Tồn kho thấp, Đơn thiếu hàng
- Tab: Tồn kho thấp / Đơn thiếu hàng
- Checkbox selection → Tạo đơn đặt hàng
- Chuyển sang `/purchase-orders/new?items=...`

### Purchase Orders (`/purchase-orders`)
- **List**: Danh sách PO với status
- **New** (`/purchase-orders/new`):
  - Pre-fill items từ query params
  - Bảng sản phẩm (edit quantity, unit_cost)
  - Form: Supplier, Warehouse, Expected date
  - Modal thêm sản phẩm (ZSelect search)
  - Tổng chi phí + shipping + other costs

### Products (`/products`)
- Danh sách sản phẩm
- Variants với size/color
- Cost price tracking

### Customers (`/customers`)
- Danh sách khách hàng
- Lịch sử mua hàng
- Loyalty points

### Staff (`/staff`)
- Danh sách nhân viên
- Commission tracking
- Lương

## State Management

### Pinia Stores
```typescript
// stores/orders.ts
export const useOrderStore = defineStore('orders', () => {
  const orders = ref<Order[]>([])
  const fetchOrders = async () => { ... }
  return { orders, fetchOrders }
})

// stores/auth.ts
export const useAuthStore = defineStore('auth', () => {
  const token = useCookie('token')
  const user = ref<User | null>(null)
  const login = async (credentials) => { ... }
  return { token, user, login }
})
```

### Composables
```typescript
// composables/useApi.ts
export const useApi = () => {
  const fetch = (url: string, options?: RequestInit) => { ... }
  return { get, post, patch, delete }
}

// composables/useToast.ts
export const useToast = () => {
  return {
    success: (msg: string) => { ... },
    error: (title: string, msg: string) => { ... }
  }
}
```

## API Integration

```typescript
// utils/api.ts
const api = useApi()

// GET with params
const { data } = await api.get('/orders', { 
  params: { status: 'pending', page: 1 } 
})

// POST
const { data } = await api.post('/orders', orderData)
```

## Development

```bash
# Setup
cd panel
bun install

# Dev server
bun run dev
# → http://localhost:3000

# Build
bun run build

# Preview
bun run preview
```

## Key Features

1. **Mobile-First**: Tất cả components responsive
2. **Dark Theme**: Black/white vibe
3. **Real-time Updates**: WebSocket cho notifications
4. **Offline Support**: PWA capabilities
5. **Vietnamese UI**: Tất cả labels bằng tiếng Việt

## Routing

Nuxt file-based routing:
- `pages/index.vue` → `/`
- `pages/orders/index.vue` → `/orders`
- `pages/orders/[id].vue` → `/orders/123`
- `pages/purchase-orders/new.vue` → `/purchase-orders/new`

## Navigation

Sidebar menu (app/components/panel/Sidebar.vue):
- Dashboard
- Đơn hàng
- Đóng hàng (Fulfillment)
- Tồn kho
- Yêu cầu mua hàng
- Đơn đặt hàng
- Sản phẩm
- Khách hàng
- Nhân viên
- Báo cáo
