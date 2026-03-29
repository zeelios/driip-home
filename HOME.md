# Home - Customer Website (Nuxt 3)

Website khách hàng: xem sản phẩm, đặt hàng, theo dõi đơn, tích điểm.

## Cấu Trúc

```
home/
├── app/
│   ├── components/         # Vue components
│   │   ├── product/        # Product cards, galleries
│   │   ├── cart/           # Cart drawer, mini cart
│   │   ├── checkout/       # Checkout forms
│   │   └── ui/             # UI primitives
│   ├── pages/              # File-based routing
│   │   ├── index.vue       # Homepage
│   │   ├── products/
│   │   │   ├── index.vue   # Product listing
│   │   │   └── [slug].vue  # Product detail
│   │   ├── cart.vue        # Shopping cart
│   │   ├── checkout.vue    # Checkout
│   │   ├── account/
│   │   │   ├── index.vue   # Account dashboard
│   │   │   ├── orders.vue  # Order history
│   │   │   └── loyalty.vue # Loyalty points
│   │   └── track-order.vue # Order tracking
│   ├── layouts/
│   │   └── default.vue     # Main layout với header/footer
│   ├── stores/
│   │   ├── cart.ts         # Cart state
│   │   ├── auth.ts         # Customer auth
│   │   └── checkout.ts     # Checkout flow
│   ├── composables/        # Shared logic
│   └── utils/              # Helpers
├── nuxt.config.ts
└── package.json
```

## Pages

### Homepage (`/`)
- Hero banner
- Featured products
- New arrivals
- Sale events
- Categories grid

### Products (`/products`)
- **Listing**: Filter, sort, pagination
- **Detail** (`/products/[slug]`):
  - Product gallery (images)
  - Size/Color selection
  - Add to cart
  - Related products
  - Reviews

### Cart (`/cart`)
- Mini cart (slide-out drawer)
- Full cart page:
  - Item list (qty, price)
  - Coupon code
  - Subtotal
  - Proceed to checkout

### Checkout (`/checkout`)
- **Step 1**: Thông tin giao hàng
  - Guest checkout hoặc login
  - Địa chỉ mới hoặc chọn từ saved
- **Step 2**: Phương thức thanh toán
  - COD (Cash on delivery)
  - Bank transfer
  - VNPay (nếu có)
- **Step 3**: Xác nhận
  - Order summary
  - Đặt hàng

### Account (`/account`)
- **Dashboard**: Thông tin cá nhân
- **Orders**: Lịch sử đơn hàng
  - List với status
  - Click → Chi tiết + tracking
- **Loyalty**: Điểm thưởng, tier
- **Addresses**: Quản lý địa chỉ

### Order Tracking (`/track-order`)
- Nhập order number + phone
- Hiển thị status + shipment tracking

## State Management

### Cart Store
```typescript
// stores/cart.ts
export const useCartStore = defineStore('cart', () => {
  const items = ref<CartItem[]>([])
  const isOpen = ref(false)  // Mini cart drawer
  
  const addItem = (product: Product, variant: Variant, qty: number) => { ... }
  const removeItem = (itemId: string) => { ... }
  const updateQty = (itemId: string, qty: number) => { ... }
  const applyCoupon = (code: string) => { ... }
  
  const subtotal = computed(() => ...)
  const total = computed(() => subtotal.value - discount.value)
  
  return { items, isOpen, addItem, removeItem, updateQty, subtotal, total }
})
```

### Checkout Store
```typescript
// stores/checkout.ts
export const useCheckoutStore = defineStore('checkout', () => {
  const step = ref(1)
  const shippingInfo = ref<ShippingInfo>({})
  const paymentMethod = ref<'cod' | 'bank' | 'vnpay'>('cod')
  const isProcessing = ref(false)
  
  const placeOrder = async () => {
    // Call API POST /orders
    // Redirect to order confirmation
  }
  
  return { step, shippingInfo, paymentMethod, placeOrder }
})
```

## API Integration

```typescript
// composables/usePublicApi.ts
export const usePublicApi = () => {
  // No auth required
  const fetchProducts = (params: ProductFilter) => 
    $fetch('/api/v1/products', { params })
    
  const fetchProduct = (slug: string) => 
    $fetch(`/api/v1/products/${slug}`)
    
  const createOrder = (data: CreateOrderDto) => 
    $fetch('/api/v1/orders', { method: 'POST', body: data })
  
  return { fetchProducts, fetchProduct, createOrder }
}
```

## Design

### Colors
- Background: `white` hoặc `gray-50`
- Primary: Brand color (e.g., `blue-600`)
- Text: `gray-900` (primary), `gray-600` (secondary)

### Layout
- Header: Logo, search, cart icon, account
- Main content
- Footer: Links, contact, social

### Mobile
- Bottom navigation bar
- Swipeable product gallery
- Touch-optimized buttons

## Checkout Flow

```
Cart → Checkout → Shipping → Payment → Confirm → Success
         ↓
    Guest/Login
         ↓
    Add/Select Address
         ↓
    Select Payment
         ↓
    Review & Place Order
         ↓
    Order Confirmation (token-based)
```

## Guest Checkout

- Không yêu cầu login
- Tạo customer record ẩn (guest)
- Có thể convert thành account sau
- Order tracking via public token

## Order Confirmation Page

Public page không cần login:
```
/orders/{token}
```

Hiển thị:
- Order details
- Items
- Shipping status
- Tracking info

## Features

1. **Product Filter**: Category, size, price, sort
2. **Search**: Autocomplete, recent searches
3. **Wishlist**: Save products (nếu login)
4. **Recently Viewed**: Cookie-based
5. **Loyalty**: Tích điểm, redeem
6. **Sale Events**: Flash sales, countdown

## Development

```bash
# Setup
cd home
bun install

# Dev server
bun run dev
# → http://localhost:3001 (hoặc port khác)

# Build
bun run build

# Preview
bun run preview
```

## SEO

- Meta tags cho mỗi page
- Product structured data (JSON-LD)
- Sitemap generation
- Image optimization

## Analytics

- Google Analytics / GTM
- Facebook Pixel
- Hotjar (heatmaps)

## Performance

- Image lazy loading
- Route prefetching
- CDN cho static assets
- API response caching
