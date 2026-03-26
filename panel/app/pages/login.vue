<template>
  <div class="flex min-h-screen items-center justify-center bg-[#0a0a0a] p-6">
    <div class="w-full max-w-sm">
      <div class="mb-10">
        <div
          class="mb-8 flex h-9 w-9 items-center justify-center rounded-lg bg-white"
        >
          <span class="text-sm font-bold tracking-tight text-[#0a0a0a]">D</span>
        </div>

        <h1
          class="text-[1.75rem] leading-none font-semibold tracking-tight text-white"
        >
          Đăng nhập
        </h1>

        <p class="mt-2 text-sm text-white/50">Chào mừng trở lại.</p>
      </div>

      <form class="space-y-5" @submit.prevent="submit">
        <ZInput
          v-model="form.email"
          label="Email"
          type="email"
          inputmode="email"
          autocomplete="username"
          placeholder="you@driip.vn"
          required
        />

        <ZInput
          v-model="form.password"
          label="Mật khẩu"
          type="password"
          autocomplete="current-password"
          placeholder="••••••••"
          required
        />

        <p v-if="auth.error" class="text-sm text-red-400">
          {{ auth.error }}
        </p>

        <ZButton
          type="submit"
          size="lg"
          :loading="auth.loginPending"
          :disabled="auth.loginPending"
          class="mt-2 w-full"
        >
          {{ auth.loginPending ? "Đang đăng nhập..." : "Đăng nhập" }}
        </ZButton>
      </form>

      <div class="mt-6 text-center">
        <NuxtLink
          to="/forgot-password"
          class="text-sm text-white/50 transition-colors hover:text-white/80"
        >
          Quên mật khẩu?
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from "~/stores/auth";

interface LoginFormState {
  email: string;
  password: string;
}

definePageMeta({ layout: false });

const auth = useAuthStore();

const form = reactive<LoginFormState>({
  email: "",
  password: "",
});

async function submit(): Promise<void> {
  if (auth.loginPending) {
    return;
  }

  const isLoggedIn: boolean = await auth.login({
    email: form.email,
    password: form.password,
  });

  if (!isLoggedIn) {
    return;
  }

  await navigateTo(auth.completeLoginRedirect());
}
</script>
