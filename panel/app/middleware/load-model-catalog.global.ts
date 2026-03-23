import { useModelCatalogStore } from "~/stores/model-catalog";

export default defineNuxtRouteMiddleware(async () => {
  if (import.meta.server) return;

  const store = useModelCatalogStore();

  if (store.status === "idle") {
    await store.fetchCatalog();
  }
});
