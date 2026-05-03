<template>
  <div class="relative" ref="el">
    <button
      @click="open = !open"
      class="w-9 h-9 flex items-center justify-center rounded t-text-sub hover:t-text transition-colors"
      aria-label="Switch language">
      <!-- Current locale flag -->
      <span class="text-base leading-none select-none">{{ currentFlag }}</span>
    </button>

    <Transition name="dropdown">
      <div v-if="open"
        class="absolute right-0 top-full mt-1 t-bg-raised border t-border rounded-lg shadow-xl overflow-hidden min-w-[120px] z-50">
        <button
          v-for="loc in locales" :key="loc.code"
          @click="switchLocale(loc.code)"
          class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm transition-colors"
          :class="locale === loc.code
            ? 't-text font-medium t-bg-card'
            : 't-text-sub hover:t-text hover:t-bg-card'">
          <span class="text-base leading-none">{{ loc.flag }}</span>
          <span>{{ loc.name }}</span>
          <svg v-if="locale === loc.code" class="w-3 h-3 ml-auto t-text-mute" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
const { locale, setLocale } = useI18n()
const open = ref(false)
const el = ref<HTMLElement | null>(null)

const locales = [
  {
    code: 'vi',
    name: 'Tiếng Việt',
    flag: '🇻🇳',
  },
  {
    code: 'en',
    name: 'English',
    flag: '🇬🇧',
  },
]

const currentFlag = computed(() => locales.find(l => l.code === locale.value)?.flag ?? '🌐')

function switchLocale (code: string) {
  setLocale(code as 'vi' | 'en')
  open.value = false
}

// Close on outside click
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (el.value && !el.value.contains(e.target as Node)) open.value = false
  })
})
</script>

<style scoped>
.dropdown-enter-active, .dropdown-leave-active { transition: opacity 0.15s ease, transform 0.12s ease; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
