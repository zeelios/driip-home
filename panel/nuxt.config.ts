// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: true },

  css: ["~/assets/main.css"],

  modules: [
    "@nuxt/eslint",
    "@nuxt/fonts",
    "@nuxt/icon",
    "@nuxt/image",
    "@pinia/nuxt",
  ],

  fonts: {
    families: [{ name: "Inter", provider: "google", weights: [300, 400, 500, 600, 700] }],
  },

  runtimeConfig: {
    public: {
      apiUrl: '',
    }
  },

  vite: {
    plugins: [tailwindcss()],
  },

  app: {
    rootId: '__zeelios'
  }
});
