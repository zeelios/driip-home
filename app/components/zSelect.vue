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
      <div v-if="isOpen" class="z-select-panel">
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

.z-select-panel {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 8px);
  z-index: 40;
  background: #0f0f0f;
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.45);
  overflow: hidden;
}

.embedded .z-select-panel {
  top: calc(100% + 4px);
}

.z-select-search {
  width: 100%;
  border: none;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: #111;
  color: var(--white);
  font-family: var(--font-body);
  font-size: 14px;
  font-weight: 300;
  padding: 14px 16px;
  outline: none;
}

.z-select-search::placeholder {
  color: var(--grey-700);
}

.z-select-options {
  max-height: 280px;
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
  padding: 14px 16px;
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
  .z-select-search {
    padding: 16px 18px;
  }

  .z-select-option {
    padding: 15px 18px;
  }
}
</style>
