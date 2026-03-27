export interface VietnamProvince {
  code: string;
  name: string;
  districts: string[];
  zipCode?: string;
}

/**
 * All 63 provinces/municipalities of Vietnam.
 * Districts are populated for major cities; other provinces
 * fall back to a free-text input in the form.
 */
export const vietnamProvinces: VietnamProvince[] = [
  { code: "HAN", name: "Hà Nội", districts: [], zipCode: "100000" },
  { code: "HUE", name: "Huế", districts: [], zipCode: "530000" },
  { code: "LCU", name: "Lai Châu", districts: [], zipCode: "390000" },
  { code: "DBN", name: "Điện Biên", districts: [], zipCode: "380000" },
  { code: "SLA", name: "Sơn La", districts: [], zipCode: "360000" },
  { code: "LSN", name: "Lạng Sơn", districts: [], zipCode: "240000" },
  { code: "QNH", name: "Quảng Ninh", districts: [], zipCode: "200000" },
  { code: "THA", name: "Thanh Hóa", districts: [], zipCode: "440000" },
  { code: "NAN", name: "Nghệ An", districts: [], zipCode: "460000" },
  { code: "HTH", name: "Hà Tĩnh", districts: [], zipCode: "480000" },
  { code: "CBG", name: "Cao Bằng", districts: [], zipCode: "270000" },
  { code: "TQG", name: "Tuyên Quang", districts: [], zipCode: "300000" },
  { code: "LCI", name: "Lào Cai", districts: [], zipCode: "330000" },
  { code: "TNN", name: "Thái Nguyên", districts: [], zipCode: "250000" },
  { code: "PTH", name: "Phú Thọ", districts: [], zipCode: "290000" },
  { code: "BNH", name: "Bắc Ninh", districts: [], zipCode: "790000" },
  { code: "HYN", name: "Hưng Yên", districts: [], zipCode: "160000" },
  { code: "HPG", name: "Hải Phòng", districts: [], zipCode: "180000" },
  { code: "NBH", name: "Ninh Bình", districts: [], zipCode: "430000" },
  { code: "QTR", name: "Quảng Trị", districts: [], zipCode: "520000" },
  { code: "DAN", name: "Đà Nẵng", districts: [], zipCode: "550000" },
  { code: "QNI", name: "Quảng Ngãi", districts: [], zipCode: "570000" },
  { code: "GLA", name: "Gia Lai", districts: [], zipCode: "600000" },
  { code: "KHA", name: "Khánh Hòa", districts: [], zipCode: "650000" },
  { code: "LDG", name: "Lâm Đồng", districts: [], zipCode: "670000" },
  { code: "DLK", name: "Đắk Lắk", districts: [], zipCode: "630000" },
  { code: "HCM", name: "TP. Hồ Chí Minh", districts: [], zipCode: "700000" },
  { code: "DNI", name: "Đồng Nai", districts: [], zipCode: "810000" },
  { code: "TNH", name: "Tây Ninh", districts: [], zipCode: "840000" },
  { code: "CTO", name: "Cần Thơ", districts: [], zipCode: "900000" },
  { code: "VLG", name: "Vĩnh Long", districts: [], zipCode: "890000" },
  { code: "DTH", name: "Đồng Tháp", districts: [], zipCode: "870000" },
  { code: "CMU", name: "Cà Mau", districts: [], zipCode: "970000" },
  { code: "AGG", name: "An Giang", districts: [], zipCode: "880000" },
];

const provinceZipMap = new Map<string, string>();
for (const province of vietnamProvinces) {
  if (province.zipCode) {
    provinceZipMap.set(province.name, province.zipCode);
  }
}

export function getProvinceZipCode(name: string): string | undefined {
  return provinceZipMap.get(name);
}
