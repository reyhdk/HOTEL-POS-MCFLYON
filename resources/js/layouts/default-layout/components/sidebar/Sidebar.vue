<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import { displaySidebar } from "@/layouts/default-layout/config/helper";
import KTSidebarLogo from "@/layouts/default-layout/components/sidebar/SidebarLogo.vue";
import KTSidebarMenu from "@/layouts/default-layout/components/sidebar/SidebarMenu.vue";

const sidebarRef = ref<HTMLElement | null>(null);
let timer: ReturnType<typeof setTimeout> | null = null;

onMounted(() => {
    // Default: Minimize saat loading
    document.body.setAttribute("data-kt-app-sidebar-minimize", "on");
});

onUnmounted(() => {
    // Cleanup timer saat component di-unmount
    if (timer) {
        clearTimeout(timer);
    }
});

const handleMouseEnter = () => {
    // Batalkan timer penutupan jika ada
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
    
    // Buka sidebar dengan smooth transition
    document.body.setAttribute("data-kt-app-sidebar-minimize", "off");
};

const handleMouseLeave = () => {
    // Tunda penutupan 300ms untuk pengalaman yang lebih smooth
    timer = setTimeout(() => {
        document.body.setAttribute("data-kt-app-sidebar-minimize", "on");
    }, 300);
};
</script>

<template>
    <div
        v-if="displaySidebar"
        ref="sidebarRef"
        id="kt_app_sidebar"
        class="app-sidebar flex-column"
        @mouseenter="handleMouseEnter"
        @mouseleave="handleMouseLeave"
        data-kt-drawer="true"
        data-kt-drawer-name="app-sidebar"
        data-kt-drawer-activate="{default: true, lg: false}"
        data-kt-drawer-overlay="true"
        data-kt-drawer-width="250px"
        data-kt-drawer-direction="start"
        data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle"
    >
        <KTSidebarLogo :sidebar-ref="sidebarRef" />
        <KTSidebarMenu />
    </div>
</template>

<style scoped>
/* Enhanced Sidebar Styling - Tidak mengganggu layout */
.app-sidebar {
    background: linear-gradient(180deg, #1e1e2d 0%, #1a1a27 50%, #16161f 100%) !important;
    border-right: 1px solid rgba(246, 139, 30, 0.12);
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.15);
}

/* Hover Effect */
.app-sidebar:hover {
    box-shadow: 4px 0 30px rgba(0, 0, 0, 0.2);
    border-right-color: rgba(246, 139, 30, 0.2);
}
</style>