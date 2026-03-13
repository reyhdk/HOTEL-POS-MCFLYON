<template>
  <div
    class="modal fade"
    id="kt_modal_export_laporan_stok"
    ref="modalRef"
    tabindex="-1"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-dialog-centered mw-500px">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">

        <!-- ── HEADER ── -->
        <div class="modal-header border-0 px-7 pt-7 pb-4">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-wrap">
              <i class="ki-duotone ki-file-down fs-2 text-white">
                <span class="path1"></span><span class="path2"></span>
              </i>
            </div>
            <div>
              <h3 class="fw-bold text-gray-900 mb-0 fs-5">Export Laporan Stok</h3>
              <span class="text-gray-500 fs-7">Pilih filter lalu unduh dokumen</span>
            </div>
          </div>
          <div class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal">
            <i class="ki-duotone ki-cross fs-2 text-gray-500">
              <span class="path1"></span><span class="path2"></span>
            </i>
          </div>
        </div>

        <!-- ── BODY ── -->
        <div class="modal-body px-7 pb-7 pt-2">

          <!-- ══════════════════════════════════════════
               SECTION: FILTER
          ══════════════════════════════════════════ -->
          <div class="d-flex flex-column gap-5">

            <!-- Tanggal -->
            <div>
              <label class="form-label fw-semibold text-gray-700 mb-2 fs-6">
                Periode Tanggal
              </label>
              <el-date-picker
                v-model="form.dateRange"
                type="daterange"
                range-separator="–"
                start-placeholder="Tanggal Mulai"
                end-placeholder="Tanggal Akhir"
                format="DD MMM YYYY"
                value-format="YYYY-MM-DD"
                class="w-100 metronic-datepicker-orange"
                clearable
              />
            </div>

            <!-- Kategori -->
            <div>
              <label class="form-label fw-semibold text-gray-700 mb-2 fs-6">
                Kategori
                <span class="text-muted fw-normal ms-1">(opsional)</span>
              </label>
              <el-select
                v-model="form.category"
                placeholder="Semua Kategori"
                class="w-100 metronic-select-orange"
                clearable
                :loading="loadingCategories"
              >
                <el-option
                  v-for="cat in categories"
                  :key="cat.value"
                  :label="cat.label"
                  :value="cat.value"
                />
              </el-select>
            </div>

          </div>

          <!-- ══════════════════════════════════════════
               SECTION: PREVIEW DOKUMEN (dinamis)
               Pengguna langsung tahu dokumen apa yang
               akan mereka dapat sebelum klik download
          ══════════════════════════════════════════ -->
          <transition name="swap" mode="out-in">

            <!-- Ketika ADA tanggal dipilih → Riwayat Transaksi -->
            <div
              v-if="form.dateRange && form.dateRange.length === 2"
              key="detail"
              class="doc-preview mt-5 border-start border-4 border-primary ps-4 py-3 bg-light-primary rounded-end"
            >
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="ki-duotone ki-document fs-3 text-primary">
                  <span class="path1"></span><span class="path2"></span>
                </i>
                <span class="fw-bold text-primary fs-6">Riwayat Transaksi Gudang</span>
              </div>
              <div class="text-gray-600 fs-7 lh-lg">
                Dokumen berisi <strong>semua transaksi</strong> (barang masuk, keluar, dan revisi stok)
                selama periode <strong>{{ previewPeriode }}</strong>.
                Setiap baris menampilkan tanggal, nama barang, jumlah, harga satuan, dan total nilai.
              </div>
              <div class="mt-2 d-flex align-items-center gap-1 text-gray-500 fs-8">
                <i class="ki-duotone ki-information fs-6"><span class="path1"></span><span class="path2"></span></i>
                Cocok untuk rekap pembelian atau audit bulanan
              </div>
            </div>

            <!-- Ketika TIDAK ADA tanggal → Rekap Status Stok -->
            <div
              v-else
              key="comparison"
              class="doc-preview mt-5 border-start border-4 border-warning ps-4 py-3 bg-light-warning rounded-end"
            >
              <div class="d-flex align-items-center gap-2 mb-2">
                <i class="ki-duotone ki-chart-simple fs-3 text-warning">
                  <span class="path1"></span><span class="path2"></span>
                  <span class="path3"></span><span class="path4"></span>
                </i>
                <span class="fw-bold text-warning fs-6">Rekap Status Stok Semua Barang</span>
              </div>
              <div class="text-gray-600 fs-7 lh-lg">
                Dokumen berisi <strong>kondisi stok saat ini</strong> untuk setiap barang di gudang:
                stok awal, total masuk, total keluar, stok sekarang, dan status ketersediaan.
              </div>
              <div class="mt-2 d-flex align-items-center gap-1 text-gray-500 fs-8">
                <i class="ki-duotone ki-information fs-6"><span class="path1"></span><span class="path2"></span></i>
                Pilih tanggal di atas jika ingin laporan transaksi per periode
              </div>
            </div>

          </transition>

          <!-- ══════════════════════════════════════════
               SECTION: TOMBOL DOWNLOAD
          ══════════════════════════════════════════ -->
          <div class="d-flex gap-3 mt-6">
            <button
              type="button"
              class="btn fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2"
              :class="exportingExcel ? 'btn-success' : 'btn-light-success'"
              :disabled="exportingExcel || exportingPdf"
              @click="doExport('excel')"
            >
              <span v-if="exportingExcel" class="spinner-border spinner-border-sm"></span>
              <template v-else>
                <i class="ki-duotone ki-document fs-3">
                  <span class="path1"></span><span class="path2"></span>
                </i>
              </template>
              <span>{{ exportingExcel ? 'Menyiapkan...' : 'Excel (.xlsx)' }}</span>
            </button>

            <button
              type="button"
              class="btn fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2"
              :class="exportingPdf ? 'btn-danger' : 'btn-light-danger'"
              :disabled="exportingExcel || exportingPdf"
              @click="doExport('pdf')"
            >
              <span v-if="exportingPdf" class="spinner-border spinner-border-sm"></span>
              <template v-else>
                <i class="ki-duotone ki-file fs-3">
                  <span class="path1"></span><span class="path2"></span>
                </i>
              </template>
              <span>{{ exportingPdf ? 'Menyiapkan...' : 'PDF' }}</span>
            </button>
          </div>

          <!-- Batal -->
          <button
            type="button"
            class="btn btn-light w-100 mt-2 fw-semibold text-gray-600"
            data-bs-dismiss="modal"
            :disabled="exportingExcel || exportingPdf"
          >
            Batal
          </button>

        </div>
      </div>
    </div>
  </div>
</template>


<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';
import ApiService from '@/core/services/ApiService';
import JwtService from '@/core/services/JwtService';

interface FilterPreset {
  dateRange?: [string, string] | null;
  category?: string;
}
interface CategoryOption {
  value: string;
  label: string;
}

const modalRef          = ref<HTMLElement | null>(null);
const modalInstance     = ref<InstanceType<typeof Modal> | null>(null);
const exportingExcel    = ref(false);
const exportingPdf      = ref(false);
const loadingCategories = ref(false);
const categories        = ref<CategoryOption[]>([]);

const form = reactive<FilterPreset>({
  dateRange: null,
  category: '',
});

// Label periode untuk ditampilkan di preview
const previewPeriode = computed(() => {
  if (!form.dateRange || form.dateRange.length < 2) return '';
  const fmt = (d: string) => new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
  return `${fmt(form.dateRange[0])} – ${fmt(form.dateRange[1])}`;
});

onMounted(() => {
  if (modalRef.value) modalInstance.value = new Modal(modalRef.value);
  fetchCategories();
});

function open(preset?: FilterPreset) {
  if (preset) {
    form.dateRange = preset.dateRange ?? null;
    form.category  = preset.category ?? '';
  }
  modalInstance.value?.show();
}
function close() {
  modalInstance.value?.hide();
}
defineExpose({ open, close });

async function fetchCategories() {
  loadingCategories.value = true;
  try {
    const res = await ApiService.get('warehouse/categories');
    if (res.data.success) {
      categories.value = res.data.data.map((c: any) => ({ value: c.name, label: c.name }));
    }
  } catch (e) {
    console.error('Gagal memuat kategori:', e);
  } finally {
    loadingCategories.value = false;
  }
}

async function doExport(format: 'excel' | 'pdf') {
  try {
    if (format === 'excel') exportingExcel.value = true;
    else exportingPdf.value = true;

    const params = new URLSearchParams();
    params.append('format', format);

    const hasDateRange = form.dateRange && form.dateRange.length === 2;
    if (hasDateRange) {
      params.append('start_date', form.dateRange![0]);
      params.append('end_date',   form.dateRange![1]);
    }
    if (form.category) params.append('category', form.category);

    const baseUrl  = import.meta.env.VITE_APP_API_URL || '/api';
    const url      = `${baseUrl}/warehouse/transactions/export-laporan?${params.toString()}`;
    const token    = JwtService.getToken() || window.localStorage.getItem('id_token');
    const mimeType = format === 'excel'
      ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      : 'application/pdf';

    const response = await fetch(url, {
      method: 'GET',
      headers: { 'Authorization': `Bearer ${token}`, 'Accept': mimeType },
    });

    if (!response.ok) {
      const errJson = await response.json().catch(() => ({}));
      throw new Error(errJson.message ?? `Server error ${response.status}`);
    }

    const blob        = await response.blob();
    const downloadUrl = URL.createObjectURL(blob);
    const link        = document.createElement('a');
    link.href         = downloadUrl;

    const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    const prefix  = hasDateRange ? 'Laporan_Riwayat_Transaksi_Gudang' : 'Laporan_Status_Stok_Gudang';
    link.download = `${prefix}_${dateStr}.${format === 'excel' ? 'xlsx' : 'pdf'}`;

    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(downloadUrl);

    close();
  } catch (err: any) {
    console.error('Export error:', err);
    Swal.fire({
      icon: 'error',
      title: 'Gagal Export',
      text: err.message ?? 'Terjadi kesalahan saat mengekspor laporan.',
      buttonsStyling: false,
      confirmButtonText: 'Ok, Mengerti',
      customClass: { confirmButton: 'btn btn-danger' },
    });
  } finally {
    exportingExcel.value = false;
    exportingPdf.value   = false;
  }
}
</script>


<style scoped>
/* ── Icon header ── */
.icon-wrap {
  width: 38px; height: 38px;
  background: linear-gradient(135deg, #F68B1E, #d87614);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* ── Doc preview transition ── */
.swap-enter-active, .swap-leave-active { transition: all 0.2s ease; }
.swap-enter-from { opacity: 0; transform: translateX(8px); }
.swap-leave-to   { opacity: 0; transform: translateX(-8px); }

.doc-preview { transition: all 0.2s ease; }

/* ── El-component orange theme ── */
.metronic-datepicker-orange :deep(.el-input__wrapper),
.metronic-select-orange     :deep(.el-input__wrapper) {
  background-color: var(--bs-body-bg);
  border-color: var(--bs-gray-300);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.metronic-datepicker-orange :deep(.el-input__wrapper:hover),
.metronic-select-orange     :deep(.el-input__wrapper:hover) { border-color: #F68B1E; }
.metronic-datepicker-orange :deep(.el-input__wrapper.is-focus),
.metronic-select-orange     :deep(.el-input__wrapper.is-focus) {
  border-color: #F68B1E;
  box-shadow: 0 0 0 0.2rem rgba(246, 139, 30, 0.15);
}

/* ── Dark mode ── */
[data-bs-theme="dark"] .metronic-datepicker-orange :deep(.el-input__wrapper),
[data-bs-theme="dark"] .metronic-select-orange     :deep(.el-input__wrapper) {
  background-color: #1e1e2d; border-color: #323248;
}
[data-bs-theme="dark"] .doc-preview { background-color: rgba(255,255,255,0.03) !important; }
</style>