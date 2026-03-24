/**
 * Dev-only tracking event logger.
 *
 * Keeps a singleton in-memory log of every Pixel / CAPI call so the
 * TrackingDebugPanel can display them without polluting production builds.
 * All logic is no-op when import.meta.dev is false.
 */
import { ref } from "vue";

export type EventSource = "pixel" | "capi";

export interface TrackedEvent {
  id: string;
  time: string;
  source: EventSource;
  name: string;
  params?: Record<string, unknown>;
  payload?: Record<string, unknown>;
}

// Module-level singleton so every composable call shares the same list.
const _events = ref<TrackedEvent[]>([]);
const MAX_EVENTS = 40;

export function useTrackingDebug() {
  function log(
    source: EventSource,
    name: string,
    params?: Record<string, unknown>
  ) {
    if (!import.meta.dev) return;

    const payload =
      params && typeof params === "object"
        ? ((params._payload_sent_to_meta ??
            params._payload_sent ??
            params.payload ??
            params.request_payload) as Record<string, unknown> | undefined)
        : undefined;

    const entry: TrackedEvent = {
      id: `${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
      time: new Date().toLocaleTimeString("en", { hour12: false }),
      source,
      name,
      params,
      payload,
    };

    _events.value.unshift(entry);
    if (_events.value.length > MAX_EVENTS) _events.value.length = MAX_EVENTS;

    // Styled console output
    const color = source === "pixel" ? "#1877f2" : "#10b981";
    const badge = `%c ${source.toUpperCase()} %c ${name} `;
    const s1 = `background:${color};color:#fff;font-weight:700;padding:2px 6px;border-radius:3px 0 0 3px`;
    const s2 = `background:#1a1a1a;color:#e5e5e5;font-weight:600;padding:2px 8px;border-radius:0 3px 3px 0`;

    if (params && Object.keys(params).length) {
      console.groupCollapsed(badge, s1, s2);
      console.table(params);
      console.groupEnd();
    } else {
      console.log(badge, s1, s2);
    }
  }

  function clear() {
    _events.value = [];
  }

  return { events: _events, log, clear };
}
