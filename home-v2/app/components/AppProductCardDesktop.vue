<template>
  <div class="dcard">
    <NuxtLink :to="`/products/${product.slug}`" class="dcard__link">

      <!-- Image block -->
      <div class="dcard__media">
        <!-- Badge -->
        <span v-if="product.badge" class="dcard__badge">{{ product.badge }}</span>

        <!-- Product image or placeholder -->
        <div class="dcard__img-wrap">
          <img
            v-if="coverImage"
            :src="coverImage"
            :alt="product.name"
            class="dcard__img"
            :class="{ 'dcard__img--loaded': imgLoaded }"
            @load="imgLoaded = true"
            loading="lazy" />
          <div v-if="!imgLoaded" class="dcard__art" aria-hidden="true">CK</div>
        </div>

        <!-- Quick-add overlay — slides up on hover -->
        <div class="dcard__quick">
          <button
            class="dcard__quick-btn"
            @click.prevent="addToCart"
            :aria-label="`Thêm ${product.name} vào giỏ`">
            <svg class="dcard__quick-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            Thêm vào giỏ
          </button>
        </div>
      </div>

      <!-- Info -->
      <div class="dcard__info">
        <div class="dcard__info-top">
          <div>
            <p class="dcard__brand">Calvin Klein</p>
            <h3 class="dcard__name">{{ product.name }}</h3>
          </div>
          <p class="dcard__price">{{ formatVND(product.priceCents) }}</p>
        </div>
        <!-- Color dots -->
        <div v-if="product.colors?.length" class="dcard__colors">
          <span
            v-for="c in product.colors.slice(0, 4)"
            :key="c.hex ?? c.name"
            class="dcard__color-dot"
            :style="{ background: c.hex ?? '#888' }"
            :title="c.name" />
          <span v-if="product.colors.length > 4" class="dcard__color-more">
            +{{ product.colors.length - 4 }}
          </span>
        </div>
      </div>
    </NuxtLink>

    <!-- Added toast -->
    <Transition name="dcard-toast">
      <div v-if="added" class="dcard__toast" role="status">✓ Đã thêm</div>
    </Transition>
  </div>
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

const cart = useCartStore()
const added = ref(false)
const imgLoaded = ref(false)

const coverImage = computed(() => props.product.colors?.[0]?.images?.[0] ?? null)

function formatVND (cents: number) {
  return (cents / 100).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' })
}

function addToCart () {
  cart.add({ productId: props.product.id, name: props.product.name, priceCents: props.product.priceCents, quantity: 1 })
  added.value = true
  setTimeout(() => { added.value = false }, 1800)
}
</script>

<style scoped>
/* ── Root ────────────────────────────────────────────────────────────── */
.dcard {
  position: relative;
  -webkit-tap-highlight-color: transparent;
}
.dcard__link { display: block; text-decoration: none; color: inherit; }

/* ── Media block ─────────────────────────────────────────────────────── */
.dcard__media {
  position: relative;
  aspect-ratio: 3 / 4;
  border-radius: 0.875rem;
  overflow: hidden;
  border: 1px solid var(--border);
  background-color: var(--bg-card);
  margin-bottom: 0.75rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.dcard:hover .dcard__media {
  border-color: var(--border-hi);
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}

/* Image */
.dcard__img-wrap {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
}
.dcard__img {
  width: 100%; height: 100%;
  object-fit: contain;
  padding: 1rem;
  opacity: 0;
  transition: opacity 0.3s ease, transform 0.4s ease;
}
.dcard__img--loaded { opacity: 1; }
.dcard:hover .dcard__img--loaded { transform: scale(1.03); }

.dcard__art {
  font-family: "Barlow Condensed", sans-serif;
  font-weight: 700; font-size: 4rem;
  letter-spacing: 0.2em; text-transform: uppercase;
  color: var(--border-hi);
  user-select: none;
  transition: color 0.25s ease;
}
.dcard:hover .dcard__art { color: var(--border-focus); }

/* Badge */
.dcard__badge {
  position: absolute; top: 0.75rem; left: 0.75rem; z-index: 2;
  padding: 0.2rem 0.6rem;
  background-color: var(--accent); color: var(--accent-fg);
  font-size: 0.6rem; font-weight: 700;
  letter-spacing: 0.1em; text-transform: uppercase;
  border-radius: 0; /* sharp corners = edgy */
  clip-path: polygon(0 0, 100% 0, 95% 100%, 0 100%);
  padding-right: 0.85rem;
}

/* Quick-add: appears as a thin strip at the bottom */
.dcard__quick {
  position: absolute; bottom: 0; left: 0; right: 0;
  padding: 0 0.75rem 0.75rem;
  z-index: 3;
  transform: translateY(8px);
  opacity: 0;
  transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.32, 0.72, 0, 1);
}
.dcard:hover .dcard__quick { opacity: 1; transform: translateY(0); }

.dcard__quick-btn {
  width: 100%;
  display: flex; align-items: center; justify-content: center; gap: 0.4rem;
  padding: 0.6rem;
  background-color: var(--accent); color: var(--accent-fg);
  font-family: "Be Vietnam Pro", sans-serif;
  font-size: 0.68rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
  border: none; border-radius: 0.375rem; cursor: pointer;
  transition: opacity 0.15s ease;
}
.dcard__quick-btn:hover { opacity: 0.88; }
.dcard__quick-icon { width: 0.8rem; height: 0.8rem; }

/* ── Info row ────────────────────────────────────────────────────────── */
.dcard__info { padding: 0 0.125rem; }
.dcard__info-top {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem;
}

.dcard__brand {
  font-size: 0.62rem; font-weight: 600;
  letter-spacing: 0.15em; text-transform: uppercase;
  color: var(--text-mute); margin-bottom: 0.2rem;
}
.dcard__name {
  font-size: 0.825rem; font-weight: 500; line-height: 1.3;
  color: var(--text); transition: color 0.15s ease;
}
.dcard:hover .dcard__name { color: var(--text-sub); }

.dcard__price {
  font-size: 0.825rem; font-weight: 600;
  color: var(--text); white-space: nowrap; flex-shrink: 0;
}

/* Color dots */
.dcard__colors {
  display: flex; align-items: center; gap: 0.3rem; margin-top: 0.5rem;
}
.dcard__color-dot {
  width: 0.7rem; height: 0.7rem;
  border-radius: 999px;
  border: 1.5px solid var(--border-hi);
  flex-shrink: 0;
  display: inline-block;
}
.dcard__color-more {
  font-size: 0.6rem; color: var(--text-mute); letter-spacing: 0.05em;
}

/* ── Toast ───────────────────────────────────────────────────────────── */
.dcard__toast {
  position: absolute; top: 0.75rem; right: 0.75rem; z-index: 10;
  padding: 0.3rem 0.65rem;
  background-color: var(--bg-raised); color: var(--text);
  border: 1px solid var(--border-hi);
  font-size: 0.68rem; font-weight: 600;
  border-radius: 0.375rem;
  pointer-events: none;
}

.dcard-toast-enter-active, .dcard-toast-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.dcard-toast-enter-from, .dcard-toast-leave-to { opacity: 0; transform: translateY(-4px) scale(0.95); }
</style>
