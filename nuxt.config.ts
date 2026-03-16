import tailwindcss from "@tailwindcss/vite";

export default defineNuxtConfig({
  compatibilityDate: "latest",

  vite: {
    plugins: [tailwindcss()],
  },
  devtools: { enabled: true },

  modules: ["@nuxt/image", "@nuxtjs/i18n"],

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
    locales: [
      { code: "vi", language: "vi-VN", name: "Tiếng Việt", file: "vi.json" },
      { code: "en", language: "en-US", name: "English", file: "en.json" },
    ],
    defaultLocale: "vi",
    strategy: "prefix_except_default",
    langDir: "i18n/locales/",
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
        { property: "og:type", content: "website" },
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
          href: "https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@300;400;500;600&family=Cormorant+Garamond:ital,wght@1,300;1,400&display=swap",
        },
      ],
    },
  },

  // ── Runtime config ────────────────────────────────────────────────
  runtimeConfig: {
    // Server-only — populated from NUXT_GOOGLE_CLIENT_EMAIL, etc.
    googleClientEmail: "",
    googlePrivateKey: "",
    googleSheetId: "",
    metaCAPIAccessToken: "",
    metaTestEventCode: "",
    // Public — populated from NUXT_PUBLIC_META_PIXEL_ID
    public: {
      metaPixelId: "",
    },
  },
});
