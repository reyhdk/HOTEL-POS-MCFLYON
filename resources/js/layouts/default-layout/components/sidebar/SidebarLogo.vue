<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { ToggleComponent } from "@/assets/ts/components";
import { getAssetPath } from "@/core/helpers/assets";
import {
    layout,
    sidebarToggleDisplay,
    themeMode,
} from "@/layouts/default-layout/config/helper";
import { useSetting } from "@/services";

interface IProps {
    sidebarRef: HTMLElement | null;
}

const props = defineProps<IProps>();

const { data: setting } = useSetting();

const toggleRef = ref<HTMLFormElement | null>(null);
const isHovered = ref(false);

// Computed property untuk menentukan logo yang akan ditampilkan
const logoSrc = computed(() => {
    return setting.value?.logo || getAssetPath("media/logos/default-logo.svg");
});

// Computed property untuk kondisi dark mode
const isDarkMode = computed(() => {
    return layout.value === 'dark-sidebar' || (themeMode.value === 'dark' && layout.value === 'light-sidebar');
});

onMounted(() => {
    setTimeout(() => {
        const toggleObj = ToggleComponent.getInstance(
            toggleRef.value!
        ) as ToggleComponent | null;

        if (toggleObj === null) {
            return;
        }

        // Add a class to prevent sidebar hover effect after toggle click
        toggleObj.on("kt.toggle.change", function () {
            // Set animation state
            props.sidebarRef?.classList.add("animating");

            // Wait till animation finishes
            setTimeout(function () {
                // Remove animation state
                props.sidebarRef?.classList.remove("animating");
            }, 300);
        });
    }, 1);
});
</script>

<template>
    <!--begin::Logo-->
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <!--begin::Logo image-->
        <router-link 
            to="/" 
            class="logo-link d-flex align-items-center position-relative"
            @mouseenter="isHovered = true"
            @mouseleave="isHovered = false"
        >
            <!-- Logo Default (Expanded) -->
            <div class="logo-wrapper">
                <img 
                    alt="Logo" 
                    :src="logoSrc" 
                    class="app-sidebar-logo-default logo-image"
                />
            </div>
            
            <!-- Logo Minimize (Collapsed) -->
            <div class="logo-wrapper-minimize">
                <img 
                    alt="Logo" 
                    :src="logoSrc" 
                    class="app-sidebar-logo-minimize logo-image-mini"
                />
            </div>

            <!-- Hover Glow Effect -->
            <div class="logo-glow" :class="{ active: isHovered }"></div>
        </router-link>
        <!--end::Logo image-->
        
        <!--begin::Sidebar toggle-->
        <div 
            v-if="sidebarToggleDisplay" 
            ref="toggleRef" 
            id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary position-absolute top-50 start-100 translate-middle rotate toggle-btn-enhanced"
            data-kt-toggle="true" 
            data-kt-toggle-state="active" 
            data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize"
        >
            <KTIcon icon-name="black-left-line" icon-class="fs-3 rotate-180 ms-1 toggle-icon" />
        </div>
        <!--end::Sidebar toggle-->
    </div>
    <!--end::Logo-->
</template>

<style scoped>
/* ==================================================
   LOGO SECTION - MODERN & SMOOTH ANIMATIONS
   ================================================== */

/* 1. Logo Container */
.app-sidebar-logo {
    position: relative;
    min-height: 90px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1.5rem !important;
    overflow: visible !important;
    background: linear-gradient(135deg, rgba(246, 139, 30, 0.03) 0%, rgba(246, 139, 30, 0) 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 2. Logo Link */
.logo-link {
    width: 100%;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0.5rem;
    border-radius: 12px;
}

.logo-link:hover {
    transform: translateY(-2px);
}

/* 3. Logo Wrapper - Default (Expanded) */
.logo-wrapper {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    transform: scale(1);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.logo-image {
    height: 50px;
    width: auto;
    max-width: 100%;
    object-fit: contain;
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 4. Logo Wrapper - Minimize (Collapsed) */
.logo-wrapper-minimize {
    position: absolute;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.7) rotate(-15deg);
    transition: all 0.45s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    pointer-events: none;
}

.logo-image-mini {
    height: 32px;
    width: 32px;
    object-fit: contain;
    filter: drop-shadow(0 2px 12px rgba(246, 139, 30, 0.3));
    border-radius: 8px;
}

/* 5. Minimized State */
[data-kt-app-sidebar-minimize="on"] .logo-wrapper {
    opacity: 0;
    transform: scale(0.7) rotate(15deg);
}

[data-kt-app-sidebar-minimize="on"] .logo-wrapper-minimize {
    opacity: 1;
    transform: scale(1) rotate(0deg);
    pointer-events: auto;
}

[data-kt-app-sidebar-minimize="on"] .app-sidebar-logo {
    padding: 1rem 0.5rem !important;
}

/* 6. Hover Glow Effect */
.logo-glow {
    position: absolute;
    inset: -10px;
    background: radial-gradient(circle at center, rgba(246, 139, 30, 0.15) 0%, transparent 70%);
    border-radius: 16px;
    opacity: 0;
    transform: scale(0.9);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
    z-index: -1;
}

.logo-glow.active {
    opacity: 1;
    transform: scale(1);
}

.logo-link:hover .logo-image {
    filter: drop-shadow(0 4px 16px rgba(246, 139, 30, 0.3));
    transform: scale(1.05);
}

/* 7. Toggle Button Enhanced */
.toggle-btn-enhanced {
    height: 32px !important;
    width: 32px !important;
    background: linear-gradient(135deg, #F68B1E 0%, #e67a10 100%) !important;
    border: 2px solid rgba(255, 255, 255, 0.1);
    box-shadow: 
        0 4px 12px rgba(246, 139, 30, 0.3),
        0 2px 6px rgba(0, 0, 0, 0.2) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.toggle-btn-enhanced:hover {
    background: linear-gradient(135deg, #ff9a2e 0%, #F68B1E 100%) !important;
    transform: scale(1.1) rotate(180deg);
    box-shadow: 
        0 6px 20px rgba(246, 139, 30, 0.5),
        0 3px 10px rgba(0, 0, 0, 0.3) !important;
}

.toggle-btn-enhanced:active {
    transform: scale(0.95) rotate(180deg);
}

.toggle-icon {
    color: #ffffff !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Rotate icon saat minimize */
[data-kt-app-sidebar-minimize="on"] .toggle-btn-enhanced .toggle-icon {
    transform: rotate(180deg);
}

/* 8. Pulse Animation untuk Toggle Button */
@keyframes pulse {
    0%, 100% {
        box-shadow: 
            0 4px 12px rgba(246, 139, 30, 0.3),
            0 2px 6px rgba(0, 0, 0, 0.2);
    }
    50% {
        box-shadow: 
            0 4px 20px rgba(246, 139, 30, 0.5),
            0 2px 10px rgba(0, 0, 0, 0.3);
    }
}

.toggle-btn-enhanced:hover {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* 9. Loading Animation */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.app-sidebar-logo {
    animation: fadeInDown 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

/* 10. Responsive */
@media (max-width: 991.98px) {
    .app-sidebar-logo {
        min-height: 70px;
        padding: 1rem 1rem !important;
    }
    
    .logo-image {
        height: 40px;
    }
    
    .logo-image-mini {
        height: 35px;
    }
}

/* 11. Animating State (Saat Toggle) */
.animating .logo-wrapper,
.animating .logo-wrapper-minimize {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

/* 12. Dark Mode Adjustments */
[data-bs-theme="dark"] .app-sidebar-logo {
    background: linear-gradient(135deg, rgba(246, 139, 30, 0.05) 0%, rgba(246, 139, 30, 0) 100%);
    border-bottom-color: rgba(255, 255, 255, 0.08);
}

[data-bs-theme="dark"] .logo-image,
[data-bs-theme="dark"] .logo-image-mini {
    filter: brightness(1.1) drop-shadow(0 2px 8px rgba(246, 139, 30, 0.15));
}

/* 13. Additional Polish */
.logo-link::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.logo-link:hover::before {
    opacity: 1;
}
</style>