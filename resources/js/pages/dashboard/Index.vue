  <template>
    <div v-if="loading.stats" class="row g-5 g-xl-10 mb-5 mb-xl-10">
      <div v-for="n in 4" :key="n" class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
        <div class="card card-flush h-md-50 mb-xl-10">
          <div class="card-header pt-5">
            <div class="card-title d-flex flex-column">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="row g-5 g-xl-10 mb-5 mb-xl-10">
      <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
        <div class="card card-flush h-md-50 mb-5 mb-xl-10">
          <div class="card-header pt-5">
            <div class="card-title d-flex flex-column">
              <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ formatCurrency(stats?.todays_revenue) }}</span>
              <span class="text-gray-400 pt-1 fw-semibold fs-6">Pendapatan Hari Ini</span>
            </div>
          </div>
        </div>
        <div class="card card-flush h-md-50 mb-5 mb-xl-10">
          <div class="card-header pt-5">
            <div class="card-title d-flex flex-column">
              <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ stats?.todays_orders_count }}</span>
              <span class="text-gray-400 pt-1 fw-semibold fs-6">Pesanan Baru Hari Ini</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
        <div class="card card-flush h-md-50 mb-5 mb-xl-10">
          <div class="card-header pt-5">
            <div class="card-title d-flex flex-column">
              <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ stats?.occupied_rooms_count }}</span>
              <span class="text-gray-400 pt-1 fw-semibold fs-6">Kamar Terisi</span>
            </div>
          </div>
        </div>
        <div class="card card-flush h-md-50 mb-5 mb-xl-10">
          <div class="card-header pt-5">
            <div class="card-title d-flex flex-column">
              <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ stats?.total_rooms }}</span>
              <span class="text-gray-400 pt-1 fw-semibold fs-6">Total Kamar</span>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xxl-6">
        <div class="card card-flush h-md-100">
          <div class="card-header pt-7">
            <h3 class="card-title align-items-start flex-column">
              <span class="card-label fw-bold text-gray-800">Statistik Penjualan</span>
              <span class="text-gray-400 mt-1 fw-semibold fs-6">Pendapatan 7 Hari Terakhir</span>
            </h3>
          </div>
          <div class="card-body pt-5">
            <apexchart v-if="!loading.chart" type="area" :options="chartOptions" :series="chartSeries" height="350"></apexchart>
            <div v-else class="d-flex justify-content-center align-items-center h-350px">
              <span class="text-muted">Memuat data grafik...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script lang="ts">
  import { defineComponent, ref, onMounted, computed } from "vue";
  import ApiService from "@/core/services/ApiService";

  // Definisikan tipe data untuk kejelasan kode
  interface DashboardStats {
    todays_revenue: number;
    todays_orders_count: number;
    occupied_rooms_count: number;
    total_rooms: number;
  }
  interface ChartData {
    labels: string[];
    series: number[];
  }

  export default defineComponent({
    name: "main-dashboard",
    components: {}, // Tidak ada komponen child, jadi kosong
    setup() {
      // --- VARIABEL REAKTIF ---
      // Inisialisasi 'stats' sebagai null untuk menandakan data belum ada
      const stats = ref<DashboardStats | null>(null);
      const chartData = ref<ChartData>({ labels: [], series: [] });
      const loading = ref({ stats: true, chart: true });

      // --- FUNGSI-FUNGSI ---

      // Fungsi untuk mengambil data statistik utama
      const getStats = async () => {
        try {
          loading.value.stats = true;
          const response = await ApiService.get("dashboard-stats");
          // Mengambil data dari 'response.data.data' sesuai struktur API
          stats.value = response.data.data;
        } catch (error) {
          console.error("Gagal memuat statistik dasbor:", error);
          // Beri nilai default jika terjadi error agar aplikasi tidak crash
          stats.value = { todays_revenue: 0, todays_orders_count: 0, occupied_rooms_count: 0, total_rooms: 0 };
        } finally {
          loading.value.stats = false;
        }
      };

      // Fungsi untuk mengambil data grafik
      const getChartData = async () => {
        try {
          loading.value.chart = true;
          const response = await ApiService.get("sales-chart-data");
          chartData.value = response.data.data;
        } catch (error) {
          console.error("Gagal memuat data grafik:", error);
        } finally {
          loading.value.chart = false;
        }
      };

      // Fungsi untuk memformat angka menjadi format mata uang Rupiah
      const formatCurrency = (value: number | undefined) => {
        if (value === undefined || isNaN(value)) return "Rp 0";
        return new Intl.NumberFormat("id-ID", {
          style: "currency",
          currency: "IDR",
          minimumFractionDigits: 0,
        }).format(value);
      };

      // --- KONFIGURASI GRAFIK ---

      // Opsi konfigurasi untuk ApexCharts
      const chartOptions = computed(() => {
        const labelColor = "#9899A6";
        const baseColor = "#009EF7";
        const lightColor = "#F1FAFF";

        return {
          chart: { fontFamily: "inherit", type: "area", height: 350, toolbar: { show: false } },
          xaxis: {
            categories: chartData.value.labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: labelColor, fontSize: "12px" } },
          },
          yaxis: {
            labels: {
              style: { colors: labelColor, fontSize: "12px" },
              formatter: (val: number) => "Rp " + val.toLocaleString('id-ID'),
            },
          },
          fill: { type: "solid", opacity: 0.3 },
          stroke: { curve: "smooth", show: true, width: 3, colors: [baseColor] },
          dataLabels: { enabled: false },
          markers: { colors: [baseColor], strokeColors: [lightColor], strokeWidth: 3 },
          tooltip: { y: { formatter: (val: number) => "Rp " + val.toLocaleString('id-ID') } },
        };
      });

      // Data series untuk ApexCharts
      const chartSeries = computed(() => {
        return [{ name: "Pendapatan", data: chartData.value.series }];
      });

      // --- LIFECYCLE HOOK ---
      // Panggil kedua fungsi pengambilan data saat komponen pertama kali dimuat
      onMounted(() => {
        getStats();
        getChartData();
      });

      // --- RETURN ---
      // Semua variabel dan fungsi yang perlu diakses di template harus di-return di sini
      return {
        stats,
        loading,
        formatCurrency,
        chartOptions,
        chartSeries,
      };
    },
  });
  </script>