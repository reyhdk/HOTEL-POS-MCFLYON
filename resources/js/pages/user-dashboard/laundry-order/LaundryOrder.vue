<template>
  <div>
    <h1 class="mb-5">Layanan Laundry</h1>
    <p class="fs-6 text-muted mb-8">
      Pakaian kotor? Jangan khawatir. Pesan layanan laundry dan petugas kami akan segera menjemputnya ke kamar Anda.
    </p>

    <!-- Loading State -->
    <div v-if="isLoading" class="d-flex justify-content-center py-10">
      <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Belum Check In State -->
    <div v-else-if="!activeRoom" class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
      <i class="ki-duotone ki-information fs-2tx text-warning me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
      <div class="d-flex flex-stack flex-grow-1">
        <div class="fw-semibold">
          <h4 class="text-gray-900 fw-bold">Fitur Belum Tersedia</h4>
          <div class="fs-6 text-gray-700">Layanan laundry hanya dapat dipesan setelah Anda melakukan check-in.</div>
        </div>
      </div>
    </div>

    <!-- Main Content (Sudah Check-In) -->
    <div v-else>
      <!-- Info Tamu -->
      <div class="card card-flush shadow-sm mb-8">
        <div class="card-body py-5">
          <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="d-flex align-items-center mb-3 mb-md-0">
              <i class="ki-duotone ki-profile-circle fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
              <div>
                <div class="fs-7 text-muted">Permintaan dari</div>
                <div class="fs-5 fw-bold">{{ guestName }}</div>
              </div>
            </div>
            <div class="d-flex align-items-center">
               <i class="ki-duotone ki-key fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span></i>
              <div>
                <div class="fs-7 text-muted">Nomor Kamar</div>
                <div class="fs-5 fw-bold">{{ activeRoom.room_number }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-6 g-xl-9">
        
        <!-- Bagian Kiri: Grid Pilihan Layanan -->
        <div class="col-lg-6">
          <div class="d-flex justify-content-between align-items-center mb-5">
            <h3 class="fw-bold text-gray-800 m-0">Pilih Layanan Laundry</h3>
          </div>
          
          <div class="row g-4">
            <div v-for="service in laundryServices" :key="service.id" class="col-sm-6">
              <!-- Service Card -->
              <div 
                class="card card-flush shadow-sm h-100 cursor-pointer border border-transparent hover-border-primary transition-all" 
                @click="confirmRequest(service)"
              >
                <div class="card-body p-6 text-center d-flex flex-column justify-content-center">
                  <i class="ki-duotone ki-basket fs-3x text-primary mb-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                  <h5 class="fw-bold text-gray-800 mb-2">{{ service.name }}</h5>
                  <div class="fs-6 fw-bold text-primary mb-3">
                    {{ formatCurrency(service.price) }} 
                    <span class="fs-8 text-muted fw-normal">/ {{ service.unit.toUpperCase() }}</span>
                  </div>
                  <div class="mt-auto">
                    <span class="badge badge-light-primary fs-8 py-2 px-3">Pilih Layanan</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Jika tidak ada layanan -->
            <div v-if="laundryServices.length === 0" class="col-12">
              <div class="card card-flush shadow-sm">
                <div class="card-body text-center py-10 text-muted">
                  Belum ada data layanan laundry yang tersedia saat ini.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bagian Kanan: Riwayat Request Laundry (Hanya Tampilkan Terbaru) -->
        <div class="col-lg-6">
          <div class="card card-flush shadow-sm h-100">
            <div class="card-header pt-5">
              <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold text-gray-800">Pesanan Terbaru Anda</span>
                <span class="text-muted mt-1 fw-semibold fs-7">Pantau proses pencucian terakhir Anda</span>
              </h3>
              <div class="card-toolbar">
                <!-- Tombol Refresh dengan Spinner -->
                <button @click="fetchMyRequests()" class="btn btn-sm btn-icon btn-light-primary" :disabled="isRefreshing">
                  <span v-if="isRefreshing" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                  <i v-else class="ki-duotone ki-arrows-circle fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
              </div>
            </div>
            <div class="card-body pt-4">
              
              <div v-if="myRequests.length === 0" class="d-flex flex-column align-items-center justify-content-center py-10">
                <i class="ki-duotone ki-time fs-4x text-muted mb-4"><span class="path1"></span><span class="path2"></span></i>
                <div class="fs-5 fw-bold text-gray-600">Belum ada pesanan laundry</div>
                <div class="fs-7 text-muted text-center mt-1">Pesanan Anda akan muncul di sini setelah petugas dipanggil.</div>
              </div>

              <!-- Menampilkan Hanya Pesanan Terbaru (Data Index 0) -->
              <div v-else-if="latestRequest" class="d-flex flex-column gap-5">
                <div class="border border-dashed border-primary bg-light-primary rounded p-5 relative">
                  <!-- Overlay Loading saat submit request baru -->
                  <div v-if="isSubmitting" class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-50 d-flex justify-content-center align-items-center" style="z-index: 10;">
                  </div>

                  <div class="d-flex flex-stack mb-3">
                    <div class="fw-bold fs-5 text-gray-800">{{ latestRequest.order_number }}</div>
                    <span :class="['badge fs-8 fw-bold', getStatusBadge(latestRequest.status).class]">
                      {{ getStatusBadge(latestRequest.status).text }}
                    </span>
                  </div>
                  
                  <div class="d-flex flex-stack mb-3">
                    <div class="text-muted fs-7">
                      <i class="ki-duotone ki-calendar me-1"><span class="path1"></span><span class="path2"></span></i> 
                      {{ formatDate(latestRequest.created_at) }}
                    </div>
                  </div>

                  <div class="separator separator-dashed border-primary my-3"></div>

                  <!-- Jika petugas belum pickup / menimbang -->
                  <div v-if="latestRequest.items.length === 0" class="text-muted fs-7 fst-italic">
                    Petugas belum melakukan input cucian Anda.
                  </div>

                  <!-- Jika petugas sudah pickup & menimbang -->
                  <div v-else>
                    <div class="fs-7 fw-bold text-gray-700 mb-2">Detail Cucian:</div>
                    <div v-for="item in latestRequest.items" :key="item.id" class="d-flex justify-content-between mb-1 fs-7">
                      <span class="text-gray-600">{{ item.service?.name }} ({{ item.qty }} {{ item.service?.unit }})</span>
                      <span class="text-gray-800 fw-semibold">{{ formatCurrency(item.subtotal) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mt-3 pt-3 border-top border-gray-300">
                      <span class="fw-bold text-gray-800">Total Tagihan</span>
                      <span class="fw-bold text-primary fs-5">{{ formatCurrency(latestRequest.total_price) }}</span>
                    </div>
                  </div>
                </div>

                <!-- Info tambahan histori -->
                <div class="text-center mt-2">
                  <span class="text-muted fs-8">Sistem hanya menampilkan pesanan aktif terakhir.</span>
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
import { ref, onMounted, computed } from "vue";
import ApiService from "@/core/services/ApiService";
import Swal from "sweetalert2";

// --- Interfaces ---
interface Room { id: number; room_number: string; }
interface LaundryService { id: number; name: string; price: number; unit: string; }
interface OrderItem { id: number; service: LaundryService; qty: number; subtotal: number; }
interface LaundryOrder { id: number; order_number: string; status: string; total_price: number; created_at: string; items: OrderItem[]; }

// --- State ---
const isLoading = ref(true);
const isRefreshing = ref(false); // State khusus untuk tombol refresh
const isSubmitting = ref(false);
const activeRoom = ref<Room | null>(null);
const guestName = ref<string>("");
const laundryServices = ref<LaundryService[]>([]);
const myRequests = ref<LaundryOrder[]>([]);

// --- Computed ---
// Mengambil pesanan index ke-0 (paling terbaru karena orderBy('created_at', 'desc') dari API)
const latestRequest = computed(() => {
  return myRequests.value.length > 0 ? myRequests.value[0] : null;
});

// --- Fetch Data ---
const fetchInitialData = async () => {
  isLoading.value = true;
  try {
    const profileRes = await ApiService.get("/guest/profile");
    activeRoom.value = profileRes.data.active_room;
    guestName.value = profileRes.data.guest_details.name;

    if (activeRoom.value) {
      await fetchLaundryServices();
      await fetchMyRequests(false);
    }
  } catch (error) {
    console.error("Gagal mengambil data profil.", error);
  } finally {
    isLoading.value = false;
  }
};

const fetchLaundryServices = async () => {
  try {
    const res = await ApiService.get("/guest/laundry/services");
    laundryServices.value = res.data.data;
  } catch (error) {
    console.error("Gagal mengambil daftar harga laundry", error);
  }
};

const fetchMyRequests = async (showSpinner = true) => {
  if (showSpinner) isRefreshing.value = true;
  try {
    const res = await ApiService.get("/guest/laundry/my-requests");
    myRequests.value = res.data.data;
  } catch (error) {
    console.error("Gagal mengambil riwayat request laundry", error);
  } finally {
    if (showSpinner) isRefreshing.value = false;
  }
};

// --- Actions ---
const confirmRequest = async (service: LaundryService) => {
  if (!activeRoom.value) return;

  const { isConfirmed } = await Swal.fire({
    title: 'Panggil Petugas?',
    html: `Anda akan memesan layanan <b>${service.name}</b>.<br><br>Petugas kami akan segera menuju kamar Anda untuk mengambil pakaian kotor Anda.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Panggil Petugas',
    cancelButtonText: 'Batal',
    customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-light" }
  });

  if (isConfirmed) {
    requestLaundry();
  }
};

const requestLaundry = async () => {
  if (!activeRoom.value) return;
  
  isSubmitting.value = true;
  try {
    await ApiService.post("/guest/laundry/request", {
      room_number: activeRoom.value.room_number
    });
    
    Swal.fire({
      title: "Terkirim!",
      text: "Permintaan berhasil dikirim. Harap tunggu di kamar, petugas akan segera datang.",
      icon: "success",
      confirmButtonText: "Selesai",
      customClass: { confirmButton: "btn btn-primary" }
    });

    // Refresh riwayat status
    await fetchMyRequests(false);
  } catch (error: any) {
    const msg = error.response?.data?.message || "Gagal memanggil petugas.";
    Swal.fire({
      text: msg,
      icon: "error",
      confirmButtonText: "Ok",
      customClass: { confirmButton: "btn btn-danger" }
    });
  } finally {
    isSubmitting.value = false;
  }
};

// --- Formatters ---
const formatCurrency = (value: number) => {
  if (!value) return "Rp 0";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value);
};

const formatDate = (dateString: string) => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleString("id-ID", {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

const getStatusBadge = (status: string) => {
  const map: Record<string, { text: string; class: string }> = {
    'requested': { text: 'Menunggu Penjemputan', class: 'badge-light-warning' },
    'picked_up': { text: 'Sedang Ditimbang', class: 'badge-light-info' },
    'processing': { text: 'Sedang Dicuci', class: 'badge-light-primary' },
    'delivered': { text: 'Selesai Dikirim', class: 'badge-light-success' }
  };
  return map[status] || { text: status, class: 'badge-light-secondary' };
};

onMounted(() => {
  fetchInitialData();
});
</script>

<style scoped>
.notice {
  transition: all 0.3s ease;
}
.hover-border-primary:hover {
  border-color: var(--bs-primary) !important;
  transform: translateY(-3px);
}
.transition-all {
  transition: all 0.3s ease;
}
</style>