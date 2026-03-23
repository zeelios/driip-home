<template>
  <Teleport to="body">
    <div
      aria-live="polite"
      aria-relevant="additions removals"
      class="pointer-events-none fixed inset-x-0 top-0 z-50 flex justify-center px-4 py-4 sm:justify-end sm:px-6"
    >
      <TransitionGroup
        tag="div"
        name="panel-toast"
        class="flex w-full max-w-sm flex-col gap-3"
      >
        <article
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto overflow-hidden rounded-2xl border bg-white/95 shadow-[0_18px_50px_rgba(0,0,0,0.12)] backdrop-blur"
          :class="toastClasses(toast.variant)"
        >
          <div class="flex items-start gap-3 px-4 py-4">
            <div
              class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
              :class="badgeClasses(toast.variant)"
              aria-hidden="true"
            >
              {{ variantLabel(toast.variant) }}
            </div>

            <div class="min-w-0 flex-1">
              <h2 class="text-sm font-semibold text-neutral-900">
                {{ toast.title }}
              </h2>
              <p
                v-if="toast.message"
                class="mt-1 text-sm leading-5 text-neutral-500"
              >
                {{ toast.message }}
              </p>
            </div>

            <button
              type="button"
              class="mt-0.5 rounded-full p-1 text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700"
              @click="dismiss(toast.id)"
              aria-label="Dismiss notification"
            >
              <span aria-hidden="true">×</span>
            </button>
          </div>
        </article>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { useToast } from "~/composables/useToast";

const toast = useToast();
const { toasts } = storeToRefs(toast);
const { dismiss } = toast;

function variantLabel(
  variant: "success" | "error" | "warning" | "info"
): string {
  switch (variant) {
    case "success":
      return "✓";
    case "error":
      return "!";
    case "warning":
      return "!";
    case "info":
    default:
      return "i";
  }
}

function toastClasses(
  variant: "success" | "error" | "warning" | "info"
): string {
  switch (variant) {
    case "success":
      return "border-emerald-200";
    case "error":
      return "border-rose-200";
    case "warning":
      return "border-amber-200";
    case "info":
    default:
      return "border-neutral-200";
  }
}

function badgeClasses(
  variant: "success" | "error" | "warning" | "info"
): string {
  switch (variant) {
    case "success":
      return "bg-emerald-100 text-emerald-700";
    case "error":
      return "bg-rose-100 text-rose-700";
    case "warning":
      return "bg-amber-100 text-amber-700";
    case "info":
    default:
      return "bg-neutral-100 text-neutral-600";
  }
}
</script>

<style scoped>
.panel-toast-enter-active,
.panel-toast-leave-active {
  transition: opacity 180ms ease, transform 180ms ease;
}

.panel-toast-enter-from,
.panel-toast-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.98);
}
</style>
