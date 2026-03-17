<template>
  <section class="access" id="access">
    <div class="access-inner">
      <div class="access-header reveal">
        <p class="label light">{{ t("ck.access.label") }}</p>
        <h2 class="access-title">{{ t("ck.access.title") }}</h2>
        <p class="access-sub">{{ t("ck.access.sub") }}</p>
      </div>
      <form v-if="accessState !== 'success'" class="form" @submit.prevent="submitAccess">
        <div class="form-row">
          <div class="form-field">
            <label>{{ t("ck.access.name") }}</label>
            <input v-model="access.name" type="text" :placeholder="t('ck.access.namePlaceholder')" required autocomplete="name" />
          </div>
          <div class="form-field">
            <label>{{ t("ck.access.email") }}</label>
            <input v-model="access.email" type="email" :placeholder="t('ck.access.emailPlaceholder')" required autocomplete="email" />
          </div>
        </div>
        <div class="form-row single">
          <div class="form-field">
            <label>{{ t("ck.access.phone") }} <span class="opt">{{ t("ck.access.optional") }}</span></label>
            <input v-model="access.phone" type="tel" :placeholder="t('ck.access.phonePlaceholder')" autocomplete="tel" />
          </div>
        </div>
        <div v-if="accessState === 'error'" class="form-error">{{ t("common.error") }}</div>
        <button type="submit" class="btn-submit" :disabled="accessState === 'loading'">
          <span v-if="accessState === 'idle' || accessState === 'error'">{{ t("ck.access.submit") }}</span>
          <span v-else class="loading-dots"><span></span><span></span><span></span></span>
        </button>
        <p class="form-fine">{{ t("ck.access.fine") }}</p>
      </form>
      <div v-if="accessState === 'success'" class="success-message">
        <div class="success-icon">✓</div>
        <p class="success-title">{{ t("ck.access.successTitle") }}</p>
        <p class="success-body">{{ t("ck.access.successBody", { code: "DRIIP20" }) }}</p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { storeToRefs } from "pinia";
import { useCkUnderwearStore } from "~/stores/ck-underwear";
const { t } = useI18n();
const store = useCkUnderwearStore();
const { access, accessState } = storeToRefs(store);
const { submitAccess } = store;
</script>

<style scoped>
.access { background: var(--grey-900); padding: 80px 24px 100px; border-top: 1px solid rgba(255, 255, 255, 0.06); }
.access-inner { max-width: 720px; margin: 0 auto; }
.access-header { margin-bottom: 56px; }
.access-title { font-family: var(--font-display); font-size: clamp(44px, 9vw, 76px); line-height: 0.95; letter-spacing: -0.01em; color: var(--white); margin-bottom: 20px; white-space: pre-line; }
.access-sub { font-size: 14px; font-weight: 300; color: var(--grey-400); line-height: 1.7; }
.label { font-size: 10px; font-weight: 600; letter-spacing: 0.3em; color: var(--grey-700); margin-bottom: 16px; display: block; }
.label.light { color: var(--grey-400); }
.form { display: flex; flex-direction: column; }
.form-row { display: grid; grid-template-columns: 1fr; border-top: 1px solid rgba(255, 255, 255, 0.1); }
.form-row.single { border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
.form-field { display: flex; flex-direction: column; padding: 20px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); gap: 8px; }
.form-row.single .form-field { border-bottom: none; }
.form-field label { font-size: 10px; font-weight: 600; letter-spacing: 0.25em; color: var(--grey-400); }
.form-field input { background: transparent; border: none; outline: none; font-family: var(--font-body); font-size: 18px; font-weight: 300; color: var(--white); padding: 4px 0; }
.form-field input::placeholder { color: var(--grey-700); }
.opt { font-size: 9px; color: var(--grey-700); letter-spacing: 0.15em; }
.form-error { margin: 16px 0; padding: 12px 16px; border: 1px solid rgba(255, 80, 80, 0.4); color: #ff6b6b; font-size: 12px; letter-spacing: 0.1em; }
.btn-submit { margin-top: 40px; width: 100%; background: var(--white); color: var(--black); border: none; padding: 20px; font-family: var(--font-body); font-size: 13px; font-weight: 600; letter-spacing: 0.2em; cursor: pointer; transition: background 0.2s, opacity 0.2s; }
.btn-submit:hover:not(:disabled) { background: var(--off-white); }
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
.form-fine { margin-top: 16px; font-size: 10px; color: var(--grey-700); text-align: center; letter-spacing: 0.05em; line-height: 1.6; }
.loading-dots { display: inline-flex; align-items: center; gap: 4px; }
.loading-dots span { width: 5px; height: 5px; background: var(--black); border-radius: 50%; animation: bounce 0.6s infinite alternate; }
.loading-dots span:nth-child(2) { animation-delay: 0.2s; }
.loading-dots span:nth-child(3) { animation-delay: 0.4s; }
.success-message { margin-top: 56px; padding: 48px; border: 1px solid rgba(255, 255, 255, 0.1); text-align: center; animation: fadeIn 0.4s ease; }
.success-icon { font-size: 32px; margin-bottom: 16px; }
.success-title { font-family: var(--font-display); font-size: 36px; letter-spacing: 0.1em; color: var(--white); margin-bottom: 16px; }
.success-body { font-size: 14px; font-weight: 300; color: var(--grey-400); line-height: 1.8; }
.reveal { opacity: 0; transform: translateY(28px); transition: opacity 0.75s ease, transform 0.75s ease; }
.reveal.is-visible { opacity: 1; transform: translateY(0); }
@keyframes bounce { from { opacity: 0.3; transform: translateY(0); } to { opacity: 1; transform: translateY(-4px); } }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
@media (min-width: 640px) { .form-row:not(.single) { grid-template-columns: 1fr 1fr; } .form-row:not(.single) .form-field { border-bottom: none; } .form-row:not(.single) .form-field:first-child { border-right: 1px solid rgba(255, 255, 255, 0.1); padding-right: 32px; } .form-row:not(.single) .form-field:last-child { padding-left: 32px; } }
@media (min-width: 1024px) { .access { padding: 100px 64px 120px; } }
</style>
