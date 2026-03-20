<template>
  <div
    ref="rootRef"
    class="z-select"
    :class="{ 'is-open': isOpen, disabled, embedded }"
  >
    <label v-if="label && !embedded" class="z-select-label">{{ label }}</label>

    <button
      type="button"
      class="z-select-trigger"
      :class="{ 'has-value': Boolean(selectedOption) }"
      :disabled="disabled"
      :aria-expanded="isOpen"
      :aria-label="label || placeholder"
      @click="toggleDropdown"
    >
      <span class="z-select-value">{{ selectedText }}</span>
      <span class="z-select-arrow" aria-hidden="true">⌄</span>
    </button>

    <!--
      Teleported to <body> to escape any parent transform / overflow / stacking
      context that would break position:fixed on iOS Safari.
    -->
    <Teleport to="body">
      <Transition :name="isMobile ? 'zs-sheet' : 'zs-drop'">
        <div
          v-if="isOpen"
          class="zs-overlay"
          :class="{
            'zs-overlay--mobile': isMobile,
            'zs-overlay--desktop': !isMobile,
          }"
          @mousedown.self="closeDropdown"
          @touchstart.self="closeDropdown"
        >
          <!-- Mobile backdrop -->
          <div v-if="isMobile" class="zs-backdrop" @click="closeDropdown" />

          <!-- Panel -->
          <div
            class="zs-panel"
            ref="panelRef"
            :class="{
              'zs-panel--mobile': isMobile,
              'zs-panel--desktop': !isMobile,
            }"
            :style="!isMobile ? desktopPanelStyle : undefined"
            role="dialog"
            :aria-label="label || placeholder"
          >
            <!-- Mobile handle bar -->
            <div v-if="isMobile" class="zs-bar">
              <div class="zs-grabber" aria-hidden="true" />
              <div class="zs-bar-row">
                <span class="zs-bar-title">{{ label || placeholder }}</span>
                <button type="button" class="zs-done" @click="closeDropdown">
                  Xong
                </button>
              </div>
            </div>

            <!-- Search -->
            <input
              ref="searchRef"
              v-model="query"
              class="zs-search"
              type="text"
              :placeholder="searchPlaceholder || placeholder"
              autocomplete="off"
              autocorrect="off"
              spellcheck="false"
              @keydown.esc.prevent="closeDropdown"
              @keydown.enter.prevent="selectFirstVisible"
            />

            <!-- Options -->
            <div class="zs-options" role="listbox">
              <button
                v-for="option in visibleOptions"
                :key="option.value"
                type="button"
                class="zs-option"
                :class="{ active: option.value === modelValue }"
                @click="selectOption(option)"
              >
                <span class="zs-option-label">{{ option.label }}</span>
                <span v-if="option.hint" class="zs-option-hint">{{
                  option.hint
                }}</span>
              </button>

              <div v-if="!visibleOptions.length" class="zs-empty">
                {{ emptyState }}
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from "vue";

export interface ZSelectOption {
  value: string;
  label: string;
  hint?: string;
}

const props = withDefaults(
  defineProps<{
    modelValue: string;
    options: ZSelectOption[];
    label?: string;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyState?: string;
    disabled?: boolean;
    embedded?: boolean;
  }>(),
  {
    label: "",
    placeholder: "Select an option",
    searchPlaceholder: "",
    emptyState: "No results found.",
    disabled: false,
    embedded: false,
  }
);

const emit = defineEmits<{
  "update:modelValue": [value: string];
  change: [value: string];
}>();

const rootRef = ref<HTMLElement | null>(null);
const panelRef = ref<HTMLElement | null>(null);
const searchRef = ref<HTMLInputElement | null>(null);
const isOpen = ref(false);
const query = ref("");
const isMobile = ref(true);
const desktopPanelStyle = ref<Record<string, string>>({});

const selectedOption = computed(
  () => props.options.find((o) => o.value === props.modelValue) ?? null
);

const selectedText = computed(
  () => selectedOption.value?.label ?? props.placeholder
);

const visibleOptions = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return props.options;
  return props.options.filter((o) =>
    [o.label, o.value, o.hint ?? ""].join(" ").toLowerCase().includes(q)
  );
});

function computeDesktopPosition(): void {
  if (!rootRef.value) return;
  const rect = rootRef.value.getBoundingClientRect();
  desktopPanelStyle.value = {
    top: `${rect.bottom + 6}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`,
  };
}

function openDropdown(): void {
  if (props.disabled) return;

  if (process.client) {
    isMobile.value = window.innerWidth < 640;
    if (!isMobile.value) {
      computeDesktopPosition();
    }
  }

  isOpen.value = true;
  query.value = "";

  // Only auto-focus search on desktop — on mobile it triggers the iOS
  // virtual keyboard before the bottom sheet has finished animating in,
  // which pushes the sheet off-screen.
  if (!isMobile.value) {
    void nextTick(() => searchRef.value?.focus());
  }
}

function closeDropdown(): void {
  isOpen.value = false;
  query.value = "";
}

function toggleDropdown(): void {
  if (isOpen.value) {
    closeDropdown();
  } else {
    openDropdown();
  }
}

function selectOption(option: ZSelectOption): void {
  emit("update:modelValue", option.value);
  emit("change", option.value);
  closeDropdown();
}

function selectFirstVisible(): void {
  const first = visibleOptions.value[0];
  if (first) selectOption(first);
}

function onOutsidePointerDown(event: MouseEvent): void {
  if (!isOpen.value || isMobile.value) return;
  const target = event.target as Node | null;
  const isInsideRoot = rootRef.value?.contains(target ?? null) ?? false;
  const isInsidePanel = panelRef.value?.contains(target ?? null) ?? false;
  if (target && !isInsideRoot && !isInsidePanel) {
    closeDropdown();
  }
}

watch(
  () => props.modelValue,
  () => {
    if (!isOpen.value) query.value = "";
  }
);

watch(isOpen, (open) => {
  if (!process.client) return;
  document.documentElement.style.overflow = open ? "hidden" : "";
});

onMounted(() => {
  document.addEventListener("mousedown", onOutsidePointerDown, true);
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onOutsidePointerDown, true);
  document.documentElement.style.overflow = "";
});
</script>

<style scoped>
/* ── TRIGGER ────────────────────────────────────────────────────── */
.z-select {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 20px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.z-select.embedded {
  gap: 0;
  padding: 0;
  border-bottom: none;
}

.z-select-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
}

.z-select-trigger {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: transparent;
  border: none;
  outline: none;
  padding: 4px 0;
  font-family: var(--font-body);
  font-size: 20px;
  font-weight: 300;
  color: var(--white);
  cursor: pointer;
  text-align: left;
  min-height: 36px;
}

.z-select-trigger:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.z-select-value {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.z-select-trigger:not(.has-value) .z-select-value {
  color: rgba(255, 255, 255, 0.2);
}

.z-select-arrow {
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.25);
  font-size: 14px;
  line-height: 1;
  transition: transform 0.2s, color 0.2s;
}

.embedded .z-select-trigger {
  padding: 4px 0 6px;
}

.is-open .z-select-arrow {
  transform: rotate(180deg);
  color: rgba(255, 255, 255, 0.5);
}

/* ── OVERLAY (teleported to body) ───────────────────────────────── */
.zs-overlay {
  position: fixed;
  inset: 0;
  z-index: 1500;
}

/* Mobile: bottom-sheet layout */
.zs-overlay--mobile {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  justify-content: flex-end;
}

/* Desktop: transparent click-catcher */
.zs-overlay--desktop {
  background: transparent;
  pointer-events: none;
}

/* ── BACKDROP (mobile only) ─────────────────────────────────────── */
.zs-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

/* ── PANEL ──────────────────────────────────────────────────────── */
.zs-panel {
  position: relative;
  z-index: 1;
  background: rgb(14, 14, 14);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 32px 80px rgba(0, 0, 0, 0.7);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Mobile panel: bottom sheet */
.zs-panel--mobile {
  border-radius: 24px 24px 0 0;
  max-height: 80dvh;
  /* Safe area padding so options aren't hidden behind home bar */
  padding-bottom: env(safe-area-inset-bottom, 0px);
  border-bottom: none;
}

/* Desktop panel: dropdown anchored by JS inline style */
.zs-panel--desktop {
  position: fixed;
  border-radius: 0;
  pointer-events: all;
  max-height: 340px;
}

/* ── MOBILE HANDLE BAR ──────────────────────────────────────────── */
.zs-bar {
  flex-shrink: 0;
  background: rgb(18, 18, 18);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding: 10px 16px 12px;
}

.zs-grabber {
  width: 40px;
  height: 4px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.18);
  margin: 0 auto 12px;
}

.zs-bar-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.zs-bar-title {
  color: var(--white);
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.zs-done {
  flex-shrink: 0;
  border: none;
  background: transparent;
  color: rgba(255, 255, 255, 0.55);
  font-family: var(--font-body);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 0 4px 16px;
  transition: color 0.15s;
}

.zs-done:hover {
  color: var(--white);
}

/* ── SEARCH ─────────────────────────────────────────────────────── */
.zs-search {
  flex-shrink: 0;
  width: 100%;
  border: none;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  color: var(--white);
  font-family: var(--font-body);
  font-size: 16px;
  font-weight: 400;
  padding: 16px;
  outline: none;
  /* Prevent iOS zoom on focus (font-size must be ≥16px) */
  font-size: 16px;
}

.zs-search::placeholder {
  color: rgba(255, 255, 255, 0.2);
}

/* ── OPTIONS ────────────────────────────────────────────────────── */
.zs-options {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  flex: 1;
  min-height: 0;
}

.zs-option {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  border: none;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  background: transparent;
  color: var(--white);
  cursor: pointer;
  /* 52px touch target on mobile */
  padding: 16px;
  text-align: left;
  transition: background 0.12s;
}

.zs-option:hover,
.zs-option.active {
  background: rgba(255, 255, 255, 0.06);
}

.zs-option.active {
  color: var(--white);
}

.zs-option-label {
  font-size: 15px;
  font-weight: 400;
  letter-spacing: 0.02em;
}

.zs-option-hint {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
}

.zs-empty {
  padding: 20px 16px;
  color: rgba(255, 255, 255, 0.3);
  font-size: 12px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
}

/* ── TRANSITIONS ────────────────────────────────────────────────── */

/* Mobile: slide up from bottom */
.zs-sheet-enter-active {
  transition: opacity 0.22s ease, transform 0.28s cubic-bezier(0.32, 0.72, 0, 1);
}
.zs-sheet-leave-active {
  transition: opacity 0.18s ease, transform 0.22s cubic-bezier(0.32, 0.72, 0, 1);
}
.zs-sheet-enter-from .zs-backdrop,
.zs-sheet-leave-to .zs-backdrop {
  opacity: 0;
}
.zs-sheet-enter-from .zs-panel--mobile,
.zs-sheet-leave-to .zs-panel--mobile {
  transform: translateY(100%);
}
.zs-sheet-enter-to .zs-panel--mobile,
.zs-sheet-leave-from .zs-panel--mobile {
  transform: translateY(0);
}

/* Desktop: fade + slight drop */
.zs-drop-enter-active,
.zs-drop-leave-active {
  transition: opacity 0.14s ease, transform 0.14s ease;
}
.zs-drop-enter-from,
.zs-drop-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
