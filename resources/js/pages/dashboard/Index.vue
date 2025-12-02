<template>
  <div v-if="loading.stats" class="row g-5 g-xl-8">
    <div v-for="n in 8" :key="n" class="col-md-6 col-lg-3 mb-5">
      <div class="card card-flush h-100 placeholder-glow" style="background-color: #f0f0f0;">
        <div class="card-body">
          <span class="placeholder col-6"></span>
          <span class="placeholder w-75"></span>
          <span class="placeholder w-100"></span>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="row g-5 g-xl-8">
    <div class="col-xl-4">
      <div class="card card-flush bg-primary hoverable h-100">
        <div class="card-header pt-5">
          <div class="card-title d-flex flex-column">
            <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{ formatCurrency(stats?.todays_revenue) }}</span>
            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Pendapatan Hari Ini</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card card-flush bg-success hoverable h-100">
        <div class="card-header pt-5">
          <div class="card-title d-flex flex-column">
            <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{ formatCurrency(stats?.monthly_revenue) }}</span>
            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Pendapatan Bulan Ini</span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4">
      <div class="card card-flush bg-dark hoverable h-100">
        <div class="card-header pt-5">
          <div class="card-title d-flex flex-column">
            <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{ formatCurrency(stats?.yearly_revenue) }}</span>
            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Pendapatan Tahun Ini</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card card-flush h-100">
        <div class="card-body text-center d-flex flex-column justify-content-center">
          <div class="fs-4x fw-bold text-danger">{{ stats?.occupied_rooms_count }}</div>
          <div class="fs-6 fw-semibold text-muted mt-2">Kamar Terisi</div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-flush h-100">
        <div class="card-body text-center d-flex flex-column justify-content-center">
          <div class="fs-4x fw-bold text-warning">{{ stats?.rooms_needing_cleaning }}</div>
          <div class="fs-6 fw-semibold text-muted mt-2">Perlu Dibersihkan</div>
        </div>
      </div>
    </div>

    <div class="col-md-6 col-lg-3">
      <div class="card card-flush h-100">
        <div class="card-body text-center d-flex flex-column justify-content-center">
          <div class="fs-4x fw-bold text-info">{{ stats?.total_food_orders }}</div>
          <div class="fs-6 fw-semibold text-muted mt-2">Pesanan Makanan</div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card card-flush h-100">
        <div class="card-body text-center d-flex flex-column justify-content-center">
          <div class="fs-4x fw-bold text-primary">{{ stats?.food_items_sold }}</div>
          <div class="fs-6 fw-semibold text-muted mt-2">Item Terjual</div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card card-flush h-md-100">
        <div class="card-header pt-7">
          <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-800">Statistik Penjualan</span>
            <span class="text-gray-400 mt-1 fw-semibold fs-6">Pendapatan Gabungan 7 Hari Terakhir</span>
          </h3>
        </div>
        <div class="card-body pt-5">
          <apexchart v-if="!loading.chart" type="area" :options="chartOptions" :series="chartSeries" height="350"></apexchart>
          <div v-else class="d-flex justify-content-center align-items-center h-350px">
            <span class="spinner-border text-primary"></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, computed } from "vue";
import ApiService from "@/core/services/ApiService";

// Definisikan tipe data yang lebih lengkap
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

export default defineComponent({
  name: "main-dashboard",
  setup() {
    const stats = ref<DashboardStats | null>(null);
    const chartData = ref<ChartData>({ labels: [], series: [] });
    const loading = ref({ stats: true, chart: true });

    const getStats = async () => {
      try {
        loading.value.stats = true;
        const { data } = await ApiService.get("dashboard-stats");
        stats.value = data.data;
      } catch (error) {
        console.error("Gagal memuat statistik dasbor:", error);
      } finally {
        loading.value.stats = false;
      }
    };

    const getChartData = async () => {
      try {
        loading.value.chart = true;
        const { data } = await ApiService.get("sales-chart-data");
        chartData.value = data.data;
      } catch (error) {
        console.error("Gagal memuat data grafik:", error);
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
      }).format(value);
    };

    const chartOptions = computed(() => ({
      chart: { fontFamily: "inherit", type: "area", height: 350, toolbar: { show: false } },
      xaxis: { categories: chartData.value.labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: "#9899A6", fontSize: "12px" } } },
      yaxis: { labels: { style: { colors: "#9899A6", fontSize: "12px" }, formatter: (val: number) => "Rp " + val.toLocaleString('id-ID') } },
      fill: { type: "solid", opacity: 0.3 },
      stroke: { curve: "smooth", show: true, width: 3, colors: ["#009EF7"] },
      dataLabels: { enabled: false },
      markers: { colors: ["#009EF7"], strokeColors: ["#F1FAFF"], strokeWidth: 3 },
      tooltip: { y: { formatter: (val: number) => "Rp " + val.toLocaleString('id-ID') } },
    }));

    const chartSeries = computed(() => [{ name: "Pendapatan", data: chartData.value.series }]);

    onMounted(() => { 
      getStats();
      getChartData();
    });

    return { stats, loading, formatCurrency, chartOptions, chartSeries };
  },
});
</script>
