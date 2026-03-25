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
          Đặt lại mật khẩu
        </h1>
        <p class="mt-2 text-sm text-white/50">
          Tạo mật khẩu mới cho tài khoản của bạn.
        </p>
      </div>

      <!-- Done state -->
      <template v-if="done">
        <div
          class="rounded-lg bg-white/10 px-5 py-4 mb-6 border border-white/10"
        >
          <p class="text-sm font-medium text-white">
            Mật khẩu đã được cập nhật.
          </p>
          <p class="mt-1 text-sm text-white/50">
            Bạn có thể đăng nhập ngay bây giờ.
          </p>
        </div>
        <NuxtLink
          to="/login"
          class="block w-full text-center bg-white text-[#0a0a0a] rounded-lg py-3 text-sm font-semibold hover:bg-white/90 transition-colors"
        >
          Đăng nhập
        </NuxtLink>
      </template>

      <!-- Form -->
      <form v-else @submit.prevent="submit" class="space-y-5">
        <div>
          <label
            class="block text-[11px] font-semibold tracking-widest uppercase text-white/50 mb-2"
          >
            Mật khẩu mới
          </label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            placeholder="Tối thiểu 8 ký tự"
            required
            class="w-full border border-white/12 bg-white/4 rounded-lg px-4 py-3 text-sm text-white placeholder:text-white/30 outline-none focus:border-white/40 transition-colors"
          />
        </div>

        <div>
          <label
            class="block text-[11px] font-semibold tracking-widest uppercase text-white/50 mb-2"
          >
            Xác nhận mật khẩu
          </label>
          <input
            v-model="form.passwordConfirmation"
            type="password"
            autocomplete="new-password"
            placeholder="••••••••"
            required
            class="w-full border border-white/12 bg-white/4 rounded-lg px-4 py-3 text-sm text-white placeholder:text-white/30 outline-none focus:border-white/40 transition-colors"
          />
        </div>

        <p v-if="error" class="text-sm text-red-400">{{ error }}</p>

        <button
          type="submit"
          :disabled="pending"
          class="w-full bg-white text-[#0a0a0a] rounded-lg py-3 text-sm font-semibold hover:bg-white/90 active:bg-white/80 transition-colors disabled:opacity-40 disabled:cursor-wait"
        >
          {{ pending ? "Đang xử lý..." : "Đặt lại mật khẩu" }}
        </button>
      </form>

      <!-- Back -->
      <div v-if="!done" class="mt-6 text-center">
        <NuxtLink
          to="/login"
          class="text-sm text-white/50 hover:text-white/80 transition-colors"
        >
          ← Quay lại đăng nhập
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "~/stores/auth";

definePageMeta({ layout: false });

const auth = useAuthStore();
const route = useRoute();

const form = reactive({
  password: "",
  passwordConfirmation: "",
});

const pending = ref(false);
const done = ref(false);
const error = ref<string | null>(null);

async function submit(): Promise<void> {
  error.value = null;

  if (form.password !== form.passwordConfirmation) {
    error.value = "Mật khẩu xác nhận không khớp.";
    return;
  }

  const token = typeof route.query.token === "string" ? route.query.token : "";
  const email = typeof route.query.email === "string" ? route.query.email : "";

  if (!token || !email) {
    error.value = "Link đặt lại mật khẩu không hợp lệ.";
    return;
  }

  pending.value = true;

  const result = await auth.resetPassword({
    email,
    token,
    password: form.password,
    password_confirmation: form.passwordConfirmation,
  });

  pending.value = false;

  if (result.ok) {
    done.value = true;
  } else {
    error.value = result.error;
  }
}
</script>
