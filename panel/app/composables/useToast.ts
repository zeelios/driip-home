import { ref } from "vue";
import { defineStore } from "pinia";

type ToastVariant = "success" | "error" | "warning" | "info";

interface ToastInput {
  title: string;
  message?: string;
  variant?: ToastVariant;
  timeout?: number;
}

interface ToastEntry extends Required<Pick<ToastInput, "title">> {
  id: string;
  message: string;
  variant: ToastVariant;
  timeout: number;
}

const DEFAULT_TIMEOUT_BY_VARIANT: Record<ToastVariant, number> = {
  success: 4000,
  error: 6000,
  warning: 5000,
  info: 4000,
};

const timers = new Map<string, ReturnType<typeof setTimeout>>();

function createToastId(): string {
  const crypto = globalThis.crypto;
  if (crypto && typeof crypto.randomUUID === "function") {
    return crypto.randomUUID();
  }

  return `toast-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function resolveTimeout(variant: ToastVariant, timeout?: number): number {
  return timeout ?? DEFAULT_TIMEOUT_BY_VARIANT[variant];
}

export const useToastStore = defineStore("toast", () => {
  const toasts = ref<ToastEntry[]>([]);

  function dismiss(id: string): void {
    const timer = timers.get(id);
    if (timer) {
      clearTimeout(timer);
      timers.delete(id);
    }

    toasts.value = toasts.value.filter((toast) => toast.id !== id);
  }

  function scheduleDismiss(id: string, timeout: number): void {
    if (import.meta.server || timeout <= 0) return;

    const timer = setTimeout(() => dismiss(id), timeout);
    timers.set(id, timer);
  }

  function showToast(input: ToastInput): string {
    const variant = input.variant ?? "info";
    const id = createToastId();
    const toast: ToastEntry = {
      id,
      title: input.title,
      message: input.message ?? "",
      variant,
      timeout: resolveTimeout(variant, input.timeout),
    };

    toasts.value = [...toasts.value, toast];
    scheduleDismiss(id, toast.timeout);

    return id;
  }

  function success(title: string, message = ""): string {
    return showToast({ title, message, variant: "success" });
  }

  function error(title: string, message = ""): string {
    return showToast({ title, message, variant: "error" });
  }

  function warning(title: string, message = ""): string {
    return showToast({ title, message, variant: "warning" });
  }

  function info(title: string, message = ""): string {
    return showToast({ title, message, variant: "info" });
  }

  function clear(): void {
    for (const timer of timers.values()) {
      clearTimeout(timer);
    }
    timers.clear();
    toasts.value = [];
  }

  return {
    toasts,
    showToast,
    success,
    error,
    warning,
    info,
    dismiss,
    clear,
  };
});

export function useToast() {
  return useToastStore();
}
