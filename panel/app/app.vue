<template>
  <div>
    <div
      v-if="!auth.initialized"
      class="fixed inset-0 z-40 flex items-center justify-center bg-neutral-50"
    >
      <div class="flex flex-col items-center gap-4 text-center">
        <div
          class="flex h-12 w-12 items-center justify-center rounded-xl bg-neutral-900 text-sm font-bold text-white"
        >
          D
        </div>
        <div>
          <p class="text-sm font-semibold text-neutral-900">
            Đang tải phiên làm việc
          </p>
          <p class="mt-1 text-sm text-neutral-500">
            Vui lòng chờ trong giây lát...
          </p>
        </div>
      </div>
    </div>

    <NuxtLayout v-else>
      <NuxtPage />
    </NuxtLayout>
    <ToastHost />
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from "~/stores/auth";

const auth = useAuthStore();
</script>
