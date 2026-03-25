<template>
  <div
    class="flex flex-col gap-1.5"
    :class="{ '[&_.z-select-trigger]:border-red-500': !!error }"
  >
    <label
      v-if="label"
      class="text-xs font-semibold tracking-[0.06em] uppercase text-white/50"
      >{{ label }}</label
    >

    <!-- Mobile: Custom trigger that opens wheel picker -->
    <template v-if="isMobile">
      <button
        type="button"
        class="z-select-trigger w-full py-3 px-4 md:py-2.5 md:px-3.5 pr-9 md:pr-9 border border-white/12 rounded-lg bg-white/4 text-base md:text-sm text-left outline-none transition-all duration-150 focus:border-white/40 focus:shadow-[0_0_0_3px_rgba(255,255,255,0.08)] disabled:bg-white/2 disabled:text-white/35 disabled:cursor-not-allowed relative min-h-11 md:min-h-0"
        :class="modelValue ? 'text-white/90' : 'text-white/50'"
        :disabled="disabled"
        @click="openPicker"
      >
        {{ selectedLabel || placeholder || "Chọn..." }}
        <span
          class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-white/40 pointer-events-none"
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
    </template>

    <!-- Desktop: Native select -->
    <template v-else>
      <div class="relative">
        <select
          :id="selectId"
          v-bind="$attrs"
          class="w-full py-2.5 pr-9 pl-3.5 border border-white/12 rounded-lg bg-white/4 font-inherit text-sm text-white/90 outline-none appearance-none cursor-pointer transition-all duration-150 focus:border-white/40 focus:shadow-[0_0_0_3px_rgba(255,255,255,0.08)] disabled:bg-white/2 disabled:text-white/35 disabled:cursor-not-allowed"
          :value="modelValue"
          :disabled="disabled"
          :required="required"
          @change="onNativeChange"
        >
          <option v-if="placeholder" value="" disabled :selected="!modelValue">
            {{ placeholder }}
          </option>
          <option
            v-for="opt in options"
            :key="String(opt.value)"
            :value="opt.value"
            :disabled="opt.disabled"
          >
            {{ opt.label }}
          </option>
        </select>
        <span
          class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center text-white/40 pointer-events-none"
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
      </div>
    </template>

    <p v-if="error" class="m-0 text-xs text-red-500" role="alert">
      {{ error }}
    </p>
    <p v-else-if="hint" class="m-0 text-xs text-white/45">{{ hint }}</p>

    <!-- Mobile Picker Modal -->
    <Teleport v-if="isMobile" to="body">
      <Transition name="picker">
        <div v-if="isOpen" class="fixed inset-0 z-50 flex flex-col justify-end">
          <!-- Backdrop -->
          <div
            class="absolute inset-0 bg-black/60 backdrop-blur-sm"
            @click="closePicker"
          />

          <!-- Picker Sheet -->
          <div class="relative bg-[#111111] rounded-t-2xl overflow-hidden">
            <!-- Header -->
            <div
              class="flex items-center justify-between px-4 py-3 border-b border-white/10"
            >
              <button
                type="button"
                class="text-sm text-white/60 hover:text-white transition-colors"
                @click="closePicker"
              >
                Hủy
              </button>
              <span class="text-sm font-semibold text-white/90">{{
                label || "Chọn"
              }}</span>
              <button
                type="button"
                class="text-sm font-semibold text-amber-500 hover:text-amber-400 transition-colors"
                @click="confirmSelection"
              >
                Xong
              </button>
            </div>

            <!-- Search (if searchable) -->
            <div v-if="searchable" class="px-4 py-3 border-b border-white/10">
              <div class="relative">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Tìm kiếm..."
                  class="w-full py-3 px-4 md:py-2.5 md:px-3.5 pl-9 md:pl-9 border border-white/12 rounded-lg bg-white/4 text-base md:text-sm text-white/90 placeholder:text-white/40 outline-none focus:border-white/30 min-h-11 md:min-h-0"
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
              </div>
            </div>

            <!-- Options List -->
            <div class="max-h-[50vh] overflow-y-auto">
              <div
                v-for="opt in filteredOptions"
                :key="String(opt.value)"
                class="flex items-center justify-between px-4 py-4 md:py-3.5 border-b border-white/5 cursor-pointer active:bg-white/5 min-h-11"
                :class="tempValue === opt.value ? 'bg-white/8' : ''"
                @click="selectOption(opt.value)"
              >
                <span
                  class="text-base md:text-sm"
                  :class="opt.disabled ? 'text-white/40' : 'text-white/90'"
                >
                  {{ opt.label }}
                </span>
                <svg
                  v-if="tempValue === opt.value"
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2.5"
                  class="text-amber-500"
                >
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              </div>
              <div
                v-if="filteredOptions.length === 0"
                class="px-4 py-8 text-center"
              >
                <p class="text-sm text-white/50">Không tìm thấy</p>
              </div>
            </div>

            <!-- Safe area spacer for iOS -->
            <div class="h-[env(safe-area-inset-bottom)]" />
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

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
    disabled?: boolean;
    required?: boolean;
    error?: string | null;
    hint?: string;
    id?: string;
    searchable?: boolean;
  }>(),
  { disabled: false, required: false, searchable: false }
);

const emit = defineEmits<{
  "update:modelValue": [value: string | number];
  change: [value: string | number];
}>();

// Mobile detection
const isMobile = ref(false);
const isOpen = ref(false);
const tempValue = ref<string | number>("");
const searchQuery = ref("");

const selectId = computed(
  () => props.id ?? `z-select-${Math.random().toString(36).slice(2, 7)}`
);

const selectedLabel = computed(() => {
  const opt = props.options.find((o) => o.value === props.modelValue);
  return opt?.label || "";
});

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options;
  const q = searchQuery.value.toLowerCase();
  return props.options.filter((o) => o.label.toLowerCase().includes(q));
});

function checkMobile() {
  isMobile.value = window.innerWidth < 1024; // lg breakpoint
}

function openPicker() {
  if (props.disabled) return;
  tempValue.value = props.modelValue ?? "";
  searchQuery.value = "";
  isOpen.value = true;
  document.body.style.overflow = "hidden";
}

function closePicker() {
  isOpen.value = false;
  document.body.style.overflow = "";
}

function selectOption(value: string | number) {
  tempValue.value = value;
}

function confirmSelection() {
  if (tempValue.value !== (props.modelValue ?? "")) {
    emit("update:modelValue", tempValue.value);
    emit("change", tempValue.value);
  }
  closePicker();
}

function onNativeChange(event: Event) {
  const val = (event.target as HTMLSelectElement).value;
  emit("update:modelValue", val);
  emit("change", val);
}

let resizeHandler: (() => void) | null = null;

onMounted(() => {
  checkMobile();
  resizeHandler = () => checkMobile();
  window.addEventListener("resize", resizeHandler);
});

onUnmounted(() => {
  if (resizeHandler) window.removeEventListener("resize", resizeHandler);
  document.body.style.overflow = "";
});

// Close on escape key
watch(isOpen, (open) => {
  if (open) {
    const onKeydown = (e: KeyboardEvent) => {
      if (e.key === "Escape") closePicker();
    };
    document.addEventListener("keydown", onKeydown);
    // Cleanup when closing
    watch(
      isOpen,
      () => {
        document.removeEventListener("keydown", onKeydown);
      },
      { once: true }
    );
  }
});
</script>

<style scoped>
.picker-enter-active,
.picker-leave-active {
  transition: opacity 0.2s ease;
}
.picker-enter-from,
.picker-leave-to {
  opacity: 0;
}
.picker-enter-active .bg-\[\#111111\],
.picker-leave-active .bg-\[\#111111\] {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.picker-enter-from .bg-\[\#111111\],
.picker-leave-to .bg-\[\#111111\] {
  transform: translateY(100%);
}
</style>
