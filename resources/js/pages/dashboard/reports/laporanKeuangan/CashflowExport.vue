<template>
  <div class="modal fade" id="kt_modal_export_cashflow" ref="modalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-500px">
      <div class="modal-content rounded shadow-lg border-0">
        
        <!-- Modal Header -->
        <div class="modal-header border-0 pb-0 pt-6 px-7">
          <h2 class="fw-bolder m-0 text-gray-900 d-flex align-items-center">
            <i class="ki-duotone ki-file-down fs-1 text-success me-2">
              <span class="path1"></span>
              <span class="path2"></span>
            </i>
            Export Laporan Kas
          </h2>
          <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-1">
              <span class="path1"></span>
              <span class="path2"></span>
            </i>
          </div>
        </div>

        <!-- Modal Body -->
        <div class="modal-body scroll-y px-7 py-5">
          <p class="text-gray-500 fw-semibold fs-6 mb-6">
            Pilih filter di bawah ini untuk mengunduh laporan arus kas. Data akan diekspor sesuai dengan kriteria yang Anda tentukan.
          </p>

          <el-form label-position="top" size="default">
            
            <!-- Date Range -->
            <el-form-item label="Rentang Tanggal" class="mb-5">
              <el-date-picker
                v-model="filters.dateRange"
                type="daterange"
                range-separator="-"
                start-placeholder="Tgl Mulai"
                end-placeholder="Tgl Akhir"
                format="DD MMM YYYY"
                value-format="YYYY-MM-DD"
                class="w-100 metronic-datepicker-orange"
              />
            </el-form-item>

            <!-- Type -->
            <el-form-item label="Tipe Transaksi" class="mb-5">
              <el-select v-model="filters.type" placeholder="Semua Tipe (Masuk & Keluar)" class="w-100 metronic-select-orange" clearable>
                <el-option label="Semua Tipe" value="" />
                <el-option label="Pemasukan (Income)" value="income">
                  <span class="text-success"><i class="ki-duotone ki-arrow-down text-success me-1"><span class="path1"></span><span class="path2"></span></i> Pemasukan</span>
                </el-option>
                <el-option label="Pengeluaran (Expense)" value="expense">
                  <span class="text-danger"><i class="ki-duotone ki-arrow-up text-danger me-1"><span class="path1"></span><span class="path2"></span></i> Pengeluaran</span>
                </el-option>
              </el-select>
            </el-form-item>

            <!-- Category -->
            <el-form-item label="Kategori" class="mb-5">
              <el-select v-model="filters.category" placeholder="Semua Kategori" class="w-100 metronic-select-orange" clearable>
                <el-option label="Semua Kategori" value="" />
                <el-option label="Booking Kamar" value="booking" />
                <el-option label="Restoran (POS)" value="resto" />
                <el-option label="Laundry" value="laundry" />
                <el-option label="Stok Gudang" value="warehouse" />
                <el-option label="Operasional Lain" value="other" />
              </el-select>
            </el-form-item>

          </el-form>

          <!-- Action Buttons -->
          <div class="d-flex justify-content-end gap-3 mt-8 pt-5 border-top">
            <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-dismiss="modal">
              Batal
            </button>
            <button type="button" class="btn btn-success btn-sm fw-bold d-flex align-items-center" @click="handleExport('excel')" :disabled="loadingExcel || loadingPdf">
              <span v-if="loadingExcel" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="ki-duotone ki-document fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
              Unduh Excel
            </button>
            <button type="button" class="btn btn-danger btn-sm fw-bold d-flex align-items-center" @click="handleExport('pdf')" :disabled="loadingExcel || loadingPdf">
              <span v-if="loadingPdf" class="spinner-border spinner-border-sm me-2"></span>
              <i v-else class="ki-duotone ki-file fs-3 me-1"><span class="path1"></span><span class="path2"></span></i>
              Unduh PDF
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';
import JwtService from "@/core/services/JwtService";

// Refs
const modalRef = ref<HTMLElement | null>(null);
const modalInstance = ref<any>(null);

// Loading States
const loadingExcel = ref(false);
const loadingPdf = ref(false);

// Filter State
const filters = reactive({
  dateRange: null as any,
  type: '',
  category: ''
});

onMounted(() => {
  if (modalRef.value) {
    modalInstance.value = new Modal(modalRef.value);
  }
});

const open = () => {
  filters.dateRange = null;
  filters.type = '';
  filters.category = '';
  
  if (modalInstance.value) {
    modalInstance.value.show();
  }
};

// Logika Export ke API yang diperbaiki (Memaksa format .xlsx)
const handleExport = async (format: string) => {
  try {
    if (format === 'excel') loadingExcel.value = true;
    if (format === 'pdf') loadingPdf.value = true;
    
    const params = new URLSearchParams();
    params.append('format', format); // format yang dikirim adalah 'excel'
    
    if (filters.type) params.append('type', filters.type);
    if (filters.category) params.append('category', filters.category);
    
    if (filters.dateRange && filters.dateRange.length === 2) {
      params.append('start_date', filters.dateRange[0]);
      params.append('end_date', filters.dateRange[1]);
    }

    const baseUrl = import.meta.env.VITE_APP_API_URL || '/api';
    const exportUrl = `${baseUrl}/cash-flow/export?${params.toString()}`;
    const token = JwtService.getToken() || window.localStorage.getItem('id_token');

    // Tentukan mimeType spesifik untuk Excel dan PDF
    const mimeType = format === 'excel' 
      ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
      : 'application/pdf';

    const response = await fetch(exportUrl, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': mimeType
      }
    });

    if (!response.ok) {
      throw new Error('Gagal mendownload file');
    }

    const blob = await response.blob();
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    
    const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, "");
    const timeStr = new Date().toTimeString().slice(0, 8).replace(/:/g, "");
    
    // PERBAIKAN: Pastikan ekstensi selalu .xlsx untuk excel
    const ext = format === 'excel' ? 'xlsx' : 'pdf';
    link.download = `Laporan_Arus_Kas_${dateStr}_${timeStr}.${ext}`;
    
    document.body.appendChild(link);
    link.click();
    
    link.remove();
    window.URL.revokeObjectURL(downloadUrl);

    if (modalInstance.value) {
      modalInstance.value.hide();
    }

  } catch (error) {
    console.error("Export Error:", error);
    Swal.fire({
      text: "Terjadi kesalahan saat mengekspor data. Silakan coba lagi.",
      icon: "error",
      buttonsStyling: false,
      confirmButtonText: "Ok, Mengerti",
      customClass: { confirmButton: "btn btn-danger" }
    });
  } finally {
    loadingExcel.value = false;
    loadingPdf.value = false;
  }
};

defineExpose({ open });
</script>

<style scoped>
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

:deep(.el-form-item__label) {
  font-weight: 600;
  color: var(--bs-gray-700);
  font-size: 0.925rem;
  margin-bottom: 0.5rem;
}

[data-bs-theme="dark"] .metronic-input-orange :deep(.el-input__wrapper),
[data-bs-theme="dark"] .metronic-select-orange :deep(.el-input__wrapper),
[data-bs-theme="dark"] .metronic-datepicker-orange :deep(.el-input__wrapper) {
  background-color: #1e1e2d; border-color: #323248;
}
</style>