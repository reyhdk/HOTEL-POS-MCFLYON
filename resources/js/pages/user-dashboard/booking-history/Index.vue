<template>
  <div class="card shadow-sm">
    <div class="card-header">
      <h3 class="card-title">Riwayat Pemesanan Saya</h3>
    </div>
    <div class="card-body">
      <div v-if="loading" class="text-center text-muted py-10">
        <span class="spinner-border text-primary"></span>
        <p class="mt-4">Memuat riwayat...</p>
      </div>
      <div v-else-if="bookings.length === 0" class="text-center text-muted py-10">
        <i class="ki-duotone ki-abstract-11 fs-3x mb-4"></i>
        <p class="fw-bold fs-4">Riwayat Kosong</p>
        <p>Anda belum pernah melakukan pemesanan.</p>
      </div>
      
      <div v-else class="row g-5">
        <div v-for="booking in bookings" :key="booking.id" class="col-md-12">
          <div class="card card-flush shadow-sm border">
            <div class="card-body p-5">
              <div class="d-flex flex-column flex-sm-row">
                <div class="symbol symbol-100px symbol-lg-150px me-sm-5 mb-3 mb-sm-0">
                  <img :src="booking.room.image_url || '/media/svg/files/blank-image.svg'" alt="Room Image" class="rounded object-fit-cover" />
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                      <h5 class="fw-bold text-gray-800 mb-1">Kamar {{ booking.room.room_number }} - {{ booking.room.type }}</h5>
                      <span class="text-gray-600 fw-semibold d-block fs-7"> Atas Nama: {{ booking.guest?.name || 'Data Tamu Dihapus' }}
                      </span>
                    </div>
                    <span class="badge" :class="getStatusBadge(booking.status)">{{ getStatusLabel(booking.status) }}</span>
                  </div>
                  
                  <div class="row g-5">
                    <div class="col-sm-4">
                      <div class="fw-semibold text-gray-700">Check-in:</div>
                      <div class="fw-bold">{{ formatDate(booking.check_in_date) }}</div>
                    </div>
                    <div class="col-sm-4">
                      <div class="fw-semibold text-gray-700">Check-out:</div>
                      <div class="fw-bold">{{ formatDate(booking.check_out_date) }}</div>
                    </div>
                    <div class="col-sm-4 text-sm-end">
                      <div class="fw-semibold text-gray-700">Total Biaya:</div>
                      <div class="fw-bold fs-4 text-primary">{{ formatCurrency(booking.total_price) }}</div>
                    </div>
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

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from '@/libs/axios';

// Interface disesuaikan untuk menerima data 'guest'
interface Guest { name: string; }
interface Room { room_number: string; type: string; image_url: string | null; }
interface Booking {
  id: number;
  check_in_date: string;
  check_out_date: string;
  total_price: number;
  status: string;
  room: Room;
  guest: Guest; // <-- Tambahkan ini
}

const bookings = ref<Booking[]>([]);
const loading = ref(true);

const getBookingHistory = async () => {
  try {
    loading.value = true;
    const response = await axios.get('/my-bookings');
    bookings.value = response.data;
  } catch (error) {
    console.error("Gagal mengambil riwayat booking:", error);
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString: string) => {
  if (!dateString) return '...';
  const options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' };
  return new Date(dateString).toLocaleDateString('id-ID', options);
};

const formatCurrency = (value: number) => {
  if (!value || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

const getStatusBadge = (status: string) => ({
  'confirmed': 'badge-light-primary',
  'completed': 'badge-light-success',
  'cancelled': 'badge-light-danger',
}[status] || 'badge-light');

const getStatusLabel = (status: string) => status.charAt(0).toUpperCase() + status.slice(1);

onMounted(() => {
  getBookingHistory();
});
</script>