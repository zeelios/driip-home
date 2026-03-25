// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";

const nodeEnv =
  (
    globalThis as typeof globalThis & {
      process?: { env?: Record<string, string | undefined> };
    }
  ).process?.env ?? {};

export default defineNuxtConfig({
  compatibilityDate: "2025-07-15",
  devtools: { enabled: false },
  ssr: false,
  css: ["~/assets/main.css"],

  modules: [
    "@nuxt/eslint",
    "@nuxt/fonts",
    "@nuxt/icon",
    "@nuxt/image",
    "@pinia/nuxt",
  ],

  fonts: {
    families: [
      { name: "Inter", provider: "google", weights: [300, 400, 500, 600, 700] },
    ],
  },

  runtimeConfig: {
    public: {
      apiUrl: nodeEnv.NUXT_PUBLIC_API_URL ?? "http://localhost/api/v1/panel",
    },
  },

  vite: {
    plugins: [tailwindcss()],
  },

  app: {
    rootId: "__zeelios",
    head: {
      viewport: "width=device-width, initial-scale=1, viewport-fit=cover",
      meta: [
        { name: "color-scheme", content: "dark light" },
        {
          name: "theme-color",
          content: "#000000",
          media: "(prefers-color-scheme: light)",
        },
        {
          name: "theme-color",
          content: "#000000",
          media: "(prefers-color-scheme: dark)",
        },
        { name: "theme-color", content: "#000000" },
        { name: "apple-mobile-web-app-capable", content: "yes" },
        {
          name: "apple-mobile-web-app-status-bar-style",
          content: "black-translucent",
        },
      ],
    },
  },
});
