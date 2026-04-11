import type { RouterConfig } from "@nuxt/schema";

export default <RouterConfig>{
  // Scroll behavior for smooth navigation
  scrollBehavior(to, from, savedPosition) {
    // If there's a saved position (back/forward navigation), use it
    if (savedPosition) {
      return savedPosition;
    }

    // If there's a hash, scroll to the element
    if (to.hash) {
      return {
        el: to.hash,
        behavior: "smooth",
      };
    }

    // Otherwise scroll to top
    return { top: 0, behavior: "smooth" };
  },
};
