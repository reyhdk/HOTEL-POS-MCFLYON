<template>
  <div class="card mb-5 mb-xl-10 shadow-sm">
    <div class="card-header border-0">
      <h3 class="card-title align-items-start flex-column">
        <span class="card-label fw-bold fs-3 mb-1">Cari Kamar Impianmu</span>
        <span class="text-muted mt-1 fw-semibold fs-7">Pilih tanggal dan preferensimu</span>
      </h3>
    </div>
    <div class="card-body pt-0">
      <div class="row g-3 align-items-end">
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-semibold">Tanggal Check-in:</label>
          <el-date-picker v-model="searchParams.check_in_date" type="date" placeholder="Pilih tanggal" class="w-100" format="DD-MM-YYYY" value-format="YYYY-MM-DD" />
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-semibold">Tanggal Check-out:</label>
          <el-date-picker v-model="searchParams.check_out_date" type="date" placeholder="Pilih tanggal" class="w-100" format="DD-MM-YYYY" value-format="YYYY-MM-DD" />
        </div>

        <div class="col-lg-2 col-md-6">
          <label class="form-label fw-semibold">Jenis Kamar:</label>
          <el-select v-model="searchParams.type" placeholder="Semua Jenis" class="w-100" clearable>
            <el-option label="Standard" value="Standard" />
            <el-option label="Deluxe" value="Deluxe" />
            <el-option label="Suite" value="Suite" />
          </el-select>
        </div>

        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-semibold">Fasilitas:</label>
          <el-select v-model="searchParams.facility_ids" multiple filterable placeholder="Pilih fasilitas..." class="w-100" :loading="isLoadingFacilities" clearable>
            <el-option v-for="facility in allFacilities" :key="facility.id" :label="facility.name" :value="facility.id" />
          </el-select>
        </div>

        <div class="col-lg-1 col-md-12">
          <button @click="searchRooms" class="btn btn-primary w-100" :disabled="isSearchDisabled">
            <i class="ki-duotone ki-magnifier fs-2" v-if="!isLoading"></i>
            <span v-if="isLoading" class="spinner-border spinner-border-sm"></span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header border-0">
      <h3 class="card-title">Hasil Pencarian</h3>
    </div>
    <div class="card-body">
      <div v-if="isLoading" class="text-center py-10">
        <span class="spinner-border text-primary"></span>
        <p class="mt-4">Mencari kamar terbaik untuk Anda...</p>
      </div>

      <div v-else-if="!availableRooms.length && hasSearched" class="text-center py-10">
        <i class="ki-duotone ki-magnifier-r fs-3x text-muted mb-4"></i>
        <p class="fw-bold fs-4">Maaf, Kamar Tidak Ditemukan</p>
        <p class="text-muted">Tidak ada kamar yang tersedia dengan kriteria tersebut. Silakan coba tanggal atau filter lain.</p>
      </div>

      <div v-else-if="!hasSearched" class="text-center py-10">
        <i class="ki-duotone ki-calendar-8 fs-3x text-muted mb-4"></i>
        <p class="fw-bold fs-4">Silakan Pilih Tanggal Menginap</p>
        <p class="text-muted">Pilih tanggal check-in dan check-out untuk melihat kamar yang tersedia.</p>
      </div>

      <div v-else class="row g-5">
        <div v-for="room in availableRooms" :key="room.id" class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm border border-light-subtle card-flush">
            <img :src="room.image_url || '/media/misc/pattern-4.jpg'" class="card-img-top" alt="Room Image" style="height: 200px; object-fit: cover;">
            <div class="card-body d-flex flex-column p-5">
              <span class="badge badge-light-info align-self-start mb-3">{{ room.type }}</span>
              <h5 class="card-title fs-4 fw-bold">{{ room.room_number }}</h5>
              <p class="card-text text-muted flex-grow-1 mb-4">{{ room.description }}</p>

              <div class="mb-4">
                <h6 class="fw-semibold text-gray-700 fs-7 mb-3">Fasilitas:</h6>
                <div v-if="room.facilities && room.facilities.length > 0" class="d-flex flex-wrap gap-3">
                  <div v-for="facility in room.facilities" :key="facility.id" class="symbol symbol-30px" v-tooltip :title="facility.name">
                    <img v-if="facility.icon_url" :src="facility.icon_url" :alt="facility.name" />
                    <span v-else class="symbol-label bg-light-primary text-primary fs-7">{{ facility.name.charAt(0) }}</span>
                  </div>
                </div>
                <div v-else class="text-muted fs-7">
                    Tidak ada fasilitas khusus.
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-auto pt-4 border-top">
                <span class="fw-bold fs-3 text-primary">{{ formatCurrency(room.price_per_night) }}<small class="fs-8 text-muted">/malam</small></span>
                <button @click="openBookingModal(room)" class="btn btn-success">Pesan Sekarang</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <BookingModal :room="selectedRoom" :booking-dates="searchParams" @booking-success="handleBookingSuccess"/>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from '@/libs/axios';
import { toast } from 'vue3-toastify';
import BookingModal from './BookingModal.vue';
import { Modal } from 'bootstrap';

// Interface untuk Fasilitas
interface Facility {
  id: number;
  name: string;
  icon_url: string | null;
}

// State untuk menyimpan parameter pencarian
const searchParams = ref({
  check_in_date: '',
  check_out_date: '',
  facility_ids: [] as number[],
  type: '', // Tambahkan properti type
});

// State lainnya
const availableRooms = ref<any[]>([]);
const selectedRoom = ref<any | null>(null);
const allFacilities = ref<Facility[]>([]);
const isLoadingFacilities = ref(true);
const isLoading = ref(false);
const hasSearched = ref(false); // Untuk membedakan state awal dan state "tidak ditemukan"

// Computed property untuk menonaktifkan tombol pencarian
const isSearchDisabled = computed(() => {
  return isLoading.value || !searchParams.value.check_in_date || !searchParams.value.check_out_date;
});

// Fungsi helper untuk format mata uang
const formatCurrency = (value: number) => {
  if (!value || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

// Fungsi untuk mengambil daftar fasilitas dari API
const getFacilities = async () => {
  try {
    isLoadingFacilities.value = true;
    const response = await axios.get('/public/facilities');
    allFacilities.value = response.data;
  } catch (error) {
    toast.error('Gagal memuat daftar fasilitas.');
  } finally {
    isLoadingFacilities.value = false;
  }
};

// Fungsi untuk memanggil API pencarian kamar
const searchRooms = async () => {
  if (!searchParams.value.check_in_date || !searchParams.value.check_out_date) {
    toast.warn("Harap isi tanggal check-in dan check-out terlebih dahulu.");
    return;
  }

  isLoading.value = true;
  hasSearched.value = true;
  availableRooms.value = [];

  try {
    const response = await axios.get('/public/available-rooms', {
      params: searchParams.value
    });
    availableRooms.value = response.data;
  } catch (error: any) {
    if (error.response && error.response.status === 422) {
      toast.error('Input tidak valid. Pastikan tanggal check-out setelah tanggal check-in.');
    } else {
      toast.error('Gagal mengambil data kamar.');
    }
    console.error(error);
  } finally {
    isLoading.value = false;
  }
};

// Fungsi untuk membuka modal booking
const openBookingModal = (room: any) => {
  selectedRoom.value = room;
  const modalEl = document.getElementById('kt_modal_booking'); // Pastikan ID modal booking benar
  if(modalEl) {
    const modal = Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
};

// Fungsi setelah booking berhasil
const handleBookingSuccess = () => {
  toast.success("Kamar berhasil dipesan! Daftar kamar akan diperbarui.");
  searchRooms();
};

// Mengambil data fasilitas saat komponen dimuat
onMounted(() => {
  getFacilities();
});
</script>
