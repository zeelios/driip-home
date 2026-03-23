<template>
  <main class="panel-home">
    <section class="panel-home__card">
      <p class="panel-home__eyebrow">Panel Session</p>
      <h1 class="panel-home__title">{{ auth.user?.name }}</h1>
      <p class="panel-home__meta">
        <span>{{ auth.user?.email }}</span>
        <span v-if="auth.user?.department">· {{ auth.user.department }}</span>
        <span v-if="auth.user?.position">· {{ auth.user.position }}</span>
      </p>

      <dl class="panel-home__details">
        <div>
          <dt>Status</dt>
          <dd>{{ auth.status }}</dd>
        </div>
        <div>
          <dt>Employee Code</dt>
          <dd>{{ auth.user?.employee_code || "N/A" }}</dd>
        </div>
        <div>
          <dt>Roles</dt>
          <dd>{{ auth.user?.roles?.join(", ") || "N/A" }}</dd>
        </div>
      </dl>

      <p v-if="auth.error" class="panel-home__error">{{ auth.error }}</p>

      <button
        class="panel-home__logout"
        :disabled="auth.logoutPending"
        @click="signOut"
      >
        {{ auth.logoutPending ? "Signing out..." : "Sign out" }}
      </button>
    </section>
  </main>
</template>

<script setup lang="ts">
import { useAuthStore } from "~/stores/auth";

const auth = useAuthStore();

async function signOut(): Promise<void> {
  const redirect = await auth.logout();
  await navigateTo(redirect.path);
}
</script>

<style scoped>
.panel-home {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 2rem;
  background:
    radial-gradient(circle at top left, rgba(215, 177, 116, 0.18), transparent 30%),
    linear-gradient(135deg, #f8f4ec 0%, #efe5d2 45%, #ded3bd 100%);
}

.panel-home__card {
  width: min(100%, 42rem);
  padding: 2rem;
  border: 1px solid rgba(62, 43, 27, 0.12);
  background: rgba(255, 252, 246, 0.92);
  box-shadow: 0 24px 60px rgba(62, 43, 27, 0.12);
}

.panel-home__eyebrow {
  margin: 0 0 0.75rem;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #8b6a42;
}

.panel-home__title {
  margin: 0;
  font-size: clamp(2rem, 4vw, 3rem);
  line-height: 1;
  color: #2f2115;
}

.panel-home__meta {
  margin: 0.75rem 0 0;
  color: #5d4c3b;
}

.panel-home__details {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1rem;
  margin: 2rem 0;
}

.panel-home__details dt {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8b6a42;
}

.panel-home__details dd {
  margin: 0.35rem 0 0;
  color: #2f2115;
}

.panel-home__error {
  margin: 0 0 1rem;
  color: #9f2d2d;
}

.panel-home__logout {
  border: 0;
  padding: 0.9rem 1.2rem;
  font: inherit;
  font-weight: 700;
  color: #fffaf0;
  background: #2f2115;
  cursor: pointer;
}

.panel-home__logout:disabled {
  opacity: 0.6;
  cursor: wait;
}

@media (max-width: 720px) {
  .panel-home__details {
    grid-template-columns: 1fr;
  }
}
</style>
