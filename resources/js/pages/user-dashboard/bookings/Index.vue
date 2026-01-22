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
          <el-date-picker 
            v-model="searchParams.check_in_date" 
            type="date" 
            placeholder="Pilih tanggal" 
            class="w-100" 
            format="DD-MM-YYYY" 
            value-format="YYYY-MM-DD"
            :disabled-date="disabledDate" 
            clearable
          />
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-semibold">Tanggal Check-out:</label>
          <el-date-picker 
            v-model="searchParams.check_out_date" 
            type="date" 
            placeholder="Pilih tanggal" 
            class="w-100" 
            format="DD-MM-YYYY" 
            value-format="YYYY-MM-DD"
            :disabled-date="disabledDateCheckout"
            clearable
          />
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
            <span v-if="!isLoading">
               <i class="ki-outline ki-magnifier fs-2 p-0"></i>
            </span>
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
        <i class="ki-outline ki-calendar-remove fs-3x text-muted mb-4"></i>
        <p class="fw-bold fs-4">Maaf, Kamar Tidak Ditemukan</p>
        <p class="text-muted">Tidak ada kamar tersedia untuk tanggal atau kriteria tersebut. <br>Kemungkinan kamar sudah dibooking tamu lain.</p>
      </div>

      <div v-else-if="!hasSearched" class="text-center py-10">
        <i class="ki-outline ki-calendar-8 fs-3x text-muted mb-4"></i>
        <p class="fw-bold fs-4">Silakan Pilih Tanggal Menginap</p>
        <p class="text-muted">Tentukan tanggal check-in dan check-out untuk melihat ketersediaan.</p>
      </div>

      <div v-else class="row g-5">
        <div v-for="room in availableRooms" :key="room.id" class="col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm border border-light-subtle card-flush transition-hover">
            <div class="position-relative">
                <img :src="room.image_url || '/media/misc/pattern-4.jpg'" class="card-img-top" alt="Room Image" style="height: 200px; object-fit: cover;">
                <span class="position-absolute top-0 end-0 badge badge-success m-3">Tersedia</span>
            </div>
            
            <div class="card-body d-flex flex-column p-5">
              <span class="badge badge-light-info align-self-start mb-3">{{ room.type }}</span>
              <h5 class="card-title fs-4 fw-bold">Kamar No. {{ room.room_number }}</h5>
              <p class="card-text text-muted flex-grow-1 mb-4 text-truncate-3">{{ room.description }}</p>

              <div class="mb-4">
                <h6 class="fw-semibold text-gray-700 fs-7 mb-3">Fasilitas:</h6>
                <div v-if="room.facilities && room.facilities.length > 0" class="d-flex flex-wrap gap-2">
                  <div v-for="facility in room.facilities" :key="facility.id" class="symbol symbol-30px" v-tooltip :title="facility.name">
                    <img v-if="facility.icon_url" :src="facility.icon_url" :alt="facility.name" class="rounded" />
                    <span v-else class="symbol-label bg-light-primary text-primary fs-7 fw-bold">{{ facility.name.charAt(0) }}</span>
                  </div>
                </div>
                <div v-else class="text-muted fs-7 fst-italic">
                    Standar
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mt-auto pt-4 border-top">
                <div class="d-flex flex-column">
                    <span class="fw-bold fs-3 text-primary">{{ formatCurrency(room.price_per_night) }}</span>
                    <small class="fs-8 text-muted">per malam</small>
                </div>
                <button @click="openBookingModal(room)" class="btn btn-sm btn-primary">
                    Pesan Sekarang
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <BookingModal 
    :room="selectedRoom" 
    :booking-dates="searchParams" 
    @booking-success="handleBookingSuccess"
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import axios from '@/libs/axios';
import { toast } from 'vue3-toastify';
import BookingModal from './BookingModal.vue';
import { Modal } from 'bootstrap';

// Interface
interface Facility { id: number; name: string; icon_url: string | null; }

// State
const searchParams = ref({
  check_in_date: '',
  check_out_date: '',
  facility_ids: [] as number[],
  type: '', 
});

const availableRooms = ref<any[]>([]);
const selectedRoom = ref<any | null>(null);
const allFacilities = ref<Facility[]>([]);
const isLoadingFacilities = ref(true);
const isLoading = ref(false);
const hasSearched = ref(false);

const isSearchDisabled = computed(() => {
  return isLoading.value || !searchParams.value.check_in_date || !searchParams.value.check_out_date;
});

// Watcher untuk menghapus check-out jika check-in dihapus
watch(() => searchParams.value.check_in_date, (newVal) => {
  if (!newVal) {
    searchParams.value.check_out_date = '';
  }
});

// Helpers
const formatCurrency = (value: number) => {
  if (!value || isNaN(value)) return "Rp 0";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(value);
};

// Disable dates in past
const disabledDate = (time: Date) => {
  return time.getTime() < Date.now() - 8.64e7; // Disable yesterday
};

// Disable checkout date <= checkin date
const disabledDateCheckout = (time: Date) => {
    if (!searchParams.value.check_in_date) return disabledDate(time);
    const checkIn = new Date(searchParams.value.check_in_date);
    return time.getTime() <= checkIn.getTime();
};

const getFacilities = async () => {
  try {
    isLoadingFacilities.value = true;
    const response = await axios.get('/public/facilities');
    allFacilities.value = response.data;
  } catch (error) {
    // Silent fail optional
  } finally {
    isLoadingFacilities.value = false;
  }
};

const searchRooms = async () => {
  if (!searchParams.value.check_in_date || !searchParams.value.check_out_date) {
    toast.warn("Harap isi tanggal check-in dan check-out terlebih dahulu.");
    return;
  }
  
  if (searchParams.value.check_out_date <= searchParams.value.check_in_date) {
      toast.error("Tanggal Check-out harus setelah tanggal Check-in.");
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
      toast.error(error.response.data.message || 'Input tanggal tidak valid.');
    } else {
      toast.error('Gagal memuat data kamar. Silakan coba lagi.');
    }
  } finally {
    isLoading.value = false;
  }
};

const openBookingModal = (room: any) => {
  if (!searchParams.value.check_in_date || !searchParams.value.check_out_date) {
      toast.warn("Sesi pencarian kadaluarsa. Silakan cari ulang.");
      return;
  }
  selectedRoom.value = room;
  const modalEl = document.getElementById('kt_modal_booking');
  if(modalEl) {
    const modal = Modal.getOrCreateInstance(modalEl);
    modal.show();
  }
};

const handleBookingSuccess = () => {
  searchRooms();
};

onMounted(() => {
  getFacilities();
});
</script>

<style scoped>
.transition-hover {
    transition: transform 0.2s ease-in-out;
}
.transition-hover:hover {
    transform: translateY(-5px);
}
.text-truncate-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>