<template>
  <div class="z-input-wrap" :class="{ 'z-input-wrap--error': !!error, 'z-input-wrap--disabled': disabled }">
    <!-- Label row -->
    <div v-if="label || $slots.label || hint" class="z-input-label-row">
      <label v-if="label || $slots.label" :for="inputId" class="z-input-label">
        <slot name="label">{{ label }}</slot>
        <span v-if="optional" class="z-input-optional">tùy chọn</span>
      </label>
      <span v-if="hint" class="z-input-hint">{{ hint }}</span>
    </div>

    <!-- Input wrapper -->
    <div class="z-input-field" :class="{ 'z-input-field--focused': focused }">
      <!-- Leading slot (icon, prefix text) -->
      <span v-if="$slots.leading" class="z-input-adorn z-input-adorn--leading" aria-hidden="true">
        <slot name="leading" />
      </span>

      <textarea
        v-if="type === 'textarea'"
        :id="inputId"
        v-bind="$attrs"
        :value="modelValue"
        :disabled="disabled"
        :placeholder="placeholder"
        :rows="rows ?? 4"
        class="z-input"
        :class="{ 'has-leading': $slots.leading, 'has-trailing': $slots.trailing || type === 'password' }"
        @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        @focus="focused = true"
        @blur="focused = false" />

      <input
        v-else
        :id="inputId"
        v-bind="$attrs"
        :type="currentType"
        :value="modelValue"
        :disabled="disabled"
        :placeholder="placeholder"
        class="z-input"
        :class="{ 'has-leading': $slots.leading, 'has-trailing': $slots.trailing || type === 'password' }"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        @focus="focused = true"
        @blur="focused = false" />

      <!-- Password toggle -->
      <button
        v-if="type === 'password'"
        type="button"
        class="z-input-adorn z-input-adorn--trailing z-input-pw-toggle"
        :aria-label="showPw ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
        @click="showPw = !showPw">
        <svg class="z-input-pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="!showPw" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
        </svg>
      </button>

      <!-- Trailing slot -->
      <span v-else-if="$slots.trailing" class="z-input-adorn z-input-adorn--trailing" aria-hidden="true">
        <slot name="trailing" />
      </span>
    </div>

    <!-- Password strength bar -->
    <div v-if="type === 'password' && modelValue" class="z-input-strength">
      <div class="z-input-strength-bar">
        <div v-for="i in 4" :key="i" class="z-input-strength-seg"
          :class="strengthScore >= i ? `z-input-strength--${strengthLevel}` : ''" />
      </div>
      <span class="z-input-strength-label">{{ strengthText }}</span>
    </div>

    <!-- Error message -->
    <Transition name="z-msg">
      <p v-if="error" class="z-input-error" role="alert">{{ error }}</p>
    </Transition>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  modelValue?: string
  type?: 'text' | 'email' | 'password' | 'tel' | 'number' | 'textarea'
  label?: string
  hint?: string
  placeholder?: string
  error?: string
  disabled?: boolean
  optional?: boolean
  rows?: number
}>(), { type: 'text' })

defineEmits<{ 'update:modelValue': [value: string] }>()

const inputId = useId()
const focused = ref(false)
const showPw = ref(false)

const currentType = computed(() => {
  if (props.type === 'password') return showPw.value ? 'text' : 'password'
  return props.type
})

// Password strength
const strengthScore = computed(() => {
  const p = props.modelValue ?? ''
  if (!p || props.type !== 'password') return 0
  let s = 0
  if (p.length >= 8)  s++
  if (p.length >= 12) s++
  if (/[A-Z]/.test(p) && /[0-9]/.test(p)) s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return s
})
const strengthLevel = computed(() => {
  const s = strengthScore.value
  if (s <= 1) return 'weak'
  if (s === 2) return 'fair'
  if (s === 3) return 'good'
  return 'strong'
})
const strengthText = computed(() => ({
  weak: 'Yếu', fair: 'Trung bình', good: 'Khá mạnh', strong: 'Mạnh',
}[strengthLevel.value]))
</script>

<style scoped>
/* ── Wrapper ───────────────────────────────────────────────────────── */
.z-input-wrap { display: flex; flex-direction: column; gap: 0.35rem; }
.z-input-wrap--disabled { opacity: 0.5; pointer-events: none; }

/* ── Label row ─────────────────────────────────────────────────────── */
.z-input-label-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem;
}
.z-input-label {
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--text-mute);
  cursor: pointer;
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
}
.z-input-optional {
  font-size: 0.65rem;
  font-weight: 400;
  letter-spacing: 0;
  text-transform: none;
  color: var(--text-mute);
  opacity: 0.7;
}
.z-input-hint {
  font-size: 0.65rem;
  color: var(--text-mute);
  text-align: right;
}

/* ── Field shell ───────────────────────────────────────────────────── */
.z-input-field {
  position: relative;
  display: flex;
  align-items: center;
  border-radius: 0.5rem;
  border: 1px solid var(--border-hi);
  background-color: var(--input-bg);
  transition: border-color 0.18s ease, background-color 0.18s ease,
              box-shadow 0.18s ease;
}
.z-input-field--focused {
  border-color: var(--border-focus);
  background-color: var(--input-bg-focus);
  box-shadow: 0 0 0 3px rgba(161, 161, 170, 0.08);
}
.z-input-wrap--error .z-input-field {
  border-color: rgba(239, 68, 68, 0.6);
}
.z-input-wrap--error .z-input-field--focused {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* ── Input element ─────────────────────────────────────────────────── */
.z-input {
  flex: 1;
  min-width: 0;
  background: transparent;
  border: none;
  outline: none;
  padding: 0.65rem 0.875rem;
  font-family: "Be Vietnam Pro", sans-serif;
  font-size: 0.875rem;
  color: var(--text);
  caret-color: var(--text);
  transition: color 0.18s ease;
  resize: none; /* textarea */
}
.z-input::placeholder { color: var(--text-mute); opacity: 0.7; }
.z-input.has-leading  { padding-left: 0.5rem; }
.z-input.has-trailing { padding-right: 0.5rem; }

/* ── Adornments ────────────────────────────────────────────────────── */
.z-input-adorn {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  color: var(--text-mute);
}
.z-input-adorn--leading  { padding-left: 0.75rem; }
.z-input-adorn--trailing { padding-right: 0.75rem; }

/* Password toggle button */
.z-input-pw-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0 0.75rem;
  height: 100%;
  transition: color 0.15s ease;
}
.z-input-pw-toggle:hover { color: var(--text-sub); }
.z-input-pw-icon { width: 1rem; height: 1rem; }

/* ── Password strength ─────────────────────────────────────────────── */
.z-input-strength {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.15rem;
}
.z-input-strength-bar {
  display: flex;
  gap: 3px;
  flex: 1;
}
.z-input-strength-seg {
  flex: 1;
  height: 3px;
  border-radius: 999px;
  background-color: var(--border-hi);
  transition: background-color 0.3s ease;
}
.z-input-strength--weak   { background-color: #ef4444; }
.z-input-strength--fair   { background-color: #f59e0b; }
.z-input-strength--good   { background-color: #3b82f6; }
.z-input-strength--strong { background-color: #22c55e; }

.z-input-strength-label {
  font-size: 0.65rem;
  color: var(--text-mute);
  white-space: nowrap;
}

/* ── Error message ─────────────────────────────────────────────────── */
.z-input-error {
  font-size: 0.72rem;
  color: #ef4444;
  margin: 0;
}

/* ── Transition ────────────────────────────────────────────────────── */
.z-msg-enter-active, .z-msg-leave-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.z-msg-enter-from, .z-msg-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
