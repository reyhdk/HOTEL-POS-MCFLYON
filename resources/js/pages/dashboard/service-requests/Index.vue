<template>
  <div class="d-flex flex-column gap-5 anim-page-in">
    
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden anim-slide-down">
      <div class="card-body py-5 d-flex flex-column flex-md-row align-items-center justify-content-between gap-4 bg-header-gradient">
        
        <div class="d-flex align-items-center z-index-1">
           <div class="symbol symbol-50px me-4">
              <div class="symbol-label bg-white bg-opacity-20 text-white rounded-circle">
                 <i class="bi bi-bell-fill fs-2"></i>
              </div>
           </div>
           <div>
              <h1 class="fs-2 fw-bold text-white m-0 lh-1">Layanan Tamu</h1>
              <div class="text-white text-opacity-75 fw-semibold fs-7 mt-1">Housekeeping, Laundry & Amenities</div>
           </div>
        </div>

        <div class="d-flex gap-4 z-index-1">
            <div class="d-flex flex-column align-items-end">
                <span class="text-white text-opacity-75 fs-8 fw-bold text-uppercase ls-1">Permintaan Pending</span>
                <span class="fs-2 fw-bolder text-white">{{ countPending }}</span>
            </div>
        </div>

        <div class="position-absolute top-0 end-0 opacity-10 pe-none">
            <i class="bi bi-bell fs-5x text-white me-n5 mt-n5"></i>
        </div>
      </div>
    </div>

    <div class="card card-flush shadow-sm rounded-4 border-0 card-adaptive anim-slide-up" style="--delay: 0.1s">
        
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="bi bi-search fs-3 position-absolute ms-4 text-gray-400"></i>
                    <input 
                        type="text" 
                        v-model="filters.search" 
                        @input="handleSearch"
                        class="form-control form-control-solid w-250px ps-12 rounded-pill" 
                        placeholder="Cari Kamar..." 
                    />
                </div>
            </div>

            <div class="card-toolbar flex-row-fluid justify-content-end gap-3">
                <div class="dropdown">
                    <button 
                        class="btn btn-light fw-bold btn-sm rounded-pill px-4 border border-gray-300 dropdown-toggle hover-elevate transition-all" 
                        type="button" 
                        data-bs-toggle="dropdown"
                    >
                        <i class="bi bi-filter fs-5 me-1 text-gray-500"></i> 
                        <span class="text-gray-700">{{ getStatusLabel(filters.status) }}</span>
                    </button>
                    <ul class="dropdown-menu menu menu-sub menu-sub-dropdown menu-rounded menu-gray-600 menu-state-bg-light-orange fw-semibold fs-7 w-200px py-3 shadow-lg border-0 anim-dropdown">
                        <li class="menu-item px-3">
                            <a class="menu-link px-3" @click="setStatusFilter('')">Semua Status</a>
                        </li>
                        <li class="menu-item px-3">
                            <a class="menu-link px-3" @click="setStatusFilter('pending')">
                                <span class="bullet bullet-dot bg-warning me-2"></span> Pending
                            </a>
                        </li>
                        <li class="menu-item px-3">
                            <a class="menu-link px-3" @click="setStatusFilter('processing')">
                                <span class="bullet bullet-dot bg-primary me-2"></span> Proses
                            </a>
                        </li>
                        <li class="menu-item px-3">
                            <a class="menu-link px-3" @click="setStatusFilter('completed')">
                                <span class="bullet bullet-dot bg-success me-2"></span> Selesai
                            </a>
                        </li>
                        <li class="menu-item px-3">
                            <a class="menu-link px-3" @click="setStatusFilter('cancelled')">
                                <span class="bullet bullet-dot bg-danger me-2"></span> Batal
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" style="border-collapse: separate; border-spacing: 0 8px;">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-150px ps-4">Info Kamar</th>
                            <th class="min-w-150px">Layanan</th>
                            <th class="min-w-100px">Detail</th>
                            <th class="min-w-200px">Catatan</th>
                            <th class="min-w-100px">Status</th>
                            <th class="text-end min-w-70px pe-4">Aksi</th>
                        </tr>
                    </thead>
                    
                    <TransitionGroup name="list" tag="tbody" class="fw-semibold text-gray-600">
                        
                        <tr v-if="!isLoading && requests.length === 0" key="empty-state" class="static-row">
                            <td colspan="6" class="text-center py-10">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="symbol symbol-60px bg-light-warning rounded-circle mb-3">
                                        <i class="bi bi-clipboard-x fs-1 text-warning"></i>
                                    </div>
                                    <span class="fs-5 fw-bold text-gray-800">Tidak ada permintaan ditemukan</span>
                                </div>
                            </td>
                        </tr>

                        <tr 
                            v-for="req in requests" 
                            :key="req.id" 
                            class="hover-elevate-row cursor-pointer"
                            @click="openUpdateStatusModal(req)"
                        >
                            <td class="ps-4 rounded-start">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-3">
                                        <span class="symbol-label bg-light-orange text-orange fw-bold fs-5 shadow-sm">
                                            {{ req.room_number }}
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-800 fw-bold text-hover-orange fs-6 mb-1">
                                            Kamar {{ req.room_number }}
                                        </span>
                                        <span class="text-gray-400 fw-semibold fs-7">{{ req.guest_name }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="text-gray-800 fw-bold d-block fs-6">{{ req.service_name }}</span>
                                <span class="text-gray-400 fs-8">{{ req.category }}</span>
                            </td>

                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge badge-light border border-gray-200 fw-bold align-self-start">
                                        Qty: {{ req.quantity }}
                                    </span>
                                    <span class="text-gray-400 fs-9 mt-1" v-if="req.schedule_time">
                                        <i class="bi bi-clock me-1 text-gray-300"></i> {{ formatTime(req.schedule_time) }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span v-if="req.notes" class="text-gray-600 fst-italic fs-7 text-truncate-2" style="max-width: 250px; display: block;">
                                    "{{ req.notes }}"
                                </span>
                                <span v-else class="text-gray-300 fs-7">-</span>
                            </td>

                            <td>
                                <span :class="getStatusBadge(req.status)" class="badge fs-8 fw-bolder px-3 py-2 text-uppercase rounded-pill">
                                    {{ req.status }}
                                </span>
                            </td>

                            <td class="text-end pe-4 rounded-end">
                                <button 
                                    class="btn btn-icon btn-light-orange btn-sm action-btn transition-all"
                                    title="Ubah Status"
                                >
                                    <i class="bi bi-pencil-fill fs-5"></i>
                                </button>
                            </td>
                        </tr>
                    </TransitionGroup>
                </table>
                
                <div v-if="isLoading" class="position-absolute top-50 start-50 translate-middle">
                    <div class="spinner-border text-orange" role="status"></div>
                </div>

            </div>

            <div class="row pt-5" v-if="pagination.total > 0">
                <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
                    <div class="dataTables_info fw-semibold text-gray-600">
                        Total <b>{{ pagination.total }}</b> Permintaan
                    </div>
                </div>
                <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                    <ul class="pagination">
                        <li class="page-item previous" :class="{ disabled: !pagination.prev_page_url }">
                            <a href="javascript:;" class="page-link" @click="fetchServiceRequests(pagination.current_page - 1)">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item active">
                            <a href="javascript:;" class="page-link bg-orange border-orange">{{ pagination.current_page }}</a>
                        </li>
                        <li class="page-item next" :class="{ disabled: !pagination.next_page_url }">
                            <a href="javascript:;" class="page-link" @click="fetchServiceRequests(pagination.current_page + 1)">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, computed } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

interface ServiceRequest {
  id: number;
  room_number: string;
  guest_name: string;
  service_name: string;
  category: string;
  quantity: number;
  status: string;
  notes: string | null;
  schedule_time: string | null;
}

interface PaginationData {
  current_page: number; last_page: number; total: number; from: number; to: number;
  prev_page_url: string | null; next_page_url: string | null;
}

const requests = ref<ServiceRequest[]>([]);
const isLoading = ref(true);
const debounceTimer = ref<any>(null);
const filters = reactive({ status: '', search: '' });
const pagination = ref<PaginationData>({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0, prev_page_url: null, next_page_url: null });

const countPending = computed(() => requests.value.filter(r => r.status === 'pending').length);

const fetchServiceRequests = async (page = 1) => {
  try {
    isLoading.value = true;
    const params = { page, status: filters.status, search: filters.search };
    const { data } = await axios.get("/admin/service-requests", { params }); 
    
    if (data.data) {
        requests.value = data.data;
        pagination.value = {
            current_page: data.current_page,
            last_page: data.last_page,
            total: data.total,
            from: data.from,
            to: data.to,
            prev_page_url: data.prev_page_url,
            next_page_url: data.next_page_url
        };
    } else {
        requests.value = data; 
    }
  } catch (error) {
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

const handleSearch = () => {
    if (debounceTimer.value) clearTimeout(debounceTimer.value);
    debounceTimer.value = setTimeout(() => { fetchServiceRequests(1); }, 500);
};

const setStatusFilter = (status: string) => {
    filters.status = status;
    fetchServiceRequests(1);
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = { 
        '': 'Semua Status',
        'pending': 'Pending', 
        'processing': 'Proses', 
        'completed': 'Selesai', 
        'cancelled': 'Batal' 
    };
    return labels[status] || 'Semua Status';
};

const openUpdateStatusModal = async (req: ServiceRequest) => {
    const { value: newStatus } = await Swal.fire({
        title: `Update Status`,
        html: `Permintaan <b>${req.service_name}</b> di Kamar ${req.room_number}`,
        input: 'select',
        inputOptions: {
            pending: 'Pending',
            processing: 'Processing',
            completed: 'Completed',
            cancelled: 'Cancelled'
        },
        inputValue: req.status,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        customClass: { confirmButton: 'btn btn-orange', cancelButton: 'btn btn-light' }
    });

    if (newStatus && newStatus !== req.status) {
        try {
            await axios.patch(`/admin/service-requests/${req.id}/status`, { status: newStatus });
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Status berhasil diperbarui.', timer: 1500, showConfirmButton: false });
            fetchServiceRequests(pagination.value.current_page);
        } catch (error) {
            Swal.fire('Gagal', 'Terjadi kesalahan saat memperbarui status.', 'error');
        }
    }
};

// HELPERS
const getStatusBadge = (status: string) => {
  const statusMap: any = {
    pending: "badge-light-warning text-warning",
    processing: "badge-light-primary text-primary",
    completed: "badge-light-success text-success",
    cancelled: "badge-light-danger text-danger",
  };
  return statusMap[status] || "badge-light-secondary";
};

const formatTime = (dateStr: string | null) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => { fetchServiceRequests(); });
</script>

<style scoped>
/* --- 1. THEME & COLORS --- */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff5f0 !important; }
.border-orange { border-color: #ff6b00 !important; }

/* Header Gradient Modern */
.bg-header-gradient {
    background: linear-gradient(135deg, #ff6b00 0%, #ff8534 100%);
    position: relative;
}

/* Custom Buttons */
.btn-orange { background-color: #ff6b00; color: #ffffff; border-color: #ff6b00; }
.btn-orange:hover { background-color: #e65c00 !important; color: #ffffff; }
.btn-light-orange { background-color: #fff5f0; color: #ff6b00; border: none; }
.btn-light-orange:hover { background-color: #ff6b00; color: #fff; }

/* Pagination */
.page-item.active .page-link { background-color: #ff6b00 !important; border-color: #ff6b00 !important; }
.page-link:hover { color: #ff6b00 !important; background-color: #fff5f0 !important; }

/* --- 2. LIST ANIMATIONS (FLUID EXIT/ENTER) --- */
/* Item Entering */
.list-enter-active {
  transition: all 0.4s ease-out;
}
.list-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

/* Item Leaving (Slide Left & Fade) */
.list-leave-active {
  transition: all 0.4s ease-in;
  position: absolute; /* Penting agar item lain langsung menggeser ke atas */
  width: 100%; /* Menjaga lebar tabel tetap konsisten saat absolute */
  z-index: 0;
}
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

/* Item Moving (Reordering) */
.list-move {
  transition: transform 0.4s ease;
}

/* --- 3. PAGE ENTRANCE ANIMATION --- */
.anim-page-in { animation: fadeIn 0.8s ease-out forwards; }
.anim-slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(-20px); }
.anim-slide-up { animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(20px); }
.anim-dropdown { animation: dropdownPop 0.2s cubic-bezier(0.34, 1.56, 0.64, 1); transform-origin: top center; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { to { opacity: 1; transform: translateY(0); } }
@keyframes slideUp { to { opacity: 1; transform: translateY(0); } }
@keyframes dropdownPop { from { opacity: 0; transform: scale(0.95) translateY(-10px); } to { opacity: 1; transform: scale(1) translateY(0); } }

/* --- 4. HOVER ROW EFFECT --- */
.hover-elevate-row {
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s;
    position: relative;
    background-color: transparent;
}
/* Membulatkan ujung baris */
.hover-elevate-row td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
.hover-elevate-row td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

.hover-elevate-row:hover {
    background-color: #ffffff;
    transform: scale(1.01); /* Efek zoom in halus */
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    z-index: 10;
    box-shadow: inset 4px 0 0 #ff6b00, 0 10px 30px rgba(0, 0, 0, 0.08);
}

/* --- 5. DARK MODE SUPPORT --- */
[data-bs-theme="dark"] .card-adaptive { background-color: #1e1e2d; }
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(255, 107, 0, 0.15) !important; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-800 { color: #e4e6ef !important; }
[data-bs-theme="dark"] .bg-light { background-color: #2b2b40 !important; border-color: #2b2b40 !important; }
[data-bs-theme="dark"] .hover-elevate-row:hover {
    background-color: #2b2b40;
    box-shadow: inset 4px 0 0 #ff6b00, 0 10px 30px rgba(0, 0, 0, 0.3);
}
[data-bs-theme="dark"] .menu-state-bg-light-orange .menu-link:hover { background-color: rgba(255, 107, 0, 0.15) !important; }
</style>