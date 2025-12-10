<template>
  <div class="d-flex flex-column gap-5">
    
    <div class="row g-5 g-xl-8">
      <div class="col-xl-4 col-12 animate-item" style="--delay: 0s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-orange theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-orange text-orange rounded-4">
                <i class="ki-duotone ki-profile-user fs-2x text-orange"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ guests.length }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Tamu Terdaftar</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm theme-card animate-item position-relative" style="--delay: 0.1s; z-index: 99;">
        <div class="card-body py-4">
            <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-4">
                
                <div class="d-flex align-items-center position-relative w-100 w-sm-300px">
                    <i class="ki-duotone ki-magnifier fs-2 position-absolute ms-4 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                    <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 search-input" placeholder="Cari nama, email, atau no.hp..." />
                </div>

                <button v-if="userHasPermission('create guests')" class="btn btn-sm btn-orange fw-bold hover-scale ms-lg-2 box-shadow-orange w-100 w-sm-auto" @click="openAddModal">
                    <i class="ki-duotone ki-plus fs-2 text-white"></i> Tambah Tamu
                </button>
            </div>
        </div>
    </div>

    <div class="position-relative min-h-300px" style="z-index: 1;">
        
        <div v-if="loading" class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center z-index-10 bg-body bg-opacity-75 rounded-3">
             <div class="d-flex flex-column align-items-center animate-pulse">
                 <span class="spinner-border text-orange mb-3 w-40px h-40px"></span>
                 <span class="text-gray-500 fw-bold">Memuat data...</span>
             </div>
        </div>

        <div v-else-if="filteredGuests.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted animate-fade-in">
             <div class="symbol symbol-100px mb-5 bg-light-orange rounded-circle d-flex align-items-center justify-content-center">
                <i class="ki-duotone ki-user-square fs-4x text-orange"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
             </div>
             <span class="fs-4 fw-bold text-gray-800">Tidak ada tamu ditemukan.</span>
        </div>

        <div v-else>
            <TransitionGroup name="fade-grid" tag="div" class="row g-6">
                <div class="col-md-6 col-lg-4 col-xl-3" v-for="guest in filteredGuests" :key="guest.id">
                    
                    <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up transition-300 group-card">
                        <div class="card-body p-5 d-flex flex-column">
                            
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-50px symbol-circle me-3">
                                    <div class="symbol-label fw-bold fs-3" :class="getRandomColorClass(guest.id)">
                                        {{ getInitials(guest.name) }}
                                    </div>
                                </div>
                                <div class="d-flex flex-column overflow-hidden">
                                    <a href="#" class="text-gray-900 text-hover-orange fw-bold fs-6 text-truncate transition-200" @click.prevent="openEditModal(guest)">
                                        {{ guest.name }}
                                    </a>
                                    <span class="text-gray-400 fw-semibold fs-8">Tamu Reguler</span>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-3 mb-5">
                                <div class="d-flex align-items-center text-gray-600 fs-7">
                                    <i class="ki-duotone ki-sms fs-5 me-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="text-truncate">{{ guest.email || '-' }}</span>
                                </div>
                                <div class="d-flex align-items-center text-gray-600 fs-7">
                                    <i class="ki-duotone ki-phone fs-5 me-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                                    <span>{{ guest.phone_number || '-' }}</span>
                                </div>
                                <div class="d-flex align-items-start text-gray-600 fs-7">
                                    <i class="ki-duotone ki-geolocation fs-5 me-2 text-gray-400 mt-1"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="line-clamp-2">{{ guest.address || '-' }}</span>
                                </div>
                            </div>

                            <div class="mt-auto d-flex gap-2 pt-4 border-top border-gray-200 border-dashed">
                                <button v-if="userHasPermission('edit guests')" @click="openEditModal(guest)" class="btn btn-sm btn-light btn-active-light-orange fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-pencil fs-5"><span class="path1"></span><span class="path2"></span></i> Edit
                                </button>
                                <button v-if="userHasPermission('delete guests')" @click="deleteGuest(guest.id)" class="btn btn-sm btn-light btn-active-light-danger fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </TransitionGroup>
        </div>
    </div>

    <GuestModal :guest-data="selectedGuest" @guest-updated="refreshData" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import GuestModal from "./GuestModal.vue";

interface Guest {
  id: number;
  name: string;
  email: string | null;
  phone_number: string | null;
  address: string | null;
}

const authStore = useAuthStore();
const guests = ref<Guest[]>([]);
const loading = ref(true);
const selectedGuest = ref<Guest | null>(null);
const searchQuery = ref("");

const userHasPermission = (permission: string): boolean => {
  return authStore.user?.all_permissions?.includes(permission) ?? false;
};

// Filtered Search
const filteredGuests = computed(() => {
    if (!searchQuery.value) return guests.value;
    const lowerQuery = searchQuery.value.toLowerCase();
    return guests.value.filter(g => 
        g.name.toLowerCase().includes(lowerQuery) || 
        (g.email && g.email.toLowerCase().includes(lowerQuery)) ||
        (g.phone_number && g.phone_number.includes(lowerQuery))
    );
});

// Helper: Initials Generator (e.g. "Budi Santoso" -> "BS")
const getInitials = (name: string) => {
    if (!name) return "?";
    const parts = name.split(" ");
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
};

// Helper: Random Color Class for Avatar
const getRandomColorClass = (id: number) => {
    const colors = ['bg-light-primary text-primary', 'bg-light-warning text-warning', 'bg-light-success text-success', 'bg-light-info text-info', 'bg-light-danger text-danger'];
    return colors[id % colors.length];
};

const getGuests = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/guests");
    guests.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil data tamu:", error);
  } finally {
    loading.value = false;
  }
};

const refreshData = () => { getGuests(); };

const openAddModal = () => {
  selectedGuest.value = null;
  const modalEl = document.getElementById("kt_modal_guest");
  if (modalEl) new Modal(modalEl).show();
};

const openEditModal = (guest: Guest) => {
  selectedGuest.value = { ...guest };
  const modalEl = document.getElementById("kt_modal_guest");
  if (modalEl) new Modal(modalEl).show();
};

const deleteGuest = (id: number) => {
  Swal.fire({
    text: "Hapus data tamu ini?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, Hapus",
    cancelButtonText: "Batal",
    customClass: {
      confirmButton: "btn fw-bold btn-danger",
      cancelButton: "btn fw-bold btn-light",
    },
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await axios.delete(`/guests/${id}`);
        Swal.fire({ text: "Data tamu terhapus!", icon: "success", timer: 1000, showConfirmButton: false });
        refreshData();
      } catch (error) {
        Swal.fire("Error!", "Gagal menghapus data.", "error");
      }
    }
  });
};

onMounted(() => { getGuests(); });
</script>

<style scoped>
/* ========================
   THEME COLORS
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF8F1 !important; }
.btn-orange { background-color: #F68B1E; color: white; border: none; }
.btn-orange:hover { background-color: #d97814; color: white; }
.hover-text-orange:hover { color: #F68B1E !important; }
.box-shadow-orange { box-shadow: 0 4px 12px rgba(246, 139, 30, 0.3); }

/* ========================
   CARD & ANIMATION
   ======================== */
.group-card { transition: all 0.3s ease; }
.hover-elevate-up:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important; 
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animations */
.animate-item { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; animation-delay: var(--delay, 0s); }
@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.animate-pulse { animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }

.fade-grid-enter-active, .fade-grid-leave-active { transition: all 0.4s ease; }
.fade-grid-enter-from, .fade-grid-leave-to { opacity: 0; transform: translateY(20px); }

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; color: #ffffff; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e1e3ea !important; }
[data-bs-theme="dark"] .text-gray-600 { color: #9A9CAE !important; }
[data-bs-theme="dark"] .text-gray-500, [data-bs-theme="dark"] .text-gray-400 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .border-gray-200 { border-color: #323248 !important; }
[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #ffffff; }
[data-bs-theme="dark"] .btn-active-light-orange:hover { background-color: #2b2b40 !important; color: #F68B1E !important; }
</style>