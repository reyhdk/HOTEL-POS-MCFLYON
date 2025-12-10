<template>
  <div class="d-flex flex-column gap-5">
    
    <div class="row g-5 g-xl-8">
      <div class="col-xl-4 col-12 animate-item" style="--delay: 0s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-orange theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-orange text-orange rounded-4">
                <i class="ki-duotone ki-abstract-26 fs-2x text-orange"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ facilities.length }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Fasilitas</span>
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
                    <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 search-input" placeholder="Cari fasilitas..." />
                </div>

                <button v-if="userHasPermission('create facilities')" class="btn btn-sm btn-orange fw-bold hover-scale ms-lg-2 box-shadow-orange w-100 w-sm-auto" @click="openAddModal">
                    <i class="ki-duotone ki-plus fs-2 text-white"></i> Tambah Fasilitas
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

        <div v-else-if="filteredFacilities.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted animate-fade-in">
             <div class="symbol symbol-100px mb-5 bg-light-orange rounded-circle d-flex align-items-center justify-content-center">
                <i class="ki-duotone ki-file-sheet fs-4x text-orange"><span class="path1"></span><span class="path2"></span></i>
             </div>
             <span class="fs-4 fw-bold text-gray-800">Tidak ada fasilitas ditemukan.</span>
        </div>

        <div v-else>
            <TransitionGroup name="fade-grid" tag="div" class="row g-6">
                <div class="col-md-6 col-lg-4 col-xl-3" v-for="facility in filteredFacilities" :key="facility.id">
                    
                    <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up transition-300 group-card">
                        <div class="card-body p-5 d-flex flex-column align-items-center text-center">
                            
                            <div class="symbol symbol-80px symbol-circle mb-5 bg-light-subtle p-3 border border-gray-200 border-dashed">
                                <img v-if="facility.icon_url" :src="facility.icon_url" class="object-fit-cover rounded-circle" :alt="facility.name" />
                                <div v-else class="symbol-label bg-transparent">
                                    <i class="ki-duotone ki-abstract-26 fs-3x text-orange"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>

                            <div class="mb-5">
                                <a href="#" class="fs-4 fw-bolder text-gray-800 text-hover-orange mb-1 d-block transition-200" @click.prevent="openEditModal(facility)">
                                    {{ facility.name }}
                                </a>
                                <div class="text-gray-400 fs-7 fw-semibold line-clamp-2">
                                    {{ facility.description || 'Tidak ada deskripsi tambahan.' }}
                                </div>
                            </div>

                            <div class="mt-auto d-flex justify-content-center gap-2 w-100">
                                <button v-if="userHasPermission('edit facilities')" @click="openEditModal(facility)" class="btn btn-sm btn-light btn-active-light-orange fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-pencil fs-5"><span class="path1"></span><span class="path2"></span></i> Edit
                                </button>
                                <button v-if="userHasPermission('delete facilities')" @click="deleteFacility(facility.id)" class="btn btn-sm btn-light btn-active-light-danger fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i> Hapus
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </TransitionGroup>
        </div>
    </div>

    <FacilityModal :facility-data="selectedFacility" @facility-updated="refreshData" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import axios from "@/libs/axios";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import FacilityModal from "./FacilityModal.vue";

interface Facility {
  id: number;
  name: string;
  icon: string | null;
  description: string | null;
  icon_url: string | null;
}

const authStore = useAuthStore();
const facilities = ref<Facility[]>([]);
const loading = ref(true);
const selectedFacility = ref<Facility | null>(null);
const searchQuery = ref("");

const userHasPermission = (permission: string): boolean => {
  return authStore.user?.all_permissions?.includes(permission) ?? false;
};

// Filtered Computed Property
const filteredFacilities = computed(() => {
    if (!searchQuery.value) return facilities.value;
    const lowerQuery = searchQuery.value.toLowerCase();
    return facilities.value.filter(f => 
        f.name.toLowerCase().includes(lowerQuery) || 
        (f.description && f.description.toLowerCase().includes(lowerQuery))
    );
});

const getFacilities = async () => {
  try {
    loading.value = true;
    const response = await axios.get("/facilities");
    if (Array.isArray(response.data)) {
      facilities.value = response.data;
    } else {
      facilities.value = [];
    }
  } catch (error) {
    console.error("Gagal mengambil data fasilitas:", error);
    facilities.value = [];
  } finally {
    loading.value = false;
  }
};

const refreshData = () => { getFacilities(); };

const openAddModal = () => {
  selectedFacility.value = null;
  const modalEl = document.getElementById("kt_modal_facility");
  if (modalEl) new Modal(modalEl).show();
};

const openEditModal = (facility: Facility) => {
  selectedFacility.value = { ...facility };
  const modalEl = document.getElementById("kt_modal_facility");
  if (modalEl) new Modal(modalEl).show();
};

const deleteFacility = (id: number) => {
  Swal.fire({
    text: "Hapus fasilitas ini?",
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
        await axios.delete(`/facilities/${id}`);
        Swal.fire({ text: "Terhapus!", icon: "success", timer: 1000, showConfirmButton: false });
        refreshData();
      } catch (error) {
        Swal.fire("Error!", "Gagal menghapus data.", "error");
      }
    }
  });
};

onMounted(() => { getFacilities(); });
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
[data-bs-theme="dark"] .text-gray-500, [data-bs-theme="dark"] .text-gray-400 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .border-gray-200 { border-color: #323248 !important; }
[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #ffffff; }
[data-bs-theme="dark"] .btn-active-light-orange:hover { background-color: #2b2b40 !important; color: #F68B1E !important; }
</style>