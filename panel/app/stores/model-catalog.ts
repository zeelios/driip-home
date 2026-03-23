import { defineStore } from "pinia";
import {
  DEFAULT_MODEL_CATALOG,
  MODEL_CATEGORIES,
  MODEL_COLORS,
  type ModelAsset,
  type ModelCatalog,
  type ModelCategory,
  type ModelColor,
} from "~~/types/models";

type LoadState = "idle" | "loading" | "loaded" | "error";

export const useModelCatalogStore = defineStore("model-catalog", {
  state: () => ({
    status: "idle" as LoadState,
    error: null as string | null,
    catalog: structuredClone(DEFAULT_MODEL_CATALOG) as ModelCatalog,
    hydratedFromApi: false,
  }),

  getters: {
    isReady: (state) => state.status === "loaded",

    getAsset:
      (state) =>
      (category: ModelCategory, color: ModelColor): ModelAsset =>
        state.catalog[category][color],

    listByCategory:
      (state) =>
      (category: ModelCategory): ModelAsset[] =>
        MODEL_COLORS.map((color) => state.catalog[category][color]),

    listAll: (state): ModelAsset[] => {
      const items: ModelAsset[] = [];
      for (const category of MODEL_CATEGORIES) {
        for (const color of MODEL_COLORS) {
          items.push(state.catalog[category][color]);
        }
      }
      return items;
    },
  },

  actions: {
    resetToDefault(): void {
      this.catalog = structuredClone(DEFAULT_MODEL_CATALOG);
      this.hydratedFromApi = false;
      this.error = null;
      this.status = "idle";
    },

    async fetchCatalog(force = false): Promise<void> {
      if (!force && (this.status === "loading" || this.status === "loaded")) {
        return;
      }

      this.status = "loading";
      this.error = null;

      try {
        this.catalog = structuredClone(DEFAULT_MODEL_CATALOG);
        this.hydratedFromApi = false;
        this.status = "loaded";
      } catch (error) {
        this.catalog = structuredClone(DEFAULT_MODEL_CATALOG);
        this.hydratedFromApi = false;
        this.status = "error";
        this.error =
          error instanceof Error
            ? error.message
            : "Failed to load model catalog.";
      }
    },
  },
});
