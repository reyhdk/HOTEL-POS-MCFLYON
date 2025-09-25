<template>
  <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
    <div class="px-3 py-4 bg-success text-white fw-bold">
      <p class="m-0">--- DEBUG FINAL V2 ---</p>
      <p class="m-0">
        Role Terdeteksi: <strong class="fs-4">{{ userRole }}</strong>
      </p>
      <p class="m-0">Jumlah Menu Tampil: {{ filteredMenu.length }}</p>
    </div>

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
          <div v-if="item.heading" class="menu-item pt-5">
            <div class="menu-content">
              <span class="menu-heading fw-bold text-uppercase fs-7">
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
                </span>
                <span class="menu-title">{{
                  translate(menuItem.heading)
                }}</span>
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
                </span>
                <span class="menu-title">{{
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

// =======================================================
// ▼▼▼ LOGIKA FILTER MENU BARU BERBASIS PERMISSION ▼▼▼
// =======================================================
const userRole = computed(() => authStore.userRole);

const filteredMenu = computed(() => {
  const result: MenuItem[] = [];

  for (const section of MainMenuConfig) {
    const newSection: MenuItem = { ...section, pages: [] };

    if (section.pages) {
      for (const page of section.pages) {
        // Logika Pengecekan Baru:
        // 1. Jika menu untuk user biasa, cek rolenya.
        // 2. Jika menu untuk admin, cek PERMISSION-nya.
        
        const isUserMenu = page.roles?.includes('user');
        const hasAccess = isUserMenu 
          ? page.roles?.includes(userRole.value) 
          : page.name ? authStore.hasPermission(page.name) : true;

        if (hasAccess) {
          const newPage = { ...page };

          // Jika ada sub-menu, filter sub-menunya juga dengan logika yang sama
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
// =======================================================


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