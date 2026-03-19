<template>
  <div v-if="isDev" class="dbg-wrap">
    <!-- Toggle button -->
    <button class="dbg-toggle" @click="open = !open">
      <span class="dbg-icon">◉</span>
      <span class="dbg-label">EVENTS</span>
      <span class="dbg-count">{{ events.length }}</span>
    </button>

    <!-- Panel -->
    <Transition name="dbg-slide">
      <div v-if="open" class="dbg-panel">
        <div class="dbg-panel-head">
          <span class="dbg-panel-title">Tracking Events</span>
          <button class="dbg-clear" @click="clear">CLEAR</button>
          <button class="dbg-close" @click="open = false">✕</button>
        </div>

        <div class="dbg-scroll">
          <div v-if="!events.length" class="dbg-empty">
            No events fired yet.
          </div>

          <div v-for="e in events" :key="e.id" class="dbg-row">
            <span class="dbg-source" :class="e.source">{{
              e.source === "pixel" ? "PX" : "CAPI"
            }}</span>
            <span class="dbg-name">{{ e.name }}</span>
            <span class="dbg-time">{{ e.time }}</span>
            <button
              v-if="e.params && Object.keys(e.params).length"
              class="dbg-expand"
              @click="toggle(e.id)"
            >
              {{ expanded.has(e.id) ? "▲" : "▼" }}
            </button>
            <div v-if="getRequestMeta(e.params)" class="dbg-request-meta">
              <span v-if="getRequestMeta(e.params)?.clientIp"
                >IP: {{ getRequestMeta(e.params)?.clientIp }}</span
              >
              <span
                v-if="
                  getRequestMeta(e.params)?.clientIp &&
                  getRequestMeta(e.params)?.userAgent
                "
                >·</span
              >
              <span v-if="getRequestMeta(e.params)?.userAgent" class="dbg-ua">
                UA: {{ getRequestMeta(e.params)?.userAgent }}
              </span>
            </div>
            <pre v-if="expanded.has(e.id)" class="dbg-params">{{
              JSON.stringify(e.params, null, 2)
            }}</pre>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { useTrackingDebug } from "~/composables/useTrackingDebug";

const isDev = import.meta.dev;
const { events, clear } = useTrackingDebug();
const open = ref(false);
const expanded = reactive(new Set<string>());

type RequestMeta = {
  clientIp?: string;
  userAgent?: string;
};

function toggle(id: string) {
  if (expanded.has(id)) expanded.delete(id);
  else expanded.add(id);
}

function getRequestMeta(params?: Record<string, unknown>): RequestMeta | null {
  const meta = params?._request;

  if (!meta || typeof meta !== "object") return null;

  const request = meta as RequestMeta;
  if (!request.clientIp && !request.userAgent) return null;

  return request;
}
</script>

<style scoped>
.dbg-wrap {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
  font-family: "SF Mono", "Fira Code", monospace;
  font-size: 11px;
}

/* ── Toggle ────────────────────────────────────── */
.dbg-toggle {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #1a1a1a;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #e5e5e5;
  padding: 7px 12px;
  cursor: pointer;
  border-radius: 6px;
  letter-spacing: 0.08em;
  transition: border-color 0.15s;
  margin-left: auto;
}
.dbg-toggle:hover {
  border-color: rgba(255, 255, 255, 0.4);
}
.dbg-icon {
  color: #10b981;
  font-size: 10px;
}
.dbg-label {
  font-weight: 700;
  letter-spacing: 0.15em;
}
.dbg-count {
  background: #1877f2;
  color: #fff;
  font-weight: 700;
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
}

/* ── Panel ─────────────────────────────────────── */
.dbg-panel {
  position: absolute;
  bottom: calc(100% + 8px);
  right: 0;
  width: 360px;
  max-height: 480px;
  background: #0d0d0d;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.7);
}

.dbg-panel-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  flex-shrink: 0;
}
.dbg-panel-title {
  font-weight: 700;
  letter-spacing: 0.15em;
  color: #e5e5e5;
  flex: 1;
}
.dbg-clear {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #666;
  font-size: 9px;
  letter-spacing: 0.15em;
  padding: 2px 8px;
  cursor: pointer;
  border-radius: 3px;
  transition: color 0.15s, border-color 0.15s;
}
.dbg-clear:hover {
  color: #e5e5e5;
  border-color: rgba(255, 255, 255, 0.4);
}
.dbg-close {
  background: transparent;
  border: none;
  color: #555;
  font-size: 13px;
  cursor: pointer;
  padding: 0 4px;
  transition: color 0.15s;
}
.dbg-close:hover {
  color: #e5e5e5;
}

.dbg-scroll {
  overflow-y: auto;
  flex: 1;
  scrollbar-width: thin;
  scrollbar-color: #333 transparent;
}

/* ── Row ───────────────────────────────────────── */
.dbg-row {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 6px;
  padding: 8px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  transition: background 0.1s;
}
.dbg-row:hover {
  background: rgba(255, 255, 255, 0.03);
}

.dbg-source {
  font-weight: 700;
  font-size: 9px;
  letter-spacing: 0.12em;
  padding: 2px 5px;
  border-radius: 3px;
  flex-shrink: 0;
}
.dbg-source.pixel {
  background: #1877f2;
  color: #fff;
}
.dbg-source.capi {
  background: #10b981;
  color: #fff;
}

.dbg-name {
  flex: 1;
  color: #e5e5e5;
  font-weight: 600;
  letter-spacing: 0.05em;
}
.dbg-time {
  color: #555;
  flex-shrink: 0;
  font-size: 10px;
}

.dbg-request-meta {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  color: #9ca3af;
  font-size: 10px;
  letter-spacing: 0.02em;
}

.dbg-ua {
  word-break: break-word;
}
.dbg-expand {
  background: transparent;
  border: none;
  color: #555;
  cursor: pointer;
  font-size: 9px;
  padding: 0 2px;
  transition: color 0.15s;
}
.dbg-expand:hover {
  color: #e5e5e5;
}

.dbg-params {
  width: 100%;
  margin-top: 4px;
  background: #111;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 4px;
  padding: 8px 10px;
  color: #aaa;
  font-size: 10px;
  line-height: 1.6;
  overflow-x: auto;
  white-space: pre;
}

.dbg-empty {
  padding: 24px;
  text-align: center;
  color: #444;
  letter-spacing: 0.1em;
}

/* ── Transition ────────────────────────────────── */
.dbg-slide-enter-active,
.dbg-slide-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}
.dbg-slide-enter-from,
.dbg-slide-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
