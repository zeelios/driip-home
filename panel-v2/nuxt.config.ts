import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  srcDir: 'app',
  compatibilityDate: 'latest',
  ssr: true,

  vite: {
    plugins: [tailwindcss()],
  },

  modules: ['@pinia/nuxt'],
  css: ['~/assets/css/main.css'],

  app: {
    head: {
      htmlAttrs: { lang: 'vi' },
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1, maximum-scale=1' },
        { name: 'theme-color', content: '#050505' },
        { name: 'robots', content: 'noindex, nofollow' },
      ],
      title: 'driip- Panel',
    },
    rootId: '__panel',
  },

  runtimeConfig: {
    public: {
      apiUrl:''
    },
  },
})
