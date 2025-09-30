<template>
  <div>
    <div class="mb-8">
      <h1 class="mb-1">Selamat Datang, {{ guestName || 'Tamu' }}!</h1>
      <p class="fs-6 text-muted">
        Nikmati pengalaman menginap Anda. Semua yang Anda butuhkan ada di sini.
      </p>
    </div>

    <div v-if="isLoading" class="d-flex justify-content-center py-20">
      <div class="spinner-border text-primary" style="width: 3rem; height: 3rem"></div>
    </div>

    <div v-else-if="!activeCheckIn" class="card shadow-sm">
      <div class="card-body d-flex flex-column flex-center text-center p-20">
        <i class="ki-duotone ki-calendar-tick fs-5x text-muted mb-5">
          <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span>
        </i>
        <h3 class="fs-3 text-gray-800">Anda Belum Check-in</h3>
        <p class="fs-6 text-muted mb-5">
          Silakan lakukan check-in di resepsionis untuk mengaktifkan semua fitur.
        </p>
        <router-link to="/user/booking-history" class="btn btn-primary">
          Lihat Riwayat Booking Saya
        </router-link>
      </div>
    </div>

    <div v-else>
      <div class="row g-6 g-xl-9">
        <div class="col-lg-7">
          <div class="card card-flush h-100 shadow-sm border border-2 border-primary">
            <div class="card-header pt-5">
              <h3 class="card-title">Status Menginap Anda</h3>
            </div>
            <div class="card-body">
              <div class="d-flex flex-wrap justify-content-between">
                <div class="d-flex align-items-center mb-5 me-5">
                  <i class="ki-duotone ki-key fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span></i>
                  <div>
                    <div class="fs-7 text-muted">Nomor Kamar</div>
                    <div class="fs-4 fw-bold">{{ activeCheckIn.room.room_number }}</div>
                  </div>
                </div>
                <div class="d-flex align-items-center mb-5 me-5">
                  <i class="ki-duotone ki-calendar-2 fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                  <div>
                    <div class="fs-7 text-muted">Tanggal Check-in</div>
                    <div class="fs-6 fw-bold">{{ formatDate(activeCheckIn.check_in_time) }}</div>
                  </div>
                </div>
                <div class="d-flex align-items-center mb-5">
                   <i class="ki-duotone ki-calendar-8 fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span></i>
                  <div>
                    <div class="fs-7 text-muted">Tanggal Check-out</div>
                    <div class="fs-6 fw-bold">{{ formatDate(activeCheckIn.booking.check_out_date) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card card-flush h-100 shadow-sm">
            <div class="card-header pt-5">
              <h3 class="card-title">Akses Cepat</h3>
            </div>
            <div class="card-body d-flex flex-column justify-content-around">
              <router-link to="/user/food-order" class="btn btn-light-primary text-start d-flex align-items-center mb-4 p-5">
                <i class="ki-duotone ki-coffee fs-2x me-4"></i>
                <span>Pesan Makanan & Minuman</span>
              </router-link>
              <router-link to="/user/room-service" class="btn btn-light-info text-start d-flex align-items-center p-5">
                <i class="ki-duotone ki-profile-circle fs-2x me-4"></i>
                <span>Minta Layanan Kamar</span>
              </router-link>
            </div>
          </div>
        </div>

        <div class="col-12">
           <div class="card card-flush shadow-sm bg-light-success">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div class="d-flex align-items-center mb-5 mb-md-0">
                        <i class="ki-duotone ki-discount fs-3x text-success me-5"><span class="path1"></span><span class="path2"></span></i>
                        <div>
                            <h4 class="fw-bold">Penawaran Spesial Untuk Anda!</h4>
                            <p class="mb-0 text-muted">Dapatkan diskon 20% untuk semua item di restoran kami hari ini.</p>
                        </div>
                    </div>
                    <router-link to="/user/food-order" class="btn btn-success flex-shrink-0">
                        Lihat Menu
                    </router-link>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import ApiService from '@/core/services/ApiService';

// --- INTERFACES ---
interface Booking {
    check_in_date: string;
    check_out_date: string;
}
interface Room {
    room_number: string;
}
interface ActiveCheckIn {
    check_in_time: string;
    room: Room;
    booking: Booking;
}
interface UserProfile {
    active_check_in: ActiveCheckIn | null;
    user: {
        name: string;
    };
}

// --- STATE ---
const isLoading = ref(true);
const profileData = ref<UserProfile | null>(null);

// --- COMPUTED PROPERTIES ---
const activeCheckIn = computed(() => profileData.value?.active_check_in);
const guestName = computed(() => profileData.value?.user.name);

// --- API FUNCTIONS ---
const fetchProfile = async () => {
  isLoading.value = true;
  try {
    const { data } = await ApiService.get('/guest/profile');
    profileData.value = data;
  } catch (error) {
    console.log("Tamu belum check-in atau terjadi error.");
    profileData.value = null;
  } finally {
    isLoading.value = false;
  }
};

// --- HELPER FUNCTIONS ---
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

// --- LIFECYCLE HOOK ---
onMounted(fetchProfile);
</script>
