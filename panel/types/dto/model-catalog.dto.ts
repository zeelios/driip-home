import type { ModelCategory, ModelColor } from "../models";

export interface ModelAssetDto {
  category: ModelCategory;
  color: ModelColor;
  filename: string;
  public_path: string;
}

export interface GetModelCatalogResponseDto {
  items: ModelAssetDto[];
}
