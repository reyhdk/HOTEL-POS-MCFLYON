<template>
  <div class="kds-index-container d-flex flex-column gap-6 anim-fade-in p-2 p-md-5">
    
    <!-- HEADER MODERN & CLEAN -->
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-5 mb-2 anim-slide-down">
       <div class="header-content">
          <h1 class="text-gray-900 fw-bolder fs-2 mb-2 tracking-tight">Dapur & Antrean</h1>
          <div class="d-flex align-items-center gap-2">
              <span class="badge badge-light fw-bold text-gray-600 px-3 py-2 rounded-pill fs-8 border border-gray-200">
                  <i class="bi bi-calendar-event me-2"></i> {{ currentDate }}
              </span>
              <span class="badge badge-light-success fw-bold px-3 py-2 rounded-pill fs-8">
                  <i class="bi bi-cloud-check-fill me-2"></i> Terkoneksi
              </span>
          </div>
       </div>

       <div class="d-flex flex-column flex-sm-row align-items-center gap-3 w-100 w-xl-auto">
          <!-- Filter Status Element Plus -->
          <div class="w-100 w-sm-200px">
              <el-select 
                  v-model="filters.status" 
                  @change="handleStatusChange" 
                  placeholder="Pilih Status" 
                  class="premium-filter-select w-100"
              >
                  <template #prefix>
                      <div class="d-flex align-items-center gap-2">
                          <i class="ki-duotone ki-filter-search fs-4 text-gray-500">
                              <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                          </i>
                          <span class="fs-9 fw-bold text-gray-600 text-uppercase ls-1">Status</span>
                      </div>
                  </template>
                  <el-option label="Semua Pesanan" value="" />
                  <el-option label="Antrean (Pending)" value="pending" />
                  <el-option label="Proses Masak" value="processing" />
                  <el-option label="Siap Antar" value="ready_for_delivery" />
              </el-select>
          </div>

          <!-- Search Bar -->
          <div class="position-relative w-100 w-sm-250px">
             <i class="bi bi-search position-absolute top-50 ms-4 translate-middle-y text-gray-400 fs-6"></i>
             <input 
                type="text" 
                v-model="searchInput" 
                @input="handleSearch"
                class="form-control form-control-solid ps-12 py-2 rounded-pill border border-gray-200 shadow-none fs-7"
                placeholder="Cari pesanan..."
             />
          </div>
       </div>
    </div>

    <!-- PANEL MANAJEMEN DAPUR (MODERN MINIMALIST) -->
    <!-- mb-8 ditambahkan agar tidak nabrak dengan card di bawahnya -->
    <div class="chef-panel bg-white rounded-4 p-5 border border-gray-200 mb-8 anim-fade-up">
       <div class="row align-items-center g-4">
          <div class="col-md-6 col-lg-7 d-flex align-items-center">
             <div class="symbol symbol-50px me-4 rounded-3 bg-light-primary border border-primary border-opacity-25 d-flex align-items-center justify-content-center">
                <i class="bi bi-person-workspace fs-2 text-primary"></i>
             </div>
             <div>
                <h4 class="fw-bolder text-gray-800 m-0 fs-5">Kru Dapur Aktif</h4>
                <p class="text-gray-500 fs-8 m-0 mt-1">Kelola koki yang bertugas saat ini.</p>
             </div>
          </div>
          <div class="col-md-6 col-lg-5">
             <div class="d-flex align-items-center justify-content-md-end gap-3">
                <div class="stats-group d-none d-sm-flex gap-4 me-4">
                    <div class="text-center">
                        <div class="fs-5 fw-bolder text-primary">{{ selectedChefs.length }}</div>
                        <div class="fs-9 fw-bold text-gray-400 text-uppercase ls-1">Aktif</div>
                    </div>
                    <div class="vr bg-gray-200 h-25px align-self-center"></div>
                    <div class="text-center">
                        <div class="fs-5 fw-bolder text-gray-600">{{ allChefs.length }}</div>
                        <div class="fs-9 fw-bold text-gray-400 text-uppercase ls-1">Total</div>
                    </div>
                </div>
                <button @click="openChefManager" class="btn btn-light-primary fw-bold px-6 py-2 rounded-pill hover-elevate-up">
                    <i class="bi bi-people fs-5 me-2"></i> Kelola Koki
                </button>
             </div>
          </div>
       </div>
    </div>

    <!-- KONTEN PESANAN (GRID CLEAN) -->
    <div class="position-relative min-h-400px">
       <div v-if="isLoadingOrders" class="py-15 text-center">
          <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem; opacity: 0.5;"></div>
          <p class="text-gray-500 fw-bold fs-7">Memuat antrean...</p>
       </div>

       <div v-else-if="orders.length === 0" class="empty-state py-15 text-center animate-fade-in">
           <div class="symbol symbol-100px mb-5 bg-light rounded-circle p-8 border border-gray-200">
                <i class="bi bi-inbox fs-3x text-gray-300"></i>
           </div>
           <h4 class="text-gray-700 fw-bolder fs-5">Dapur Kosong</h4>
           <p class="text-gray-500 fs-8 mx-auto mw-300px">Belum ada pesanan untuk filter saat ini.</p>
       </div>

       <div v-else class="row g-5">
          <TransitionGroup name="staggered-list" tag="div" class="contents" style="display: contents;">
             <div 
                class="col-12 col-md-6 col-lg-4 col-xxl-3" 
                v-for="(order, index) in orders" 
                :key="order.id"
                :style="{ '--delay': `${index * 0.05}s` }"
             >
                <div class="h-100 anim-staggered-item" @click="openDetail(order)"> 
                    <div class="card h-100 border border-gray-200 shadow-none rounded-4 hover-lift card-order transition-300 cursor-pointer bg-white">
                       <!-- Penanda warna status yang lebih soft (kiri) -->
                       <div class="status-strip opacity-75" :class="getLineColor(order.status)"></div>

                       <div class="card-body p-5 d-flex flex-column">
                           <div class="d-flex justify-content-between align-items-center mb-4">
                              <span class="badge px-3 py-1 rounded-pill fs-9 fw-bold text-uppercase border" :class="getStatusClass(order.status)">
                                 {{ getStatusLabel(order.status) }}
                              </span>
                              <div class="text-gray-400 fs-9 fw-bold">
                                 #{{ order.order_code?.split('/').pop() || order.id }}
                              </div>
                           </div>

                           <div class="text-center mb-4 flex-grow-1 pt-2">
                              <h3 class="fw-bolder text-gray-800 fs-4 mb-1">{{ getLocationInfo(order) }}</h3>
                              <div class="text-gray-500 fw-semibold fs-8 text-truncate mb-4">{{ getGuestName(order) }}</div>
                              
                              <!-- Ringkasan menu dengan desain bersih (tidak mencolok) -->
                              <div class="menu-summary d-inline-flex align-items-center bg-light rounded-pill px-4 py-2 border border-gray-200">
                                 <i class="bi bi-journal-text me-2 fs-6 text-gray-500"></i>
                                 <span class="text-gray-700 fw-bold fs-7">{{ order.items?.length || 0 }} Menu</span>
                              </div>
                           </div>

                           <div class="separator separator-dashed border-gray-200 w-100 mb-4"></div>

                           <div class="d-flex align-items-center w-100 mt-auto justify-content-between">
                              <div class="d-flex align-items-center gap-2">
                                 <div class="symbol symbol-25px symbol-circle bg-light-primary border border-primary border-opacity-25">
                                    <span class="symbol-label text-primary fs-9 fw-bolder">
                                        {{ order.chef?.name ? order.chef.name.charAt(0).toUpperCase() : '?' }}
                                    </span>
                                 </div>
                                 <span class="text-gray-600 fw-semibold fs-8 text-truncate mw-100px">{{ order.chef?.name || 'Otomatis' }}</span>
                              </div>
                              <div class="d-flex align-items-center text-gray-500 fs-8 fw-bold bg-light px-2 py-1 rounded">
                                  <i class="bi bi-clock me-1"></i> {{ order.calculated_estimation || 0 }}m
                              </div>
                           </div>
                       </div>
                    </div>
                </div>
             </div>
          </TransitionGroup>
       </div>

       <!-- Pagination (Lebih minimalis) -->
       <div class="d-flex flex-stack flex-wrap pt-8 mt-5" v-if="pagination.last_page > 1">
           <div class="fs-8 fw-bold text-gray-500">Hal {{ pagination.current_page }} dari {{ pagination.last_page }}</div>
           <ul class="pagination pagination-sm gap-1 m-0">
               <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                   <button class="page-link rounded bg-light border-0 text-gray-600" @click="changePage(pagination.current_page - 1)"><i class="bi bi-chevron-left"></i></button>
               </li>
               <li class="page-item" v-for="p in pagination.last_page" :key="p" :class="{ active: pagination.current_page === p }">
                   <button class="page-link rounded border-0" :class="pagination.current_page === p ? 'bg-primary text-white' : 'bg-light text-gray-600'" @click="changePage(p)">{{ p }}</button>
               </li>
               <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                   <button class="page-link rounded bg-light border-0 text-gray-600" @click="changePage(pagination.current_page + 1)"><i class="bi bi-chevron-right"></i></button>
               </li>
           </ul>
       </div>
    </div>

    <!-- MODAL MANAJEMEN KOKI (TERINTEGRASI TAMBAH KOKI) -->
    <div class="modal fade" id="modal_chef_manager" tabindex="-1" aria-hidden="true" ref="chefManagerModalRef">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-sm border-0 rounded-4 card-adaptive overflow-hidden">
                <div class="modal-header border-bottom border-gray-200 py-4 px-6">
                    <h5 class="modal-title fw-bolder text-gray-800 d-flex align-items-center fs-4">
                        <i class="bi bi-people text-primary me-2 fs-4"></i> Manajemen Kru Dapur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-6">
                    
                    <!-- Area Tambah Koki (Terintegrasi) -->
                    <div class="bg-light p-4 rounded-3 border border-gray-200 mb-6 d-flex flex-column flex-sm-row gap-3 align-items-sm-center">
                        <div class="flex-grow-1">
                            <label class="form-label fs-9 fw-bolder text-gray-600 text-uppercase mb-1">Tambah Koki Bertugas</label>
                            <!-- Menggunakan el-select untuk dropdown user agar rapi -->
                            <el-select 
                                v-model="newChefData.user_id" 
                                placeholder="Pilih akun pengguna..." 
                                class="w-100" 
                                filterable
                            >
                                <el-option 
                                    v-for="user in eligibleUsers" 
                                    :key="user.id" 
                                    :label="`${user.name} (${user.email})`" 
                                    :value="user.id" 
                                />
                            </el-select>
                        </div>
                        <div class="mt-sm-4 pt-sm-1">
                            <button @click="saveNewChef" class="btn btn-primary fw-bold px-5" :disabled="!newChefData.user_id || isSavingChef">
                                <span v-if="isSavingChef" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-plus-lg me-1"></i> Tambah
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive rounded-3 border border-gray-200">
                        <table class="table align-middle gs-4 gy-4 mb-0">
                            <thead>
                                <tr class="fw-bold text-gray-500 fs-9 text-uppercase bg-light border-bottom border-gray-200">
                                    <th class="ps-4">Koki</th>
                                    <th class="text-center">Antrean</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="chef in allChefs" :key="chef.id" class="border-bottom border-gray-100 hover-bg-light">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-35px me-3 bg-light-secondary rounded">
                                                <span class="symbol-label text-gray-700 fw-bold">{{ chef.user?.name?.charAt(0) }}</span>
                                            </div>
                                            <span class="text-gray-800 fw-semibold fs-7">{{ chef.user?.name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-gray-600 fw-bold fs-9 border border-gray-200 px-2 py-1 rounded">
                                            {{ chef.current_workload }} Job
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch form-check-custom form-check-solid justify-content-center">
                                            <input class="form-check-input h-20px w-35px cursor-pointer" type="checkbox" :checked="chef.is_active" @change="toggleChefStatus(chef)" />
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button @click="removeChef(chef)" class="btn btn-icon btn-light-danger btn-sm rounded shadow-none">
                                            <i class="bi bi-trash fs-7"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="allChefs.length === 0">
                                    <td colspan="4" class="text-center py-10">
                                        <div class="text-gray-400 fs-7">Belum ada kru yang bertugas hari ini.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <OrderDetailModal ref="detailModalRef" />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, onUnmounted } from "vue";
import ApiService from "@/core/services/ApiService";
import OrderDetailModal from "./OrderDetailModal.vue"; 
import Swal from "sweetalert2";
import { Modal } from "bootstrap";

const orders = ref<any[]>([]);
const allChefs = ref<any[]>([]); 
const eligibleUsers = ref<any[]>([]);
const isLoadingOrders = ref(true);
const isLoadingChefs = ref(true);
const isSavingChef = ref(false);
const detailModalRef = ref<InstanceType<typeof OrderDetailModal> | null>(null);
const chefManagerModalRef = ref<HTMLElement | null>(null);

let chefManagerModal: Modal | null = null;

const currentDate = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const searchInput = ref('');
let searchTimeout: any = null;
let pollInterval: any = null;

// Payload disederhanakan karena form hanya butuh user_id
const newChefData = reactive({
    user_id: '',
    specialization: 'General', // Default jika API butuh
    notes: '',
    max_concurrent_orders: 5,
    is_active: true
});

const filters = reactive({ search: '', status: '', page: 1, per_page: 12 });
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 12, total: 0 });

const statusOptions: Record<string, string> = {
    '': 'Semua', 'pending': 'Antrean', 'processing': 'Masak', 'ready_for_delivery': 'Antar'
};

const selectedChefs = computed(() => {
    return allChefs.value.filter(chef => chef.is_active);
});

// --- ACTIONS ---
const openChefManager = () => {
    fetchChefs();
    fetchEligibleUsers(); // Panggil saat modal terbuka
    chefManagerModal?.show();
};

const fetchEligibleUsers = async () => {
    try {
        const { data } = await ApiService.get('/chefs/eligible-users');
        eligibleUsers.value = data;
    } catch (e) {
        console.error("Gagal ambil eligible users", e);
    }
};

const saveNewChef = async () => {
    isSavingChef.value = true;
    try {
        await ApiService.post('/chefs', newChefData);
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Koki ditambahkan', showConfirmButton: false, timer: 1500 });
        
        // Reset Dropdown
        newChefData.user_id = '';
        fetchChefs(); // Refresh list koki
    } catch (e: any) {
        Swal.fire('Error', e.response?.data?.message || 'Gagal menambahkan koki', 'error');
    } finally {
        isSavingChef.value = false;
    }
};

const toggleChefStatus = async (chef: any) => {
    try {
        await ApiService.patch(`/chefs/${chef.id}/toggle-status`, {});
        chef.is_active = !chef.is_active;
        calculateQueue();
    } catch (e) {
        Swal.fire('Error', 'Gagal mengubah status', 'error');
    }
};

const removeChef = async (chef: any) => {
    const result = await Swal.fire({
        title: 'Hapus Koki?',
        text: `Keluarkan ${chef.user?.name} dari daftar tugas?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' }
    });

    if (result.isConfirmed) {
        try {
            await ApiService.delete(`/chefs/${chef.id}`);
            allChefs.value = allChefs.value.filter(c => c.id !== chef.id);
        } catch (e: any) {
            Swal.fire('Gagal', e.response?.data?.message || 'Gagal menghapus koki', 'error');
        }
    }
};

const fetchChefs = async () => {
    isLoadingChefs.value = true;
    try {
        const { data } = await ApiService.get('/chefs');
        allChefs.value = data; 
    } catch (e) {
        console.error("Gagal ambil data koki", e);
    } finally {
        isLoadingChefs.value = false;
    }
};

const fetchOrders = async (quiet = false) => {
  if(!quiet) isLoadingOrders.value = true;
  try {
    const params = new URLSearchParams({
        page: filters.page.toString(),
        per_page: filters.per_page.toString(),
        status: filters.status,
        search: filters.search
    });
    const { data } = await ApiService.get(`/kitchen/orders?${params.toString()}`);
    orders.value = data.data; 
    pagination.current_page = data.current_page;
    pagination.last_page = data.last_page;
    pagination.total = data.total;
    calculateQueue();
  } catch (e) { 
      console.error(e); 
  } finally { 
      isLoadingOrders.value = false; 
  }
};

const calculateQueue = () => {
    let activeChefsCount = selectedChefs.value.length > 0 ? selectedChefs.value.length : 1;
    let chefWorkloads = Array(activeChefsCount).fill(0);
    let activeOrders = orders.value.filter(o => ['pending', 'processing', 'paid'].includes(o.status));
    let sortedOrders = [...activeOrders].reverse();

    sortedOrders.forEach(order => {
        let chosenChefIndex = 0;
        let minWorkload = chefWorkloads[0];
        for (let i = 1; i < chefWorkloads.length; i++) {
            if (chefWorkloads[i] < minWorkload) {
                minWorkload = chefWorkloads[i];
                chosenChefIndex = i;
            }
        }
        let orderCookingTime = 0;
        order.items?.forEach((item: any) => {
            orderCookingTime += (item.menu?.cooking_estimation_time || 10) * item.quantity;
        });
        order.calculated_estimation = minWorkload + Math.max(5, orderCookingTime);
        chefWorkloads[chosenChefIndex] += orderCookingTime;
    });
};

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        filters.page = 1; 
        fetchOrders();
    }, 500);
};

// Handle perubahan filter dari el-select
const handleStatusChange = () => {
    filters.page = 1;
    fetchOrders();
}

const changePage = (page: number) => {
    if (page < 1 || page > pagination.last_page) return;
    filters.page = page;
    fetchOrders();
};

const openDetail = (order: any) => { 
    if (detailModalRef.value) detailModalRef.value.open(order);
};

const getGuestName = (o: any) => o.guest?.name || o.user?.name || 'Walk-in';
const getLocationInfo = (o: any) => o.room ? `Kamar ${o.room.room_number}` : (o.table ? `Meja ${o.table.name}` : 'POS');
const getStatusLabel = (s: string) => statusOptions[s] || s;

// Pewarnaan status yang lebih kalem (soft)
const getStatusClass = (s: string) => {
    const map: Record<string, string> = { 
        pending: 'bg-light-danger text-danger border-danger border-opacity-25', 
        processing: 'bg-light-warning text-warning border-warning border-opacity-25', 
        ready_for_delivery: 'bg-light-success text-success border-success border-opacity-25' 
    };
    return map[s] || 'bg-light border-gray-200 text-gray-500';
};
const getLineColor = (s: string) => {
    const map: Record<string, string> = { pending: 'bg-danger', processing: 'bg-warning', ready_for_delivery: 'bg-success' };
    return map[s] || 'bg-secondary';
}

onMounted(() => {
    if (chefManagerModalRef.value) chefManagerModal = new Modal(chefManagerModalRef.value);

    fetchChefs(); 
    fetchOrders();
    pollInterval = setInterval(() => {
        fetchOrders(true);
        if (!chefManagerModalRef.value?.classList.contains('show')) {
            fetchChefs();
        }
    }, 15000);
});

onUnmounted(() => {
    if(pollInterval) clearInterval(pollInterval);
});
</script>

<style scoped>
/* Card & Clean UI Tweaks */
.card-adaptive { background-color: #fff; }

/* Animasi hover dikurangi intensitasnya agar tidak mencolok */
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.hover-lift:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.04) !important; border-color: #e4e6ef !important; }

/* Status strip di kiri card */
.status-strip { position: absolute; top: 0; left: 0; height: 100%; width: 4px; border-radius: 1rem 0 0 1rem; }

/* Style untuk el-select Premium Filter */
:deep(.premium-filter-select .el-input__wrapper) {
    background-color: #f8f9fa;
    border-radius: 50rem; /* pill shape */
    box-shadow: none !important;
    border: 1px solid #e4e6ef;
    padding-top: 0.25rem;
    padding-bottom: 0.25rem;
}
:deep(.premium-filter-select .el-input__wrapper.is-focus) {
    border-color: #009ef7; /* primary color */
}

.transition-300 { transition: all 0.3s ease; }
.hover-bg-light:hover { background-color: #f9f9f9; }

/* Animations */
.anim-fade-in { animation: fadeIn 0.6s ease; }
.anim-slide-down { animation: slideDown 0.5s ease; }
.anim-staggered-item { animation: fadeUp 0.4s ease forwards; opacity: 0; animation-delay: var(--delay); }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeUp { to { opacity: 1; transform: translateY(10px); } }
</style>

<style>
/* DARK MODE CLEAN ENHANCEMENTS */
[data-bs-theme="dark"] .kds-index-container .card-adaptive,
[data-bs-theme="dark"] .kds-index-container .card-order,
[data-bs-theme="dark"] .kds-index-container .chef-panel,
[data-bs-theme="dark"] .kds-index-container .bg-white { background-color: #1e1e2d !important; border-color: #2b2b40 !important; }

[data-bs-theme="dark"] .kds-index-container .bg-light { background-color: #2b2b40 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-800 { color: #e4e6ef !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-700 { color: #b5b5c3 !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-600 { color: #a1a5b7 !important; }
[data-bs-theme="dark"] .kds-index-container .text-gray-500 { color: #7e8299 !important; }

/* Element Plus Dark Mode Fixes */
[data-bs-theme="dark"] .premium-filter-select .el-input__wrapper {
    background-color: #1e1e2d !important;
    border-color: #2b2b40 !important;
}
[data-bs-theme="dark"] .el-select-dropdown {
    background-color: #1e1e2d !important;
    border-color: #2b2b40 !important;
}
[data-bs-theme="dark"] .el-select-dropdown__item {
    color: #a1a5b7;
}
[data-bs-theme="dark"] .el-select-dropdown__item.hover, 
[data-bs-theme="dark"] .el-select-dropdown__item:hover {
    background-color: #2b2b40;
}

[data-bs-theme="dark"] .modal-content.card-adaptive { background-color: #1e1e2d !important; }
[data-bs-theme="dark"] .modal-header { border-bottom-color: #2b2b40 !important; }
[data-bs-theme="dark"] .table thead tr { background-color: #151521 !important; border-bottom: 0 !important; }
[data-bs-theme="dark"] .table tr { border-bottom-color: #2b2b40 !important; }
[data-bs-theme="dark"] .hover-bg-light:hover { background-color: #151521 !important; }

[data-bs-theme="dark"] .form-control-solid { 
    background-color: #2b2b40 !important; 
    color: #ffffff !important; 
    border-color: #323248 !important; 
}
</style>