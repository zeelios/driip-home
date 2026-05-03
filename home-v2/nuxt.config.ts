import tailwindcss from "@tailwindcss/vite";
import { defineNuxtConfig } from "nuxt/config";

export default defineNuxtConfig({
  srcDir: "app",
  compatibilityDate: "latest",

  vite: {
    plugins: [tailwindcss()],
  },
  devtools: { enabled: true },
  // ssr: true,

  modules: ["@nuxt/image", "@nuxtjs/i18n", "@pinia/nuxt"],

  css: ["~/assets/css/main.css"],

  // ── @nuxt/image ──────────────────────────────────────────────────
  image: {
    quality: 85,
    format: ["webp", "png"],
    screens: {
      xs: 375,
      sm: 640,
      md: 768,
      lg: 1024,
      xl: 1280,
    },
  },

  // ── @nuxtjs/i18n ─────────────────────────────────────────────────
  i18n: {
    vueI18n: "../i18n.config.ts",
    locales: [
      { code: "vi", language: "vi-VN", name: "Tiếng Việt" },
      { code: "en", language: "en-US", name: "English" },
    ],
    defaultLocale: "vi",
    strategy: "prefix_except_default",
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: "driip_locale",
      redirectOn: "root",
    },
  },

  // ── Head defaults ─────────────────────────────────────────────────
  app: {
    head: {
      htmlAttrs: { lang: "vi" },
      meta: [
        {
          name: "viewport",
          content:
            "width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover",
        },
        {
          name: "theme-color",
          content: "#050505",
          media: "(prefers-color-scheme: dark)",
        },
        {
          name: "theme-color",
          content: "#050505",
          media: "(prefers-color-scheme: light)",
        },
        { name: "author", content: "Zeelios" },
        { name: "robots", content: "index, follow" },
        { property: "og:type", content: "website" },
        { property: "og:site_name", content: "driip-" },
        { property: "og:title", content: "driip- | CK Boxer & Brief" },
        {
          property: "og:description",
          content:
            "driip- SS26 drop — premium essentials, website pricing, and Calvin Klein tech.",
        },
        { property: "og:locale", content: "vi_VN" },
        { property: "og:url", content: "https://driip.com/" },
        {
          property: "og:image",
          content: "https://driip.com/driip-cover.jpg",
        },
        {
          property: "og:image:secure_url",
          content: "https://driip.com/driip-cover.jpg",
        },
        { property: "og:image:type", content: "image/jpeg" },
        { property: "og:image:width", content: "8484" },
        { property: "og:image:height", content: "4512" },
        { property: "og:image:alt", content: "driip- brand cover image" },
        { name: "twitter:card", content: "summary_large_image" },
        { name: "twitter:site", content: "@zeelios" },
        { name: "twitter:creator", content: "@zeelios" },
        { name: "twitter:title", content: "driip- | CK Boxer & Brief" },
        {
          name: "twitter:description",
          content:
            "driip- SS26 drop — premium essentials, website pricing, and Calvin Klein tech.",
        },
        {
          name: "twitter:image",
          content: "https://driip.com/driip-cover.jpg",
        },
        {
          name: "description",
          content:
            "driip- SS26 drop — premium essentials, website pricing, and Calvin Klein tech.",
        },
      ],
      link: [
        { rel: "preconnect", href: "https://fonts.googleapis.com" },
        {
          rel: "preconnect",
          href: "https://fonts.gstatic.com",
          crossorigin: "",
        },
        {
          rel: "stylesheet",
          href: "https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700&family=Be+Vietnam+Pro:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@1,300;1,400&display=swap",
        },
        { rel: "canonical", href: "https://driip.com/" },
      ],
    },
    rootId: "__zeelios",
  },

  // ── Runtime config ────────────────────────────────────────────────
  runtimeConfig: {
    // Server-only
    googleClientEmail: import.meta.env.GOOGLE_CLIENT_EMAIL || "",
    googlePrivateKey: import.meta.env.GOOGLE_PRIVATE_KEY || "",
    googleSheetId: import.meta.env.GOOGLE_SHEET_ID || "",
    googleServiceAccountJson: import.meta.env.GOOGLE_SERVICE_ACCOUNT_JSON || "",
    metaCAPIAccessToken: import.meta.env.META_CAPI_ACCESS_TOKEN || "",
    resendApiKey: import.meta.env.RESEND_API_KEY || "",
    metaTestEventCode:
      import.meta.env.META_TEST_EVENT_CODE ||
      import.meta.env.NUXT_META_TEST_EVENT_CODE ||
      "",
    // Public — populated from NUXT_PUBLIC_*
    public: {
      metaPixelId: "",
      fbPageId: "",
      apiUrl:
        import.meta.env.NUXT_PUBLIC_API_URL || "http://localhost:8000/api/v1",
    },
  },
});
