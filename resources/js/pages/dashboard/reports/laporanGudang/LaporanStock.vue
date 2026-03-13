<template>
  <div class="d-flex flex-column gap-5">
    
    <!-- HEADER CARDS - Summary Stock -->
    <div class="row g-5 g-xl-8 mb-5 animate__animated animate__fadeInDown">
      
      <!-- Card: Total Barang Masuk -->
      <div class="col-md-4 col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-light-success">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px me-5">
                <span class="symbol-label bg-success bg-opacity-10">
                  <i class="ki-duotone ki-delivery-2 fs-2x text-success">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                    <span class="path5"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2 fw-bold text-gray-900 lh-1 mb-1">{{ formatCurrency(summary.total_in_value) }}</span>
                <span class="text-success fw-semibold fs-6">Nilai Barang Masuk</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Total Barang Keluar -->
      <div class="col-md-4 col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-light-danger">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px me-5">
                <span class="symbol-label bg-danger bg-opacity-10">
                  <i class="ki-duotone ki-package fs-2x text-danger">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2 fw-bold text-gray-900 lh-1 mb-1">{{ formatCurrency(summary.total_out_value) }}</span>
                <span class="text-danger fw-semibold fs-6">Nilai Barang Keluar</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Total Transaksi (Premium Orange Theme) -->
      <div class="col-md-4 col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-orange-gradient">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px me-5">
                <span class="symbol-label bg-white bg-opacity-25">
                  <i class="ki-duotone ki-chart-simple fs-2x text-white">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2 fw-bold text-white lh-1 mb-1">{{ summary.total_transactions }} Transaksi</span>
                <span class="text-white text-opacity-75 fw-semibold fs-6">Aktivitas Gudang</span>
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
            <h3 class="fw-bold text-gray-800 mb-1">Riwayat Transaksi Stok</h3>
            <span class="text-gray-500 fs-7">Pantau pergerakan barang masuk, keluar, dan penyesuaian (adjustment)</span>
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

            <!-- EXPORT BUTTON -->
            <button @click="openExportModal" class="btn btn-sm btn-light-success fw-bold d-flex align-items-center gap-2">
              <i class="ki-duotone ki-file-down fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
              Export Laporan
            </button>
          </div>
        </div>

        <!-- Bottom Row: Filter Controls Wrapper -->
        <div class="d-flex flex-wrap align-items-center gap-3 bg-light p-4 rounded border border-dashed border-gray-300 w-100">
          
          <div class="fw-semibold text-gray-600 me-2 d-none d-md-flex align-items-center">
            <i class="ki-duotone ki-filter fs-2 text-gray-500 me-2">
              <span class="path1"></span><span class="path2"></span>
            </i>
            Filter:
          </div>

          <!-- Search Bar -->
          <el-input 
            v-model="filters.search" 
            placeholder="Cari kode/nama..." 
            class="w-100 w-md-200px metronic-input-orange"
            clearable
            @input="handleSearch"
            @clear="fetchData(1)"
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
            v-model="filters.dateRange"
            type="daterange"
            range-separator="-"
            start-placeholder="Tgl Mulai"
            end-placeholder="Tgl Akhir"
            format="DD MMM YYYY"
            value-format="YYYY-MM-DD"
            class="metronic-datepicker-orange w-100 w-md-auto"
            @change="fetchData(1)"
          />

          <!-- Filter Tipe -->
          <el-select 
            v-model="filters.type" 
            placeholder="Semua Transaksi" 
            class="w-100 w-md-150px metronic-select-orange"
            @change="fetchData(1)"
            clearable
          >
            <el-option label="Semua Transaksi" value="" />
            <el-option label="Barang Masuk" value="in" />
            <el-option label="Barang Keluar" value="out" />
            <el-option label="Stok Revisi" value="adjustment" />
          </el-select>

          <!-- Filter Kategori (BARU) -->
          <el-select 
            v-model="filters.category" 
            placeholder="Semua Kategori" 
            class="w-100 w-md-150px metronic-select-orange"
            @change="fetchData(1)"
            clearable
          >
            <el-option 
              v-for="cat in categories" 
              :key="cat.value" 
              :label="cat.label" 
              :value="cat.value" 
            />
          </el-select>

          <!-- Reset Button -->
          <button 
            v-if="filters.dateRange || filters.type || filters.search || filters.category"
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
          <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
              <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0 border-bottom-2">
                <th class="min-w-150px">Tanggal</th>
                <th class="min-w-200px">Barang & Kategori</th>
                <th class="min-w-125px">Tipe & Qty</th>
                <th class="text-end min-w-125px">Total Nilai</th>
                <th class="min-w-150px ps-4">Keterangan / Ref</th>
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
                        <i class="ki-duotone ki-box fs-3x text-orange">
                          <span class="path1"></span>
                          <span class="path2"></span>
                          <span class="path3"></span>
                        </i>
                      </span>
                    </div>
                    <h3 class="fw-bold text-gray-800 mb-3">Tidak Ada Data Transaksi</h3>
                    <span class="text-gray-500 fs-6">Belum ada pergerakan stok pada periode dan filter ini.</span>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <tr v-for="item in transactions" :key="item.id" class="hover-table-row">
                
                <!-- Tanggal & Kode Trx -->
                <td>
                  <div class="d-flex flex-column">
                    <span class="text-gray-800 fw-bold">{{ formatDate(item.transaction_date) }}</span>
                    <span class="text-gray-500 fs-8">{{ item.transaction_code || '-' }}</span>
                  </div>
                </td>

                <!-- Barang & Kategori (Icon Dihilangkan) -->
                <td>
                  <div class="d-flex flex-column py-1">
                    <span class="text-gray-800 fw-bold mb-1 fs-6">{{ item.warehouse_item?.name || 'Barang Dihapus' }}</span>
                    <span class="badge badge-light-dark text-gray-700 w-fit-content">
                      {{ item.warehouse_item?.category || '-' }}
                    </span>
                  </div>
                </td>

                <!-- Tipe & Qty (Warna Dinamis dengan Helper) -->
                <td>
                  <div class="d-flex flex-column gap-1 w-fit-content">
                    <span :class="`badge badge-light-${getTransactionTypeInfo(item).color} fs-7`">
                      <i :class="`ki-duotone ${getTransactionTypeInfo(item).icon} text-${getTransactionTypeInfo(item).color} me-1`">
                        <span class="path1"></span><span class="path2"></span>
                      </i>
                      {{ getTransactionTypeInfo(item).label }}
                    </span>
                    <span class="text-gray-700 fw-bold mt-1">
                      {{ item.quantity }} {{ item.warehouse_item?.unit || 'Pcs' }}
                    </span>
                  </div>
                </td>

                <!-- Total Nilai -->
                <td class="text-end">
                  <span class="fw-bolder fs-5" :class="`text-${getTransactionTypeInfo(item).color}`">
                    {{ formatCurrency(item.total_price) }}
                  </span>
                  <div class="text-muted fs-8">@ {{ formatCurrency(item.unit_price) }}</div>
                </td>

                <!-- Keterangan -->
                <td class="ps-4">
                  <div class="text-gray-800 fs-7 text-truncate" style="max-width: 200px;" :title="item.notes">
                    {{ item.notes || '-' }}
                  </div>
                  <div class="text-gray-500 fs-8 mt-1" v-if="item.reference_type !== 'other'">
                    Ref: <span class="text-uppercase fw-bold">{{ item.reference_type }}</span>
                  </div>
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

    <!-- Export Modal Component -->
    <LaporanStockExport ref="exportModalComp" />

    <!-- Detail Modal -->
    <div class="modal fade" id="kt_modal_view_detail" ref="detailModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content rounded shadow-lg border-0">
          
          <div class="modal-header border-0 pb-0 pt-6 px-7 justify-content-between align-items-center">
            <h2 class="fw-bolder m-0 text-gray-900 d-flex align-items-center">
              <i class="ki-duotone ki-document fs-1 text-primary me-2">
                <span class="path1"></span>
                <span class="path2"></span>
              </i>
              Detail Transaksi Stok
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
                 :class="`bg-light-${getTransactionTypeInfo(selectedTransaction).color}`">
              <div class="d-flex flex-column">
                <span class="fw-semibold fs-6" :class="`text-${getTransactionTypeInfo(selectedTransaction).color}`">
                  {{ getTransactionTypeInfo(selectedTransaction).fullLabel }}
                </span>
                <span class="fw-bolder fs-1" :class="`text-${getTransactionTypeInfo(selectedTransaction).color}`">
                  {{ formatCurrency(selectedTransaction.total_price) }}
                </span>
              </div>
              <div class="symbol symbol-50px">
                <span class="symbol-label" :class="`bg-${getTransactionTypeInfo(selectedTransaction).color}`">
                  <i class="ki-duotone fs-2x text-white" :class="getTransactionTypeInfo(selectedTransaction).icon">
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
                  {{ formatDate(selectedTransaction.transaction_date) }}
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Kode Transaksi</div>
                <div class="fw-bold text-gray-800 fs-6">
                  {{ selectedTransaction.transaction_code || '-' }}
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Nama Barang</div>
                <div class="fw-bold text-gray-800 fs-6">
                  {{ selectedTransaction.warehouse_item?.name || '-' }}
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Kategori</div>
                <div class="d-flex align-items-center">
                  <span class="badge badge-dark text-white">
                    {{ selectedTransaction.warehouse_item?.category || '-' }}
                  </span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Kuantitas (Qty)</div>
                <div class="fw-bold text-gray-800 fs-6">
                  {{ selectedTransaction.quantity }} {{ selectedTransaction.warehouse_item?.unit || 'Pcs' }}
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Harga Satuan</div>
                <div class="fw-bold text-gray-800 fs-6">
                  {{ formatCurrency(selectedTransaction.unit_price) }}
                </div>
              </div>
            </div>

            <!-- Deskripsi -->
            <div class="bg-gray-100 p-4 rounded border border-gray-300 border-dashed">
              <div class="fw-semibold text-gray-500 mb-2">Keterangan / Referensi:</div>
              <p class="m-0 text-gray-800 fw-medium text-break">
                {{ selectedTransaction.notes || '-' }}
              </p>
              <div class="mt-2 text-gray-500 fs-8" v-if="selectedTransaction.reference_type !== 'other'">
                Tipe Referensi: <span class="text-uppercase fw-bold">{{ selectedTransaction.reference_type }}</span>
              </div>
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
import JwtService from "@/core/services/JwtService";
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';
import LaporanStockExport from './LaporanStockExport.vue';

// --- TYPES ---
interface WarehouseItem {
  id: number;
  name: string;
  category: string;
  unit: string;
}

interface Transaction {
  id: number;
  transaction_code: string;
  transaction_date: string;
  transaction_type: 'in' | 'out';
  quantity: number;
  unit_price: number;
  total_price: number;
  notes: string;
  reference_type: string;
  warehouse_item: WarehouseItem;
}

// --- STATE ---
const loading = ref(false);
const transactions = ref<Transaction[]>([]);
const summary = ref({
  total_in_value: 0,
  total_out_value: 0,
  total_transactions: 0
});

// Categories State
const categories = ref<{value: string, label: string}[]>([]);

const filters = reactive({
  search: '',
  dateRange: null as any,
  type: '',
  category: '' // Tambahan filter kategori
});

const pagination = reactive({
  current_page: 1,
  per_page: 15,
  total: 0
});

// Modal Detail State
const detailModalRef = ref<HTMLElement | null>(null);
const detailModalInstance = ref<any>(null);
const selectedTransaction = ref<Transaction | null>(null);

const openDetailModal = (item: Transaction) => {
  selectedTransaction.value = item;
  if (detailModalInstance.value) {
    detailModalInstance.value.show();
  }
};

// --- HELPER UNTUK TAMPILAN TIPE ---
const getTransactionTypeInfo = (trx: Transaction) => {
  if (trx.transaction_code?.startsWith('ADJ/')) {
    return {
      label: `Revisi (${trx.transaction_type === 'in' ? '+' : '-'})`,
      fullLabel: 'Stok Revisi (Adjustment)',
      color: 'primary',
      icon: 'ki-arrows-loop'
    };
  } else if (trx.transaction_type === 'in') {
    return {
      label: 'Masuk',
      fullLabel: 'Barang Masuk (In)',
      color: 'success',
      icon: 'ki-arrow-down'
    };
  } else {
    return {
      label: 'Keluar',
      fullLabel: 'Barang Keluar (Out)',
      color: 'danger',
      icon: 'ki-arrow-up'
    };
  }
};

// --- METHODS ---
const fetchCategories = async () => {
  try {
    const res = await ApiService.get('warehouse/categories');
    if (res.data.success) {
      categories.value = res.data.data.map((c: any) => ({ value: c.name, label: c.name }));
    }
  } catch (error) {
    console.error("Gagal memuat kategori:", error);
  }
};

const fetchSummary = async () => {
  try {
    const params: any = {};
    if (filters.dateRange && filters.dateRange.length === 2) {
      params.start_date = filters.dateRange[0];
      params.end_date = filters.dateRange[1];
    }

    const response = await ApiService.query("warehouse/transactions/summary", params);
    if (response.data.success) {
      const totals = response.data.data.totals;
      summary.value = {
        total_in_value: Number(totals.total_in_value || 0),
        total_out_value: Number(totals.total_out_value || 0),
        total_transactions: Number(totals.total_transactions || 0)
      };
    }
  } catch (error) {
    console.error("Failed to fetch summary", error);
  }
};

const fetchData = async (page = 1) => {
  loading.value = true;
  try {
    const params: any = {
      page: page,
      per_page: pagination.per_page,
      type: filters.type === 'adjustment' ? '' : filters.type,
      search: filters.type === 'adjustment' && !filters.search ? 'ADJ/' : filters.search 
    };

    if (filters.dateRange && filters.dateRange.length === 2) {
      params.start_date = filters.dateRange[0];
      params.end_date = filters.dateRange[1];
    }
    
    // Tambahkan filter kategori ke API call
    if (filters.category) {
      params.category = filters.category;
    }

    fetchSummary();

    const response = await ApiService.query("warehouse/transactions", params);
    
    if (response.data.success) {
      transactions.value = response.data.data;
      pagination.current_page = response.data.meta.current_page;
      pagination.per_page = response.data.meta.per_page;
      pagination.total = response.data.meta.total;
    }

  } catch (error) {
    console.error("Error fetching stock transactions:", error);
  } finally {
    setTimeout(() => { loading.value = false; }, 400);
  }
};

let searchTimeout: any = null;
const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => { fetchData(1); }, 500); 
};

const refreshData = () => { fetchData(pagination.current_page); };

const resetFilters = () => {
  filters.search = '';
  filters.dateRange = null;
  filters.type = '';
  filters.category = '';
  fetchData(1);
};

const handlePageChange = (newPage: number) => { fetchData(newPage); };

const exportModalComp = ref<InstanceType<typeof LaporanStockExport> | null>(null);

const openExportModal = () => {
  exportModalComp.value?.open({
    dateRange: filters.dateRange,   
    category:  filters.category, // Pass kategori yang aktif ke modal export
  });
};

// --- FORMATTERS ---
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  const options: Intl.DateTimeFormatOptions = { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

// --- LIFECYCLE ---
onMounted(() => {
  fetchCategories();
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
   TABLE ENHANCEMENTS
   ======================== */
.hover-table-row { transition: all 0.3s ease; }
.hover-table-row:hover { background-color: rgba(246, 139, 30, 0.03); }
.w-fit-content { width: fit-content; }
.min-h-200px { min-height: 200px; }

.overlay-loading {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(3px);
  z-index: 10;
  top: 0;
  left: 0;
}

.card-flush { box-shadow: 0px 0px 20px 0px rgba(76, 87, 125, 0.02); }
.animate__animated { animation-duration: 0.6s; }

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.spin-animation { animation: spin 1s linear infinite; display: inline-block; }

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

/* Modal Customization */
.scroll-y { max-height: calc(100vh - 200px); overflow-y: auto; }
.scroll-y::-webkit-scrollbar { width: 6px; }
.scroll-y::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.scroll-y::-webkit-scrollbar-thumb { background: #F68B1E; border-radius: 10px; }

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