<template>
  <div class="min-h-screen bg-neutral-50 flex items-center justify-center p-6">
    <div class="w-full max-w-sm">

      <!-- Brand -->
      <div class="mb-10">
        <div class="w-9 h-9 bg-neutral-900 rounded-lg flex items-center justify-center mb-8">
          <span class="text-white text-sm font-bold tracking-tight">D</span>
        </div>
        <h1 class="text-[1.75rem] font-semibold tracking-tight text-neutral-900 leading-none">
          Đăng nhập
        </h1>
        <p class="mt-2 text-sm text-neutral-400">
          Chào mừng trở lại.
        </p>
      </div>

      <!-- Form -->
      <form class="space-y-5" @submit.prevent="submit">

        <div>
          <label class="block text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-2">
            Email
          </label>
          <input
            v-model="form.email"
            type="email"
            inputmode="email"
            autocomplete="username"
            placeholder="you@driip.vn"
            required
            class="w-full border border-neutral-200 bg-white rounded-lg px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-300 outline-none focus:ring-neutral-500 focus:ring-1 transition-colors"
          />
        </div>

        <div>
          <label class="block text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-2">
            Mật khẩu
          </label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            required
            class="w-full border border-neutral-200 bg-white rounded-lg px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-300 outline-none  transition-colors
            focus:ring-1 focus:ring-neutral-500"
          />
        </div>

        <p v-if="auth.error" class="text-sm text-red-500">
          {{ auth.error }}
        </p>

        <button
          type="submit"
          :disabled="auth.loginPending"
          class="w-full bg-neutral-900 text-white rounded-lg py-3 text-sm font-semibold hover:bg-neutral-800 active:bg-neutral-950 transition-colors disabled:opacity-40 disabled:cursor-wait mt-2"
        >
          {{ auth.loginPending ? "Đang đăng nhập..." : "Đăng nhập" }}
        </button>

      </form>

      <!-- Footer -->
      <div class="mt-6 text-center">
        <NuxtLink
          to="/forgot-password"
          class="text-sm text-neutral-400 hover:text-neutral-700 transition-colors"
        >
          Quên mật khẩu?
        </NuxtLink>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive } from "vue";
import { useAuthStore } from "~/stores/auth";

const auth = useAuthStore();

const form = reactive({
  email: "",
  password: "",
});

async function submit(): Promise<void> {
  const ok = await auth.login({
    email: form.email,
    password: form.password,
  });

  if (!ok) return;
  await navigateTo(auth.completeLoginRedirect());
}
</script>
