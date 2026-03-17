<template>
  <div class="page">
    <!-- NAV -->
    <nav class="nav">
      <NuxtLinkLocale to="/" class="nav-logo"
        >driip<span class="dash">-</span></NuxtLinkLocale
      >
      <div class="nav-right">
        <button class="lang-switch" @click="switchLang">
          {{ t("nav.langSwitch") }}
        </button>
      </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
      <div class="hero-inner parallax-content">
        <p class="hero-pre">{{ t("ck.hero.pre") }}</p>
        <h1 class="hero-title">
          <span class="line-1">THE</span>
          <span class="line-2">BOXER</span>
          <span class="line-3">& BRIEF<span class="dash-end">—</span></span>
        </h1>
        <p class="hero-sub">{{ t("ck.hero.sub") }}</p>
        <button class="btn-primary" @click="onHeroCTA">
          {{ t("ck.hero.cta") }}
          <span class="btn-arrow">→</span>
        </button>
      </div>
      <div class="hero-bg-text parallax-bg" aria-hidden="true">CK</div>
    </section>

    <!-- PROMO STRIP -->
    <div class="promo-strip">
      <span>{{ t("ck.strip.shipping") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.offer") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.stock") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.shipping") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.offer") }}</span>
      <span class="dot">·</span>
      <span>{{ t("ck.strip.stock") }}</span>
    </div>

    <!-- PRODUCT SECTION -->
    <section class="products" ref="productsRef">
      <div class="products-inner">
        <p class="label reveal">{{ t("ck.products.label") }}</p>
        <h2 class="products-title reveal">{{ t("ck.products.title") }}</h2>

        <div class="product-grid">
          <!-- CK BRIEF CARD -->
          <div class="product-card reveal" style="transition-delay: 0ms">
            <div class="product-img brief-gallery">
              <NuxtImg
                :src="`/products/Brief/${briefImages[briefImg - 1]}.png`"
                :width="600"
                :height="750"
                format="webp"
                quality="85"
                fit="cover"
                :alt="`CK Brief ${briefImages[briefImg - 1]}`"
                loading="lazy"
                class="product-img-el"
              />
              <button
                class="gallery-arrow gallery-prev"
                @click="briefImgPrev"
                aria-label="Previous image"
              >
                ‹
              </button>
              <button
                class="gallery-arrow gallery-next"
                @click="briefImgNext"
                aria-label="Next image"
              >
                ›
              </button>
              <div class="gallery-dots">
                <button
                  v-for="n in 3"
                  :key="n"
                  class="gallery-dot"
                  :class="{ active: briefImg === n }"
                  @click="briefImg = n"
                  :aria-label="`Image ${n}`"
                ></button>
              </div>
            </div>
            <div class="product-card-body">
              <div class="product-card-top">
                <p class="product-name">CK BRIEF</p>
                <span class="product-badge"
                  >{{ t("ck.products.from") }} RM 79</span
                >
              </div>
              <div class="product-divider"></div>
              <p class="product-desc">{{ t("ck.products.brief.desc") }}</p>
              <ul class="product-specs">
                <li v-for="spec in t('ck.products.brief.specs')" :key="spec">
                  {{ spec }}
                </li>
              </ul>
              <button class="btn-order-now" @click="prefillOrder('ck-brief')">
                {{ t("ck.products.orderThis") }}
              </button>
            </div>
          </div>

          <!-- CK BOXER CARD -->
          <div class="product-card reveal" style="transition-delay: 140ms">
            <div class="product-img">
              <NuxtImg
                :src="`/products/Boxer/${boxerColor}.png`"
                :width="600"
                :height="750"
                format="webp"
                quality="85"
                fit="cover"
                :alt="`CK Boxer ${boxerColor}`"
                loading="lazy"
                class="product-img-el"
              />
              <div class="boxer-swatches">
                <button
                  v-for="col in boxerColors"
                  :key="col.value"
                  class="boxer-swatch"
                  :class="{ active: boxerColor === col.value }"
                  :style="{ background: col.bg }"
                  :aria-label="col.value"
                  @click="boxerColor = col.value"
                ></button>
              </div>
            </div>
            <div class="product-card-body">
              <div class="product-card-top">
                <p class="product-name">CK BOXER</p>
                <span class="product-badge"
                  >{{ t("ck.products.from") }} RM 95</span
                >
              </div>
              <div class="product-divider"></div>
              <p class="product-desc">{{ t("ck.products.boxer.desc") }}</p>
              <ul class="product-specs">
                <li v-for="spec in t('ck.products.boxer.specs')" :key="spec">
                  {{ spec }}
                </li>
              </ul>
              <button class="btn-order-now" @click="prefillOrder('ck-boxer')">
                {{ t("ck.products.orderThis") }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FABRIC CALLOUT BAR -->
    <div class="fabric-bar reveal">
      <span class="fabric-item">{{ t("ck.fabric.modal") }}</span>
      <span class="fabric-sep">—</span>
      <span class="fabric-item">{{ t("ck.fabric.elastane") }}</span>
      <span class="fabric-sep">—</span>
      <span class="fabric-item">{{ t("ck.fabric.rideUp") }}</span>
      <span class="fabric-sep">—</span>
      <span class="fabric-item">{{ t("ck.fabric.waistband") }}</span>
    </div>

    <!-- OFFER SECTION -->
    <section class="offer">
      <div class="offer-inner">
        <div class="offer-left reveal">
          <p class="label">{{ t("ck.offer.label") }}</p>
          <h2 class="offer-title">{{ t("ck.offer.title") }}</h2>
          <p class="offer-body">{{ t("ck.offer.body") }}</p>
        </div>
        <div class="offer-right reveal">
          <p class="coupon-label">{{ t("ck.offer.yourCode") }}</p>
          <div class="coupon-box">
            <span class="coupon-code">DRIIP20</span>
            <button
              class="copy-btn"
              @click="copyCode"
              :class="{ copied: codeCopied }"
            >
              {{ codeCopied ? t("ck.offer.copied") : t("ck.offer.copy") }}
            </button>
          </div>
          <p class="coupon-note">{{ t("ck.offer.validity") }}</p>
        </div>
      </div>
    </section>

    <!-- MANIFESTO -->
    <section class="manifesto">
      <div class="manifesto-inner">
        <p class="manifesto-text reveal">{{ t("ck.manifesto") }}</p>
      </div>
    </section>

    <!-- EARLY ACCESS FORM -->
    <section class="access" id="access">
      <div class="access-inner">
        <div class="access-header reveal">
          <p class="label light">{{ t("ck.access.label") }}</p>
          <h2 class="access-title">{{ t("ck.access.title") }}</h2>
          <p class="access-sub">{{ t("ck.access.sub") }}</p>
        </div>

        <form
          v-if="accessState !== 'success'"
          class="form"
          @submit.prevent="submitAccess"
        >
          <div class="form-row">
            <div class="form-field">
              <label>{{ t("ck.access.name") }}</label>
              <input
                v-model="access.name"
                type="text"
                :placeholder="t('ck.access.namePlaceholder')"
                required
                autocomplete="name"
              />
            </div>
            <div class="form-field">
              <label>{{ t("ck.access.email") }}</label>
              <input
                v-model="access.email"
                type="email"
                :placeholder="t('ck.access.emailPlaceholder')"
                required
                autocomplete="email"
              />
            </div>
          </div>
          <div class="form-row single">
            <div class="form-field">
              <label
                >{{ t("ck.access.phone") }}
                <span class="opt">{{ t("ck.access.optional") }}</span></label
              >
              <input
                v-model="access.phone"
                type="tel"
                :placeholder="t('ck.access.phonePlaceholder')"
                autocomplete="tel"
              />
            </div>
          </div>
          <div v-if="accessState === 'error'" class="form-error">
            {{ t("common.error") }}
          </div>
          <button
            type="submit"
            class="btn-submit"
            :disabled="accessState === 'loading'"
          >
            <span v-if="accessState === 'idle' || accessState === 'error'">{{
              t("ck.access.submit")
            }}</span>
            <span v-else class="loading-dots"
              ><span></span><span></span><span></span
            ></span>
          </button>
          <p class="form-fine">{{ t("ck.access.fine") }}</p>
        </form>

        <div v-if="accessState === 'success'" class="success-message">
          <div class="success-icon">✓</div>
          <p class="success-title">{{ t("ck.access.successTitle") }}</p>
          <p class="success-body">
            {{ t("ck.access.successBody", { code: "DRIIP20" }) }}
          </p>
        </div>
      </div>
    </section>

    <!-- ORDER FORM -->
    <section class="order-section" id="order">
      <div class="order-inner">
        <div class="order-header reveal">
          <p class="label light">{{ t("ck.order.label") }}</p>
          <h2 class="order-title">{{ t("ck.order.title") }}</h2>
          <p class="order-sub">{{ t("ck.order.sub") }}</p>
        </div>

        <form
          v-if="orderState !== 'success'"
          class="order-form"
          @submit.prevent="submitOrder"
        >
          <!-- GROUP 1: YOUR DETAILS -->
          <div class="order-group">
            <p class="order-group-label">{{ t("ck.order.details") }}</p>
            <div class="order-fields">
              <!-- First / Last name row -->
              <div class="form-row">
                <div class="form-field">
                  <label>{{ t("ck.order.firstName") }}</label>
                  <input
                    v-model="order.firstName"
                    type="text"
                    :placeholder="t('ck.order.firstNamePlaceholder')"
                    required
                    autocomplete="given-name"
                  />
                </div>
                <div class="form-field">
                  <label>{{ t("ck.order.lastName") }}</label>
                  <input
                    v-model="order.lastName"
                    type="text"
                    :placeholder="t('ck.order.lastNamePlaceholder')"
                    required
                    autocomplete="family-name"
                  />
                </div>
              </div>

              <!-- Phone -->
              <div class="form-row single">
                <div class="form-field">
                  <label>{{ t("ck.order.phone") }}</label>
                  <input
                    v-model="order.phone"
                    type="tel"
                    :placeholder="t('ck.order.phonePlaceholder')"
                    required
                    autocomplete="tel"
                  />
                </div>
              </div>

              <!-- Province -->
              <div class="form-row single">
                <div class="form-field">
                  <label>{{ t("ck.order.province") }}</label>
                  <select
                    v-model="order.province"
                    @change="onProvinceChange"
                    required
                  >
                    <option value="" disabled>
                      {{ t("ck.order.provincePlaceholder") }}
                    </option>
                    <option
                      v-for="prov in vietnamProvinces"
                      :key="prov.code"
                      :value="prov.name"
                    >
                      {{ prov.name }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- District: select if province has districts, else text input -->
              <div class="form-row single">
                <div class="form-field">
                  <label>{{ t("ck.order.district") }}</label>
                  <select
                    v-if="
                      selectedProvince && selectedProvince.districts.length > 0
                    "
                    v-model="order.district"
                    required
                  >
                    <option value="" disabled>
                      {{ t("ck.order.districtPlaceholder") }}
                    </option>
                    <option
                      v-for="dist in selectedProvince.districts"
                      :key="dist"
                      :value="dist"
                    >
                      {{ dist }}
                    </option>
                  </select>
                  <input
                    v-else
                    v-model="order.district"
                    type="text"
                    :placeholder="t('ck.order.districtTextPlaceholder')"
                    required
                  />
                </div>
              </div>

              <!-- Ward -->
              <div class="form-row single">
                <div class="form-field">
                  <label>{{ t("ck.order.ward") }}</label>
                  <input
                    v-model="order.ward"
                    type="text"
                    :placeholder="t('ck.order.wardPlaceholder')"
                    required
                  />
                </div>
              </div>

              <!-- Street -->
              <div class="form-row single">
                <div class="form-field">
                  <label>{{ t("ck.order.street") }}</label>
                  <input
                    v-model="order.street"
                    type="text"
                    :placeholder="t('ck.order.streetPlaceholder')"
                    required
                    autocomplete="street-address"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- GROUP 2: YOUR SELECTION -->
          <div class="order-group">
            <p class="order-group-label">{{ t("ck.order.selection") }}</p>
            <div class="order-fields">
              <!-- SKU tiles -->
              <div class="select-group">
                <label class="select-group-label">{{
                  t("ck.order.product")
                }}</label>
                <div class="select-tiles">
                  <button
                    v-for="sku in skuOptions"
                    :key="sku.value"
                    type="button"
                    class="select-tile"
                    :class="{ active: order.sku === sku.value }"
                    @click="order.sku = sku.value"
                  >
                    <span class="tile-name">{{ sku.label }}</span>
                    <span class="tile-price">RM {{ sku.price }}</span>
                  </button>
                </div>
              </div>

              <!-- Size guide trigger -->
              <button
                class="size-guide-trigger"
                type="button"
                @click="sizeGuideOpen = true"
              >
                {{ t("ck.sizechart.modalButton") }}
              </button>

              <!-- Size Guide Modal -->
              <Teleport to="body">
                <div
                  v-if="sizeGuideOpen"
                  class="size-modal-overlay"
                  @click.self="sizeGuideOpen = false"
                >
                  <div class="size-modal-card">
                    <button
                      class="modal-close"
                      type="button"
                      @click="sizeGuideOpen = false"
                    >
                      ×
                    </button>

                    <div class="size-modal-header">
                      <p class="label light">{{ t("ck.sizechart.label") }}</p>
                      <h3>{{ t("ck.sizechart.note") }}</h3>
                    </div>

                    <!-- BMI Calculator -->
                    <div class="bmi-calculator">
                      <p class="bmi-title">Find Your Perfect Size</p>
                      <div class="bmi-inputs">
                        <div class="bmi-field">
                          <label>Weight (kg)</label>
                          <input
                            type="number"
                            min="30"
                            max="200"
                            v-model.number="bmiWeight"
                            placeholder="e.g. 72"
                          />
                        </div>
                        <div class="bmi-field">
                          <label>Height (cm)</label>
                          <input
                            type="number"
                            min="100"
                            max="250"
                            v-model.number="bmiHeight"
                            placeholder="e.g. 175"
                          />
                        </div>
                      </div>
                      <div v-if="calculatedBMI" class="bmi-result">
                        <p class="bmi-value">
                          BMI: {{ calculatedBMI.toFixed(1) }}
                        </p>
                        <p class="bmi-suggestion">
                          Recommended size:
                          <strong>{{ bmiSuggestedSize }}</strong>
                        </p>
                        <button
                          class="btn-apply-size"
                          type="button"
                          @click="applyBmiSize"
                        >
                          Select {{ bmiSuggestedSize }}
                        </button>
                      </div>
                    </div>

                    <!-- Size Chart Table -->
                    <div class="size-table-wrapper">
                      <div class="size-table-header">
                        <span>Size</span>
                        <span>Hip (cm)</span>
                        <span>Weight (kg)</span>
                      </div>
                      <div
                        class="size-row"
                        v-for="item in sizeChart"
                        :key="item.label"
                        :class="{
                          highlighted: bmiSuggestedSize === item.label,
                        }"
                        @click="selectSizeFromChart(item.label)"
                      >
                        <span class="size-label">{{ item.label }}</span>
                        <span class="size-hip">{{ item.hip }}</span>
                        <span class="size-weight">{{ item.weightRange }}</span>
                      </div>
                    </div>

                    <p class="size-footnote">
                      {{ t("ck.sizechart.measurement") }}
                    </p>
                  </div>
                </div>
              </Teleport>

              <!-- Size pills -->
              <div class="select-group">
                <label class="select-group-label">{{
                  t("ck.order.size")
                }}</label>
                <div class="select-pills">
                  <button
                    v-for="sz in sizes"
                    :key="sz"
                    type="button"
                    class="select-pill"
                    :class="{ active: order.size === sz }"
                    @click="order.size = sz"
                  >
                    {{ sz }}
                  </button>
                </div>
              </div>

              <!-- Colour pack tiles + preview image -->
              <div class="select-group">
                <label class="select-group-label">
                  {{ t("ck.order.colorPack") }}
                  <span class="opt">{{ t("ck.order.colorPackSub") }}</span>
                </label>
                <div class="color-selection-area">
                  <div class="select-tiles color-tiles">
                    <button
                      v-for="c in colorOptions"
                      :key="c.value"
                      type="button"
                      class="select-tile color-tile"
                      :class="{ active: order.color === c.value }"
                      @click="order.color = c.value"
                    >
                      <div class="color-swatches">
                        <span
                          v-for="swatch in c.swatches"
                          :key="swatch"
                          class="swatch"
                          :style="{ background: swatch }"
                        ></span>
                      </div>
                      <span class="tile-name">{{ c.label }}</span>
                    </button>
                  </div>

                  <!-- Order preview image -->
                  <div
                    v-if="order.sku && order.color"
                    class="order-preview-img"
                  >
                    <NuxtImg
                      v-if="order.sku === 'ck-boxer'"
                      :src="`/products/Boxer/${orderPreviewColor}.png`"
                      :width="200"
                      :height="250"
                      format="webp"
                      quality="85"
                      fit="cover"
                      :alt="`CK Boxer ${orderPreviewColor}`"
                      loading="lazy"
                    />
                    <NuxtImg
                      v-else
                      src="/products/Brief/Black.png"
                      :width="200"
                      :height="250"
                      format="webp"
                      quality="85"
                      fit="cover"
                      alt="CK Brief"
                      loading="lazy"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Validation hint -->
          <p v-if="orderValidationMsg" class="order-validation">
            {{ orderValidationMsg }}
          </p>

          <div v-if="orderState === 'error'" class="form-error">
            {{ t("common.error") }}
          </div>

          <!-- Summary bar -->
          <div
            v-if="order.sku && order.size && order.color"
            class="order-summary"
          >
            <div class="summary-row">
              <span>{{ skuLabel }} · {{ order.size }} · {{ colorLabel }}</span>
              <span class="summary-price"
                >RM {{ orderPrice }}
                <s class="summary-original"
                  >RM {{ Math.round(orderPrice / 0.8) }}</s
                ></span
              >
            </div>
            <div class="summary-coupon">{{ t("ck.order.codeApplied") }}</div>
          </div>

          <button
            type="submit"
            class="btn-order-submit"
            :disabled="orderState === 'loading'"
          >
            <span v-if="orderState === 'idle' || orderState === 'error'">
              {{ t("ck.order.submit", { price: orderPrice }) }}
            </span>
            <span v-else class="loading-dots dark"
              ><span></span><span></span><span></span
            ></span>
          </button>

          <p class="form-fine">{{ t("ck.order.fine") }}</p>
        </form>

        <div
          v-if="orderState === 'success'"
          class="success-message order-success"
        >
          <div class="success-icon">✓</div>
          <p class="success-title">{{ t("ck.order.successTitle") }}</p>
          <p class="success-body">
            {{
              t("ck.order.successBody", {
                name: `${order.firstName} ${order.lastName}`,
                sku: skuLabel,
                size: order.size,
                color: colorLabel,
                phone: order.phone,
              })
            }}
          </p>
        </div>
      </div>
    </section>

    <div
      v-if="sizeModalOpen"
      class="size-modal-overlay"
      @click.self="sizeModalOpen = false"
    >
      <div class="size-modal-card">
        <button
          class="modal-close"
          type="button"
          @click="sizeModalOpen = false"
        >
          ×
        </button>
        <h3>Size Navigator</h3>
        <p>Enter your weight (kg) — the chart auto-selects the best match.</p>
        <div class="modal-input-group">
          <label>Weight (kg)</label>
          <input
            type="number"
            min="30"
            max="150"
            v-model.number="weightInput"
            placeholder="e.g. 72"
          />
        </div>
        <div class="modal-suggestion" v-if="suggestedSize">
          Suggested size: <strong>{{ suggestedSize }}</strong>
          <p class="modal-suggestion-note">{{ suggestedWeightRange }}</p>
        </div>
        <div class="modal-actions">
          <button class="btn-primary" type="button" @click="applySuggestedSize">
            Pick suggested
          </button>
          <button class="btn-link" type="button" @click="sizeModalOpen = false">
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-inner">
        <span class="footer-logo">driip<span class="dash">-</span></span>
        <div class="footer-links">
          <a
            href="https://www.facebook.com/profile.php?id=61586812299701"
            target="_blank"
            rel="noopener"
          >
            {{ t("footer.facebook") }}
          </a>
        </div>
        <span class="footer-copy">{{ t("footer.copyright") }}</span>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { vietnamProvinces } from "~/data/vietnam-addresses";
import { useMetaEvents } from "~/composables/useMetaEvents";

const { t, locale, setLocale } = useI18n();

// ─── Types ───────────────────────────────────────────────────────
interface SizeChartItem {
  label: string;
  hip: string;
  weightRange: string;
  minWeight: number;
  maxWeight: number;
}

interface SkuOption {
  value: string;
  label: string;
  price: number;
}

interface ColorOption {
  value: string;
  label: string;
  swatches: string[];
}

interface BoxerColor {
  value: string;
  bg: string;
}

type FormState = "idle" | "loading" | "success" | "error";

// ─── Head ────────────────────────────────────────────────────────
useHead({
  title: computed(() =>
    locale.value === "vi"
      ? "driip- | CK Boxer & Brief — First Drop SS26"
      : "driip- | CK Boxer & Brief — First Drop SS26"
  ),
  htmlAttrs: { lang: locale.value },
  meta: [
    {
      name: "description",
      content:
        locale.value === "vi"
          ? "Calvin Klein Boxer & Brief SS26 — Đặt hàng sớm với mã DRIIP20 giảm 20%. Bộ 3 sản phẩm chất liệu modal cao cấp."
          : "Calvin Klein Boxer & Brief — SS26 first drop. Order now with 20% off. Use code DRIIP20. Premium modal-cotton 3-piece sets.",
    },
    { property: "og:title", content: "driip- | CK Boxer & Brief" },
    {
      property: "og:description",
      content: "Two fits. One standard. Order now with 20% off.",
    },
  ],
});

// ─── Product data ────────────────────────────────────────────────
const boxerColors: BoxerColor[] = [
  { value: "Black", bg: "#111" },
  { value: "Gray", bg: "#888" },
  { value: "White", bg: "#f0f0f0" },
];

const skuOptions: SkuOption[] = [
  { value: "ck-brief", label: "CK BRIEF", price: 79 },
  { value: "ck-boxer", label: "CK BOXER", price: 95 },
];

const sizes: string[] = ["S", "M", "L", "XL", "2XL"];

const sizeChart: SizeChartItem[] = [
  {
    label: "S",
    hip: "Hông 86–92 cm",
    weightRange: "58–66 kg",
    minWeight: 58,
    maxWeight: 66,
  },
  {
    label: "M",
    hip: "Hông 93–98 cm",
    weightRange: "67–74 kg",
    minWeight: 67,
    maxWeight: 74,
  },
  {
    label: "L",
    hip: "Hông 99–104 cm",
    weightRange: "75–84 kg",
    minWeight: 75,
    maxWeight: 84,
  },
  {
    label: "XL",
    hip: "Hông 105–110 cm",
    weightRange: "85–93 kg",
    minWeight: 85,
    maxWeight: 93,
  },
  {
    label: "2XL",
    hip: "Hông 111–116 cm",
    weightRange: "94–102 kg",
    minWeight: 94,
    maxWeight: 102,
  },
];

const briefImages: string[] = ["Black", "Gray", "White"];

const colorOptions = computed<ColorOption[]>(() => [
  {
    value: "3x-black",
    label: t("ck.order.colors.black"),
    swatches: ["#111", "#111", "#111"],
  },
  {
    value: "3x-white",
    label: t("ck.order.colors.white"),
    swatches: ["#f0f0f0", "#f0f0f0", "#f0f0f0"],
  },
  {
    value: "3x-grey",
    label: t("ck.order.colors.grey"),
    swatches: ["#888", "#888", "#888"],
  },
  {
    value: "mix",
    label: t("ck.order.colors.mix"),
    swatches: ["#111", "#888", "#f0f0f0"],
  },
]);

const colorToImage: Record<string, string> = {
  "3x-black": "Black",
  "3x-white": "White",
  "3x-grey": "Gray",
  mix: "Black",
};

// ─── State ───────────────────────────────────────────────────────
const boxerColor = ref<string>("Black");
const briefImg = ref<number>(1);
const codeCopied = ref<boolean>(false);
const accessState = ref<FormState>("idle");
const orderState = ref<FormState>("idle");
const productsRef = ref<HTMLElement | null>(null);
const viewContentFired = ref<boolean>(false);

// Size modal state
const sizeModalOpen = ref<boolean>(false);
const weightInput = ref<number>(72);

// Size guide modal state
const sizeGuideOpen = ref<boolean>(false);
const bmiWeight = ref<number | null>(null);
const bmiHeight = ref<number | null>(null);

// Form state
const access = reactive<{ name: string; email: string; phone: string }>({
  name: "",
  email: "",
  phone: "",
});

const order = reactive<{
  firstName: string;
  lastName: string;
  phone: string;
  province: string;
  district: string;
  ward: string;
  street: string;
  sku: string;
  size: string;
  color: string;
}>({
  firstName: "",
  lastName: "",
  phone: "",
  province: "",
  district: "",
  ward: "",
  street: "",
  sku: "",
  size: "",
  color: "",
});

// ─── Computed ────────────────────────────────────────────────────
const selectedProvince = computed(
  () => vietnamProvinces.find((p) => p.name === order.province) ?? null
);

const orderPreviewColor = computed<string>(
  () => colorToImage[order.color] ?? "Black"
);

const orderPrice = computed<number>(() => {
  const base =
    order.sku === "ck-brief" ? 79 : order.sku === "ck-boxer" ? 95 : 89;
  return Math.round(base * 0.8);
});

const skuLabel = computed<string>(
  () => skuOptions.find((s) => s.value === order.sku)?.label ?? ""
);

const colorLabel = computed<string>(
  () => colorOptions.value.find((c) => c.value === order.color)?.label ?? ""
);

const orderValidationMsg = computed<string>(() => {
  if (!order.sku) return t("ck.order.validate.sku");
  if (!order.size) return t("ck.order.validate.size");
  if (!order.color) return t("ck.order.validate.color");
  return "";
});

// Size suggestion computed
const suggestedSize = computed<string | null>(() => {
  const weight = weightInput.value;
  const match = sizeChart.find(
    (item) => weight >= item.minWeight && weight <= item.maxWeight
  );
  return match?.label ?? null;
});

const suggestedWeightRange = computed<string>(() => {
  const match = sizeChart.find((item) => item.label === suggestedSize.value);
  return match ? match.weightRange : "";
});

// BMI computed
const calculatedBMI = computed<number | null>(() => {
  if (bmiWeight.value && bmiHeight.value && bmiHeight.value > 0) {
    const heightInMeters = bmiHeight.value / 100;
    return bmiWeight.value / (heightInMeters * heightInMeters);
  }
  return null;
});

const bmiSuggestedSize = computed<string | null>(() => {
  if (!calculatedBMI.value || !bmiWeight.value) return null;
  const weight = bmiWeight.value;
  if (weight < 55) return "S";
  if (weight < 70) return "M";
  if (weight < 85) return "L";
  return "XL";
});

// ─── Meta events ─────────────────────────────────────────────────
const {
  trackViewContent,
  trackPurchase,
  trackInitiateCheckout,
  trackLead,
  trackSubscribe,
  setupScrollDepth,
} = useMetaEvents();

// ─── Lifecycle ───────────────────────────────────────────────────
onMounted(() => {
  setupScrollDepth();
  setupParallax();
  setupRevealObserver();
  setupViewContentObserver();
});

// ─── Setup functions ─────────────────────────────────────────────
function setupParallax(): void {
  const root = document.documentElement;
  const onScroll = (): void =>
    root.style.setProperty("--scroll-y", window.scrollY.toString());
  window.addEventListener("scroll", onScroll, { passive: true });
  onUnmounted(() => window.removeEventListener("scroll", onScroll));
}

function setupRevealObserver(): void {
  const observer = new IntersectionObserver(
    (entries) =>
      entries.forEach((e) => {
        if (e.isIntersecting) e.target.classList.add("is-visible");
      }),
    { threshold: 0.12 }
  );
  document
    .querySelectorAll(".reveal, .product-card")
    .forEach((el) => observer.observe(el));
  onUnmounted(() => observer.disconnect());
}

function setupViewContentObserver(): void {
  if (!productsRef.value) return;
  const observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting && !viewContentFired.value) {
        viewContentFired.value = true;
        trackViewContent();
        observer.disconnect();
      }
    },
    { threshold: 0.25 }
  );
  observer.observe(productsRef.value);
  onUnmounted(() => observer.disconnect());
}

// ─── Language actions ────────────────────────────────────────────
function switchLang(): void {
  setLocale(locale.value === "vi" ? "en" : "vi");
}

// ─── Navigation actions ──────────────────────────────────────────
function onHeroCTA(): void {
  trackInitiateCheckout();
  document.getElementById("order")?.scrollIntoView({ behavior: "smooth" });
}

function prefillOrder(sku: string): void {
  trackViewContent(sku);
  order.sku = sku;
  document.getElementById("order")?.scrollIntoView({ behavior: "smooth" });
}

// ─── Form actions ────────────────────────────────────────────────
function onProvinceChange(): void {
  order.district = "";
}

// ─── Size selection actions ──────────────────────────────────────
function applySuggestedSize(): void {
  const suggestion = suggestedSize.value;
  if (suggestion) {
    order.size = suggestion;
    sizeModalOpen.value = false;
  }
}

function applyBmiSize(): void {
  if (bmiSuggestedSize.value) {
    order.size = bmiSuggestedSize.value;
    sizeGuideOpen.value = false;
  }
}

function selectSizeFromChart(size: string): void {
  order.size = size;
  sizeGuideOpen.value = false;
}

// ─── Image carousel actions ──────────────────────────────────────
function briefImgPrev(): void {
  briefImg.value =
    briefImg.value === 1 ? briefImages.length : briefImg.value - 1;
}

function briefImgNext(): void {
  briefImg.value =
    briefImg.value === briefImages.length ? 1 : briefImg.value + 1;
}

// ─── Clipboard actions ───────────────────────────────────────────
async function copyCode(): Promise<void> {
  await navigator.clipboard.writeText("DRIIP20");
  codeCopied.value = true;
  setTimeout(() => {
    codeCopied.value = false;
  }, 2000);
}

// ─── API submissions ─────────────────────────────────────────────
async function submitAccess(): Promise<void> {
  accessState.value = "loading";
  try {
    await $fetch("/api/subscribe", {
      method: "POST",
      body: {
        name: access.name,
        email: access.email,
        phone: access.phone,
        coupon: "DRIIP20",
        timestamp: new Date().toISOString(),
      },
    });
    trackLead(access.email, access.phone);
    trackSubscribe(access.email, access.phone);
    accessState.value = "success";
  } catch {
    accessState.value = "error";
    setTimeout(() => {
      accessState.value = "idle";
    }, 3000);
  }
}

async function submitOrder(): Promise<void> {
  if (orderValidationMsg.value) return;

  orderState.value = "loading";
  try {
    await $fetch("/api/order", {
      method: "POST",
      body: {
        firstName: order.firstName,
        lastName: order.lastName,
        phone: order.phone,
        province: order.province,
        district: order.district,
        ward: order.ward,
        street: order.street,
        sku: order.sku,
        size: order.size,
        color: order.color,
        coupon: "DRIIP20",
        timestamp: new Date().toISOString(),
      },
    });

    trackPurchase({
      firstName: order.firstName,
      lastName: order.lastName,
      phone: order.phone,
      city: order.province,
      sku: order.sku,
      value: orderPrice.value,
    });

    orderState.value = "success";
  } catch {
    orderState.value = "error";
    setTimeout(() => {
      orderState.value = "idle";
    }, 3000);
  }
}
</script>

<style scoped>
/* ─── NAV ──────────────────────────────────────────────────────── */
.nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: rgba(0, 0, 0, 0.88);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
.nav-logo {
  font-family: var(--font-display);
  font-size: 24px;
  letter-spacing: 0.1em;
  color: var(--white);
  text-decoration: none;
}
.nav-right {
  display: flex;
  align-items: center;
  gap: 16px;
}
.lang-switch {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
  border: 1px solid var(--grey-700);
  padding: 4px 10px;
  background: transparent;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}
.lang-switch:hover {
  color: var(--white);
  border-color: var(--white);
}

/* ─── HERO ─────────────────────────────────────────────────────── */
.hero {
  position: relative;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  background: var(--black);
  overflow: hidden;
  padding: 100px 24px 60px;
}

.parallax-content {
  position: relative;
  z-index: 2;
  max-width: 640px;
  will-change: transform;
  transform: translateY(calc(var(--scroll-y, 0) * -0.06px));
}

.hero-pre {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.3em;
  color: var(--grey-400);
  margin-bottom: 28px;
}

.hero-title {
  display: flex;
  flex-direction: column;
  line-height: 0.88;
  margin-bottom: 36px;
}

.line-1 {
  font-family: var(--font-display);
  font-size: clamp(64px, 14vw, 130px);
  color: var(--grey-700);
  letter-spacing: 0.05em;
}
.line-2 {
  font-family: var(--font-display);
  font-size: clamp(96px, 22vw, 210px);
  color: var(--white);
  letter-spacing: -0.02em;
}
.line-3 {
  font-family: var(--font-display);
  font-size: clamp(72px, 16vw, 150px);
  color: var(--white);
  letter-spacing: -0.02em;
  display: flex;
  align-items: baseline;
  gap: 16px;
}
.dash-end {
  font-family: var(--font-display);
  font-size: clamp(48px, 10vw, 100px);
  color: var(--grey-700);
}

.hero-sub {
  font-size: clamp(12px, 2vw, 15px);
  font-weight: 300;
  letter-spacing: 0.25em;
  color: var(--grey-400);
  text-transform: uppercase;
  line-height: 1.8;
  margin-bottom: 52px;
  border-left: 1px solid var(--grey-700);
  padding-left: 16px;
  white-space: pre-line;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 16px 32px;
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: background 0.2s, gap 0.2s;
}
.btn-primary:hover {
  background: var(--off-white);
  gap: 20px;
}
.btn-arrow {
  font-size: 16px;
}

.parallax-bg {
  position: absolute;
  right: -8%;
  top: 50%;
  will-change: transform;
  transform: translateY(calc(-50% + var(--scroll-y, 0) * 0.35px));
  font-family: var(--font-display);
  font-size: clamp(180px, 44vw, 580px);
  color: rgba(255, 255, 255, 0.03);
  letter-spacing: -0.05em;
  pointer-events: none;
  user-select: none;
  line-height: 1;
}

/* ─── PROMO STRIP ──────────────────────────────────────────────── */
.promo-strip {
  background: var(--white);
  color: var(--black);
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 12px 24px;
  overflow: hidden;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  white-space: nowrap;
}
.promo-strip .dot {
  color: #aaa;
  flex-shrink: 0;
}

/* ─── PRODUCTS ─────────────────────────────────────────────────── */
.products {
  background: var(--black);
  padding: 80px 24px;
}
.products-inner {
  max-width: 1100px;
  margin: 0 auto;
}

.products-title {
  font-family: var(--font-display);
  font-size: clamp(48px, 9vw, 96px);
  line-height: 0.9;
  color: var(--white);
  letter-spacing: -0.01em;
  margin-bottom: 64px;
  white-space: pre-line;
}

.product-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2px;
}

.product-card {
  background: var(--grey-900);
  opacity: 0;
  transform: translateY(32px);
  transition: opacity 0.75s ease, transform 0.75s ease;
  overflow: hidden;
}
.product-card.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* Image slot */
.product-img {
  position: relative;
  width: 100%;
  aspect-ratio: 3/4;
  background: #1a1a1a;
  overflow: hidden;
}

.product-img-el {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
  transition: transform 0.6s ease;
}
.product-card:hover .product-img-el {
  transform: scale(1.04);
}

/* Brief gallery controls */
.brief-gallery {
  position: relative;
}

.gallery-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 4;
  background: rgba(0, 0, 0, 0.55);
  color: var(--white);
  border: none;
  width: 36px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s, background 0.2s;
}
.brief-gallery:hover .gallery-arrow {
  opacity: 1;
}
.gallery-prev {
  left: 0;
}
.gallery-next {
  right: 0;
}
.gallery-arrow:hover {
  background: rgba(0, 0, 0, 0.82);
}

.gallery-dots {
  position: absolute;
  bottom: 14px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 6px;
  z-index: 4;
}
.gallery-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.35);
  border: none;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}
.gallery-dot.active {
  background: var(--white);
  transform: scale(1.25);
}

/* Boxer color swatches */
.boxer-swatches {
  position: absolute;
  bottom: 14px;
  left: 14px;
  display: flex;
  gap: 6px;
  z-index: 4;
}
.boxer-swatch {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.3);
  cursor: pointer;
  transition: border-color 0.2s, transform 0.15s;
}
.boxer-swatch.active {
  border-color: var(--white);
  transform: scale(1.2);
}
.boxer-swatch:hover:not(.active) {
  border-color: rgba(255, 255, 255, 0.7);
}

/* Product card body */
.product-card-body {
  padding: 28px 24px 32px;
}

.product-card-top {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 12px;
}

.product-name {
  font-family: var(--font-display);
  font-size: clamp(32px, 6vw, 52px);
  letter-spacing: 0.02em;
  color: var(--white);
}

.product-badge {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-400);
  border: 1px solid var(--grey-700);
  padding: 4px 10px;
  white-space: nowrap;
}

.product-divider {
  width: 32px;
  height: 1px;
  background: var(--grey-700);
  margin-bottom: 14px;
}

.product-desc {
  font-size: 14px;
  font-weight: 300;
  color: var(--grey-400);
  line-height: 1.7;
  margin-bottom: 16px;
  max-width: 440px;
}

.product-specs {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 24px;
}
.product-specs li {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.15em;
  color: var(--grey-700);
  display: flex;
  align-items: center;
  gap: 8px;
}
.product-specs li::before {
  content: "";
  width: 16px;
  height: 1px;
  background: var(--grey-700);
  flex-shrink: 0;
}

.btn-order-now {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  color: var(--white);
  border: 1px solid var(--grey-700);
  padding: 12px 24px;
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
}
.btn-order-now:hover {
  border-color: var(--white);
  background: rgba(255, 255, 255, 0.05);
}

/* ─── FABRIC BAR ───────────────────────────────────────────────── */
.fabric-bar {
  background: var(--grey-900);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px 20px;
  padding: 20px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}
.fabric-item {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
}
.fabric-sep {
  color: var(--grey-700);
  font-size: 12px;
}

/* ─── OFFER ────────────────────────────────────────────────────── */
.offer {
  background: var(--off-white);
  color: var(--black);
  padding: 80px 24px;
}
.offer-inner {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr;
  gap: 48px;
}

.label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  margin-bottom: 16px;
  display: block;
}
.label.light {
  color: var(--grey-400);
}

.offer-title {
  font-family: var(--font-display);
  font-size: clamp(52px, 10vw, 88px);
  line-height: 0.95;
  letter-spacing: -0.01em;
  color: var(--black);
  margin-bottom: 20px;
  white-space: pre-line;
}
.offer-body {
  font-size: 14px;
  font-weight: 300;
  color: var(--grey-700);
  line-height: 1.7;
  max-width: 380px;
}

.coupon-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  margin-bottom: 12px;
}

.coupon-box {
  display: flex;
  align-items: stretch;
  border: 1.5px solid var(--black);
  margin-bottom: 12px;
}
.coupon-code {
  font-family: var(--font-display);
  font-size: clamp(28px, 6vw, 44px);
  letter-spacing: 0.15em;
  padding: 16px 24px;
  flex: 1;
  background: var(--black);
  color: var(--white);
  display: flex;
  align-items: center;
}
.copy-btn {
  padding: 0 24px;
  background: transparent;
  border: none;
  border-left: 1.5px solid var(--black);
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  color: var(--black);
  transition: background 0.15s, color 0.15s;
  min-width: 90px;
}
.copy-btn:hover,
.copy-btn.copied {
  background: var(--black);
  color: var(--white);
}
.coupon-note {
  font-size: 11px;
  color: var(--grey-400);
  letter-spacing: 0.05em;
}

/* ─── MANIFESTO ────────────────────────────────────────────────── */
.manifesto {
  background: var(--black);
  padding: 100px 24px;
  text-align: center;
}
.manifesto-inner {
  max-width: 700px;
  margin: 0 auto;
}
.manifesto-text {
  font-family: "Cormorant Garamond", serif;
  font-style: italic;
  font-size: clamp(26px, 5vw, 44px);
  font-weight: 300;
  line-height: 1.65;
  color: var(--grey-100);
  letter-spacing: 0.02em;
  white-space: pre-line;
}

/* ─── ACCESS FORM ──────────────────────────────────────────────── */
.access {
  background: var(--grey-900);
  padding: 80px 24px 100px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}
.access-inner {
  max-width: 720px;
  margin: 0 auto;
}
.access-header {
  margin-bottom: 56px;
}
.access-title {
  font-family: var(--font-display);
  font-size: clamp(44px, 9vw, 76px);
  line-height: 0.95;
  letter-spacing: -0.01em;
  color: var(--white);
  margin-bottom: 20px;
  white-space: pre-line;
}
.access-sub {
  font-size: 14px;
  font-weight: 300;
  color: var(--grey-400);
  line-height: 1.7;
}

/* ─── ORDER SECTION ────────────────────────────────────────────── */
.order-section {
  background: var(--black);
  padding: 80px 24px 120px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.order-inner {
  max-width: 760px;
  margin: 0 auto;
}
.order-header {
  margin-bottom: 60px;
}

.order-title {
  font-family: var(--font-display);
  font-size: clamp(52px, 11vw, 96px);
  line-height: 0.9;
  color: var(--white);
  letter-spacing: -0.01em;
  margin-bottom: 20px;
}
.order-sub {
  font-size: 14px;
  font-weight: 300;
  color: var(--grey-400);
  line-height: 1.7;
}

.order-form {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.order-group {
  margin-bottom: 0;
}

.order-group-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.3em;
  color: var(--grey-700);
  padding: 28px 0 0;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: block;
  margin-bottom: 0;
}
.order-fields {
  padding: 0;
}

/* ─── FORM SHARED ──────────────────────────────────────────────── */
.form,
.order-form {
  display: flex;
  flex-direction: column;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.form-row.single {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.form-field {
  display: flex;
  flex-direction: column;
  padding: 20px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  gap: 8px;
}
.form-row.single .form-field {
  border-bottom: none;
}

.form-field label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
}
.opt {
  font-size: 9px;
  color: var(--grey-700);
  letter-spacing: 0.15em;
}

.form-field input,
.form-field textarea,
.form-field select {
  background: transparent;
  border: none;
  outline: none;
  font-family: var(--font-body);
  font-size: 18px;
  font-weight: 300;
  color: var(--white);
  padding: 4px 0;
  resize: vertical;
  appearance: none;
  -webkit-appearance: none;
}
.form-field input::placeholder,
.form-field textarea::placeholder {
  color: var(--grey-700);
}
.form-field select option {
  background: #111;
  color: white;
}

/* Placeholder style for select when empty */
.form-field select:invalid,
.form-field select option[disabled] {
  color: var(--grey-700);
}

.form-error {
  margin: 16px 0;
  padding: 12px 16px;
  border: 1px solid rgba(255, 80, 80, 0.4);
  color: #ff6b6b;
  font-size: 12px;
  letter-spacing: 0.1em;
}

/* ─── SELECTION TILES & PILLS ──────────────────────────────────── */
.select-group {
  padding: 20px 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.select-group-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
  display: block;
  margin-bottom: 16px;
}

.select-tiles {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.select-tile {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 16px;
  background: transparent;
  border: 1px solid var(--grey-700);
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  text-align: left;
}
.select-tile:hover {
  border-color: var(--grey-400);
}
.select-tile.active {
  border-color: var(--white);
  background: rgba(255, 255, 255, 0.04);
}

.tile-name {
  font-family: var(--font-display);
  font-size: 20px;
  letter-spacing: 0.08em;
  color: var(--white);
}
.tile-price {
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.15em;
  color: var(--grey-400);
}

/* Size pills */
.select-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.select-pill {
  width: 52px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid var(--grey-700);
  font-family: var(--font-body);
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: var(--grey-400);
  cursor: pointer;
  transition: border-color 0.15s, color 0.15s, background 0.15s;
}
.select-pill:hover {
  border-color: var(--grey-400);
  color: var(--white);
}
.select-pill.active {
  border-color: var(--white);
  color: var(--white);
  background: rgba(255, 255, 255, 0.06);
}

/* Colour tiles + preview */
.color-selection-area {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.color-tiles {
  grid-template-columns: 1fr 1fr;
}

.color-tile {
  gap: 10px;
}

.color-swatches {
  display: flex;
  gap: 4px;
}

.swatch {
  width: 14px;
  height: 14px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  flex-shrink: 0;
}

/* Order preview image */
.order-preview-img {
  width: 200px;
  aspect-ratio: 3/4;
  overflow: hidden;
  background: #1a1a1a;
  align-self: flex-start;
}
.order-preview-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
}

/* ─── ORDER SUMMARY ────────────────────────────────────────────── */
.order-validation {
  margin: 12px 0 0;
  font-size: 11px;
  color: #ff9f6b;
  letter-spacing: 0.1em;
}

.order-summary {
  margin-top: 32px;
  padding: 20px 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.02);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-size: 13px;
  font-weight: 500;
  letter-spacing: 0.1em;
  color: var(--white);
  margin-bottom: 6px;
}

.summary-price {
  font-family: var(--font-display);
  font-size: 22px;
  letter-spacing: 0.05em;
}

.summary-original {
  font-size: 14px;
  color: var(--grey-700);
  text-decoration: line-through;
  margin-left: 8px;
}

.summary-coupon {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.2em;
  color: var(--grey-400);
}

/* ─── SUBMIT BUTTONS ───────────────────────────────────────────── */
.btn-submit,
.btn-order-submit {
  margin-top: 40px;
  width: 100%;
  background: var(--white);
  color: var(--black);
  border: none;
  padding: 20px;
  font-family: var(--font-body);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.2em;
  cursor: pointer;
  transition: background 0.2s, opacity 0.2s;
}
.btn-submit:hover:not(:disabled),
.btn-order-submit:hover:not(:disabled) {
  background: var(--off-white);
}
.btn-submit:disabled,
.btn-order-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.form-fine {
  margin-top: 16px;
  font-size: 10px;
  color: var(--grey-700);
  text-align: center;
  letter-spacing: 0.05em;
  line-height: 1.6;
}

/* Loading dots */
.loading-dots {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.loading-dots span {
  width: 5px;
  height: 5px;
  background: var(--black);
  border-radius: 50%;
  animation: bounce 0.6s infinite alternate;
}
.loading-dots.dark span {
  background: var(--black);
}
.loading-dots span:nth-child(2) {
  animation-delay: 0.2s;
}
.loading-dots span:nth-child(3) {
  animation-delay: 0.4s;
}
@keyframes bounce {
  from {
    opacity: 0.3;
    transform: translateY(0);
  }
  to {
    opacity: 1;
    transform: translateY(-4px);
  }
}

/* ─── SUCCESS ──────────────────────────────────────────────────── */
.success-message {
  margin-top: 56px;
  padding: 48px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  text-align: center;
  animation: fadeIn 0.4s ease;
}
.order-success {
  margin-top: 40px;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.success-icon {
  font-size: 32px;
  margin-bottom: 16px;
}
.success-title {
  font-family: var(--font-display);
  font-size: 36px;
  letter-spacing: 0.1em;
  color: var(--white);
  margin-bottom: 16px;
}
.success-body {
  font-size: 14px;
  font-weight: 300;
  color: var(--grey-400);
  line-height: 1.8;
}
.success-body strong {
  color: var(--white);
}

/* ─── FOOTER ───────────────────────────────────────────────────── */
.footer {
  background: var(--black);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding: 32px 24px;
}
.footer-inner {
  max-width: 1100px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  text-align: center;
}
.footer-logo {
  font-family: var(--font-display);
  font-size: 28px;
  letter-spacing: 0.1em;
}
.footer-links {
  display: flex;
  gap: 24px;
}
.footer-links a {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.25em;
  color: var(--grey-400);
  text-decoration: none;
  transition: color 0.2s;
}
.footer-links a:hover {
  color: var(--white);
}
.footer-copy {
  font-size: 9px;
  color: var(--grey-700);
  letter-spacing: 0.2em;
}
.dash {
  color: var(--grey-400);
}

/* ─── SCROLL REVEAL ────────────────────────────────────────────── */
.reveal {
  opacity: 0;
  transform: translateY(28px);
  transition: opacity 0.75s ease, transform 0.75s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* ─── TABLET 640px ─────────────────────────────────────────────── */
@media (min-width: 640px) {
  .product-grid {
    grid-template-columns: 1fr 1fr;
    gap: 2px;
  }
  .offer-inner {
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }
  .select-tiles {
    grid-template-columns: 1fr 1fr;
  }
  .color-tiles {
    grid-template-columns: repeat(4, 1fr);
  }
  .color-selection-area {
    flex-direction: row;
    align-items: flex-start;
    gap: 28px;
  }
  .color-selection-area .select-tiles {
    flex: 1;
  }

  .form-row:not(.single) {
    grid-template-columns: 1fr 1fr;
  }
  .form-row:not(.single) .form-field {
    border-bottom: none;
  }
  .form-row:not(.single) .form-field:first-child {
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    padding-right: 32px;
  }
  .form-row:not(.single) .form-field:last-child {
    padding-left: 32px;
  }

  .footer-inner {
    flex-direction: row;
    justify-content: space-between;
    text-align: left;
  }
}

/* ─── DESKTOP 1024px ───────────────────────────────────────────── */
@media (min-width: 1024px) {
  .nav {
    padding: 24px 64px;
  }
  .hero {
    padding: 120px 64px 80px;
  }
  .products {
    padding: 100px 64px;
  }
  .offer {
    padding: 100px 64px;
  }
  .manifesto {
    padding: 140px 64px;
  }
  .access {
    padding: 100px 64px 120px;
  }
  .order-section {
    padding: 100px 64px 140px;
  }
  .footer {
    padding: 40px 64px;
  }

  .promo-strip {
    justify-content: center;
    gap: 32px;
    font-size: 11px;
    padding: 14px 64px;
  }
  .fabric-bar {
    flex-wrap: nowrap;
    padding: 20px 64px;
    gap: 28px;
  }
  .footer-inner {
    max-width: 1100px;
  }
}
</style>
