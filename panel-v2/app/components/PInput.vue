<template>
  <div class="pi-wrap" :class="{ 'pi-wrap--error': !!error }">
    <label v-if="label" :for="id" class="pi-label">{{ label }}</label>
    <div class="pi-field" :class="{ 'pi-field--focused': focused }">
      <span v-if="$slots.leading" class="pi-adorn pi-adorn--l"><slot name="leading" /></span>
      <select v-if="type === 'select'"
        :id="id" v-bind="$attrs"
        :value="modelValue" :disabled="disabled"
        class="pi-input"
        :class="{ 'has-l': $slots.leading, 'has-r': $slots.trailing }"
        @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        @focus="focused = true" @blur="focused = false">
        <slot />
      </select>
      <textarea v-else-if="type === 'textarea'"
        :id="id" v-bind="$attrs"
        :value="modelValue" :disabled="disabled" :placeholder="placeholder"
        :rows="rows ?? 3"
        class="pi-input"
        :class="{ 'has-l': $slots.leading, 'has-r': $slots.trailing }"
        @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        @focus="focused = true" @blur="focused = false" />
      <input v-else
        :id="id" v-bind="$attrs"
        :type="type" :value="modelValue"
        :disabled="disabled" :placeholder="placeholder"
        class="pi-input"
        :class="{ 'has-l': $slots.leading, 'has-r': $slots.trailing }"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        @focus="focused = true" @blur="focused = false" />
      <span v-if="$slots.trailing" class="pi-adorn pi-adorn--r"><slot name="trailing" /></span>
    </div>
    <p v-if="error" class="pi-error">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  modelValue?: string | number
  type?: 'text' | 'email' | 'password' | 'tel' | 'number' | 'date' | 'select' | 'textarea' | 'search'
  label?: string
  placeholder?: string
  error?: string
  disabled?: boolean
  rows?: number
}>(), { type: 'text' })

defineEmits<{ 'update:modelValue': [v: string] }>()

const id = useId()
const focused = ref(false)
</script>

<style scoped>
.pi-wrap { display: flex; flex-direction: column; gap: 0.3rem; }
.pi-label {
  font-size: 0.68rem; font-weight: 600; letter-spacing: 0.08em;
  text-transform: uppercase; color: var(--text-mute);
}
.pi-field {
  display: flex; align-items: center;
  border: 1px solid var(--border-hi); border-radius: 0.375rem;
  background: rgba(18,18,22,0.7); transition: border-color 0.15s, box-shadow 0.15s;
}
html.light .pi-field { background: rgba(255,255,255,0.9); }
.pi-field--focused { border-color: var(--border-focus); box-shadow: 0 0 0 2px rgba(161,161,170,0.1); }
.pi-wrap--error .pi-field { border-color: rgba(239,68,68,0.6); }

.pi-input {
  flex: 1; min-width: 0; background: transparent;
  border: none; outline: none;
  padding: 0.5rem 0.625rem; font-size: 0.8rem; color: var(--text);
  font-family: inherit; resize: vertical;
}
.pi-input::placeholder { color: var(--text-mute); }
.pi-input.has-l { padding-left: 0.375rem; }
.pi-input.has-r { padding-right: 0.375rem; }

.pi-adorn { display: flex; align-items: center; color: var(--text-mute); padding: 0 0.5rem; flex-shrink: 0; }
.pi-error { font-size: 0.7rem; color: #ef4444; }
</style>
