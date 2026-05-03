// Unified product catalog
// Replace id values with actual UUIDs from driip-rust DB (SELECT id, name FROM products;)

export interface ProductColor {
  name: string
  label: { vi: string; en: string }
  hex: string
  images: string[]     // paths relative to /public/
  modelImage?: string  // on-model shot
}

export interface ProductLocale {
  name: string
  tagline: string
  description: string
  details: { label: string; value: string }[]
}

export interface ProductPack {
  qty: number
  label: { vi: string; en: string }
  totalCents: number
}

export interface Product {
  id: string
  slug: string
  badge?: string
  priceCents: number
  originalPriceCents: number
  colors: ProductColor[]
  sizes: string[]
  packs?: ProductPack[]
  locale: {
    vi: ProductLocale
    en: ProductLocale
  }
}

// ─────────────────────────────────────────────────────────────────────────────
const PRODUCTS: Product[] = [
  // ── CK Cotton Boxer Brief ────────────────────────────────────────────────
  {
    id: 'REPLACE_WITH_PRODUCT_UUID_1',
    slug: 'ck-cotton-boxer-brief',
    badge: 'Bán chạy',
    priceCents:         37900_00,
    originalPriceCents: 45000_00,
    colors: [
      {
        name: 'Black',
        label: { vi: 'Đen', en: 'Black' },
        hex: '#1a1a1a',
        images: ['/products/Brief/Black.png'],
        modelImage: '/models/brief/Black.png',
      },
      {
        name: 'Gray',
        label: { vi: 'Xám', en: 'Gray' },
        hex: '#6b6b6b',
        images: ['/products/Brief/Gray.png'],
        modelImage: '/models/brief/Gray.png',
      },
      {
        name: 'White',
        label: { vi: 'Trắng', en: 'White' },
        hex: '#f0f0f0',
        images: ['/products/Brief/White.png'],
        modelImage: '/models/brief/White.png',
      },
    ],
    sizes: ['S', 'M', 'L', 'XL', '2XL'],
    locale: {
      vi: {
        name: 'Cotton Stretch Boxer Brief',
        tagline: 'Dáng lưng thấp. Ôm sát. Thoải mái cả ngày.',
        description:
          'Calvin Klein Cotton Stretch Boxer Brief — kiểu dáng boxer brief lưng thấp ôm vừa phải, cạp thun rộng nổi bật chữ Calvin Klein. Chất liệu cotton co giãn 4 chiều, thoáng khí, giữ form tốt sau nhiều lần giặt.',
        details: [
          { label: 'Chất liệu', value: 'Cotton 95%, Elastane 5%' },
          { label: 'Dáng', value: 'Boxer Brief — Low Rise' },
          { label: 'Xuất xứ', value: 'Bangladesh (Calvin Klein Official)' },
          { label: 'SKU', value: 'NB1181' },
        ],
      },
      en: {
        name: 'Cotton Stretch Boxer Brief',
        tagline: 'Low rise. Fitted. All-day comfort.',
        description:
          'Calvin Klein Cotton Stretch Boxer Brief — low-rise boxer brief with a moderate fit and signature wide CK waistband. 4-way stretch cotton keeps you comfortable all day and holds its shape wash after wash.',
        details: [
          { label: 'Material', value: 'Cotton 95%, Elastane 5%' },
          { label: 'Cut', value: 'Boxer Brief — Low Rise' },
          { label: 'Origin', value: 'Bangladesh (Calvin Klein Official)' },
          { label: 'SKU', value: 'NB1181' },
        ],
      },
    },
  },

  // ── CK Cotton Stretch Boxer ──────────────────────────────────────────────
  {
    id: 'REPLACE_WITH_PRODUCT_UUID_2',
    slug: 'ck-cotton-stretch-boxer',
    badge: null,
    priceCents:         35900_00,
    originalPriceCents: 43000_00,
    colors: [
      {
        name: 'Black',
        label: { vi: 'Đen', en: 'Black' },
        hex: '#1a1a1a',
        images: ['/products/Boxer/Black.png'],
        modelImage: '/models/boxer/Black.png',
      },
      {
        name: 'Gray',
        label: { vi: 'Xám', en: 'Gray' },
        hex: '#6b6b6b',
        images: ['/products/Boxer/Gray.png'],
        modelImage: '/models/boxer/Gray.png',
      },
      {
        name: 'White',
        label: { vi: 'Trắng', en: 'White' },
        hex: '#f0f0f0',
        images: ['/products/Boxer/White.png'],
        modelImage: '/models/boxer/White.png',
      },
    ],
    sizes: ['S', 'M', 'L', 'XL', '2XL'],
    packs: [
      { qty: 1, label: { vi: '1 cái',           en: '1 piece'    }, totalCents: 35900_00  },
      { qty: 3, label: { vi: '3 cái — giảm 10%', en: '3 pcs — 10% off' }, totalCents: 96930_00  },
      { qty: 5, label: { vi: '5 cái — giảm 15%', en: '5 pcs — 15% off' }, totalCents: 152575_00 },
    ],
    locale: {
      vi: {
        name: 'Cotton Stretch Boxer',
        tagline: 'Dáng Boxer rộng rãi. Hỗ trợ tối ưu.',
        description:
          'Calvin Klein Cotton Stretch Boxer — ống chân dài hơn, hạn chế xê dịch, thiết kế hỗ trợ theo đường cong cơ thể. Phù hợp cho daily wear hoặc active lifestyle.',
        details: [
          { label: 'Chất liệu', value: 'Cotton 95%, Elastane 5%' },
          { label: 'Dáng', value: 'Boxer — Relaxed Mid Rise' },
          { label: 'Xuất xứ', value: 'Bangladesh (Calvin Klein Official)' },
          { label: 'SKU', value: 'NB1183' },
        ],
      },
      en: {
        name: 'Cotton Stretch Boxer',
        tagline: 'Relaxed fit. Extended coverage. No ride-up.',
        description:
          'Calvin Klein Cotton Stretch Boxer — extended leg coverage, anti-ride-up hem, contoured support. Great for daily wear or active use.',
        details: [
          { label: 'Material', value: 'Cotton 95%, Elastane 5%' },
          { label: 'Cut', value: 'Boxer — Relaxed Mid Rise' },
          { label: 'Origin', value: 'Bangladesh (Calvin Klein Official)' },
          { label: 'SKU', value: 'NB1183' },
        ],
      },
    },
  },

  // ── CK Cotton Low Rise Brief ─────────────────────────────────────────────
  {
    id: 'REPLACE_WITH_PRODUCT_UUID_3',
    slug: 'ck-cotton-low-rise-brief',
    badge: 'Mới',
    priceCents:         33900_00,
    originalPriceCents: 41000_00,
    colors: [
      {
        name: 'Black',
        label: { vi: 'Đen', en: 'Black' },
        hex: '#1a1a1a',
        images: ['/products/Brief/Black.png'],
        modelImage: '/models/brief/Black.png',
      },
      {
        name: 'White',
        label: { vi: 'Trắng', en: 'White' },
        hex: '#f0f0f0',
        images: ['/products/Brief/White.png'],
        modelImage: '/models/brief/White.png',
      },
    ],
    sizes: ['S', 'M', 'L', 'XL'],
    locale: {
      vi: {
        name: 'Cotton Low Rise Brief',
        tagline: 'Thiết kế tối giản. Không dây thừa.',
        description:
          'Calvin Klein Cotton Low Rise Brief — kiểu brief lưng thấp tối giản, ôm gọn, không dây thừa. Phong cách clean với cạp thun CK mỏng đặc trưng.',
        details: [
          { label: 'Chất liệu', value: 'Cotton 95%, Elastane 5%' },
          { label: 'Dáng', value: 'Brief — Ultra Low Rise' },
          { label: 'Xuất xứ', value: 'Bangladesh (Calvin Klein Official)' },
          { label: 'SKU', value: 'NB2220' },
        ],
      },
      en: {
        name: 'Cotton Low Rise Brief',
        tagline: 'Minimal design. Zero bulk.',
        description:
          'Calvin Klein Cotton Low Rise Brief — ultra low-rise brief, minimal cut, zero bulk. Clean style with the signature slim CK waistband.',
        details: [
          { label: 'Material', value: 'Cotton 95%, Elastane 5%' },
          { label: 'Cut', value: 'Brief — Ultra Low Rise' },
          { label: 'Origin', value: 'Bangladesh (Calvin Klein Official)' },
          { label: 'SKU', value: 'NB2220' },
        ],
      },
    },
  },

  // ── CK Cotton Trunk ──────────────────────────────────────────────────────
  {
    id: 'REPLACE_WITH_PRODUCT_UUID_4',
    slug: 'ck-cotton-trunk',
    badge: null,
    priceCents:         36900_00,
    originalPriceCents: 44000_00,
    colors: [
      {
        name: 'Black',
        label: { vi: 'Đen', en: 'Black' },
        hex: '#1a1a1a',
        images: ['/products/Boxer/Black.png'],
        modelImage: '/models/boxer/Black.png',
      },
      {
        name: 'Gray',
        label: { vi: 'Xám', en: 'Gray' },
        hex: '#6b6b6b',
        images: ['/products/Boxer/Gray.png'],
        modelImage: '/models/boxer/Gray.png',
      },
    ],
    sizes: ['S', 'M', 'L', 'XL', '2XL'],
    locale: {
      vi: {
        name: 'Cotton Stretch Trunk',
        tagline: 'Giữa Brief và Boxer. Ôm đúng chỗ.',
        description:
          'Calvin Klein Cotton Stretch Trunk — cut ngắn hơn Boxer nhưng dài hơn Brief, dáng trunk cổ điển ôm vừa. Lý tưởng cho những ai muốn khoảng giữa hoàn hảo.',
        details: [
          { label: 'Chất liệu', value: 'Cotton 95%, Elastane 5%' },
          { label: 'Dáng', value: 'Trunk — Mid Rise' },
          { label: 'Xuất xứ', value: 'Bangladesh (Calvin Klein Official)' },
          { label: 'SKU', value: 'NB1085' },
        ],
      },
      en: {
        name: 'Cotton Stretch Trunk',
        tagline: 'Between brief and boxer. Fits just right.',
        description:
          'Calvin Klein Cotton Stretch Trunk — shorter than a boxer, longer than a brief, classic trunk cut with a mid-rise fit. Ideal for those who want the perfect in-between.',
        details: [
          { label: 'Material', value: 'Cotton 95%, Elastane 5%' },
          { label: 'Cut', value: 'Trunk — Mid Rise' },
          { label: 'Origin', value: 'Bangladesh (Calvin Klein Official)' },
          { label: 'SKU', value: 'NB1085' },
        ],
      },
    },
  },
]

export default PRODUCTS

export function getProduct (slug: string): Product | undefined {
  return PRODUCTS.find(p => p.slug === slug)
}
