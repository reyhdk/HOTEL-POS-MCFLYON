<template>
  <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <div
      id="kt_app_sidebar_menu_wrapper"
      class="app-sidebar-wrapper hover-scroll-overlay-y my-5"
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
          
          <!-- Section Heading -->
          <div v-if="item.heading" class="menu-item pt-5">
            <div class="menu-content">
              <span class="menu-heading fw-bold text-uppercase fs-7">
                {{ translate(item.heading) }}
              </span>
            </div>
          </div>

          <!-- Menu Items -->
          <template v-for="(menuItem, j) in item.pages" :key="j">
            
            <!-- Simple Link Menu -->
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
                </span>
                <span class="menu-title fw-semibold">{{
                  translate(menuItem.heading)
                }}</span>
              </router-link>
            </div>

            <!-- Accordion Menu dengan Sub Items -->
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
                </span>
                <span class="menu-title fw-semibold">{{
                  translate(menuItem.sectionTitle)
                }}</span>
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
                      <span class="menu-title">{{
                        translate(item2.heading)
                      }}</span>
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
   SIDEBAR MENU - SMOOTH & MINIMAL
   ======================================== */

/* Menu Link Base */
.menu-item .menu-link {
    transition: all 0.3s ease;
    border-radius: 10px;
    margin-bottom: 4px;
}

/* Hover Effect */
.menu-item .menu-link:hover:not(.active) {
    background: rgba(255, 255, 255, 0.05);
    transform: translateX(4px);
}

.menu-item .menu-link:hover:not(.active) .menu-icon i,
.menu-item .menu-link:hover:not(.active) .menu-icon .svg-icon {
    color: #ffffff !important;
}

/* Active State */
.menu-link.active {
    background: linear-gradient(90deg, rgba(246, 139, 30, 0.15) 0%, rgba(246, 139, 30, 0.05) 100%) !important;
    border-left: 3px solid #F68B1E;
    padding-left: 1.5rem !important;
    border-radius: 0 10px 10px 0;
}

.menu-link.active .menu-title {
    color: #F68B1E !important;
    font-weight: 700;
}

.menu-link.active .menu-icon i,
.menu-link.active .menu-icon .svg-icon {
    color: #F68B1E !important;
}

/* Submenu Styling */
.menu-sub {
    margin-left: 1rem;
    padding-left: 1rem;
    border-left: 1px solid rgba(255, 255, 255, 0.08);
}

.menu-sub .menu-link.active {
    background: transparent !important;
    border-left: none !important;
    padding-left: 1rem !important;
}

/* Active Bullet */
.menu-sub .menu-link .menu-bullet .bullet-dot {
    width: 6px;
    height: 6px;
    background-color: rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.menu-sub .menu-link.active .menu-bullet .bullet-dot {
    background-color: #F68B1E !important;
    transform: scale(1.5);
    box-shadow: 0 0 10px rgba(246, 139, 30, 0.6);
}

.menu-sub .menu-link.active .menu-title {
    color: #ffffff !important;
    font-weight: 600;
}

/* Section Heading */
.menu-heading {
    color: #6c7293 !important;
    letter-spacing: 1.5px;
    opacity: 0.7;
}

/* Accordion Arrow */
.menu-arrow {
    transition: transform 0.3s ease;
}

.menu-accordion.show .menu-arrow {
    transform: rotate(90deg);
}

/* Custom Scrollbar */
.hover-scroll-overlay-y::-webkit-scrollbar {
    width: 6px;
}

.hover-scroll-overlay-y::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.hover-scroll-overlay-y::-webkit-scrollbar-thumb:hover {
    background: rgba(246, 139, 30, 0.3);
}
</style>