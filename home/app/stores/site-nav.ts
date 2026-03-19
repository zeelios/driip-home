import { ref } from "vue";
import { defineStore } from "pinia";

export interface SiteNavLink {
  id: string;
  label: string;
}

export interface SiteNavConfig {
  title?: string;
  links?: SiteNavLink[];
  ctaLabel?: string;
  ctaTarget?: string;
}

export const useSiteNavStore = defineStore("site-nav", () => {
  const title = ref<string>("");
  const links = ref<SiteNavLink[]>([]);
  const activeSection = ref<string>("");
  const ctaLabel = ref<string>("");
  const ctaTarget = ref<string>("");
  const scrollRequest = ref<string | null>(null);

  function setNav(config: SiteNavConfig): void {
    title.value = config.title ?? "";
    links.value = config.links ?? [];
    ctaLabel.value = config.ctaLabel ?? "";
    ctaTarget.value = config.ctaTarget ?? "";
    activeSection.value = "";
    scrollRequest.value = null;
  }

  function setActiveSection(id: string): void {
    activeSection.value = id;
  }

  function requestScroll(id: string): void {
    scrollRequest.value = id;
  }

  function clearScrollRequest(): void {
    scrollRequest.value = null;
  }

  return {
    title,
    links,
    activeSection,
    ctaLabel,
    ctaTarget,
    scrollRequest,
    setNav,
    setActiveSection,
    requestScroll,
    clearScrollRequest,
  };
});
