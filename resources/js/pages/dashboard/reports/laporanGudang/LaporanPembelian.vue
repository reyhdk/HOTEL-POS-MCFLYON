<template>
  <div class="d-flex flex-column gap-5">
    
    <!-- HEADER CARDS - Summary Pembelian -->
    <div class="row g-5 g-xl-8 mb-5 animate__animated animate__fadeInDown">
      
      <!-- Card: Total Pengeluaran Pembelian (Rp) -->
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
                <span class="fs-2 fw-bold text-white lh-1 mb-1">{{ formatCurrency(summary.total_in_value) }}</span>
                <span class="text-white text-opacity-75 fw-semibold fs-6">Total Nilai Pembelian</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Total Barang Masuk (Qty) -->
      <div class="col-md-4 col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-light-success">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px me-5">
                <span class="symbol-label bg-success bg-opacity-10">
                  <i class="ki-duotone ki-delivery fs-2x text-success">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                    <span class="path5"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2 fw-bold text-gray-900 lh-1 mb-1">{{ summary.total_in }} Item</span>
                <span class="text-success fw-semibold fs-6">Kuantitas Barang Masuk</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Jumlah Transaksi -->
      <div class="col-md-4 col-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-light-primary">
          <div class="card-body d-flex align-items-center justify-content-between py-7">
            <div class="d-flex align-items-center">
              <div class="symbol symbol-50px me-5">
                <span class="symbol-label bg-primary bg-opacity-10">
                  <i class="ki-duotone ki-document fs-2x text-primary">
                    <span class="path1"></span>
                    <span class="path2"></span>
                  </i>
                </span>
              </div>
              <div class="d-flex flex-column">
                <span class="fs-2 fw-bold text-gray-900 lh-1 mb-1">{{ summary.total_transactions }} Nota</span>
                <span class="text-primary fw-semibold fs-6">Jumlah Transaksi Beli</span>
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
            <h3 class="fw-bold text-gray-800 mb-1">Riwayat Pembelian Barang</h3>
            <span class="text-gray-500 fs-7">Audit histori stok masuk untuk mencegah kecurangan harga & qty</span>
          </div>

          <div class="d-flex align-items-center gap-2">
            <!-- Settings Icon Button -->
            <button 
              @click="openIconSettings"
              class="btn btn-sm btn-icon btn-light-warning border border-warning border-dashed"
              data-bs-toggle="tooltip"
              title="Pengaturan Ikon Kategori"
            >
              <i class="ki-duotone ki-setting-2 fs-2">
                <span class="path1"></span><span class="path2"></span>
              </i>
            </button>

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

            <!-- Export Button (Trigger Modal) -->
            <button @click="handleOpenExport" class="btn btn-sm btn-light-success fw-bold d-flex align-items-center gap-2">
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
            v-model="searchQuery" 
            placeholder="Cari kode trx / barang..." 
            class="w-100 w-md-200px metronic-input-orange"
            clearable
            @input="handleSearch"
            @clear="handleFilterChange"
          >
            <template #prefix>
              <i class="ki-duotone ki-magnifier fs-3 text-gray-500">
                <span class="path1"></span><span class="path2"></span>
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
            class="metronic-datepicker-orange w-100 w-md-250px"
            @change="handleFilterChange"
          />

          <!-- Filter Kategori Barang -->
          <el-select 
            v-model="filterCategory" 
            placeholder="Semua Kategori" 
            class="w-100 w-md-200px metronic-select-orange"
            filterable
            @change="handleFilterChange"
            clearable
          >
            <el-option label="Semua Kategori" value="" />
            <el-option 
              v-for="cat in categories" 
              :key="cat.id" 
              :label="cat.name" 
              :value="cat.name" 
            />
          </el-select>

          <!-- Reset Button -->
          <button 
            v-if="dateRange || filterCategory || searchQuery"
            @click="resetFilters"
            class="btn btn-sm btn-light-danger fw-bold ms-auto"
            data-bs-toggle="tooltip"
            title="Reset Filter"
          >
            <i class="ki-duotone ki-cross fs-2">
              <span class="path1"></span><span class="path2"></span>
            </i>
            Reset
          </button>
        </div>
      </div>

      <!-- Card Body -->
      <div class="card-body pt-0 mt-4">
        
        <!-- Data Table Wrapper -->
        <div class="table-responsive position-relative min-h-200px">
          
          <!-- Loading Overlay -->
          <div v-if="loading" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center overlay-loading">
            <div class="d-flex flex-column align-items-center bg-white p-6 rounded shadow-sm">
              <div class="spinner-border text-orange mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                <span class="visually-hidden">Loading...</span>
              </div>
              <span class="text-gray-700 fw-bold fs-6">Menarik Histori Pembelian...</span>
            </div>
          </div>

          <!-- Table -->
          <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
              <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0 border-bottom-2">
                <th class="min-w-150px">Tanggal & Kode</th>
                <th class="min-w-200px">Detail Barang</th>
                <th class="text-end min-w-100px">Harga Satuan</th>
                <th class="text-center min-w-100px">Qty Masuk</th>
                <th class="text-end min-w-125px">Total (Rp)</th>
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
                        <i class="ki-duotone ki-shop fs-3x text-orange">
                          <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
                        </i>
                      </span>
                    </div>
                    <h3 class="fw-bold text-gray-800 mb-3">Belum Ada Pembelian</h3>
                    <span class="text-gray-500 fs-6">Tidak ada histori stok masuk pada filter yang dipilih.</span>
                  </div>
                </td>
              </tr>

              <!-- Data Rows -->
              <tr v-for="item in transactions" :key="item.id" class="hover-table-row">
                
                <!-- Tanggal & Kode -->
                <td>
                  <div class="d-flex flex-column">
                    <span class="text-gray-800 fw-bold mb-1">{{ formatDate(item.transaction_date) }}</span>
                    <span class="text-gray-500 fs-8 mb-1">{{ formatTime(item.transaction_date) }}</span>
                    <span class="badge badge-dark text-white w-fit-content px-2 py-1 fs-8">{{ item.transaction_code }}</span>
                  </div>
                </td>

                <!-- Detail Barang -->
                <td>
                  <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-3">
                      <span :class="`symbol-label bg-light-${getItemColor(item.warehouse_item?.category)}`">
                        <i :class="`ki-duotone ${getItemIcon(item.warehouse_item?.category)} fs-2 text-${getItemColor(item.warehouse_item?.category)}`">
                          <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                        </i>
                      </span>
                    </div>
                    <div class="d-flex flex-column">
                      <span class="text-gray-800 fw-bold mb-1">{{ item.warehouse_item?.name || 'Barang Dihapus' }}</span>
                      <span class="text-gray-500 fs-8">Kategori: {{ item.warehouse_item?.category || '-' }}</span>
                      <span class="text-gray-500 fs-8 mt-1" v-if="item.notes" :title="item.notes">
                        📝 <i>{{ item.notes.length > 25 ? item.notes.substring(0, 25) + '...' : item.notes }}</i>
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Harga Satuan -->
                <td class="text-end text-gray-700 fw-bold fs-6">
                  {{ formatCurrency(item.unit_price) }}
                  <span class="d-block text-muted fs-8 fw-normal">per {{ item.warehouse_item?.unit || 'unit' }}</span>
                </td>

                <!-- Qty Masuk -->
                <td class="text-center">
                  <span class="badge badge-light-success fs-5 px-3 py-2 fw-bolder">
                    <i class="ki-duotone ki-plus fs-4 text-success me-1"><span class="path1"></span><span class="path2"></span></i>
                    {{ item.quantity }}
                  </span>
                </td>

                <!-- Total -->
                <td class="text-end text-gray-900 fw-bolder fs-5">
                  {{ formatCurrency(item.total_price) }}
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
            dari {{ pagination.total }} barang masuk
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

    <!-- MODAL VIEW DETAIL PEMBELIAN -->
    <div class="modal fade" id="kt_modal_view_purchase_detail" ref="detailModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content rounded shadow-lg border-0">
          
          <div class="modal-header border-0 pb-0 pt-6 px-7 justify-content-between align-items-center">
            <h2 class="fw-bolder m-0 text-gray-900 d-flex align-items-center">
              <i class="ki-duotone ki-receipt-square fs-1 text-primary me-2">
                <span class="path1"></span><span class="path2"></span>
              </i>
              Detail Pembelian
            </h2>
            <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
              <i class="ki-duotone ki-cross fs-1">
                <span class="path1"></span><span class="path2"></span>
              </i>
            </div>
          </div>

          <div class="modal-body scroll-y px-7 py-5" v-if="selectedTransaction">
            
            <!-- Summary Header di Dalam Modal -->
            <div class="d-flex align-items-center justify-content-between p-5 mb-5 rounded bg-light-success">
              <div class="d-flex flex-column">
                <span class="fw-semibold fs-6 text-success">Total Nilai Pembelian (Rp)</span>
                <span class="fw-bolder fs-1 text-success">
                  {{ formatCurrency(selectedTransaction.total_price) }}
                </span>
              </div>
              <div class="symbol symbol-50px">
                <span class="symbol-label bg-success">
                  <i class="ki-duotone ki-arrow-down fs-2x text-white">
                    <span class="path1"></span><span class="path2"></span>
                  </i>
                </span>
              </div>
            </div>

            <!-- Detail Grid -->
            <div class="row g-5 mb-5">
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Kode Transaksi</div>
                <div class="fw-bold text-gray-800 fs-6">
                  <span class="badge badge-dark text-white px-2 py-1">{{ selectedTransaction.transaction_code }}</span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Tanggal & Waktu Masuk</div>
                <div class="fw-bold text-gray-800 fs-6">
                  {{ formatDate(selectedTransaction.transaction_date) }} - {{ formatTime(selectedTransaction.transaction_date) }}
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Detail Barang</div>
                <div class="d-flex flex-column">
                  <span class="fw-bold text-gray-800 fs-6">{{ selectedTransaction.warehouse_item?.name }}</span>
                  <span class="text-gray-600 fs-8">{{ selectedTransaction.warehouse_item?.category }}</span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="fw-semibold text-gray-500 mb-1">Kuantitas & Harga Satuan</div>
                <div class="d-flex flex-column">
                  <span class="fw-bold text-gray-800 fs-6">
                    {{ selectedTransaction.quantity }} {{ selectedTransaction.warehouse_item?.unit }} 
                    <span class="text-muted fw-normal">× {{ formatCurrency(selectedTransaction.unit_price) }}</span>
                  </span>
                </div>
              </div>
            </div>

            <!-- Pencatat Data -->
            <div class="d-flex align-items-center mb-5 p-4 rounded bg-light">
              <div class="symbol symbol-circle symbol-40px me-3">
                <img v-if="selectedTransaction.creator?.avatar" :src="selectedTransaction.creator.avatar" alt="pic">
                <div v-else class="symbol-label bg-primary text-white fw-bold">
                  {{ (selectedTransaction.creator?.name || 'A').charAt(0).toUpperCase() }}
                </div>
              </div>
              <div class="d-flex flex-column">
                <span class="fw-bold text-gray-800 fs-6">Diinput oleh: {{ selectedTransaction.creator?.name || 'Sistem' }}</span>
                <span class="text-gray-500 fs-8">Staff Gudang / Admin</span>
              </div>
            </div>

            <!-- Deskripsi -->
            <div class="bg-gray-100 p-4 rounded border border-gray-300 border-dashed">
              <div class="fw-semibold text-gray-500 mb-2">Catatan Pembelian:</div>
              <p class="m-0 text-gray-800 fw-medium text-break">
                {{ selectedTransaction.notes || 'Tidak ada catatan.' }}
              </p>
            </div>

          </div>
          
          <div class="modal-footer border-0 pt-0 px-7 pb-6">
            <button type="button" class="btn btn-light w-100 fw-bold" data-bs-dismiss="modal">
              Tutup Rincian
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL PENGATURAN IKON KATEGORI -->
    <div class="modal fade" id="kt_modal_icon_settings" ref="iconSettingsModalRef" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content rounded shadow-lg border-0">
          
          <div class="modal-header border-0 pb-0 pt-6 px-7">
            <h2 class="fw-bolder m-0 text-gray-900 d-flex align-items-center">
              <i class="ki-duotone ki-setting-2 fs-1 text-warning me-2">
                <span class="path1"></span><span class="path2"></span>
              </i>
              Pengaturan Ikon Kategori
            </h2>
            <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
              <i class="ki-duotone ki-cross fs-1">
                <span class="path1"></span><span class="path2"></span>
              </i>
            </div>
          </div>

          <div class="modal-body scroll-y px-7 py-5">
            <p class="text-muted fs-7 mb-5">
              Ikon yang Anda simpan di sini akan diterapkan pada tabel. Data sementara akan disimpan pada perangkat Anda (Local Storage).
            </p>

            <div v-if="categories.length === 0" class="text-center py-5">
              <span class="text-muted">Sedang memuat kategori...</span>
            </div>

            <!-- List Kategori & Select Ikon -->
            <div v-for="cat in paginatedCategories" :key="cat.id" class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-dashed">
              <div class="d-flex align-items-center">
                <span class="symbol-label bg-light-warning me-3 p-2 rounded">
                  <i :class="`ki-duotone ${categoryIconMappings[cat.name] || getItemIcon(cat.name)} fs-2 text-warning`">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                  </i>
                </span>
                <span class="fw-bold text-gray-800">{{ cat.name }}</span>
              </div>
              
              <el-select 
                v-model="categoryIconMappings[cat.name]" 
                placeholder="Pilih Ikon" 
                class="w-150px metronic-select-orange"
              >
                <el-option 
                  v-for="icon in availableIcons" 
                  :key="icon.value" 
                  :label="icon.label" 
                  :value="icon.value"
                >
                  <span class="d-flex align-items-center">
                    <i :class="`ki-duotone ${icon.icon} me-2 text-gray-600`">
                      <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                    </i>
                    {{ icon.label }}
                  </span>
                </el-option>
              </el-select>
            </div>

            <!-- Pagination untuk Modal -->
            <div v-if="categories.length > categoryPagination.per_page" class="d-flex justify-content-center mt-6">
              <el-pagination
                background
                layout="prev, pager, next"
                :total="categories.length"
                :page-size="categoryPagination.per_page"
                :current-page="categoryPagination.current_page"
                @current-change="handleCategoryPageChange"
                class="pagination-orange"
                size="small"
              />
            </div>
          </div>

          <div class="modal-footer border-0 pt-0 px-7 pb-6">
            <button type="button" class="btn btn-light btn-sm fw-bold me-2" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-orange btn-sm fw-bold" @click="saveIconSettings">Simpan Ikon</button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL EXPORT LAPORAN -->
    <LaporanPembelianExport ref="exportModalRef" :categories="categories" />

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';
import ApiService from "@/core/services/ApiService";
import LaporanPembelianExport from './LaporanPembelianExport.vue'; // <-- Import Modal Export

// --- TYPES ---
interface Creator {
  id: number;
  name: string;
  avatar?: string;
}

interface WarehouseItem {
  id: number;
  name: string;
  category: string;
  unit: string;
  code: string;
}

interface Transaction {
  id: number;
  transaction_code: string;
  transaction_date: string;
  transaction_type: 'in' | 'out';
  quantity: number;
  unit_price: number;
  total_price: number;
  notes?: string;
  warehouse_item: WarehouseItem;
  creator: Creator;
}

interface Category {
  id: number;
  name: string;
  icon?: string;
}

// --- STATE ---
const loading = ref(false);
const searchQuery = ref('');
const dateRange = ref<any>(null);
const filterCategory = ref<string>('');

const pagination = reactive({
  current_page: 1,
  per_page: 15,
  total: 0
});

const summary = ref({
  total_in: 0,
  total_in_value: 0,
  total_transactions: 0
});

const transactions = ref<Transaction[]>([]);
const categories = ref<Category[]>([]);

// Modal Setup
const detailModalRef = ref<HTMLElement | null>(null);
const detailModalInstance = ref<any>(null);
const selectedTransaction = ref<Transaction | null>(null);

const iconSettingsModalRef = ref<HTMLElement | null>(null);
const iconSettingsModalInstance = ref<any>(null);
const categoryIconMappings = ref<Record<string, string>>({});

const exportModalRef = ref<any>(null); // Ref untuk Modal Export

// Pagination Modal Setting Kategori
const categoryPagination = reactive({
  current_page: 1,
  per_page: 5
});

const paginatedCategories = computed(() => {
  const start = (categoryPagination.current_page - 1) * categoryPagination.per_page;
  const end = start + categoryPagination.per_page;
  return categories.value.slice(start, end);
});

// Daftar Ikon
const availableIcons = [
  { value: 'ki-box', label: 'Box / Default', icon: 'ki-box' },
  { value: 'ki-burger', label: 'Makanan', icon: 'ki-burger' },
  { value: 'ki-coffee', label: 'Minuman', icon: 'ki-coffee' },
  { value: 'ki-mask', label: 'Sabun / Cairan', icon: 'ki-mask' },
  { value: 'ki-setting-2', label: 'Mesin / Tools', icon: 'ki-setting-2' },
  { value: 'ki-document', label: 'Kertas / ATK', icon: 'ki-document' },
  { value: 'ki-basket', label: 'Keranjang', icon: 'ki-basket' },
  { value: 'ki-screen', label: 'Elektronik', icon: 'ki-screen' },
  { value: 'ki-heart', label: 'Kesehatan', icon: 'ki-heart' },
  { value: 'ki-paint-bucket', label: 'Cat / Material', icon: 'ki-paint-bucket' },
  { value: 'ki-brifcase-tick', label: 'Lain-lain', icon: 'ki-brifcase-tick' },
];

// --- HELPER & MODALS ---

const handleOpenExport = () => {
  if (exportModalRef.value) {
    exportModalRef.value.open();
  }
};

const openIconSettings = () => {
  categoryPagination.current_page = 1; 
  if (iconSettingsModalInstance.value) {
    iconSettingsModalInstance.value.show();
  }
};

const saveIconSettings = async () => {
  try {
    await ApiService.post("warehouse/categories/update-icons", {
      icons: categoryIconMappings.value
    });
    
    if (iconSettingsModalInstance.value) {
      iconSettingsModalInstance.value.hide();
    }

    Swal.fire({
      text: "Pengaturan ikon berhasil disimpan!",
      icon: "success",
      buttonsStyling: false,
      confirmButtonText: "Ok, Mengerti",
      customClass: { confirmButton: "btn btn-orange" }
    });

    await fetchCategories();
    fetchData(pagination.current_page);
  } catch (error) {
    console.error("Gagal menyimpan pengaturan ikon:", error);
    Swal.fire({
      text: "Gagal menyimpan pengaturan ikon.",
      icon: "error",
      buttonsStyling: false,
      confirmButtonText: "Ok",
      customClass: { confirmButton: "btn btn-danger" }
    });
  }
};

const getItemIcon = (category?: string) => {
  if (!category) return 'ki-box';
  
  if (categoryIconMappings.value[category]) {
    return categoryIconMappings.value[category];
  }

  const cat = category.toLowerCase();
  if (cat.includes('makanan') || cat.includes('food') || cat.includes('dapur')) return 'ki-burger';
  if (cat.includes('minuman') || cat.includes('beverage')) return 'ki-coffee';
  if (cat.includes('sabun') || cat.includes('laundry') || cat.includes('amenities')) return 'ki-mask';
  if (cat.includes('elektronik') || cat.includes('mesin') || cat.includes('peralatan')) return 'ki-setting-2';
  if (cat.includes('kertas') || cat.includes('atk') || cat.includes('office')) return 'ki-document';
  
  return 'ki-box';
};

const getItemColor = (category?: string) => {
  if (!category) return 'warning';
  const cat = category.toLowerCase();
  if (cat.includes('makanan') || cat.includes('food')) return 'success';
  if (cat.includes('minuman') || cat.includes('beverage')) return 'info';
  if (cat.includes('sabun') || cat.includes('laundry')) return 'primary';
  if (cat.includes('elektronik')) return 'danger';
  return 'warning';
};


// --- API METHODS ---

const fetchData = async (page = 1) => {
  loading.value = true;
  try {
    const params: any = {
      page: page,
      per_page: pagination.per_page,
      type: 'in', 
      search: searchQuery.value 
    };

    if (filterCategory.value !== '') {
      params.category = filterCategory.value; 
    }

    if (dateRange.value && dateRange.value.length === 2) {
      params.start_date = dateRange.value[0];
      params.end_date = dateRange.value[1];
    }

    const response = await ApiService.query("warehouse/transactions", params);
    
    transactions.value = response.data.data;
    pagination.current_page = response.data.meta.current_page;
    pagination.per_page = response.data.meta.per_page;
    pagination.total = response.data.meta.total;

  } catch (error) {
    console.error("Gagal mengambil histori pembelian:", error);
  } finally {
    loading.value = false;
  }
};

const fetchSummary = async () => {
  try {
    const params: any = { period: 'monthly' };
    
    if (dateRange.value && dateRange.value.length === 2) {
      params.start_date = dateRange.value[0];
      params.end_date = dateRange.value[1];
    } else {
      params.start_date = '2000-01-01'; 
      const tmr = new Date();
      tmr.setDate(tmr.getDate() + 1); 
      params.end_date = tmr.toISOString().split('T')[0];
    }

    const response = await ApiService.query("warehouse/transactions/summary", params);
    
    if (response.data && response.data.data.totals) {
      summary.value = {
        total_in: response.data.data.totals.total_in,
        total_in_value: response.data.data.totals.total_in_value,
        total_transactions: pagination.total 
      };
    }
  } catch (error) {
    console.error("Gagal mengambil ringkasan pembelian:", error);
  }
};

const fetchCategories = async () => {
  try {
    const response = await ApiService.query("warehouse/categories", {});
    categories.value = response.data.data;
    
    categories.value.forEach(cat => {
      if (cat.icon) {
         categoryIconMappings.value[cat.name] = cat.icon;
      } else if (!categoryIconMappings.value[cat.name]) {
         categoryIconMappings.value[cat.name] = getItemIcon(cat.name);
      }
    });

  } catch (error) {
    console.error("Gagal mengambil kategori:", error);
  }
};


// --- HANDLERS ---
let searchTimeout: any = null;
const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchData(1);
    fetchSummary();
  }, 500); 
};

const refreshData = () => {
  fetchData(pagination.current_page);
  fetchSummary();
};

const handleFilterChange = () => {
  fetchData(1);
  fetchSummary();
};

const resetFilters = () => {
  searchQuery.value = '';
  dateRange.value = null;
  filterCategory.value = '';
  fetchData(1);
  fetchSummary();
};

const handlePageChange = (newPage: number) => {
  fetchData(newPage);
};

const handleCategoryPageChange = (newPage: number) => {
  categoryPagination.current_page = newPage;
};

const openDetailModal = (item: Transaction) => {
  selectedTransaction.value = item;
  if (detailModalInstance.value) {
    detailModalInstance.value.show();
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

// Lifecycle
onMounted(async () => {
  if (detailModalRef.value) {
    detailModalInstance.value = new Modal(detailModalRef.value);
  }

  if (iconSettingsModalRef.value) {
    iconSettingsModalInstance.value = new Modal(iconSettingsModalRef.value);
  }

  await fetchCategories();
  await fetchData();
  await fetchSummary();
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