<template>
  <div>
    <div v-if="isLoading" class="text-center py-10">
      <span class="spinner-border text-primary"></span>
      <p class="mt-4">Memeriksa status check-in...</p>
    </div>

    <div v-else-if="checkInData">
      <h1 class="mb-5">Selamat Datang, {{ checkInData.booking.user?.name }}!</h1>
      <div class="card">
        <div class="card-body">
          <p class="fs-4">Anda saat ini sedang check-in di kamar:</p>
          <h2 class="fw-bold text-primary">{{ checkInData.room.room_number }} - {{ checkInData.room.type }}</h2>
          <hr class="my-5">
          <p>
            <strong>Check-in:</strong> {{ formatDate(checkInData.booking.check_in_date) }} <br>
            <strong>Check-out:</strong> {{ formatDate(checkInData.booking.check_out_date) }}
          </p>
          <p class="mt-5">Gunakan menu di samping untuk memesan makanan atau layanan kamar.</p>
        </div>
      </div>
    </div>

    <div v-else>
      <h1 class="mb-3">Selamat Datang, {{ authStore.user?.name }}!</h1>
      <p class="fs-5 text-muted mb-10">Nikmati pengalaman menginap Anda. Semua yang Anda butuhkan ada di sini.</p>
      <div class="card">
        <div class="card-body text-center">
            <img src="/media/icons/duotune/maps/map001.svg" alt="Calendar Icon" class="mb-5" style="height: 80px;" />
            <h3 class="fw-bold">Anda Belum Check-in</h3>
            <p class="text-muted">Silakan lakukan check-in di resepsionis untuk mengaktifkan semua fitur.</p>
            <router-link to="/booking-history" class="btn btn-primary">
              Lihat Riwayat Booking Saya
            </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
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

// Fungsi helper untuk format tanggal
const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const options: Intl.DateTimeFormatOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  // ### PERBAIKAN DI SINI ###
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

onMounted(() => {
  fetchCheckInStatus();
});
</script>