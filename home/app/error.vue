<template>
  <div class="error-shell">
    <div class="error-card">
      <p class="error-label">status code</p>
      <p class="error-code">{{ statusCode }}</p>
      <h1 class="error-title">{{ title }}</h1>
      <p class="error-description">{{ description }}</p>
      <p class="error-message" v-if="fallbackMessage">{{ fallbackMessage }}</p>
      <div class="error-actions">
        <button class="btn-primary" @click="handlePrimary">{{ primaryLabel }}</button>
        <button class="btn-link" @click="handleSecondary">Contact Zeelios</button>
      </div>
      <p class="error-credit">crafted with care by Zeelios · {{ statusCodeMessage }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ error: { statusCode?: number; message?: string } }>();
const router = useRouter();
const statusCode = props.error?.statusCode ?? 500;
const fallbackMessage = props.error?.message ?? "";
const isClientError = statusCode >= 400 && statusCode < 500;
const title = isClientError
  ? "Looks like that route escaped the drop."
  : "The crew is resetting the stage.";
const description = isClientError
  ? "This page is either gone or hiding behind new routes."
  : "Server signal interrupted — we’re rebooting the experience.";
const primaryLabel = isClientError ? "Return home" : "Retry now";
const statusCodeMessage = isClientError
  ? "client glitch" 
  : "server moment";

function handlePrimary() {
  router.push("/");
}

function handleSecondary() {
  window.open("mailto:zeelios@driip.com", "_blank");
}
</script>

<style scoped>
.error-shell {
  min-height: 100dvh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: radial-gradient(circle at top, rgba(255,255,255,0.08), transparent 55%), #000;
  padding: 2rem;
}
.error-card {
  width: min(480px, 100%);
  text-align: center;
  padding: 3rem 2.5rem;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(0,0,0,0.8);
  box-shadow: 0 24px 60px rgba(0,0,0,0.45);
  border-radius: 24px;
}
.error-label {
  letter-spacing: 0.45em;
  font-size: 0.75rem;
  color: var(--grey-400);
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}
.error-code {
  font-size: clamp(96px, 18vw, 180px);
  font-family: var(--font-display);
  margin: 0;
  color: var(--white);
}
.error-title {
  font-family: var(--font-display);
  margin: 1rem 0;
  font-size: clamp(24px, 4vw, 36px);
  color: var(--white);
}
.error-description,
.error-message {
  color: var(--grey-400);
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 0.75rem;
}
.error-actions {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin: 1.5rem 0;
}
.btn-primary {
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 0.9rem 2rem;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  cursor: pointer;
}
.btn-link {
  background: transparent;
  border: 1px solid rgba(255,255,255,0.4);
  color: var(--grey-100);
  padding: 0.9rem 1.8rem;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  cursor: pointer;
}
.error-credit {
  font-size: 0.75rem;
  letter-spacing: 0.25em;
  color: var(--grey-700);
  margin-top: 1rem;
}
@media (max-width: 640px) {
  .error-card {
    padding: 2rem;
  }
}
</style>
