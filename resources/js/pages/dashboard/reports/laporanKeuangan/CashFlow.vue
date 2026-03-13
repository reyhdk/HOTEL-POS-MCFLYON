<template>
  <div class="d-flex flex-column gap-5">
    
    <!-- HEADER CARDS - Summary Cash Flow -->
    <div class="row g-5 g-xl-8 mb-5 animate__animated animate__fadeInDown">
      
      <!-- Card: Total Pemasukan -->
      <div class="col-md-4 col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-light-success">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px me-5">
                <span class="symbol-label bg-success bg-opacity-10">
                  <i class="ki-duotone ki-arrow-down fs-2x text-success">
                    <span class="path1"></span>
                    <span class="path2"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2 fw-bold text-gray-900 lh-1 mb-1">{{ formatCurrency(summary.totalIncome) }}</span>
                <span class="text-success fw-semibold fs-6">Total Pemasukan</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Total Pengeluaran -->
      <div class="col-md-4 col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-light-danger">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px me-5">
                <span class="symbol-label bg-danger bg-opacity-10">
                  <i class="ki-duotone ki-arrow-up fs-2x text-danger">
                    <span class="path1"></span>
                    <span class="path2"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2 fw-bold text-gray-900 lh-1 mb-1">{{ formatCurrency(summary.totalExpense) }}</span>
                <span class="text-danger fw-semibold fs-6">Total Pengeluaran</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Saldo Bersih (Premium Orange Theme) -->
      <div class="col-md-4 col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-orange-gradient">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px me-5">
                <span class="symbol-label bg-white bg-opacity-25">
                  <i class="ki-duotone ki-wallet fs-2x text-white">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2 fw-bold text-white lh-1 mb-1">{{ formatCurrency(summary.netBalance) }}</span>
                <span class="text-white text-opacity-75 fw-semibold fs-6">Saldo Bersih (Net)</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- MAIN TABLE CARD -->
    <div class="card card-flush animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
      
      <!-- Card Header with Filters -->
      <div class="card-header border-0 pt-6 pb-4 d-flex flex-column gap-5">
        
        <!-- Top Row: Title & Action Buttons -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center w-100 gap-3">
          <div class="card-title flex-column align-items-start m-0">
            <h3 class="fw-bold text-gray-800 mb-1">Riwayat Transaksi</h3>
            <span class="text-gray-500 fs-7">Arus kas dari seluruh departemen hotel</span>
          </div>

          <div class="d-flex align-items-center gap-2">
            <!-- Refresh Button -->
            <button 
              @click="refreshData"
              class="btn btn-sm btn-icon btn-light-primary border border-primary border-dashed"
              data-bs-toggle="tooltip"
              title="Refresh Data"
              :disabled="loading"
            >
              <i class="ki-duotone ki-arrows-circle fs-2" :class="{'spin-animation': loading}">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
            </button>

            <!-- EXPORT BUTTON (Membuka Modal) -->
            <button @click="handleOpenExport" class="btn btn-sm btn-light-success fw-bold d-flex align-items-center gap-2">
              <i class="ki-duotone ki-file-down fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
              Export Laporan
            </button>

            <!-- Button Tambah Transaksi Manual -->
            <button @click="handleAddManual" class="btn btn-sm btn-orange fw-bold">
              <i class="ki-duotone ki-plus fs-2"></i> Catat Manual
            </button>
          </div>
        </div>

        <!-- Bottom Row: Filter Controls Wrapper -->
        <div class="d-flex flex-wrap align-items-center gap-3 bg-light p-4 rounded border border-dashed border-gray-300 w-100">
          
          <!-- Filter Icon/Label -->
          <div class="fw-semibold text-gray-600 me-2 d-none d-md-flex align-items-center">
            <i class="ki-duotone ki-filter fs-2 text-gray-500 me-2">
              <span class="path1"></span><span class="path2"></span>
            </i>
            Filter:
          </div>

          <!-- Search Bar -->
          <el-input 
            v-model="searchQuery" 
            placeholder="Cari deskripsi / referensi..." 
            class="w-100 w-md-200px metronic-input-orange"
            clearable
            @input="handleSearch"
            @clear="handleFilterChange"
          >
            <template #prefix>
              <i class="ki-duotone ki-magnifier fs-3 text-gray-500">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
            </template>
          </el-input>

          <!-- Date Range Picker -->
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="-"
            start-placeholder="Tgl Mulai"
            end-placeholder="Tgl Akhir"
            format="DD MMM YYYY"
            value-format="YYYY-MM-DD"
            class="metronic-datepicker-orange w-100 w-md-auto"
            @change="handleFilterChange"
          />

          <!-- Filter Tipe -->
          <el-select 
            v-model="filterType" 
            placeholder="Semua Tipe" 
            class="w-100 w-md-150px metronic-select-orange"
            @change="handleFilterChange"
            clearable
          >
            <el-option label="Semua Tipe" value="" />
            <el-option label="Pemasukan" value="income" />
            <el-option label="Pengeluaran" value="expense" />
          </el-select>

          <!-- Filter Kategori -->
          <el-select 
            v-model="filterCategory" 
            placeholder="Semua Kategori" 
            class="w-100 w-md-150px metronic-select-orange"
            @change="handleFilterChange"
            clearable
          >
            <el-option label="Semua Kategori" value="" />
            <el-option label="Booking Kamar" value="booking" />
            <el-option label="Restoran (POS)" value="resto" />
            <el-option label="Laundry" value="laundry" />
            <el-option label="Stok Gudang" value="warehouse" />
            <el-option label="Operasional Lain" value="other" />
          </el-select>

          <!-- Reset Button -->
          <button 
            v-if="dateRange || filterType || filterCategory || searchQuery"
            @click="resetFilters"
            class="btn btn-sm btn-light-danger fw-bold ms-auto"
            data-bs-toggle="tooltip"
            title="Reset Filter"
          >
            <i class="ki-duotone ki-cross fs-2">
              <span class="path1"></span>
              <span class="path2"></span>
            </i>
            Reset
          </button>
        </div>
      </div>

      <!-- Card Body -->
      <div class="card-body pt-0 mt-4">
        
        <!-- Data Table Wrapper -->
        <div class="table-responsive position-relative min-h-200px">
          
          <!-- Elegant Loading Spinner Overlay -->
          <div v-if="loading" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center overlay-loading">
            <div class="d-flex flex-column align-items-center bg-white p-6 rounded shadow-sm">
              <div class="spinner-border text-orange mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                <span class="visually-hidden">Loading...</span>
              </div>
              <span class="text-gray-700 fw-bold fs-6">Memuat Data Transaksi...</span>
            </div>
          </div>

          <!-- Table -->
          <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_cashflow_table">
            <thead>
              <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0 border-bottom-2">
                <th class="min-w-150px">Tanggal</th>
                <th class="min-w-250px">Deskripsi & Kategori</th>
                <th class="min-w-125px">Metode</th>
                <th class="text-end min-w-125px">Uang Masuk</th>
                <th class="text-end min-w-125px">Uang Keluar</th>
                <th class="text-end min-w-75px">Aksi</th>
              </tr>
            </thead>
            <tbody class="fw-semibold text-gray-600">
              
              <!-- Empty State -->
              <tr v-if="transactions.length === 0 && !loading">
                <td colspan="6" class="text-center py-20">
                  <div class="d-flex flex-column align-items-center">
                    <div class="symbol symbol-100px symbol-circle mb-7">
                      <span class="symbol-label bg-light-orange">
                        <i class="ki-duotone ki-abstract-26 fs-3x text-orange">
                          <span class="path1"></span>
                          <span class="path2"></span>
                        </i>
                      </span>
                    </div>
                    <h3 class="fw-bold text-gray-800 mb-3">Tidak Ada Transaksi</h3>
                    <span class="text-gray-500 fs-6">Belum ada pergerakan kas pada periode dan filter ini.</span>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <tr v-for="item in transactions" :key="item.id" class="hover-table-row">
                
                <!-- Tanggal -->
                <td>
                  <div class="d-flex flex-column">
                    <span class="text-gray-800 fw-bold">{{ formatDate(item.transaction_date) }}</span>
                    <span class="text-gray-500 fs-8">{{ formatTime(item.transaction_date) }}</span>
                  </div>
                </td>

                <!-- Deskripsi -->
                <td>
                  <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3">
                      <span :class="`symbol-label bg-light-${getCategoryColor(item.category)}`">
                        <i :class="`ki-duotone ${getCategoryIcon(item.category)} fs-2 text-${getCategoryColor(item.category)}`">
                          <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                      </span>
                    </div>
                    <div class="d-flex flex-column">
                      <span class="text-gray-800 fw-bold mb-1 text-truncate" style="max-width: 250px;" :title="item.description">
                        {{ item.description }}
                      </span>
                      <span :class="`badge badge-light-${getCategoryColor(item.category)} w-fit-content`">
                        {{ formatCategoryText(item.category) }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Metode Pembayaran -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-gray-700 fs-6">
                      ({{ (item.payment_method || '').toUpperCase() }})
                    </span>
                  </div>
                  <span class="text-muted fs-8 d-block mt-1" v-if="item.reference_id">{{ item.reference_id }}</span>
                </td>

                <!-- Masuk -->
                <td class="text-end text-success fw-bolder fs-5">
                  <span v-if="item.type === 'income'">+ {{ formatCurrency(item.amount) }}</span>
                  <span v-else class="text-gray-300">-</span>
                </td>

                <!-- Keluar -->
                <td class="text-end text-danger fw-bolder fs-5">
                  <span v-if="item.type === 'expense'">- {{ formatCurrency(item.amount) }}</span>
                  <span v-else class="text-gray-300">-</span>
                </td>

                <!-- Aksi -->
                <td class="text-end">
                  <button 
                    @click="openDetailModal(item)" 
                    class="btn btn-icon btn-light btn-active-light-primary btn-sm"
                    data-bs-toggle="tooltip" 
                    title="Lihat Detail"
                  >
                    <i class="ki-duotone ki-eye fs-3">
                      <span class="path1"></span>
                      <span class="path2"></span>
                      <span class="path3"></span>
                    </i>
                  </button>
                </td>

              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > 0" class="d-flex flex-stack flex-wrap pt-10">
          <div class="fs-6 fw-semibold text-gray-700">
            Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }} hingga 
            {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
            dari {{ pagination.total }} data
          </div>
          <el-pagination
            background
            layout="prev, pager, next"
            :total="pagination.total"
            :page-size="pagination.per_page"
            :current-page="pagination.current_page"
            @current-change="handlePageChange"
            class="pagination-orange"
          />
        </div>
      </div>
    </div>

    <!-- Modal Form (Create Manual) -->
    <Form ref="formRef" @saved="fetchData(1)" />

    <!-- Modal Export Data -->
    <CashflowExport ref="exportModalRef" />

    <!-- Modal View Detail -->
    <div class="modal fade" id="kt_modal_view_detail" ref="detailModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content rounded shadow-lg border-0">
          
          <div class="modal-header border-0 pb-0 pt-6 px-7 justify-content-between align-items-center">
            <h2 class="fw-bolder m-0 text-gray-900 d-flex align-items-center">
              <i class="ki-duotone ki-document fs-1 text-primary me-2">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
              Detail Transaksi
            </h2>
            <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
              <i class="ki-duotone ki-cross fs-1">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
            </div>
          </div>

          <div class="modal-body scroll-y px-7 py-5" v-if="selectedTransaction">
            
            <!-- Summary Header di Dalam Modal -->
            <div class="d-flex align-items-center justify-content-between p-5 mb-5 rounded"
                 :class="selectedTransaction.type === 'income' ? 'bg-light-success' : 'bg-light-danger'">
              <div class="d-flex flex-column">
                <span class="fw-semibold fs-6" :class="selectedTransaction.type === 'income' ? 'text-success' : 'text-danger'">
                  {{ selectedTransaction.type === 'income' ? 'Uang Masuk (Income)' : 'Uang Keluar (Expense)' }}
                </span>
                <span class="fw-bolder fs-1" :class="selectedTransaction.type === 'income' ? 'text-success' : 'text-danger'">
                  {{ formatCurrency(selectedTransaction.amount) }}
                </span>
              </div>
              <div class="symbol symbol-50px">
                <span class="symbol-label" :class="selectedTransaction.type === 'income' ? 'bg-success' : 'bg-danger'">
                  <i class="ki-duotone fs-2x text-white" :class="selectedTransaction.type === 'income' ? 'ki-arrow-down' : 'ki-arrow-up'">
                    <span class="path1"></span>
                    <span class="path2"></span>
                  </i>
                </span>
              </div>
            </div>

            <!-- Detail Grid -->
            <div class="row g-5 mb-5">
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Tanggal Transaksi</div>
                <div class="fw-bold text-gray-800 fs-6">
                  {{ formatDate(selectedTransaction.transaction_date) }} - {{ formatTime(selectedTransaction.transaction_date) }}
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Kategori</div>
                <div class="d-flex align-items-center">
                  <span :class="`badge badge-light-${getCategoryColor(selectedTransaction.category)}`">
                    {{ formatCategoryText(selectedTransaction.category) }}
                  </span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Metode Pembayaran</div>
                <div class="fw-bold text-gray-800 fs-6">
                  {{ selectedTransaction.payment_method }}
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">No. Referensi / ID</div>
                <div class="fw-bold text-gray-800 fs-6">
                  {{ selectedTransaction.reference_id || '-' }}
                </div>
              </div>
            </div>

            <!-- Deskripsi -->
            <div class="bg-gray-100 p-4 rounded border border-gray-300 border-dashed">
              <div class="fw-semibold text-gray-500 mb-2">Deskripsi Keterangan:</div>
              <p class="m-0 text-gray-800 fw-medium text-break">
                {{ selectedTransaction.description }}
              </p>
            </div>

          </div>
          
          <div class="modal-footer border-0 pt-0 px-7 pb-6">
            <button type="button" class="btn btn-light w-100 fw-bold" data-bs-dismiss="modal">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import ApiService from "@/core/services/ApiService";
import Form from './Form.vue';
import CashflowExport from './CashflowExport.vue'; // <-- Import file export baru
import { Modal } from 'bootstrap'; 

// --- TYPES ---
interface Transaction {
  id: number;
  transaction_date: string;
  type: 'income' | 'expense';
  category: 'booking' | 'resto' | 'laundry' | 'warehouse' | 'other';
  description: string;
  payment_method: string;
  reference_id?: string;
  amount: number;
}

// --- STATE ---
const loading = ref(false);
const searchQuery = ref('');
const dateRange = ref<any>(null);
const filterType = ref('');
const filterCategory = ref('');
const formRef = ref<any>(null);
const exportModalRef = ref<any>(null); // Ref untuk Modal Export

// Modal Detail
const detailModalRef = ref<HTMLElement | null>(null);
const detailModalInstance = ref<any>(null);
const selectedTransaction = ref<Transaction | null>(null);

const pagination = reactive({
  current_page: 1,
  per_page: 10,
  total: 0
});

const summary = ref({
  totalIncome: 0,
  totalExpense: 0,
  netBalance: 0
});

const transactions = ref<Transaction[]>([]);

// --- API METHODS ---
const fetchData = async (page = 1) => {
  loading.value = true;
  try {
    const params: any = {
      page: page,
      per_page: pagination.per_page,
      type: filterType.value,
      category: filterCategory.value,
      search: searchQuery.value 
    };

    if (dateRange.value && dateRange.value.length === 2) {
      params.start_date = dateRange.value[0];
      params.end_date = dateRange.value[1];
    }

    const response = await ApiService.query("cash-flow", params);
    
    transactions.value = response.data.transactions.data;
    pagination.current_page = response.data.transactions.current_page;
    pagination.per_page = response.data.transactions.per_page;
    pagination.total = response.data.transactions.total;

    summary.value = response.data.summary;

  } catch (error) {
    console.error("Error fetching cash flow data:", error);
  } finally {
    setTimeout(() => {
      loading.value = false;
    }, 400);
  }
};

// --- HANDLERS ---
let searchTimeout: any = null;
const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchData(1);
  }, 500); 
};

const refreshData = () => {
  fetchData(pagination.current_page);
};

const handleFilterChange = () => {
  fetchData(1);
};

const resetFilters = () => {
  searchQuery.value = '';
  dateRange.value = null;
  filterType.value = '';
  filterCategory.value = '';
  fetchData(1);
};

const handlePageChange = (newPage: number) => {
  fetchData(newPage);
};

const handleAddManual = () => {
  if (formRef.value) {
    formRef.value.open();
  }
};

const openDetailModal = (item: Transaction) => {
  selectedTransaction.value = item;
  if (detailModalInstance.value) {
    detailModalInstance.value.show();
  }
};

// --- BUKA MODAL EXPORT ---
const handleOpenExport = () => {
  if (exportModalRef.value) {
    exportModalRef.value.open();
  }
};

// --- FORMATTERS ---
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  const options: Intl.DateTimeFormatOptions = { day: '2-digit', month: 'short', year: 'numeric' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

const formatTime = (dateString: string) => {
  if (!dateString) return '-';
  const options: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleTimeString('id-ID', options);
};

// UI Helpers
const getCategoryColor = (category: string) => {
  const colors: Record<string, string> = {
    booking: 'primary',
    resto: 'warning',
    laundry: 'info',
    warehouse: 'danger',
    other: 'dark'
  };
  return colors[category] || 'gray';
};

const getCategoryIcon = (category: string) => {
  const icons: Record<string, string> = {
    booking: 'ki-home',
    resto: 'ki-coffee',
    laundry: 'ki-discount',
    warehouse: 'ki-shop',
    other: 'ki-wallet'
  };
  return icons[category] || 'ki-document';
};

const formatCategoryText = (category: string) => {
  const texts: Record<string, string> = {
    booking: 'Booking Kamar',
    resto: 'Restoran',
    laundry: 'Laundry',
    warehouse: 'Pembelian Stok',
    other: 'Lain-lain'
  };
  return texts[category] || category;
};

// Lifecycle
onMounted(() => {
  fetchData();
  
  if (detailModalRef.value) {
    detailModalInstance.value = new Modal(detailModalRef.value);
  }
});
</script>

<style scoped>
/* ========================
   ORANGE THEME COLORS
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-orange { background-color: #F68B1E !important; }
.bg-light-orange { background-color: rgba(246, 139, 30, 0.1) !important; }

.bg-orange-gradient {
  background: linear-gradient(135deg, #F68B1E 0%, #d87614 100%) !important;
}

.btn-orange {
  background-color: #F68B1E;
  border-color: #F68B1E;
  color: #fff;
}

.btn-orange:hover {
  background-color: #e57b0e;
  border-color: #e57b0e;
  color: #fff;
}

/* ========================
   TABLE ENHANCEMENTS & OVERLAY
   ======================== */
.hover-table-row {
  transition: all 0.3s ease;
}

.hover-table-row:hover {
  background-color: rgba(246, 139, 30, 0.03);
}

.w-fit-content {
  width: fit-content;
}

.min-h-200px {
  min-height: 200px;
}

.overlay-loading {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(3px);
  z-index: 10;
  top: 0;
  left: 0;
}

.card-flush {
  box-shadow: 0px 0px 20px 0px rgba(76, 87, 125, 0.02);
}

.animate__animated {
  animation-duration: 0.6s;
}

/* ========================
   ANIMATION SPIN FOR REFRESH
   ======================== */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.spin-animation {
  animation: spin 1s linear infinite;
  display: inline-block;
}

/* ========================
   EL-ELEMENTS ORANGE THEME
   ======================== */
.metronic-input-orange :deep(.el-input__wrapper),
.metronic-select-orange :deep(.el-input__wrapper),
.metronic-datepicker-orange :deep(.el-input__wrapper) {
  background-color: var(--bs-body-bg);
  border-color: var(--bs-gray-300);
}

.metronic-input-orange :deep(.el-input__wrapper:hover),
.metronic-select-orange :deep(.el-input__wrapper:hover),
.metronic-datepicker-orange :deep(.el-input__wrapper:hover) {
  border-color: #F68B1E;
}

.metronic-input-orange :deep(.el-input__wrapper.is-focus),
.metronic-select-orange :deep(.el-input__wrapper.is-focus),
.metronic-datepicker-orange :deep(.el-input__wrapper.is-focus) {
  border-color: #F68B1E;
  box-shadow: 0 0 0 0.25rem rgba(246, 139, 30, 0.15);
}

.pagination-orange :deep(.el-pager li:hover) { color: #F68B1E; }
.pagination-orange :deep(.el-pager li.is-active) { background-color: #F68B1E; color: #fff; }

/* Dark Mode Overrides */
[data-bs-theme="dark"] .bg-light-orange { background-color: rgba(246, 139, 30, 0.15) !important; }
[data-bs-theme="dark"] .overlay-loading { background: rgba(30, 30, 45, 0.7); }
[data-bs-theme="dark"] .overlay-loading > div { background-color: #1e1e2d !important; border: 1px solid #323248; }
[data-bs-theme="dark"] .metronic-input-orange :deep(.el-input__wrapper),
[data-bs-theme="dark"] .metronic-select-orange :deep(.el-input__wrapper),
[data-bs-theme="dark"] .metronic-datepicker-orange :deep(.el-input__wrapper) {
  background-color: #1e1e2d; border-color: #323248;
}
</style>