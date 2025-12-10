<template>
  <div class="dashboard-container">
    
    <div v-if="loading.stats" class="row g-5 g-xl-10">
       <div class="col-12 d-flex justify-content-center align-items-center h-400px">
          <div class="d-flex flex-column align-items-center">
             <div class="spinner-grow text-orange mb-3" role="status"></div>
             <span class="text-gray-500 fw-bold fs-6">Menyiapkan data...</span>
          </div>
       </div>
    </div>

    <div v-else class="d-flex flex-column gap-7">
        
        <div class="d-flex flex-stack flex-wrap gap-4">
            <div class="d-flex flex-column">
                <h1 class="text-gray-900 fw-bolder m-0 fs-3">Dashboard Performance</h1>
                <span class="text-gray-500 fs-7 fw-semibold">Ringkasan operasional hotel</span>
            </div>

            <div class="nav-group nav-group-fluid">
                <button 
                    v-for="period in periods" 
                    :key="period.value"
                    @click="activePeriod = period.value"
                    :class="['btn btn-sm fw-bold px-4 transition-all', activePeriod === period.value ? 'btn-orange text-white shadow-sm' : 'btn-light text-gray-600 hover-text-orange']"
                >
                    {{ period.label }}
                </button>
            </div>
        </div>

        <div class="row g-5 g-xl-10">
            
            <div class="col-md-6 col-xl-4 animate-item" style="--delay: 0.1s">
                <div class="card card-flush h-md-100 border-0 shadow-sm bg-gradient-orange text-white">
                    <div class="card-body py-8 d-flex flex-column justify-content-center">
                        <span class="opacity-75 fw-bold fs-7 mb-1">{{ currentStats.revenueLabel }}</span>
                        <span class="fs-2x fw-bolder lh-1 mb-4">{{ formatCurrency(currentStats.revenue) }}</span>
                        
                        <div class="d-flex align-items-center bg-white bg-opacity-10 rounded p-2 mt-2" style="width: fit-content;">
                             <span class="fw-bold fs-7">{{ currentStats.orders }} Transaksi</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4 animate-item" style="--delay: 0.2s">
                <div class="card card-flush h-md-100 border-0 shadow-sm theme-card">
                    <div class="card-body d-flex align-items-center justify-content-between py-6 px-8">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-bold fs-7 mb-1">Okupansi Kamar</span>
                            <span class="fs-3x fw-bolder text-gray-900 lh-1">{{ occupancyPercentage }}<small class="fs-4">%</small></span>
                            <div class="d-flex flex-column mt-3">
                                <span class="text-gray-400 fs-8 fw-bold">Terisi: <span class="text-gray-800">{{ stats?.occupied_rooms_count }}</span></span>
                                <span class="text-gray-400 fs-8 fw-bold">Kosong: <span class="text-gray-800">{{ (stats?.total_rooms || 0) - (stats?.occupied_rooms_count || 0) }}</span></span>
                            </div>
                        </div>
                        
                        <div class="position-relative d-flex justify-content-center align-items-center" style="height: 120px; width: 120px;">
                            <apexchart type="radialBar" :options="occupancyChartOptions" :series="[occupancyPercentage]" height="160"></apexchart>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-4 animate-item" style="--delay: 0.3s">
                <div class="card card-flush h-md-100 border-0 shadow-sm theme-card">
                    <div class="card-body py-8 d-flex flex-column justify-content-center">
                        <span class="text-gray-500 fw-bold fs-7 mb-1">{{ currentStats.foodLabel }}</span>
                        <div class="d-flex align-items-baseline mb-4">
                            <span class="fs-2x fw-bolder text-gray-900 lh-1 me-2">{{ currentStats.foodOrders }}</span>
                            <span class="text-gray-400 fw-semibold fs-7">Pesanan</span>
                        </div>
                        
                        <div class="bg-light rounded p-3 d-flex align-items-center justify-content-between border border-gray-200 border-dashed">
                            <span class="text-gray-600 fw-bold fs-7">Item Terjual</span>
                            <span class="badge badge-light-orange fw-bolder fs-6 text-orange">{{ currentStats.foodItems }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 g-xl-10">
            
            <div class="col-xl-8 animate-item" style="--delay: 0.4s">
                <div class="card card-flush h-100 border-0 shadow-sm theme-card">
                    <div class="card-header pt-7 pb-0 border-0">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Trend Penjualan</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-7">Statistik 7 hari terakhir</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0 px-5 pb-5">
                        <div v-if="loading.chart" class="d-flex justify-content-center align-items-center h-300px">
                             <span class="spinner-border text-orange"></span>
                        </div>
                        <apexchart v-else type="area" :options="salesChartOptions" :series="salesChartSeries" height="300"></apexchart>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 animate-item" style="--delay: 0.5s">
                <div class="card card-flush h-100 border-0 shadow-sm theme-card">
                    <div class="card-header pt-7 mb-2 border-0">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Housekeeping</span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-7">Status Kebersihan Kamar</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex flex-column gap-3">
                            
                            <div class="d-flex flex-column bg-light-warning rounded-3 p-5 border-start border-4 border-warning">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-gray-800 fs-6">Perlu Dibersihkan</span>
                                    <span class="fw-bolder text-warning fs-2x lh-1">{{ stats?.rooms_needing_cleaning }}</span>
                                </div>
                                <div class="progress h-6px bg-warning bg-opacity-25 w-100 mt-2">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>

                            <div class="d-flex flex-column bg-light-success rounded-3 p-5 border-start border-4 border-success">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-gray-800 fs-6">Siap Digunakan</span>
                                    <span class="fw-bolder text-success fs-2x lh-1">{{ (stats?.total_rooms || 0) - (stats?.occupied_rooms_count || 0) - (stats?.rooms_needing_cleaning || 0) }}</span>
                                </div>
                                <div class="progress h-6px bg-success bg-opacity-25 w-100 mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from "vue";
import ApiService from "@/core/services/ApiService";

interface DashboardStats {
  todays_revenue: number;
  monthly_revenue: number;
  yearly_revenue: number;
  todays_orders_count: number;
  total_food_orders: number;
  food_items_sold: number;
  occupied_rooms_count: number;
  rooms_needing_cleaning: number;
  total_rooms: number;
}
interface ChartData {
  labels: string[];
  series: number[];
}
type PeriodType = 'today' | 'month' | 'year';

export default defineComponent({
  name: "ModernDashboard",
  setup() {
    const stats = ref<DashboardStats | null>(null);
    const chartData = ref<ChartData>({ labels: [], series: [] });
    const loading = ref({ stats: true, chart: true });
    
    const activePeriod = ref<PeriodType>('today');
    const periods: { label: string; value: PeriodType }[] = [
        { label: 'Harian', value: 'today' },
        { label: 'Bulanan', value: 'month' },
        { label: 'Tahunan', value: 'year' },
    ];

    const getStats = async () => {
      try {
        loading.value.stats = true;
        const { data } = await ApiService.get("dashboard-stats");
        stats.value = data.data || data;
      } catch (error) {
        console.error("Error:", error);
      } finally {
        loading.value.stats = false;
      }
    };

    const getChartData = async () => {
      try {
        loading.value.chart = true;
        const { data } = await ApiService.get("sales-chart-data");
        chartData.value = data.data || data;
      } catch (error) {
        console.error("Error:", error);
      } finally {
        loading.value.chart = false;
      }
    };

    const formatCurrency = (value: number | undefined) => {
      if (value === undefined || isNaN(value)) return "Rp 0";
      return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
      }).format(value);
    };

    const currentStats = computed(() => {
        const s = stats.value;
        if (!s) return { revenue: 0, revenueLabel: 'Pendapatan', orders: 0, foodOrders: 0, foodLabel: 'Pesanan', foodItems: 0 };

        if (activePeriod.value === 'month') {
            return {
                revenue: s.monthly_revenue,
                revenueLabel: 'Pendapatan Bulan Ini',
                orders: s.todays_orders_count * 30, 
                foodOrders: s.total_food_orders,
                foodLabel: 'Pesanan Total',
                foodItems: s.food_items_sold
            };
        } else if (activePeriod.value === 'year') {
            return {
                revenue: s.yearly_revenue,
                revenueLabel: 'Pendapatan Tahun Ini',
                orders: s.todays_orders_count * 365,
                foodOrders: s.total_food_orders,
                foodLabel: 'Pesanan Total',
                foodItems: s.food_items_sold
            };
        } else {
            return {
                revenue: s.todays_revenue,
                revenueLabel: 'Pendapatan Hari Ini',
                orders: s.todays_orders_count,
                foodOrders: s.todays_orders_count,
                foodLabel: 'Pesanan Hari Ini',
                foodItems: s.food_items_sold
            };
        }
    });

    const occupancyPercentage = computed(() => {
        if (!stats.value || stats.value.total_rooms === 0) return 0;
        return Math.round((stats.value.occupied_rooms_count / stats.value.total_rooms) * 100);
    });

    const salesChartOptions = computed(() => ({
      chart: { fontFamily: "inherit", type: "area", height: 300, toolbar: { show: false }, zoom: { enabled: false } },
      xaxis: { 
          categories: chartData.value.labels, 
          axisBorder: { show: false }, 
          axisTicks: { show: false }, 
          labels: { style: { colors: "#A1A5B7", fontSize: "12px" }, offsetY: 5 },
          crosshairs: { show: false }
      },
      yaxis: { 
          labels: { 
              style: { colors: "#A1A5B7", fontSize: "12px" }, 
              formatter: (val: number) => val >= 1000000 ? (val/1000000).toFixed(1) + 'jt' : (val/1000).toFixed(0) + 'rb'
          } 
      },
      fill: { type: "gradient", gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05, stops: [0, 90, 100] } },
      stroke: { curve: "smooth", show: true, width: 3, colors: ["#F68B1E"] }, 
      dataLabels: { enabled: false },
      grid: { borderColor: "#eff2f5", strokeDashArray: 4, padding: { left: 10 } },
      markers: { strokeColors: ["#ffffff"], strokeWidth: 3 },
      colors: ["#F68B1E"],
      tooltip: { theme: 'light', y: { formatter: (val: number) => formatCurrency(val) } } 
    }));
    const salesChartSeries = computed(() => [{ name: "Pendapatan", data: chartData.value.series }]);

    // FIXED RADIAL CHART: Clean & Simple
    const occupancyChartOptions = computed(() => ({
        chart: { height: 160, type: "radialBar", fontFamily: "inherit" },
        plotOptions: {
            radialBar: {
                hollow: { margin: 0, size: "55%" },
                dataLabels: { show: false }, // WAJIB FALSE AGAR TIDAK DOUBLE
                track: { background: "#FFF4E6", strokeWidth: '100%' }
            }
        },
        colors: ["#F68B1E"],
        stroke: { lineCap: "round" }
    }));

    onMounted(() => { 
      getStats();
      getChartData();
    });

    return { 
        stats, loading, activePeriod, periods, currentStats,
        formatCurrency, occupancyPercentage,
        salesChartOptions, salesChartSeries, occupancyChartOptions
    };
  },
});
</script>

<style scoped>
/* COLORS */
.text-orange { color: #F68B1E !important; }
.bg-orange { background-color: #F68B1E !important; }
.btn-orange { background-color: #F68B1E !important; color: #fff !important; }
.badge-light-orange { background-color: #FFF4E6 !important; color: #F68B1E !important; }
.hover-text-orange:hover { color: #F68B1E !important; }

/* UTILS */
.nav-group { background-color: #F5F8FA; padding: 4px; border-radius: 8px; display: inline-flex; }
.transition-all { transition: all 0.3s ease; }
.bg-gradient-orange { background: linear-gradient(135deg, #F68B1E 0%, #FFB347 100%); }

/* DARK MODE */
[data-bs-theme="dark"] .theme-card { background-color: #1e1e2d !important; color: #ffffff; }
[data-bs-theme="dark"] .bg-light { background-color: #2B2B40 !important; border-color: #323248 !important; }
[data-bs-theme="dark"] .text-gray-900 { color: #ffffff !important; }
[data-bs-theme="dark"] .text-gray-500 { color: #7e8299 !important; }
[data-bs-theme="dark"] .bg-light-warning { background-color: rgba(255, 199, 0, 0.1) !important; }
[data-bs-theme="dark"] .bg-light-success { background-color: rgba(80, 205, 137, 0.1) !important; }
[data-bs-theme="dark"] .nav-group { background-color: #151521; }
</style>