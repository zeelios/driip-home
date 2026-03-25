import { configureApiClient } from "~/composables/useApi";

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig();

  configureApiClient({
    apiBaseUrl: String(config.public.apiUrl || "/api/v1/panel"),
  });
});
