import { onBeforeUnmount, reactive, ref } from "vue";

export interface StableImageLoadOptions {
  minDelayMs?: number;
  maxWaitMs?: number;
}

type TimerState = {
  startedAt: number;
  revealTimer: number | null;
  timeoutTimer: number | null;
};

function clearTimer(timer: number | null): void {
  if (timer === null || typeof window === "undefined") return;
  window.clearTimeout(timer);
}

export function useStableImageLoad(options: StableImageLoadOptions = {}) {
  const minDelayMs = options.minDelayMs ?? 350;
  const maxWaitMs = options.maxWaitMs ?? 6000;

  const isLoaded = ref(false);
  let startedAt = 0;
  let revealTimer: number | null = null;
  let timeoutTimer: number | null = null;

  function clearTimers(): void {
    clearTimer(revealTimer);
    clearTimer(timeoutTimer);
    revealTimer = null;
    timeoutTimer = null;
  }

  function finish(): void {
    clearTimers();
    isLoaded.value = true;
  }

  function arm(): void {
    clearTimers();
    isLoaded.value = false;

    if (typeof window === "undefined") return;

    startedAt = window.performance.now();
    timeoutTimer = window.setTimeout(finish, maxWaitMs);
  }

  function settle(): void {
    if (typeof window === "undefined") {
      isLoaded.value = true;
      return;
    }

    const elapsed = window.performance.now() - startedAt;
    const remaining = Math.max(0, minDelayMs - elapsed);

    if (remaining === 0) {
      finish();
      return;
    }

    clearTimer(revealTimer);
    revealTimer = window.setTimeout(finish, remaining);
  }

  onBeforeUnmount(clearTimers);

  return {
    arm,
    isLoaded,
    settle,
  };
}

export function useStableImageLoadMap(options: StableImageLoadOptions = {}) {
  const minDelayMs = options.minDelayMs ?? 350;
  const maxWaitMs = options.maxWaitMs ?? 6000;

  const loadedImages = reactive<Record<string, boolean>>({});
  const timers = new Map<string, TimerState>();

  function clearEntry(src: string): void {
    const entry = timers.get(src);
    if (!entry) return;

    clearTimer(entry.revealTimer);
    clearTimer(entry.timeoutTimer);
    timers.delete(src);
  }

  function finish(src: string): void {
    clearEntry(src);
    loadedImages[src] = true;
  }

  function arm(src: string): void {
    clearEntry(src);
    loadedImages[src] = false;

    if (typeof window === "undefined") return;

    timers.set(src, {
      startedAt: window.performance.now(),
      revealTimer: null,
      timeoutTimer: window.setTimeout(() => finish(src), maxWaitMs),
    });
  }

  function settle(src: string): void {
    if (typeof window === "undefined") {
      loadedImages[src] = true;
      return;
    }

    const entry = timers.get(src);
    if (!entry) {
      loadedImages[src] = true;
      return;
    }

    const elapsed = window.performance.now() - entry.startedAt;
    const remaining = Math.max(0, minDelayMs - elapsed);

    if (remaining === 0) {
      finish(src);
      return;
    }

    clearTimer(entry.revealTimer);
    entry.revealTimer = window.setTimeout(() => finish(src), remaining);
  }

  function isLoaded(src: string): boolean {
    return loadedImages[src] === true;
  }

  onBeforeUnmount(() => {
    for (const src of timers.keys()) clearEntry(src);
  });

  return {
    arm,
    isLoaded,
    loadedImages,
    settle,
  };
}
