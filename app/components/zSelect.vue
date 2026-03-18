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

    <Transition name="z-select-fade">
      <div v-if="isOpen" class="z-select-overlay">
        <button
          type="button"
          class="z-select-backdrop"
          aria-label="Close picker"
          @click="closeDropdown"
        />

        <div
          class="z-select-panel"
          role="dialog"
          :aria-label="label || placeholder"
        >
          <div class="z-select-panel-bar">
            <div class="z-select-panel-grabber" aria-hidden="true" />

            <div class="z-select-panel-heading">
              <span class="z-select-panel-title">{{
                label || placeholder
              }}</span>

              <button
                type="button"
                class="z-select-panel-done"
                @click="closeDropdown"
              >
                Done
              </button>
            </div>
          </div>

          <input
            ref="searchRef"
            v-model="query"
            class="z-select-search"
            type="text"
            :placeholder="searchPlaceholder || placeholder"
            autocomplete="off"
            spellcheck="false"
            @keydown.esc.prevent="closeDropdown"
            @keydown.enter.prevent="selectFirstVisible"
          />

          <div class="z-select-options" role="listbox">
            <button
              v-for="option in visibleOptions"
              :key="option.value"
              type="button"
              class="z-select-option"
              :class="{ active: option.value === modelValue }"
              @click="selectOption(option)"
            >
              <span class="z-select-option-label">{{ option.label }}</span>
              <span v-if="option.hint" class="z-select-option-hint">{{
                option.hint
              }}</span>
            </button>

            <div v-if="!visibleOptions.length" class="z-select-empty">
              {{ emptyState }}
            </div>
          </div>
        </div>
      </div>
    </Transition>
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
const searchRef = ref<HTMLInputElement | null>(null);
const isOpen = ref(false);
const query = ref("");

const selectedOption = computed(
  () =>
    props.options.find((option) => option.value === props.modelValue) ?? null
);

const selectedText = computed(
  () => selectedOption.value?.label ?? props.placeholder
);

const visibleOptions = computed(() => {
  const q = query.value.trim().toLowerCase();
  if (!q) return props.options;

  return props.options.filter((option) => {
    const searchable = [option.label, option.value, option.hint ?? ""]
      .join(" ")
      .toLowerCase();

    return searchable.includes(q);
  });
});

function openDropdown(): void {
  if (props.disabled) return;

  isOpen.value = true;
  query.value = "";

  void nextTick(() => {
    searchRef.value?.focus();
  });
}

function closeDropdown(): void {
  isOpen.value = false;
  query.value = "";
}

function toggleDropdown(): void {
  if (isOpen.value) {
    closeDropdown();
    return;
  }

  openDropdown();
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

function onDocumentPointerDown(event: MouseEvent): void {
  if (!isOpen.value) return;

  const target = event.target as Node | null;
  if (rootRef.value && target && !rootRef.value.contains(target)) {
    closeDropdown();
  }
}

watch(
  () => props.modelValue,
  () => {
    if (!isOpen.value) query.value = "";
  }
);

onMounted(() => {
  document.addEventListener("mousedown", onDocumentPointerDown, true);
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onDocumentPointerDown, true);
});

watch(isOpen, (open) => {
  if (!process.client) return;

  document.documentElement.style.overflow = open ? "hidden" : "";
});
</script>

<style scoped>
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
  font-size: 18px;
  font-weight: 300;
  color: var(--white);
  cursor: pointer;
  text-align: left;
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

.z-select-value:not(:empty) {
  color: var(--white);
}

.z-select-trigger:not(.has-value) .z-select-value {
  color: var(--grey-700);
}

.z-select-arrow {
  flex-shrink: 0;
  color: var(--grey-700);
  font-size: 14px;
  line-height: 1;
  transition: transform 0.2s, color 0.2s;
}

.embedded .z-select-trigger {
  padding: 4px 0 6px;
}

.is-open .z-select-arrow {
  transform: rotate(180deg);
  color: var(--grey-400);
}

.z-select-overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.z-select-backdrop {
  position: absolute;
  inset: 0;
  border: none;
  background: rgba(0, 0, 0, 0.48);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  padding: 0;
}

.z-select-panel {
  position: relative;
  z-index: 1;
  width: min(100%, 560px);
  max-height: min(82vh, 760px);
  display: flex;
  flex-direction: column;
  background: rgba(14, 14, 14, 0.96);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 28px 70px rgba(0, 0, 0, 0.55);
  overflow: hidden;
  border-radius: 28px 28px 0 0;
  padding-bottom: env(safe-area-inset-bottom);
}

.z-select-panel-bar {
  position: sticky;
  top: 0;
  z-index: 2;
  background: linear-gradient(
    180deg,
    rgba(18, 18, 18, 0.98),
    rgba(18, 18, 18, 0.92)
  );
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding: 12px 14px 10px;
}

.z-select-panel-grabber {
  width: 42px;
  height: 5px;
  margin: 0 auto 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.2);
}

.z-select-panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.z-select-panel-title {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--white);
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.z-select-panel-done {
  flex-shrink: 0;
  border: none;
  background: transparent;
  color: var(--grey-400);
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.04em;
  cursor: pointer;
  padding: 0;
}

.z-select-panel-done:hover {
  color: var(--white);
}

.embedded .z-select-panel {
  top: calc(100% + 4px);
}

.z-select-search {
  width: 100%;
  border: none;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.03);
  color: var(--white);
  font-family: var(--font-body);
  font-size: 16px;
  font-weight: 400;
  padding: 16px 16px;
  outline: none;
}

.z-select-search::placeholder {
  color: var(--grey-700);
}

.z-select-options {
  max-height: 50vh;
  overflow-y: auto;
}

.z-select-option {
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
  padding: 16px;
  text-align: left;
  transition: background 0.15s;
}

.z-select-option:hover,
.z-select-option.active {
  background: rgba(255, 255, 255, 0.05);
}

.z-select-option-label {
  font-size: 15px;
  font-weight: 400;
  letter-spacing: 0.02em;
}

.z-select-option-hint {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--grey-400);
}

.z-select-empty {
  padding: 18px 16px;
  color: var(--grey-700);
  font-size: 12px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
}

.z-select-fade-enter-active,
.z-select-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.z-select-fade-enter-from,
.z-select-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (min-width: 640px) {
  .z-select-overlay {
    align-items: flex-start;
    justify-content: flex-start;
    position: absolute;
  }

  .z-select-backdrop {
    display: none;
  }

  .z-select-panel {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 8px);
    width: 100%;
    max-height: none;
    border-radius: 0;
    padding-bottom: 0;
  }

  .z-select-panel-bar {
    padding: 0;
    border-bottom: none;
    background: transparent;
  }

  .z-select-panel-grabber,
  .z-select-panel-heading,
  .z-select-panel-done {
    display: none;
  }

  .z-select-search {
    padding: 16px 18px;
  }

  .z-select-option {
    padding: 15px 18px;
  }
}
</style>
