export type ModelCategory = "brief" | "boxer";

export type ModelColor = "Black" | "Gray" | "White";

export interface ModelAsset {
  category: ModelCategory;
  color: ModelColor;
  filename: string;
  publicPath: string;
}

export interface ModelCatalog {
  brief: Record<ModelColor, ModelAsset>;
  boxer: Record<ModelColor, ModelAsset>;
}

export const MODEL_CATEGORIES: ModelCategory[] = ["brief", "boxer"];

export const MODEL_COLORS: ModelColor[] = ["Black", "Gray", "White"];

export const DEFAULT_MODEL_CATALOG: ModelCatalog = {
  brief: {
    Black: {
      category: "brief",
      color: "Black",
      filename: "Black.png",
      publicPath: "/models/brief/Black.png",
    },
    Gray: {
      category: "brief",
      color: "Gray",
      filename: "Gray.png",
      publicPath: "/models/brief/Gray.png",
    },
    White: {
      category: "brief",
      color: "White",
      filename: "White.png",
      publicPath: "/models/brief/White.png",
    },
  },
  boxer: {
    Black: {
      category: "boxer",
      color: "Black",
      filename: "Black.png",
      publicPath: "/models/boxer/Black.png",
    },
    Gray: {
      category: "boxer",
      color: "Gray",
      filename: "Gray.png",
      publicPath: "/models/boxer/Gray.png",
    },
    White: {
      category: "boxer",
      color: "White",
      filename: "White.png",
      publicPath: "/models/boxer/White.png",
    },
  },
};
