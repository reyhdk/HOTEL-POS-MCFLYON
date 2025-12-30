<template>
  <div>
    <div v-if="isLoading" class="text-center py-20">
      <span class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></span>
      <p class="mt-4 fs-5 text-muted">Memeriksa status check-in...</p>
    </div>

    <div v-else-if="checkInData && checkInData.room">
      <!-- UI Check-in Active - TIDAK BERUBAH -->
      <div class="card shadow-sm mb-8 bg-light-primary border-primary border-dashed">
        <div class="card-body p-lg-10">
          <div class="d-flex align-items-center mb-5">
             <div class="symbol symbol-50px symbol-circle me-5">
                <span class="symbol-label bg-primary text-inverse-primary fs-2 fw-bold">
                    <i class="ki-duotone ki-key fs-1"><span class="path1"></span><span class="path2"></span></i>
                </span>
            </div>
            <div>
              <p class="fs-6 text-muted mb-0">Selamat Datang,</p>
              <h2 class="fw-bold display-6 mb-0">{{ checkInData.booking?.user?.name || checkInData.guest?.name || 'Tamu Terhormat' }}</h2>
            </div>
          </div>
          
          <div class="separator separator-dashed border-primary opacity-25 my-5"></div>

          <div class="row g-5">
            <div class="col-md-6">
               <div class="d-flex align-items-center bg-white rounded p-4 shadow-sm h-100">
                    <i class="ki-duotone ki-home fs-2x text-primary me-4"></i>
                    <div>
                        <div class="fs-7 text-muted">Kamar Anda</div>
                        <div class="fs-3 fw-bold text-dark">{{ checkInData.room?.room_number }} <span class="badge badge-light-primary fs-7 ms-2">{{ checkInData.room?.type }}</span></div>
                    </div>
               </div>
            </div>
             <div class="col-md-6">
               <div class="d-flex align-items-center bg-white rounded p-4 shadow-sm h-100">
                    <i class="ki-duotone ki-calendar-tick fs-2x text-danger me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <div>
                        <div class="fs-7 text-muted">Jadwal Check-out</div>
                        <div class="fs-4 fw-bold text-dark">{{ formatDate(checkInData.booking?.check_out_date) }}</div>
                    </div>
               </div>
            </div>
          </div>
        </div>
      </div>

      <h3 class="fw-bold mb-5">Layanan Kamar</h3>
      <div class="row g-5 g-xl-8">
        <div class="col-6 col-md-4 col-lg-3">
            <router-link to="/restaurant" class="card h-100 hover-elevate-up shadow-sm text-decoration-none">
                <div class="card-body d-flex flex-column align-items-center text-center justify-content-center p-5">
                    <div class="symbol symbol-60px mb-4 bg-light-warning">
                        <span class="symbol-label">
                            <i class="ki-duotone ki-coffee fs-2x text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
                        </span>
                    </div>
                    <span class="fs-6 fw-bold text-gray-800">Restoran</span>
                </div>
            </router-link>
        </div>
        
        <div class="col-6 col-md-4 col-lg-3">
            <router-link to="/my-bill" class="card h-100 hover-elevate-up shadow-sm text-decoration-none">
                <div class="card-body d-flex flex-column align-items-center text-center justify-content-center p-5">
                    <div class="symbol symbol-60px mb-4 bg-light-success">
                        <span class="symbol-label">
                            <i class="ki-duotone ki-bill fs-2x text-success"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
                        </span>
                    </div>
                    <span class="fs-6 fw-bold text-gray-800">Tagihan</span>
                </div>
            </router-link>
        </div>
      </div>
    </div>

    <div v-else>
      <!-- UI Belum Check-in - TIDAK BERUBAH -->
      <div class="card shadow-sm">
        <div class="card-body text-center d-flex flex-column justify-content-center p-10 py-20" style="min-height: 400px;">
            <div class="mb-10">
                <i class="ki-duotone ki-calendar-add fs-5x text-muted"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
            </div>
            
            <h3 class="fw-bold fs-2x text-dark mb-2">Anda Belum Check-in</h3>
            <p class="text-muted fs-5 mb-10">
              Sepertinya Anda belum memiliki sesi menginap yang aktif saat ini. <br>
              Silakan lakukan pemesanan kamar untuk menikmati layanan kami.
            </p>
            
            <router-link to="/user/booking" class="btn btn-primary btn-lg mx-auto mw-300px">
              <i class="ki-duotone ki-magnifier fs-2 me-2">
                <span class="path1"></span><span class="path2"></span>
              </i>
              Cari & Pesan Kamar
            </router-link>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import axios from '@/libs/axios';
import { useAuthStore } from "@/stores/auth";

const checkInData = ref<any>(null);
const isLoading = ref(true);
const authStore = useAuthStore();

// ✅ PERBAIKAN: Variable untuk interval refresh
let refreshInterval: any = null;

// --- API: Cek Status Checkin ---
const fetchCheckInStatus = async () => {
  if (!authStore.user) {
    isLoading.value = false;
    return;
  }

  try {
    isLoading.value = true;
    console.log('🔍 Fetching check-in status for user:', authStore.user.id);
    
    const response = await axios.get('/my-check-in-status');
    checkInData.value = response.data;
    
    console.log('✅ Check-in data received:', response.data);
  } catch (error) {
    console.error("❌ Gagal mengambil status check-in:", error);
    checkInData.value = null;
  } finally {
    isLoading.value = false;
  }
};

// --- Helper: Format Tanggal ---
const formatDate = (dateString: string) => {
  if (!dateString) return '-';
  const options: Intl.DateTimeFormatOptions = { 
    weekday: 'long', 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

// ✅ PERBAIKAN: Fungsi untuk start auto-refresh
const startAutoRefresh = () => {
  // Refresh setiap 30 detik untuk deteksi check-in baru
  refreshInterval = setInterval(() => {
    console.log('🔄 Auto-refreshing check-in status...');
    fetchCheckInStatus();
  }, 30000); // 30 detik
};

// ✅ PERBAIKAN: Stop auto-refresh saat component unmount
const stopAutoRefresh = () => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
    refreshInterval = null;
  }
};

// --- Lifecycle ---
onMounted(() => {
  console.log('📍 Dashboard User mounted');
  
  // Cek jika user sudah terload di store
  if (authStore.user) {
    fetchCheckInStatus();
    startAutoRefresh(); // ✅ Mulai auto-refresh
  } else {
    // Tunggu user terload
    const unwatch = watch(() => authStore.user, (newUser) => {
      if (newUser) {
        fetchCheckInStatus();
        startAutoRefresh(); // ✅ Mulai auto-refresh
        unwatch();
      } else {
        isLoading.value = false;
      }
    });
  }
});

// ✅ PERBAIKAN: Cleanup saat component unmount
onBeforeUnmount(() => {
  stopAutoRefresh();
});

// ✅ PERBAIKAN: Expose method untuk parent component
// Jika ada parent yang trigger check-in, bisa manual refresh
defineExpose({
  refreshCheckInStatus: fetchCheckInStatus
});
</script>

<style scoped>
.hover-elevate-up {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-elevate-up:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>