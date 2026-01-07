<template>
    <div class="anime-dashboard-container">
        
        <div v-if="checkInStore.isLoading">
            <div class="card border-0 shadow-sm anime-card mb-6 overflow-hidden">
                <div class="card-body p-lg-10">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="w-100">
                            <div class="skeleton-box w-150px h-20px mb-3 rounded-pill"></div>
                            <div class="skeleton-box w-250px h-40px mb-4 rounded-pill"></div>
                            <div class="skeleton-box w-120px h-30px rounded-pill"></div>
                        </div>
                        <div class="skeleton-box w-100px h-100px rounded-circle flex-shrink-0 ms-3"></div>
                    </div>
                </div>
            </div>

            <div class="row g-5 mb-8">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 anime-card">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-10">
                            <div class="skeleton-box w-60px h-60px rounded-3 mb-4"></div>
                            <div class="skeleton-box w-100px h-15px mb-3 rounded-pill"></div>
                            <div class="skeleton-box w-80px h-40px rounded-pill"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm h-100 anime-card">
                        <div class="card-body d-flex flex-column justify-content-center py-10 px-8">
                            <div class="skeleton-box w-150px h-20px mb-8 rounded-pill"></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-center w-100">
                                    <div class="skeleton-box w-80px h-15px mb-2 mx-auto rounded-pill"></div>
                                    <div class="skeleton-box w-120px h-30px mx-auto rounded-pill"></div>
                                </div>
                                <div class="mx-4">
                                    <div class="skeleton-box w-40px h-40px rounded-circle"></div>
                                </div>
                                <div class="text-center w-100">
                                    <div class="skeleton-box w-80px h-15px mb-2 mx-auto rounded-pill"></div>
                                    <div class="skeleton-box w-120px h-30px mx-auto rounded-pill"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-4 d-md-flex">
                <div class="skeleton-box flex-fill h-120px rounded-3"></div>
                <div class="skeleton-box flex-fill h-120px rounded-3"></div>
            </div>
        </div>

        <div v-else-if="checkInStore.isCheckedIn">
            
            <div class="card border-0 shadow-lg anime-card overflow-hidden mb-6">
                <div class="anime-bg-blob"></div>

                <div class="card-body p-lg-10 position-relative z-index-1">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="pe-3">
                            <p class="text-muted fs-6 mb-1 fw-semibold anime-text-secondary">Selamat Datang,</p>
                            <h2 class="display-6 fw-bolder mb-3 anime-text-primary">
                                {{ checkInStore.guestName || authStore.user?.name || "Tamu" }}
                            </h2>
                            
                            <div class="status-badge-glass d-inline-flex align-items-center">
                                <span class="pulse-dot me-2"></span>
                                <span class="fw-bold fs-7 text-success">STATUS: CHECK-IN</span>
                            </div>
                        </div>
                        
                        <div class="logo-container-glass floating-animation">
                            <img :src="logoSrc" alt="Logo Aplikasi" class="anime-mascot-img" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-5 mb-8">
                <div class="col-md-4">
                    <div class="anime-info-card h-100 d-flex flex-column justify-content-center align-items-center text-center">
                        <div class="icon-wrapper bg-orange-light mb-3">
                            <i class="ki-duotone ki-home fs-1 text-anime-orange">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </div>
                        <div class="fs-6 text-muted fw-bold anime-text-secondary">Nomor Kamar</div>
                        <div class="fs-1 fw-bolder anime-text-highlight">{{ checkInStore.roomNumber }}</div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="anime-info-card h-100 d-flex flex-column justify-content-center">
                        <h5 class="mb-4 text-muted fs-7 text-uppercase fw-bold ls-1 anime-text-secondary">Detail Menginap</h5>
                        
                        <div class="d-flex justify-content-between align-items-center px-2 mobile-flex-column">
                            <div class="text-start mobile-text-center">
                                <span class="fs-7 text-muted fw-bold d-block anime-text-secondary mb-1">Check In</span>
                                <span class="fs-3 fw-bolder anime-text-primary">
                                    {{ formatDate(checkInStore.checkInDate) }}
                                </span>
                            </div>

                            <div class="mx-3 text-center my-3">
                                <div class="rounded-pill bg-light-orange p-2 d-inline-block">
                                    <i class="ki-duotone ki-arrow-right fs-2 text-anime-orange rotate-mobile">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                </div>
                            </div>

                            <div class="text-end mobile-text-center">
                                <span class="fs-7 text-muted fw-bold d-block anime-text-secondary mb-1">Check Out</span>
                                <span class="fs-3 fw-bolder anime-text-primary">
                                    {{ formatDate(checkInStore.checkOutDate) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-4 d-md-flex">
                <router-link to="/user/food-order" class="btn btn-anime-primary flex-fill p-4 position-relative overflow-hidden">
                    <div class="d-flex align-items-center justify-content-center position-relative z-index-1">
                        <div class="icon-box-glass me-4">
                            <i class="ki-duotone ki-coffee fs-2x text-white">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>
                            </i>
                        </div>
                        <div class="text-start">
                            <div class="fs-5 fw-bold text-white text-shadow-sm">Pesan Makanan</div>
                            <div class="fs-7 opacity-90 fw-normal text-white">Restoran & Cafe</div>
                        </div>
                    </div>
                    <i class="ki-duotone ki-coffee position-absolute opacity-10" style="font-size: 6rem; right: -10px; bottom: -10px; color: white;">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </router-link>
                
                <router-link to="/user/room-service" class="btn btn-anime-purple flex-fill p-4 position-relative overflow-hidden">
                    <div class="d-flex align-items-center justify-content-center position-relative z-index-1">
                        <div class="icon-box-glass me-4">
                            <i class="ki-duotone ki-trash fs-2x text-white">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                            </i>
                        </div>
                        <div class="text-start">
                            <div class="fs-5 fw-bold text-white text-shadow-sm">Room Service</div>
                            <div class="fs-7 text-white opacity-90 fw-normal">Bersihkan Kamar</div>
                        </div>
                    </div>
                    <i class="ki-duotone ki-trash position-absolute opacity-10" style="font-size: 6rem; right: -10px; bottom: -10px; color: white;">
                         <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                    </i>
                </router-link>
            </div>
        </div>

        <div v-else>
            <div class="card anime-card overflow-hidden position-relative border-0" style="min-height: 450px;">
                <div class="locked-bg-pattern"></div>
                <div class="locked-overlay"></div>
                
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center position-relative z-index-1 p-10">
                    
                    <div class="locked-icon-wrapper mb-6">
                        <div class="icon-circle-glass">
                            <i class="ki-duotone ki-lock-2 fs-3x text-white">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                            </i>
                        </div>
                        <div class="icon-pulse-ring"></div>
                    </div>

                    <h2 class="fw-bolder display-6 mb-3 text-white text-shadow-md">
                        Dashboard Terkunci
                    </h2>
                    
                    <p class="text-white opacity-75 fs-6 mb-8 mx-auto fw-semibold" style="max-width: 500px; line-height: 1.6;">
                        Halo! Saat ini kamu belum memiliki sesi menginap yang aktif. 
                        Silakan lakukan <span class="text-warning fw-bold">Check-In</span> di resepsionis untuk membuka akses layanan kamar.
                    </p>

                    <div class="d-none d-md-flex align-items-center justify-content-center gap-4 mb-8 opacity-75">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ki-duotone ki-user-square fs-2 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-white fs-8 fw-bold text-uppercase ls-1">Resepsionis</span>
                        </div>
                        <i class="ki-duotone ki-arrow-right fs-4 text-white opacity-50"><span class="path1"></span><span class="path2"></span></i>
                        <div class="d-flex align-items-center gap-2">
                            <i class="ki-duotone ki-key fs-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                            <span class="text-white fs-8 fw-bold text-uppercase ls-1">Check In</span>
                        </div>
                        <i class="ki-duotone ki-arrow-right fs-4 text-white opacity-50"><span class="path1"></span><span class="path2"></span></i>
                        <div class="d-flex align-items-center gap-2">
                            <i class="ki-duotone ki-tablet-ok fs-2 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-white fs-8 fw-bold text-uppercase ls-1">Akses Terbuka</span>
                        </div>
                    </div>

                    <button @click="checkInStore.fetchStatus()" class="btn btn-refresh-glass px-8 py-3 d-flex align-items-center gap-3">
                        <i class="ki-duotone ki-arrows-circle fs-2 text-dark">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <span class="fw-bold fs-5 text-dark">Refresh Status</span>
                    </button>
                    
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useCheckInStore } from "@/stores/checkin";
import { useSetting } from "@/services"; 
import { getAssetPath } from "@/core/helpers/assets"; 
import moment from "moment";
import "moment/locale/id";

const authStore = useAuthStore();
const checkInStore = useCheckInStore();
const { data: setting } = useSetting();

let refreshInterval: any = null;

const logoSrc = computed(() => {
    return setting.value?.logo || getAssetPath("media/logos/default-logo.svg");
});

const formatDate = (dateString: string | null) => {
    if (!dateString) return "-";
    return moment(dateString).locale("id").format("DD MMM YYYY");
};

const startAutoRefresh = () => {
    stopAutoRefresh();
    refreshInterval = setInterval(() => {
        if (!document.hidden) checkInStore.fetchStatus();
    }, 30000);
};

const stopAutoRefresh = () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
};

onMounted(() => {
    checkInStore.fetchStatus();
    startAutoRefresh();
    watch(() => authStore.user, (u) => u && checkInStore.fetchStatus());
});

onBeforeUnmount(() => stopAutoRefresh());
</script>

<style scoped>
/* --- VARIABLES: ORANGE THEME --- */
:root {
    --anime-orange: #FF9F43; /* Warna Utama Oranye */
    --anime-bg-card: #ffffff;
    --anime-text-main: #2b2b2b;
    --anime-text-sec: #7e8299;
    --anime-border: #f1f1f4;
    --anime-bg-subtle: #FFF2D6; /* Background Oranye Pudar */
    --anime-shadow: rgba(255, 159, 67, 0.15); /* Bayangan Oranye */
}

/* DARK MODE ADJUSTMENT */
[data-bs-theme="dark"] :root, 
.dark-mode :root,
body.dark-mode {
    --anime-bg-card: #1e1e2d;
    --anime-text-main: #ffffff;
    --anime-text-sec: #9D9DA6;
    --anime-border: #2B2B40;
    --anime-bg-subtle: #2b2b40;
    --anime-shadow: rgba(0, 0, 0, 0.3);
    --anime-orange: #ffb066; /* Oranye lebih terang untuk dark mode */
}

/* --- UTILS --- */
.text-anime-orange { color: var(--anime-orange) !important; }
.anime-text-primary { color: var(--anime-text-main) !important; }
.anime-text-secondary { color: var(--anime-text-sec) !important; }
.anime-text-highlight { color: var(--anime-text-main); }
.bg-orange-light { background-color: rgba(255, 159, 67, 0.15) !important; }
.bg-light-orange { background-color: rgba(255, 159, 67, 0.1) !important; }

/* --- CARD STYLES --- */
.anime-card {
    background: var(--anime-bg-card);
    border-radius: 20px;
    box-shadow: 0 10px 30px var(--anime-shadow) !important;
    transition: background 0.3s ease, color 0.3s ease;
}

.anime-info-card {
    background: var(--anime-bg-card);
    border: 1px solid var(--anime-border);
    border-radius: 16px;
    padding: 1.5rem;
    transition: transform 0.2s ease;
    position: relative;
    overflow: hidden;
}
.anime-info-card:hover {
    transform: translateY(-5px);
    border-color: var(--anime-orange); /* Border berubah oranye saat hover */
}

/* Lingkaran blur di background */
.anime-bg-blob {
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, var(--anime-bg-subtle) 0%, rgba(0,0,0,0) 70%);
    opacity: 0.6;
    border-radius: 50%;
    filter: blur(40px);
}

/* --- LOGO GLASS --- */
.logo-container-glass {
    width: 100px; height: 100px; border-radius: 50%;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    display: flex; align-items: center; justify-content: center;
    padding: 15px; border: 1px solid rgba(255, 255, 255, 0.5);
}
[data-bs-theme="dark"] .logo-container-glass,
.dark-mode .logo-container-glass {
    background: rgba(40, 40, 50, 0.6); border: 1px solid rgba(255, 255, 255, 0.1);
}
.anime-mascot-img { width: 100%; height: 100%; object-fit: contain; }

/* --- STATUS BADGE --- */
.status-badge-glass {
    background: rgba(25, 135, 84, 0.1); border: 1px solid rgba(25, 135, 84, 0.2);
    border-radius: 30px; padding: 8px 16px;
    box-shadow: 0 0 15px rgba(25, 135, 84, 0.1);
}
.pulse-dot {
    width: 10px; height: 10px; background-color: #28a745;
    border-radius: 50%; position: relative;
}
.pulse-dot::before {
    content: ''; position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%); width: 100%; height: 100%;
    border-radius: 50%; background-color: #28a745;
    animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}
@keyframes pulse-ring {
    0% { width: 10px; height: 10px; opacity: 1; }
    100% { width: 25px; height: 25px; opacity: 0; }
}

/* --- BUTTONS --- */
/* Tombol Utama Oranye */
.btn-anime-primary {
    background: linear-gradient(135deg, #FF9F43 0%, #FF6B6B 100%); /* Gradien Oranye ke Merah Muda */
    border: none; border-radius: 16px; color: white;
    transition: all 0.3s; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}
.btn-anime-primary:hover {
    transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4); color: white;
}

/* Tombol Sekunder Ungu (Untuk kontras) */
.btn-anime-purple {
    background: linear-gradient(135deg, #7F5AD5 0%, #9B72F0 100%);
    border: none; border-radius: 16px; color: white;
    transition: all 0.3s; box-shadow: 0 4px 15px rgba(127, 90, 213, 0.3);
}
.btn-anime-purple:hover {
    transform: translateY(-2px); box-shadow: 0 8px 25px rgba(127, 90, 213, 0.4); color: white;
}

/* --- ICON GLASS BUTTONS --- */
.icon-box-glass {
    width: 60px; height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}
.btn-anime-primary:hover .icon-box-glass,
.btn-anime-purple:hover .icon-box-glass {
    transform: scale(1.1) rotate(5deg); background: rgba(255, 255, 255, 0.3);
}
.text-shadow-sm { text-shadow: 0 1px 3px rgba(0,0,0,0.1); }

/* --- SKELETON LOADER (SHIMMER EFFECT) --- */
.skeleton-box {
    background: #f0f2f5;
    background: linear-gradient(90deg, #eff2f5 25%, #ffffff 50%, #eff2f5 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite linear;
}
[data-bs-theme="dark"] .skeleton-box,
.dark-mode .skeleton-box {
    background: #2B2B40;
    background: linear-gradient(90deg, #2B2B40 25%, #3a3a50 50%, #2B2B40 75%);
    background-size: 200% 100%;
}
@keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

/* --- LOCKED STATE STYLES --- */
.locked-bg-pattern {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%); /* Tetap Cool/Dark agar kontras dengan active state */
    z-index: 0;
}
.locked-overlay {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background-image: radial-gradient(rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 20px 20px; z-index: 0; opacity: 0.3;
}
.locked-icon-wrapper {
    position: relative; width: 100px; height: 100px;
    display: flex; justify-content: center; align-items: center;
}
.icon-circle-glass {
    width: 80px; height: 80px;
    background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 50%;
    display: flex; justify-content: center; align-items: center; z-index: 2;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    animation: floatIcon 4s ease-in-out infinite;
}
.icon-pulse-ring {
    position: absolute; width: 100%; height: 100%; border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3); animation: ripple 2s infinite; z-index: 1;
}
.btn-refresh-glass {
    background: rgba(255, 255, 255, 0.9); color: #2c3e50;
    border: none; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
}
.btn-refresh-glass:hover {
    background: #ffffff; transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3); color: var(--anime-orange);
}
.btn-refresh-glass:active { transform: scale(0.95); }
.text-shadow-md { text-shadow: 0 2px 10px rgba(0,0,0,0.2); }

@keyframes floatIcon { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
@keyframes ripple { 0% { transform: scale(0.8); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }

/* --- ANIMATIONS --- */
.floating-animation { animation: floating 3s ease-in-out infinite; }
@keyframes floating { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
.ls-1 { letter-spacing: 1px; }

@media (max-width: 576px) {
    .mobile-flex-column { flex-direction: column; text-align: center; }
    .mobile-text-center { text-align: center !important; width: 100%; margin-bottom: 10px; }
    .rotate-mobile { transform: rotate(90deg); }
}
</style>