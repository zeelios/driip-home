import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  compatibilityDate: "latest",

  vite: {
    plugins: [tailwindcss()],
  },
  devtools: { enabled: true },

  modules: ["@nuxt/image", "@nuxtjs/i18n", "@pinia/nuxt", "@vercel/analytics"],

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
    vueI18n: "./i18n.config.ts",
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
        { name: "theme-color", content: "#000000" },
        { name: "author", content: "Zeelios" },
        { name: "robots", content: "index, follow" },
        { property: "og:type", content: "website" },
        { property: "og:site_name", content: "driip-" },
        { property: "og:title", content: "driip- | CK Boxer & Brief" },
        {
          property: "og:description",
          content:
            "driip- SS26 drop. Limited CK Boxer & Brief packs with 20% off early access.",
        },
        { property: "og:locale", content: "vi_VN" },
        {
          property: "og:image",
          content: "https://driip.com/products/Brief/Black.png",
        },
        { name: "twitter:card", content: "summary_large_image" },
        { name: "twitter:creator", content: "@zeelios" },
        {
          name: "description",
          content:
            "driip- SS26 drop — premium essentials, early access notification, and Calvin Klein tech. Zeelios.",
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
        { rel: "preload", as: "image", href: "/products/Brief/Black.png" },
        { rel: "preload", as: "image", href: "/products/Brief/Gray.png" },
        { rel: "canonical", href: "https://driip.com/" },
      ],
    },
  },

  // ── Runtime config ────────────────────────────────────────────────
  runtimeConfig: {
    // Server-only — explicitly map from process.env since they lack NUXT_ prefix
    googleClientEmail: process.env.GOOGLE_CLIENT_EMAIL || "",
    googlePrivateKey: process.env.GOOGLE_PRIVATE_KEY || "",
    googleSheetId: process.env.GOOGLE_SHEET_ID || "",
    googleServiceAccountJson: process.env.GOOGLE_SERVICE_ACCOUNT_JSON || "",
    metaCAPIAccessToken: process.env.META_CAPI_ACCESS_TOKEN || "",
    metaTestEventCode: process.env.META_TEST_EVENT_CODE || "",
    // Public — populated from NUXT_PUBLIC_META_PIXEL_ID
    public: {
      metaPixelId: "",
    },
  },
});
