
// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from "@tailwindcss/vite";

interface NodeEnvMap {
  NUXT_API_URL?: string;
  NUXT_PUBLIC_API_URL?: string;
  NUXT_SANCTUM_CSRF_URL?: string;
  NUXT_PUBLIC_SANCTUM_CSRF_URL?: string;
}

function getNodeEnv(): NodeEnvMap {
  const processEnv =
    (
      globalThis as typeof globalThis & {
        process?: { env?: Record<string, string | undefined> };
      }
    ).process?.env ?? {};

  return processEnv;
}

function isAbsoluteUrl(value: string): boolean {
  return /^https?:\/\//i.test(value);
}

function withHttpProtocol(value: string): string {
  if (!value) {
    return value;
  }

  return isAbsoluteUrl(value) ? value : `http://${value}`;
}

function trimTrailingSlash(value: string): string {
  return value.replace(/\/+$/, "");
}

function normalizeUrl(value: string | undefined, fallback: string): string {
  const resolvedValue: string = value?.trim() || fallback;

  return trimTrailingSlash(withHttpProtocol(resolvedValue));
}

function deriveSanctumCsrfUrl(apiUrl: string): string {
  try {
    const parsedUrl = new URL(apiUrl);

    return `${parsedUrl.origin}/sanctum/csrf-cookie`;
  } catch {
    return "http://localhost:8888/sanctum/csrf-cookie";
  }
}

const nodeEnv = getNodeEnv();

const apiUrl: string = normalizeUrl(
  nodeEnv.NUXT_PUBLIC_API_URL ?? nodeEnv.NUXT_API_URL,
  "http://localhost:8888/api/v1",
);

const sanctumCsrfUrl: string = normalizeUrl(
  nodeEnv.NUXT_PUBLIC_SANCTUM_CSRF_URL ?? nodeEnv.NUXT_SANCTUM_CSRF_URL,
  deriveSanctumCsrfUrl(apiUrl),
);

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
      apiUrl,
      sanctumCsrfUrl,
    },
  },

  vite: {
    plugins: [tailwindcss()],
  },

  app: {
    rootId: "__zeelios",
    head: {
      viewport: "width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover",
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
