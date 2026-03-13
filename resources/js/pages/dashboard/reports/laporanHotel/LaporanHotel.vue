<template>
  <div class="d-flex flex-column gap-5 position-relative">
    
    <!-- Elegant Loading Spinner Overlay -->
    <div v-if="loading" class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center overlay-loading" style="z-index: 99;">
      <div class="d-flex flex-column align-items-center bg-white p-6 rounded shadow-sm">
        <div class="spinner-border text-orange mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
          <span class="visually-hidden">Loading...</span>
        </div>
        <span class="text-gray-700 fw-bold fs-6">Memuat Data Laporan...</span>
      </div>
    </div>

    <!-- FILTER SECTION -->
    <div class="card card-flush shadow-sm border-0 animate__animated animate__fadeInDown">
      <div class="card-header border-0 pt-6 pb-4 d-flex flex-column gap-5">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center w-100 gap-3">
          <div class="card-title flex-column align-items-start m-0">
            <h3 class="fw-bold text-gray-800 mb-1">Laporan Operasional Hotel</h3>
            <span class="text-gray-500 fs-7">Ringkasan statistik okupansi, pendapatan, dan reservasi</span>
          </div>

          <div class="d-flex align-items-center gap-2">
            <button @click="fetchData" class="btn btn-sm btn-icon btn-light-primary border border-primary border-dashed" :disabled="loading">
              <i class="ki-duotone ki-arrows-circle fs-2" :class="{'spin-animation': loading}">
                <span class="path1"></span><span class="path2"></span>
              </i>
            </button>
            
            <!-- TOMBOL EXPORT MENGGUNAKAN MODAL -->
            <button @click="showExportModal = true" class="btn btn-sm btn-light-success fw-bold d-flex align-items-center gap-2">
              <i class="ki-duotone ki-file-down fs-2"><span class="path1"></span><span class="path2"></span></i>
              Export PDF
            </button>
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3 bg-light p-4 rounded border border-dashed border-gray-300 w-100">
          <div class="fw-semibold text-gray-600 me-2 d-none d-md-flex align-items-center">
            <i class="ki-duotone ki-filter fs-2 text-gray-500 me-2"><span class="path1"></span><span class="path2"></span></i>
            Filter Laporan:
          </div>

          <!-- Filter Periode -->
          <el-select v-model="filter.period" class="w-100 w-md-150px metronic-select-orange" @change="fetchData">
            <el-option label="Harian" value="daily" />
            <el-option label="Bulanan" value="monthly" />
            <el-option label="Tahunan" value="yearly" />
          </el-select>

          <!-- Filter Bulan (Tampil jika periode bulanan) -->
          <el-select v-if="filter.period === 'monthly'" v-model="filter.month" class="w-100 w-md-150px metronic-select-orange" @change="fetchData">
            <el-option v-for="m in months" :key="m.value" :label="m.label" :value="m.value" />
          </el-select>

          <!-- Filter Tahun -->
          <el-select v-if="filter.period !== 'daily'" v-model="filter.year" class="w-100 w-md-100px metronic-select-orange" @change="fetchData">
            <el-option v-for="y in years" :key="y" :label="y" :value="y" />
          </el-select>

          <!-- Filter Tanggal (Tampil jika periode harian) -->
          <el-date-picker v-if="filter.period === 'daily'" v-model="filter.date" type="date" placeholder="Pilih Tanggal" format="DD MMM YYYY" value-format="YYYY-MM-DD" class="metronic-datepicker-orange w-100 w-md-200px" @change="fetchData" />
        </div>
      </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT (ROW 1 - KEUANGAN & OKUPANSI) -->
    <div v-if="reportData" class="row g-5 g-xl-8 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
      
      <!-- 1. STATISTIK PENDAPATAN (Highlight Card) -->
      <div class="col-xl-4 col-md-6">
        <div class="card card-flush h-xl-100 shadow-sm border-0 bg-orange-gradient">
          <div class="card-header pt-5">
            <div class="card-title d-flex flex-column">
              <span class="text-white text-opacity-75 pt-1 fw-semibold fs-6">Total Pendapatan</span>
              <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{ formatCurrency(reportData.revenue.total_revenue) }}</span>
            </div>
            <div class="card-toolbar">
              <i class="ki-duotone ki-wallet fs-3x text-white text-opacity-50"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
            </div>
          </div>
          <div class="card-body pt-5 d-flex flex-column justify-content-end">
            <div class="d-flex flex-column gap-3">
              <div class="d-flex flex-stack bg-white bg-opacity-10 rounded p-3 text-white">
                <span class="fw-semibold">Kamar ({{ reportData.revenue.room_bookings }} Trx)</span>
                <span class="fw-bold">{{ formatCurrency(reportData.revenue.room_revenue) }}</span>
              </div>
              <div class="d-flex flex-stack bg-white bg-opacity-10 rounded p-3 text-white">
                <span class="fw-semibold">Restoran ({{ reportData.revenue.restaurant_orders }} Trx)</span>
                <span class="fw-bold">{{ formatCurrency(reportData.revenue.restaurant_revenue) }}</span>
              </div>
              <div class="d-flex flex-stack bg-white bg-opacity-10 rounded p-3 text-white">
                <span class="fw-semibold">Laundry ({{ reportData.revenue.laundry_orders }} Trx)</span>
                <span class="fw-bold">{{ formatCurrency(reportData.revenue.laundry_revenue) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 1.5 LABA RUGI BOX (FITUR BARU DITENGAH) -->
      <div class="col-xl-4 col-md-6">
        <div class="card card-flush h-xl-100 shadow-sm border-0" :class="reportData.profit.amount >= 0 ? 'bg-light-success' : 'bg-light-danger'">
           <div class="card-body d-flex flex-column justify-content-center p-6 text-center">
              <h3 class="fw-bold mb-2" :class="reportData.profit.amount >= 0 ? 'text-success' : 'text-danger'">
                {{ reportData.profit.amount >= 0 ? 'Laba Bersih (Keuntungan)' : 'Kerugian' }}
              </h3>
              <span class="fs-2hx fw-bold mb-3" :class="reportData.profit.amount >= 0 ? 'text-success' : 'text-danger'">
                {{ formatCurrency(reportData.profit.amount) }}
              </span>
              <div class="d-flex justify-content-center gap-4 mt-2">
                 <div class="d-flex flex-column">
                   <span class="text-gray-600 fs-7 fw-semibold">Total Pengeluaran</span>
                   <span class="fw-bold text-gray-800">{{ formatCurrency(reportData.expense.total_expense) }}</span>
                 </div>
                 <div class="d-flex flex-column border-start border-gray-400 ps-4">
                   <span class="text-gray-600 fs-7 fw-semibold">Margin Untung</span>
                   <span class="fw-bold" :class="reportData.profit.margin_percentage >= 0 ? 'text-success' : 'text-danger'">
                     {{ reportData.profit.margin_percentage }}%
                   </span>
                 </div>
              </div>
           </div>
        </div>
      </div>

      <!-- 2. STATISTIK OKUPANSI KAMAR (DIKEMBALIKAN KE VERSI ASLI) -->
      <div class="col-xl-4 col-md-12">
        <div class="card card-flush h-xl-100 shadow-sm border-0">
          <div class="card-header pt-5">
            <h3 class="card-title align-items-start flex-column">
              <span class="card-label fw-bold text-gray-800">Okupansi Kamar</span>
              <span class="text-gray-400 mt-1 fw-semibold fs-6">Kondisi realtime saat ini</span>
            </h3>
          </div>
          <div class="card-body pt-5">
            <div class="d-flex flex-center position-relative mb-5">
              <!-- Simple UI Progress representation -->
              <div class="w-150px h-150px rounded-circle d-flex align-items-center justify-content-center border border-5 border-orange bg-light-orange" style="border-width: 8px !important;">
                <div class="d-flex flex-column align-items-center">
                  <span class="fs-1 fw-bold text-orange">{{ reportData.occupancy.occupancy_rate }}%</span>
                  <span class="text-gray-500 fs-7 fw-semibold">Terisi</span>
                </div>
              </div>
            </div>
            
            <div class="row g-3">
              <div class="col-6">
                <div class="border border-dashed border-gray-300 rounded p-3 text-center">
                  <span class="fs-4 fw-bold text-success d-block">{{ reportData.occupancy.available_rooms }}</span>
                  <span class="fs-8 fw-semibold text-gray-500">Kamar Tersedia</span>
                </div>
              </div>
              <div class="col-6">
                <div class="border border-dashed border-gray-300 rounded p-3 text-center">
                  <span class="fs-4 fw-bold text-primary d-block">{{ reportData.occupancy.occupied_rooms }}</span>
                  <span class="fs-8 fw-semibold text-gray-500">Sedang Terisi</span>
                </div>
              </div>
              <div class="col-6">
                <div class="border border-dashed border-gray-300 rounded p-3 text-center bg-light-danger">
                  <span class="fs-4 fw-bold text-danger d-block">{{ reportData.occupancy.dirty_rooms }}</span>
                  <span class="fs-8 fw-semibold text-danger">Kotor/Pembersihan</span>
                </div>
              </div>
              <div class="col-6">
                <div class="border border-dashed border-gray-300 rounded p-3 text-center bg-light-warning">
                  <span class="fs-4 fw-bold text-warning d-block">{{ reportData.occupancy.maintenance_rooms }}</span>
                  <span class="fs-8 fw-semibold text-warning">Maintenance</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ROW 2 - RESERVASI DAN TAMU (DIKEMBALIKAN KE VERSI ASLI) -->
    <div v-if="reportData" class="row g-5 g-xl-8 animate__animated animate__fadeInUp" style="animation-delay: 0.15s;">
        <!-- Reservasi Card -->
        <div class="col-md-6">
            <div class="card card-flush shadow-sm border-0 h-100 bg-light-primary">
                <div class="card-body d-flex flex-column justify-content-center p-6">
                <h3 class="fw-bold text-gray-800 mb-4">Statistik Reservasi</h3>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-gray-600 fw-semibold">Total Reservasi (Periode ini)</span>
                    <span class="fs-4 fw-bold text-gray-900">{{ reportData.reservations.total }}</span>
                </div>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-gray-600 fw-semibold">Reservasi Hari Ini</span>
                    <span class="fs-5 fw-bold text-primary">{{ reportData.reservations.today }}</span>
                </div>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-gray-600 fw-semibold">Online / Offline</span>
                    <span class="fs-6 fw-bold text-gray-800">
                    <span class="text-success">{{ reportData.reservations.online }}</span> / 
                    <span class="text-info">{{ reportData.reservations.offline }}</span>
                    </span>
                </div>
                <div class="d-flex flex-stack">
                    <span class="text-gray-600 fw-semibold">Dibatalkan</span>
                    <span class="fs-6 fw-bold text-danger">{{ reportData.reservations.cancelled }} ({{ reportData.reservations.cancellation_rate }}%)</span>
                </div>
                </div>
            </div>
        </div>

        <!-- Tamu Card -->
        <div class="col-md-6">
            <div class="card card-flush shadow-sm border-0 h-100 bg-light-info">
                <div class="card-body d-flex flex-column justify-content-center p-6">
                <h3 class="fw-bold text-gray-800 mb-4">Statistik Tamu</h3>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-gray-600 fw-semibold">Tamu Menginap Saat Ini</span>
                    <span class="fs-4 fw-bold text-gray-900">{{ reportData.guests.currently_staying }}</span>
                </div>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-gray-600 fw-semibold">Check-in Hari Ini</span>
                    <span class="fs-5 fw-bold text-success">+{{ reportData.guests.check_in_today }}</span>
                </div>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-gray-600 fw-semibold">Check-out Hari Ini</span>
                    <span class="fs-5 fw-bold text-warning">-{{ reportData.guests.check_out_today }}</span>
                </div>
                <div class="d-flex flex-stack">
                    <span class="text-gray-600 fw-semibold">Rata-rata Lama Inap</span>
                    <span class="fs-6 fw-bold text-gray-800">{{ reportData.guests.avg_stay_days }} Malam</span>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. GRAFIK (DIKEMBALIKAN DENGAN 4 SERIES DATA SEPERTI ASLI) -->
    <div class="card card-flush shadow-sm border-0 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;" v-if="reportData">
      <div class="card-header pt-5">
        <h3 class="card-title align-items-start flex-column">
          <span class="card-label fw-bold dark-mode-title">Grafik Pendapatan & Transaksi</span>
          <!-- Diubah ke text-white agar terlihat di dark mode -->
          <span class="mt-1 fw-semibold fs-7 text-gray-500">Tren pendapatan berdasarkan filter yang dipilih</span>
        </h3>
      </div>
      <div class="card-body pt-5">
        <!-- Komponen grafik ApexCharts -->
        <apexchart type="area" height="350" :options="chartOptions" :series="chartSeries"></apexchart>
      </div>
    </div>

    <!-- 5. STATISTIK KAMAR TABEL BAWAH (DIKEMBALIKAN KE VERSI ASLI) -->
    <div class="row g-5 g-xl-8 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;" v-if="reportData">
      <div class="col-md-6">
        <div class="card card-flush shadow-sm border-0 h-100">
          <div class="card-header pt-5">
            <h3 class="card-title align-items-start flex-column">
              <span class="card-label fw-bold text-gray-800">Tipe Kamar Terpopuler</span>
              <span class="text-gray-400 mt-1 fw-semibold fs-7">Berdasarkan total pemesanan</span>
            </h3>
          </div>
          <div class="card-body pt-5">
            <div class="table-responsive">
              <table class="table align-middle table-row-dashed fs-6 gy-3">
                <tbody class="fw-semibold text-gray-600">
                  <tr v-for="(room, index) in reportData.rooms.popular_types" :key="index">
                    <td class="w-50px">
                      <!-- Fix TypeScript Warning by casting/parsing index -->
                      <span class="badge badge-light-orange fs-6 fw-bold">#{{ Number(index) + 1 }}</span>
                    </td>
                    <td>{{ room.type.toUpperCase() }}</td>
                    <td class="text-end text-gray-800 fw-bold">{{ room.total_bookings }} Booking</td>
                  </tr>
                  <tr v-if="reportData.rooms.popular_types.length === 0">
                    <td colspan="3" class="text-center text-muted py-5">Belum ada data pemesanan kamar.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card card-flush shadow-sm border-0 h-100">
          <div class="card-header pt-5">
            <h3 class="card-title align-items-start flex-column">
              <span class="card-label fw-bold text-gray-800">Kamar Sedang Maintenance</span>
              <span class="text-gray-400 mt-1 fw-semibold fs-7">Membutuhkan perbaikan teknis</span>
            </h3>
          </div>
          <div class="card-body pt-5">
            <div class="table-responsive">
              <table class="table align-middle table-row-dashed fs-6 gy-3">
                <tbody class="fw-semibold text-gray-600">
                  <tr v-for="(room, index) in reportData.rooms.maintenance_rooms" :key="index">
                    <td>
                      <div class="d-flex align-items-center">
                        <i class="ki-duotone ki-setting-2 fs-2 text-warning me-3"><span class="path1"></span><span class="path2"></span></i>
                        <span class="fw-bold text-gray-800">Kamar {{ room.room_number }}</span>
                      </div>
                    </td>
                    <td class="text-end">
                      <span class="badge badge-light-warning">{{ room.type }}</span>
                    </td>
                  </tr>
                  <tr v-if="reportData.rooms.maintenance_rooms.length === 0">
                    <td colspan="2" class="text-center text-muted py-5">
                       <i class="ki-duotone ki-check-circle fs-2 text-success me-2"><span class="path1"></span><span class="path2"></span></i>
                       Tidak ada kamar yang rusak.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL EXPORT COMPONENT -->
    <LaporanHotelExport 
      v-model:visible="showExportModal" 
      :current-filter="filter" 
    />

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import ApiService from "@/core/services/ApiService"; 
import LaporanHotelExport from './LaporanHotelExport.vue'; // IMPORT MODAL

// --- STATE ---
const loading = ref(false);
const showExportModal = ref(false); // STATE MODAL
const reportData = ref<any>(null);

const today = new Date();
const filter = ref({
  period: 'monthly',
  month: today.getMonth() + 1,
  year: today.getFullYear(),
  date: today.toISOString().split('T')[0]
});

// Menggunakan Array of Objects untuk mencegah error pada loop El-Option
const months = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' }
];

const years = Array.from({ length: 5 }, (_, i) => today.getFullYear() - i); // 5 tahun terakhir

// --- API FETCH ---
const fetchData = async () => {
  loading.value = true;
  try {
    const params = {
      period: filter.value.period,
      month: filter.value.month,
      year: filter.value.year,
      date: filter.value.period === 'daily' ? filter.value.date : undefined
    };

    const response = await ApiService.query("reports/hotel", params);
    reportData.value = response.data.data;
  } catch (error) {
    console.error("Gagal mengambil laporan hotel:", error);
  } finally {
    setTimeout(() => { loading.value = false; }, 400);
  }
};

// --- CHART CONFIGURATION (APEXCHARTS DENGAN 4 GARIS DIKEMBALIKAN) ---
const chartSeries = computed(() => {
  if (!reportData.value) return [];
  return [
    { name: 'Total Pendapatan', data: reportData.value.charts.total },
    { name: 'Kamar', data: reportData.value.charts.revenue },
    { name: 'Restoran', data: reportData.value.charts.restaurant },
    { name: 'Laundry', data: reportData.value.charts.laundry }
  ];
});

const chartOptions = computed(() => {
  const labels = reportData.value?.charts?.labels || [];
  return {
    chart: { type: 'area', height: 350, toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#F68B1E', '#50CD89', '#009EF7', '#7239EA'], 
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3 },
    xaxis: {
      categories: labels,
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: { style: { colors: '#99A1B7', fontSize: '12px' } } 
    },
    yaxis: {
      labels: {
        style: { colors: '#99A1B7', fontSize: '12px' }, 
        formatter: (val: number) => `Rp ${Number(val).toLocaleString('id-ID')}`
      }
    },
    legend: { 
      show: true, 
      position: 'top', 
      horizontalAlign: 'right',
      labels: {
        colors: '#99A1B7'
      }
    },
    grid: { borderColor: '#E4E6EF', strokeDashArray: 4, yaxis: { lines: { show: true } } },
    fill: {
      type: 'gradient',
      gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 90, 100] }
    },
    tooltip: {
      theme: 'dark', 
      y: { formatter: (val: number) => `Rp ${Number(val).toLocaleString('id-ID')}` }
    }
  };
});

// --- HELPERS ---
const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0);
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
/* ========================
   ORANGE THEME COLORS
   ======================== */
.text-orange { color: #F68B1E !important; }
.bg-orange { background-color: #F68B1E !important; }
.bg-light-orange { background-color: rgba(246, 139, 30, 0.1) !important; }
.border-orange { border-color: #F68B1E !important; }

.bg-orange-gradient {
  background: linear-gradient(135deg, #F68B1E 0%, #d87614 100%) !important;
}

/* Penyesuaian spesifik Dark Mode untuk Title Grafik */
[data-bs-theme="dark"] .dark-mode-title {
  color: #FFFFFF !important;
}

/* ========================
   TABLE & CARDS ENHANCEMENTS
   ======================== */
.overlay-loading {
  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(3px);
  top: 0; left: 0;
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