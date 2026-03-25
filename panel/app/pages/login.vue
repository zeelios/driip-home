<template>
  <div class="min-h-screen bg-[#0a0a0a] flex items-center justify-center p-6">
    <div class="w-full max-w-sm">
      <!-- Brand -->
      <div class="mb-10">
        <div
          class="w-9 h-9 bg-white rounded-lg flex items-center justify-center mb-8"
        >
          <span class="text-[#0a0a0a] text-sm font-bold tracking-tight">D</span>
        </div>
        <h1
          class="text-[1.75rem] font-semibold tracking-tight text-white leading-none"
        >
          Đăng nhập
        </h1>
        <p class="mt-2 text-sm text-white/50">Chào mừng trở lại.</p>
      </div>

      <!-- Form -->
      <form class="space-y-5" @submit.prevent="submit">
        <div>
          <label
            class="block text-[11px] font-semibold tracking-widest uppercase text-white/50 mb-2"
          >
            Email
          </label>
          <input
            v-model="form.email"
            type="email"
            inputmode="email"
            autocomplete="username"
            placeholder="you@driip.vn"
            required
            class="w-full border border-white/12 bg-white/4 rounded-lg px-4 py-3 text-sm text-white placeholder:text-white/30 outline-none focus:border-white/40 focus:ring-1 focus:ring-white/20 transition-colors"
          />
        </div>

        <div>
          <label
            class="block text-[11px] font-semibold tracking-widest uppercase text-white/50 mb-2"
          >
            Mật khẩu
          </label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            required
            class="w-full border border-white/12 bg-white/4 rounded-lg px-4 py-3 text-sm text-white placeholder:text-white/30 outline-none focus:border-white/40 focus:ring-1 focus:ring-white/20 transition-colors"
          />
        </div>

        <p v-if="auth.error" class="text-sm text-red-400">
          {{ auth.error }}
        </p>

        <button
          type="submit"
          :disabled="auth.loginPending"
          class="w-full bg-white text-[#0a0a0a] rounded-lg py-3 text-sm font-semibold hover:bg-white/90 active:bg-white/80 transition-colors disabled:opacity-40 disabled:cursor-wait mt-2"
        >
          {{ auth.loginPending ? "Đang đăng nhập..." : "Đăng nhập" }}
        </button>
      </form>

      <!-- Footer -->
      <div class="mt-6 text-center">
        <NuxtLink
          to="/forgot-password"
          class="text-sm text-white/50 hover:text-white/80 transition-colors"
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

definePageMeta({ layout: false });

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
