<template>
  <div class="d-flex flex-column gap-5">
    
    <div class="card border-0 shadow-sm bg-white rounded-4 animate-fade-in">
      
      <div class="card-header border-0 pt-6 pb-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        
        <div class="mb-4 mb-md-0">
          <div class="d-flex align-items-center mb-1">
            <span class="symbol symbol-40px me-3">
              <span class="symbol-label bg-light-orange text-orange rounded-circle">
                <i class="bi bi-clock-history fs-2"></i>
              </span>
            </span>
            <h2 class="fs-2 fw-bold text-dark m-0">Riwayat Transaksi</h2>
          </div>
          <div class="text-muted fs-7 fw-semibold ms-11">
            Pantau arus kas, status pembayaran, dan unduh laporan keuangan.
          </div>
        </div>

        <div class="d-flex align-items-center bg-gray-50 rounded-3 px-5 py-3 border border-dashed border-gray-300 hover-border-orange transition-all">
           <div class="d-flex flex-column text-end me-4">
              <span class="text-gray-500 fw-bold fs-8 text-uppercase ls-1">Total Pendapatan</span>
              <div class="d-flex align-items-center justify-content-end">
                 <span v-if="loading" class="spinner-border spinner-border-sm text-orange mt-1"></span>
                 <span v-else class="fs-2 fw-bolder text-dark">{{ formatCurrency(totalRevenue) }}</span>
              </div>
           </div>
           <span class="symbol symbol-40px">
              <span class="symbol-label bg-orange text-white rounded-3 shadow-sm">
                 <i class="bi bi-wallet2 fs-2 text-white"></i>
              </span>
           </span>
        </div>
      </div>

      <div class="card-body py-4">
        <div class="d-flex flex-wrap align-items-center gap-3">
           
           <div class="position-relative flex-grow-1 flex-md-grow-0" style="min-width: 280px;">
              <span class="position-absolute top-50 translate-middle-y ms-4 text-gray-400">
                 <i class="bi bi-search fs-5"></i>
              </span>
              <input 
                 type="text" 
                 v-model="filters.search" 
                 @input="handleSearch"
                 class="form-control form-control-solid custom-input ps-12"
                 placeholder="Cari Order ID, Tamu, atau Staff..."
              />
           </div>

           <div class="input-group w-auto flex-nowrap">
              <span class="input-group-text bg-light-subtle border-end-0 text-gray-500">
                  <i class="bi bi-calendar3"></i>
              </span>
              <input type="date" v-model="filters.startDate" @change="fetchData(1)" class="form-control form-control-solid custom-input rounded-start-0" style="max-width: 160px" title="Dari Tanggal"/>
              <span class="input-group-text bg-light-subtle border-start-0 border-end-0 text-gray-400 px-2">-</span>
              <input type="date" v-model="filters.endDate" @change="fetchData(1)" class="form-control form-control-solid custom-input rounded-start-0" style="max-width: 160px" title="Sampai Tanggal"/>
           </div>

           <div class="w-150px">
              <select v-model="filters.status" @change="fetchData(1)" class="form-select form-select-solid custom-input fw-bold text-gray-600">
                 <option value="">Semua Status</option>
                 <option value="paid">Lunas</option>
                 <option value="cancelled">Dibatalkan</option>
              </select>
           </div>

           <div class="ms-auto d-flex gap-2">
             
             <button 
                @click="resetFilters" 
                class="btn btn-icon btn-light-danger text-danger hover-scale" 
                title="Reset Filter"
             >
                <i class="bi bi-x-lg fs-4"></i>
             </button>

             <div class="dropdown">
                <button 
                  class="btn btn-primary d-flex align-items-center fw-bold px-4 dropdown-toggle" 
                  type="button" 
                  id="dropdownExport"
                  data-bs-toggle="dropdown" 
                  aria-expanded="false"
                  :disabled="exporting"
                >
                  <span v-if="exporting" class="spinner-border spinner-border-sm me-2"></span>
                  <i v-else class="bi bi-cloud-download-fill me-2"></i>
                  Export Laporan
                </button>
                
                <ul class="dropdown-menu dropdown-menu-end shadow-lg rounded-3 border-0 p-2" aria-labelledby="dropdownExport" style="min-width: 240px; z-index: 1055;">
                  <li><h6 class="dropdown-header text-uppercase fs-9 ls-1 text-gray-500 fw-bold mb-1">Pilih Format Unduhan</h6></li>
                  <li>
                    <a class="dropdown-item d-flex align-items-center px-3 py-2 rounded-2 cursor-pointer mb-1 hover-bg-light-success transition-all" @click="downloadReport('excel')">
                      <span class="symbol symbol-30px me-3">
                         <span class="symbol-label bg-light-success text-success">
                            <i class="bi bi-file-earmark-spreadsheet-fill fs-5"></i>
                         </span>
                      </span>
                      <div class="d-flex flex-column">
                        <span class="fw-bold text-dark fs-7">Excel Spreadsheet</span>
                        <span class="fs-9 text-muted">Format .xlsx lengkap</span>
                      </div>
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item d-flex align-items-center px-3 py-2 rounded-2 cursor-pointer hover-bg-light-danger transition-all" @click="downloadReport('pdf')">
                      <span class="symbol symbol-30px me-3">
                         <span class="symbol-label bg-light-danger text-danger">
                            <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                         </span>
                      </span>
                      <div class="d-flex flex-column">
                        <span class="fw-bold text-dark fs-7">PDF Document</span>
                        <span class="fs-9 text-muted">Format cetak A4</span>
                      </div>
                    </a>
                  </li>
                </ul>
             </div>

           </div>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-borderless align-middle" style="border-spacing: 0 12px; border-collapse: separate;">
        <thead class="d-none d-md-table-header-group animate-fade-in">
           <tr class="text-gray-400 fw-bold fs-8 text-uppercase ls-1 ms-2">
              <th class="ps-4">Detail Order</th>
              <th>Tamu & Kamar</th>
              <th>Metode Bayar</th>
              <th>Total Transaksi</th>
              <th>Status</th>
              <th>Waktu</th>
              <th class="text-end pe-4">Detail</th>
           </tr>
        </thead>
        <tbody>
           
           <tr v-if="loading">
              <td colspan="7" class="text-center py-10">
                 <div class="d-flex flex-column justify-content-center align-items-center">
                    <div class="spinner-border text-orange mb-3" role="status"></div>
                    <span class="text-gray-500 fs-6 fw-semibold">Sedang memuat data transaksi...</span>
                 </div>
              </td>
           </tr>

           <tr v-else-if="history.length === 0" class="animate-fade-up">
              <td colspan="7" class="text-center py-10 bg-white rounded-3 shadow-sm border border-dashed border-gray-300">
                 <div class="d-flex flex-column align-items-center">
                    <div class="symbol symbol-70px bg-light-warning mb-4 rounded-circle">
                       <i class="bi bi-inbox fs-2x text-warning"></i>
                    </div>
                    <span class="fs-4 fw-bold text-dark mb-1">Tidak ada transaksi ditemukan</span>
                    <span class="text-muted fs-7">Cobalah ubah filter tanggal atau kata kunci pencarian Anda.</span>
                 </div>
              </td>
           </tr>

           <tr 
              v-for="(order, index) in history" 
              :key="order.id" 
              class="shadow-sm bg-white rounded-3 hover-elevate animate-fade-up position-relative overflow-hidden" 
              :style="{ animationDelay: `${index * 0.05}s` }"
           >
              <td class="p-0" style="width: 4px;">
                 <div class="h-100 position-absolute top-0 start-0" style="width: 4px;" 
                      :class="order.status === 'paid' ? 'bg-success' : (order.status === 'cancelled' ? 'bg-danger' : 'bg-warning')">
                 </div>
              </td>

              <td class="ps-4 py-4">
                 <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3">
                       <span class="symbol-label bg-light-primary text-primary fw-bolder fs-5">#</span>
                    </div>
                    <div class="d-flex flex-column">
                       <span class="text-dark fw-bold fs-6 hover-text-orange cursor-pointer" @click="viewDetails(order)">
                          Order #{{ order.id }}
                       </span>
                       <span class="text-muted fs-9">ID: {{ order.midtrans_order_id ? 'Gateway' : 'Manual POS' }}</span>
                    </div>
                 </div>
              </td>

              <td class="py-4">
                 <div class="d-flex flex-column">
                    <span class="text-dark fw-bold fs-6 mb-1">
                       {{ order.guest?.name || order.user?.name || 'Guest Umum' }}
                    </span>
                    <div class="d-flex align-items-center text-gray-500 fs-7">
                       <i class="bi bi-door-open me-2 text-orange"></i>
                       <span class="fw-semibold">{{ order.room ? `Kamar ${order.room.room_number}` : 'Tanpa Kamar' }}</span>
                    </div>
                 </div>
              </td>

              <td class="py-4">
                 <div class="d-flex align-items-center">
                    <span class="badge py-2 px-3 d-flex align-items-center gap-2" 
                          :class="getPaymentBadgeColor(order.payment_method)">
                       <i :class="`bi ${getPaymentIcon(order.payment_method)} fs-7`"></i>
                       {{ formatPaymentMethod(order.payment_method, order.midtrans_order_id) }}
                    </span>
                 </div>
              </td>

              <td class="py-4">
                 <span class="text-dark fw-bolder fs-6 font-monospace">{{ formatCurrency(order.total_price) }}</span>
              </td>

              <td class="py-4">
                 <span :class="getStatusBadgeClass(order.status)">
                    {{ capitalize(order.status) }}
                 </span>
              </td>

              <td class="py-4">
                 <div class="d-flex flex-column">
                    <span class="text-gray-800 fw-bold fs-7">{{ formatDate(order.updated_at) }}</span>
                    <span class="text-muted fs-9">{{ formatTime(order.updated_at) }} WIB</span>
                 </div>
              </td>

              <td class="text-end pe-4 py-4">
                 <button 
                    @click="viewDetails(order)" 
                    class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm shadow-sm rounded-circle"
                    data-bs-toggle="tooltip" 
                    title="Lihat Detail Transaksi"
                 >
                    <i class="bi bi-arrow-right-short fs-2"></i>
                 </button>
              </td>
           </tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pt-2 animate-fade-in" v-if="pagination.total > 0">
       <div class="fs-7 fw-semibold text-gray-500 mb-2 mb-md-0">
          Menampilkan <span class="fw-bold text-dark">{{ pagination.from }}</span> sampai <span class="fw-bold text-dark">{{ pagination.to }}</span> dari <span class="fw-bold text-dark">{{ pagination.total }}</span> transaksi
       </div>
       
       <ul class="pagination pagination-circle pagination-outline">
          <li class="page-item previous" :class="{ disabled: !pagination.prev_page_url }">
             <button class="page-link shadow-sm border-0" @click="changePage(pagination.current_page - 1)">
                <i class="bi bi-chevron-left"></i>
             </button>
          </li>
          <li class="page-item active">
             <span class="page-link shadow-sm bg-orange border-0">{{ pagination.current_page }}</span>
          </li>
          <li class="page-item next" :class="{ disabled: !pagination.next_page_url }">
             <button class="page-link shadow-sm border-0" @click="changePage(pagination.current_page + 1)">
                <i class="bi bi-chevron-right"></i>
             </button>
          </li>
       </ul>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

// --- Interfaces ---
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

// --- State ---
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

// --- Logic Export ---
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
    link.setAttribute('download', `Laporan-Transaksi-${new Date().toISOString().split('T')[0]}.${ext}`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
    Toast.fire({ icon: 'success', title: `Berhasil mengunduh laporan ${type.toUpperCase()}` });
  } catch (error) {
    Swal.fire("Gagal", "Terjadi kesalahan saat mengunduh laporan.", "error");
  } finally {
    exporting.value = false;
  }
};

// --- Logic Data ---
const fetchData = async (page = 1) => {
  try {
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

const changePage = (page: number) => {
  if (page >= 1 && page <= pagination.value.last_page) fetchData(page);
};

// --- Formatters ---
const formatPaymentMethod = (method: string | null, midtransId: string | null) => {
   if (method) {
      if (method === 'cash') return 'Tunai';
      if (method === 'bank_transfer') return 'Transfer';
      if (method === 'credit_card') return 'K. Kredit';
      if (method === 'qris') return 'QRIS';
      return capitalize(method.replace('_', ' '));
   }
   if (midtransId) return 'Online';
   return 'Tunai';
};

const getPaymentIcon = (method: string | null) => {
   if (!method) return 'bi-cash-coin';
   if (method === 'cash') return 'bi-cash';
   if (method.includes('bank') || method.includes('transfer')) return 'bi-bank';
   if (method.includes('card')) return 'bi-credit-card';
   if (method.includes('qris')) return 'bi-qr-code-scan';
   return 'bi-wallet2';
};

const getPaymentBadgeColor = (method: string | null) => {
   if (!method || method === 'cash') return 'badge-light-success text-success';
   if (method.includes('bank')) return 'badge-light-primary text-primary';
   if (method.includes('card') || method.includes('qris')) return 'badge-light-info text-info';
   return 'badge-light-secondary text-gray-700';
};

const getStatusBadgeClass = (status: string) => {
  const base = "badge fw-bold px-3 py-2 rounded-pill ";
  if (status === 'paid') return base + 'badge-light-success text-success';
  if (status === 'cancelled') return base + 'badge-light-danger text-danger';
  return base + 'badge-light-warning text-warning';
};

const viewDetails = (order: Order) => {
  let itemsHtml = order.items.map(item => `
    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-dashed border-gray-300">
      <div class="d-flex flex-column">
          <span class="text-dark fw-bold">${item.menu?.name || 'Item'}</span>
          <span class="text-muted fs-7">Qty: ${item.quantity}</span>
      </div>
      <span class="text-dark fw-bold align-self-center">${formatCurrency(item.price * item.quantity)}</span>
    </div>
  `).join('');

  const paymentInfo = `
     <div class="alert bg-light-orange border border-dashed border-orange d-flex align-items-center p-3 mb-4 rounded-3">
        <i class="bi ${getPaymentIcon(order.payment_method)} fs-1 text-orange me-4"></i>
        <div class="d-flex flex-column">
           <span class="text-gray-600 fs-8 text-uppercase fw-bold ls-1">Metode Pembayaran</span>
           <span class="fw-bolder text-dark fs-6">${formatPaymentMethod(order.payment_method, order.midtrans_order_id)}</span>
        </div>
     </div>
  `;

  Swal.fire({
    html: `
      <div class="text-center mb-5 mt-2">
         <div class="symbol symbol-60px bg-white border border-gray-300 shadow-sm rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
            <i class="bi bi-receipt-cutoff fs-2 text-dark"></i>
         </div>
         <h3 class="fw-bold text-dark mb-1">Struk Transaksi</h3>
         <div class="text-muted fs-7 fw-semibold">Order ID #${order.id}</div>
      </div>
      
      ${paymentInfo}

      <div class="bg-gray-100 rounded-3 p-4 mb-4 text-start border border-dashed border-gray-300">
         <div class="d-flex justify-content-between mb-2">
            <span class="text-gray-600 fw-semibold">Pelanggan:</span>
            <span class="fw-bold text-dark">${order.guest?.name || order.user?.name || '-'}</span>
         </div>
         <div class="d-flex justify-content-between">
            <span class="text-gray-600 fw-semibold">Kamar:</span>
            <span class="fw-bold text-dark">${order.room?.room_number || '-'}</span>
         </div>
         <div class="d-flex justify-content-between mt-2">
            <span class="text-gray-600 fw-semibold">Waktu:</span>
            <span class="fw-bold text-dark">${formatDate(order.updated_at)}</span>
         </div>
      </div>

      <div class="text-start mb-4 px-1" style="max-height: 200px; overflow-y: auto;">
         ${itemsHtml}
      </div>

      <div class="d-flex justify-content-between align-items-center pt-3 border-top border-2">
         <span class="fs-5 fw-bold text-gray-600">Total Bayar</span>
         <span class="fs-2 fw-bolder text-orange">${formatCurrency(order.total_price)}</span>
      </div>
    `,
    showConfirmButton: false, showCloseButton: true, customClass: { popup: 'rounded-4 shadow-lg' }
  });
};

const formatCurrency = (val: number) => new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val);
const formatDate = (date: string) => new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const formatTime = (date: string) => new Date(date).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
const capitalize = (s: string) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

onMounted(() => { fetchData(); });
</script>

<style scoped>
/* --- THEME SYSTEM --- */
.text-orange { color: #ff6b00 !important; }
.bg-orange { background-color: #ff6b00 !important; }
.bg-light-orange { background-color: #fff0e6 !important; }
.btn-primary { background-color: #ff6b00; border-color: #ff6b00; color: #fff; }
.btn-primary:hover, .btn-primary:active { background-color: #e65c00 !important; border-color: #e65c00 !important; }
.btn-light-orange { background-color: #fff0e6; color: #ff6b00; }
.btn-light-orange:hover { background-color: #ff6b00; color: #fff; }

/* --- ANIMATIONS --- */
@keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.animate-fade-up { opacity: 0; animation: fadeInUp 0.4s ease-out forwards; }
.animate-fade-in { animation: fadeIn 0.5s ease-out; }

/* --- CARD & TABLE INTERACTIONS --- */
.hover-elevate { transition: all 0.2s ease; border: 1px solid transparent; }
.hover-elevate:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important; border-color: #f1f1f1; z-index: 2; }

.hover-border-orange:hover { border-color: #ff6b00 !important; background-color: #fff !important; }
.hover-scale:hover { transform: scale(1.1); transition: transform 0.2s; }
.hover-text-orange:hover { color: #ff6b00 !important; transition: color 0.2s; }

.transition-all { transition: all 0.2s ease-in-out; }

/* --- INPUTS --- */
.custom-input { 
  background-color: #f9f9f9; 
  border: 1px solid #eef0f3; 
  border-radius: 8px; 
  font-size: 0.9rem; 
  font-weight: 500;
  min-height: 42px;
}
.custom-input:focus { 
  background-color: #fff; 
  border-color: #ff6b00; 
  box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.1); 
}

/* --- PAGINATION --- */
.page-link { 
  color: #7e8299; 
  border-radius: 8px; 
  margin: 0 4px; 
  width: 36px; height: 36px; 
  display: flex; align-items: center; justify-content: center;
  font-weight: 600;
}
.page-link:hover { color: #ff6b00; background-color: #fff0e6; }
.page-item.active .page-link { background-color: #ff6b00; color: #fff; box-shadow: 0 4px 10px rgba(255, 107, 0, 0.3); }

/* --- DROPDOWN --- */
.dropdown-menu { z-index: 9999 !important; border: 1px solid rgba(0,0,0,0.05); }
.hover-bg-light-success:hover { background-color: #f1faff !important; }
.hover-bg-light-danger:hover { background-color: #fff5f8 !important; }

</style>