<template>
  <div class="relative" ref="containerRef">
    <!-- Input Trigger -->
    <ZInput
      :model-value="displayValue"
      :label="label"
      :placeholder="placeholder"
      :size="size"
      :disabled="disabled"
      :error="error"
      :hint="hint"
      readonly
      @click="togglePicker"
    >
      <template #suffix>
        <svg
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          class="text-white/40"
        >
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
          <line x1="16" y1="2" x2="16" y2="6" />
          <line x1="8" y1="2" x2="8" y2="6" />
          <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
      </template>
    </ZInput>

    <!-- Calendar Popup -->
    <div
      v-if="isOpen"
      class="absolute z-50 mt-1 bg-[#111111] border border-white/12 rounded-lg shadow-xl overflow-hidden min-w-75 md:min-w-90"
      :class="positionClass"
    >
      <!-- Calendar Header -->
      <div
        class="flex items-center justify-between p-3 md:p-4 border-b border-white/8"
      >
        <button
          class="p-1.5 md:p-2 hover:bg-white/10 rounded transition-colors"
          @click="previousMonth"
        >
          <svg
            class="w-4 h-4 md:w-5 md:h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <polyline points="15 18 9 12 15 6" />
          </svg>
        </button>
        <span class="text-sm md:text-base font-medium text-white/90">
          {{ currentMonthLabel }}
        </span>
        <button
          class="p-1.5 md:p-2 hover:bg-white/10 rounded transition-colors"
          @click="nextMonth"
        >
          <svg
            class="w-4 h-4 md:w-5 md:h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <polyline points="9 18 15 12 9 6" />
          </svg>
        </button>
      </div>

      <!-- Calendar Grid -->
      <div class="p-3 md:p-4">
        <!-- Weekday Headers -->
        <div class="grid grid-cols-7 gap-1 md:gap-2 mb-2 md:mb-3">
          <span
            v-for="day in weekDays"
            :key="day"
            class="text-center text-xs md:text-sm text-white/40 py-1"
          >
            {{ day }}
          </span>
        </div>

        <!-- Days Grid -->
        <div class="grid grid-cols-7 gap-1 md:gap-2">
          <button
            v-for="date in calendarDays"
            :key="date.date"
            class="aspect-square flex items-center justify-center text-sm md:text-base rounded transition-colors"
            :class="{
              'text-white/30': !date.isCurrentMonth,
              'text-white/70 hover:bg-white/10':
                date.isCurrentMonth && !date.isSelected,
              'bg-amber-500 text-black font-medium': date.isSelected,
              'hover:bg-amber-500/20': date.isCurrentMonth && !date.isSelected,
            }"
            @click="selectDate(date.date)"
          >
            {{ date.day }}
          </button>
        </div>
      </div>

      <!-- Footer -->
      <div
        class="flex items-center justify-between p-3 md:p-4 border-t border-white/8"
      >
        <button
          class="text-xs md:text-sm text-white/50 hover:text-white/80 transition-colors"
          @click="selectToday"
        >
          Hôm nay
        </button>
        <button
          class="text-xs md:text-sm text-amber-500 hover:text-amber-400 transition-colors"
          @click="clearDate"
        >
          Xóa
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

const props = withDefaults(
  defineProps<{
    modelValue?: string | null;
    label?: string;
    placeholder?: string;
    size?: "sm" | "md" | "lg";
    disabled?: boolean;
    error?: string | null;
    hint?: string;
    position?: "bottom-left" | "bottom-right";
  }>(),
  {
    placeholder: "Chọn ngày",
    size: "md",
    disabled: false,
    position: "bottom-left",
  }
);

const emit = defineEmits<{
  "update:modelValue": [value: string | null];
}>();

const isOpen = ref(false);
const containerRef = ref<HTMLElement | null>(null);
const currentDate = ref(new Date());

const weekDays = ["T2", "T3", "T4", "T5", "T6", "T7", "CN"];

const positionClass = computed(() => {
  return props.position === "bottom-right" ? "right-0" : "left-0";
});

const displayValue = computed(() => {
  if (!props.modelValue) return "";
  const date = new Date(props.modelValue);
  if (isNaN(date.getTime())) return "";
  return formatDateDisplay(date);
});

const currentMonthLabel = computed(() => {
  const months = [
    "Tháng 1",
    "Tháng 2",
    "Tháng 3",
    "Tháng 4",
    "Tháng 5",
    "Tháng 6",
    "Tháng 7",
    "Tháng 8",
    "Tháng 9",
    "Tháng 10",
    "Tháng 11",
    "Tháng 12",
  ];
  return `${
    months[currentDate.value.getMonth()]
  } ${currentDate.value.getFullYear()}`;
});

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();

  const firstDayOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);
  const daysInMonth = lastDayOfMonth.getDate();

  // Adjust for Monday start (0 = Monday in our grid)
  let startDayOfWeek = firstDayOfMonth.getDay();
  startDayOfWeek = startDayOfWeek === 0 ? 6 : startDayOfWeek - 1;

  const days = [];

  // Previous month days
  const prevMonth = new Date(year, month, 0);
  for (let i = startDayOfWeek - 1; i >= 0; i--) {
    const day = prevMonth.getDate() - i;
    days.push({
      date: formatDateValue(new Date(year, month - 1, day)),
      day: day,
      isCurrentMonth: false,
      isSelected: isSelectedDate(new Date(year, month - 1, day)),
    });
  }

  // Current month days
  for (let day = 1; day <= daysInMonth; day++) {
    days.push({
      date: formatDateValue(new Date(year, month, day)),
      day: day,
      isCurrentMonth: true,
      isSelected: isSelectedDate(new Date(year, month, day)),
    });
  }

  // Next month days to fill grid (6 rows * 7 cols = 42 cells)
  const remainingCells = 42 - days.length;
  for (let day = 1; day <= remainingCells; day++) {
    days.push({
      date: formatDateValue(new Date(year, month + 1, day)),
      day: day,
      isCurrentMonth: false,
      isSelected: isSelectedDate(new Date(year, month + 1, day)),
    });
  }

  return days;
});

function formatDateDisplay(date: Date): string {
  const day = date.getDate().toString().padStart(2, "0");
  const month = (date.getMonth() + 1).toString().padStart(2, "0");
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
}

function formatDateValue(date: Date): string {
  const year = date.getFullYear();
  const month = (date.getMonth() + 1).toString().padStart(2, "0");
  const day = date.getDate().toString().padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function isSelectedDate(date: Date): boolean {
  if (!props.modelValue) return false;
  const selected = new Date(props.modelValue);
  return (
    date.getDate() === selected.getDate() &&
    date.getMonth() === selected.getMonth() &&
    date.getFullYear() === selected.getFullYear()
  );
}

function togglePicker(): void {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
}

function selectDate(dateValue: string): void {
  emit("update:modelValue", dateValue);
  isOpen.value = false;
}

function previousMonth(): void {
  currentDate.value = new Date(
    currentDate.value.getFullYear(),
    currentDate.value.getMonth() - 1,
    1
  );
}

function nextMonth(): void {
  currentDate.value = new Date(
    currentDate.value.getFullYear(),
    currentDate.value.getMonth() + 1,
    1
  );
}

function selectToday(): void {
  const today = new Date();
  emit("update:modelValue", formatDateValue(today));
  currentDate.value = today;
  isOpen.value = false;
}

function clearDate(): void {
  emit("update:modelValue", null);
  isOpen.value = false;
}

function handleClickOutside(event: MouseEvent): void {
  if (
    containerRef.value &&
    !containerRef.value.contains(event.target as Node)
  ) {
    isOpen.value = false;
  }
}

// Watch for modelValue changes to sync current month
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      const date = new Date(newValue);
      if (!isNaN(date.getTime())) {
        currentDate.value = date;
      }
    }
  },
  { immediate: true }
);

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>
