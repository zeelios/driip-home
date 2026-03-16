export default defineNuxtConfig({
  modules: [
    'my-module',
    '@nuxt/image',
    '@nuxtjs/i18n',
  ],

  devtools: { enabled: true },
  compatibilityDate: 'latest',
  myModule: {},

  css: ['~/assets/css/main.css'],

  // ── @nuxt/image ─────────────────────────────────────────────────
  image: {
    quality: 85,
    format: ['webp', 'png'],
    screens: {
      xs: 375,
      sm: 640,
      md: 768,
      lg: 1024,
      xl: 1280,
      '2xl': 1536,
    },
  },

  // ── @nuxtjs/i18n ─────────────────────────────────────────────────
  i18n: {
    locales: [
      { code: 'vi', language: 'vi-VN', name: 'Tiếng Việt', file: 'vi.json' },
      { code: 'en', language: 'en-US', name: 'English',    file: 'en.json' },
    ],
    defaultLocale: 'vi',
    strategy: 'prefix_except_default',
    langDir: 'locales/',
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'driip_locale',
      redirectOn: 'root',
    },
  },

  app: {
    head: {
      htmlAttrs: { lang: 'vi' },
      meta: [
        { name: 'theme-color', content: '#000000' },
        { property: 'og:type', content: 'website' },
      ],
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@300;400;500;600&family=Cormorant+Garamond:ital,wght@1,300;1,400&display=swap',
        },
      ],
    },
  },

  runtimeConfig: {
    // ── Server-only ──────────────────────────────────────────────
    googleClientEmail:   process.env.GOOGLE_CLIENT_EMAIL,
    googlePrivateKey:    process.env.GOOGLE_PRIVATE_KEY,
    googleSheetId:       process.env.GOOGLE_SHEET_ID,
    metaCAPIAccessToken: process.env.META_CAPI_ACCESS_TOKEN,
    metaTestEventCode:   process.env.META_TEST_EVENT_CODE,
    // ── Public (browser-safe) ────────────────────────────────────
    public: {
      metaPixelId: process.env.NUXT_PUBLIC_META_PIXEL_ID,
    },
  },
})
