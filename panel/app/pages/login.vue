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
          :disabled="auth.loginPending || auth.loginAttemptLocked"
          class="w-full mt-2"
        >
          {{
            auth.loginPending
              ? "Đang đăng nhập..."
              : auth.loginAttemptLocked
              ? "Đã khóa thử lại"
              : "Đăng nhập"
          }}
        </ZButton>
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
