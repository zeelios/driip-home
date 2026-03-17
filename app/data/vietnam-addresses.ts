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
  { code: 'HAN', name: 'Hà Nội', districts: [] },
  { code: 'HUE', name: 'Huế', districts: [] },
  { code: 'LCU', name: 'Lai Châu', districts: [] },
  { code: 'DBN', name: 'Điện Biên', districts: [] },
  { code: 'SLA', name: 'Sơn La', districts: [] },
  { code: 'LSN', name: 'Lạng Sơn', districts: [] },
  { code: 'QNH', name: 'Quảng Ninh', districts: [] },
  { code: 'THA', name: 'Thanh Hóa', districts: [] },
  { code: 'NAN', name: 'Nghệ An', districts: [] },
  { code: 'HTH', name: 'Hà Tĩnh', districts: [] },
  { code: 'CBG', name: 'Cao Bằng', districts: [] },
  { code: 'TQG', name: 'Tuyên Quang', districts: [] },
  { code: 'LCI', name: 'Lào Cai', districts: [] },
  { code: 'TNN', name: 'Thái Nguyên', districts: [] },
  { code: 'PTH', name: 'Phú Thọ', districts: [] },
  { code: 'BNH', name: 'Bắc Ninh', districts: [] },
  { code: 'HYN', name: 'Hưng Yên', districts: [] },
  { code: 'HPG', name: 'Hải Phòng', districts: [] },
  { code: 'NBH', name: 'Ninh Bình', districts: [] },
  { code: 'QTR', name: 'Quảng Trị', districts: [] },
  { code: 'DAN', name: 'Đà Nẵng', districts: [] },
  { code: 'QNI', name: 'Quảng Ngãi', districts: [] },
  { code: 'GLA', name: 'Gia Lai', districts: [] },
  { code: 'KHA', name: 'Khánh Hòa', districts: [] },
  { code: 'LDG', name: 'Lâm Đồng', districts: [] },
  { code: 'DLK', name: 'Đắk Lắk', districts: [] },
  { code: 'HCM', name: 'TP. Hồ Chí Minh', districts: [] },
  { code: 'DNI', name: 'Đồng Nai', districts: [] },
  { code: 'TNH', name: 'Tây Ninh', districts: [] },
  { code: 'CTO', name: 'Cần Thơ', districts: [] },
  { code: 'VLG', name: 'Vĩnh Long', districts: [] },
  { code: 'DTH', name: 'Đồng Tháp', districts: [] },
  { code: 'CMU', name: 'Cà Mau', districts: [] },
  { code: 'AGG', name: 'An Giang', districts: [] },
]
