<template>
  <NuxtLink :to="`/products/${product.slug}`" class="mcard">
    <!-- Image -->
    <div class="mcard__media">
      <span v-if="product.badge" class="mcard__badge">{{ product.badge }}</span>

      <div class="mcard__img-wrap">
        <img
          v-if="coverImage"
          :src="coverImage"
          :alt="product.name"
          class="mcard__img"
          :class="{ 'mcard__img--loaded': imgLoaded }"
          @load="imgLoaded = true"
          loading="lazy" />
        <span v-if="!imgLoaded" class="mcard__art" aria-hidden="true">CK</span>
      </div>
    </div>

    <!-- Info -->
    <div class="mcard__info">
      <p class="mcard__brand">Calvin Klein</p>
      <h3 class="mcard__name">{{ product.name }}</h3>
      <div class="mcard__bottom">
        <p class="mcard__price">{{ formatVND(product.priceCents) }}</p>
        <!-- Color dots -->
        <div v-if="product.colors?.length" class="mcard__colors">
          <span
            v-for="c in product.colors.slice(0, 3)"
            :key="c.hex ?? c.name"
            class="mcard__color-dot"
            :style="{ background: c.hex ?? '#888' }" />
        </div>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
const props = defineProps<{
  product: {
    slug: string
    name: string
    priceCents: number
    badge?: string | null
    id: string
    colors?: Array<{ name: string; hex?: string; images?: string[] }>
  }
}>()

const imgLoaded = ref(false)
const coverImage = computed(() => props.product.colors?.[0]?.images?.[0] ?? null)

function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}
</script>

<style scoped>
.mcard {
  display: block; text-decoration: none; color: inherit;
  -webkit-tap-highlight-color: transparent;
}

.mcard__media {
  position: relative;
  aspect-ratio: 3 / 4;
  background-color: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 0.75rem;
  overflow: hidden;
  margin-bottom: 0.625rem;
  transition: border-color 0.2s ease;
}
.mcard:active .mcard__media { border-color: var(--border-hi); }

.mcard__img-wrap {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
}
.mcard__img {
  width: 100%; height: 100%; object-fit: contain; padding: 0.75rem;
  opacity: 0; transition: opacity 0.3s ease;
}
.mcard__img--loaded { opacity: 1; }

.mcard__art {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 3rem; letter-spacing: 0.2em;
  text-transform: uppercase; color: var(--border-hi);
  user-select: none;
}

.mcard__badge {
  position: absolute; top: 0.5rem; left: 0.5rem; z-index: 1;
  padding: 0.15rem 0.45rem;
  background-color: var(--accent); color: var(--accent-fg);
  font-size: 0.55rem; font-weight: 700;
  letter-spacing: 0.1em; text-transform: uppercase;
  border-radius: 0; clip-path: polygon(0 0, 100% 0, 95% 100%, 0 100%);
  padding-right: 0.65rem;
}

.mcard__info { padding: 0 0.125rem; }
.mcard__brand {
  font-size: 0.6rem; font-weight: 600;
  letter-spacing: 0.15em; text-transform: uppercase;
  color: var(--text-mute); margin-bottom: 0.2rem;
}
.mcard__name {
  font-size: 0.78rem; font-weight: 500; line-height: 1.3;
  color: var(--text); margin-bottom: 0.3rem;
}
.mcard__bottom { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
.mcard__price  { font-size: 0.78rem; font-weight: 600; color: var(--text); }

.mcard__colors { display: flex; gap: 0.25rem; }
.mcard__color-dot {
  width: 0.6rem; height: 0.6rem; border-radius: 999px;
  border: 1.5px solid var(--border-hi); display: inline-block;
}
</style>
