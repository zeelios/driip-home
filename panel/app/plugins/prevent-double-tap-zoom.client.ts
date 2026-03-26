/// <reference types="nuxt" />

export default defineNuxtPlugin((): void => {
  // Return if SSR enabled
  if (!import.meta.client) return;
  let lastTouchEndAt: number = 0;

  const handleTouchEnd = (event: TouchEvent): void => {
    const now: number = Date.now();

    if (now - lastTouchEndAt <= 350) {
      event.preventDefault();
    }

    lastTouchEndAt = now;
  };

  document.addEventListener("touchend", handleTouchEnd, { passive: false });
});
