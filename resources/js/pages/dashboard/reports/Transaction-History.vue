<template>
  <div class="d-flex flex-column gap-5 anim-page-in">
    
    <div class="card card-flush shadow-sm rounded-4 border-0 card-adaptive anim-slide-down">
      <div class="card-header pt-7">
        <div class="card-title d-flex flex-column">
           <div class="d-flex align-items-center">
              <span class="symbol symbol-50px me-3">
                 <span class="symbol-label bg-light-orange">
                    <i class="bi bi-clock-history fs-1 text-orange"></i>
                 </span>
              </span>
              <h1 class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">Riwayat Transaksi</h1>
           </div>
           <div class="text-gray-500 fs-6 fw-semibold ms-14 mt-1">
              Pantau arus kas dan status pembayaran tamu.
           </div>
        </div>

        <div class="card-toolbar">
           <div class="d-flex align-items-center bg-light-success rounded p-4 mb-2 border border-success border-dashed border-opacity-25">
              <i class="bi bi-wallet2 fs-1 text-success me-4"></i>
              <div class="d-flex flex-column">
                 <span class="fw-bold text-gray-600">Total Pendapatan</span>
                 <span class="fs-2 fw-bolder text-gray-900" v-if="!loading">
                    {{ formatCurrency(totalRevenue) }}
                 </span>
                 <span class="spinner-border spinner-border-sm text-success" v-else></span>
              </div>
           </div>
        </div>
      </div>
      <div class="card-body"></div> 
    </div>

    <div class="card card-flush shadow-sm rounded-4 border-0 card-adaptive">
        
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="bi bi-search fs-3 position-absolute ms-4 text-gray-500"></i>
                    <input 
                        type="text" 
                        v-model="filters.search" 
                        @input="handleSearch"
                        class="form-control form-control-solid w-250px ps-12 rounded-pill" 
                        placeholder="Cari Order ID / Tamu" 
                    />
                </div>
            </div>

            <div class="card-toolbar flex-row-fluid justify-content-end gap-3">
                
                <div class="d-flex align-items-center gap-2">
                    <div class="position-relative">
                        <input type="date" v-model="filters.startDate" @change="fetchData(1)" class="form-control form-control-solid form-control-sm cursor-pointer" style="width: 130px;" />
                    </div>
                    <span class="text-gray-400 fw-bold">-</span>
                    <div class="position-relative">
                        <input type="date" v-model="filters.endDate" @change="fetchData(1)" class="form-control form-control-solid form-control-sm cursor-pointer" style="width: 130px;" />
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-light-orange fw-bold btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-filter fs-5 me-2"></i> 
                        {{ getStatusLabel(filters.status) }}
                    </button>
                    <ul class="dropdown-menu menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-orange fw-semibold fs-7 w-200px py-4 shadow-lg border-0">
                        <li class="menu-item px-3">
                            <a href="javascript:;" class="menu-link px-3" @click="setStatusFilter('')">
                                Semua Status
                            </a>
                        </li>
                        <li class="menu-item px-3">
                            <a href="javascript:;" class="menu-link px-3" @click="setStatusFilter('paid')">
                                <span class="d-flex align-items-center"><i class="bi bi-check-circle me-2 text-success"></i> Lunas</span>
                            </a>
                        </li>
                        <li class="menu-item px-3">
                            <a href="javascript:;" class="menu-link px-3" @click="setStatusFilter('cancelled')">
                                <span class="d-flex align-items-center"><i class="bi bi-x-circle me-2 text-danger"></i> Dibatalkan</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="dropdown">
                    <button class="btn btn-orange fw-bold btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" :disabled="exporting">
                        <span v-if="exporting" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-cloud-arrow-down fs-5 me-2 text-white"></i> Export
                    </button>
                    <ul class="dropdown-menu menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-orange fw-semibold fs-7 w-200px py-4 shadow-lg border-0">
                        <li class="menu-item px-3">
                            <div class="menu-content fs-6 text-dark fw-bold px-3 py-2">Pilih Format</div>
                        </li>
                        <li class="menu-item px-3">
                            <a href="javascript:;" class="menu-link px-3" @click="downloadReport('excel')">
                                <i class="bi bi-file-earmark-spreadsheet fs-4 me-2 text-success"></i> Excel (.xlsx)
                            </a>
                        </li>
                        <li class="menu-item px-3">
                            <a href="javascript:;" class="menu-link px-3" @click="downloadReport('pdf')">
                                <i class="bi bi-file-earmark-pdf fs-4 me-2 text-danger"></i> PDF Document
                            </a>
                        </li>
                    </ul>
                </div>

                <button @click="resetFilters" class="btn btn-icon btn-sm btn-light-danger" title="Reset Filter">
                    <i class="bi bi-x-lg fs-4"></i>
                </button>

            </div>
        </div>

        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" style="border-collapse: separate; border-spacing: 0 12px;">
                    <thead>
                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                            <th class="min-w-150px ps-4">Order Detail</th>
                            <th class="min-w-150px">Tamu & Kamar</th>
                            <th class="min-w-125px">Pembayaran</th>
                            <th class="min-w-100px">Total</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-125px">Waktu</th>
                            <th class="text-end min-w-70px pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                        
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-10">
                                <span class="spinner-border text-orange"></span>
                            </td>
                        </tr>

                        <tr v-else-if="history.length === 0">
                            <td colspan="7" class="text-center py-10">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="symbol symbol-60px bg-light-warning rounded-circle mb-3">
                                        <div class="symbol-label">
                                            <i class="bi bi-inbox fs-1 text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="fs-4 fw-bold text-gray-800">Tidak ada data</div>
                                    <div class="text-gray-400">Coba ubah filter pencarian Anda.</div>
                                </div>
                            </td>
                        </tr>

                        <TransitionGroup name="table-fade">
                            <tr v-for="order in history" :key="order.id" class="hover-elevate-row cursor-pointer" @click="viewDetails(order)">
                                <td class="ps-4 rounded-start">
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-45px me-3">
                                            <span class="symbol-label bg-light-orange text-orange fw-bold">#</span>
                                        </div>
                                        <div class="d-flex justify-content-start flex-column">
                                            <span class="text-gray-800 fw-bold text-hover-orange mb-1 fs-6 order-id-text">
                                                Order #{{ order.id }}
                                            </span>
                                            <span class="text-gray-400 fw-semibold d-block fs-7">
                                                {{ order.midtrans_order_id ? 'Gateway' : 'Manual POS' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="text-gray-800 fw-bold d-block fs-6">
                                        {{ order.guest?.name || order.user?.name || 'Tamu Umum' }}
                                    </span>
                                    <span class="text-gray-400 fw-semibold d-block fs-7">
                                        <i class="bi bi-door-open me-1 text-orange"></i> 
                                        {{ order.room ? `Kamar ${order.room.room_number}` : 'Non-Kamar' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge badge-light fw-bold fs-7 px-3 py-2 border border-gray-200">
                                        {{ formatPaymentMethod(order.payment_method, order.midtrans_order_id) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="text-gray-800 fw-bolder d-block fs-6">
                                        {{ formatCurrency(order.total_price) }}
                                    </span>
                                </td>

                                <td>
                                    <span :class="getStatusBadgeClass(order.status)" class="badge fs-7 fw-bold px-3 py-2">
                                        {{ capitalize(order.status) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="text-gray-800 fw-bold d-block fs-7">{{ formatDate(order.updated_at) }}</span>
                                    <span class="text-gray-400 fw-semibold d-block fs-8">{{ formatTime(order.updated_at) }}</span>
                                </td>

                                <td class="text-end pe-4 rounded-end">
                                    <button 
                                        @click.stop="viewDetails(order)" 
                                        class="btn btn-icon btn-bg-light btn-active-color-orange btn-sm action-btn transition-all"
                                        title="Lihat Detail"
                                    >
                                        <i class="bi bi-eye fs-3"></i>
                                    </button>
                                </td>
                            </tr>
                        </TransitionGroup>
                    </tbody>
                </table>
            </div>

            <div class="row pt-5" v-if="pagination.total > 0">
                <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
                    <div class="dataTables_info fw-semibold text-gray-600">
                        Menampilkan {{ pagination.from }} - {{ pagination.to }} dari {{ pagination.total }} data
                    </div>
                </div>
                <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                    <ul class="pagination">
                        <li class="page-item previous" :class="{ disabled: !pagination.prev_page_url }">
                            <a href="javascript:;" class="page-link" @click="changePage(pagination.current_page - 1)">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item active">
                            <a href="javascript:;" class="page-link bg-orange border-orange">{{ pagination.current_page }}</a>
                        </li>
                        <li class="page-item next" :class="{ disabled: !pagination.next_page_url }">
                            <a href="javascript:;" class="page-link" @click="changePage(pagination.current_page + 1)">
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
import { ref, onMounted, reactive } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

interface Order {
  id: number;
  midtrans_order_id: string | null;
  payment_method: string | null;
  total_price: number;
  status: string;
  updated_at: string;
  room?: { room_number: string };
  user?: { name: string };
  guest?: { name: string };
  items: { id: number; price: number; quantity: number; menu: { name: string } }[];
}

interface PaginationData {
  current_page: number; last_page: number; total: number; from: number; to: number;
  prev_page_url: string | null; next_page_url: string | null;
}

const history = ref<Order[]>([]);
const totalRevenue = ref(0);
const loading = ref(true);
const exporting = ref(false); 
const debounceTimer = ref<any>(null);

const filters = reactive({
  search: '', status: '', startDate: '', endDate: ''
});

const pagination = ref<PaginationData>({
  current_page: 1, last_page: 1, total: 0, from: 0, to: 0, prev_page_url: null, next_page_url: null
});

const fetchData = async (page = 1) => {
  try {
    loading.value = true;
    const params = { page, search: filters.search, status: filters.status, start_date: filters.startDate, end_date: filters.endDate };
    const response = await axios.get("/transaction-history", { params });
    if (response.data.orders) {
        history.value = response.data.orders.data || response.data.orders; 
        totalRevenue.value = response.data.total_revenue;
        if(response.data.orders.current_page) {
             pagination.value = {
                current_page: response.data.orders.current_page,
                last_page: response.data.orders.last_page,
                total: response.data.orders.total,
                from: response.data.orders.from,
                to: response.data.orders.to,
                prev_page_url: response.data.orders.prev_page_url,
                next_page_url: response.data.orders.next_page_url
            };
        }
    } else {
        history.value = response.data; 
    }
  } catch (error) { console.error(error); } 
  finally { loading.value = false; }
};

const handleSearch = () => {
  if (debounceTimer.value) clearTimeout(debounceTimer.value);
  debounceTimer.value = setTimeout(() => { fetchData(1); }, 500);
};

const resetFilters = () => {
  filters.search = ''; filters.status = ''; filters.startDate = ''; filters.endDate = ''; fetchData(1);
};

const setStatusFilter = (status: string) => {
    filters.status = status;
    fetchData(1);
};

const getStatusLabel = (status: string) => {
    if (status === 'paid') return 'Lunas';
    if (status === 'cancelled') return 'Dibatalkan';
    return 'Semua Status';
};

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.value.last_page) fetchData(page);
};

const downloadReport = async (type: 'excel' | 'pdf') => {
  try {
    exporting.value = true;
    const response = await axios.get('/transaction-history/export', {
      params: { type, search: filters.search, status: filters.status, start_date: filters.startDate, end_date: filters.endDate },
      responseType: 'blob'
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    const ext = type === 'excel' ? 'xlsx' : 'pdf';
    link.setAttribute('download', `Report-${new Date().toISOString().split('T')[0]}.${ext}`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) { Swal.fire("Error", "Gagal download report", "error"); } finally { exporting.value = false; }
};

const formatPaymentMethod = (method: string | null, midtransId: string | null) => {
   if (method) return method.replace('_', ' ').toUpperCase();
   if (midtransId) return 'ONLINE';
   return 'TUNAI';
};

const getStatusBadgeClass = (status: string) => {
  if (status === 'paid') return 'badge-light-success text-success';
  if (status === 'cancelled') return 'badge-light-danger text-danger';
  return 'badge-light-warning text-warning';
};

const formatCurrency = (val: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const formatTime = (date: string) => new Date(date).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
const capitalize = (s: string) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

const viewDetails = (order: Order) => {
  Swal.fire({
    title: `Order #${order.id}`,
    html: `
        <div class="text-start">
            <p><strong>Tamu:</strong> ${order.guest?.name || order.user?.name || '-'}</p>
            <p><strong>Total:</strong> ${formatCurrency(order.total_price)}</p>
            <hr>
            ${order.items.map(i => `<div class="d-flex justify-content-between"><span>${i.menu?.name} x${i.quantity}</span><span>${formatCurrency(i.price*i.quantity)}</span></div>`).join('')}
        </div>
    `,
    icon: 'info',
    confirmButtonText: 'Tutup',
    customClass: { confirmButton: 'btn btn-orange' }
  });
};

onMounted(() => { fetchData(); });
</script>

<style scoped>
/* --- 1. LUXURY ORANGE THEME --- */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.border-orange { border-color: #ff6b00 !important; }

/* Custom Buttons */
.btn-orange { background-color: #ff6b00; color: #ffffff; border-color: #ff6b00; }
.btn-orange:hover, .btn-orange:active { background-color: #e65c00 !important; border-color: #e65c00 !important; color: #ffffff !important; }

.btn-light-orange { background-color: #fff5f0; color: #ff6b00; }
.btn-light-orange:hover, .btn-light-orange.show { background-color: #ff6b00 !important; color: #ffffff !important; }
.btn-light-orange .bi { color: #ff6b00; }
.btn-light-orange:hover .bi, .btn-light-orange.show .bi { color: #ffffff; }

/* Backgrounds */
.bg-light-orange { background-color: #fff5f0 !important; }

/* Text Hovers */
.text-hover-orange:hover { color: #ff6b00 !important; transition: color 0.2s; }

/* Icon Button Active */
.btn-active-color-orange:hover i { color: #ff6b00 !important; }
.btn-active-color-orange:hover { background-color: #fff5f0 !important; }

/* Pagination */
.page-item.active .page-link { background-color: #ff6b00 !important; border-color: #ff6b00 !important; }
.page-link:hover { color: #ff6b00 !important; background-color: #fff5f0 !important; }

/* --- 2. FLOATING ROW ANIMATION (HOVER EFFECT) --- */
.hover-elevate-row {
    transition: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    border-radius: 12px; /* Membuat baris rounded saat hover */
}

.hover-elevate-row td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
.hover-elevate-row td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

.hover-elevate-row:hover {
    background-color: #ffffff; /* Warna terang saat hover */
    transform: translateY(-4px); /* Efek melayang ke atas */
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Bayangan lembut */
    z-index: 10;
    border-bottom-color: transparent !important; /* Hilangkan garis pemisah saat melayang */
}

/* Saat hover baris, tombol aksi otomatis berubah warna */
.hover-elevate-row:hover .action-btn {
    background-color: #ff6b00 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(255, 107, 0, 0.3);
}
.hover-elevate-row:hover .action-btn i { color: #ffffff !important; }

/* Saat hover baris, Order ID berubah orange */
.hover-elevate-row:hover .order-id-text { color: #ff6b00 !important; }

/* --- 3. DARK MODE ADJUSTMENTS --- */
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(255, 107, 0, 0.15) !important; }
[data-bs-theme="dark"] .btn-light-orange { background-color: rgba(255, 107, 0, 0.15); color: #ff6b00; }
[data-bs-theme="dark"] .btn-light-orange:hover { background-color: #ff6b00 !important; color: #fff; }
[data-bs-theme="dark"] .card-adaptive { background-color: #1e1e2d; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .hover-elevate-row:hover {
    background-color: #2b2b40; /* Warna highlight dark mode */
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

/* --- 4. PAGE ENTRANCE ANIMATIONS --- */
.anim-page-in { animation: fadeIn 0.8s ease-out forwards; }
.anim-slide-down { animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(-20px); }

.table-fade-enter-active, .table-fade-leave-active { transition: opacity 0.3s ease; }
.table-fade-enter-from, .table-fade-leave-to { opacity: 0; }

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideDown { to { opacity: 1; transform: translateY(0); } }
</style>