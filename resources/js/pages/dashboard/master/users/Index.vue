<template>
  <div class="d-flex flex-column gap-5">
    
    <div class="row g-5 g-xl-8">
      <div class="col-xl-4 col-md-6 animate-item" style="--delay: 0s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-orange theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-orange text-orange rounded-4">
                <i class="ki-duotone ki-people fs-2x text-orange"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ users.length }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Pengguna</span>
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
                    <input type="text" v-model="searchQuery" class="form-control form-control-solid ps-12 search-input" placeholder="Cari user..." />
                </div>

                <div class="d-flex flex-wrap gap-3 align-items-center w-100 w-sm-auto justify-content-end">
                    
                    <div class="dropdown-wrapper position-relative w-150px" v-click-outside="() => closeDropdown()">
                        <button 
                            class="btn btn-custom-select w-100 d-flex align-items-center justify-content-between px-4" 
                            type="button" 
                            @click="toggleDropdown()"
                            :class="{ 'active': isDropdownOpen }"
                        >
                            <div class="d-flex align-items-center text-truncate">
                                <i class="ki-duotone ki-shield-tick fs-2 me-2 text-gray-500"><span class="path1"></span><span class="path2"></span></i>
                                <span class="fw-bold text-gray-700 fs-7">{{ activeRoleLabel }}</span>
                            </div>
                            <i class="ki-duotone ki-down fs-5 text-gray-500 ms-2 transition-icon" :class="{ 'rotate-180': isDropdownOpen }"></i>
                        </button>

                        <transition name="dropdown-anim">
                            <div v-if="isDropdownOpen" class="custom-dropdown-menu shadow-lg border-0 p-2 rounded-3 theme-dropdown">
                                <ul class="list-unstyled m-0">
                                    <li>
                                        <a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" 
                                           :class="{ 'selected': filterRole === 'all' }" 
                                           @click.prevent="setFilterRole('all')">
                                            Semua Role
                                        </a>
                                    </li>
                                    <li v-for="role in uniqueRoles" :key="role">
                                        <a href="#" class="dropdown-item-custom rounded px-3 py-2 fw-semibold mb-1" 
                                           :class="{ 'selected': filterRole === role }" 
                                           @click.prevent="setFilterRole(role)">
                                            {{ role }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </transition>
                    </div>

                    <button v-if="userHasPermission('create users')" class="btn btn-sm btn-orange fw-bold hover-scale box-shadow-orange" @click="openAddModal">
                        <i class="ki-duotone ki-plus fs-2 text-white"></i> Tambah User
                    </button>
                </div>
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

        <div v-else-if="filteredUsers.length === 0" class="d-flex flex-column align-items-center justify-content-center py-15 text-muted animate-fade-in">
             <div class="symbol symbol-100px mb-5 bg-light-orange rounded-circle d-flex align-items-center justify-content-center">
                <i class="ki-duotone ki-profile-user fs-4x text-orange"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
             </div>
             <span class="fs-4 fw-bold text-gray-800">Tidak ada user ditemukan.</span>
        </div>

        <div v-else>
            <TransitionGroup name="fade-grid" tag="div" class="row g-6">
                <div class="col-md-6 col-lg-4 col-xl-3" v-for="user in filteredUsers" :key="user.uuid">
                    
                    <div class="card h-100 border-0 shadow-sm theme-card hover-elevate-up transition-300 group-card">
                        <div class="card-body p-5 d-flex flex-column">
                            
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-50px symbol-circle me-3">
                                    <div class="symbol-label fw-bold fs-3 text-white" :class="getRandomColorClass(user.name)">
                                        {{ getInitials(user.name) }}
                                    </div>
                                    <div class="symbol-badge bg-success start-100 top-100 border-4 h-15px w-15px ms-n2 mt-n2"></div>
                                </div>
                                <div class="d-flex flex-column overflow-hidden">
                                    <a href="#" class="text-gray-900 text-hover-orange fw-bold fs-6 text-truncate transition-200" @click.prevent="openEditModal(user)">
                                        {{ user.name }}
                                    </a>
                                    <span class="badge badge-light fw-bold fs-9 mt-1 w-fit-content" :class="getRoleBadgeClass(user.roles?.[0]?.name)">
                                        {{ user.roles?.[0]?.name || 'User' }}
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-3 mb-5">
                                <div class="d-flex align-items-center text-gray-600 fs-7">
                                    <i class="ki-duotone ki-sms fs-5 me-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                                    <span class="text-truncate">{{ user.email }}</span>
                                </div>
                                <div class="d-flex align-items-center text-gray-600 fs-7">
                                    <i class="ki-duotone ki-phone fs-5 me-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                                    <span>{{ user.phone || '-' }}</span>
                                </div>
                            </div>

                            <div class="mt-auto d-flex gap-2 pt-4 border-top border-gray-200 border-dashed">
                                <button v-if="userHasPermission('edit users')" @click="openEditModal(user)" class="btn btn-sm btn-light btn-active-light-orange fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-pencil fs-5"><span class="path1"></span><span class="path2"></span></i> Edit
                                </button>
                                <button v-if="userHasPermission('delete users')" @click="deleteUser(user.uuid)" class="btn btn-sm btn-light btn-active-light-danger fw-bold flex-grow-1">
                                    <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                                </button>
                            </div>

                        </div>
                    </div>

                </div>
            </TransitionGroup>
        </div>
    </div>

    <FormModal 
        :user-data="selectedUser" 
        @user-updated="refreshTable" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";
import { Modal } from "bootstrap";
import FormModal from "./Form.vue"; 

// --- INTERFACE (DIPERBAIKI AGAR COCOK DENGAN FORM.VUE) ---
interface Role { 
    id: number; 
    name: string; 
    full_name: string; // Tambahkan ini agar cocok dengan Form.vue
}

interface User { 
    uuid: string; 
    name: string; 
    email: string; 
    phone: string; 
    roles: Role[]; 
}

const authStore = useAuthStore();
const users = ref<User[]>([]);
const loading = ref(true);
const selectedUser = ref<User | null>(null); // Type User sekarang sudah cocok dengan prop userData
const searchQuery = ref("");
const filterRole = ref('all');
const isDropdownOpen = ref(false);

const userHasPermission = (permission: string) => authStore.user?.all_permissions?.includes(permission) ?? false;

// --- Dropdown Logic ---
const toggleDropdown = () => { isDropdownOpen.value = !isDropdownOpen.value; };
const closeDropdown = () => { isDropdownOpen.value = false; };
const setFilterRole = (role: string) => { filterRole.value = role; closeDropdown(); };

const vClickOutside = {
    mounted(el: any, binding: any) {
        el.clickOutsideEvent = function(event: Event) { if (!(el === event.target || el.contains(event.target))) binding.value(event, el); };
        document.body.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el: any) { document.body.removeEventListener('click', el.clickOutsideEvent); },
};

// --- Computed ---
const activeRoleLabel = computed(() => filterRole.value === 'all' ? 'Semua Role' : filterRole.value);

const uniqueRoles = computed(() => {
    const roles = users.value.map(u => u.roles?.[0]?.name).filter(Boolean);
    return [...new Set(roles)];
});

const filteredUsers = computed(() => {
    let result = users.value;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || u.phone.includes(q));
    }
    if (filterRole.value !== 'all') {
        result = result.filter(u => u.roles?.[0]?.name === filterRole.value);
    }
    return result;
});

// --- Utils ---
const getInitials = (name: string) => {
    if (!name) return "?";
    const parts = name.split(" ");
    return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : parts[0].substring(0, 2).toUpperCase();
};

const getRandomColorClass = (name: string) => {
    const colors = ['bg-orange', 'bg-primary', 'bg-success', 'bg-danger', 'bg-info', 'bg-dark'];
    return colors[name.length % colors.length]; // Deterministic random based on name length
};

const getRoleBadgeClass = (roleName: string) => {
    if (roleName === 'super-admin' || roleName === 'admin') return 'badge-light-danger text-danger';
    if (roleName === 'cashier') return 'badge-light-warning text-warning';
    return 'badge-light-primary text-primary';
};

// --- Actions ---
const fetchUsers = async () => {
    try {
        loading.value = true;
        const response = await ApiService.get("/master/users");
        users.value = response.data.data || response.data;
    } catch (error) {
        console.error("Gagal memuat user:", error);
    } finally {
        loading.value = false;
    }
};

const refreshTable = () => { fetchUsers(); };

const openAddModal = () => {
    selectedUser.value = null;
    const modalEl = document.getElementById("kt_modal_user");
    if (modalEl) new Modal(modalEl).show();
};

const openEditModal = (user: User) => {
    selectedUser.value = { ...user };
    const modalEl = document.getElementById("kt_modal_user");
    if (modalEl) new Modal(modalEl).show();
};

const deleteUser = (uuid: string) => {
    Swal.fire({
        text: "Hapus user ini?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn fw-bold btn-danger", cancelButton: "btn fw-bold btn-light" },
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await ApiService.delete(`/master/users/${uuid}`);
                Swal.fire("Berhasil", "User dihapus.", "success");
                fetchUsers();
            } catch (error) {
                Swal.fire("Error", "Gagal menghapus user.", "error");
            }
        }
    });
};

onMounted(fetchUsers);
</script>

<style scoped>
/* ========================
   THEME & COLORS
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF8F1 !important; }
.bg-orange { background-color: #F68B1E !important; }
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
   DROPDOWN & CUSTOM UI
   ======================== */
.dropdown-wrapper { position: relative; z-index: 100; }
.dropdown-wrapper:has(.custom-dropdown-menu) { z-index: 9999 !important; }

.btn-custom-select {
    background-color: #F9F9F9; border: 1px solid transparent; border-radius: 12px; height: 42px; transition: all 0.3s ease;
}
.btn-custom-select:hover, .btn-custom-select.active { background-color: #ffffff; border-color: #F68B1E; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.transition-icon { transition: transform 0.3s ease; }
.rotate-180 { transform: rotate(180deg); color: #F68B1E !important; }

.custom-dropdown-menu {
    position: absolute; top: 110%; left: 0; width: 100%; min-width: 180px; background: white; z-index: 99999 !important; border: 1px solid rgba(0,0,0,0.05);
}
.dropdown-item-custom { display: block; text-decoration: none; color: #5E6278; transition: all 0.2s ease; cursor: pointer; }
.dropdown-item-custom:hover { background-color: #F5F8FA; color: #F68B1E; transform: translateX(5px); }
.dropdown-item-custom.selected { background-color: #FFF4E6; color: #F68B1E; font-weight: 700; }

.dropdown-anim-enter-active { animation: dropdown-in 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.dropdown-anim-leave-active { animation: dropdown-out 0.2s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes dropdown-in { 0% { opacity: 0; transform: translateY(-10px) scale(0.95); } 100% { opacity: 1; transform: translateY(0) scale(1); } }
@keyframes dropdown-out { 0% { opacity: 1; transform: translateY(0) scale(1); } 100% { opacity: 0; transform: translateY(-10px) scale(0.95); } }

/* ========================
   DARK MODE
   ======================== */
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; color: #ffffff; }
[data-bs-theme="dark"] .theme-dropdown { background-color: #1e1e2d; border-color: #323248; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e1e3ea !important; }
[data-bs-theme="dark"] .text-gray-700 { color: #CDCDDE !important; }
[data-bs-theme="dark"] .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .bg-light-subtle { background-color: #1b1b29 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .bg-light-orange { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .border-gray-200 { border-color: #323248 !important; }
[data-bs-theme="dark"] .form-control-solid { background-color: #1b1b29 !important; border-color: #323248 !important; color: #ffffff; }
[data-bs-theme="dark"] .btn-custom-select { background-color: #1b1b29; border-color: #323248; color: #CDCDDE; }
[data-bs-theme="dark"] .btn-custom-select:hover, [data-bs-theme="dark"] .btn-custom-select.active { border-color: #F68B1E; color: #ffffff; }
[data-bs-theme="dark"] .custom-dropdown-menu { background-color: #1e1e2d; border: 1px solid #323248; }
[data-bs-theme="dark"] .dropdown-item-custom { color: #9A9CAE; }
[data-bs-theme="dark"] .dropdown-item-custom:hover { background-color: #2b2b40; color: #F68B1E; }
[data-bs-theme="dark"] .dropdown-item-custom.selected { background-color: rgba(246, 139, 30, 0.15); }
</style>