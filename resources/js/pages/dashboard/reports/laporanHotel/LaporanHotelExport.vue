<template>
  <el-dialog
    title="Export Laporan ke PDF"
    :model-value="visible"
    @update:model-value="$emit('update:visible', $event)"
    width="500px"
    class="custom-export-dialog"
    destroy-on-close
  >
    <div class="d-flex flex-column gap-4 p-2">
      <div class="notice bg-light-primary rounded p-3 mb-2 border border-primary border-dashed">
        <div class="d-flex align-items-center text-primary fw-semibold">
          <i class="ki-duotone ki-document fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
          Laporan PDF akan mencakup Laba/Rugi, rincian pendapatan, dan grafik.
        </div>
      </div>

      <!-- Filter Form inside Modal -->
      <div class="form-group">
        <label class="form-label fw-bold text-gray-700">Periode Laporan</label>
        <el-select v-model="localFilter.period" class="w-100 metronic-select-orange">
          <el-option label="Harian" value="daily" />
          <el-option label="Bulanan" value="monthly" />
          <el-option label="Tahunan" value="yearly" />
        </el-select>
      </div>

      <div v-if="localFilter.period === 'monthly'" class="form-group">
        <label class="form-label fw-bold text-gray-700">Pilih Bulan</label>
        <el-select v-model="localFilter.month" class="w-100 metronic-select-orange">
          <el-option v-for="m in months" :key="m.value" :label="m.label" :value="m.value" />
        </el-select>
      </div>

      <div v-if="localFilter.period !== 'daily'" class="form-group">
        <label class="form-label fw-bold text-gray-700">Pilih Tahun</label>
        <el-select v-model="localFilter.year" class="w-100 metronic-select-orange">
          <el-option v-for="y in years" :key="y" :label="y" :value="y" />
        </el-select>
      </div>

      <div v-if="localFilter.period === 'daily'" class="form-group">
        <label class="form-label fw-bold text-gray-700">Pilih Tanggal</label>
        <el-date-picker 
          v-model="localFilter.date" 
          type="date" 
          placeholder="Pilih Tanggal" 
          format="DD MMM YYYY" 
          value-format="YYYY-MM-DD" 
          class="w-100 metronic-datepicker-orange" 
        />
      </div>
    </div>

    <template #footer>
      <div class="d-flex justify-content-end mt-4 gap-2">
        <button type="button" class="btn btn-light" @click="$emit('update:visible', false)" :disabled="isExporting">
          Batal
        </button>
        <button type="button" class="btn btn-success d-flex align-items-center gap-2" @click="handleExport" :disabled="isExporting">
          <i v-if="isExporting" class="spinner-border spinner-border-sm"></i>
          <i v-else class="ki-duotone ki-file-down fs-2"><span class="path1"></span><span class="path2"></span></i>
          {{ isExporting ? 'Memproses...' : 'Download PDF' }}
        </button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'; 
import axios from 'axios';
import JwtService from "@/core/services/JwtService"; 

const props = defineProps<{
  visible: boolean;
  currentFilter: any;
}>();

const emit = defineEmits(['update:visible']);

const isExporting = ref(false);
const localFilter = ref({ ...props.currentFilter });

const months = [
  { value: 1, label: 'Januari' }, { value: 2, label: 'Februari' }, { value: 3, label: 'Maret' },
  { value: 4, label: 'April' }, { value: 5, label: 'Mei' }, { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' }, { value: 8, label: 'Agustus' }, { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' }, { value: 11, label: 'November' }, { value: 12, label: 'Desember' }
];

const today = new Date();
const years = Array.from({ length: 5 }, (_, i) => today.getFullYear() - i);

// Sync prop ke local saat modal dibuka
watch(() => props.visible, (newVal) => {
  if (newVal) localFilter.value = { ...props.currentFilter };
});

const handleExport = async () => {
  isExporting.value = true;
  try {
    const token = JwtService.getToken() || window.localStorage.getItem('id_token') || window.localStorage.getItem('token');
    
    // Perbaikan: Hapus `/api/` di depan URL agar sesuai dengan axios baseURL bawaan
    const response = await axios.get('reports/hotel/export', {
      params: localFilter.value,
      responseType: 'blob',
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/pdf'
      }
    });

    // Proses Download Blob menjadi File di Browser
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Laporan_Hotel_${localFilter.value.period}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Tutup Modal
    emit('update:visible', false);
  } catch (error) {
    console.error("Gagal export PDF", error);
  } finally {
    isExporting.value = false;
  }
};
</script>

<style scoped>
.custom-export-dialog :deep(.el-dialog__header) {
  border-bottom: 1px solid var(--bs-gray-200);
  margin-right: 0;
  padding-bottom: 15px;
}
.custom-export-dialog :deep(.el-dialog__footer) {
  border-top: 1px solid var(--bs-gray-200);
  padding-top: 15px;
}
</style>