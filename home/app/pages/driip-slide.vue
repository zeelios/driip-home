<template>
  <div class="slide-page">
    <!-- ── HERO WITH DYNAMIC COLOR BACKGROUND ───────────────────────── -->
    <section class="slide-hero" id="hero">
      <div class="slide-hero-bg">
        <!-- Dynamic background based on selected color -->
        <div
          class="slide-hero-bg-layer slide-hero-bg-pink"
          :class="{ active: selectedColor === 'hot-pink' }"
        >
          <NuxtImg
            src="/products/dSlide/pink-1.jpg"
            alt="Hot Pink Driip Slide"
            width="1920"
            height="1080"
            class="slide-hero-img"
            loading="eager"
          />
          <div class="slide-hero-overlay slide-hero-overlay--pink" />
        </div>
        <div
          class="slide-hero-bg-layer slide-hero-bg-blue"
          :class="{ active: selectedColor === 'cyan-blue' }"
        >
          <NuxtImg
            src="/products/dSlide/blue-1.jpg"
            alt="Cyan Blue Driip Slide"
            width="1920"
            height="1080"
            class="slide-hero-img"
            loading="eager"
          />
          <div class="slide-hero-overlay slide-hero-overlay--blue" />
        </div>
        <div
          class="slide-hero-bg-layer slide-hero-bg-default"
          :class="{ active: !selectedColor }"
        >
          <NuxtImg
            src="/products/dSlide/master.jpg"
            alt="Driip Slide Collection"
            width="1920"
            height="1080"
            class="slide-hero-img"
            loading="eager"
          />
          <div class="slide-hero-overlay" />
        </div>
      </div>

      <div class="slide-hero-content">
        <div class="slide-hero-inner">
          <div class="slide-hero-copy">
            <p class="slide-eyebrow reveal">{{ t("slide.hero.eyebrow") }}</p>
            <p class="slide-pre reveal">{{ t("slide.hero.pre") }}</p>

            <h1 class="slide-title reveal">
              <span
                v-for="(line, i) in heroTitleLines"
                :key="i"
                class="slide-title-line"
                :class="{ 'slide-title-line--muted': i === 1 }"
                >{{ line }}</span
              >
            </h1>

            <p class="slide-sub reveal">{{ t("slide.hero.sub") }}</p>

            <!-- Selected Color Preview -->
            <div v-if="selectedColor" class="slide-hero-color-preview reveal">
              <div
                class="slide-color-preview-swatch"
                :class="`slide-color-preview-swatch--${selectedColor}`"
              />
              <span class="slide-color-preview-name">
                {{ selectedColorLabel }}
              </span>
            </div>

            <!-- Experience Pricing -->
            <div class="slide-hero-pricing reveal">
              <div class="slide-pricing-context">
                <span class="slide-pricing-tag">GIÁ TRẢI NGHIỆM</span>
                <span class="slide-pricing-normal"
                  >Giá gốc: <s>480.000đ</s>/đôi</span
                >
              </div>
              <div class="slide-pricing-tiers">
                <div class="slide-pricing-item">
                  <span class="slide-pricing-label">1 ĐÔI</span>
                  <span class="slide-pricing-value">286.000đ</span>
                </div>
                <div class="slide-pricing-divider" />
                <div class="slide-pricing-item slide-pricing-item--highlight">
                  <span class="slide-pricing-label">2 ĐÔI</span>
                  <span class="slide-pricing-value">500.000đ</span>
                  <span class="slide-pricing-save">Tiết kiệm 72.000đ</span>
                </div>
              </div>
              <!-- Countdown to April 20 price revert -->
              <div v-if="!dealExpired" class="slide-countdown">
                <span class="slide-countdown-label">Hoàn giá 480k sau</span>
                <div class="slide-countdown-clock">
                  <div class="slide-countdown-unit">
                    <span class="slide-countdown-num">{{ pad(days) }}</span>
                    <span class="slide-countdown-seg">NGÀY</span>
                  </div>
                  <span class="slide-countdown-colon">:</span>
                  <div class="slide-countdown-unit">
                    <span class="slide-countdown-num">{{ pad(hours) }}</span>
                    <span class="slide-countdown-seg">GIỜ</span>
                  </div>
                  <span class="slide-countdown-colon">:</span>
                  <div class="slide-countdown-unit">
                    <span class="slide-countdown-num">{{ pad(minutes) }}</span>
                    <span class="slide-countdown-seg">PHÚT</span>
                  </div>
                  <span class="slide-countdown-colon">:</span>
                  <div class="slide-countdown-unit">
                    <span class="slide-countdown-num">{{ pad(seconds) }}</span>
                    <span class="slide-countdown-seg">GIÂY</span>
                  </div>
                </div>
              </div>
              <div v-else class="slide-countdown slide-countdown--expired">
                <span class="slide-countdown-label"
                  >Giá trải nghiệm đã kết thúc — giá hiện tại:
                  480.000đ/đôi</span
                >
              </div>
            </div>

            <div class="slide-hero-warranty reveal">
              <span class="slide-warranty-badge"
                >✓ {{ t("slide.products.warranty") }}</span
              >
            </div>

            <div class="slide-hero-actions reveal">
              <button
                class="btn-primary btn-glow"
                @click="scrollToSection('products')"
              >
                {{ t("slide.hero.cta") }} <span aria-hidden="true">↓</span>
              </button>
              <NuxtLinkLocale to="/" class="btn-ghost">
                {{ t("slide.hero.secondary") }}
              </NuxtLinkLocale>
            </div>
          </div>
        </div>
      </div>

      <div class="slide-hero-scroll" aria-hidden="true">
        <span class="slide-scroll-line" />
      </div>
    </section>

    <!-- ── TICKER STRIP ───────────────────────────────────────────── -->
    <div class="slide-ticker" aria-hidden="true">
      <div class="slide-ticker-track">
        <span v-for="n in 6" :key="n" class="slide-ticker-set">
          <span>{{ t("slide.strip.eva") }}</span>
          <span class="slide-ticker-sep">—</span>
          <span>{{ t("slide.strip.antislip") }}</span>
          <span class="slide-ticker-sep">—</span>
          <span>{{ t("slide.strip.ergonomic") }}</span>
          <span class="slide-ticker-sep">—</span>
          <span>{{ t("slide.strip.crocs") }}</span>
          <span class="slide-ticker-sep">—</span>
        </span>
      </div>
    </div>

    <!-- ── PRODUCT SELECTION ───────────────────────────────────────── -->
    <section class="slide-products" id="products">
      <div class="slide-products-inner">
        <p class="slide-section-label reveal">
          {{ t("slide.products.label") }}
        </p>
        <h2 class="slide-products-title reveal">
          {{ t("slide.products.title") }}
        </h2>
        <p class="slide-products-sub reveal">
          {{ t("slide.products.choose") }}
        </p>

        <div class="slide-products-grid reveal">
          <!-- Color Selection -->
          <div class="slide-select-block">
            <p class="slide-select-label">{{ t("slide.products.color") }}</p>
            <div class="slide-colors">
              <button
                v-for="color in store.colorOptions"
                :key="color.value"
                type="button"
                class="slide-color-btn"
                :class="{ active: store.draft.color === color.value }"
                @click="store.setDraftColor(color.value)"
              >
                <span
                  class="slide-color-swatch"
                  :class="`slide-color-swatch--${color.value}`"
                />
                <span class="slide-color-name">{{ color.label }}</span>
                <span class="slide-color-sizes">{{
                  color.sizes.join(", ")
                }}</span>
              </button>
            </div>

            <!-- Mobile Color Preview - shows selected color image -->
            <div v-if="selectedColor" class="slide-color-preview-mobile">
              <div class="slide-preview-img-wrap">
                <NuxtImg
                  :src="selectedColorImage"
                  :alt="selectedColorLabel"
                  width="600"
                  height="400"
                  class="slide-preview-img"
                  loading="lazy"
                />
              </div>
              <p class="slide-preview-caption">{{ selectedColorLabel }}</p>
            </div>
          </div>

          <!-- Size Selection -->
          <div class="slide-select-block">
            <div class="slide-select-header">
              <p class="slide-select-label">{{ t("slide.products.size") }}</p>
              <button
                type="button"
                class="slide-size-guide"
                @click="sizeGuideOpen = true"
              >
                Size guide
              </button>
            </div>
            <div class="slide-sizes">
              <button
                v-for="size in store.availableSizes"
                :key="size"
                type="button"
                class="slide-size-btn"
                :class="{ active: store.draft.size === size }"
                :disabled="!store.draft.color"
                @click="store.setDraftSize(size)"
              >
                {{ size }}
              </button>
            </div>
            <p v-if="!store.draft.color" class="slide-select-hint">
              {{ t("slide.products.choose") }}
            </p>
          </div>

          <!-- Quantity Selection -->
          <div class="slide-select-block">
            <p class="slide-select-label">{{ t("slide.products.quantity") }}</p>
            <div class="slide-quantity-options">
              <button
                type="button"
                class="slide-qty-btn"
                :class="{ active: store.draft.quantity === 1 }"
                @click="store.setDraftQuantity(1)"
              >
                <span class="slide-qty-count">1</span>
                <span class="slide-qty-price">286.000đ</span>
              </button>
              <button
                type="button"
                class="slide-qty-btn slide-qty-btn--best"
                :class="{ active: store.draft.quantity === 2 }"
                @click="store.setDraftQuantity(2)"
              >
                <span class="slide-qty-badge">BEST</span>
                <span class="slide-qty-count">2</span>
                <span class="slide-qty-price">500.000đ</span>
                <span class="slide-qty-save">Tiết kiệm 72.000đ</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Add to Cart Button -->
        <div class="slide-atc reveal">
          <Transition name="slide-added">
            <p v-if="added" class="slide-atc-msg">
              {{ t("slide.products.added") }}
            </p>
          </Transition>
          <button
            class="btn-atc"
            :class="{ 'btn-atc--ready': store.draftValid }"
            :disabled="!store.draftValid"
            @click="addToCart"
          >
            <span v-if="store.draftValid">{{
              t("slide.products.addReady")
            }}</span>
            <span v-else>{{ t("slide.products.addToCart") }}</span>
          </button>
        </div>
      </div>
    </section>

    <!-- ── GALLERY / LOOKBOOK ─────────────────────────────────────────── -->
    <section id="gallery" class="slide-gallery reveal">
      <div class="slide-gallery-header">
        <p class="slide-gallery-pre">{{ t("slide.gallery.label") }}</p>
        <h2 class="slide-gallery-title">{{ t("slide.gallery.title") }}</h2>
        <p class="slide-gallery-sub">{{ t("slide.gallery.sub") }}</p>
        <button
          class="slide-gallery-cta"
          type="button"
          @click="scrollToSection('products')"
        >
          {{ t("slide.gallery.cta") }}
        </button>
      </div>

      <!-- Color Tabs -->
      <div class="slide-gallery-tabs">
        <button
          class="slide-gallery-tab"
          :class="{ active: galleryTab === 'pink' }"
          @click="galleryTab = 'pink'"
        >
          {{ t("slide.gallery.tabPink") }}
        </button>
        <button
          class="slide-gallery-tab"
          :class="{ active: galleryTab === 'blue' }"
          @click="galleryTab = 'blue'"
        >
          {{ t("slide.gallery.tabBlue") }}
        </button>
      </div>

      <!-- Masonry Grid -->
      <Transition name="tab-fade" mode="out-in">
        <div :key="galleryTab" class="slide-masonry">
          <div
            v-for="(item, i) in galleryItems"
            :key="item.src"
            class="slide-masonry-item"
            :class="[`m-item-${i}`, item.span]"
            @click="openLightbox(i)"
          >
            <NuxtImg
              :src="item.src"
              :alt="item.alt"
              :width="item.w"
              :height="item.h"
              class="slide-masonry-img"
              loading="lazy"
              format="webp"
            />
            <div class="slide-masonry-overlay">
              <span class="slide-masonry-label">{{ item.label }}</span>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Lightbox -->
      <Teleport to="body">
        <Transition name="lb-fade">
          <div
            v-if="lightboxIndex !== null"
            class="slide-lightbox"
            @click.self="closeLightbox"
            @touchstart="onLightboxTouchStart"
            @touchmove="onLightboxTouchMove"
            @touchend="onLightboxTouchEnd"
          >
            <button
              class="slide-lb-back"
              aria-label="Back"
              @click="closeLightbox"
            >
              BACK
            </button>
            <button
              class="slide-lb-close"
              aria-label="Close"
              @click="closeLightbox"
            >
              ✕
            </button>
            <button
              class="slide-lb-prev"
              aria-label="Previous"
              @click="lightboxPrev"
            >
              ‹
            </button>
            <div class="slide-lb-wrap">
              <NuxtImg
                :src="activeLightboxItem?.src"
                :alt="activeLightboxItem?.alt"
                width="1200"
                class="slide-lb-img"
                format="webp"
              />
              <p class="slide-lb-caption">{{ activeLightboxItem?.label }}</p>
            </div>
            <button
              class="slide-lb-next"
              aria-label="Next"
              @click="lightboxNext"
            >
              ›
            </button>
          </div>
        </Transition>
      </Teleport>
    </section>

    <!-- ── CHECKOUT SECTION ─────────────────────────────────────────── -->
    <section class="slide-checkout" id="checkout">
      <div class="slide-checkout-inner">
        <!-- Header -->
        <div class="slide-checkout-head reveal">
          <p class="slide-section-label">{{ t("slide.order.label") }}</p>
          <h2 class="slide-checkout-title">{{ t("slide.order.title") }}</h2>
          <p class="slide-checkout-sub">{{ t("slide.order.sub") }}</p>
        </div>

        <!-- Success State -->
        <div v-if="store.orderState === 'success'" class="slide-success reveal">
          <!-- Celebration Animation -->
          <div class="slide-success-celebration">
            <span
              v-for="n in 6"
              :key="n"
              class="slide-confetti"
              :class="`c${n}`"
              >✦</span
            >
          </div>

          <!-- Success Icon with Ring -->
          <div class="slide-success-ring">
            <div class="slide-success-icon">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                <path
                  d="M20 6L9 17L4 12"
                  stroke="currentColor"
                  stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </div>
          </div>

          <!-- Title & Message -->
          <p class="slide-success-title">{{ t("slide.order.successTitle") }}</p>
          <p class="slide-success-body">
            {{
              t("slide.order.successMessage", {
                name: `${store.order.firstName} ${store.order.lastName}`,
              })
            }}
          </p>

          <!-- Order Summary Card -->
          <div class="slide-order-card">
            <div class="slide-order-card-header">
              <span class="slide-order-card-label">{{
                t("slide.order.orderSummary")
              }}</span>
              <span class="slide-order-card-id">#{{ orderId }}</span>
            </div>
            <div class="slide-order-items">
              <div
                v-for="item in store.items"
                :key="item.id"
                class="slide-order-item"
              >
                <div class="slide-order-item-info">
                  <span class="slide-order-item-name">Driip Slide</span>
                  <span class="slide-order-item-meta"
                    >Size {{ item.size }} · {{ item.quantity }}
                    {{ item.quantity > 1 ? "pairs" : "pair" }}</span
                  >
                </div>
                <span class="slide-order-item-price">{{
                  formatVnd(item.price)
                }}</span>
              </div>
            </div>
            <div class="slide-order-total">
              <span>{{ t("slide.cart.total") }}</span>
              <span class="slide-order-total-value">{{
                store.formattedGrandTotal
              }}</span>
            </div>
          </div>

          <!-- Next Steps -->
          <div class="slide-success-steps">
            <div class="slide-step">
              <div class="slide-step-icon">1</div>
              <p class="slide-step-text">{{ t("slide.order.stepConfirm") }}</p>
            </div>
            <div class="slide-step-arrow">→</div>
            <div class="slide-step">
              <div class="slide-step-icon">2</div>
              <p class="slide-step-text">{{ t("slide.order.stepContact") }}</p>
            </div>
            <div class="slide-step-arrow">→</div>
            <div class="slide-step">
              <div class="slide-step-icon">3</div>
              <p class="slide-step-text">{{ t("slide.order.stepDelivery") }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="slide-success-actions">
            <NuxtLinkLocale to="/" class="btn-primary btn-glow">
              {{ t("slide.order.backHome") }}
            </NuxtLinkLocale>
            <button type="button" class="btn-ghost" @click="store.resetOrder()">
              {{ t("slide.order.newOrder") }}
            </button>
          </div>
        </div>

        <!-- Checkout Form -->
        <form
          v-else
          class="slide-checkout-form"
          novalidate
          @submit.prevent="handleSubmit"
        >
          <!-- Step Progress -->
          <div class="slide-progress reveal">
            <div
              v-for="n in 3"
              :key="n"
              class="slide-progress-step"
              :class="{
                active: store.currentStep === n,
                done: store.currentStep > n,
              }"
            >
              <div class="slide-progress-dot">
                <span v-if="store.currentStep > n">✓</span>
                <span v-else>{{ n }}</span>
              </div>
              <span class="slide-progress-label">{{
                t(`slide.order.step${n}`)
              }}</span>
            </div>
            <div class="slide-progress-track">
              <div
                class="slide-progress-fill"
                :style="{ width: progressWidth }"
              />
            </div>
          </div>

          <!-- STEP 1: CART -->
          <div v-show="store.currentStep === 1" class="slide-panel reveal">
            <!-- Empty Cart -->
            <div v-if="store.isEmpty" class="slide-empty-cart">
              <p class="slide-empty-icon">🛒</p>
              <p class="slide-empty-title">{{ t("slide.cart.empty") }}</p>
              <p class="slide-empty-sub">{{ t("slide.cart.emptySub") }}</p>
              <button
                type="button"
                class="btn-ghost"
                @click="scrollToSection('products')"
              >
                {{ t("slide.cart.continueShopping") }}
              </button>
            </div>

            <!-- Cart Items -->
            <template v-else>
              <div class="slide-cart-list">
                <div
                  v-for="item in store.items"
                  :key="item.id"
                  class="slide-cart-item"
                >
                  <div class="slide-cart-info">
                    <p class="slide-cart-name">
                      Driip Slide {{ item.colorLabel }}
                    </p>
                    <div class="slide-cart-meta">
                      Size {{ item.size }} · {{ item.quantity }} đôi
                    </div>
                  </div>

                  <div class="slide-cart-qty">
                    <button
                      type="button"
                      class="slide-cart-qty-btn"
                      :disabled="item.quantity <= 1"
                      @click="store.decreaseQuantity(item.id)"
                    >
                      −
                    </button>
                    <span class="slide-cart-qty-val">{{ item.quantity }}</span>
                    <button
                      type="button"
                      class="slide-cart-qty-btn"
                      @click="store.increaseQuantity(item.id)"
                    >
                      +
                    </button>
                  </div>

                  <div class="slide-cart-price">
                    {{ formatVnd(item.price) }}
                  </div>

                  <button
                    type="button"
                    class="slide-cart-remove"
                    @click="store.removeItem(item.id)"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <!-- Cart Total -->
              <div class="slide-cart-total">
                <div class="slide-cart-total-row">
                  <span>{{ t("slide.cart.total") }}</span>
                  <span class="slide-cart-total-value">{{
                    store.formattedGrandTotal
                  }}</span>
                </div>
                <div v-if="savings > 0" class="slide-cart-savings">
                  {{ t("slide.cart.save") }} {{ formatVnd(savings) }}
                </div>
              </div>

              <button
                type="button"
                class="btn-primary btn-full"
                @click="store.currentStep = 2"
              >
                {{ t("slide.cart.next") }}
              </button>
            </template>
          </div>

          <!-- STEP 2: SHIPPING INFO -->
          <div v-show="store.currentStep === 2" class="slide-panel reveal">
            <div class="slide-field-row">
              <div class="slide-field">
                <label>{{ t("slide.order.firstName") }}</label>
                <input
                  v-model="store.order.firstName"
                  type="text"
                  :placeholder="t('slide.order.firstNamePlaceholder')"
                  required
                />
              </div>
              <div class="slide-field">
                <label>{{ t("slide.order.lastName") }}</label>
                <input
                  v-model="store.order.lastName"
                  type="text"
                  :placeholder="t('slide.order.lastNamePlaceholder')"
                  required
                />
              </div>
            </div>

            <div class="slide-field">
              <label>{{ t("slide.order.phone") }}</label>
              <input
                v-model="store.order.phone"
                type="tel"
                :placeholder="t('slide.order.phonePlaceholder')"
                inputmode="numeric"
                maxlength="13"
                required
                @input="
                  store.normalizePhoneInput(
                    ($event.target as HTMLInputElement).value
                  )
                "
              />
              <p v-if="store.phoneValidationMsg" class="slide-field-error">
                {{ store.phoneValidationMsg }}
              </p>
            </div>

            <div class="slide-field">
              <label>{{ t("slide.order.email") }}</label>
              <input
                v-model="store.order.email"
                type="email"
                :placeholder="t('slide.order.emailPlaceholder')"
              />
            </div>

            <div class="slide-field">
              <label>{{ t("slide.order.province") }}</label>
              <ZSelect
                v-model="store.order.province"
                :placeholder="t('slide.order.provincePlaceholder')"
                :search-placeholder="t('slide.order.provincePlaceholder')"
                :options="provinceOptions"
              />
            </div>

            <div class="slide-field">
              <label>{{ t("slide.order.address") }}</label>
              <input
                v-model="store.order.fullAddress"
                type="text"
                :placeholder="t('slide.order.addressPlaceholder')"
                required
              />
            </div>

            <div class="slide-field-row">
              <div class="slide-field">
                <label>
                  {{ t("slide.order.dob") }}
                  <span class="slide-field-optional">{{
                    t("slide.order.optional")
                  }}</span>
                </label>
                <input
                  v-model="store.order.dob"
                  type="text"
                  :placeholder="t('slide.order.dobPlaceholder')"
                  inputmode="numeric"
                  maxlength="10"
                />
              </div>
              <div class="slide-field">
                <label>
                  {{ t("slide.order.gender") }}
                  <span class="slide-field-optional">{{
                    t("slide.order.optional")
                  }}</span>
                </label>
                <div class="slide-gender-row">
                  <button
                    type="button"
                    class="slide-gender-btn"
                    :class="{ active: store.order.gender === 'male' }"
                    @click="
                      store.order.gender =
                        store.order.gender === 'male' ? '' : 'male'
                    "
                  >
                    {{ t("slide.order.male") }}
                  </button>
                  <button
                    type="button"
                    class="slide-gender-btn"
                    :class="{ active: store.order.gender === 'female' }"
                    @click="
                      store.order.gender =
                        store.order.gender === 'female' ? '' : 'female'
                    "
                  >
                    {{ t("slide.order.female") }}
                  </button>
                </div>
              </div>
            </div>

            <div class="slide-panel-actions">
              <button
                type="button"
                class="btn-ghost"
                @click="store.currentStep = 1"
              >
                {{ t("slide.order.back") }}
              </button>
              <button
                type="button"
                class="btn-primary"
                :disabled="!store.step2Valid"
                @click="store.currentStep = 3"
              >
                {{ t("slide.order.review") }}
              </button>
            </div>
          </div>

          <!-- STEP 3: REVIEW & CONFIRM -->
          <div v-show="store.currentStep === 3" class="slide-panel reveal">
            <div class="slide-review">
              <p class="slide-review-heading">
                {{ t("slide.order.cartSummary") }}
              </p>
              <div
                v-for="item in store.items"
                :key="item.id"
                class="slide-review-row"
              >
                <div class="slide-review-row">
                  <span
                    >Driip Slide {{ item.colorLabel }} · Size {{ item.size }} EU
                    · {{ item.quantity }} đôi</span
                  >
                  <span>{{ formatVnd(item.price) }}</span>
                </div>
                <div class="slide-review-divider" />
              </div>
              <div class="slide-review-row slide-review-total">
                <span>{{ t("slide.cart.total") }}</span>
                <span>{{ store.formattedGrandTotal }}</span>
              </div>

              <p class="slide-review-heading" style="margin-top: 24px">
                {{ t("slide.order.shippingInfo") }}
              </p>
              <div class="slide-review-row">
                <span
                  >{{ store.order.firstName }} {{ store.order.lastName }}</span
                >
              </div>
              <div class="slide-review-row">
                <span>{{ store.order.phone }}</span>
              </div>
              <div class="slide-review-row">
                <span
                  >{{ store.order.fullAddress }},
                  {{ store.order.province }}</span
                >
              </div>
              <div v-if="store.order.dob" class="slide-review-row">
                <span>{{ t("slide.order.dob") }}: {{ store.order.dob }}</span>
              </div>
            </div>

            <div v-if="store.orderState === 'error'" class="slide-error">
              {{ t("common.error") }}
            </div>

            <button
              type="submit"
              class="btn-primary btn-full btn-large"
              :disabled="store.orderState === 'loading'"
            >
              <span v-if="store.orderState !== 'loading'">
                {{
                  t("slide.order.placeOrder", {
                    price: store.formattedGrandTotal,
                  })
                }}
              </span>
              <span v-else class="slide-loading">...</span>
            </button>

            <p class="slide-fine">{{ t("slide.order.fine") }}</p>

            <button
              type="button"
              class="btn-text"
              @click="store.currentStep = 2"
            >
              {{ t("slide.order.edit") }}
            </button>
          </div>
        </form>
      </div>
    </section>

    <SharedSiteFooter />

    <SlideSizeGuide v-model:open="sizeGuideOpen" />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: "default" });

import { computed, ref, watch } from "vue";
import { useMetaEvents } from "~/composables/useMetaEvents";
import { useCountdown } from "~/composables/useCountdown";
import { useDriipSlideStore } from "~/stores/driip-slide";
import { useSiteNavStore } from "~/stores/site-nav";
import { vietnamProvinces } from "~/data/vietnam-addresses";

// Experience pricing ends 20 Apr 2026 00:00 UTC+7
const DEAL_END = new Date("2026-04-19T17:00:00.000Z");
const {
  days,
  hours,
  minutes,
  seconds,
  pad,
  isExpired: dealExpired,
} = useCountdown(DEAL_END);

const { locale, t, mergeLocaleMessage } = useI18n();

// Preload all page translation modules
const slideTranslations = import.meta.glob(
  "../../i18n/locales/pages/slide.*.json"
);

// Load page-specific translations (SSR-safe)
await useAsyncData(`slide-i18n-${locale.value}`, async () => {
  const currentLocale = locale.value;
  const modulePath = `../../i18n/locales/pages/slide.${currentLocale}.json`;
  const loader = slideTranslations[modulePath];
  if (loader) {
    const messages = (await loader()) as { default: Record<string, unknown> };
    mergeLocaleMessage(currentLocale, messages.default);
  }
  return true;
});

const { setupScrollDepth, trackPageView } = useMetaEvents();
const store = useDriipSlideStore();
const siteNavStore = useSiteNavStore();

// Navigation setup
watchEffect(() => {
  siteNavStore.setNav({
    title: "DRIIP SLIDE",
    links: [
      { id: "products", label: t("slide.products.label") },
      { id: "gallery", label: t("slide.gallery.label") },
      { id: "checkout", label: t("slide.order.label") },
    ],
    ctaLabel: t("slide.hero.cta"),
    ctaTarget: "products",
  });
});

watch(
  () => siteNavStore.scrollRequest,
  (id) => {
    if (id) {
      scrollToSection(id);
      siteNavStore.clearScrollRequest();
    }
  }
);

// Computed
const heroTitleLines = computed(() => t("slide.hero.title").split("\n"));

const provinceOptions = computed(() =>
  vietnamProvinces.map((p) => ({ value: p.name, label: p.name }))
);

const added = ref(false);
const sizeGuideOpen = ref(false);

// Gallery state
const galleryTab = ref<"pink" | "blue">("pink");
const lightboxIndex = ref<number | null>(null);
const touchStartX = ref<number | null>(null);
const touchStartY = ref<number | null>(null);
const touchLastX = ref<number | null>(null);
const touchLastY = ref<number | null>(null);

const pinkItems = [
  {
    src: "/products/dSlide/pink-1.jpg",
    alt: "Hot Pink Driip Slide",
    label: "HOT PINK",
    span: "tall" as const,
    w: 800,
    h: 1200,
  },
  {
    src: "/products/dSlide/pink-2.jpg",
    alt: "Pink Detail Shot",
    label: "DETAIL",
    span: "square" as const,
    w: 800,
    h: 800,
  },
  {
    src: "/products/dSlide/pink-3.jpg",
    alt: "Pink Side View",
    label: "SIDE VIEW",
    span: "wide" as const,
    w: 1200,
    h: 800,
  },
];

const blueItems = [
  {
    src: "/products/dSlide/blue-1.jpg",
    alt: "Cyan Blue Driip Slide",
    label: "CYAN BLUE",
    span: "tall" as const,
    w: 800,
    h: 1200,
  },
  {
    src: "/products/dSlide/blue-2.jpg",
    alt: "Blue Detail Shot",
    label: "DETAIL",
    span: "square" as const,
    w: 800,
    h: 800,
  },
  {
    src: "/products/dSlide/blue-3.jpg",
    alt: "Blue Side View",
    label: "SIDE VIEW",
    span: "wide" as const,
    w: 1200,
    h: 800,
  },
];

const galleryItems = computed(() =>
  galleryTab.value === "pink" ? pinkItems : blueItems
);
const activeLightboxItem = computed(() =>
  lightboxIndex.value !== null ? galleryItems.value[lightboxIndex.value] : null
);

function openLightbox(i: number): void {
  lightboxIndex.value = i;
  document.body.style.overflow = "hidden";
}

function closeLightbox(): void {
  lightboxIndex.value = null;
  document.body.style.overflow = "";
  resetTouchState();
}

function lightboxNext(): void {
  if (lightboxIndex.value === null) return;
  lightboxIndex.value = (lightboxIndex.value + 1) % galleryItems.value.length;
}

function lightboxPrev(): void {
  if (lightboxIndex.value === null) return;
  lightboxIndex.value =
    (lightboxIndex.value - 1 + galleryItems.value.length) %
    galleryItems.value.length;
}

function resetTouchState(): void {
  touchStartX.value = null;
  touchStartY.value = null;
  touchLastX.value = null;
  touchLastY.value = null;
}

function onLightboxTouchStart(event: TouchEvent): void {
  const touch = event.touches[0];
  if (!touch) return;
  touchStartX.value = touch.clientX;
  touchStartY.value = touch.clientY;
  touchLastX.value = touch.clientX;
  touchLastY.value = touch.clientY;
}

function onLightboxTouchMove(event: TouchEvent): void {
  const touch = event.touches[0];
  if (!touch) return;
  touchLastX.value = touch.clientX;
  touchLastY.value = touch.clientY;
}

function onLightboxTouchEnd(): void {
  if (
    touchStartX.value === null ||
    touchStartY.value === null ||
    touchLastX.value === null ||
    touchLastY.value === null
  ) {
    resetTouchState();
    return;
  }
  const deltaX = touchLastX.value - touchStartX.value;
  const deltaY = touchLastY.value - touchStartY.value;
  const absX = Math.abs(deltaX);
  const absY = Math.abs(deltaY);
  if (absY > 52 && absY > absX) {
    closeLightbox();
  } else {
    resetTouchState();
  }
}

function onGalleryKeydown(event: KeyboardEvent): void {
  if (event.key === "Escape" && lightboxIndex.value !== null) {
    closeLightbox();
  }
}

const selectedColor = computed(() => store.draft.color);

const selectedColorLabel = computed(() => {
  const color = store.colorOptions.find((c) => c.value === selectedColor.value);
  return color?.label ?? "";
});

const selectedColorImage = computed(() => {
  const images: Record<string, string> = {
    "hot-pink": "/products/dSlide/pink-1.jpg",
    "cyan-blue": "/products/dSlide/blue-1.jpg",
  };
  return images[selectedColor.value] ?? "/products/dSlide/master.jpg";
});

const progressWidth = computed(() => {
  const map: Record<number, string> = { 1: "0%", 2: "50%", 3: "100%" };
  return map[store.currentStep] ?? "0%";
});

const orderId = computed(() => {
  // Generate a short order ID based on timestamp
  const ts = Date.now().toString(36).toUpperCase();
  return ts.slice(-6);
});

const savings = computed(() => {
  const regular = store.totalPairs * store.PRICE_ONE_PAIR;
  return Math.max(0, regular - store.grandTotal);
});

const cartSummary = computed(() =>
  store.items
    .map(
      (item) => `${item.colorLabel} size ${item.size} (${item.quantity} đôi)`
    )
    .join(", ")
);

function formatVnd(value: number): string {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(value);
}

function addToCart(): void {
  if (!store.draftValid) return;
  store.addToCart();
  added.value = true;
  setTimeout(() => {
    added.value = false;
  }, 2000);
}

function handleSubmit(): void {
  store.submitOrder();
}

function scrollToSection(id: string): void {
  document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
}

// Meta
useHead({
  title: computed(() =>
    locale.value === "vi"
      ? "driip- | Driip Slide — SS26"
      : "driip- | Driip Slide — SS26"
  ),
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "Driip Slide — Phong cách Bánh Mì, chất liệu EVA, chống trượt. Hot Pink và Cyan Blue. 1 đôi 286.000đ, 2 đôi 500.000đ. Bảo hành 180 ngày."
          : "Driip Slide — Bánh Mì style, EVA material, anti-slip. Hot Pink and Cyan Blue. 1 pair 286,000đ, 2 pairs 500,000đ. 180-day warranty.",
    },
    { name: "viewport", content: "width=device-width, initial-scale=1" },
    { name: "theme-color", content: "#0a0a0a" },
    // Open Graph
    { property: "og:title", content: "driip- | Driip Slide — SS26" },
    {
      property: "og:description",
      content:
        locale.value === "vi"
          ? "Driip Slide — Phong cách Bánh Mì, chất liệu EVA. Hot Pink & Cyan Blue. 1 đôi 286.000đ, 2 đôi 500.000đ."
          : "Driip Slide — Bánh Mì style, EVA material. Hot Pink & Cyan Blue. 1 pair 286,000đ, 2 pairs 500,000đ.",
    },
    { property: "og:type", content: "product" },
    { property: "og:site_name", content: "driip-" },
    {
      property: "og:locale",
      content: computed(() => (locale.value === "vi" ? "vi_VN" : "en_US")),
    },
    { property: "og:url", content: "https://driip.com/driip-slide" },
    // OG Image
    {
      property: "og:image",
      content: "https://driip.com/products/dSlide/master.jpg",
    },
    { property: "og:image:width", content: "1200" },
    { property: "og:image:height", content: "630" },
    { property: "og:image:type", content: "image/jpeg" },
    {
      property: "og:image:alt",
      content: "Driip Slide — Hot Pink & Cyan Blue Bánh Mì Style Slides",
    },
    // Product OG
    { property: "product:price:amount", content: "286000" },
    { property: "product:price:currency", content: "VND" },
    { property: "product:availability", content: "in stock" },
    { property: "product:condition", content: "new" },
    // Twitter Card
    { name: "twitter:card", content: "summary_large_image" },
    { name: "twitter:site", content: "@driip_" },
    { name: "twitter:creator", content: "@driip_" },
    { name: "twitter:title", content: "driip- | Driip Slide — SS26" },
    {
      name: "twitter:description",
      content:
        locale.value === "vi"
          ? "Phong cách Bánh Mì, chất liệu EVA. Hot Pink & Cyan Blue. 1 đôi 286.000đ, 2 đôi 500.000đ."
          : "Bánh Mì style, EVA material. Hot Pink & Cyan Blue. 1 pair 286,000đ, 2 pairs 500,000đ.",
    },
    {
      name: "twitter:image",
      content: "https://driip.com/products/dSlide/master.jpg",
    },
    {
      name: "twitter:image:alt",
      content: "Driip Slide — Hot Pink & Cyan Blue Bánh Mì Style Slides",
    },
  ],
  link: [{ rel: "canonical", href: "https://driip.com/driip-slide" }],
});

// Lifecycle
// Store observers for cleanup
let sectionObserver: IntersectionObserver | null = null;
let viewContentObserver: IntersectionObserver | null = null;
let revealObserver: IntersectionObserver | null = null;

onMounted(() => {
  trackPageView();
  setupScrollDepth();

  // Setup reveal observer
  revealObserver = new IntersectionObserver(
    (entries) =>
      entries.forEach((entry) => {
        if (entry.isIntersecting) entry.target.classList.add("is-visible");
      }),
    { threshold: 0.12 }
  );
  document
    .querySelectorAll(".reveal")
    .forEach((el) => revealObserver?.observe(el));

  // Setup section nav observer
  const ids = ["products", "gallery", "checkout"];
  sectionObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          siteNavStore.setActiveSection(entry.target.id);
        }
      });
    },
    { threshold: 0.25, rootMargin: "-64px 0px 0px 0px" }
  );
  ids.forEach((id) => {
    const element = document.getElementById(id);
    if (element) sectionObserver?.observe(element);
  });

  // Track view content when products section is visible
  viewContentObserver = new IntersectionObserver(
    ([entry]) => {
      if (entry?.isIntersecting) {
        store.trackProductsViewed();
        viewContentObserver?.disconnect();
        viewContentObserver = null;
      }
    },
    { threshold: 0.25 }
  );
  const productsSection = document.getElementById("products");
  if (productsSection) viewContentObserver?.observe(productsSection);

  window.addEventListener("keydown", onGalleryKeydown);
});

onUnmounted(() => {
  // Clean up all observers
  sectionObserver?.disconnect();
  sectionObserver = null;
  viewContentObserver?.disconnect();
  viewContentObserver = null;
  revealObserver?.disconnect();
  revealObserver = null;

  // Clean up event listeners
  window.removeEventListener("keydown", onGalleryKeydown);

  // Reset body overflow
  document.body.style.overflow = "";

  // Clear site nav active section to prevent issues on other pages
  siteNavStore.setActiveSection("");
});
</script>

<style scoped>
/* ── PAGE ────────────────────────────────────────────────────────── */
.slide-page {
  background: var(--black);
  color: var(--white);
  min-height: 100vh;
}

/* ── HERO WITH BACKGROUND ───────────────────────────────────────── */
.slide-hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
}

.slide-hero-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.slide-hero-bg-layer {
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity 0.6s ease;
}

.slide-hero-bg-layer.active {
  opacity: 1;
}

.slide-hero-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.8);
}

.slide-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(0, 0, 0, 0.6) 0%,
    rgba(0, 0, 0, 0.75) 50%,
    rgba(0, 0, 0, 0.9) 100%
  );
}

.slide-hero-overlay--pink {
  background: linear-gradient(
    180deg,
    rgba(20, 0, 10, 0.7) 0%,
    rgba(40, 0, 20, 0.8) 50%,
    rgba(60, 0, 30, 0.9) 100%
  );
}

.slide-hero-overlay--blue {
  background: linear-gradient(
    180deg,
    rgba(0, 20, 30, 0.7) 0%,
    rgba(0, 30, 40, 0.8) 50%,
    rgba(0, 40, 50, 0.9) 100%
  );
}

/* Color Preview in Hero */
.slide-hero-color-preview {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 50px;
  animation: slideInUp 0.4s ease;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-color-preview-swatch {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
}

.slide-color-preview-swatch--hot-pink {
  background: #ff1493;
  box-shadow: 0 0 15px #ff1493;
}

.slide-color-preview-swatch--cyan-blue {
  background: #00ffff;
  box-shadow: 0 0 15px #00ffff;
}

.slide-color-preview-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--white);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.slide-hero-content {
  position: relative;
  z-index: 1;
  padding: 120px 24px 64px;
}

.slide-hero-inner {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
}

.slide-hero-copy {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.slide-eyebrow {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: rgba(255, 255, 255, 0.6);
  text-transform: uppercase;
}

.slide-pre {
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.15em;
  color: rgba(255, 255, 255, 0.5);
  text-transform: uppercase;
}

.slide-title {
  font-size: clamp(40px, 8vw, 80px);
  font-weight: 700;
  line-height: 1.05;
  letter-spacing: -0.02em;
  margin: 0;
}

.slide-title-line {
  display: block;
}

.slide-title-line--muted {
  color: rgba(255, 255, 255, 0.6);
}

.slide-sub {
  font-size: 15px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.7);
  max-width: 560px;
}

/* Hero Pricing */
.slide-hero-pricing {
  display: flex;
  flex-direction: column;
  gap: 0;
  padding: 0;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin: 16px 0;
  overflow: hidden;
}

.slide-pricing-context {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.04);
}

.slide-pricing-tag {
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: #fbbf24;
}

.slide-pricing-normal {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.45);
}

.slide-pricing-normal s {
  color: rgba(255, 255, 255, 0.3);
  text-decoration-color: rgba(255, 255, 255, 0.3);
}

.slide-pricing-tiers {
  display: flex;
  align-items: center;
  gap: 24px;
  padding: 16px 20px;
}

.slide-pricing-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.slide-pricing-item--highlight {
  position: relative;
}

.slide-pricing-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.15em;
  color: rgba(255, 255, 255, 0.5);
}

.slide-pricing-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--white);
}

.slide-pricing-save {
  font-size: 10px;
  color: #4ade80;
  font-weight: 600;
}

.slide-pricing-divider {
  width: 1px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
}

/* Countdown */
.slide-countdown {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.2);
}

.slide-countdown--expired .slide-countdown-label {
  color: rgba(255, 255, 255, 0.4);
  font-size: 11px;
}

.slide-countdown-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.4);
}

.slide-countdown-clock {
  display: flex;
  align-items: center;
  gap: 6px;
}

.slide-countdown-unit {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  min-width: 36px;
}

.slide-countdown-num {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 700;
  line-height: 1;
  letter-spacing: -0.02em;
  color: var(--white);
}

.slide-countdown-seg {
  font-size: 8px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.35);
}

.slide-countdown-colon {
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.3);
  line-height: 1;
  margin-bottom: 14px;
}

/* Warranty Badge */
.slide-hero-warranty {
  margin-bottom: 8px;
}

.slide-warranty-badge {
  padding: 8px 16px;
  background: rgba(74, 222, 128, 0.15);
  border: 1px solid rgba(74, 222, 128, 0.3);
  color: #4ade80;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.1em;
}

/* Hero Actions */
.slide-hero-actions {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 8px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 16px 32px;
  background: var(--white);
  color: var(--black);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn-primary:hover {
  background: rgba(255, 255, 255, 0.9);
  transform: translateY(-2px);
}

.btn-glow {
  box-shadow: 0 0 30px rgba(255, 20, 147, 0.4);
}

.btn-ghost {
  display: inline-flex;
  align-items: center;
  padding: 16px 24px;
  background: transparent;
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
  font-weight: 500;
  letter-spacing: 0.05em;
  border: 1px solid rgba(255, 255, 255, 0.3);
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-ghost:hover {
  border-color: rgba(255, 255, 255, 0.6);
  color: var(--white);
}

.btn-full {
  width: 100%;
  justify-content: center;
}

.btn-large {
  padding: 18px 40px;
  font-size: 14px;
}

.btn-text {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.5);
  font-size: 13px;
  cursor: pointer;
  text-decoration: underline;
  padding: 8px;
}

.btn-text:hover {
  color: rgba(255, 255, 255, 0.8);
}

/* Scroll Indicator */
.slide-hero-scroll {
  position: absolute;
  bottom: 32px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
}

.slide-scroll-line {
  display: block;
  width: 1px;
  height: 60px;
  background: linear-gradient(to bottom, rgba(255, 255, 255, 0.3), transparent);
  animation: scrollLine 2s ease-in-out infinite;
}

@keyframes scrollLine {
  0%,
  100% {
    transform: scaleY(0);
    transform-origin: top;
  }
  50% {
    transform: scaleY(1);
    transform-origin: top;
  }
  51% {
    transform-origin: bottom;
  }
  100% {
    transform: scaleY(0);
    transform-origin: bottom;
  }
}

/* ── TICKER ─────────────────────────────────────────────────────── */
.slide-ticker {
  background: var(--grey-900);
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  padding: 16px 0;
  overflow: hidden;
}

.slide-ticker-track {
  display: flex;
  animation: ticker 30s linear infinite;
  width: max-content;
}

.slide-ticker-set {
  display: flex;
  gap: 24px;
  padding-right: 24px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-400);
  text-transform: uppercase;
  white-space: nowrap;
}

.slide-ticker-sep {
  color: var(--grey-600);
}

@keyframes ticker {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-16.666%);
  }
}

/* ── SECTION COMMON ──────────────────────────────────────────────── */
.slide-section-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-500);
  text-transform: uppercase;
  margin-bottom: 16px;
}

/* ── PRODUCTS ────────────────────────────────────────────────────── */
.slide-products {
  padding: 100px 24px;
  background: var(--black);
}

.slide-products-inner {
  max-width: 800px;
  margin: 0 auto;
}

.slide-products-title {
  font-size: clamp(28px, 5vw, 48px);
  font-weight: 700;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin: 0 0 8px;
}

.slide-products-sub {
  font-size: 15px;
  color: var(--grey-400);
  margin-bottom: 48px;
}

.slide-products-grid {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.slide-select-block {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.slide-select-label {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-400);
  text-transform: uppercase;
}

.slide-select-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Mobile Color Preview - shows selected color image inline */
.slide-color-preview-mobile {
  margin-top: 20px;
  display: none;
  animation: fadeSlideUp 0.4s ease;
}

@keyframes fadeSlideUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-preview-img-wrap {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  aspect-ratio: 3/2;
}

.slide-preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.slide-preview-caption {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.15em;
  color: var(--grey-400);
  text-transform: uppercase;
  margin: 12px 0 0;
  text-align: center;
}

.slide-size-guide {
  background: transparent;
  border: none;
  padding: 0;
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--grey-500);
  text-decoration: underline;
  cursor: pointer;
  transition: color 0.15s ease;
}

.slide-size-guide:hover {
  color: var(--white);
}

/* Colors */
.slide-colors {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.slide-color-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px 24px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 140px;
}

.slide-color-btn:hover,
.slide-color-btn.active {
  border-color: rgba(255, 255, 255, 0.4);
}

.slide-color-btn.active {
  background: rgba(255, 255, 255, 0.05);
}

.slide-color-swatch {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.slide-color-swatch--hot-pink {
  background: #ff1493;
}

.slide-color-swatch--cyan-blue {
  background: #00ffff;
}

.slide-color-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--white);
}

.slide-color-sizes {
  font-size: 10px;
  color: var(--grey-500);
}

/* Sizes */
.slide-sizes {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.slide-size-btn {
  padding: 12px 20px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--grey-300);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.slide-size-btn:hover:not(:disabled) {
  border-color: rgba(255, 255, 255, 0.3);
}

.slide-size-btn.active {
  background: var(--white);
  color: var(--black);
  border-color: var(--white);
}

.slide-size-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.slide-select-hint {
  font-size: 13px;
  color: var(--grey-500);
  font-style: italic;
}

/* Quantity */
.slide-quantity-options {
  display: flex;
  gap: 16px;
}

.slide-qty-btn {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 20px 32px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 160px;
}

.slide-qty-btn:hover {
  border-color: rgba(255, 255, 255, 0.3);
}

.slide-qty-btn.active {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.5);
}

.slide-qty-btn--best {
  border-color: rgba(74, 222, 128, 0.3);
}

.slide-qty-btn--best.active {
  border-color: #4ade80;
  background: rgba(74, 222, 128, 0.1);
}

.slide-qty-badge {
  position: absolute;
  top: -10px;
  padding: 4px 12px;
  background: #4ade80;
  color: var(--black);
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.1em;
}

.slide-qty-count {
  font-size: 24px;
  font-weight: 700;
  color: var(--white);
}

.slide-qty-price {
  font-size: 14px;
  color: var(--grey-300);
}

.slide-qty-save {
  font-size: 10px;
  color: #4ade80;
}

/* Add to Cart */
.slide-atc {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  margin-top: 48px;
}

.btn-atc {
  padding: 18px 48px;
  background: var(--grey-800);
  color: var(--grey-500);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: not-allowed;
  transition: all 0.2s ease;
}

.btn-atc--ready {
  background: var(--white);
  color: var(--black);
  border-color: var(--white);
  cursor: pointer;
}

.btn-atc--ready:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(255, 255, 255, 0.2);
}

.slide-atc-msg {
  font-size: 13px;
  color: #4ade80;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-added-enter-active,
.slide-added-leave-active {
  transition: all 0.3s ease;
}

.slide-added-enter-from,
.slide-added-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* ── GALLERY / LOOKBOOK ────────────────────────────────────────────── */
.slide-gallery {
  padding: 80px 16px 64px;
  background: var(--grey-950, #0a0a0a);
}

.slide-gallery-header {
  text-align: center;
  margin-bottom: 40px;
}

.slide-gallery-pre {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.35em;
  color: var(--grey-500);
  text-transform: uppercase;
  margin-bottom: 12px;
}

.slide-gallery-title {
  font-size: clamp(32px, 8vw, 64px);
  font-weight: 700;
  color: var(--white);
  letter-spacing: -0.02em;
  line-height: 1;
  margin-bottom: 16px;
}

.slide-gallery-sub {
  font-size: 12px;
  font-weight: 300;
  letter-spacing: 0.15em;
  color: var(--grey-500);
  text-transform: uppercase;
}

.slide-gallery-cta {
  margin-top: 18px;
  padding: 12px 20px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  background: rgba(255, 255, 255, 0.04);
  color: var(--white);
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  cursor: pointer;
  transition: border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.slide-gallery-cta:hover {
  border-color: rgba(255, 255, 255, 0.32);
  background: rgba(255, 255, 255, 0.08);
}

/* Gallery Tabs */
.slide-gallery-tabs {
  display: flex;
  justify-content: center;
  gap: 0;
  margin-bottom: 28px;
}

.slide-gallery-tab {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--grey-500);
  background: transparent;
  border: 1px solid var(--grey-800);
  padding: 10px 24px;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s, background 0.2s;
}

.slide-gallery-tab:first-child {
  border-right: none;
}

.slide-gallery-tab.active {
  color: var(--black);
  background: var(--white);
  border-color: var(--white);
}

.slide-gallery-tab:not(.active):hover {
  color: var(--grey-200);
  border-color: var(--grey-600);
}

/* Masonry Grid - Mobile: 2 cols, item-0 tall (2 rows), item-2 wide (span 2) */
.slide-masonry {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-auto-rows: 180px;
  gap: 6px;
  max-width: 1200px;
  margin: 0 auto;
}

.m-item-0 {
  grid-row: span 2;
}

.m-item-2 {
  grid-column: span 2;
}

.slide-masonry-item {
  position: relative;
  overflow: hidden;
  cursor: zoom-in;
  background: var(--grey-900);
}

.slide-masonry-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0.95;
  transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94),
    opacity 0.25s ease;
}

.slide-masonry-item:hover .slide-masonry-img {
  transform: scale(1.04);
}

.slide-masonry-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.65) 0%,
    rgba(0, 0, 0, 0) 50%
  );
  display: flex;
  align-items: flex-end;
  padding: 12px;
  opacity: 0;
  transition: opacity 0.3s;
}

.slide-masonry-item:hover .slide-masonry-overlay {
  opacity: 1;
}

.slide-masonry-label {
  font-size: 9px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--white);
  text-transform: uppercase;
}

/* Lightbox */
.slide-lightbox {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.slide-lb-wrap {
  max-width: min(90vw, 800px);
  max-height: 90dvh;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.slide-lb-img {
  width: 100%;
  height: auto;
  max-height: 80dvh;
  object-fit: contain;
  display: block;
  opacity: 0.95;
}

.slide-lb-caption {
  font-size: 10px;
  letter-spacing: 0.25em;
  color: var(--grey-500);
  text-transform: uppercase;
}

.slide-lb-close {
  position: absolute;
  top: 16px;
  right: 16px;
  font-size: 18px;
  color: var(--grey-400);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  padding: 8px;
  line-height: 1;
}

.slide-lb-close:hover {
  color: var(--white);
}

.slide-lb-back {
  position: absolute;
  top: 16px;
  left: 16px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.24em;
  color: var(--grey-300);
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  cursor: pointer;
  padding: 10px 12px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s, border-color 0.2s, background 0.2s;
}

.slide-lb-back:hover {
  color: var(--white);
  border-color: rgba(255, 255, 255, 0.28);
  background: rgba(255, 255, 255, 0.08);
}

.slide-lb-prev,
.slide-lb-next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  font-size: 36px;
  color: var(--grey-500);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
  padding: 12px;
  line-height: 1;
}

.slide-lb-prev:hover,
.slide-lb-next:hover {
  color: var(--white);
}

.slide-lb-prev {
  left: 8px;
}

.slide-lb-next {
  right: 8px;
}

/* Tab Transition */
.tab-fade-enter-active,
.tab-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.tab-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.tab-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* Lightbox Transition */
.lb-fade-enter-active,
.lb-fade-leave-active {
  transition: opacity 0.25s ease;
}

.lb-fade-enter-from,
.lb-fade-leave-to {
  opacity: 0;
}

/* ── CHECKOUT ────────────────────────────────────────────────────── */
.slide-checkout {
  padding: 100px 24px;
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.slide-checkout-inner {
  max-width: 600px;
  margin: 0 auto;
}

.slide-checkout-head {
  margin-bottom: 48px;
  text-align: center;
}

.slide-checkout-title {
  font-size: clamp(36px, 8vw, 64px);
  font-weight: 700;
  line-height: 0.95;
  letter-spacing: -0.02em;
  margin: 0 0 16px;
}

.slide-checkout-sub {
  font-size: 14px;
  color: var(--grey-400);
}

/* Success State */
.slide-success {
  text-align: center;
  padding: 32px 20px 48px;
  position: relative;
  overflow: hidden;
}

/* Celebration Confetti */
.slide-success-celebration {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}

.slide-confetti {
  position: absolute;
  font-size: 20px;
  color: #4ade80;
  opacity: 0;
  animation: confettiPop 1s ease-out forwards;
}

.slide-confetti.c1 {
  top: 10%;
  left: 10%;
  animation-delay: 0.1s;
}
.slide-confetti.c2 {
  top: 20%;
  right: 15%;
  animation-delay: 0.2s;
  color: #ff1493;
}
.slide-confetti.c3 {
  top: 5%;
  left: 50%;
  animation-delay: 0.3s;
}
.slide-confetti.c4 {
  bottom: 30%;
  left: 5%;
  animation-delay: 0.4s;
  color: #00ffff;
}
.slide-confetti.c5 {
  bottom: 40%;
  right: 10%;
  animation-delay: 0.5s;
}
.slide-confetti.c6 {
  top: 30%;
  left: 80%;
  animation-delay: 0.6s;
  color: #fbbf24;
}

@keyframes confettiPop {
  0% {
    opacity: 0;
    transform: scale(0) rotate(0deg) translateY(20px);
  }
  50% {
    opacity: 1;
    transform: scale(1.2) rotate(180deg) translateY(-10px);
  }
  100% {
    opacity: 0.6;
    transform: scale(1) rotate(360deg) translateY(0);
  }
}

/* Success Ring Animation */
.slide-success-ring {
  position: relative;
  width: 100px;
  height: 100px;
  margin: 0 auto 32px;
}

.slide-success-ring::before {
  content: "";
  position: absolute;
  inset: -8px;
  border: 2px solid #4ade80;
  border-radius: 50%;
  opacity: 0;
  animation: ringPulse 1s ease-out 0.5s forwards;
}

@keyframes ringPulse {
  0% {
    opacity: 0.8;
    transform: scale(0.8);
  }
  100% {
    opacity: 0;
    transform: scale(1.3);
  }
}

.slide-success-icon {
  width: 84px;
  height: 84px;
  margin: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
  color: var(--black);
  border-radius: 50%;
  box-shadow: 0 8px 32px rgba(74, 222, 128, 0.3);
  animation: iconBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.3s both;
}

@keyframes iconBounce {
  0% {
    opacity: 0;
    transform: scale(0.3) rotate(-45deg);
  }
  100% {
    opacity: 1;
    transform: scale(1) rotate(0deg);
  }
}

.slide-success-title {
  font-size: clamp(24px, 5vw, 32px);
  font-weight: 700;
  margin-bottom: 12px;
  background: linear-gradient(90deg, var(--white) 0%, var(--grey-300) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.slide-success-body {
  font-size: 15px;
  color: var(--grey-400);
  margin-bottom: 28px;
  line-height: 1.7;
  max-width: 420px;
  margin-left: auto;
  margin-right: auto;
}

/* Order Summary Card */
.slide-order-card {
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.05) 0%,
    rgba(255, 255, 255, 0.02) 100%
  );
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 28px;
  text-align: left;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
  animation: slideUpFade 0.6s ease 0.4s both;
}

@keyframes slideUpFade {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-order-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.slide-order-card-label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.15em;
  color: var(--grey-500);
  text-transform: uppercase;
}

.slide-order-card-id {
  font-size: 12px;
  font-weight: 600;
  color: #4ade80;
  font-family: monospace;
}

.slide-order-items {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.slide-order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.slide-order-item-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.slide-order-item-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--white);
}

.slide-order-item-meta {
  font-size: 12px;
  color: var(--grey-500);
}

.slide-order-item-price {
  font-size: 14px;
  font-weight: 600;
  color: var(--white);
}

.slide-order-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 14px;
  font-weight: 600;
}

.slide-order-total-value {
  font-size: 18px;
  color: #4ade80;
}

/* Next Steps */
.slide-success-steps {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-bottom: 32px;
  flex-wrap: wrap;
  animation: slideUpFade 0.6s ease 0.6s both;
}

.slide-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.slide-step-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  font-size: 13px;
  font-weight: 600;
  color: var(--grey-300);
}

.slide-step-text {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.05em;
  color: var(--grey-500);
  text-transform: uppercase;
  max-width: 70px;
  text-align: center;
  line-height: 1.4;
}

.slide-step-arrow {
  font-size: 14px;
  color: var(--grey-600);
  margin-top: -16px;
}

/* Success Actions */
.slide-success-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
  animation: slideUpFade 0.6s ease 0.8s both;
}

.slide-success-actions .btn-primary,
.slide-success-actions .btn-ghost {
  min-width: 220px;
}

/* Progress */
.slide-progress {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 48px;
  padding-bottom: 4px;
}

.slide-progress-track {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: rgba(255, 255, 255, 0.1);
}

.slide-progress-fill {
  height: 100%;
  background: var(--white);
  transition: width 0.3s ease;
}

.slide-progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  z-index: 1;
}

.slide-progress-dot {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  color: var(--grey-500);
  font-size: 13px;
  font-weight: 600;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.slide-progress-step.active .slide-progress-dot,
.slide-progress-step.done .slide-progress-dot {
  background: var(--white);
  color: var(--black);
}

.slide-progress-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-500);
  text-transform: uppercase;
}

.slide-progress-step.active .slide-progress-label {
  color: var(--white);
}

/* Panel */
.slide-panel {
  animation: fadeIn 0.3s ease;
}

/* Empty Cart */
.slide-empty-cart {
  text-align: center;
  padding: 48px 24px;
}

.slide-empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.slide-empty-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
}

.slide-empty-sub {
  font-size: 14px;
  color: var(--grey-500);
  margin-bottom: 24px;
}

/* Cart List */
.slide-cart-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 32px;
}

.slide-cart-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.slide-cart-info {
  flex: 1;
  min-width: 0;
}

.slide-cart-name {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 4px;
}

.slide-cart-meta {
  font-size: 12px;
  color: var(--grey-500);
}

.slide-cart-qty {
  display: flex;
  align-items: center;
  gap: 8px;
}

.slide-cart-qty-btn {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: var(--white);
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.slide-cart-qty-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.slide-cart-qty-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.slide-cart-qty-val {
  font-size: 14px;
  font-weight: 600;
  min-width: 24px;
  text-align: center;
}

.slide-cart-price {
  font-size: 14px;
  font-weight: 600;
  color: var(--white);
  min-width: 100px;
  text-align: right;
}

.slide-cart-remove {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  color: var(--grey-500);
  font-size: 14px;
  cursor: pointer;
  transition: color 0.2s ease;
}

.slide-cart-remove:hover {
  color: #ef4444;
}

/* Cart Total */
.slide-cart-total {
  padding: 24px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.06);
  margin-bottom: 24px;
}

.slide-cart-total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
}

.slide-cart-total-value {
  font-size: 24px;
  font-weight: 700;
}

.slide-cart-savings {
  text-align: right;
  font-size: 12px;
  color: #4ade80;
  margin-top: 8px;
}

/* Form Fields */
.slide-field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.slide-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 20px;
}

.slide-field label {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-400);
  text-transform: uppercase;
}

.slide-field input {
  padding: 14px 16px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--white);
  font-size: 15px;
  transition: border-color 0.2s ease;
}

.slide-field input:focus {
  outline: none;
  border-color: rgba(255, 255, 255, 0.4);
}

.slide-field input::placeholder {
  color: var(--grey-600);
}

.slide-field-error {
  font-size: 12px;
  color: #ef4444;
}

.slide-field-optional {
  font-size: 9px;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: none;
  color: rgba(255, 255, 255, 0.25);
  margin-left: 6px;
}

.slide-gender-row {
  display: flex;
  gap: 8px;
}

.slide-gender-btn {
  flex: 1;
  padding: 12px 8px;
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.5);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s, background 0.2s;
}

.slide-gender-btn:hover {
  border-color: rgba(255, 255, 255, 0.3);
  color: var(--white);
}

.slide-gender-btn.active {
  border-color: var(--white);
  color: var(--white);
  background: rgba(255, 255, 255, 0.06);
}

/* Panel Actions */
.slide-panel-actions {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-top: 32px;
}

/* Review */
.slide-review {
  background: var(--grey-900);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 24px;
  margin-bottom: 32px;
}

.slide-review-heading {
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-500);
  text-transform: uppercase;
  margin-bottom: 16px;
}

.slide-review-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.slide-review-total {
  font-weight: 700;
  font-size: 16px;
  border-bottom: none;
  margin-top: 8px;
}

.slide-review-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 16px 0;
}

/* Error & Loading */
.slide-error {
  padding: 16px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #ef4444;
  text-align: center;
  margin-bottom: 16px;
}

.slide-loading {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.slide-fine {
  font-size: 12px;
  color: var(--grey-500);
  text-align: center;
  margin-top: 16px;
}

/* ── REVEAL ANIMATION ───────────────────────────────────────────── */
.reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* ── RESPONSIVE ──────────────────────────────────────────────────── */
@media (min-width: 640px) {
  .slide-gallery {
    padding: 100px 32px 80px;
  }

  .slide-masonry {
    grid-template-columns: 1fr 1fr 1fr;
    grid-auto-rows: 240px;
    gap: 8px;
  }

  .m-item-0 {
    grid-row: span 2;
    grid-column: span 1;
  }

  .m-item-1 {
    grid-row: span 1;
  }

  .m-item-2 {
    grid-column: span 2;
    grid-row: span 1;
  }

  .slide-lb-back {
    display: none;
  }
}

@media (min-width: 768px) {
  .slide-hero-content {
    padding: 160px 48px 80px;
  }

  .slide-products,
  .slide-gallery,
  .slide-checkout {
    padding: 120px 48px;
  }

  .slide-field-row {
    grid-template-columns: 1fr 1fr;
  }
}

@media (min-width: 1024px) {
  .slide-hero-content {
    padding: 180px 64px 100px;
  }

  .slide-products,
  .slide-gallery,
  .slide-checkout {
    padding: 140px 64px;
  }

  .slide-products-grid {
    flex-direction: row;
    gap: 48px;
  }

  .slide-select-block {
    flex: 1;
  }

  .slide-masonry {
    grid-auto-rows: 320px;
    gap: 10px;
  }
}

/* ── MOBILE-FIRST RESPONSIVE ─────────────────────────────────────── */

/* Mobile: 320px - 639px */
@media (max-width: 639px) {
  /* Hero Mobile */
  .slide-hero-content {
    padding: 100px 16px 48px;
  }

  .slide-title {
    font-size: clamp(32px, 10vw, 48px);
  }

  .slide-sub {
    font-size: 14px;
    padding: 0 8px;
  }

  .slide-hero-pricing {
    max-width: 360px;
  }

  .slide-pricing-tiers {
    padding: 16px 20px;
  }

  .slide-pricing-item {
    flex: 1;
  }

  .slide-pricing-value {
    font-size: 18px;
  }

  .slide-pricing-divider {
    height: 40px;
  }

  .slide-pricing-save {
    font-size: 9px;
  }

  .slide-hero-color-preview {
    padding: 10px 16px;
  }

  .slide-color-preview-swatch {
    width: 20px;
    height: 20px;
  }

  .slide-color-preview-name {
    font-size: 12px;
  }

  .slide-hero-actions {
    flex-direction: column;
    width: 100%;
    max-width: 280px;
    gap: 12px;
  }

  .btn-primary,
  .btn-ghost {
    width: 100%;
    justify-content: center;
    padding: 14px 24px;
  }

  /* Products Section Mobile */
  .slide-products {
    padding: 64px 16px;
  }

  .slide-products-title {
    font-size: 24px;
  }

  .slide-products-sub {
    font-size: 13px;
  }

  .slide-colors {
    gap: 12px;
  }

  .slide-color-btn {
    min-width: calc(50% - 6px);
    flex: 1;
    padding: 12px 16px;
  }

  .slide-color-sizes {
    font-size: 9px;
  }

  .slide-sizes {
    gap: 8px;
  }

  .slide-size-btn {
    padding: 10px 14px;
    font-size: 12px;
    min-width: 60px;
  }

  .slide-quantity-options {
    flex-direction: column;
    gap: 12px;
  }

  .slide-qty-btn {
    width: 100%;
    flex-direction: row;
    justify-content: space-between;
    padding: 16px 20px;
  }

  .slide-qty-count {
    font-size: 18px;
  }

  .btn-atc {
    width: 100%;
    padding: 16px 24px;
  }

  /* Mobile Color Preview - visible only on mobile */
  .slide-color-preview-mobile {
    display: block;
  }

  /* Gallery Mobile */
  .slide-gallery {
    padding: 64px 16px;
  }

  .slide-gallery-title {
    font-size: 28px;
  }

  .slide-masonry {
    grid-auto-rows: 160px;
    gap: 4px;
  }

  .slide-masonry-label {
    font-size: 8px;
    letter-spacing: 0.2em;
  }

  /* Lightbox Mobile */
  .slide-lb-prev,
  .slide-lb-next {
    display: none;
  }

  .slide-lb-back {
    display: inline-flex;
  }

  .slide-lb-close {
    top: 12px;
    right: 12px;
  }

  /* Checkout Mobile */
  .slide-checkout {
    padding: 64px 16px;
  }

  .slide-checkout-title {
    font-size: 28px;
  }

  .slide-progress {
    margin-bottom: 32px;
  }

  .slide-progress-label {
    font-size: 9px;
  }

  .slide-cart-item {
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px;
  }

  .slide-cart-info {
    width: calc(100% - 100px);
  }

  .slide-cart-name {
    font-size: 13px;
  }

  .slide-cart-meta {
    font-size: 11px;
  }

  .slide-cart-price {
    width: 100%;
    text-align: left;
    font-size: 15px;
    margin-top: 4px;
  }

  .slide-field-row {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .slide-field {
    margin-bottom: 16px;
  }

  .slide-field input {
    padding: 12px 14px;
    font-size: 16px; /* Prevent zoom on iOS */
  }

  .slide-panel-actions {
    flex-direction: column;
    gap: 12px;
    margin-top: 24px;
  }

  .slide-panel-actions .btn-primary,
  .slide-panel-actions .btn-ghost {
    width: 100%;
  }

  .slide-review {
    padding: 16px;
  }

  .slide-review-row {
    font-size: 13px;
    flex-direction: column;
    gap: 4px;
  }

  .slide-success-icon {
    width: 64px;
    height: 64px;
    font-size: 32px;
  }

  .slide-success-title {
    font-size: 20px;
  }

  .slide-empty-icon {
    font-size: 40px;
  }
}

/* Small Mobile: 320px - 374px */
@media (max-width: 374px) {
  .slide-pricing-tiers {
    gap: 12px;
  }

  .slide-pricing-divider {
    width: 100%;
    height: 1px;
  }

  .slide-title {
    font-size: 28px;
  }

  .slide-color-btn {
    min-width: 100%;
  }

  .slide-size-btn {
    padding: 8px 12px;
    font-size: 11px;
    min-width: 52px;
  }
}
</style>
