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
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Tamu</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-xl-4 col-12 animate-item" style="--delay: 0.1s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-success theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-success text-success rounded-4">
                <i class="ki-duotone ki-user-tick fs-2x text-success"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ activeGuestCount }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Sedang Menginap</span>
            </div>
          </div>
        </div>
      </div>

       <div class="col-xl-4 col-12 animate-item" style="--delay: 0.2s">
        <div class="card bg-body hover-elevate-up border-0 h-100 card-stat-danger theme-card shadow-sm">
          <div class="card-body d-flex align-items-center">
            <div class="symbol symbol-50px me-3">
              <div class="symbol-label bg-light-danger text-danger rounded-4">
                <i class="ki-duotone ki-shield-cross fs-2x text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
              </div>
            </div>
            <div class="d-flex flex-column">
              <span class="fw-bolder fs-2 text-gray-900 mb-1">{{ blacklistedCount }}</span>
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Blacklist</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm theme-card animate-item position-relative" style="--delay: 0.3s; z-index: 1;">
        <div class="card-body py-4">
            
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-5 gap-3">
                
                <div class="d-flex align-items-center gap-2 w-100 w-md-auto">
                    <div class="position-relative w-100 w-md-250px">
                        <i class="ki-duotone ki-magnifier fs-3 text-gray-500 position-absolute top-50 translate-middle-y ms-4"><span class="path1"></span><span class="path2"></span></i>
                        <input type="text" v-model="search" class="form-control form-control-solid ps-12" placeholder="Cari Tamu..." />
                    </div>

                    <div class="w-150px">
                        <el-select v-model="filterStatus" placeholder="Filter Status" class="w-100 metronic-select">
                            <el-option label="Semua Status" value="all" />
                            <el-option label="Sedang Check-in" value="active" />
                            <el-option label="Sudah Checkout" value="inactive" />
                            <el-option label="Blacklisted" value="blacklisted" />
                        </el-select>
                    </div>
                </div>

                <button @click="openModal()" class="btn btn-orange fw-bold w-100 w-md-auto">
                    <i class="ki-duotone ki-plus fs-2 text-white me-1"></i> Tambah Tamu
                </button>
            </div>

            <el-table :data="paginatedData" style="width: 100%" v-loading="loading" class="modern-table no-border-rows">
                
                <el-table-column label="Nama Tamu" min-width="220">
                    <template #default="scope">
                        <div class="d-flex align-items-center py-2">
                            <div class="symbol symbol-40px symbol-circle me-3">
                                <span v-if="scope.row.is_blacklisted" class="symbol-label bg-light-danger text-danger fw-bold fs-6">
                                    <i class="ki-duotone ki-cross fs-3"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                                <span v-else class="symbol-label bg-light-orange text-orange fw-bold fs-6">
                                    {{ getInitials(scope.row.name) }}
                                </span>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <span class="text-gray-800 fw-bold fs-6 mb-1 me-2">{{ scope.row.name }}</span>
                                    <span v-if="scope.row.is_blacklisted" class="badge badge-light-danger fw-bold fs-9">BLACKLISTED</span>
                                </div>
                                <span class="text-gray-400 fs-9">{{ scope.row.email || '-' }}</span>
                            </div>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column label="Kontak" min-width="150">
                    <template #default="scope">
                        <div class="d-flex align-items-center text-gray-600">
                            <i class="ki-duotone ki-phone fs-5 me-2 text-gray-400"><span class="path1"></span><span class="path2"></span></i>
                            <span class="fw-semibold fs-7">{{ scope.row.phone_number || '-' }}</span>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column label="Status" min-width="200">
                    <template #default="scope">
                        <div v-if="scope.row.is_blacklisted">
                             <el-tooltip :content="scope.row.blacklist_reason || 'Tidak ada alasan'" placement="top">
                                <span class="badge badge-light-danger fw-bold fs-8 px-3 py-2 cursor-pointer">
                                    <i class="ki-duotone ki-shield-cross fs-8 text-danger me-1"><span class="path1"></span><span class="path2"></span></i>
                                    DILARANG CHECK-IN
                                </span>
                            </el-tooltip>
                        </div>
                        
                        <div v-else-if="scope.row.check_ins && scope.row.check_ins.length > 0">
                            <span class="badge badge-light-success fw-bold fs-8 px-3 py-2 d-inline-flex align-items-center gap-2">
                                <span class="bullet bullet-dot bg-success h-6px w-6px"></span>
                                Active: Kamar {{ scope.row.check_ins[0].room?.room_number }}
                            </span>
                        </div>
                        
                        <div v-else>
                            <span class="badge badge-light-secondary fw-bold fs-8 px-3 py-2 text-gray-500">
                                <i class="ki-duotone ki-check fs-8 text-gray-400 me-1"></i> Tidak Aktif
                            </span>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column label="" width="120" align="end">
                    <template #default="scope">
                        <button class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" @click="editGuest(scope.row)">
                            <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>
                        </button>
                        <button class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm" @click="deleteGuest(scope.row.id)">
                            <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                        </button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="d-flex justify-content-end mt-4">
                <el-pagination 
                    background 
                    layout="prev, pager, next" 
                    :total="filteredGuests.length" 
                    :page-size="pageSize"
                    v-model:current-page="currentPage"
                    class="custom-pagination"
                />
            </div>
        </div>
    </div>

    <GuestModal 
        ref="guestModalRef" 
        :is-edit-mode="isEditMode" 
        :guest-data="selectedGuest"
        @saved="fetchGuests" 
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import ApiService from '@/core/services/ApiService';
import Swal from 'sweetalert2';
import GuestModal from './GuestModal.vue';

// ===== INTERFACES =====
interface Room {
    id: number;
    room_number: string;
}

interface CheckIn {
    id: number;
    is_active: boolean;
    room?: Room;
}

interface Guest {
    id: number;
    name: string;
    email: string;
    phone_number: string;
    address: string;
    // Field baru
    is_blacklisted: boolean | number;
    blacklist_reason?: string;
    check_ins?: CheckIn[];
}

// ===== STATE =====
const guests = ref<Guest[]>([]);
const loading = ref(false);
const search = ref('');
const filterStatus = ref('all');
const currentPage = ref(1);
const pageSize = ref(10);

// Modal
const guestModalRef = ref<any>(null); // Ref untuk akses defineExpose di anak
const isEditMode = ref(false);
const selectedGuest = ref<Guest | null>(null);

// ===== COMPUTED =====
const activeGuestCount = computed(() => guests.value.filter(g => g.check_ins && g.check_ins.length > 0).length);
const blacklistedCount = computed(() => guests.value.filter(g => g.is_blacklisted).length);

// FILTER LOGIC
const filteredGuests = computed(() => {
    let data = guests.value;

    // Filter Dropdown
    if (filterStatus.value === 'active') {
        data = data.filter(g => g.check_ins && g.check_ins.length > 0);
    } else if (filterStatus.value === 'inactive') {
        data = data.filter(g => (!g.check_ins || g.check_ins.length === 0) && !g.is_blacklisted);
    } else if (filterStatus.value === 'blacklisted') {
        data = data.filter(g => g.is_blacklisted);
    }

    // Filter Search
    if (search.value) {
        const lower = search.value.toLowerCase();
        data = data.filter(g => 
            g.name.toLowerCase().includes(lower) || 
            (g.phone_number && g.phone_number.includes(lower))
        );
    }

    return data;
});

const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    const end = start + pageSize.value;
    return filteredGuests.value.slice(start, end);
});

// ===== METHODS =====
const getInitials = (name: string) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const fetchGuests = async () => {
    loading.value = true;
    try {
        const response = await ApiService.get('/guests');
        guests.value = response.data;
    } catch (error) {
        console.error("Error fetching guests:", error);
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    isEditMode.value = false;
    selectedGuest.value = null;
    // Memanggil fungsi open() yang sudah di-expose di GuestModal.vue
    guestModalRef.value?.open();
};

const editGuest = (guest: Guest) => {
    isEditMode.value = true;
    selectedGuest.value = { ...guest };
    // Memanggil fungsi open() yang sudah di-expose
    guestModalRef.value?.open();
};

const deleteGuest = async (id: number) => {
    const result = await Swal.fire({
        text: "Hapus data tamu ini?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Hapus",
        cancelButtonText: "Batal",
        customClass: { confirmButton: "btn btn-danger", cancelButton: "btn btn-light" }
    });

    if (result.isConfirmed) {
        try {
            await ApiService.delete(`/guests/${id}`);
            Swal.fire("Terhapus!", "", "success");
            fetchGuests();
        } catch (error: any) {
            Swal.fire("Gagal!", error.response?.data?.message || "Error.", "error");
        }
    }
};

// ===== LIFECYCLE =====
onMounted(() => {
    fetchGuests();
});
</script>

<style scoped>
/* COLORS */
.text-orange { color: #F68B1E !important; }
.bg-light-orange { background-color: #FFF8F1 !important; }
.btn-orange { background-color: #F68B1E; color: white; border: none; transition: 0.3s; }
.btn-orange:hover { background-color: #d97814; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(246, 139, 30, 0.2); }

/* STAT CARDS */
.card-stat-orange { border-left: 4px solid #F68B1E; }
.card-stat-success { border-left: 4px solid #17C653; }
.card-stat-danger { border-left: 4px solid #f1416c; } /* Merah untuk blacklist */

/* TABLE STYLING */
:deep(.modern-table .el-table__header th) {
    background-color: transparent !important;
    color: #99A1B7;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    border-bottom: 1px solid #F1F1F4;
}
:deep(.modern-table.no-border-rows .el-table__row td) { border-bottom: none !important; padding: 1rem 0; }
:deep(.modern-table .el-table__row:hover td) { background-color: #F9F9F9; border-radius: 4px; }

/* BADGES */
.badge-light-success { background-color: #E8FFF3; color: #17C653; }
.badge-light-secondary { background-color: #F1F1F4; color: #7E8299; }
.badge-light-danger { background-color: #fff5f8; color: #f1416c; }

/* INPUT & SELECT */
:deep(.metronic-select .el-input__wrapper) {
    background-color: #F9F9F9;
    box-shadow: none !important;
    border-radius: 0.475rem;
    height: 42px;
}
:deep(.metronic-select .el-input__wrapper.is-focus) {
    background-color: #ffffff;
    box-shadow: 0 0 0 1px #F68B1E !important;
}

/* DARK MODE */
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; color: white; }
[data-bs-theme="dark"] .bg-body { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .text-gray-900 { color: white !important; }
[data-bs-theme="dark"] :deep(.el-table) { 
    --el-table-bg-color: #1e1e2d; 
    --el-table-tr-bg-color: #1e1e2d; 
    --el-table-header-bg-color: #1e1e2d;
    color: #CDCDDE;
}
[data-bs-theme="dark"] :deep(.modern-table .el-table__row:hover td) { background-color: #2b2b40 !important; }
[data-bs-theme="dark"] .badge-light-danger { background-color: rgba(241, 65, 108, 0.15); color: #f1416c; }
</style>