<template>
  <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <div
      id="kt_app_sidebar_menu_wrapper"
      class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
      ref="scrollElRef"
      data-kt-scroll="true"
      data-kt-scroll-activate="true"
      data-kt-scroll-height="auto"
      data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
      data-kt-scroll-wrappers="#kt_app_sidebar_menu"
      data-kt-scroll-offset="5px"
      data-kt-scroll-save-state="true"
    >
      <div
        id="#kt_app_sidebar_menu"
        class="menu menu-column menu-rounded menu-sub-indention px-3"
        data-kt-menu="true"
      >
        <template v-for="(item, i) in filteredMenu" :key="i">
          
          <div v-if="item.heading" class="menu-item pt-5 pb-2">
            <div class="menu-content text-muted px-2">
              <span class="menu-heading fw-bold text-uppercase fs-7 ls-1">
                {{ translate(item.heading) }}
              </span>
            </div>
          </div>

          <template v-for="(menuItem, j) in item.pages" :key="j">
            
            <div v-if="menuItem.heading" class="menu-item">
              <router-link
                v-if="menuItem.route"
                class="menu-link"
                active-class="active"
                :to="menuItem.route"
              >
                <span
                  v-if="menuItem.keenthemesIcon || menuItem.bootstrapIcon"
                  class="menu-icon"
                >
                  <KTIcon
                    v-if="sidebarMenuIcons === 'keenthemes'"
                    :icon-name="menuItem.keenthemesIcon"
                    icon-class="fs-2"
                  />
                  <i v-else :class="menuItem.bootstrapIcon" class="fs-2"></i>
                </span>
                <span class="menu-title">{{ translate(menuItem.heading) }}</span>
              </router-link>
            </div>

            <div
              v-if="menuItem.sectionTitle && menuItem.route"
              :class="{ show: hasActiveChildren(menuItem.route) }"
              class="menu-item menu-accordion"
              data-kt-menu-sub="accordion"
              data-kt-menu-trigger="click"
            >
              <span class="menu-link">
                <span
                  v-if="menuItem.keenthemesIcon || menuItem.bootstrapIcon"
                  class="menu-icon"
                >
                  <KTIcon
                    v-if="sidebarMenuIcons === 'keenthemes'"
                    :icon-name="menuItem.keenthemesIcon"
                    icon-class="fs-2"
                  />
                  <i v-else :class="menuItem.bootstrapIcon" class="fs-2"></i>
                </span>
                <span class="menu-title">{{ translate(menuItem.sectionTitle) }}</span>
                <span class="menu-arrow"></span>
              </span>
              
              <div
                :class="{ show: hasActiveChildren(menuItem.route) }"
                class="menu-sub menu-sub-accordion"
              >
                <template v-for="(item2, k) in menuItem.sub" :key="k">
                  <div v-if="item2.heading" class="menu-item">
                    <router-link
                      v-if="item2.route"
                      class="menu-link"
                      active-class="active"
                      :to="item2.route"
                    >
                      <span class="menu-bullet">
                        <span class="bullet bullet-dot"></span>
                      </span>
                      <span class="menu-title">{{ translate(item2.heading) }}</span>
                    </router-link>
                  </div>
                </template>
              </div>
            </div>
          </template>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import MainMenuConfig from "@/layouts/default-layout/config/MainMenuConfig";
import { sidebarMenuIcons } from "@/layouts/default-layout/config/helper";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import type { MenuItem } from "@/layouts/default-layout/config/types";

const { t, te } = useI18n();
const route = useRoute();
const scrollElRef = ref<null | HTMLElement>(null);
const authStore = useAuthStore();

const userRole = computed(() => authStore.userRole);

const filteredMenu = computed(() => {
  const result: MenuItem[] = [];
  for (const section of MainMenuConfig) {
    const newSection: MenuItem = { ...section, pages: [] };
    if (section.pages) {
      for (const page of section.pages) {
        const isUserMenu = page.roles?.includes('user');
        const hasAccess = isUserMenu 
          ? page.roles?.includes(userRole.value) 
          : page.name ? authStore.hasPermission(page.name) : true;

        if (hasAccess) {
          const newPage = { ...page };
          if (page.sub) {
            newPage.sub = page.sub.filter(subItem => 
              subItem.name ? authStore.hasPermission(subItem.name) : true
            );
          }
          if (!newPage.sub || newPage.sub.length > 0) {
            newSection.pages?.push(newPage);
          }
        }
      }
    }
    if (newSection.pages && newSection.pages.length > 0) {
      result.push(newSection);
    }
  }
  return result;
});

onMounted(() => {
  if (scrollElRef.value) {
    scrollElRef.value.scrollTop = 0;
  }
});

const translate = (text: string) => (te(text) ? t(text) : text);

const hasActiveChildren = (match: string) => {
  if (typeof match !== "string") return false;
  return route.path.indexOf(match) !== -1;
};
</script>

<style scoped>
/* ========================================
   PREMIUM ORANGE SIDEBAR THEME
   Modern, Smooth & Professional
   ======================================== */

/* --- 1. Base Menu Item Styles --- */
.menu-item {
    margin-bottom: 4px;
    animation: fadeInUp 0.4s ease-out backwards;
}

.menu-item:nth-child(1) { animation-delay: 0.05s; }
.menu-item:nth-child(2) { animation-delay: 0.1s; }
.menu-item:nth-child(3) { animation-delay: 0.15s; }
.menu-item:nth-child(4) { animation-delay: 0.2s; }
.menu-item:nth-child(5) { animation-delay: 0.25s; }

.menu-link {
    border-radius: 10px;
    padding: 12px 16px;
    color: #9ca3af;
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Shimmer effect on hover */
.menu-link::after {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(246, 139, 30, 0.1),
        transparent
    );
    transition: left 0.5s ease;
}

.menu-link:hover::after {
    left: 100%;
}

/* Typography */
.menu-title {
    font-weight: 500;
    font-size: 0.95rem;
    letter-spacing: 0.2px;
    transition: all 0.3s ease;
}

.menu-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    transition: all 0.35s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.menu-icon i, 
.menu-icon .svg-icon {
    color: #6b7280;
    transition: all 0.35s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    filter: drop-shadow(0 0 0 transparent);
}

/* --- 2. Hover Effects --- */
.menu-link:hover:not(.active) {
    background: linear-gradient(
        135deg,
        rgba(246, 139, 30, 0.08) 0%,
        rgba(251, 146, 60, 0.04) 100%
    );
    color: #ffffff;
    transform: translateX(6px) scale(1.02);
    border: 1px solid rgba(246, 139, 30, 0.15);
    box-shadow: 
        0 4px 15px rgba(246, 139, 30, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.menu-link:hover:not(.active) .menu-icon {
    transform: rotate(8deg) scale(1.15);
}

.menu-link:hover:not(.active) .menu-icon i,
.menu-link:hover:not(.active) .menu-icon .svg-icon {
    color: #fb923c;
    filter: drop-shadow(0 0 8px rgba(251, 146, 60, 0.4));
}

.menu-link:hover:not(.active) .menu-title {
    letter-spacing: 0.5px;
}

/* --- 3. Active State --- */
.menu-link.active {
    background: linear-gradient(
        135deg,
        rgba(246, 139, 30, 0.2) 0%,
        rgba(251, 146, 60, 0.1) 50%,
        rgba(249, 115, 22, 0.15) 100%
    );
    color: #ffffff;
    border: 1px solid rgba(246, 139, 30, 0.3);
    box-shadow: 
        0 8px 24px rgba(246, 139, 30, 0.25),
        0 4px 12px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.1),
        inset 0 -1px 0 rgba(0, 0, 0, 0.1);
    transform: translateX(4px);
}

/* Active Icon with Glow */
.menu-link.active .menu-icon {
    animation: iconBounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.menu-link.active .menu-icon i,
.menu-link.active .menu-icon .svg-icon {
    color: #F68B1E !important;
    filter: drop-shadow(0 0 12px rgba(246, 139, 30, 0.6))
            drop-shadow(0 0 4px rgba(251, 146, 60, 0.8));
    animation: iconPulse 3s ease-in-out infinite;
}

/* Active Title */
.menu-link.active .menu-title {
    color: #ffffff;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 8px rgba(246, 139, 30, 0.3);
}

/* Active Indicator - Left Border */
.menu-link.active::before {
    content: "";
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    height: 60%;
    width: 4px;
    background: linear-gradient(
        180deg,
        #fb923c 0%,
        #F68B1E 50%,
        #f97316 100%
    );
    border-radius: 0 6px 6px 0;
    box-shadow: 
        3px 0 12px rgba(246, 139, 30, 0.6),
        0 0 20px rgba(246, 139, 30, 0.3);
    animation: borderGlow 2s ease-in-out infinite;
}

/* Ripple effect on click */
.menu-link:active {
    transform: scale(0.98);
}

/* --- 4. Submenu Styling --- */
.menu-sub-accordion {
    position: relative;
    padding-left: 12px;
    margin-top: 4px;
}

/* Decorative line */
.menu-sub-accordion::before {
    content: "";
    position: absolute;
    left: 32px;
    top: 8px;
    bottom: 8px;
    width: 2px;
    background: linear-gradient(
        to bottom,
        rgba(246, 139, 30, 0.2),
        rgba(246, 139, 30, 0.05),
        transparent
    );
    border-radius: 2px;
}

.menu-sub .menu-item {
    padding-left: 0;
    animation: slideInLeft 0.4s ease-out backwards;
}

.menu-sub .menu-item:nth-child(1) { animation-delay: 0.1s; }
.menu-sub .menu-item:nth-child(2) { animation-delay: 0.15s; }
.menu-sub .menu-item:nth-child(3) { animation-delay: 0.2s; }
.menu-sub .menu-item:nth-child(4) { animation-delay: 0.25s; }

.menu-sub .menu-link {
    padding: 10px 16px 10px 3rem;
    font-size: 0.9rem;
    background: transparent;
    border: none;
    gap: 10px;
}

.menu-sub .menu-link:hover:not(.active) {
    transform: translateX(8px);
    background: rgba(246, 139, 30, 0.06);
}

.menu-sub .menu-link.active {
    background: transparent !important;
    box-shadow: none;
    border: none;
    transform: translateX(6px);
}

.menu-sub .menu-link.active .menu-title {
    color: #F68B1E !important;
    font-weight: 600;
    text-shadow: 0 0 10px rgba(246, 139, 30, 0.3);
}

.menu-sub .menu-link.active::before {
    display: none;
}

/* Bullet Dots with Animation */
.menu-bullet {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
}

.menu-bullet .bullet-dot {
    width: 6px;
    height: 6px;
    background-color: #6b7280;
    border-radius: 50%;
    transition: all 0.35s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    box-shadow: 0 0 0 rgba(246, 139, 30, 0);
}

.menu-sub .menu-link:hover .menu-bullet .bullet-dot {
    background: linear-gradient(135deg, #fb923c, #f97316);
    transform: scale(1.4);
    box-shadow: 0 0 8px rgba(246, 139, 30, 0.5);
}

.menu-sub .menu-link.active .menu-bullet .bullet-dot {
    background: linear-gradient(135deg, #F68B1E, #f97316);
    box-shadow: 
        0 0 12px rgba(246, 139, 30, 0.8),
        0 0 20px rgba(246, 139, 30, 0.4);
    transform: scale(1.6);
    animation: bulletPulse 2s ease-in-out infinite;
}

/* --- 5. Section Headings --- */
.menu-heading {
    color: #6b7280 !important;
    font-size: 0.75rem;
    letter-spacing: 1.5px;
    font-weight: 700 !important;
    opacity: 0.7;
    transition: all 0.3s ease;
    position: relative;
    padding-left: 12px;
}

.menu-heading::before {
    content: "";
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 12px;
    background: linear-gradient(180deg, #F68B1E, #f97316);
    border-radius: 2px;
    opacity: 0.5;
}

.menu-content:hover .menu-heading {
    opacity: 1;
    color: #9ca3af !important;
}

/* --- 6. Scrollbar Styling --- */
.hover-scroll-overlay-y {
    scrollbar-width: thin;
    scrollbar-color: rgba(246, 139, 30, 0.2) transparent;
}

.hover-scroll-overlay-y::-webkit-scrollbar {
    width: 6px;
}

.hover-scroll-overlay-y::-webkit-scrollbar-track {
    background: transparent;
}

.hover-scroll-overlay-y::-webkit-scrollbar-thumb {
    background: linear-gradient(
        180deg,
        rgba(246, 139, 30, 0.3),
        rgba(251, 146, 60, 0.2)
    );
    border-radius: 10px;
    transition: all 0.3s ease;
}

.hover-scroll-overlay-y:hover::-webkit-scrollbar-thumb {
    background: linear-gradient(
        180deg,
        rgba(246, 139, 30, 0.6),
        rgba(251, 146, 60, 0.4)
    );
    box-shadow: 0 0 10px rgba(246, 139, 30, 0.5);
}

/* --- 7. Accordion Arrow --- */
.menu-arrow {
    transition: all 0.35s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    margin-left: auto;
}

.menu-accordion.show > .menu-link .menu-arrow {
    transform: rotate(90deg);
}

.menu-accordion .menu-link:hover .menu-arrow {
    transform: translateX(4px);
}

.menu-accordion.show > .menu-link:hover .menu-arrow {
    transform: rotate(90deg) translateX(4px);
}

/* Arrow color */
.menu-arrow::after {
    transition: background-color 0.3s ease;
}

.menu-link:hover .menu-arrow::after,
.menu-link.active .menu-arrow::after {
    background-color: #F68B1E !important;
}

/* --- 8. Animations --- */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes iconPulse {
    0%, 100% {
        filter: drop-shadow(0 0 12px rgba(246, 139, 30, 0.6))
                drop-shadow(0 0 4px rgba(251, 146, 60, 0.8));
    }
    50% {
        filter: drop-shadow(0 0 18px rgba(246, 139, 30, 0.8))
                drop-shadow(0 0 8px rgba(251, 146, 60, 1));
    }
}

@keyframes iconBounce {
    0% { transform: scale(1); }
    30% { transform: scale(1.3) rotate(-10deg); }
    50% { transform: scale(0.9) rotate(5deg); }
    70% { transform: scale(1.1) rotate(-5deg); }
    100% { transform: scale(1) rotate(0deg); }
}

@keyframes borderGlow {
    0%, 100% {
        box-shadow: 
            3px 0 12px rgba(246, 139, 30, 0.6),
            0 0 20px rgba(246, 139, 30, 0.3);
    }
    50% {
        box-shadow: 
            3px 0 18px rgba(246, 139, 30, 0.8),
            0 0 30px rgba(246, 139, 30, 0.5);
    }
}

@keyframes bulletPulse {
    0%, 100% {
        transform: scale(1.6);
        box-shadow: 
            0 0 12px rgba(246, 139, 30, 0.8),
            0 0 20px rgba(246, 139, 30, 0.4);
    }
    50% {
        transform: scale(1.8);
        box-shadow: 
            0 0 16px rgba(246, 139, 30, 1),
            0 0 28px rgba(246, 139, 30, 0.6);
    }
}

/* --- 9. Mobile Responsive --- */
@media (max-width: 991px) {
    .menu-link {
        padding: 10px 12px;
    }
    
    .menu-sub .menu-link {
        padding-left: 2.5rem;
    }
    
    .menu-link:hover:not(.active) {
        transform: translateX(4px) scale(1.01);
    }
}

/* --- 10. Dark Theme Enhancements --- */
@media (prefers-color-scheme: dark) {
    .menu-link {
        color: #9ca3af;
    }
    
    .menu-link:hover:not(.active) {
        background: linear-gradient(
            135deg,
            rgba(246, 139, 30, 0.12) 0%,
            rgba(251, 146, 60, 0.06) 100%
        );
    }
    
    .menu-link.active {
        background: linear-gradient(
            135deg,
            rgba(246, 139, 30, 0.25) 0%,
            rgba(251, 146, 60, 0.15) 50%,
            rgba(249, 115, 22, 0.2) 100%
        );
    }
}

/* --- 11. Focus States (Accessibility) --- */
.menu-link:focus-visible {
    outline: 2px solid rgba(246, 139, 30, 0.5);
    outline-offset: 2px;
}

/* --- 12. Performance Optimization --- */
.menu-icon,
.menu-title,
.menu-bullet,
.menu-arrow {
    will-change: transform, color, filter;
}
</style>