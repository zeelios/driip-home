import { ref, computed, onMounted, onUnmounted } from "vue";

export function useCountdown(targetDate: Date) {
  const now = ref(Date.now());
  let timer: ReturnType<typeof setInterval> | null = null;

  onMounted(() => {
    timer = setInterval(() => {
      now.value = Date.now();
    }, 1000);
  });

  onUnmounted(() => {
    if (timer) clearInterval(timer);
  });

  const remaining = computed(() => {
    const diff = targetDate.getTime() - now.value;
    return Math.max(0, diff);
  });

  const isExpired = computed(() => remaining.value === 0);

  const days = computed(() => Math.floor(remaining.value / (1000 * 60 * 60 * 24)));
  const hours = computed(() => Math.floor((remaining.value % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)));
  const minutes = computed(() => Math.floor((remaining.value % (1000 * 60 * 60)) / (1000 * 60)));
  const seconds = computed(() => Math.floor((remaining.value % (1000 * 60)) / 1000));

  const pad = (n: number) => String(n).padStart(2, "0");

  return { days, hours, minutes, seconds, pad, isExpired, remaining };
}
