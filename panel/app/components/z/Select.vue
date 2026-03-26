<template>
  <div
    ref="rootRef"
    class="flex flex-col gap-1.5"
    :class="{ '[&_.z-select-trigger]:border-red-500': !!error }"
  >
    <label
      v-if="label"
      class="text-xs font-semibold tracking-[0.06em] uppercase text-white/50"
      >{{ label }}</label
    >

    <!-- Trigger button -->
    <button
      type="button"
      ref="triggerRef"
      class="z-select-trigger w-full py-3 px-4 md:py-2.5 md:px-3.5 pr-9 md:pr-9 border border-white/12 rounded-lg bg-white/4 text-base md:text-sm text-left outline-none transition-all duration-150 focus:border-white/40 focus:shadow-[0_0_0_3px_rgba(255,255,255,0.08)] disabled:bg-white/2 disabled:text-white/35 disabled:cursor-not-allowed relative min-h-11 md:min-h-0"
      :class="modelValue ? 'text-white/90' : 'text-white/50'"
      :disabled="disabled"
      :aria-expanded="isOpen"
      :aria-label="label || placeholder"
      @click="openDropdown"
    >
      {{ selectedLabel || placeholder || "Chọn..." }}
      <span
        class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-white/40 pointer-events-none transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        aria-hidden="true"
      >
        <svg
          width="14"
          height="14"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2.5"
        >
          <polyline points="6 9 12 15 18 9" />
        </svg>
      </span>
    </button>

    <p v-if="error" class="m-0 text-xs text-red-500" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" class="m-0 text-xs text-white/45">{{ hint }}</p>

    <!-- Dropdown Panel -->
    <Teleport to="body">
      <Transition name="z-select-drop">
        <div
          v-if="isOpen"
          class="fixed z-100"
          :style="dropdownStyle"
          @mousedown.self="closeDropdown"
        >
          <div
            ref="panelRef"
            class="bg-[#111111] border border-white/12 rounded-lg shadow-2xl overflow-hidden flex flex-col"
            :style="{ maxHeight: '340px' }"
            role="dialog"
            :aria-label="label || placeholder"
          >
            <!-- Search input -->
            <div v-if="searchable" class="relative border-b border-white/10">
              <input
                ref="searchRef"
                v-model="searchQuery"
                type="text"
                :placeholder="searchPlaceholder || 'Tìm kiếm...'"
                class="w-full py-3 px-4 pl-10 bg-transparent text-sm text-white/90 placeholder:text-white/40 outline-none"
                autocomplete="off"
                autocorrect="off"
                spellcheck="false"
                @keydown.esc.prevent="closeDropdown"
                @keydown.enter.prevent="selectActiveOption"
                @keydown.down.prevent="moveActiveIndex(1)"
                @keydown.up.prevent="moveActiveIndex(-1)"
                @keydown.home.prevent="moveActiveIndex('first')"
                @keydown.end.prevent="moveActiveIndex('last')"
              />
              <svg
                class="absolute left-3 top-1/2 -translate-y-1/2 text-white/40"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.35-4.35" />
              </svg>
              <div
                v-if="loading"
                class="absolute right-3 top-1/2 -translate-y-1/2"
              >
                <div
                  class="w-4 h-4 border-2 border-white/30 border-t-[#C4A77D] rounded-full animate-spin"
                />
              </div>
            </div>

            <!-- Options list -->
            <div
              ref="optionsRef"
              class="overflow-y-auto flex-1 min-h-0"
              role="listbox"
            >
              <button
                v-for="(opt, index) in filteredOptions"
                :key="String(opt.value)"
                type="button"
                class="w-full flex items-center justify-between px-4 py-3 md:py-2.5 text-left border-b border-white/5 last:border-b-0 transition-colors"
                :class="[
                  opt.disabled
                    ? 'text-white/40 cursor-not-allowed'
                    : 'text-white/90 cursor-pointer hover:bg-white/5',
                  activeIndex === index ? 'bg-white/8' : '',
                  modelValue === opt.value ? 'bg-[#8B4513]/10' : '',
                ]"
                :disabled="opt.disabled"
                :data-index="index"
                @click="!opt.disabled && selectOption(opt)"
                @mouseenter="activeIndex = index"
              >
                <span class="text-sm">{{ opt.label }}</span>
                <svg
                  v-if="modelValue === opt.value"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2.5"
                  class="text-[#C4A77D] shrink-0"
                >
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              </button>

              <!-- Create New Option -->
              <button
                v-if="showCreateOption && searchQuery.trim()"
                type="button"
                class="w-full flex items-center gap-2 px-4 py-3 md:py-2.5 text-left border-t border-white/10 bg-white/5 transition-colors cursor-pointer hover:bg-white/8"
                :class="
                  activeIndex === filteredOptions.length ? 'bg-white/8' : ''
                "
                :data-index="filteredOptions.length"
                @click="handleCreateOption"
                @mouseenter="activeIndex = filteredOptions.length"
              >
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  class="text-[#C4A77D]"
                >
                  <line x1="12" y1="5" x2="12" y2="19" />
                  <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span class="text-sm text-white/90">{{
                  createOptionLabel || "+ Tạo mới"
                }}</span>
              </button>

              <div
                v-if="filteredOptions.length === 0 && !showCreateOption"
                class="px-4 py-8 text-center"
              >
                <p class="text-sm text-white/50">
                  {{ emptyText || "Không tìm thấy" }}
                </p>
              </div>

              <!-- Empty state with create option -->
              <div
                v-if="
                  filteredOptions.length === 0 &&
                  showCreateOption &&
                  searchQuery.trim()
                "
                class="px-4 py-6 text-center"
              >
                <p class="text-sm text-white/50 mb-3">
                  {{ emptyText || "Không tìm thấy" }}
                </p>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 px-3 py-2 text-sm text-[#C4A77D] hover:text-[#D4B78D] transition-colors"
                  @click="handleCreateOption"
                >
                  <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                  </svg>
                  {{ createOptionLabel || "+ Tạo mới" }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import Fuse from "fuse.js";
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from "vue";

export interface SelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null;
    options: SelectOption[];
    label?: string;
    placeholder?: string;
    searchPlaceholder?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string | null;
    hint?: string;
    id?: string;
    searchable?: boolean;
    async?: boolean;
    loading?: boolean;
    debounceMs?: number;
    fuseThreshold?: number;
    emptyText?: string;
    showCreateOption?: boolean;
    createOptionLabel?: string;
  }>(),
  {
    disabled: false,
    required: false,
    searchable: true,
    async: false,
    loading: false,
    debounceMs: 150,
    fuseThreshold: 0.4,
    showCreateOption: false,
  }
);

const emit = defineEmits<{
  "update:modelValue": [value: string | number];
  change: [value: string | number];
  search: [query: string];
  create: [];
}>();

const rootRef = ref<HTMLElement | null>(null);
const triggerRef = ref<HTMLElement | null>(null);
const panelRef = ref<HTMLElement | null>(null);
const searchRef = ref<HTMLInputElement | null>(null);
const optionsRef = ref<HTMLElement | null>(null);
const isOpen = ref(false);
const searchQuery = ref("");
const activeIndex = ref(0);
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null);
const dropdownStyle = ref<Record<string, string>>({});

const selectId = computed(
  () => props.id ?? `z-select-${Math.random().toString(36).slice(2, 7)}`
);

const selectedLabel = computed(() => {
  const opt = props.options.find((o) => o.value === props.modelValue);
  return opt?.label || "";
});

// Fuse instance for fuzzy search
const fuse = computed(() => {
  return new Fuse(props.options, {
    keys: ["label", "value"],
    threshold: props.fuseThreshold,
    includeScore: false,
    shouldSort: true,
  });
});

// Filter options using Fuse.js fuzzy search
const filteredOptions = computed((): SelectOption[] => {
  const q = searchQuery.value.trim();
  if (!q) return props.options;

  // If async mode, return all options (filtered by parent via @search)
  if (props.async) return props.options;

  // Use Fuse.js for fuzzy search
  const results = fuse.value.search(q);
  return results.map((r) => r.item);
});

// Debounced search emit for async filtering
watch(searchQuery, (newQuery) => {
  if (!props.async) return;

  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }

  searchTimeout.value = setTimeout(() => {
    emit("search", newQuery.trim());
  }, props.debounceMs);
});

// Reset active index when options change
watch(
  filteredOptions,
  () => {
    activeIndex.value = 0;
    scrollToActiveOption();
  },
  { flush: "post" }
);

// Reset query when dropdown closes
watch(isOpen, (open) => {
  if (!open) {
    searchQuery.value = "";
    activeIndex.value = 0;
  }
});

function computeDropdownPosition(): void {
  if (!triggerRef.value) return;
  const rect = triggerRef.value.getBoundingClientRect();
  const panelWidth = Math.max(rect.width, 240);

  dropdownStyle.value = {
    top: `${rect.bottom + 4}px`,
    left: `${rect.left}px`,
    width: `${panelWidth}px`,
  };
}

function openDropdown(): void {
  if (props.disabled) return;

  computeDropdownPosition();
  isOpen.value = true;
  searchQuery.value = "";
  activeIndex.value = 0;

  // Auto-focus search input
  if (props.searchable) {
    void nextTick(() => {
      searchRef.value?.focus();
      scrollToActiveOption();
    });
  }
}

function closeDropdown(): void {
  isOpen.value = false;
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
}

function selectOption(opt: SelectOption): void {
  if (opt.disabled) return;
  emit("update:modelValue", opt.value);
  emit("change", opt.value);
  closeDropdown();
}

function selectActiveOption(): void {
  const opts = filteredOptions.value;
  const createIndex =
    props.showCreateOption && searchQuery.value.trim() ? opts.length : -1;

  // If active index is at create option position
  if (activeIndex.value === createIndex && createIndex >= 0) {
    handleCreateOption();
    return;
  }

  const opt = opts[activeIndex.value];
  if (opt && !opt.disabled) {
    selectOption(opt);
  }
}

function handleCreateOption(): void {
  emit("create");
  closeDropdown();
}

function moveActiveIndex(direction: 1 | -1 | "first" | "last"): void {
  const opts = filteredOptions.value;
  const hasCreateOption = props.showCreateOption && searchQuery.value.trim();
  const max = opts.length - 1 + (hasCreateOption ? 1 : 0);
  if (max < 0) return;

  // Skip disabled options
  let newIndex = activeIndex.value;

  if (direction === "first") {
    newIndex = 0;
  } else if (direction === "last") {
    newIndex = max;
  } else {
    newIndex = Math.max(0, Math.min(max, newIndex + direction));
  }

  // If landing on disabled, skip to next available
  if (newIndex <= opts.length - 1 && opts[newIndex]?.disabled) {
    if (direction === 1 || direction === "first") {
      // Find next enabled
      while (
        newIndex < max &&
        newIndex < opts.length &&
        opts[newIndex]?.disabled
      ) {
        newIndex++;
      }
    } else {
      // Find prev enabled
      while (newIndex > 0 && opts[newIndex]?.disabled) {
        newIndex--;
      }
    }
  }

  activeIndex.value = newIndex;
  scrollToActiveOption();
}

function scrollToActiveOption(): void {
  void nextTick(() => {
    const optionEl = optionsRef.value?.querySelector(
      `[data-index="${activeIndex.value}"]`
    ) as HTMLElement | null;
    if (optionEl && optionsRef.value) {
      optionEl.scrollIntoView({ block: "nearest", behavior: "smooth" });
    }
  });
}

function onOutsidePointerDown(event: MouseEvent): void {
  if (!isOpen.value) return;
  const target = event.target as Node | null;
  const isInsideRoot = rootRef.value?.contains(target ?? null) ?? false;
  const isInsidePanel = panelRef.value?.contains(target ?? null) ?? false;
  if (target && !isInsideRoot && !isInsidePanel) {
    closeDropdown();
  }
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === "Escape" && isOpen.value) {
    closeDropdown();
  }
}

function onResize(): void {
  if (isOpen.value) {
    computeDropdownPosition();
  }
}

onMounted(() => {
  document.addEventListener("mousedown", onOutsidePointerDown, true);
  document.addEventListener("keydown", onKeydown);
  window.addEventListener("resize", onResize);
  window.addEventListener("scroll", onResize, true);
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onOutsidePointerDown, true);
  document.removeEventListener("keydown", onKeydown);
  window.removeEventListener("resize", onResize);
  window.removeEventListener("scroll", onResize, true);
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
});
</script>

<style scoped>
.z-select-drop-enter-active,
.z-select-drop-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.z-select-drop-enter-from,
.z-select-drop-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
