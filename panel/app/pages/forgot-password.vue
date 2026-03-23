<template>
  <div class="min-h-screen bg-neutral-50 flex items-center justify-center p-6">
    <div class="w-full max-w-sm">

      <!-- Brand -->
      <div class="mb-10">
        <div class="w-9 h-9 bg-neutral-900 rounded-lg flex items-center justify-center mb-8">
          <span class="text-white text-sm font-bold tracking-tight">D</span>
        </div>
        <h1 class="text-[1.75rem] font-semibold tracking-tight text-neutral-900 leading-none">
          Quên mật khẩu
        </h1>
        <p class="mt-2 text-sm text-neutral-400">
          Nhập email để nhận link đặt lại mật khẩu.
        </p>
      </div>

      <!-- Sent state -->
      <div v-if="sent" class="rounded-lg bg-neutral-900 px-5 py-4">
        <p class="text-sm font-medium text-white">Đã gửi email.</p>
        <p class="mt-1 text-sm text-neutral-400">Kiểm tra hộp thư đến của bạn.</p>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="submit" class="space-y-5">

        <div>
          <label class="block text-[11px] font-semibold tracking-widest uppercase text-neutral-400 mb-2">
            Email
          </label>
          <input
            v-model="email"
            type="email"
            inputmode="email"
            autocomplete="username"
            placeholder="you@driip.vn"
            required
            class="w-full border border-neutral-200 bg-white rounded-lg px-4 py-3 text-sm text-neutral-900 placeholder:text-neutral-300 outline-none focus:border-neutral-900 transition-colors"
          />
        </div>

        <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

        <button
          type="submit"
          :disabled="pending"
          class="w-full bg-neutral-900 text-white rounded-lg py-3 text-sm font-semibold hover:bg-neutral-800 active:bg-neutral-950 transition-colors disabled:opacity-40 disabled:cursor-wait"
        >
          {{ pending ? "Đang gửi..." : "Gửi link đặt lại" }}
        </button>

      </form>

      <!-- Back -->
      <div class="mt-6 text-center">
        <NuxtLink
          to="/login"
          class="text-sm text-neutral-400 hover:text-neutral-700 transition-colors"
        >
          ← Quay lại đăng nhập
        </NuxtLink>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useAuthStore } from "~/stores/auth";

const auth = useAuthStore();

const email = ref("");
const pending = ref(false);
const sent = ref(false);
const error = ref<string | null>(null);

async function submit(): Promise<void> {
  pending.value = true;
  error.value = null;

  const result = await auth.forgotPassword(email.value);

  pending.value = false;

  if (result.ok) {
    sent.value = true;
  } else {
    error.value = result.error;
  }
}
</script>
