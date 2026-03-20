<template>
  <Teleport to="body">
    <div class="mc">
      <!-- FAB -->
      <a
        :href="messengerHref"
        :target="isMobile ? '_self' : '_blank'"
        :rel="isMobile ? undefined : 'noopener noreferrer'"
        class="mc-fab"
        aria-label="Chat hỗ trợ qua Messenger"
      >
        <svg
          v-if="isMobile"
          class="mc-icon"
          viewBox="0 0 24 24"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            d="M13.5 22v-7h2.25l.75-2.75H13.5V10c0-.9.3-1.5 1.8-1.5h1.2V6.1c-.2 0-.9-.1-1.7-.1-2.6 0-4.3 1.6-4.3 4.5v1.75H8v2.75h2.5V22h3Z"
          />
        </svg>
        <svg
          v-else
          class="mc-icon"
          viewBox="0 0 24 24"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            d="M12 2C6.477 2 2 6.145 2 11.243c0 2.908 1.438 5.504 3.686 7.207V22l3.368-1.849c.9.249 1.853.385 2.946.385 5.523 0 10-4.145 10-9.293C22 6.145 17.523 2 12 2zm.99 12.513-2.547-2.713-4.972 2.713 5.468-5.802 2.61 2.713 4.909-2.713-5.468 5.802z"
          />
        </svg>
        <!-- Unread dot — shown until first click -->
        <span v-if="showBadge" class="mc-badge" aria-hidden="true" />
      </a>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";

const config = useRuntimeConfig();
const pageId = computed(() => config.public.fbPageId as string | undefined);
const isMobile = ref(false);
const messengerHref = computed(() => {
  if (!pageId.value) return "https://m.me";

  return `https://m.me/${pageId.value}`;
});

const showBadge = ref(true);

function detectMobileDevice(): boolean {
  if (!process.client) return false;

  const ua = navigator.userAgent || navigator.vendor || "";
  return /android|iphone|ipad|ipod|mobile/i.test(ua);
}

onMounted(() => {
  if (!process.client) return;

  isMobile.value = detectMobileDevice();
});
</script>

<style scoped>
/* ── WRAPPER ─────────────────────────────────────────────────────── */
.mc {
  position: fixed;
  right: 20px;
  bottom: calc(env(safe-area-inset-bottom, 0px) + 20px);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
  pointer-events: none; /* children opt back in */
}

/* ── FAB ─────────────────────────────────────────────────────────── */
.mc-fab {
  pointer-events: all;
  position: relative;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #000;
  border: 1.5px solid rgba(255, 255, 255, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5), 0 1px 4px rgba(0, 0, 0, 0.3);
  transition: transform 0.18s ease, box-shadow 0.18s ease,
    border-color 0.18s ease;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}

.mc-fab:hover,
.mc-fab:focus-visible {
  transform: scale(1.08);
  border-color: rgba(255, 255, 255, 0.5);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 2px 8px rgba(0, 0, 0, 0.4);
  outline: none;
}

.mc-fab:active {
  transform: scale(0.95);
}

/* ── ICON ────────────────────────────────────────────────────────── */
.mc-icon {
  width: 26px;
  height: 26px;
  color: #fff;
  flex-shrink: 0;
}

/* ── UNREAD BADGE ────────────────────────────────────────────────── */
.mc-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #fff;
  border: 2px solid #000;
}

/* ── PULSE ───────────────────────────────────────────────────────── */
.mc-fab--pulse::after {
  content: "";
  position: absolute;
  inset: -6px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.35);
  animation: mc-pulse 1.8s ease-out infinite;
}

@keyframes mc-pulse {
  0% {
    transform: scale(1);
    opacity: 0.7;
  }
  100% {
    transform: scale(1.55);
    opacity: 0;
  }
}

/* ── TOOLTIP ─────────────────────────────────────────────────────── */
.mc-tooltip {
  pointer-events: all;
  background: #fff;
  color: #000;
  border-radius: 12px 12px 4px 12px;
  padding: 12px 36px 12px 16px;
  max-width: 200px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25), 0 1px 4px rgba(0, 0, 0, 0.15);
  position: relative;
}

.mc-tooltip-text {
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  line-height: 1.5;
  letter-spacing: 0.01em;
  color: #000;
  white-space: nowrap;
}

.mc-tooltip-close {
  position: absolute;
  top: 8px;
  right: 10px;
  background: transparent;
  border: none;
  cursor: pointer;
  color: rgba(0, 0, 0, 0.4);
  font-size: 11px;
  padding: 2px;
  line-height: 1;
  transition: color 0.15s;
}

.mc-tooltip-close:hover {
  color: #000;
}

/* ── TOOLTIP TRANSITION ──────────────────────────────────────────── */
.mc-tip-enter-active {
  transition: opacity 0.22s ease,
    transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.mc-tip-leave-active {
  transition: opacity 0.15s ease, transform 0.18s ease;
}
.mc-tip-enter-from,
.mc-tip-leave-to {
  opacity: 0;
  transform: translateY(8px) scale(0.95);
}

/* ── DESKTOP ADJUSTMENTS ─────────────────────────────────────────── */
@media (min-width: 640px) {
  .mc {
    right: 28px;
    bottom: calc(env(safe-area-inset-bottom, 0px) + 28px);
  }

  .mc-fab {
    width: 52px;
    height: 52px;
  }

  .mc-icon {
    width: 24px;
    height: 24px;
  }
}
</style>
