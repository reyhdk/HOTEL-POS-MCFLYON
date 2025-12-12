<template>
    <div
        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg menu-state-primary fw-semibold py-3 fs-6 user-menu-dropdown"
        data-kt-menu="true"
        style="width: 350px !important; max-width: 350px !important;"    >
        <div v-if="user" class="menu-item px-4 mb-2">
            <div class="menu-content px-2">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px symbol-circle me-3 position-relative">
                        <img
                            alt="Profile"
                            :src="user.photo || getAssetPath('media/avatars/blank.png')"
                            class="profile-avatar"
                        />
                        <div class="symbol-badge bg-success start-100 top-100 border-3 h-12px w-12px ms-n2 mt-n2"></div>
                    </div>
                    
                    <div class="d-flex flex-column flex-grow-1">
                        <span class="fw-bold text-gray-800 fs-6">
                            {{ user.name }}
                        </span>
                        <span class="text-muted fs-7 text-truncate" style="max-width: 150px;">
                            {{ user.email }}
                        </span>
                        <span
                            v-if="user.role"
                            class="badge badge-light-success fs-8 fw-semibold align-self-start mt-1"
                        >
                            <i class="bi bi-shield-check fs-8 me-1"></i>
                            {{ user.role.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="separator my-2"></div>

        <div class="menu-item px-4">
            <router-link 
                :to="profileUrl" 
                class="menu-link px-3 py-2 menu-item-hover"
            >
                <span class="menu-icon me-2">
                    <i class="bi bi-person-circle text-primary fs-4"></i>
                </span>
                <span class="menu-title fw-semibold">
                    Pengaturan Akun
                </span>
            </router-link>
        </div>

        <div class="separator my-2"></div>

        <div class="menu-item px-4">
            <a 
                @click="signOut()" 
                class="menu-link px-3 py-2 menu-item-hover logout-item"
            >
                <span class="menu-icon me-2">
                    <i class="bi bi-box-arrow-right text-danger fs-4"></i>
                </span>
                <span class="menu-title fw-semibold text-danger">
                    Keluar
                </span>
            </a>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { useAuthStore } from "@/stores/auth";
import Swal from "sweetalert2";
import { computed } from "vue"; 

const store = useAuthStore();
// Gunakan computed untuk user agar reaktif jika foto berubah
const user = computed(() => store.user as any);
const profileUrl = computed(() => {
    // KITA UBAH DI SINI: Cast user ke 'any' agar bisa baca properti apa saja (termasuk roles)
    const userAny = store.user as any;
    
    // Ambil role dari object user (jika ada), atau dari getter userRole, atau default 'user'
    const role = userAny?.roles?.[0]?.name || store.userRole || 'user';
    
    if (role === 'user') {
        return { name: 'user-profile' };
    }
    return { name: 'admin-profile' };
});

const signOut = () => {
    Swal.fire({
        title: "Konfirmasi Keluar",
        text: "Apakah Anda yakin ingin keluar dari akun?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Ya, Keluar',
        cancelButtonText: '<i class="bi bi-x-circle me-1"></i> Batal',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn fw-semibold btn-danger px-5",
            cancelButton: "btn fw-semibold btn-light px-5",
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Berhasil!",
                text: "Anda telah keluar dari akun.",
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            });
            
            setTimeout(() => {
                store.logout();
            }, 1000);
        }
    });
};
</script>

<style scoped>
/* === User Menu Dropdown Styles === */
.user-menu-dropdown {
    width: 280px !important;
    max-width: 280px !important;
    min-width: 280px !important;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12) !important;
    border: 1px solid rgba(0, 0, 0, 0.05);
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* === Profile Avatar === */
.profile-avatar {
    object-fit: cover;
    width: 100%;
    height: 100%;
    border-radius: 50%; /* Memastikan gambar bulat */
}

/* === Menu Items Hover Effect === */
.menu-item-hover {
    transition: all 0.2s ease;
    border-radius: 6px;
    cursor: pointer;
}

.menu-item-hover:hover {
    background-color: #f8f9fa !important;
    transform: translateX(3px);
}

.logout-item:hover {
    background-color: #fff5f5 !important;
}
</style>