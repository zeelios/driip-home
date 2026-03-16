export interface VietnamProvince {
  code: string
  name: string
  districts: string[]
}

/**
 * All 63 provinces/municipalities of Vietnam.
 * Districts are populated for major cities; other provinces
 * fall back to a free-text input in the form.
 */
export const vietnamProvinces: VietnamProvince[] = [
  {
    code: 'HCM',
    name: 'TP. Hồ Chí Minh',
    districts: [
      'Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6',
      'Quận 7', 'Quận 8', 'Quận 10', 'Quận 11', 'Quận 12',
      'TP. Thủ Đức',
      'Quận Bình Tân', 'Quận Bình Thạnh', 'Quận Gò Vấp',
      'Quận Phú Nhuận', 'Quận Tân Bình', 'Quận Tân Phú',
      'Huyện Bình Chánh', 'Huyện Cần Giờ', 'Huyện Củ Chi',
      'Huyện Hóc Môn', 'Huyện Nhà Bè',
    ],
  },
  {
    code: 'HAN',
    name: 'Hà Nội',
    districts: [
      'Quận Ba Đình', 'Quận Bắc Từ Liêm', 'Quận Cầu Giấy',
      'Quận Đống Đa', 'Quận Hà Đông', 'Quận Hai Bà Trưng',
      'Quận Hoàn Kiếm', 'Quận Hoàng Mai', 'Quận Long Biên',
      'Quận Nam Từ Liêm', 'Quận Tây Hồ', 'Quận Thanh Xuân',
      'Huyện Ba Vì', 'Huyện Chương Mỹ', 'Huyện Đan Phượng',
      'Huyện Đông Anh', 'Huyện Gia Lâm', 'Huyện Hoài Đức',
      'Huyện Mê Linh', 'Huyện Mỹ Đức', 'Huyện Phú Xuyên',
      'Huyện Phúc Thọ', 'Huyện Quốc Oai', 'Huyện Sóc Sơn',
      'Huyện Thạch Thất', 'Huyện Thanh Oai', 'Huyện Thanh Trì',
      'Huyện Thường Tín', 'Huyện Ứng Hòa', 'TX. Sơn Tây',
    ],
  },
  {
    code: 'DAN',
    name: 'Đà Nẵng',
    districts: [
      'Quận Cẩm Lệ', 'Quận Hải Châu', 'Quận Liên Chiểu',
      'Quận Ngũ Hành Sơn', 'Quận Sơn Trà', 'Quận Thanh Khê',
      'Huyện Hòa Vang',
    ],
  },
  {
    code: 'CTO',
    name: 'Cần Thơ',
    districts: [
      'Quận Bình Thủy', 'Quận Cái Răng', 'Quận Ninh Kiều',
      'Quận Ô Môn', 'Quận Thốt Nốt',
      'Huyện Cờ Đỏ', 'Huyện Phong Điền', 'Huyện Thới Lai', 'Huyện Vĩnh Thạnh',
    ],
  },
  {
    code: 'HPG',
    name: 'Hải Phòng',
    districts: [
      'Quận Dương Kinh', 'Quận Đồ Sơn', 'Quận Hải An',
      'Quận Hồng Bàng', 'Quận Kiến An', 'Quận Lê Chân', 'Quận Ngô Quyền',
      'Huyện An Dương', 'Huyện An Lão', 'Huyện Cát Hải',
      'Huyện Kiến Thụy', 'Huyện Thủy Nguyên', 'Huyện Tiên Lãng', 'Huyện Vĩnh Bảo',
    ],
  },
  {
    code: 'BDG',
    name: 'Bình Dương',
    districts: [
      'TP. Dĩ An', 'TP. Thủ Dầu Một', 'TP. Thuận An',
      'TX. Bến Cát', 'TX. Tân Uyên',
      'Huyện Bắc Tân Uyên', 'Huyện Bàu Bàng', 'Huyện Dầu Tiếng', 'Huyện Phú Giáo',
    ],
  },
  {
    code: 'DNI',
    name: 'Đồng Nai',
    districts: [
      'TP. Biên Hòa', 'TP. Long Khánh',
      'Huyện Cẩm Mỹ', 'Huyện Định Quán', 'Huyện Long Thành',
      'Huyện Nhơn Trạch', 'Huyện Tân Phú', 'Huyện Thống Nhất',
      'Huyện Trảng Bom', 'Huyện Vĩnh Cửu', 'Huyện Xuân Lộc',
    ],
  },
  {
    code: 'KHA',
    name: 'Khánh Hòa',
    districts: [
      'TP. Nha Trang', 'TX. Cam Ranh', 'TX. Ninh Hòa',
      'Huyện Cam Lâm', 'Huyện Diên Khánh', 'Huyện Khánh Sơn',
      'Huyện Khánh Vĩnh', 'Huyện Trường Sa (Vân Đồn)',
    ],
  },
  // ── Remaining 55 provinces (text-input fallback for district) ──
  { code: 'AGG', name: 'An Giang', districts: [] },
  { code: 'BRV', name: 'Bà Rịa - Vũng Tàu', districts: [] },
  { code: 'BCL', name: 'Bạc Liêu', districts: [] },
  { code: 'BGG', name: 'Bắc Giang', districts: [] },
  { code: 'BKN', name: 'Bắc Kạn', districts: [] },
  { code: 'BNH', name: 'Bắc Ninh', districts: [] },
  { code: 'BTR', name: 'Bến Tre', districts: [] },
  { code: 'BDH', name: 'Bình Định', districts: [] },
  { code: 'BPC', name: 'Bình Phước', districts: [] },
  { code: 'BTN', name: 'Bình Thuận', districts: [] },
  { code: 'CMU', name: 'Cà Mau', districts: [] },
  { code: 'CBG', name: 'Cao Bằng', districts: [] },
  { code: 'DLK', name: 'Đắk Lắk', districts: [] },
  { code: 'DKN', name: 'Đắk Nông', districts: [] },
  { code: 'DBN', name: 'Điện Biên', districts: [] },
  { code: 'DTH', name: 'Đồng Tháp', districts: [] },
  { code: 'GLA', name: 'Gia Lai', districts: [] },
  { code: 'HGG', name: 'Hà Giang', districts: [] },
  { code: 'HNM', name: 'Hà Nam', districts: [] },
  { code: 'HTH', name: 'Hà Tĩnh', districts: [] },
  { code: 'HDG', name: 'Hải Dương', districts: [] },
  { code: 'HGN', name: 'Hậu Giang', districts: [] },
  { code: 'HBH', name: 'Hòa Bình', districts: [] },
  { code: 'HYN', name: 'Hưng Yên', districts: [] },
  { code: 'KGG', name: 'Kiên Giang', districts: [] },
  { code: 'KTM', name: 'Kon Tum', districts: [] },
  { code: 'LCU', name: 'Lai Châu', districts: [] },
  { code: 'LDG', name: 'Lâm Đồng', districts: [] },
  { code: 'LSN', name: 'Lạng Sơn', districts: [] },
  { code: 'LCI', name: 'Lào Cai', districts: [] },
  { code: 'LAN', name: 'Long An', districts: [] },
  { code: 'NDH', name: 'Nam Định', districts: [] },
  { code: 'NAN', name: 'Nghệ An', districts: [] },
  { code: 'NBH', name: 'Ninh Bình', districts: [] },
  { code: 'NTN', name: 'Ninh Thuận', districts: [] },
  { code: 'PTH', name: 'Phú Thọ', districts: [] },
  { code: 'PYN', name: 'Phú Yên', districts: [] },
  { code: 'QBH', name: 'Quảng Bình', districts: [] },
  { code: 'QNM', name: 'Quảng Nam', districts: [] },
  { code: 'QNI', name: 'Quảng Ngãi', districts: [] },
  { code: 'QNH', name: 'Quảng Ninh', districts: [] },
  { code: 'QTR', name: 'Quảng Trị', districts: [] },
  { code: 'STG', name: 'Sóc Trăng', districts: [] },
  { code: 'SLA', name: 'Sơn La', districts: [] },
  { code: 'TNH', name: 'Tây Ninh', districts: [] },
  { code: 'TBH', name: 'Thái Bình', districts: [] },
  { code: 'TNN', name: 'Thái Nguyên', districts: [] },
  { code: 'THA', name: 'Thanh Hóa', districts: [] },
  { code: 'TTH', name: 'Thừa Thiên Huế', districts: [] },
  { code: 'TGG', name: 'Tiền Giang', districts: [] },
  { code: 'TVH', name: 'Trà Vinh', districts: [] },
  { code: 'TQG', name: 'Tuyên Quang', districts: [] },
  { code: 'VLG', name: 'Vĩnh Long', districts: [] },
  { code: 'VPC', name: 'Vĩnh Phúc', districts: [] },
  { code: 'YBI', name: 'Yên Bái', districts: [] },
]
