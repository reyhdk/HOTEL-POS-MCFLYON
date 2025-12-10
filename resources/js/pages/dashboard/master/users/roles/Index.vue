<template>
  <div class="d-flex flex-column gap-5">
    
    <div class="row g-5 g-xl-8">
      <div class="col-xl-4 col-md-6 animate-item" style="--delay: 0s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-orange theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-orange text-orange rounded-4">
                <i class="ki-duotone ki-shield-tick fs-2x text-orange"><span class="path1"></span><span class="path2"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ roles.length }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Role Akses</span>
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
                    <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 search-input" placeholder="Cari role..." />
                </div>

                <button v-if="userHasPermission('create roles')" class="btn btn-sm btn-orange fw-bold hover-scale box-shadow-orange w-100 w-sm-auto" @click="openAddModal">
                    <i class="ki-duotone ki-plus fs-2 text-white"></i> Tambah Role
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

        <div v-else-if="filteredRoles.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted animate-fade-in">
             <div class="symbol symbol-100px mb-5 bg-light-orange rounded-circle d-flex align-items-center justify-content-center">
                <i class="ki-duotone ki-shield-cross fs-4x text-orange"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
             </div>
             <span class="fs-4 fw-bold text-gray-800">Tidak ada role ditemukan.</span>
        </div>

        <div v-else>
            <TransitionGroup name="fade-grid" tag="div" class="row g-6">
                <div class="col-md-6 col-lg-4 col-xl-3" v-for="role in filteredRoles" :key="role.id">
                    
                    <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up transition-300 group-card">
                        <div class="card-body p-5 d-flex flex-column">
                            
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-50px symbol-circle me-3">
                                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                                        {{ role.full_name.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="d-flex flex-column overflow-hidden">
                                    <a href="#" class="text-gray-900 text-hover-orange fw-bold fs-5 text-truncate transition-200" @click.prevent="openEditModal(role)">
                                        {{ role.full_name }}
                                    </a>
                                    <span class="text-gray-400 fw-semibold fs-8 text-truncate">Slug: {{ role.name }}</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center bg-light-subtle rounded p-3 mb-5 border border-dashed border-gray-300">
                                <i class="ki-duotone ki-key fs-2 text-gray-500 me-3"><span class="path1"></span><span class="path2"></span></i>
                                <div class="d-flex flex-column">
                                    <span class="fs-8 fw-bold text-gray-700">Akses Izin</span>
                                    <span class="fs-9 text-muted">{{ role.permissions ? role.permissions.length : (role.permissions_count || 0) }} Hak Akses</span>
                                </div>
                            </div>

                            <div class="mt-auto d-flex gap-2 pt-4 border-top border-gray-200 border-dashed">
                                <button v-if="userHasPermission('edit roles')" @click="openEditModal(role)" class="btn btn-sm btn-light btn-active-light-orange fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-pencil fs-5"><span class="path1"></span><span class="path2"></span></i> Edit
                                </button>
                                <button v-if="userHasPermission('delete roles')" @click="deleteRole(role.id)" class="btn btn-sm btn-light btn-active-light-danger fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </TransitionGroup>
        </div>
    </div>

    <FormModal :role-data="selectedRole" @role-updated="refreshData" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import FormModal from "./Form.vue";

interface Permission { id: number; name: string; }
interface Role { 
    id: number; 
    name: string; 
    full_name: string; 
    permissions?: Permission[];
    permissions_count?: number; 
}

const authStore = useAuthStore();
const roles = ref<Role[]>([]);
const loading = ref(true);
const selectedRole = ref<Role | null>(null);
const searchQuery = ref("");

const userHasPermission = (permission: string) => authStore.user?.all_permissions?.includes(permission) ?? false;

const filteredRoles = computed(() => {
    if (!searchQuery.value) return roles.value;
    const q = searchQuery.value.toLowerCase();
    return roles.value.filter(r => r.full_name.toLowerCase().includes(q) || r.name.toLowerCase().includes(q));
});

const getRoles = async () => {
    try {
        loading.value = true;
        const response = await ApiService.get("/master/roles");
        // Handle struktur response umum (array atau pagination)
        roles.value = response.data.data || response.data; 
    } catch (error) {
        console.error("Gagal memuat role:", error);
    } finally {
        loading.value = false;
    }
};

const refreshData = () => { getRoles(); };

const openAddModal = () => {
    selectedRole.value = null;
    const modalEl = document.getElementById("kt_modal_role");
    if (modalEl) new Modal(modalEl).show();
};

const openEditModal = (role: Role) => {
    selectedRole.value = { ...role };
    const modalEl = document.getElementById("kt_modal_role");
    if (modalEl) new Modal(modalEl).show();
};

const deleteRole = (id: number) => {
    Swal.fire({
        text: "Hapus role ini? User dengan role ini akan kehilangan akses.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn fw-bold btn-danger", cancelButton: "btn fw-bold btn-light" },
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await ApiService.delete(`/master/roles/${id}`);
                Swal.fire("Berhasil", "Role dihapus.", "success");
                refreshData();
            } catch (error: any) {
                Swal.fire("Error", error.response?.data?.message || "Gagal menghapus role.", "error");
            }
        }
    });
};

onMounted(getRoles);
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
.group-card:hover { z-index: 10; }
.hover-elevate-up:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.12) !important; }

/* Animations */
.animate-item { opacity: 0; animation: fadeUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; animation-delay: var(--delay, 0s); }
@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.fade-grid-enter-active, .fade-grid-leave-active { transition: all 0.4s ease; }
.fade-grid-enter-from, .fade-grid-leave-to { opacity: 0; transform: translateY(20px); }
.animate-pulse { animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; color: #ffffff; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-700, [data-bs-theme="dark"] .text-gray-800 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .text-gray-500, [data-bs-theme="dark"] .text-gray-400 { color: #9A9CAE !important; }
[data-bs-theme="dark"] .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .bg-light-primary { background-color: rgba(0, 158, 247, 0.15) !important; color: #009ef7 !important; }
[data-bs-theme="dark"] .border-gray-200, [data-bs-theme="dark"] .border-gray-300 { border-color: #323248 !important; }
[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #ffffff; }
</style>