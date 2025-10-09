<template>
  <div>
    <div v-if="isLoading" class="text-center py-20">
      <span class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></span>
      <p class="mt-4 fs-5 text-muted">Memeriksa status check-in...</p>
    </div>

    <div v-else-if="checkInData && checkInData.room">
      <h1 class="mb-5 display-6">Selamat Datang, {{ checkInData.booking?.user?.name || 'Tamu' }}!</h1>
      <div class="card shadow-sm">
        <div class="card-body p-lg-10">
          <div class="d-flex align-items-center mb-5">
            <i class="ki-duotone ki-key fs-3x text-primary me-5"><span class="path1"></span><span class="path2"></span></i>
            <div>
              <p class="fs-5 mb-0">Anda saat ini sedang check-in di kamar:</p>
              <h2 class="fw-bold display-5 mb-0">{{ checkInData.room?.room_number }} - {{ checkInData.room?.type }}</h2>
            </div>
          </div>
          <div class="separator separator-dashed my-8"></div>
          <div class="row g-5">
            <div class="col-md-6">
              <p class="text-muted mb-2">
                <i class="ki-duotone ki-calendar-tick fs-4 me-2"><span class="path1"></span><span class="path2"></span></i>
                Check-in
              </p>
              <p class="fw-semibold fs-5">{{ formatDate(checkInData.booking?.check_in_date) }}</p>
            </div>
            <div class="col-md-6">
              <p class="text-muted mb-2">
                <i class="ki-duotone ki-calendar-remove fs-4 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                Check-out
              </p>
              <p class="fw-semibold fs-5">{{ formatDate(checkInData.booking?.check_out_date) }}</p>
            </div>
          </div>
          <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6 mt-8">
            <i class="ki-duotone ki-information-5 fs-2tx text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <div class="d-flex flex-stack flex-grow-1">
              <div class="fw-semibold">
                <div class="fs-6 text-gray-700">Gunakan menu di samping untuk memesan makanan atau meminta layanan kamar langsung ke kamar Anda.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else>
      <div class="card shadow-sm">
        <div class="card-body text-center d-flex flex-column justify-content-center p-10" style="min-height: 400px;">
            <h3 class="fw-bold fs-2x text-dark">Anda Belum Check-in</h3>
            <p class="text-muted fs-5 mb-7">
              Sepertinya Anda belum memiliki sesi menginap yang aktif. <br>
              Silakan lakukan booking untuk menikmati layanan kami.
            </p>
            <router-link to="/user/booking" class="btn btn-primary mx-auto">
              <i class="ki-duotone ki-calendar-add fs-2 me-2">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
              </i>
              Cari Kamar Sekarang
            </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from '@/libs/axios';
import { useAuthStore } from "@/stores/auth";

const checkInData = ref<any>(null);
const isLoading = ref(true);
const authStore = useAuthStore();

const fetchCheckInStatus = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/my-check-in-status');
    checkInData.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil status check-in:", error);
    checkInData.value = null;
  } finally {
    isLoading.value = false;
  }
};

const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const options: Intl.DateTimeFormatOptions = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

onMounted(() => {
  if (authStore.user) {
    fetchCheckInStatus();
  } else {
    const unwatch = watch(() => authStore.user, (newUser) => {
      if (newUser) {
        fetchCheckInStatus();
        unwatch();
      }
    });
  }
});
</script>
